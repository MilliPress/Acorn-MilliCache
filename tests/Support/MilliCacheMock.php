<?php

/**
 * Lightweight mock for the global millicache() function.
 *
 * Returns $this from all chain methods (flags(), options(), response(),
 * clear()) so a single instance satisfies every fluent call the
 * middleware and service provider make.
 */
class MilliCacheMock
{
    public static ?self $instance = null;

    /** @var list<string> */
    public array $addedFlags = [];

    /** @var list<string> */
    public array $clearedPatterns = [];

    public bool $cachingAllowed = true;

    public bool $storeThrows = false;

    public int $storeCalled = 0;

    public bool $executeQueueCalled = false;

    public function flags(string $flag = ''): self
    {
        if ($flag !== '') {
            $this->clearedPatterns[] = $flag;
        }

        return $this;
    }

    public function options(): self
    {
        return $this;
    }

    public function response(): self
    {
        return $this;
    }

    public function clear(): self
    {
        return $this;
    }

    public function add(string $flag): void
    {
        $this->addedFlags[] = $flag;
    }

    public function is_caching_allowed(): bool
    {
        return $this->cachingAllowed;
    }

    public function get_ttl(): int
    {
        return 3600;
    }

    public function get_grace(): int
    {
        return 60;
    }

    /**
     * @param  array<string>  $headers
     */
    public function store(string $content, array $headers, int $status, int $ttl, int $grace): array
    {
        $this->storeCalled++;

        if ($this->storeThrows) {
            throw new \RuntimeException('Redis connection refused');
        }

        return [];
    }

    public function execute_queue(): bool
    {
        $this->executeQueueCalled = true;

        return true;
    }
}

if (! function_exists('millicache')) {
    function millicache(): MilliCacheMock
    {
        return MilliCacheMock::$instance;
    }
}
