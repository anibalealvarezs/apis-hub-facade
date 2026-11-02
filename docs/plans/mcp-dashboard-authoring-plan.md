# Implementation Plan: MCP-Driven Dashboard Authoring & Management

**Version:** 1.0.0  
**Date:** 2026-11-02  
**Status:** Draft / Planned  
**Repositories Involved:**
- `apis-hub-facade` (Central Application, Billing, Models, Validation, Auth & Draft Lifecycle)
- `apis-hub` (Tenant MCP Server, Internal Proxying, Execution Node)

---

## 1. Executive Summary

This feature enables AI Assistants and autonomous agents (via Model Context Protocol - MCP) to inspect existing dashboards, understand project capabilities and billing tier limits, design or refactor dashboards iteratively via a **staged draft workflow**, preview them securely in real time, and commit them atomically without generating messy intermediate states or bypassing subscription quotas.

---

## 2. Architecture & Communication Flow

```
┌────────────────────────────────────────────────────────┐
│                   AI Assistant / Agent                 │
│      (Cursor, Claude Desktop, Antigravity Agent)       │
└──────────────┬────────────────────────▲────────────────┘
               │ 1. Read / Draft / Commit│ MCP Protocol (JSON-RPC)
               ▼                        │
┌────────────────────────────────────────────────────────┐
│            Tenant MCP Server (`apis-hub`)              │
│       /var/www/apis-hub/tenants/{subdomain}/...        │
└──────────────┬────────────────────────▲────────────────┘
               │ 2. Internal Signed HTTP │ 3. Staged Draft State
               ▼ (HMAC / Tenant Secret) │
┌────────────────────────────────────────────────────────┐
│             APIs Hub Facade (Central App)              │
│   • Enforces Billing Quotas & Feature Tiers            │
│   • Manages Staged Draft Sessions (`dashboard_drafts`) │
│   • Validates Widget Schemas & Channel Bindings        │
│   • Executes Atomic Production Commits & Audit Trail   │
│   • Re-syncs `project_context.json` to Tenant Node     │
└────────────────────────────────────────────────────────┘
```

---

## 3. Key Design Pillars & Challenges

### Pillar A: Context Awareness & Prompt Criteria (Challenge 1 & 2)
The agent needs full visibility into:
1. **Connected Channels & Asset Types:** Enabled sources (Google Ads, Meta, GSC, GA4, Mailchimp, etc.) and asset scopes (Standalone accounts vs Asset Groups).
2. **Billing Restrictions & Tier Quotas:** Maximum allowed dashboards for the tenant's tier, maximum widgets per dashboard, and allowed chart/widget types.
3. **User-Provided Criteria:**
   - **Purpose:** (e.g. SEO technical audit, executive KPI overview, paid performance).
   - **Target Audience:** (Internal dev/analyst, agency client, executive C-level).
   - **Granularity & Density:** Preferred widget count (e.g. 6–12 executive tiles vs 25+ granular charts).

### Pillar B: Staged Draft Sessions (Challenge 3 & 4)
- **Problem:** Direct updates to production dashboards pollute version history and create unwanted database commits during prompt iterations.
- **Solution:** A dedicated draft lifecycle (`dashboard_drafts`):
  - `create_dashboard_draft`: Creates or forks an existing dashboard into a draft session with a 48h TTL.
  - `update_dashboard_draft`: Allows unlimited iterative adjustments (reordering, swapping metrics, updating chart types) within the draft session.
  - `preview_url`: Facade generates a secure, read-only preview URL for human inspection.
  - `commit_dashboard_draft`: The agent executes this **only when the user approves**, triggering an atomic DB transaction and a single commit in `version_history`.
  - `discard_dashboard_draft`: Cleans up the staging session if abandoned.

