# Changelog

All notable changes to `magecopilot/module-search-island` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-11-17

### Added
- **Admin Endpoint Mode Detection**: Added automatic endpoint classification in `Block\Adminhtml\System\Config\Notice` (`notice.phtml`) to indicate whether the active Typesense host targets Managed Memory Grid Cloud, Local Self-Hosted Docker, or a Custom Remote Node while confirming unrestricted OSL-3.0 self-hosted operation.
- **Unit Tests**: Added `Test/Unit/Block/Adminhtml/System/Config/NoticeTest.php` covering endpoint mode classification.

### Changed
- **Frontend Bundle**: Refreshed the compiled Preact search modal bundle (`view/frontend/web/js/magecopilot-search.iife.js`).
- **License & Metadata**: Updated `LICENSE.txt` and changelog formatting.

## [1.0.1] - 2026-11-09

### Added
- Circuit breaker with graceful fallback to native Magento search when Typesense connection latency exceeds 2500ms.
- Granular administrative ACL permission resource (`MageCopilot_SearchIsland::config`).
- Exclusion of competitor customer group prices in client-side scoped search key generation.

### Changed
- Replaced destructive layout block removal with scoped CSS fallback in search result page template.
- Updated Preact IIFE search island bundle.
- Enforced strict Composer semver constraints.

## [1.0.0] - 2026-10-31

### Added
- Initial enterprise release of MageCopilot SearchIsland.
- Preact-based micro-island for instant search results and faceted filtering.
- Scoped search key generation with HMAC-SHA256 signature support.
- Native theme compatibility with Hyvä Themes and Luma.
- Customer session data section for client-side search key retrieval.

