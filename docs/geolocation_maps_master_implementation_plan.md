# Geolocation Maps & Multi-Tier Visualization Implementation Plan

> **Scope:** APIs Hub Facade (`apis-hub-facade`) & Aggregation Engine (`apis-hub`)  
> **Visual Primitives:** 
> 1. **Choropleth Vector Maps (Macro/Regional):** Countries, States/Provinces (World, US, EU).
> 2. **Pinnable Cluster Maps (Micro/Human):** Physical points, customer addresses, store locations.  
> **Library Paradigm:** 100% Local / Self-Hosted Vector Rendering (`jsVectorMap` + Leaflet/OpenStreetMap local tiles). Zero mandatory external Google Maps API key requirements.

---

## 🗺️ Progression Dynamic & State Tracker

| Stage | Focus Area | Status | Target Package |
| :--- | :--- | :--- | :--- |
| **Stage 1** | Data Explorer Map UX (Segmented View vs. Table Toggle in GSC & GA4) | `COMPLETED` | `D:\laragon\www\apis-hub-facade` |
| **Stage 2** | Dashboard Widget Architecture (Choropleth & Pinnable Map Widgets) | `COMPLETED` | `D:\laragon\www\apis-hub-facade` |
| **Stage 3** | Local Vector & Pinning Engines Integration (`jsVectorMap` + Leaflet) | `COMPLETED` | Frontend Bundles (`vite`) |
| **Stage 4** | Aggregation Backend Support (`country`, `region`, `location`) | `COMPLETED` | `D:\laragon\www\apis-hub` & Facade Controller |
| **Stage 5** | Quality Gate & Bundle Verification | `VERIFIED` | Facade Testing |

---

## 🎨 1. Data Explorer UX Design: How the Map Should Live in the UI

### 1.1 The UX Dilemma
In Data Explorer pages (e.g. Google Search Console, GA4):
- The **Country** breakdown tab is currently hidden behind secondary tabs (`Queries` or `Pages` is active by default).
- Rendering a massive world map globally above the tabs pushes the main line chart and tables down below the fold, damaging executive scanning.
- Conversely, hiding the map inside the table without affordances means 80% of users will never discover it.

### 1.2 The Optimal Solution: **Context-Aware Segmented View (Table ↔ Map Toggle)**
When the user clicks the **`COUNTRIES`** (or `REGIONS`) tab in the breakdown section:
1. **Header Action Controls:** The table header displays a sleek, segmented view switch on the right side:
   ```
   [ 📋 Table View ]   [ 🗺️ Map View ]
   ```
2. **Persistence:** The user's preferred view (`table` vs `map`) is saved in `localStorage` or component state.
3. **Dual Hybrid Layout (Side-by-Side on Desktop):**
   - On widescreen monitors ($\ge$ 1280px), selecting Map View displays a **Split 60/40 View**:
     - **Left (60%):** Interactive Choropleth World/Regional Vector Map. Hovering over a country highlights its metric value. Clicking a country applies an instant filter across the entire dashboard!
     - **Right (40%):** A scrollable compact Country Leaderboard table ranking the top 10 countries with mini-bars (% of total).
   - On tablet/mobile, it cleanly stacks: Map on top, compact table underneath.
4. **Why this wins on UX:**
   - Zero layout shift or vertical bloat on default pages.
   - Extremely rich, engaging experience the moment geographic analysis is requested.
   - Seamless bidirectional interaction: clicking a country polygon in the map toggles the exact same filter as clicking a table row!

---

## 🧩 2. Custom Dashboard Builder: New Map Widget Types

To support custom dashboards (in `dashboard-renderer.js` and `DashboardWidgetDataController.php`), we introduce **two new first-class widget types**:

```
Widget Types: [tile, line_chart, bar_chart, table, gauge, sparkline, anomaly_list] 
               + [choropleth_map, pinnable_map]
```

### 2.1 Widget Type: `choropleth_map` (Macro Distribution)
- **Applicable Scopes:** Any channel/metric supporting geographic dimensions (`country`, `region`, `state`).
  - GSC: Clicks/Impressions by Country (`country`).
  - GA4: Sessions/Users by Country (`country`) or Region/State (`region`).
  - Google Ads: Spend/Conversions by Metro/DMA or Country.
  - Shopify: Gross Sales by Country or US State.
- **Configurable Controls:**
  - `geo_scope`: `'world'`, `'us_states'`, `'europe'`.
  - `metric`: `'clicks'`, `'revenue'`, `'sessions'`, `'leads'`.
  - `color_scale`: `'blue'`, `'emerald'`, `'purple'`, `'amber'`.
- **Interactivity:**
  - Hover tooltip: Country Name, Metric Value, Global Share %.
  - Click action: Cross-filters other dashboard widgets by that country/region!

### 2.2 Widget Type: `pinnable_map` (Micro / Physical Touchpoints)
- **Applicable Scopes:** Physical address entities, store locations, and localized customer events.
  - **Mailchimp:** Subscribers by coordinates (`latitude`, `longitude`).
  - **Meta Leads:** Inbound leads by city / zip coordinates.
  - **Shopify:** High-value customers or fulfillment locations by shipping postal coordinates.
  - **Google Business Profile:** Store locations and customer directions/call requests.
- **Engine:** **Leaflet.js** with localized cluster markers (`leaflet.markercluster`) and OpenStreetMap/CartoDB dark/light raster tiles.
- **Features:**
  - **Marker Clustering:** Groups thousands of customer points into numeric clusters (e.g., `(45)` in Miami, `(120)` in New York) that expand smoothly on zoom.
  - **Customer Pin Popup:** Clicking a pin reveals the **Human Journey Card**:
    - Customer Name & Email
    - Order / Lead Value
    - Timestamp & Originating Campaign

