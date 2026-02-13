# Acorn MilliCache

[MilliCache](https://github.com/MilliPress/MilliCache) integration for [Acorn](https://roots.io/acorn/) — a Laravel middleware that stores Acorn route responses in MilliCache's Redis/Valkey full-page cache for WordPress sites powered by the [Roots.io Stack](https://roots.io/).

## Requirements

| Requirement      | Version                 |
|------------------|-------------------------|
| PHP              | >= 8.1                  |
| Roots Acorn      | ^4.0 \| ^5.0            |
| MilliCache       | ^1.2.2 (auto-installed) |

## Quick Start

```bash
composer require millipress/acorn-millicache
```

Optionally publish the config:

```bash
wp acorn vendor:publish --tag=millicache
```

The middleware is registered automatically via Acorn's package discovery. On a cache MISS it captures the Acorn route response and stores it in Redis; on the next request `advanced-cache.php` serves it directly — no WordPress, no Acorn, no controller.

## Documentation

Full documentation is available at **[millipress.com/docs/acorn-millicache](https://millipress.com/docs/acorn-millicache/)** or in the [`docs/`](docs/) directory:

- [Introduction](docs/01-getting-started/01-introduction.md)
- [Installation](docs/01-getting-started/02-installation.md)
- [Configuration](docs/02-configuration/01-configuration.md)
- [How It Works](docs/03-how-it-works/01-how-it-works.md)

## Better Together: Acorn MilliRules

Acorn MilliCache stores your responses — [Acorn MilliRules](https://github.com/MilliPress/Acorn-MilliRules) gives you full control over *what happens* to them. Add route-aware conditions, HTTP response actions, redirects, header manipulation, and custom cache flags — all from expressive rule classes that are auto-discovered by Acorn.

```bash
composer require millipress/acorn-millirules
```

Scaffold a rule in seconds:

```bash
wp acorn rules:make:rule RedirectLegacyPages
```

Caching is just the beginning. See the [Acorn MilliRules documentation](https://millipress.com/docs/acorn-millirules/) to explore what's possible.

## Related

- **[MilliCache](https://github.com/MilliPress/MilliCache)** — the Redis/Valkey full-page cache engine for WordPress
- **[Acorn MilliRules](https://github.com/MilliPress/Acorn-MilliRules)** — route-aware rules, conditions, and actions for Acorn
- **[millipress.com](https://millipress.com)** — MilliPress documentation and resources

## License

GPL-2.0-or-later
