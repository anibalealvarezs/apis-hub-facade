# Collaborator Asset Sharing via Asset Groups — Implementation Plan

## Overview

Today, collaborators are given access to assets **per channel**. The "Manage Assets" action on the
Team & Collaborators page lets an admin pick, for every active channel, either *"allow all channel
assets"* or an explicit subset of that channel's assets. Access is stored in
`project_user_allowed_assets` (one row per `project_id + user_id + channel`) and enforced at
widget-data time by intersecting the requested asset list with the per-channel allow-list.

This is **not** what the product needs. The desired unit of sharing is the **Asset Group**
(`asset_groups` / `asset_group_items`), which already exists as a first-class concept in the
dashboard builder, KPI builder, and dashboard view (group → per-channel asset resolution). Instead
of "channel A allows {a1,a2}, channel B allows all", an admin should say **"this collaborator can
access asset group(s) G1, G2…"** and every asset inside those groups (across all channels the group
covers) becomes visible to them.

**Goal of this rework**

1. Sharing is expressed as **asset groups**, never channels.
2. The **per-channel sharing UI, storage, and validation** are removed.
3. Enforcement is re-based on groups: a collaborator only sees the union of assets inside the groups
   shared with them, intersected with the project's enabled assets.
4. Existing per-channel scoping is **data-migrated** into asset groups so no access is silently lost.
5. Collaborators can no longer **bypass** scoping by referencing a group they were not given.

---

## Status — COMPLETE (2026-07-31)

All steps 1–13 implemented, verified with tests, and documented. Highlights:

- New pivot `project_user_asset_groups` (unique `project_id, user_id, asset_group_id`) and the
  `asset_access_unrestricted` boolean on `project_user`.
- New `CollaboratorAssetAccessService` is the single enforcement point (owner/editor bypass,
  `isUnrestricted`, shared-group queries, `filterAllowedAssets`).
- `DashboardWidgetDataController::show()` gate: restricted collaborators are constrained to the
  union of their shared groups' enabled assets. A fully disallowed request → `403 access_restricted`;
  a partially allowed explicit list is narrowed before reaching the engine; widgets with **no**
  asset scoping default to the shared asset set; KPI dependent/independent sources are intersected
  with the shared set (or short-circuited to `___EMPTY_GROUP___`).
- `extractAssetFilter` now intersects every source (`asset`, `assets`, `asset_group`, DM series
  `asset_filter`, `series_assets`, and the no-scope fallback) with the shared assets for restricted
  users.
- View/builder listings (`LoadsDashboardViewData`, `KpiFormBuilder`, `DashboardBuilder`,
  `DataSources`, `KpiExecuteActionBuilder`) are scoped via `getAllowedAssetGroupQuery`.
- `ManageCollaborators` exposes an "Asset access" summary column + "Manage Asset Groups" modal
  (unrestricted toggle + `CheckboxList` of project groups); legacy per-channel methods removed.
- Data migration converts existing per-channel scoping per decision §C (snapshot +
  allow-all rules) and drops the legacy table; `down()` restores from the snapshot.
- Legacy `ProjectUserAllowedAsset` model deleted; legacy migration retained for history.
- Tests: `CollaboratorAssetGroupSharingTest` (18 cases) + existing `AssetFilteringTest`
  (18 cases) and the full `tests/Feature/Analytics` suite (58 tests) pass.
- Spanish translations for the new collaborator-sharing UI strings added to `lang/es.json`.

> Note: the new migrations were validated by the test suite (SQLite in-memory). Running
> `php artisan migrate` against the docker PostgreSQL dev DB (`apis-hub-facade-db`) is a manual
> step to do in the normal dev environment.

---

## Current State — Code Map

### 1. Per-channel storage

| Piece | Location |
|---|---|
| Table `project_user_allowed_assets` | `database/migrations/2026_06_10_000002_create_project_user_allowed_assets_table.php` |
| Model `ProjectUserAllowedAsset` | `app/Models/ProjectUserAllowedAsset.php` |
| Semantics | `(project_id, user_id, channel, allowed_assets JSON NULL)` — `NULL` = allow all assets of that channel; otherwise an explicit id list. `UNIQUE(project_id, user_id, channel)`. |

