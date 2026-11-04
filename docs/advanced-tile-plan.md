# Advanced tile implementation plan

## Scope and compatibility

- Add a distinct `advanced_tile` type for direct metric and derived metric sources. Keep existing tiles and KPI compatibility unchanged. Exclude KPI advanced tiles on both client and server.
- Use the resolved dashboard or widget date range by default. Support exact trailing 7 and 30 day windows anchored to the resolved end date. Previous equivalent period is the default and uses the same inclusive day count. Allow comparison to be disabled.
- Enable the sparkline by default. Select an automatic or explicit temporal interval independently of the value calculation.
- Derived metric value and comparison reduce each period's computed result series using latest, sum, or average. Latest is the default. Ignore missing values and show unavailable when no numeric observations exist.
- Direct metrics require the channel's correct period-level aggregation for headline and previous periods. Avoid summing daily ratio or distinct values. Use only channel and metric combinations whose summary contracts preserve filters, scope, assets, and correct reducers. Show an explicit unsupported state for others until verified.

## Implementation

1. Define and validate stored configuration and period boundaries, including invalid dates, custom overrides, leap days, inclusive boundaries, and zero previous values.
2. Audit summary and chart endpoint contracts for each channel, including access control, asset groups, filters, units, error propagation, and metric scope. Preserve authorization and return clear unsupported errors where required.
3. Compute derived series for current and previous periods separately with the same filters, assets, and selected interval. Reduce numeric points consistently and keep null gaps in the sparkline. Verify referenced derived metrics and cache keys include dates, interval, and reducer.
4. Add renderer output with value and sparkline, comparison dates, unavailable and zero-baseline states. Preserve existing tile behavior.
5. Add builder creation, type conversion, controls, validation, translations, and backend source restrictions. Update dashboard specification validation and all relevant public, shared, and private rendering paths.
6. Add regression tests for boundaries, reducers, channel contracts, compatibility, caching, access, errors, and navigation. Run the PHP suite and frontend build, then verify representative widgets in a browser.

## Findings to resolve during implementation

- Direct metric tiles currently call chart endpoints and use the latest bucket. Google Analytics and Search Console summary endpoints separately calculate equivalent previous windows. Confirm other channels individually.
- The Facade generic aggregation service applies a limited set of weighted reducers and cannot be used universally for period-level metric values.
- Derived metric handling returns labeled datasets, but some paths aggregate source series before formula evaluation. Check interval semantics and chronological ordering before applying the chosen reducer.
- Existing tile series fallback averages KPI points. Any basic-tile fix needs a source-specific test and must preserve intentional existing behavior.
- PHPUnit previously could not start because vendor/autoload.php was absent. Verify the current dependency installation before relying on test results.

## Progress (2026-11-03)

- Implemented the period boundary and derived-series reducer service with focused unit tests. The service is not yet connected to widget requests.
- PHP syntax check passed. The Pest run could not load the vendor bootstrap in this environment, so the unit tests remain unverified.
- Source dispatch, channel contract audit, builder, renderer, and integration testing remain outstanding. Advanced tile is not yet available.

- Added provisional derived-metric request dispatch for current and previous periods. Builder, renderer, direct metric support, and integration verification remain pending.
- Connected derived-metric creation, builder settings, and sparkline rendering. Direct metrics and end-to-end verification remain pending.
- Added direct metric support for single-series Search Console clicks and impressions, with auto, daily, weekly and monthly sparkline intervals. Derived-metric intervals, broader channel verification and end-to-end checks remain open.
- Added derived-metric sparkline intervals. Weekly and monthly trends use a separate calculation, while the headline and comparison continue to reduce daily results. PHP syntax passed. Broader channel verification and browser checks remain open.
- Extended direct metric support to unfiltered Google Analytics sessions in traffic scope. Other direct metric combinations require channel-specific verification. Dashboard specification validation and Builder creation now reject unsupported source types. Pest still cannot load its bootstrap. Browser and live-tenant verification remain open.
