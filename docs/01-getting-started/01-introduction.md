---
title: 'Introduction'
post_excerpt: 'What Acorn MilliCache does, why it is needed, and what you need before installing.'
menu_order: 10
---

# Introduction

Acorn MilliCache bridges [Acorn](https://roots.io/acorn/) custom routes with [MilliCache](https://millipress.com/docs/millicache/)'s Redis full-page caching. It provides a single Laravel middleware (`StoreResponse`) that captures Acorn route responses and stores them in the exact format MilliCache's `advanced-cache.php` drop-in expects.

## Why Acorn MilliCache?

Acorn MilliCache adds a `StoreResponse` middleware to your Acorn router. On a cache MISS, the middleware:

1. Checks that MilliCache is active and caching is allowed, e.g. that all cache rules pass and the request is cacheable
2. Lets the controller handle the request as usual
3. Captures the finished response (content, headers, status code)
4. Tags the entry with a `route:{name}` cache flag for targeted invalidation
5. Stores it in Redis/ValKey via MilliCache's `ResponseProcessor`

On the next request, `advanced-cache.php` serves the cached version directly — no WordPress, no Acorn/Laravel, no controller. Just Redis and PHP.

> [!NOTE]
> This package only handles cache **storage**. Cache **serving** is handled by MilliCache's `advanced-cache.php` drop-in. Cache **rules and conditions** are managed by [MilliRules](https://millipress.com/docs/millirules/) and [Acorn MilliRules](https://millipress.com/docs/acorn-millirules/).

## Prerequisites

| Requirement       | Version                   |
|-------------------|---------------------------|
| PHP               | >= 8.1                    |
| Roots Acorn       | ^4.0 or ^5.0              |
| MilliCache        | ^1.2.2                    |
| Acorn MilliRules  | optional                  |

MilliCache is declared as a Composer dependency and will be installed automatically. However, it is a WordPress plugin that must be **activated and configured** separately. See the [MilliCache installation guide](https://millipress.com/docs/millicache/getting-started/installation/) for details.

[Acorn MilliRules](https://millipress.com/docs/acorn-millirules/) is an optional companion package that adds route-aware cache rules and conditions. Install it separately if needed:

```bash
composer require millipress/acorn-millirules
```

## Next Steps

- **[Installation](./02-installation.md)** — install the package, publish the config, and verify caching works
- **[Configuration](../02-configuration/01-configuration.md)** — customize middleware groups or disable auto-registration
- **[How It Works](../03-how-it-works/01-how-it-works.md)** — understand the middleware pipeline and cache lifecycle

---

**Ready to get started?** Continue to the [Installation guide](./02-installation.md).
