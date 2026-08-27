export const dataExplorerTour = {
    routePattern: '/data-explorer',
    steps: [
        {
            element: '.gsc-header-row',
            popover: {
                title: 'Controls Bar',
                description: 'Filter data by date range and property, toggle trend overlays when available, and export the dashboard to PDF.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.metrics-grid-gsc',
            popover: {
                title: 'Summary & Chart',
                description: 'Get a quick, impactful overview of total clicks, impressions, CTR, and position — with a trend chart to spot patterns at a glance.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.tab-nav-gsc',
            popover: {
                title: 'Data Table Filtering',
                description: 'Switch between Queries, Pages, Countries, Devices, and Search Appearance — then click any row to apply it as an active filter.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
