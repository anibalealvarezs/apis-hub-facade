# 🗺️ APIs Hub: Facade to Node Integration Map

This document serves as the **Technical Audit Log** for all communication pathways between the central **Facade** (Dashboard) and the distributed **Nodes** (Workers).

## ⚖️ Integration Strategy: Two-Tier Governance

The architecture uses two distinct pathways based on the health and state of the Node.

| Path                        | Transport  | Use Case                           | Responsibility         |
| :-------------------------- | :--------- | :--------------------------------- | :--------------------- |
| **Infrastructure (Active)** | SSH        | Bootstrap, Repair, Hard Restart    | `DeployerService`      |
| **Orchestration (Active)**  | HTTP / API | Config, Logic, Soft Restart        | `RemoteEngineService`  |
| **Monitoring (Passive)**    | HTTP / API | Health Metrics, Heartbeat Registry | `MonitoringController` |

---

## 🛠️ Infrastructure Pathway (SSH)

**Service**: [`DeployerService`](file:///d:/laragon/www/apis-hub-facade/app/Services/DeployerService.php)  
This pathway bypasses the application logic to interact with the underlying OS/Docker Host.

| Command / Script          | Hub Node File        | Triggered By               | Reason                                      |
| :------------------------ | :------------------- | :------------------------- | :------------------------------------------ |
| `bash bin/full-deploy.sh` | `bin/full-deploy.sh` | "Deploy Project" Action    | Initial install or host repair.             |
| `echo 'ENV' > .env`       | `.env`               | "Save Settings" (Internal) | Force syncing credentials when API is down. |
| `git pull`                | (Node Repository)    | "Deploy Project" Action    | Native version control updates.             |

---

## 📡 Orchestration Pathway (HTTP API)

**Service**: [`RemoteEngineService`](file:///d:/laragon/www/apis-hub-facade/app/Services/RemoteEngineService.php)  
This pathway communicates with the **Hub's Management Controller** once the node is online.

| Endpoint                              | Hub Node Method       | Facade Service Method            | Logic Purpose                           |
| :------------------------------------ | :-------------------- | :------------------------------- | :-------------------------------------- |
| `POST /management/redeploy`           | `triggerRedeploy()`   | `triggerRedeploy($prj)`          | Soft containers restart.                |
| `POST /management/update-credentials` | `updateCredentials()` | `updateCredentials($prj, $data)` | Hot-reload tokens without full restart. |
| `GET /management/status`              | `getStatus()`         | `getStatus($prj)`                | Light infrastructure ping.              |
| `GET /heartbeat`                      | `getHeartbeat()`      | `getHeartbeat($prj)`             | Comprehensive health diagnostic.        |
| `GET /monitoring/data`                | (Internal Hub Logic)  | `getMonitoringData($prj)`        | Real-time job/log visibility.           |
| `POST /sync/run`                      | (Hub Sync Command)    | `triggerSync($prj, $chan)`       | Manually kick off a channel sync.       |

---

## 🩺 Monitoring Pathway (Passive Callback)

**Service**: [`MonitoringController`](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/MonitoringController.php)  
The Node "phones home" to this endpoint on a schedule.

| Incoming Route        | Node Caller            | Purpose                 | Result in Facade                    |
| :-------------------- | :--------------------- | :---------------------- | :---------------------------------- |
| `POST /api/heartbeat` | `app:health-check` CLI | Scheduled Status Report | Updates `health_metrics` DB column. |

---

## 🔓 Security Summary

- **SSH**: Standard Public Key Authentication (`id_rsa` trust).
- **Incoming Hub -> Facade**: Validated via **`X-Monitoring-Token`**.
- **Outgoing Facade -> Hub**: Validated via **`X-Admin-API-Key`**.

> [!IMPORTANT]
> This map should be reviewed whenever you add new Hub Nodes to ensure your **`ADMIN_API_KEY`** rotations are synchronized across the cluster.
