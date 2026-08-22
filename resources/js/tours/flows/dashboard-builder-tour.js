export const dashboardBuilderTour = {
    routePattern: '/dashboards',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Dashboards & Visual Analytics',
                description: 'Build responsive, multi-channel analytics dashboards with custom KPI cards, blended charts, and tables.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:click*="openAddWidgetModal"], button:has(.heroicon-m-plus), #btn-open-add-widget, .fi-page-header-actions button',
            popover: {
                title: 'Add Widgets',
                description: 'Open the widget palette to add KPI Cards, Multi-Channel Trend Charts, Blended Matrices, or Donut Breakdowns.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '.dashboard-controls-bar, [wire\\:key*="controls-bar"], .fi-section',
            popover: {
                title: 'Global Controls & Asset Group Selector',
                description: 'Set global date ranges and asset group filters. Enable "Show Asset Group Selector" in dashboard settings to let viewers filter on the fly.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.grid-stack, [wire\\:key*="gridstack-container"], .grid-stack-item',
            popover: {
                title: 'GridStack Drag, Resize & Layout',
                description: 'Drag tiles to reposition them and drag corner handles to resize. Hold Shift+Click to multi-select and align multiple widgets.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: 'button[wire\\:click*="saveLayout"], .fi-page-header-actions button:has(.heroicon-m-check)',
            popover: {
                title: 'Save Layout & Snapshot Versioning',
                description: 'Persist your grid layout and access snapshot version history to restore previous dashboard arrangements at any time.',
                side: 'left',
                align: 'center'
            }
        }
    ]
};
