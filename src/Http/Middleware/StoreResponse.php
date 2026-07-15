<?php

namespace MilliCache\Acorn\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Stores Acorn route responses in MilliCache's Redis cache.
 *
 * Acorn custom routes exit at `parse_request`, before WordPress's
 * `template_redirect` fires. MilliCache normally hooks output buffering
 * there, so these responses are never cached on a MISS. This middleware
 * fills that gap by capturing the Laravel Response and writing it to
 * Redis in the exact format the Engine's advanced-cache.php expects.
 *
 * Cache SERVING is already handled by advanced-cache.php (runs before
 * WordPress loads). This middleware only handles cache STORAGE on MISS.
 */
class StoreResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! function_exists('millicache')) {
            return $next($request);
        }

        $response = $next($request);

        if ($this->isNonStorable($response)) {
            return $response;
        }

        if (! millicache()->check_cache_decision()) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false) {
            return $response;
        }

        $this->addFlags($request);
        $this->store($content, $response);

        return $response;
    }

    /**
     * Store the response via MilliCache's ResponseProcessor.
     *
     * Delegates hash generation, flag collection, entry creation,
     * compression, and storage to the Engine's pipeline.
     */
    protected function store(string $content, Response $response): void
    {
        try {
            millicache()->response()->store(
                $content,
                $this->collectHeaders($response),
                $response->getStatusCode(),
                millicache()->options()->get_ttl(),
                millicache()->options()->get_grace(),
            );
        } catch (Throwable $e) {
            // Cache failures must never break the response.
            error_log('[acorn-millicache] StoreResponse failed: '.$e->getMessage());
        }
    }

    /**
     * Add cache flags for the current Acorn route.
     *
     * Named routes get a 'route:{name}' flag (dots converted to colons
     * to match MilliCache's flag convention). Unnamed routes get a bare
     * 'route' fallback flag. Both are matched by the 'route*' wildcard.
     */
    protected function addFlags(Request $request): void
    {
        $name = $request->route()->getName();

        if ($name !== null) {
            millicache()->flags()->add('route:'.str_replace('.', ':', $name));
        } else {
            millicache()->flags()->add('route');
        }
    }

    /**
     * Whether the response itself forbids shared-cache storage.
     *
     * Rules can only reason about the request, so a controller that knows
     * its response must not be cached (auth-gated payload, content
     * negotiation gone sideways, error representation) says so on the
     * response via `Cache-Control: no-store` — and that always wins, even
     * over a rule's doCache(true). Only `no-store` counts: Symfony stamps
     * `no-cache, private` on every response that didn't set its own
     * Cache-Control, so treating those as non-storable would disable the
     * middleware entirely.
     */
    protected function isNonStorable(Response $response): bool
    {
        $cacheControl = (string) $response->headers->get('Cache-Control', '');

        return (bool) preg_match('/\bno-store\b/i', $cacheControl);
    }

    /**
     * Collect headers from the Response in "Key: Value" format.
     *
     * The Engine's Writer::validate_headers() handles filtering
     * Set-Cookie and X-MilliCache-* headers internally.
     *
     * @return array<string>
     */
    protected function collectHeaders(Response $response): array
    {
        $headers = [];

        foreach ($response->headers->all() as $name => $values) {
            foreach ((array) $values as $value) {
                $headers[] = "$name: $value";
            }
        }

        return $headers;
    }
}
