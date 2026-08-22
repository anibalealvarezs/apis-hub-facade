export const telemetryTour = {
    routePattern: '/telemetry',
    steps: [
        {
            element: '#telemetry-workers-status, .fi-header-heading, .fi-header',
            popover: {
                title: 'Sync Telemetry & Worker Health',
                description: 'Monitor data sync queues, pipeline latencies, and ingestion heartbeats before performing deep analytics.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '#telemetry-overall-progress',
            popover: {
                title: 'Overall Sync Progress',
                description: 'Track global ingestion progress, total synced assets, and historical backfill completion across all providers.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#telemetry-channel-breakdown',
            popover: {
                title: 'Channel Breakdown & Drill-down',
                description: 'Inspect status per channel. Click any channel card to expand and view individual ad account sync progress and job states.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