### 2. Sharing UI (per-channel modal)

`app/Filament/App/Pages/ManageCollaborators.php`

- `manage_assets` action — `ManageCollaborators.php:162-219`
  - `mountUsing` (`:180-195`) — loads rows keyed by channel, fills `allow_all_{channel}` toggles and `assets_{channel}` multi-selects.
  - `action` (`:197-213`) — `updateOrCreate` one row per channel with `allowed_assets => null` (allow all) or the selected ids.
- `buildAssetScopeForm()` (`:369-397`) — renders one Filament Section per channel: `allow_all_{channel}` Toggle + `assets_{channel}` Select.
- `getActiveChannels()` (`:313-329`) — **hardcoded channel validation** (`$validChannels = ['facebook_marketing', 'facebook_organic', 'google_search_console']` filtered by `sync_config.enabled`). This is the "channels validation" to remove.
- `getAssetsForChannel()` (`:331-367`) — scans `sync_config` asset keys (`sites`, `ad_accounts`, `pages`, `locations`, `profiles`, `accounts`, `shops`, `properties`, `assets.*`) for enabled, non-lost-access assets.

### 3. Enforcement (data layer)

`app/Services/WidgetDataService.php:80-94` — `filterAllowedAssets(Project $project, int $userId, string $channel, array $assetIds)`

- No row, or `allowed_assets === null` → return all `$assetIds` (allow-all).
- Otherwise → `array_intersect($assetIds, $allowed->allowed_assets)`.

Consumers:

- `app/Http/Controllers/Api/DashboardWidgetDataController.php:79-104` — non-public dashboards; users whose project role is `project_user` get `filterAllowedAssets(...)` applied; empty result ⇒ HTTP 403 `access_restricted`.
- `app/Filament/App/Resources/DashboardResource/Traits/LoadsDashboardViewData.php:105-125` — `$getAssetsForChannel` closure; admins (`$user->role` admin/owner) get everything, everyone else is filtered.

### 4. Asset groups (already exist)

| Piece | Location |
|---|---|
| `asset_groups` (project_id, name, description) | `database/migrations/2026_07_07_163421_create_asset_groups_table.php` |
| `asset_group_items` (asset_group_id, channel, asset_id) | `database/migrations/2026_07_07_163424_create_asset_group_items_table.php` |
| Models | `app/Models/AssetGroup.php`, `app/Models/AssetGroupItem.php` |
| Resource (CRUD) | `app/Filament/App/Resources/AssetGroupResource.php` |
| `Project::assetGroups()` | `app/Models/Project.php:195-198` |
| `AssetGroup::active_items` | `app/Models/AssetGroup.php:28-72` (filters items to enabled, non-lost-access `sync_config` assets) |

Group resolution at request time already exists (this is reused for enforcement):

- `DashboardWidgetDataController::extractAssetFilter()` — `DashboardWidgetDataController.php:1888-1990`; project-scoped group lookup at `:1915-1927`, `___EMPTY_GROUP___` sentinel when empty/invalid.
- `DashboardWidgetDataController::handleKpiSource()` — dependent/independent group resolution `:1290-1320` and `:1362-1401`.
- `handleDerivedMetricSource()` — per-series group resolution via `extractAssetFilter` `:2388`.
- `LoadsDashboardViewData::getChannelAssetGroupMap()` — `:37-57`; feeds `channelAssetGroupMap` to the dashboard view JS (group → channel → asset-ids).

### 5. Group visibility today (all project groups, regardless of collaborator)

- `KpiFormBuilder::getAssetGroupOptions()` — `app/Services/Analytics/KpiFormBuilder.php:473-481` (all project groups).
- `DashboardBuilder::getAllAssetGroups()` — `app/Filament/App/Resources/DashboardResource/Pages/DashboardBuilder.php:205-216`.
- `LoadsDashboardViewData::getAllAssetGroups()` — `:24-35`.
- `DataSources::getAssetGroupsData()` — `app/Filament/App/Pages/DataSources.php:79-98`.
- `KpiExecuteActionBuilder` — resolves `dependent_asset_group` / `independent_asset_group` to asset options `:92-142`.

---

## Target Design

### Data model

