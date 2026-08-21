export const telemetryTour = {
    routePattern: '/telemetry',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Sync Telemetry & Worker Health',
                description: 'Monitor data sync queues, pipeline latencies, and ingestion heartbeats before performing deep analytics.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '.fi-ta, table, .fi-section, [wire\\:key*="telemetry"]',
            popover: {
                title: 'Channel Sync Status',
                description: 'Verify that all connected channels have finished their initial historical data backfills and daily syncs.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
