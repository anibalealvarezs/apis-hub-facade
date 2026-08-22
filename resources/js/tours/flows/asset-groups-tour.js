export const assetGroupsTour = {
    routePattern: '/asset-groups',
    steps: [
        {
            element: '.fi-header, .fi-page-header, header, .fi-ta-header',
            popover: {
                title: 'Asset Groups',
                description: 'Organize related ad accounts, properties, and stores from different channels into unified logical groups (e.g. "Brand US", "E-commerce Europe").',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '.fi-ta, .fi-ta-table, table, .fi-section, [wire\\:key*="table"]',
            popover: {
                title: 'Active Groups & Summary',
                description: 'View your configured asset groups, total linked assets, and cross-channel summaries at a glance.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '.fi-page-header-actions, button:has(.heroicon-m-plus), .fi-btn-primary, a[href*="create"], .fi-header-actions',
            popover: {
                title: 'Create New Asset Group',
                description: 'Click to create a new cluster grouping ad accounts and stores for global dashboard filtering.',
                side: 'left',
                align: 'center'
            }
        }
    ]
};
