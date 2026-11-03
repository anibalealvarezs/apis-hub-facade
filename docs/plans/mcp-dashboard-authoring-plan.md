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

The tenant MCP server (`mcp-server/index.js`) exposes the following 6 authoring tools to AI agents:

#### 2.1 `get_dashboard_authoring_context`
- **Tool Name:** `get_dashboard_authoring_context`
- **Description:** "Inspect active channels, connected asset groups, current dashboard counts, allowed widget types, and billing tier quota limitations before planning, designing, or editing a dashboard. Use this tool first to ground your dashboard proposal within the client's actual capabilities and subscription boundaries."
- **Input Schema:**
  ```json
  {
    "type": "object",
    "properties": {},
    "additionalProperties": false
  }
  ```
- **Output Contract:**
  ```json
  {
    "status": "success",
    "tier": "growth",
    "quotas": {
      "max_dashboards": 10,
      "current_dashboards": 4,
      "can_create_more_dashboards": true,
      "max_widgets_per_dashboard": 25,
      "allowed_widget_types": ["stat", "line_chart", "bar_chart", "combo_chart", "table", "scatter_plot", "sparkline"],
      "allowed_source_types": ["metric", "kpi", "derived_metric", "entity"],
      "can_create_public_dashboards": true
    },
    "connected_channels": ["google_ads", "google_search_console", "google_analytics", "mailchimp"],
    "asset_groups": [
      { "id": 1, "name": "Main Brand Assets", "channels": ["google_ads", "google_search_console"] }
    ],
    "active_custom_kpis": [
      { "id": 3, "name": "CTR Efficiency (By Page)", "calculation_type": "calculate_regression", "compatible_widgets": ["table", "scatter_plot"] }
    ],
    "active_derived_metrics": [
      { "id": 7, "name": "Paid vs Organic Search Click Share", "format": "percentage" }
    ]
  }
  ```

---

#### 2.2 `read_dashboard_spec`
- **Tool Name:** `read_dashboard_spec`
- **Description:** "Read the full structural JSON specification of an existing dashboard, including all constituent widgets, metric bindings, chart types, source channels, breakdowns, and grid positions. Use this tool before proposing modifications or full rewrites of an existing dashboard."
- **Input Schema:**
  ```json
  {
    "type": "object",
    "properties": {
      "dashboard_id": {
        "type": "number",
        "description": "The unique numerical identifier of the live dashboard to inspect."
      }
    },
    "required": ["dashboard_id"],
    "additionalProperties": false
  }
  ```
- **Output Contract:**
  ```json
  {
    "status": "success",
    "dashboard": {
      "id": 12,
      "name": "SERP & Ads Technical Audit",
      "description": "Comprehensive diagnostic dashboard tracking search performance and cannibalization.",
      "is_default": false,
      "is_public": false,
      "widgets_count": 8,
      "widgets": [
        {
          "id": 101,
          "title": "Paid vs Organic Click Share",
          "widget_type": "line_chart",
          "source_type": "derived_metric",
          "derived_metric_id": 7,
          "grid_layout": { "w": 6, "h": 4, "x": 0, "y": 0 }
        }
      ]
    }
  }
  ```

---

#### 2.3 `create_dashboard_draft`
- **Tool Name:** `create_dashboard_draft`
- **Description:** "Stage a new dashboard draft or clone an existing dashboard into a draft session for iterative refinement. Validates widget bindings and subscription quotas without writing directly to production or polluting version history. Returns a draft token and a secure preview URL."
- **Input Schema:**
  ```json
  {
    "type": "object",
    "properties": {
      "name": {
        "type": "string",
        "description": "The descriptive title of the dashboard."
      },
      "description": {
        "type": "string",
        "description": "Optional: Purpose, objectives, and guidance for viewers."
      },
      "base_dashboard_id": {
        "type": "number",
        "description": "Optional: ID of an existing dashboard to clone/edit. If provided, existing widgets will be loaded as baseline."
      },
      "target_audience": {
        "type": "string",
        "description": "Audience persona: 'client', 'agency', 'internal', or 'executive'.",
        "enum": ["client", "agency", "internal", "executive"]
      },
      "account_mode": {
        "type": "string",
        "description": "Default data scoping: 'standalone', 'asset_group', or 'mixed'.",
        "enum": ["standalone", "asset_group", "mixed"]
      },
      "widgets": {
        "type": "array",
        "description": "Ordered list of widgets to include in this draft.",
        "items": {
          "type": "object",
          "properties": {
            "title": { "type": "string" },
            "description": { "type": "string" },
            "widget_type": {
              "type": "string",
              "enum": ["stat", "line_chart", "bar_chart", "combo_chart", "table", "scatter_plot", "sparkline"]
            },
            "source_type": {
              "type": "string",
              "enum": ["metric", "kpi", "derived_metric", "entity"]
            },
            "channel": { "type": "string", "description": "Channel identifier (required if source_type is 'metric')" },
            "metric": { "type": "string", "description": "Canonical metric key (required if source_type is 'metric')" },
            "custom_kpi_id": { "type": "number", "description": "Custom KPI ID (required if source_type is 'kpi')" },
            "derived_metric_id": { "type": "number", "description": "Derived Metric ID (required if source_type is 'derived_metric')" },
            "granularity": { "type": "string", "enum": ["daily", "weekly", "monthly", "lifetime"], "default": "daily" },
            "breakdown": {
              "type": "object",
              "properties": {
                "dimension": { "type": "string" },
                "limit": { "type": "number", "default": 10 }
              }
            },
            "grid_layout": {
              "type": "object",
              "properties": {
                "w": { "type": "number", "description": "Width columns (1-12)" },
                "h": { "type": "number", "description": "Height rows" },
                "x": { "type": "number", "description": "Column offset (0-11)" },
                "y": { "type": "number", "description": "Row offset" }
              }
            }
          },
          "required": ["title", "widget_type", "source_type"]
        }
      }
    },
    "required": ["name", "widgets"],
    "additionalProperties": false
  }
  ```