### Pillar C: Tenant $\leftrightarrow$ Facade Bridge & Anti-Bypass Security (Challenge 5)
- The tenant node communicates with Facade via internal authenticated endpoints:
  - Header: `X-Tenant-Signature` (HMAC SHA-256 over timestamp + payload using project's secret key).
- Quota enforcement lives in Facade's `BillingLifecycleService`:
  - The MCP server cannot forge or bypass limits. Any draft exceeding tier thresholds (e.g., maximum widgets or maximum dashboards) is rejected immediately with informative guidance so the agent can self-correct.

---

## 4. Phase-by-Phase Implementation Plan

### Phase 1: Facade Data Model & Internal API (`apis-hub-facade`)

#### 1.1 Database Migration: `dashboard_drafts` table
- `id` (bigint, PK)
- `project_id` (foreignId $\rightarrow$ `projects`, cascade)
- `dashboard_id` (foreignId $\rightarrow$ `dashboards`, nullable; populated if editing an existing dashboard)
- `draft_token` (string, unique, index)
- `name` (string)
- `description` (text, nullable)
- `target_audience` (string, default: `internal`)
- `account_mode` (enum: `standalone`, `asset_group`, `mixed`)
- `widgets` (json: array of widget specifications)
- `grid_layout` (json, nullable)
- `version` (int, default: 1)
- `expires_at` (timestamp, index)
- `created_at`, `updated_at`

#### 1.2 Internal Controller & Route: `TenantDashboardDraftController`
Internal endpoints under `routes/api.php` (`/api/internal/v1/tenant-dashboards/`):
- `GET /context`: Returns active channels, asset groups, current dashboard usage, and tier limits.
- `GET /{id}`: Returns the full spec of a live dashboard.
- `POST /draft`: Creates a new draft (validating against tier limits and channel availability).
- `PATCH /draft/{token}`: Modifies draft widgets, titles, or grid layout.
- `GET /draft/{token}/preview`: Generates a short-lived signed preview token.
- `DELETE /draft/{token}`: Discards the draft.
- `POST /draft/{token}/commit`: Commits the draft to production in a DB transaction, increments audit logs, and calls `DeployerService::syncProjectMetadata($project)`.

#### 1.3 Quota & Schema Validator: `DashboardSpecValidator`
- Validates each widget in the payload:
  - `widget_type`: Must be one of `stat`, `line_chart`, `bar_chart`, `combo_chart`, `table`, `scatter_plot`, `sparkline`.
  - `source_type`: `metric`, `kpi`, `derived_metric`, `entity`.
  - Channel / metric integrity: Validates that channels and metrics exist in `ChannelCapabilityRegistry`.
  - Quota check: Ensures `count($widgets) <= BillingLifecycleService::getMaxWidgetsPerDashboard($tier)`.

---

### Phase 2: Tenant MCP Server Tools (`apis-hub/mcp-server`)

Add the following tool definitions and handlers to `mcp-server/index.js`:

#### 2.1 `get_dashboard_authoring_context`
- **Description:** "Inspect active channels, connected asset groups, current dashboard counts, and billing tier quota limits before designing or editing a dashboard."
- **Parameters:** None.
- **Output:**
  ```json
  {
    "tier": "growth",
    "limits": {
      "max_dashboards": 10,
      "current_dashboards": 4,
      "max_widgets_per_dashboard": 25,
      "allowed_widget_types": ["stat", "line_chart", "bar_chart", "combo_chart", "table", "scatter_plot", "sparkline"]
    },
    "connected_channels": ["google_ads", "google_search_console", "google_analytics", "mailchimp"],
    "asset_groups": [{"id": 1, "name": "Main Brand Assets"}]
  }
  ```

#### 2.2 `read_dashboard_spec`
- **Description:** "Read the full structural JSON specification of an existing dashboard, including all widgets, metric configurations, and grid positions."
- **Parameters:** `dashboard_id` (integer, required).

#### 2.3 `create_dashboard_draft`
- **Description:** "Stage a new dashboard draft or stage a clone/edit of an existing dashboard for iterative modification."
- **Parameters:**
  - `name`: string
  - `description`: string (optional)
  - `base_dashboard_id`: integer (optional, to edit an existing dashboard)
  - `target_audience`: `client` | `agency` | `internal` | `executive`
  - `account_mode`: `standalone` | `asset_group` | `mixed`
  - `widgets`: array of widget specifications

#### 2.4 `update_dashboard_draft`
- **Description:** "Update the widgets, titles, or layout of an active dashboard draft session without committing to production."
- **Parameters:**
  - `draft_token`: string (required)
  - `patch`: object with `name`, `description`, `widgets`, `grid_layout`

#### 2.5 `commit_dashboard_draft`
- **Description:** "Atomically commit an approved dashboard draft into production. Enforces billing quotas, creates a single version history record, and syncs project metadata."
- **Parameters:**
  - `draft_token`: string (required)

#### 2.6 `discard_dashboard_draft`
- **Description:** "Discard and clean up an active dashboard draft session."
- **Parameters:**
  - `draft_token`: string (required)

---

### Phase 3: Preview UI & Feedback Loop (`apis-hub-facade`)

1. **Draft Preview Route:**
   - Dedicated lightweight preview route `/draft-preview/{token}` accessible via signed token.
   - Reuses the public dashboard frontend (`dashboard-renderer.js` / Alpine component) in read-only mode so users can inspect the staged layout before giving the agent the green light to commit.
2. **Diff Summary Generation:**
   - When updating an existing dashboard, `commit_dashboard_draft` returns a summary diff (`widgets_added: 2`, `widgets_removed: 1`, `widgets_modified: 3`) logged into `VersionHistory`.

---

### Phase 4: Testing & Verification Strategy

1. **Unit & Feature Tests in `apis-hub-facade`:**
   - `TenantDashboardDraftTest.php`:
     - Test draft creation, patching, and discard.
     - Test quota enforcement: Creating 11 widgets when the tier limit is 10 must return HTTP 422 with `quota_exceeded`.
     - Test atomic commit: Ensures widgets and dashboards are created within a transaction, and version history is written once.
     - Test security: Reject unsigned or invalid HMAC requests from tenant nodes.
2. **MCP Integration Tests in `apis-hub`:**
   - Test tool schema compliance in `mcp-server/index.js`.
   - Test mock agent workflow:
     `get_dashboard_authoring_context` $\rightarrow$ `create_dashboard_draft` $\rightarrow$ `update_dashboard_draft` $\rightarrow$ `commit_dashboard_draft` $\rightarrow$ assert dashboard published and `project_context.json` updated.

---

## 5. Rollout Checklist

- [ ] Create migration for `dashboard_drafts` in `apis-hub-facade`.
- [ ] Implement `TenantDashboardDraftController` and HMAC authentication middleware.
- [ ] Add `DashboardSpecValidator` with quota checks via `BillingLifecycleService`.
- [ ] Implement MCP tools in `apis-hub/mcp-server/index.js`.
- [ ] Add preview rendering for staged drafts.
- [ ] Add Pest/PHPUnit automated test suite covering quotas and atomic commit.
- [ ] Update MCP server docs and OpenAPI specifications.
