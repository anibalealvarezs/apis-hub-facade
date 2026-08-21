export const assetGroupsTour = {
    routePattern: '/asset-groups',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Asset Groups',
                description: 'Organize related ad accounts, properties, and stores from different channels into unified logical groups (e.g. "Brand US", "E-commerce Europe").',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: 'input[name*="name"], [wire\\:model*="name"], .fi-fo-text-input',
            popover: {
                title: 'Assign a Group Name',
                description: 'Enter a clear name for this group. This will appear as a global filter option in your dashboards.',
                side: 'top',
                align: 'start'
            }
        },
        {
            element: '[wire\\:model*="assets"], .fi-fo-checkbox-list, .fi-fo-select, .fi-section',
            popover: {
                title: 'Cross-Channel Asset Selection',
                description: 'Select the ad accounts, Google Analytics properties, or Shopify stores to include in this group.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: 'button[type="submit"], .fi-form-actions button, .fi-btn-primary',
            popover: {
                title: 'Save & Activate',
                description: 'Save the asset group to enable instant cross-channel filtering across all your dashboards.',
                side: 'left',
                align: 'center'
            }
        }
    ]
};
