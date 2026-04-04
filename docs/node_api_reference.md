# 📘 APIs Hub: API & Orchestration Reference

This document provides a comprehensive list of all technical interfaces available for managing, monitoring, and interacting with **APIs Hub Nodes** from the **Facade** or manual CLI.

---

## 🏗️ 1. Infrastructure (SSH Orchestration)

Used by the [**DeployerService**](file:///d:/laragon/www/apis-hub-facade/app/Services/DeployerService.php) for "Hard" deployments and server management.

| Command                        | Purpose                                   | Path (on Node)            |
| :----------------------------- | :---------------------------------------- | :------------------------ |
| `bash bin/full-deploy.sh`      | Full orchestration (Docker-out-of-Docker) | `bin/full-deploy.sh`      |
| `bash bin/full-deploy-demo.sh` | Single-instance Demo deployment           | `bin/full-deploy-demo.sh` |
| `git pull origin main`         | Update source code core                   | (Repository Root)         |
| `docker compose down`          | Hard stop of all node instances           | (Repository Root)         |
| `docker compose up -d`         | Launch node instances                     | (Repository Root)         |

---

## 📡 2. Management API (Node Control)

Auth: Requires **`X-Admin-API-Key`** header.

### `POST /api/management/redeploy`

Triggers a soft background redeployment (via `full-deploy.sh`).

- **Response**: `{"success": true, "message": "Redeployment triggered in background"}`

### `POST /api/management/update-credentials`

Updates `.env` credentials and hot-reloads them.

- **Payload Example**:

    ```json
    {
        "FACEBOOK_USER_TOKEN": "new_long_lived_token_...",
        "GOOGLE_REFRESH_TOKEN": "refresh_token_abc_..."
    }
    ```

### `GET /api/management/status`

Lightweight infrastructure health check.

- **Data Fields**: `project_name`, `php_version`, `uptime`, `memory_usage`, `os`.

### `GET /api/heartbeat`

Comprehensive business-logic diagnostic.

- **Data Fields**: `status`, `db`, `redis`, `catalog` (row counts), `channels` (token status), `system`.

---

## 📊 3. Synchronization API (Logic Control)

Auth: Requires **`X-Admin-API-Key`** header.

### `POST /api/sync/run`

Manually trigger a data synchronization process for a channel.

- **Payload**: `{"channel": "facebook_marketing"}` (or `"all"`)

### `POST /api/sync/stop`

Interrupt all currently running sync jobs on the node.

- **Payload**: `{"channel": "all"}`

### `GET /api/monitoring/data`

Fetch real-time visibility into the node's task runner.

- **Returns**: Active job IDs, log tails, and queue status.

---

## 🛠️ 4. Configuration Manager API

Auth: Requires **`X-Admin-API-Key`** header.

### `POST /api/config-manager/update`

The "Master Update" for channel selection.

- **Payload (GSC example)**:

    ```json
    {
        "type": "gsc",
        "enabled": true,
        "assets": { "gsc": [{ "url": "...", "title": "..." }] }
    }
    ```

### `GET /api/config-manager/assets`

Fetches all currently available sites/pages discovered on the remote platform.

---

## 💻 5. CLI Utility Commands

Run these via `docker exec -it <container_name> php bin/cli.php ...`

| Command                     | Purpose                                   |
| :-------------------------- | :---------------------------------------- |
| `app:health-check`          | Runs heartbeats and pushes to Facade      |
| `app:initialize-entities`   | Syncs DB Catalog with YAML config         |
| `app:refresh-instances`     | Re-calculates and creates new Node splits |
| `app:setup-db`              | Core schema migration and seed engine     |
| `app:schedule-initial-jobs` | Fills the job queue with start tasks      |

---

## 🩺 6. Monitoring Receiver (Facade Only)

Auth: Requires **`X-Monitoring-Token`** header.

### `POST /api/heartbeat` (on Facade server)

Listens for Node reports and registers them in the dashboard database.

- **Security**: Token is generated per project during `DeployerService` invocation.
