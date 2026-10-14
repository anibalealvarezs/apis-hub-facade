# Mailchimp Data Explorer Implementation Plan

## 1. Overview
Implement a new Data Explorer page in the `apis-hub-facade` for Mailchimp data browsing. The page will adhere to the existing pattern used by other channels (e.g., Google Search Console, Facebook Marketing) utilizing Livewire for the layout, vanilla JS for interactions/caching, and the `aggregateChanneledPool` endpoint via a dedicated API controller.

## 2. Proposed Dashboard Structure

Mailchimp data centers around two main pillars: **Campaigns** (Emails sent) and **Audiences** (Lists of contacts). The metrics (Sends, Opens, Clicks) are highly hierarchical and can be broken down by Campaign and Audience.

### Section 1: Campaign & Audience Performance (Global Aggregates)
This section will follow the standard pattern of linking global metrics to hierarchical breakdowns over time.
- **Global Metric Tiles:** 
  - Total Emails Sent
  - Total Opens
  - Total Clicks
  - Average Open Rate (Derived)
  - Average Click Rate (Derived)
  - Unsubscribes
- **Trend Chart:** 
  - Line chart showing the selected metrics over the chosen date range.
- **Breakdown Tables (Tabs):**
  - **Campaigns:** Performance per individual email campaign.
  - **Audiences (Lists):** Performance metrics aggregated by audience.

### Section 2: Audience Growth / E-commerce (Disconnected Scopes - Future/Optional)
Since Mailchimp also handles e-commerce and generic audience growth which might not align directly with campaign send dates in a 1-to-1 manner, a secondary section could be added if these datascopes are synced.
- *For the initial implementation, we will focus on Section 1 (Campaigns & Audiences) as it covers the core marketing performance data.*

## 3. Required Files and Modifications

### 3.1. Frontend (Livewire & Blade)
1. **`app/Filament/App/Pages/MailchimpDashboard.php`**: 
   - Extend `Page`, use `RedirectsWhenChannelDisabled`.
   - Setup `$cluster = DataExplorer::class`.
   - Implement `loadAccounts()` to fetch enabled Mailchimp accounts from `sync_config` and `RemoteEngineService::listChanneled`.
2. **`resources/views/filament/app/pages/mailchimp-dashboard.blade.php`**:
   - Create the layout with the header (account selector, date picker, sync button).
   - Metric tiles with Alpine.js data binding (`x-data="mailchimpDashboard(...)"`).
   - Chart canvas container.
   - Data table component `<x-data-table>` with tabs for Campaigns and Audiences.

### 3.2. Frontend (Javascript)
1. **`resources/js/dashboards/mailchimp-dashboard.js`**:
   - Implement state management for `account`, `dateStart`, `dateEnd`, `activeTab`.
   - Implement caching using `sessionStorage` (e.g., `mailchimp_{tenantId}_{account}_...`).
   - Fetch methods: `fetchSummary()`, `fetchChart()`, `fetchTable()`, `fetchTrends()`.
   - Register it in `resources/js/app.js` or via standard mix/vite build process.

### 3.3. Backend (API Controller & Routes)
1. **`routes/web.php`**:
   - Add new grouped POST routes under `/api/mailchimp/` for `summary`, `chart`, `table`, and `trend`.
   - Apply middleware `['web', 'auth', 'channel.asset.access:mailchimp']`.
2. **`app/Http/Controllers/Api/MailchimpController.php`**:
   - Implement `summary()`, `chart()`, `table()`, and `trend()` methods.
   - Use `RemoteEngineService::aggregateChanneledPool()` to query the worker.
   - Map aggregations for Mailchimp metrics (`emails_sent`, `opens`, `clicks`, `unsubscribes`).
   - Group by dimensions (`campaign`, `audience`) depending on the `activeTab`.

## 4. Execution Steps
1. Create the `MailchimpController` and register the API routes.
2. Create the `MailchimpDashboard` Livewire component.
3. Create the Blade template `mailchimp-dashboard.blade.php`.
4. Create the Javascript logic in `mailchimp-dashboard.js`.
5. Link the JS file in the main application layout/build.
6. Test rendering, account selection dropdown, and mock/live API requests.
