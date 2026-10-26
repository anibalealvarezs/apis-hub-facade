# Geolocation & Choropleth World Map Implementation Plan

> **Scope:** APIs Hub Facade (`apis-hub-facade`) & Aggregation Engine (`apis-hub`)  
> **Primary Channels:** Google Search Console (GSC), Google Analytics 4 (GA4), and future-ready for Shopify, Meta Leads, and Klaviyo  
> **Visual Primitive:** Interactive Choropleth World Vector Map with Local Rendering (No external public API dependencies)

---

## 🗺️ Progression Dynamic & State Tracker

| Stage | Focus Area | Status | Target Package |
| :--- | :--- | :--- | :--- |
| **Phase 0** | Library & Architectural Comparison (Local vs Google Maps) | `COMPLETED` | Architecture Specification |
| **Stage 1** | Asset Bundling & Lightweight Vector Map Integration | `PENDING` | `D:\laragon\www\apis-hub-facade` |
| **Stage 2** | Backend Country Aggregation Endpoints (`apis-hub` & Facade) | `PENDING` | `D:\laragon\www\apis-hub` & Facade Controller |
| **Stage 3** | Data Explorer Map Component & Dashboard Wiring (GSC & GA4) | `PENDING` | `D:\laragon\www\apis-hub-facade` |
| **Stage 4** | Quality Gate & Cross-Browser Verification | `PENDING` | Facade Testing |

---

## 🔍 Phase 0: Architectural Analysis: Local Vector Maps vs. Google Maps

### 0.1 Local SVG / Canvas Vector Map vs. Google Maps JavaScript API

| Criteria | **Local Vector Map (e.g. jsVectorMap / Chart.js Geo)** | **Google Maps JavaScript API** |
| :--- | :--- | :--- |
| **External Dependencies** | **ZERO.** Self-contained in npm bundle / assets. | Requires loading external script from `maps.googleapis.com`. |
| **API Keys & Billing Risk** | **No API key needed.** Free forever, zero quota limits. | Requires GCP Project, Maps JavaScript API enabled, billing account, and per-map-load fees ($7 per 1,000 loads after quota). |
| **Security & Air-Gapping** | Works in air-gapped or strictly isolated enterprise networks. | Blocked if client firewalls restrict external Google domains. |
| **Thematic Styling (Dark/Light)** | Native SVG/Canvas styling matching Filament dark/light theme tokens directly via CSS/JS. | Custom JSON styling required; theme switches can cause redraw flicker. |
| **Performance & Footprint** | Ultra-lightweight (~30-50KB gzip for world map vector coordinates). Instant vector fill. | Heavy runtime footprint (~500KB+ JS + tile downloads). |
| **Use-Case Fit** | **PERFECT for Choropleth country data** (shading country borders based on metric values like Clicks, Sessions, Orders). | Overkill for country-level analytics; designed for street-level navigation, pins, and driving directions. |

### 0.2 Strategic Recommendation
> [!IMPORTANT]
> **Recommendation:** Adopt a self-hosted, local vector map library: **`jsVectorMap`** (or **`chartjs-chart-geo`**).
> 
> **Why?**
> 1. It operates 100% locally with **zero external API calls**, zero API key management, and zero recurring billing costs.
> 2. It renders crisp, reactive SVG vectors that effortlessly blend into APIs Hub's dark-mode UI palette.
> 3. It accepts simple key-value pairs (ISO 3166-1 alpha-2 or alpha-3 codes $\rightarrow$ metric value) with built-in tooltips and dynamic color gradients.
> 
> *When would Google Maps provide value?* Only if APIs Hub later builds a **Hyper-Local Store Locator / Field Rep Tracker** requiring real-time street tiles, satellite imagery, or geocoded routing. For SaaS country-level and regional analytics, a local vector map is infinitely superior in speed, cost, and reliability.

---

## 🔍 Channel Country Support & Normalization Audit

| Channel | Native Country Dimension | Code Format | APIs Hub Entity Mapping |
| :--- | :--- | :--- | :--- |
| **Google Search Console (GSC)** | `country` | 3-letter lowercase (`usa`, `gbr`, `deu`, `esp`, etc.) | Maps to `CountryEnum` / ISO 3166-1 alpha-3 |
| **Google Analytics 4 (GA4)** | `country` | Country Name (e.g. "United States", "Germany") or ISO | Converted to ISO alpha-2/3 via `CountryEnum` |
| **Mailchimp** | `country_code` (on list members / campaign opens) | 2-letter uppercase (`US`, `GB`, `ES`) | Converted via `CountryEnum` |
| **Shopify (Future)** | `shipping_address.country_code` | 2-letter uppercase (`US`, `CA`, `GB`) | Direct match with `CountryEnum` |
| **Meta Leads (Future)** | Form `country` response | Variable / standard ISO | Converted via normalizer helper |

