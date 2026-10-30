# Durable tenant upgrades

Tenant upgrades run in a detached host process, independent of the SSH connection
and Facade queue worker. Facade stores one UUID per operation in its deployment
log. The remote directory is `/var/www/apis-hub/upgrade-operations/<UUID>` and
contains a private runner, private output, a start marker, and an atomically
written exit result. The target environment travels through SSH stdin and is
removed from the operation directory when execution ends. Do not publish these
directories or paste their raw build logs: older tenant releases can print ENV
values. Facade stores only recognized progress stages and sanitized outcomes.

The runner requires Bash, `nohup`, and util-linux `flock`. Preflight verifies
availability before changing the tenant. A server lock serializes remote upgrades;
Facade deployment jobs also defer while a durable upgrade is active on that server.
The runner is installed outside the Git checkout, so checkout/reset cannot remove
it. Neither an already started run nor a missing previously prepared run is replayed.

`upgrades:reconcile`, scheduled every minute, dispatches short monitoring jobs for
running operations. Keep Laravel's scheduler and queue workers running. SSH loss,
queue timeout, and Facade restart leave the operation running and reconnectable.
Repeated polls without new output do not refresh the displayed progress timestamp.
The release record and successful deployment log are committed together only after
the remote process exits successfully and the authenticated API answers its
readiness request inside the updated master. Optional classification runs after
master restarts. No code rollback runs after migrations start.

## Rollout

1. Deploy Facade and run its migrations, including the durable-operation columns.
2. Restart queue workers and Octane so serialized jobs, code, and queue reservation
   settings agree. Confirm the scheduler runs `upgrades:reconcile` every minute.
3. Run the Linux detached-runner test and PostgreSQL migration suite in staging.
4. Upgrade one tenant outside sync schedules and low-activity hours. Restart its
   Facade queue worker during migration and briefly interrupt SSH. Confirm the
   same UUID resumes monitoring, master returns, and release/status finalize once.

This does not adopt an upgrade launched by an older blocking worker. Let such an
operation finish and reconcile it explicitly before rolling out another upgrade.

## Operator recovery

`reconnecting` means the server cannot currently be checked. A stale progress time
does not prove the migration stopped. Never launch another migration based on it.

`interrupted` means the host process stopped without a recorded result, or a
previously prepared operation disappeared. It intentionally keeps maintenance
active and infrastructure buttons disabled. Inspect the operation and database,
restore service when safe, and reconcile the operation explicitly. A host reboot
does not automatically replay a migration whose commit outcome is uncertain.

`verifying` means execution completed but the API has not passed readiness yet.
Inspect master and connectivity; polling continues without replaying any upgrade.
An explicit nonzero remote result reports failure and retains the existing release
pin. Check whether migration committed before deciding on recovery.

The remote runner has no one-hour limit. Storage, process failure, database errors,
and actual application readiness problems can still cause an upgrade to fail.

## Tests

Facade: run PHPUnit against `tests/Unit/Services/DurableUpgradeTest.php` plus the
existing UpgradeDowntime, UpgradeExperience and UpgradeRequestSuppression tests.
The actual detached-process test runs on Linux; Windows skips that one test.

Engine: see `docs/gsc-upgrade-postgres-validation.md` for isolated PostgreSQL tests
and the production-volume fixture. Do not use a tenant production database.
