<?php

namespace MilliPress\AcornMilliCache\Console\Commands;

use Illuminate\Console\Command;

class CacheStatusCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'millicache:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show MilliCache middleware and storage status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('MilliCache Status');
        $this->newLine();

        $this->reportPlugin();
        $this->reportStorage();
        $this->reportMiddleware();
        $this->reportCacheableCodes();
        $this->reportMilliRules();

        return self::SUCCESS;
    }

    /**
     * Report MilliCache plugin availability.
     */
    protected function reportPlugin(): void
    {
        $active = function_exists('millicache');

        $this->components->twoColumnDetail(
            'MilliCache Plugin',
            $active
                ? '<fg=green>Active</>'
                : '<fg=red>Not Active</>'
        );
    }

    /**
     * Report storage backend connection status.
     */
    protected function reportStorage(): void
    {
        if (! function_exists('millicache')) {
            $this->components->twoColumnDetail('Storage Backend', '<fg=yellow>Unknown</>');

            return;
        }

        try {
            $status = millicache()->storage()->get_status();

            if (! empty($status['connected'])) {
                $server = $status['info']['Server']['redis_version'] ?? 'unknown';
                $this->components->twoColumnDetail(
                    'Storage Backend',
                    "<fg=green>Connected</> (v{$server})"
                );
            } else {
                $error = $status['error'] ?? 'connection failed';
                $this->components->twoColumnDetail(
                    'Storage Backend',
                    "<fg=red>Unavailable</> ({$error})"
                );
            }
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail(
                'Storage Backend',
                '<fg=red>Error</> (' . $e->getMessage() . ')'
            );
        }
    }

    /**
     * Report middleware configuration.
     */
    protected function reportMiddleware(): void
    {
        /** @var bool $enabled */
        $enabled = config('millicache.middleware.enabled', true);

        /** @var list<string> $groups */
        $groups = config('millicache.middleware.groups', ['web']);

        $this->components->twoColumnDetail(
            'Middleware',
            $enabled
                ? '<fg=green>Enabled</> [' . implode(', ', $groups) . ']'
                : '<fg=yellow>Disabled</>'
        );
    }

    /**
     * Report cacheable status codes.
     */
    protected function reportCacheableCodes(): void
    {
        /** @var list<int> $codes */
        $codes = config('millicache.cacheable_status_codes', [200]);

        $this->components->twoColumnDetail(
            'Cacheable Status Codes',
            implode(', ', $codes)
        );
    }

    /**
     * Report MilliRules availability.
     */
    protected function reportMilliRules(): void
    {
        $available = class_exists(\MilliRules\MilliRules::class);

        $this->components->twoColumnDetail(
            'MilliRules',
            $available
                ? '<fg=green>Available</>'
                : '<fg=yellow>Not Available</>'
        );
    }
}