---

## 🛠️ 3. Technical Library Stack: 100% Local, Air-Gapped & Scalable

```
┌────────────────────────────────────────────────────────────────────────┐
│                   APIs Hub Frontend Mapping Stack                      │
├───────────────────────────────────┬────────────────────────────────────┤
│   MACRO (Choropleth Maps)         │   MICRO (Pinnable Maps)            │
│   • Library: jsVectorMap          │   • Library: Leaflet.js            │
│   • Format: Pure SVG vectors      │   • Format: Raster Tiles + Clusters│
│   • Assets: world.js, us-aea.js   │   • Clustering: MarkerClusterGroup │
│   • Size: ~45KB gzip              │   • Size: ~38KB gzip               │
│   • API Key: NONE (Local file)    │   • API Key: NONE (Local CartoDB)  │
└───────────────────────────────────┴────────────────────────────────────┘
```

### Why not Google Maps?
1. **Billing & Hard Quotas:** Google Maps requires a credit card and GCP project, charging $7.00 per 1,000 loads. For analytics dashboards where teams reload dashboards multiple times a day, this generates unnecessary SaaS operational friction.
2. **Local Control & Dark Mode:** `jsVectorMap` uses SVG DOM paths that inherit Filament CSS tokens directly (`fill: var(--primary-500)`). Leaflet with CartoDB Dark Matter tiles matches the Filament dark UI out-of-the-box.
3. **Privacy Compliance:** Ingesting human customer pins into Google Maps client-side can trigger enterprise GDPR/HIPAA compliance concerns. Leaflet runs purely in-browser without sending user coordinates to Google servers.

---

## 📌 Stage 1: Data Explorer Map Integration (GSC & GA4)

### Tasks
- [ ] **1.1 Install & Bundle `jsVectorMap`:**
  - In `d:\laragon\www\apis-hub-facade`:
    ```bash
    npm install jsvectormap @react-jvectormap/world @react-jvectormap/unitedstates --save
    ```
- [ ] **1.2 Create Reusable Alpine Component (`resources/js/components/choropleth-map.js`):**
  - Encapsulates `new jsVectorMap({ selector, map: 'world', series: { regions: [...] } })`.
  - Supports dynamic theme updates (`dark` vs `light`).
  - Emits custom event `country-selected` when a polygon is clicked to trigger `toggleFilter('countries', code)`.
- [ ] **1.3 Add Segmented View Toggle in GSC & GA4 Blade Templates:**
  - In `google-search-console-dashboard.blade.php`:
    - When `activeTab === 'countries'`, display the **[Table | Map]** toggle in the header.
    - Render the split 60/40 layout: interactive SVG world map on the left, top countries leaderboard on the right.
- [ ] **1.4 Wire Up Reactive State in `gsc-dashboard.js` & `ga4-dashboard.js`:**
  - Map ISO country codes (`usa`, `gbr`, `esp`) to polygon IDs.
  - When the map is clicked, toggle the filter and highlight the selected country polygon.

---

## 📌 Stage 2: Dashboard Builder Map Widgets (`choropleth_map` & `pinnable_map`)

### Tasks
- [ ] **2.1 Register New Widget Types in `DashboardWidgetDataController.php`:**
  - Support `source_type: 'choropleth_map'` and `'pinnable_map'`.
  - Format response payload:
    ```json
    {
      "type": "choropleth_map",
      "geo_level": "country",
      "metric": "clicks",
      "data": { "US": 15200, "GB": 6400, "DE": 4200, "ES": 3100 }
    }
    ```
    ```json
    {
      "type": "pinnable_map",
      "pins": [
        { "lat": 30.2672, "lng": -97.7431, "title": "Lead: Jane Doe", "value": "$150", "city": "Austin" },
        { "lat": 25.7617, "lng": -80.1918, "title": "Order #1042", "value": "$320", "city": "Miami" }
      ]
    }
    ```
- [ ] **2.2 Implement Renderers in `public/js/dashboard-renderer.js`:**
  - `renderChoroplethMap(widgetId, containerEl, data)`
  - `renderPinnableMap(widgetId, containerEl, data)` (using Leaflet and MarkerCluster).

---

## 📌 Stage 3: Aggregation Engine & Geographic Resolvers (`apis-hub`)

### Tasks
- [ ] **3.1 Country & Location Code Resolver:**
  - Ensure `MetricsProcessor` and `RemoteEngineService` resolve 3-letter GSC codes (`usa`), 2-letter ISO codes (`US`), and country names (`United States`) into standardized ISO 3166-1 alpha-2 keys for the SVG map parser.
- [ ] **3.2 Coordinates Aggregator for Pinnable Maps:**
  - Query `locations` table or `channeled_customers.data` where `lat` and `lng` are non-null.

---

## 📌 Stage 4: Multi-Level Testing & Quality Gate

### Pre-Release Verification Checklist
- [ ] **Zero External Script Dependencies:** DevTools Network panel confirms zero outbound requests to external mapping APIs (no Google Maps, Mapbox, or unbundled CDNs).
- [ ] **Dark Mode Reactivity:** Switching Filament themes immediately updates vector fills and tile styles without page reload.
- [ ] **Bidirectional Filter Test:** Clicking a country on the map updates the table, and clicking a row in the table highlights the country polygon on the map.
- [ ] **Marker Clustering Test:** Pinnable map handles 1,000+ customer points smoothly with numeric clusters expanding on zoom.
