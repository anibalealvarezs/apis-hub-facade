# APIs Hub Migration & Versioning Strategy

This document outlines the official architectural guidelines for managing schema migrations, version tracking, and backwards-compatible hotfixes within the APIs Hub ecosystem.

## 1. The Core Philosophy: "Schema Version" vs "Code Version"

The APIs Hub sequencer relies on an automated, array-based pathfinder. We strictly adhere to **Option B: Add Only Required Migrations**. 

You do **not** need to create empty, "dummy" migration files for every patch or minor version release. You only create a migration routine when the database structure or core data representations actually change.

### How it works:
- If a tenant is on schema `1.14.0`, and you deploy codebase `1.14.5`, the system will attempt to upgrade from `1.14.0`.
- Because there are no schema changes between `1.14.0` and `1.14.5`, the pathfinder will evaluate the graph, find no valid bridges beyond `1.14.0`, and safely exit with `Already at the maximum available version`.
- The code runs `1.14.5`, but the schema remains securely at `1.14.0`.

---

## 2. The Many-to-One Array Funnel

When you finally introduce a schema change in a future release (e.g., `1.15.0`), you **must** account for all the patches that were released since the last migration.

You do this by utilizing the `getFromVersions(): array` method. This acts as a funnel to merge all intermediate patches back into the mainline.

```php
public function getFromVersions(): array
{
    // Funnels all tenants running patches 1.14.0 through 1.14.5 into the 1.15.0 migration!
    return ['1.14.0', '1.14.1', '1.14.2', '1.14.3', '1.14.4', '1.14.5']; 
}
```

---

## 3. Emergency Backporting & Hotfix Migrations

When a critical bug is found that requires a schema change (e.g., a missing index that causes timeouts), and it affects users on older supported versions, you must backport the migration carefully to prevent "stranded nodes."

### Scenario
You are building `1.17.x`. A critical issue is found affecting `1.14.0`. You must release a hotfix `1.14.1` containing a migration.

### Step 1: Create the Hotfix Node
Create `Upgrade_v1_14_0_to_v1_14_1.php`.
```php
public function getFromVersions(): array {
    return ['1.14.0'];
}
public function getToVersion(): string {
    return '1.14.1';
}
```
This migration class **stays in the codebase forever**. Any user on `1.14.0` who eventually upgrades to `1.17` will naturally step on this node and execute the hotfix.

### Step 2: Funnel the Next Major Release Correctly
When you write the migration for the *next* mainline release (e.g., `1.15.0`), you must **omit** `1.14.0` from its funnel to prevent users from bypassing the hotfix.
```php
// Upgrade_to_v1_15_0.php
public function getFromVersions(): array {
    // Deliberately omits '1.14.0'. Forces 1.14.0 users to cross the 1.14.1 bridge first!
    return ['1.14.1', '1.14.2', '1.14.3'];
}
```

### Step 3: Backport to Modern Users (Idempotency Rule)
Users already on `1.17.1` missed the `1.14.1` hotfix. You must release `1.17.2` containing the exact same hotfix logic.
```php
// Upgrade_v1_17_1_to_v1_17_2.php
public function getFromVersions(): array {
    return ['1.17.1'];
}
```

**CRITICAL RULE:** Because a tenant currently on `1.14.1` (who already executed the hotfix) might eventually upgrade to `1.17.2`, they will execute the `1.17.2` migration class. This means the hotfix logic will run **twice** for them over their lifetime. 

Therefore, **all backported SQL must be idempotent**.
- ✅ `CREATE INDEX IF NOT EXISTS...`
- ✅ `ALTER TABLE ADD COLUMN IF NOT EXISTS...`
- ❌ `CREATE INDEX...` (Will crash the upgrade if executed twice)

---

## 4. Nuclear Resync Declarations

Certain migrations fundamentally alter data shapes in ways that invalidate the historical cache. Instead of enforcing a destructive "Golden Rule" that wipes data on every upgrade, you declare intent per-migration.

```php
public function requiresNuclearResync(): bool
{
    return true; 
}
```

If **any** routine executed during an upgrade sequence returns `true`, the `UpgradeManager` will automatically execute `app:nuclear-resync --channel=all` precisely *after* the entire sequence safely completes.

---

## 5. Deployment Lifecycle & Worker Safety

To ensure database integrity, migrations must **never** run while background workers are active. 

If you are running `app:upgrade-version` manually during a deployment, ensure it is executed between bringing the containers online and registering the cron daemon:

```bash
# 1. Update Codebase (e.g., git pull)
# 2. Boot environment (workers are inert without crons)
docker compose up -d --build

# 3. Execute Migration Sequencer
docker compose exec -T master php bin/cli.php app:upgrade-version --current-version=1.14.0

# 4. Activate Workers
docker compose exec -T master php bin/setup-cron.php
```
