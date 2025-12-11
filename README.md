# Acorn MilliCache

[MilliCache](https://github.com/MilliPress/MilliCache) integration for [Roots Acorn](https://roots.io/acorn/), [Bedrock](https://roots.io/bedrock/), and [Radicle](https://roots.io/radicle/) projects.

Seamlessly integrates enterprise-grade full-page caching with Laravel's service container and Bedrock's modern WordPress architecture.

> For complete MilliCache documentation, features, and usage, see the [main MilliCache repository](https://github.com/MilliPress/MilliCache).

## Requirements

- PHP >= 8.1
- [Roots Acorn](https://roots.io/acorn/) >= 4.0 (included in Bedrock & Radicle)
- Redis/ValKey/Dragonfly/KeyDB server

## Installation

```bash
composer require millipress/acorn-millicache
```

This automatically installs MilliCache and registers it with Acorn.

### Install Advanced Cache Drop-in

Enable the WordPress advanced-cache drop-in:

```bash
# Copy drop-in to Bedrock location
cp vendor/millipress/millicache/advanced-cache.php web/app/advanced-cache.php

# Enable in config/application.php
Config::define('WP_CACHE', true);
```

## Configuration

### Quick Start

Works out of the box with Redis on `127.0.0.1:6379`.

### Environment Variables

Add to your `.env`:

```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
MILLICACHE_TTL=3600
MILLICACHE_GRACE=600
```

### Publish Config (Optional)

```bash
wp acorn vendor:publish --provider="MilliPress\AcornMilliCache\Providers\MilliCacheServiceProvider"
```

### Environment-Specific Settings

Override per environment in `config/environments/{environment}.php`:

```php
// config/environments/development.php
Config::define('MILLICACHE_TTL', 60); // Short cache for dev
```

## Usage

All MilliCache helper functions and WP-CLI commands work as documented in the [main repository](https://github.com/MilliPress/MilliCache#usage).

### Laravel Container Access

```php
// Via helper
millicache()->clear()->urls(['https://example.com']);

// Via service container
app('millicache')->storage()->get_status();

// Via dependency injection
use MilliCache\Engine;

class MyController
{
    public function __construct(Engine $engine) { }
}
```

## Troubleshooting

**Cache not working?**

```bash
# Verify drop-in exists
ls -la web/app/advanced-cache.php

# Check Redis connection
redis-cli ping
```

**Service provider not loading?**

```bash
wp acorn package:discover
wp acorn optimize:clear
```

## Documentation

- **[MilliCache Plugin Documentation](https://github.com/MilliPress/MilliCache)** - Complete features, usage, and API reference
- [Roots Bedrock](https://roots.io/bedrock/docs/)
- [Roots Acorn](https://roots.io/acorn/docs/)
- [Roots Radicle](https://roots.io/radicle/)

## Support

- [Report Issues](https://github.com/MilliPress/acorn-millicache/issues)
- [MilliCache Plugin Issues](https://github.com/MilliPress/MilliCache/issues)

## License

GPL-2.0-or-later
