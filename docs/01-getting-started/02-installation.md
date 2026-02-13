---
title: 'Installation'
post_excerpt: 'Install Acorn MilliCache via Composer, publish the config file, and verify caching works.'
menu_order: 20
---

# Installation

## Requirements

Before installing, ensure you have:

- **Roots Acorn ^4.0 or ^5.0** set up in your [Bedrock](https://roots.io/bedrock/), [Sage](https://roots.io/sage/) or [Radicle](https://roots.io/radicle/) project.

## Install the Package

```bash
composer require millipress/acorn-millicache
```

This also installs [MilliCache](https://millipress.com/docs/millicache/) and [Acorn MilliRules](https://millipress.com/docs/acorn-millirules/) as Composer dependencies.

> [!IMPORTANT]
> MilliCache is a regular WordPress plugin. After Composer installs it, you still need to **activate** it in WordPress and configure it (Redis/ValKey connection, `advanced-cache.php` drop-in, etc.). See the [MilliCache installation guide](https://millipress.com/docs/millicache/getting-started/installation/).

> [!TIP]
> The service provider is registered automatically via Acorn's package discovery (`extra.acorn.providers` in `composer.json`). No manual provider registration is needed.

## Publish the Config

```bash
wp acorn vendor:publish --tag=millicache
```

This copies the config file to `config/millicache.php` in your Acorn application. The config controls whether the middleware is active and which middleware groups it attaches to.

> [!NOTE]
> Publishing the config is optional. The package works with sensible defaults out of the box: middleware enabled, attached to the `web` group.

## Verify

1. Make sure you are **logged out** (the middleware respects MilliCache's caching rules, which skip logged-in users by default)
2. Visit an Acorn route in your browser
3. Reload the page
4. Check the response headers — you should see:

```
X-MilliCache-Status: HIT
```

If you see `MISS` on every request, check that:

- MilliCache is active and its `advanced-cache.php` drop-in is in place
- The route is not excluded by a MilliCache rule or condition
- You are not logged in or sending cookies that bypass caching

## Next Steps

- **[Configuration](../02-configuration/01-configuration.md)** — add middleware groups, disable auto-registration, or register manually
- **[How It Works](../03-how-it-works/01-how-it-works.md)** — understand the full request lifecycle
