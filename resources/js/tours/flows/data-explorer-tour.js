export const dataExplorerTour = {
    routePattern: '/data-explorer',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Data Explorer',
                description: 'Directly inspect and validate raw normalized data streams from all connected channels before building dashboards.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:model*="channel"], select[name*="channel"], .fi-fo-select, .fi-tabs',
            popover: {
                title: 'Choose Channel',
                description: 'Select the channel integration whose normalized data schema you wish to inspect.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.fi-ta, table, .fi-section, [wire\\:key*="explorer-table"]',
            popover: {
                title: 'Explore Synced Records',
                description: 'Sort, filter by date ranges, and inspect dimensions, metrics, and currency-normalized values.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
