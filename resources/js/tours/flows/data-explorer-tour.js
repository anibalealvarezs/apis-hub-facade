export const dataExplorerTour = {
    routePattern: '/data-explorer',
    steps: [
        {
            element: '.gsc-header-row, .ga4-header-row, .fb-header-row',
            popover: {
                title: 'Controls Bar',
                description: 'Filter data by date range and asset, toggle trend overlays when available, and export the dashboard to PDF.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.dash-overview-section',
            popover: {
                title: 'Summary & Chart',
                description: 'Get a quick, impactful overview of your key metrics at a glance — with a trend chart to spot patterns over time.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.gsc-table-container, .ga4-table-container, .fb-table-container',
            popover: {
                title: 'Data Table Filtering',
                description: 'Switch between breakdown tabs, search, sort — then click any row to apply it as an active filter across the dashboard.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
