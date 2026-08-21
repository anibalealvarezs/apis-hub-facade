export const dataSourcesTour = {
    routePattern: '/data-sources',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Data Sources & Channels',
                description: 'Manage all your external integrations (Meta Ads, Google Analytics, Shopify, Klaviyo, Amazon, etc.) from this central hub.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:click*="activeChannel"], [wire\\:key*="channel-tab"], .fi-tabs, nav.flex',
            popover: {
                title: 'Channel Selection',
                description: 'Switch between integrated marketing, social, and ecommerce channels to configure authorization and assets.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: 'button[wire\\:click*="connectAction"], button[wire\\:click*="updateCredentialsAction"], .fi-btn-primary',
            popover: {
                title: 'Channel Authorization (OAuth)',
                description: 'Click to securely connect or update your account permissions via OAuth in seconds.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '.fi-ta, table, [wire\\:key*="assets-table"], .fi-section',
            popover: {
                title: 'Discovered Assets Selection',
                description: 'Select the specific ad accounts, Google properties, or stores you want the ingestion engine to monitor and synchronize.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: 'button[type="submit"], button[wire\\:click*="save"], .fi-form-actions button',
            popover: {
                title: 'Save & Apply Configuration',
                description: 'Save your asset selections to sync them with your tenant worker container.',
                side: 'left',
                align: 'center'
            }
        }
    ]
};