---

## 📌 Stage 1: Asset Bundling & Lightweight Vector Map Integration

### Tasks
- [ ] **1.1 Install `jsVectorMap` & World Map Coordinates**
  - In `d:\laragon\www\apis-hub-facade`:
    ```bash
    npm install jsvectormap @react-jvectormap/world --save
    ```
    *(Or install pure JS version `jsvectormap` and include standard world vector map)*.
- [ ] **1.2 Register Map Styles in Design System (`resources/css/app.css`)**
  - Define theme variables for choropleth gradients:
    - Base fill (unselected/zero countries): `#1f2937` (dark mode) / `#f3f4f6` (light mode).
    - Stroke/Border color: `#374151` / `#e5e7eb`.
    - Gradient scale: From subtle primary tint to deep vibrant primary (e.g., `#38bdf8` to `#0284c7`).
    - Tooltip styling matching existing Chart.js tooltips.
- [ ] **1.3 Reusable Map Wrapper Component**
  - Create `resources/js/components/choropleth-map.js`:
    - Handles initialization, theme switching (light/dark), tooltips, and data reactivity (`updateData(countryValues, metricLabel)`).

---

## 📌 Stage 2: Backend Country Aggregation Endpoints (`apis-hub` & Facade)

### Tasks
- [ ] **2.1 Standardize Country Aggregation in `apis-hub`**
  - Ensure `MetricsProcessor` / `RemoteEngineService::aggregateChanneledPool()` handles `breakdown: 'country'` for supported channels (`gsc`, `ga4`).
  - Output standard conformed response:
    ```json
    {
      "status": "success",
      "metric": "clicks",
      "data": [
        { "country_code": "US", "country_name": "United States", "value": 14250 },
        { "country_code": "GB", "country_name": "United Kingdom", "value": 6820 },
        { "country_code": "ES", "country_name": "Spain", "value": 3190 }
      ]
    }
    ```
- [ ] **2.2 Facade API Route & Controller**
  - Expose `/api/{channel}/geo-distribution` in `routes/web.php` guarded by `channel.asset.access:{channel}`.
  - Implement caching in `RemoteEngineService` using session-based hash for snappy map hover interactions.

---

## 📌 Stage 3: Data Explorer Map Integration (GSC & GA4)

### Tasks
- [ ] **3.1 Google Search Console Data Explorer (`resources/views/filament/app/pages/google-search-console-dashboard.blade.php`)**
  - Add a dedicated **"Geographic Distribution" (Map View)** section:
    - Toggle between **Choropleth Map** and **Country Table**.
    - Metric selector: Clicks, Impressions, CTR, Average Position.
    - Interactive tooltips showing Country name, Metric value, and % of global total.
  - Wire up in `resources/js/dashboards/gsc-dashboard.js`.
- [ ] **3.2 Google Analytics 4 Dashboard (`resources/views/filament/app/pages/google-analytics-dashboard.blade.php`)**
  - Add **Global Traffic Choropleth Map**:
    - Metric selector: Active Users, Sessions, Engagement Rate, Key Events.
    - Color scale dynamic adjustment based on selected metric.
  - Wire up in `resources/js/dashboards/ga4-dashboard.js`.

---

## 📌 Stage 4: Quality Gate & Verification

### Tasks
- [ ] **4.1 Offline / Local Rendering Verification:**
  - Disconnect network or block external scripts in DevTools $\rightarrow$ Verify map renders 100% locally from bundled assets with zero console errors.
- [ ] **4.2 Theme Switch Test:**
  - Toggle between dark mode and light mode $\rightarrow$ Verify map background, country borders, and tooltips instantly update colors without requiring full page reload.
- [ ] **4.3 Metric Slicing Test:**
  - Switch metric from "Clicks" to "Impressions" $\rightarrow$ Verify color gradient smoothly re-interpolates across all countries.
- [ ] **4.4 Country Code Alignment Test:**
  - Verify GSC 3-letter codes (`usa`) and ISO 2-letter codes (`US`) map to identical SVG polygons without missing regions.
