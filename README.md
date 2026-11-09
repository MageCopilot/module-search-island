# MageCopilot Search Island

[![License: OSL-3.0](https://img.shields.io/badge/License-OSL--3.0-blue.svg)](https://opensource.org/licenses/OSL-3.0)
[![Packagist](https://img.shields.io/packagist/v/magecopilot/module-search-island.svg)](https://packagist.org/packages/magecopilot/module-search-island)

Fast, lightweight search island for Magento 2, Adobe Commerce, and Mage-OS storefronts running Hyvä Themes or Luma, powered by Preact and Typesense.

## Overview

MageCopilot_SearchIsland replaces the default store search input with an instant modal dialog (Command Palette / Spotlight style triggered via `Cmd+K` or search input click). Queries are resolved directly against an in-memory Typesense cluster in milliseconds.

The frontend bundle is compiled as a self-contained Preact IIFE (~12 KB gzipped) with scoped styles, preventing CSS or JavaScript conflicts with Hyvä Tailwind, Luma LESS, or Alpine.js.

## Key Features

- Fast Search Queries: Requests hit Typesense memory directly without invoking PHP-FPM, MySQL, or Elasticsearch on keystrokes.
- Cryptographic Scoped Search Keys: Uses HMAC-SHA256 scoped keys to restrict front-end query permissions. Wholesale prices, internal margins, and embedding vectors are excluded for guest users.
- Graceful Fallback: Built-in circuit breaker detects connection timeouts or network errors. If Typesense is unreachable, queries automatically fall back to the native Magento search results page.
- Keyboard Navigation: Supports `Cmd+K` / `Ctrl+K`, forward slash (`/`), Up/Down arrow keys, and `Enter`.
- Zero Core Overwrites: Cart, quote, customer account, and checkout flows remain completely untouched.

## Compatibility

- Magento Open Source / Adobe Commerce 2.4.4+
- Mage-OS 1.0+
- Hyvä Themes (1.2+, 1.3+), Luma, Blank, or custom themes
- Typesense 27.0+ or 30.2+

## Installation

Install using Composer:

```bash
composer require magecopilot/module-search-island
bin/magento module:enable MageCopilot_SearchIsland
bin/magento setup:upgrade
bin/magento cache:clean
```

## Typesense Setup

You can run Typesense on the same host or a dedicated server using Docker:

```yaml
version: '3.8'
services:
  typesense:
    image: typesense/typesense:30.2
    container_name: typesense
    restart: unless-stopped
    ports:
      - "8108:8108"
    volumes:
      - typesense_data:/data
    command: '--data-dir /data --api-key=YOUR_ADMIN_API_KEY --enable-cors=true'

volumes:
  typesense_data:
```

## Admin Configuration

In the Magento Admin Panel, navigate to:
**Stores > Configuration > MageCopilot > Search Island (Hyvä / Luma)**

| Setting | Default | Description |
| :--- | :--- | :--- |
| Enable Search Island | Yes | Enables or disables the frontend search modal. |
| Typesense Host | `http://localhost:8108` | URL of the Typesense cluster. |
| Parent / Admin Search Key | Empty | Master search key used by Magento to generate scoped search tokens. |
| Public Search Key | Empty | Static read-only search key (used if parent key generation is disabled). |
| Collection Name | `products` | Typesense collection storing catalog documents. |
| Input Placeholder | `Search by product name, SKU, or category...` | Placeholder text inside the search modal. |
| Product URL Suffix | `.html` | Product URL suffix configured for your store. |

## JavaScript Events and API

You can open or close the modal programmatically:

```javascript
// Open the modal
window.MageCopilotSearch.open();

// Close the modal
window.MageCopilotSearch.close();

// Or dispatch custom DOM events
window.dispatchEvent(new CustomEvent('magecopilot:search:open'));
window.dispatchEvent(new CustomEvent('magecopilot:search:close'));
```

## Testing

Run unit tests via PHPUnit:

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml app/code/MageCopilot/SearchIsland/Test/Unit
```

Run static analysis:

```bash
vendor/bin/phpstan analyse --level 1 app/code/MageCopilot/SearchIsland
vendor/bin/phpcs --standard=Magento2 app/code/MageCopilot/SearchIsland
```

## License

Open Software License v3.0 (OSL-3.0).
