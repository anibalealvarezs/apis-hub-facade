# Tenant request coordination

Read-only aggregation, KPI, entity-list, and attribute-discovery calls pass through `RemoteRequestCoordinator`. Each project has a shared slot pool. Identical requests are coalesced by project, actor permission scope, operation, and canonicalized payload; associative key order is normalized while list order is preserved. Successful results are shared for two seconds. Errors are never cached. Management/mutation calls continue to use `execute()` directly and receive no automatic retries.

The default shared cache store is Redis. Do not configure an in-process array store in production; the coordinator rejects it. Cache failures produce a temporary `503` rather than removing the resource protection. Locks are owner-specific and expire. Their duration includes all configured HTTP attempts, bounded retry delays, and a cleanup margin.

| Environment setting | Default |
|---|---:|
| `REMOTE_REQUEST_CACHE_STORE` | `redis` |
| `REMOTE_MAX_CONCURRENT_REQUESTS` | 4 per project |
| `REMOTE_REQUEST_LEASE_SECONDS` | 120 minimum |
| `REMOTE_REQUEST_QUEUE_WAIT_MS` | 750 |
| `REMOTE_REQUEST_COALESCE_WAIT_MS` | 1500 |
| `REMOTE_REQUEST_COALESCE_TTL_SECONDS` | 2 |
| `REMOTE_REQUEST_MAX_RETRIES` | 1, hard maximum 2 |
| `REMOTE_REQUEST_MAX_RETRY_DELAY_MS` | 1500 |

Only read-only calls retry `429`/`503`. Retries honor integer and HTTP-date `Retry-After` headers, use exponential backoff with jitter, and refuse to sleep past the configured delay ceiling. Longer delays are returned to the caller. `RemoteRequestBackpressure` preserves the HTTP `429`/`503` and `Retry-After` even when an older controller would otherwise unwrap an upstream error as empty metrics. After a capacity denial, further read calls in that same facade request are skipped.

The facade sends an HMAC actor identifier to the tenant. Signed-in users have separate budgets; background calls share a system identity and anonymous calls use an anonymous identity. No raw user IDs are sent in this header. The tenant trusts it only with admin authentication.

Data Explorer attribute edits wait for one second of inactivity. Controls remain disabled/inert until all metadata/data requests settle, including queued calls. Explorer HTTP requests run two at a time, with at most 32 waiting. Widget rendering retains four concurrent requests with a bounded 100-item queue. Widget retries honor `Retry-After`, have at most three short attempts, and at most two automatic delayed reloads; the user can retry manually after that.

Deploy the tenant resource policy and facade together, rebuild Laravel's configuration cache, ensure both Redis connections are shared across their respective PHP workers, and tune the limits using admission/rejection counters and observed query durations. No Composer manifest changes or database migrations are required.

Livewire transient capacity and gateway failures (429, 502–504, 520–526) use a nonblocking connection notice instead of Livewire's HTML error dialog. Livewire still runs its normal failure cleanup, and its request pool can complete without the repeated `showModal()` exception. Existing polling pauses for 30 seconds, doubles after consecutive failures, and caps at five minutes with jitter. A successful request after polling resumes clears the notice. User actions are never replayed automatically; authorization and session-expiry responses retain Livewire's normal handling. This improves recovery; diagnosing the origin of a Cloudflare 520 still requires origin/proxy logs.
