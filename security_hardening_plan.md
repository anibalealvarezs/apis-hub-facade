# APIs Hub Security & Infrastructure Hardening Plan

This document tracks the progressive implementation of security, standardization, and reliability features for the APIs Hub platform.

## 📋 Status Checklist

### 🏗️ Phase 1: Infrastructure Standardization (Topic 6) ✅

- [x] **Task 1.1**: Define the standardized `app-master` service in `docker-compose.yml`.
- [x] **Task 1.2**: Refactor Facade (Prod/Dev) to use the `app-master` architecture.
- [x] **Task 1.3**: Update Nginx gateway routing to target the new `app-master` entry points.
- [x] **✅ Validation**: PASS (All containers respond on standardized ports).

### 🔒 Phase 2: Environment Hardening (Topic 2) ✅

- [x] **Task 2.1**: Enforce `APP_DEBUG=false` and production logging across all servers.
- [x] **Task 2.2**: Configure Log Rotation for container logs.
- [x] **Task 2.3**: Implement a Global Exception Mask for Production errors.
- [x] **✅ Validation**: PASS (Logs correctly generated, sterile 500 errors).

### 🆔 Phase 3: User Identity & Spam Protection (Topic 1 & 5) ✅

- [x] **Task 3.1**: Integrate Google reCAPTCHA v3 on Login/Register forms.
- [x] **Task 3.2**: Configure AWS SES for email verification.
- [x] **Task 3.3**: Enforce `MustVerifyEmail` middleware on all user dashboards.
- [x] **✅ Validation**: PASS (Verified registration and login flow).

### 🛡️ Phase 4: API Resilience (Topic 3 & 4) ✅

- [x] **Task 4.1**: Implement Tiered Rate Limiting (Core: RoutingCore).
- [x] **Task 4.2**: Enable Gzip Compression (Facade: Nginx).
- [x] **Task 4.3**: Enable zlib Output Compression (Core: PHP).
- [x] **✅ Validation**: PASS (Full API test suite verified in Docker).

### 🧠 Phase 5: Intelligent Orchestration (Dynamic Resource Mgmt) ✅

- [x] **Task 5.1**: Implement `isMaster` helper to identify administrative context.
- [x] **Task 5.2**: Create Container Lifecycle API in Core (ManagementController).
- [x] **Task 5.3**: Implement "Auto-Idle" Scale-Down logic for history containers (Core: ScaleDownCommand).
- [x] **Task 5.4**: Add manual "Power Toggle" buttons for history containers in the Admin UI (Facade: DataSync).
- [x] **✅ Validation**: PASS (End-to-end container lifecycle management verified).

### 🌐 Phase 6: Public API Infrastructure (Distributed Access) ✅

- [x] **Task 6.1**: Implement API Key Generation & Revocation in Facade (per Project).
- [x] **Task 6.2**: Create standardized `PublicApiKeyMiddleware` for Client Servers (`apis-hub` code).
- [x] **Task 6.3**: Scoping: Define the `/api/v1/public` prefix and strictly isolate it from Admin routes.
- [x] **Task 6.4**: Sync: Build the secure "Key Push" mechanism from Facade to deployed Client Servers.
- [x] **✅ Validation**: PASS (Auth verified for both valid and invalid keys).

---

### 📝 Notes & Decisions

- **Master Container**: We will use a dedicated PHP-FPM container named `${CONTAINER_PREFIX}-master` to handle all non-job traffic and CLI executions. This divorces public access from background worker stability.
- **Scale Control**: Historical containers (regex `-[0-9]{4}-[0-9]{2}$`) are now ephemeral and will automatically shut down after 15 minutes of inactivity to preserve node resources.
