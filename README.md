# MageCopilot Search Island (`magecopilot/module-search-island`)

> **Drop-in <4ms In-Memory Search Island for Hyvä Themes & Luma powered by Typesense & Preact.**

[![License: OSL-3.0](https://img.shields.io/badge/License-OSL--3.0-blue.svg)](https://opensource.org/licenses/OSL-3.0)
[![Magento 2.4.x / Mage-OS Compatible](https://img.shields.io/badge/Magento-2.4.x%20%7C%20Mage--OS-orange.svg)](https://mage-os.org)
[![Payload Size](https://img.shields.io/badge/JS%20Payload-8.17KB%20gzipped-success.svg)](https://magecopilot.com)

---

## 1. Visión General

**MageCopilot Search Island** es la **Feature Puente** oficial de MageCopilot. Permite que tiendas existentes con **Hyvä Themes** o **Luma** reemplacen instantáneamente la búsqueda nativa pesada de Magento (Elasticsearch/OpenSearch/MySQL) por una experiencia tipo Spotlight / Command Palette (`Cmd+K`) ejecutada en memoria RAM en **<4ms vía Typesense C++**.

### Beneficios Clave
- **Velocidad Extrema (<4ms):** Las consultas se resuelven directamente en la memoria del cluster Typesense en el Edge, sin pasar por PHP-FPM ni MySQL.
- **Cero Fugas de Estilos o JS (Zero-Bleed):** Empaquetado como bundle IIFE de Preact 10 (~8.1 KB gzipped) con CSS estrictamente encapsulado. No interfiere con Tailwind v3/v4 de Hyvä, ni con LESS de Luma, ni con Alpine.js.
- **Intercepción Automática:** Captura clics y foco en cualquier barra nativa (`#search`, `input[name="q"]`, `.search-input`), previniendo recargas de página lentas.
- **Navegación por Teclado:** Soporte nativo para atajos universales `Cmd+K` / `Ctrl+K`, `/`, flechas `↑`/`↓` y `Enter` para navegar a la PDP.
- **No Altera el Checkout:** El cliente permanece en su tienda actual; el carrito y el checkout continúan funcionando exactamente igual.

---

## 2. Requisitos
- PHP 8.1, 8.2, 8.3, 8.4 o 8.5
- Magento Open Source / Adobe Commerce 2.4.x o Mage-OS 1.x
- Cluster Typesense activo (o la instancia 24/7 en la nube provista por MageCopilot)
- Tema Hyvä, Luma, Blank o cualquier tema personalizado.

---

## 3. Instalación

### Opción A: Vía Composer (Recomendado)
```bash
composer require magecopilot/module-search-island
bin/magento module:enable MageCopilot_SearchIsland
bin/magento setup:upgrade
bin/magento cache:clean
```

### Opción B: Copia Directa en `app/code`
1. Clonar o copiar este directorio en `app/code/MageCopilot/SearchIsland`.
2. Habilitar el módulo:
   ```bash
   bin/magento module:enable MageCopilot_SearchIsland
   bin/magento setup:upgrade
   bin/magento cache:clean
   ```

---

## 4. Configuración en el Admin Panel

Accede en tu panel de administración a:
👉 **Stores > Configuration > MageCopilot > Search Island (Hyvä / Luma)**

| Parámetro | Valor por Defecto | Descripción |
| :--- | :--- | :--- |
| **Enable Search Island** | `Yes` | Activa o desactiva la micro-isla en el frontend. |
| **Typesense Host** | `https://cconesa-magecopilot-typesense.hf.space` | URL HTTPS del cluster Typesense. |
| **Public Search Key** | `mc_search_public_key_2026_always_free` | API Key de solo lectura para búsquedas públicas. |
| **Collection Name** | `products` | Nombre de la colección indexada en Typesense. |
| **Input Placeholder** | `Search by product name, SKU, or category...` | Texto guía dentro del modal. |
| **Product URL Suffix** | `.html` | Sufijo configurado en tu catálogo (ej. `.html` o vacío). |

---

## 5. Opción Drop-in Universal (Sin PHP / CDN / Google Tag Manager)

Para tiendas en plataformas externas o implementaciones sin acceso al servidor Magento, puede insertarse directamente en el HTML:

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

## 6. API JavaScript Global

La micro-isla expone métodos de control en `window.MageCopilotSearch`:
```javascript
// Abrir el modal desde cualquier botón personalizado
window.MageCopilotSearch.open();

// Cerrar el modal
window.MageCopilotSearch.close();

// O mediante eventos personalizados del DOM:
window.dispatchEvent(new CustomEvent('magecopilot:search:open'));
window.dispatchEvent(new CustomEvent('magecopilot:search:close'));
```

---

## 7. Licencia y Soporte
Desarrollado y mantenido por el equipo de **MageCopilot** ([https://magecopilot.com](https://magecopilot.com)).
Licencia Open Software License (OSL-3.0).