**New pivot table `project_user_asset_groups`** (the only "sharing" table):

```sql
project_user_asset_groups
├── id                BIGINT UNSIGNED PK AUTO_INCREMENT
├── project_id        FK → projects (cascade delete)
├── user_id           FK → users (cascade delete)
├── asset_group_id    FK → asset_groups (cascade delete)
├── created_at        TIMESTAMP
├── updated_at        TIMESTAMP
├── UNIQUE (project_id, user_id, asset_group_id)
└── INDEX (project_id, user_id)
```

**Per-collaborator "unrestricted" flag** on the existing `project_user` pivot (project ↔ user):

```sql
ALTER TABLE project_user ADD COLUMN asset_access_unrestricted BOOLEAN NOT NULL DEFAULT TRUE;
```

Semantics:

- `asset_access_unrestricted = true` → collaborator sees **every enabled asset** (current default
  behavior preserved; equivalent to today's per-channel "Allow all" toggles, but project-wide).
- `asset_access_unrestricted = false` → collaborator sees only the **union of assets** across the
  asset groups present in `project_user_asset_groups` for that project, intersected with the
  project's enabled assets per channel (via `AssetGroup::active_items` / `getValidAssetsForChannel`).

Why a project-level flag and not a per-channel one: the sharing unit is now a group, which is
channel-agnostic by design. A channel-level "allow all" would reintroduce the exact channel
semantics being removed. For mixed old data (allow-all on one channel, restricted on another), the
data migration materializes the allow-all channel as a real group snapshot (see Step 10).

### Role semantics (recommended, to be confirmed)

| Project role | Unrestricted by default | Group scoping applies? |
|---|---|---|
| `project_owner` | yes | never (cannot be managed — existing `manage_assets` hides for owners) |
| `project_editor` | yes | never (can manage channels/groups; scoping a manager is nonsensical) |
| `project_viewer` | yes | yes, when admin turns it off |
| `project_user` | yes | yes, when admin turns it off |

Enforcement gate (**CONFIRMED, §A**): **any** project member whose role is not
`project_owner`/`project_editor` and who is **not** unrestricted is scoped — including
`project_viewer`, closing the latent viewer bypass in the widget-data controller
(`DashboardWidgetDataController.php:79-104` today only gates `project_user`).

Confirmed behavior for scoped (restricted) collaborators when **viewing**:
1. Only the asset groups **shared with them** appear in the dashboard view's group list
   (`channelAssetGroupMap` / `getAllAssetGroups`) — non-shared groups are simply not offered.
2. When a dashboard/widget does **not** use an asset-group filter, the individual assets they may
   see are the **union of assets inside their shared groups** (intersected with the project's
   enabled assets per channel) — enforced by the group-based `filterAllowedAssets` at widget-data
   time. Any asset outside their shared groups returns `access_restricted` (403) / empty.

### Enforcement points (replace per-channel logic with group logic)

1. `WidgetDataService` — replace `filterAllowedAssets()` internals (keep the public signature
   `(project, userId, channel, assetIds)` so both consumers keep compiling) with group-based
   resolution; add group-specific helpers.
2. `DashboardWidgetDataController::show()` — same call, new semantics.
3. `DashboardWidgetDataController` group-reference resolution (`extractAssetFilter`,
   `handleKpiSource`, DM path) — **validate the referenced group is shared** with the requesting
   user (or the user is owner/editor/unrestricted) before resolving it. Non-shared/missing/foreign
   group ⇒ `___EMPTY_GROUP___`. This is the bypass-prevention "validation" replacing the removed
   per-channel validation.
4. `LoadsDashboardViewData` — `$getAssetsForChannel` closure uses group-based filtering; group
   visibility methods (`getAllAssetGroups`, `getChannelAssetGroupMap`) are restricted to shared
   groups for scoped users.
5. `KpiFormBuilder::getAssetGroupOptions()`, `DashboardBuilder::getAllAssetGroups()`,
   `DataSources::getAssetGroupsData()`, `KpiExecuteActionBuilder` — same visibility restriction.

### What gets deleted

- `project_user_allowed_assets` table + `ProjectUserAllowedAsset` model.
- `ManageCollaborators`: `getActiveChannels()`, `getAssetsForChannel()`, `buildAssetScopeForm()`,
  and the per-channel modal schema/logic.
- Per-channel `channel` argument semantics in `filterAllowedAssets` (the parameter stays for the
  SQL lookup against `asset_group_items.channel`, but is no longer a *sharing* dimension).

---

## Step-by-Step Implementation

### Step 1 — Migration: create `project_user_asset_groups`

**New file:** `database/migrations/<today>_000001_create_project_user_asset_groups_table.php`

```php
Schema::create('project_user_asset_groups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('asset_group_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['project_id', 'user_id', 'asset_group_id']);
    $table->index(['project_id', 'user_id']);
});
```

### Step 2 — Migration: add the unrestricted flag to `project_user`

**New file:** `database/migrations/<today>_000002_add_asset_access_unrestricted_to_project_user_table.php`

```php
Schema::table('project_user', function (Blueprint $table) {
    $table->boolean('asset_access_unrestricted')->default(true);
});
```

`down()`: drop column; `project_user_asset_groups` `down()`: drop table.

### Step 3 — Eloquent models

- **New** `app/Models/ProjectUserAssetGroup.php` (pivot): fillable `project_id, user_id,
  asset_group_id`; `belongsTo` `project`, `user`, `assetGroup`.
- **Modify** `app/Models/ProjectUser.php`: add `asset_access_unrestricted` to `$fillable` /
  `$casts` (`'boolean'`).
- **Modify** `app/Models/User.php`: add `sharedAssetGroups(Project $project)` relationship
  (`belongsToMany(AssetGroup::class, 'project_user_asset_groups', 'user_id', 'asset_group_id')
  ->wherePivot('project_id', ...)`).
- **Modify** `app/Models/AssetGroup.php`: add `sharedWithUsers()` inverse relation (optional, for
  admin-facing "who can see this group" future work).

### Step 4 — `WidgetDataService`: group-based enforcement

**Modify:** `app/Services/WidgetDataService.php`

Replace `filterAllowedAssets()` internals (keep signature) and add helpers:

```php
public function isUnrestricted(Project $project, int $userId): bool
{
    $row = DB::table('project_user')
        ->where('project_id', $project->id)
        ->where('user_id', $userId)
        ->first();

    return $row ? (bool) $row->asset_access_unrestricted : true;
}

public function getSharedAssetGroupIds(Project $project, int $userId): array
{
    return ProjectUserAssetGroup::where('project_id', $project->id)
        ->where('user_id', $userId)
        ->pluck('asset_group_id')
        ->all();
}

public function getAllowedAssetIdsForChannel(Project $project, int $userId, string $channel): array
{
    if ($this->isUnrestricted($project, $userId)) {
        return $this->getValidEnabledAssetsForChannel($project, $channel); // all enabled
    }

    $groupIds = $this->getSharedAssetGroupIds($project, $userId);
    if (empty($groupIds)) {
        return [];
    }

    $groupAssetIds = AssetGroupItem::whereIn('asset_group_id', $groupIds)
        ->where('channel', $channel)
        ->distinct()
        ->pluck('asset_id')
        ->all();

    // Still clamp to what the project actually has enabled for the channel.
    return array_values(array_intersect($groupAssetIds, $this->getValidEnabledAssetsForChannel($project, $channel)));
}

public function filterAllowedAssets(Project $project, int $userId, string $channel, array $assetIds): array
{
    $allowed = $this->getAllowedAssetIdsForChannel($project, $userId, $channel);

    return $allowed ? array_values(array_intersect($assetIds, $allowed)) : [];
}
```

`getValidEnabledAssetsForChannel()` is the same scan already used by
`DashboardWidgetDataController::getValidAssetsForChannel()` (`:1992-2031`) and
`ManageCollaborators::getAssetsForChannel()` — extract one canonical implementation (e.g. into the
service) and have both consumers call it, deleting the duplicate `getAssetsForChannel()`.

> Behavior note: previously an *empty* `allowed_assets` row meant "deny everything for that
> channel" only when the row existed with `[]`. With groups, a scoped user with zero shared groups
> gets `[]` ⇒ all channel data blocked. Unrestricted users are untouched.

### Step 5 — Controller: replace the per-channel validation with group validation

**Modify:** `app/Http/Controllers/Api/DashboardWidgetDataController.php`

1. `show()` (`:79-104`): keep the existing role gate, replace the filtering call:
   ```php
   $allowedAssets = $this->widgetDataService->filterAllowedAssets(
       $project, $user->id, $resolvedControls['channel'], $assetList
   );
   ```
   This now resolves through shared groups. Optionally widen the gate from `project_user` to
   "any non-owner/non-editor collaborator" (Open Question §A).

2. **Group-reference validation (the core bypass fix).** Introduce one helper used by all group
   resolution sites:

   ```php
   protected function resolveGroupForUser(Project $project, int|string $groupId): ?AssetGroup
   {
       $group = AssetGroup::where('id', $groupId)->where('project_id', $project->id)->first();
       if (! $group) return null;

       $user = auth()->user();
       if (! $user) return $group; // public/anonymous flows keep existing behavior

       if (isOwnerOrEditor($user, $project) || $this->widgetDataService->isUnrestricted($project, $user->id)) {
           return $group;
       }

       return in_array($group->id, $this->widgetDataService->getSharedAssetGroupIds($project, $user->id), true)
           ? $group
           : null;
   }
   ```

   Replace all direct group lookups with `resolveGroupForUser()`:
   - `extractAssetFilter()` group branch — `:1915-1927` (currently project-scoped only).
   - `handleKpiSource()` dependent group — `:1291` (`AssetGroup::find($controls['asset_group'])`).
   - `handleKpiSource()` independent group — `:1363`.
   - `handleDerivedMetricSource()` path — flows through `extractAssetFilter()`.
   - `KpiPayloadBuilder` / `KpiExecuteActionBuilder` resolve groups at build time — they must
     receive the same restriction (or be guaranteed upstream since they run inside `handleKpiSource`
     after resolution).

   Missing / non-shared group ⇒ `___EMPTY_GROUP___` ⇒ empty-result short-circuit (already wired).

### Step 6 — `LoadsDashboardViewData`: scoped group visibility + asset options

**Modify:** `app/Filament/App/Resources/DashboardResource/Traits/LoadsDashboardViewData.php`

1. `$getAssetsForChannel` closure (`:105-125`): replace `filterAllowedAssets(...)` call with
   `getAllowedAssetIdsForChannel(...)` (or keep the same call — semantics now group-based). The
   existing `$isAdmin` shortcut stays.
2. `getAllAssetGroups()` (`:24-35`) and `getChannelAssetGroupMap()` (`:37-57`): filter to shared
   groups when the current user is a scoped collaborator:
   ```php
   $groups = AssetGroup::where('project_id', $project->id);
   if ($user && ! $userIsOwnerOrEditor && ! $service->isUnrestricted($project, $user->id)) {
       $groups->whereIn('id', $service->getSharedAssetGroupIds($project, $user->id));
   }
   ```
   The dashboard view JS (`dashboard-view-content.blade.php:765`, `:800-890`, `:1145-1174`) already
   constrains selections by whatever `channelAssetGroupMap` contains, so no JS change is needed —
   it naturally stops offering non-shared groups.

### Step 7 — Manage Collaborators: group-based "Manage Assets" modal

**Modify:** `app/Filament/App/Pages/ManageCollaborators.php`

Delete: `getActiveChannels()`, `getAssetsForChannel()`, `buildAssetScopeForm()`.

Rework the `manage_assets` action (`:162-219`):

```php
Action::make('manage_assets')
    ->label(__('Manage Asset Groups'))
    ->modalHeading(fn (User $record) => __('Asset access:') . " {$record->name}")
    ->modalDescription(__('When "Allow all assets" is off, this user only sees assets inside the selected asset groups.'))
    ->mountUsing(fn (Form $form, User $record) => $form->fill([
        'asset_access_unrestricted' => $service->isUnrestricted($project, $record->id),
        'asset_group_ids'           => $service->getSharedAssetGroupIds($project, $record->id),
    ]))
    ->form(fn () => [
        Toggle::make('asset_access_unrestricted')
            ->label(__('Allow all assets'))
            ->default(true)
            ->reactive(),
        CheckboxList::make('asset_group_ids')
            ->label(__('Shared asset groups'))
            ->options(AssetGroup::where('project_id', $project->id)
                ->withCount('items')
                ->get()
                ->mapWithKeys(fn ($g) => [$g->id => "{$g->name} ({$g->items_count})"])
                ->toArray())
            ->columns(2)
            ->visible(fn (Get $get) => ! $get('asset_access_unrestricted')),
    ])
    ->action(function (array $data, User $record) use ($project) {
        DB::table('project_user')
            ->where('project_id', $project->id)->where('user_id', $record->id)
            ->update(['asset_access_unrestricted' => (bool) ($data['asset_access_unrestricted'] ?? true)]);

        ProjectUserAssetGroup::where('project_id', $project->id)->where('user_id', $record->id)->delete();
        foreach (($data['asset_group_ids'] ?? []) as $groupId) {
            ProjectUserAssetGroup::create([
                'project_id' => $project->id,
                'user_id' => $record->id,
                'asset_group_id' => $groupId,
            ]);
        }
        // Notify user of changed access.
    })
```

Also add a table column summarizing access:

```php
TextColumn::make('asset_access')
    ->label(__('Asset access'))
    ->getStateUsing(function (User $record) use ($project) {
        // "All assets" | "Groups: G1, G2" | "No access"
    })
```

### Step 8 — Group visibility for collaborators (KPI builder / builder / data sources)

**Modify:**

- `app/Services/Analytics/KpiFormBuilder.php::getAssetGroupOptions()` (`:473-481`) — restrict to
  shared groups for scoped users.
- `app/Filament/App/Resources/DashboardResource/Pages/DashboardBuilder.php::getAllAssetGroups()`
  (`:205-216`) — same restriction.
- `app/Filament/App/Pages/DataSources.php::getAssetGroupsData()` (`:79-98`) — same restriction for
  the asset-group tags shown in the data-sources asset list.
- `app/Services/Analytics/KpiExecuteActionBuilder.php` (`:92-142`) — when resolving
  `dependent_asset_group` / `independent_asset_group`, apply `resolveGroupForUser` semantics
  (or reuse the controller helper if moved to a shared service).

Proposed shared helper to avoid duplication — **new**
`app/Services/CollaboratorAssetAccessService.php`:

```php
class CollaboratorAssetAccessService
{
    public function isUnrestricted(Project $project, int $userId): bool;
    public function getSharedAssetGroupIds(Project $project, int $userId): array;
    public function getAllowedAssetGroupQuery(Project $project, ?int $userId): Builder; // pre-filtered AssetGroup query
    public function getAllowedAssetIdsForChannel(Project $project, int $userId, string $channel): array;
    public function filterAllowedAssets(Project $project, int $userId, string $channel, array $assetIds): array;
    public function canAccessGroup(Project $project, int $userId, int $groupId): bool; // owner/editor/unrestricted/shared
}
```

`WidgetDataService` delegates to it (keeps `DashboardWidgetDataController` untouched) OR the
controller calls it directly — pick one entry point; the plan recommends the service as the single
enforcement owner and `WidgetDataService` as a thin delegator to preserve the existing call sites.

### Step 9 — Frontend / blade cleanup

- `dashboard-view-content.blade.php` and `dashboard-builder.blade.php` already consume
  `channelAssetGroupMap` and `getAllAssetGroups()`; no structural JS change required once those
  return scoped data.
- `compiled.php` at repo root appears to be a generated/stale artifact of `dashboard-builder.blade.php`
  — **do not hand-edit**; regenerate or leave (verify before shipping, Open Question §D).

### Step 10 — Data migration: convert existing per-channel scoping

**New file:** `database/migrations/<today>_000003_migrate_project_user_allowed_assets_to_groups.php`

Migration decision (**CONFIRMED, §C**): channel-based sharing is deprecated, so the migration
**never snapshots an "allow-all channel" as a group** — future assets/channels must not be locked
behind a stale snapshot. Access is preserved (never silently revoked); the flag rule is:

1. **User has no restricted-list rows** (no rows, or only `allowed_assets IS NULL` allow-all rows):
   `asset_access_unrestricted = true`, no pivot rows. They keep full access — this is the
   overwhelmingly common case and the desired new default.
2. **User has restricted-list rows but allow-all on ANY channel** (mixed: any `NULL` row, or an
   enabled channel with no row): `asset_access_unrestricted = true`. Preserving their allow-all
   channels takes precedence; their former restricted lists are **not** re-applied (channel
   scoping is being retired). Recorded in the plan/doc for the admin to re-scope via real groups
   if desired.
3. **User restricted on EVERY covered channel** (restricted-list rows covering all their enabled
   channels, no allow-all anywhere): `asset_access_unrestricted = false`, and each restricted list
   becomes an imported group `Imported · {user.name} · {channel}` with `asset_group_items`
   validated against currently-enabled project assets (dangling ids dropped), plus pivot rows.
4. After conversion, drop `project_user_allowed_assets` in the same migration; `down()` recreates
   the table and restores rows from a snapshot taken before the drop.

Idempotency guard: only migrate rows where `project_user_asset_groups` has no matching import yet
(skip if the importer already ran). Documented limitation: if the admin later re-scopes a case-2
user, their old lists are not carried over.

### Step 11 — Removal of legacy code (after migration is safe)

- Delete migration `2026_06_10_000002_create_project_user_allowed_assets_table.php`? — **No.**
  Keep it for history; instead let the *new* migration drop the table. (Existing deploy DBs already
  ran the old one.)
- Delete `app/Models/ProjectUserAllowedAsset.php`.
- Remove every remaining `ProjectUserAllowedAsset` reference:
  - `app/Services/WidgetDataService.php:8` (import) and `:80-94` (replaced in Step 4).
  - `app/Filament/App/Pages/ManageCollaborators.php:7`, `:181-213` (replaced in Step 7).
- Grep-sweep for `allowed_assets`, `allow_all_`, `getActiveChannels`, `getAssetsForChannel` to
  confirm zero references remain.

### Step 12 — Tests

**Update:** `tests/Feature/Analytics/AssetFilteringTest.php`

- Its helpers (`makeAfGroup`, `afFbmSyncConfig`, `makeAfProject`) keep working; tests exercising
  `extractAssetFilter` with asset groups remain valid once group resolution is user-scoped (they
  run as the acting `$this->user` who must be owner/unrestricted — add project ownership or
  `asset_access_unrestricted` seeding so existing tests don't start returning `___EMPTY_GROUP___`).

**New:** `tests/Feature/Analytics/CollaboratorAssetGroupSharingTest.php`

1. Unrestricted user sees all enabled assets (no filter applied).
2. Scoped user sees only the union of their shared groups' assets per channel.
3. Scoped user with zero shared groups sees nothing (403 `access_restricted` on widget-data).
4. Scoped user referencing a **non-shared group** in controls ⇒ `___EMPTY_GROUP___` (no engine call).
5. Scoped user referencing a **shared group** ⇒ assets reach the engine payload (metric, KPI
   dependent, KPI independent, DM series).
6. Owner/editor are never scoped even if a pivot row exists.
7. `getAllAssetGroups()` / `getChannelAssetGroupMap()` / `getAssetGroupOptions()` return only shared
   groups for a scoped user.
8. Manage-Collaborators `manage_assets` action: toggle + group sync persist correctly
   (`asset_access_unrestricted` flag + pivot rows), owner cannot be scoped.
9. Data migration: old restricted-row user → imported groups + `unrestricted = false`; user with
   any allow-all row → `unrestricted = true` with no snapshot groups; `project_user_allowed_assets`
   dropped; rerun is idempotent.
10. DM/KPI heritage chain: group scoping propagates through KPI→DM source-series without leaking.

### Step 13 — Docs & translations

- Update `MEMORY.md` (facade) with the new sharing model and migration notes.
- Add/refresh Spanish strings (`lang/es.json` / `resources/lang`) for the new modal labels.
- Update `USER_WORKFLOW.md` if it documents collaborator asset scoping.

---

## Files Summary

### New files
| File | Purpose |
|---|---|
| `database/migrations/*_create_project_user_asset_groups_table.php` | Group-sharing pivot |
| `database/migrations/*_add_asset_access_unrestricted_to_project_user_table.php` | Unrestricted flag |
| `database/migrations/*_migrate_project_user_allowed_assets_to_groups.php` | Data migration + legacy table drop |
| `app/Models/ProjectUserAssetGroup.php` | Pivot model |
| `app/Services/CollaboratorAssetAccessService.php` | Single enforcement owner (shared helpers) |
| `tests/Feature/Analytics/CollaboratorAssetGroupSharingTest.php` | New test suite |

### Modified files
| File | Change |
|---|---|
| `app/Models/ProjectUser.php` | `asset_access_unrestricted` fillable/cast |
| `app/Models/User.php` | `sharedAssetGroups()` relation |
| `app/Models/AssetGroup.php` | `sharedWithUsers()` inverse (optional) |
| `app/Services/WidgetDataService.php` | Group-based `filterAllowedAssets` + helpers (delegates to service) |
| `app/Http/Controllers/Api/DashboardWidgetDataController.php` | `resolveGroupForUser()` + all group lookups; `show()` keeps role gate |
| `app/Filament/App/Resources/DashboardResource/Traits/LoadsDashboardViewData.php` | Scoped group visibility + group-based asset options |
| `app/Filament/App/Pages/ManageCollaborators.php` | Group modal, access column; remove per-channel code |
| `app/Services/Analytics/KpiFormBuilder.php` | `getAssetGroupOptions()` scoped |
| `app/Filament/App/Resources/DashboardResource/Pages/DashboardBuilder.php` | `getAllAssetGroups()` scoped |
| `app/Filament/App/Pages/DataSources.php` | `getAssetGroupsData()` scoped |
| `app/Services/Analytics/KpiExecuteActionBuilder.php` | Group resolution respects sharing |
| `tests/Feature/Analytics/AssetFilteringTest.php` | Adapt user/ownership seeding |
| `MEMORY.md`, `USER_WORKFLOW.md`, lang files | Docs/translations |

### Deleted files
| File | Reason |
|---|---|
| `app/Models/ProjectUserAllowedAsset.php` | Legacy per-channel model |
| `project_user_allowed_assets` table | Dropped by the data migration |

---

## Decision Log / Open Questions

- **A — Enforcement gate.** CONFIRMED. Any non-owner/non-editor collaborator (including
  `project_viewer`) is scoped unless `asset_access_unrestricted`. Scoped users see only their
  shared groups in the view; widgets without group filters only surface assets inside their shared
  groups (403/empty otherwise).
- **B — "Allow all" representation.** CONFIRMED (see §Target Design): the project-level
  `asset_access_unrestricted` boolean on `project_user`. Per-channel allow-all flags were rejected
  (they reintroduce channel sharing semantics being removed).
- **C — Migration of existing scoping.** CONFIRMED: no snapshot groups for allow-all channels
  (future channels/assets must not be locked behind stale snapshots); users with any allow-all are
  migrated `unrestricted = true`; only users restricted on every covered channel are migrated to
  imported groups + `unrestricted = false`. Access is never silently revoked.
- **D — `compiled.php` at repo root.** RESOLVED: it is a gitignored (`.gitignore:109`) compiled
  Blade cache of `dashboard-builder.blade.php` (a local scratch artifact from `compile_blade.php`,
  `.gitignore:107`). Not tracked, not shipped; Laravel auto-recompiles on next render. No action
  needed for this feature.
- **E — Public dashboards.** DEFERRED to a separate plan (confirmed). Public dashboards get their
  own logic: any visitor (no login, no project membership) automatically accesses whichever assets
  are configured in the dashboard. Out of scope here; only "public dashboards bypass scoping" is
  preserved in the meantime.

---

## Out of Scope

- Public dashboards (`is_public`): scoping is bypassed in the meantime; a **separate plan** will be
  created for public access (any visitor, no login, sees whatever assets the dashboard configures).
- One-time share tokens / public share codes (`OneTimeShareToken`, `ShareCodesTable`,
  `SharedDashboardController`) — separate feature.
- Billing-profile sharing (`SharedWithUsersRelationManager`) — separate feature.
- Asset-group CRUD UX itself (creating/editing groups) — already exists via `AssetGroupResource`.