- **Output Contract:**
  ```json
  {
    "status": "success",
    "draft_token": "drft_8f7a9c2b4d1e",
    "preview_url": "https://apis-hub.cloud/draft-preview/drft_8f7a9c2b4d1e?sig=abc...",
    "expires_at": "2026-11-04T09:30:00Z",
    "widget_count": 8,
    "validation_warnings": []
  }
  ```

---

#### 2.4 `update_dashboard_draft`
- **Tool Name:** `update_dashboard_draft`
- **Description:** "Make iterative modifications to an active dashboard draft session (add, remove, or edit widgets, reorder layout, or change titles) before seeking user approval. Generates no production commits."
- **Input Schema:**
  ```json
  {
    "type": "object",
    "properties": {
      "draft_token": {
        "type": "string",
        "description": "The active draft session token."
      },
      "patch": {
        "type": "object",
        "properties": {
          "name": { "type": "string" },
          "description": { "type": "string" },
          "target_audience": { "type": "string", "enum": ["client", "agency", "internal", "executive"] },
          "widgets": {
            "type": "array",
            "description": "Full replacement array of staged widgets."
          }
        }
      }
    },
    "required": ["draft_token", "patch"],
    "additionalProperties": false
  }
  ```
- **Output Contract:**
  ```json
  {
    "status": "success",
    "draft_token": "drft_8f7a9c2b4d1e",
    "preview_url": "https://apis-hub.cloud/draft-preview/drft_8f7a9c2b4d1e?sig=abc...",
    "widget_count": 9,
    "diff_summary": "Added 1 widget, updated 2 widgets"
  }
  ```

---

#### 2.5 `commit_dashboard_draft`
- **Tool Name:** `commit_dashboard_draft`
- **Description:** "Atomically publish an approved dashboard draft into production. Enforces billing quotas, creates or updates the live dashboard in a single database transaction, creates one version history entry, and synchronizes project context metadata."
- **Input Schema:**
  ```json
  {
    "type": "object",
    "properties": {
      "draft_token": {
        "type": "string",
        "description": "The draft token to publish."
      },
      "commit_message": {
        "type": "string",
        "description": "Summary of changes for the project's version audit trail (e.g. 'Initial technical SEO & PPC audit dashboard layout')."
      }
    },
    "required": ["draft_token"],
    "additionalProperties": false
  }
  ```
- **Output Contract:**
  ```json
  {
    "status": "success",
    "dashboard_id": 15,
    "name": "SERP & Ads Technical Audit",
    "live_url": "https://apis-hub.cloud/app/projects/my-tenant/dashboards/15",
    "widgets_created": 8,
    "committed_at": "2026-11-02T09:35:10Z"
  }
  ```

---

#### 2.6 `discard_dashboard_draft`
- **Tool Name:** `discard_dashboard_draft`
- **Description:** "Discard and delete an active draft session if the user decides to cancel or start over."
- **Input Schema:**
  ```json
  {
    "type": "object",
    "properties": {
      "draft_token": {
        "type": "string",
        "description": "The draft token to discard."
      }
    },
    "required": ["draft_token"],
    "additionalProperties": false
  }
  ```
- **Output Contract:**
  ```json
  {
    "status": "success",
    "message": "Draft session discarded successfully."
  }
  ```

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
