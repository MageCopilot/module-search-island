# MageCopilot Search Island (`magecopilot/module-search-island`)

> **Drop-in <4ms In-Memory Search Island for Hyvä Themes & Luma powered by Typesense & Preact.**

[![License: OSL-3.0](https://img.shields.io/badge/License-OSL--3.0-blue.svg)](https://opensource.org/licenses/OSL-3.0)
[![Magento 2.4.x / Mage-OS Compatible](https://img.shields.io/badge/Magento-2.4.x%20%7C%20Mage--OS-orange.svg)](https://mage-os.org)
[![Payload Size](https://img.shields.io/badge/JS%20Payload-8.17KB%20gzipped-success.svg)](https://magecopilot.com)
[![Packagist](https://img.shields.io/packagist/v/magecopilot/module-search-island.svg)](https://packagist.org/packages/magecopilot/module-search-island)

---

## 1. Overview

**MageCopilot Search Island** is the official acceleration bridge for Magento 2 and Adobe Commerce. It allows stores running **Hyvä Themes** or **Luma** to instantly replace slow, resource-heavy native search (Elasticsearch/OpenSearch/MySQL) with a Spotlight / Command Palette (`Cmd+K`) interface executed directly in RAM in **<4ms via Typesense C++**.

### Key Benefits
- **Sub-4ms Instant Speed:** Queries resolve directly in memory at the Edge without hitting PHP-FPM or MySQL.
- **Zero Style or JS Bleed:** Packaged as an isolated Preact 10 IIFE bundle (~8.1 KB gzipped) with scoped CSS. Does not conflict with Hyvä Tailwind v3/v4, Luma LESS, or Alpine.js.
- **Automatic Interception:** Automatically intercepts clicks and keystrokes on native search inputs (`#search`, `input[name="q"]`, `.search-input`).
- **Universal Keyboard Shortcuts:** Full accessibility support for `Cmd+K` / `Ctrl+K`, `/`, arrow keys `↑`/`↓`, and `Enter`.
- **Checkout Untouched:** Customers remain on your existing frontend; cart, quote, and checkout workflows continue to operate as usual.

---

## 2. Requirements
- PHP 8.1, 8.2, 8.3, 8.4, or 8.5
- Magento Open Source / Adobe Commerce 2.4.x or Mage-OS 1.x
- Active Typesense instance (Self-Hosted via Docker or managed via MageCopilot Memory Grid™)
- Any theme: Hyvä, Luma, Blank, or custom.

---

## 3. Installation

### Via Composer (Recommended)
```bash
composer require magecopilot/module-search-island
bin/magento module:enable MageCopilot_SearchIsland
bin/magento setup:upgrade
bin/magento cache:clean
```

### Manual Installation
1. Clone or copy this repository into `app/code/MageCopilot/SearchIsland`.
2. Enable and register the module:
   ```bash
   bin/magento module:enable MageCopilot_SearchIsland
   bin/magento setup:upgrade
   bin/magento cache:clean
   ```

---

## 4. Typesense Infrastructure Options

You can either run your own self-hosted Typesense instance for free, or connect to MageCopilot's managed cloud.

### Option A: Self-Hosted Typesense (Free & Open-Source)
Run Typesense locally or on your own VPS with Docker:

```yaml
# docker-compose.typesense.yml
version: '3.8'
services:
  typesense:
    image: typesense/typesense:27.1
    container_name: magecopilot_typesense
    restart: unless-stopped
    ports:
      - "8108:8108"
    volumes:
      - typesense_data:/data
    command: '--data-dir /data --api-key=YOUR_TYPESENSE_API_KEY --enable-cors=true'

volumes:
  typesense_data:
```

### Option B: MageCopilot Memory Grid™ Managed Cloud ($99/mo)
For production stores requiring zero DevOps, high availability, daily catalog backups, and automated SSL:
👉 **[Get Managed Cloud on MageCopilot.com](https://magecopilot.com/products/search-island)**

---

## 5. Admin Panel Configuration

Navigate to:  
👉 **Stores > Configuration > MageCopilot > Search Island (Hyvä / Luma)**

| Parameter | Default | Description |
| :--- | :--- | :--- |
| **Enable Search Island** | `Yes` | Toggles the search island on frontend. |
| **Typesense Host** | `https://cconesa-magecopilot-typesense.hf.space` | HTTPS endpoint of the Typesense cluster. |
| **Public Search Key** | `mc_search_public_key_2026_always_free` | Read-only scoped API key for public queries. |
| **Collection Name** | `products` | Indexed catalog collection in Typesense. |
| **Input Placeholder** | `Search by product name, SKU, or category...` | Placeholder text inside modal input. |
| **Product URL Suffix** | `.html` | Store URL suffix (`.html` or empty). |

---

## 6. Universal CDN Drop-in (Optional / Headless / GTM)

For platforms or headless setups where direct PHP module installation is not required:

```html
<!-- MageCopilot Search Island Drop-in -->
<link rel="stylesheet" href="https://store.magecopilot.com/islands/magecopilot-search.css" />
<script>
  window.MAGECOPILOT_SEARCH_CONFIG = {
    typesenseHost: "https://cconesa-magecopilot-typesense.hf.space",
    typesenseKey: "mc_search_public_key_2026_always_free",
    collection: "products",
    urlSuffix: ".html"
  };
</script>
<script defer src="https://store.magecopilot.com/islands/magecopilot-search.iife.js"></script>
```

---

## 7. Global JavaScript API

Control the modal programmatically via `window.MageCopilotSearch`:

```javascript
// Open modal
window.MageCopilotSearch.open();

// Close modal
window.MageCopilotSearch.close();

// Or dispatch universal DOM events:
window.dispatchEvent(new CustomEvent('magecopilot:search:open'));
```

---

## 8. License & Commercial Support

Licensed under the **Open Software License v. 3.0 (OSL-3.0)**.  
For enterprise SLAs, multi-store support, and managed Memory Grid™ clusters, visit [magecopilot.com](https://magecopilot.com).
