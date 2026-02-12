<?php

namespace MilliPress\AcornMilliCache\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if (! millicache()->options()->is_caching_allowed()) {
            return $next($request);
        }

        $response = $next($request);

        $content = $response->getContent();

        if ($content === false) {
            return $response;
        }

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
        } catch (\Throwable $e) {
            // Cache failures must never break the response.
            error_log('[acorn-millicache] StoreResponse failed: '.$e->getMessage());
        }
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
