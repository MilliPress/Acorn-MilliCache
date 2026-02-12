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
        // Skip if MilliCache plugin is not active.
        if (! function_exists('millicache')) {
            return $next($request);
        }

        if (! $this->shouldCache()) {
            return $next($request);
        }

        $response = $next($request);

        if (! $this->isCacheableStatus($response)) {
            return $response;
        }

        // Re-check after controller — rules or controller code
        // may have changed the decision during request handling.
        if (! $this->shouldCache()) {
            return $response;
        }

        $this->storeInCache($response);

        return $response;
    }

    /**
     * Check if the Engine decided this request is cacheable.
     *
     * Reads the cache decision from MilliCache's Options, which
     * aggregates all MilliRules (PHP + WP) and DONOTCACHEPAGE.
     */
    protected function shouldCache(): bool
    {
        $decision = millicache()->options()->get_cache_decision();

        if ($decision && ! $decision['decision']) {
            return false;
        }

        if (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE) {
            return false;
        }

        return true;
    }

    /**
     * Check if the response status code is cacheable.
     */
    protected function isCacheableStatus(Response $response): bool
    {
        /** @var list<int> $codes */
        $codes = config('millicache.cacheable_status_codes', [200]);

        return in_array($response->getStatusCode(), $codes, true);
    }

    /**
     * Store the response in MilliCache's Redis cache.
     *
     * Mirrors the storage path of Engine\Response\Processor::process_output_buffer()
     * but reads from the Symfony Response instead of PHP's output buffer.
     */
    protected function storeInCache(Response $response): void
    {
        try {
            $engine = millicache();

            // Build a RequestProcessor to generate the hash and URL hash.
            // The Engine already cleaned $_SERVER/$_COOKIE during start(),
            // so the Hasher produces the identical hash from the same state.
            $processor = new \MilliCache\Engine\Request\Processor($engine->config());
            $hash = $processor->get_hasher()->generate();

            if (empty($hash)) {
                return;
            }

            // Collect flags (mirrors ResponseProcessor::process_output_buffer).
            $flags = $engine->flags()->get_all();
            $flags[] = 'url:' . $processor->get_url_hash();
            $flags = array_unique($flags);

            // Fallback flag when no content-specific flags were added.
            if (count($flags) <= 1) {
                $flags[] = $engine->flags()->get_key('flag');
            }

            // Get TTL/grace overrides set via millicache_set_ttl()/millicache_set_grace().
            $customTtl = $engine->options()->get_ttl();
            $customGrace = $engine->options()->get_grace();

            // Build header list from the Response, excluding cookies and
            // MilliCache's own headers (matches Writer::process_headers logic).
            $headers = $this->collectHeaders($response);

            // Create, compress, and store the cache entry via MilliCache's Writer.
            $writer = $engine->cache()->get_writer();

            $entry = $writer->create_entry(
                $response->getContent(),
                $headers,
                $response->getStatusCode(),
                $customTtl,
                $customGrace,
            );

            $entry = $writer->compress($entry);
            $writer->store($hash, $entry, $flags);
        } catch (\Throwable $e) {
            // Cache failures must never break the response.
            // Log for debugging — storage errors, config issues, etc.
            error_log('[acorn-millicache] StoreResponse failed: ' . $e->getMessage());
        }
    }

    /**
     * Collect storable headers from the Response.
     *
     * Filters out Set-Cookie and X-MilliCache-* headers, matching the
     * filtering logic in Writer::process_headers().
     *
     * @return array<string>
     */
    protected function collectHeaders(Response $response): array
    {
        $headers = [];

        foreach ($response->headers->all() as $name => $values) {
            if ($name === 'set-cookie' || str_starts_with($name, 'x-millicache')) {
                continue;
            }

            foreach ((array) $values as $value) {
                $headers[] = "$name: $value";
            }
        }

        return $headers;
    }
}
