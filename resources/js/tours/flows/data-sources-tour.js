export const dataSourcesTour = {
    routePattern: '/data-sources',
    steps: [
        {
            element: '.fi-header-heading, .fi-page-header, .fi-header',
            popover: {
                title: 'Data Sources Configuration',
                description: 'Manage all your external integrations (Meta Ads, Google Analytics, Shopify, Klaviyo, Amazon, etc.) from this central hub.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '#ds-channels-sidebar, .ds-sidebar',
            popover: {
                title: 'Channel Selection',
                description: 'Select the marketing, social, or ecommerce channel you want to configure from the left panel.',
                side: 'right',
                align: 'start'
            }
        },
        {
            element: '#ds-auth-actions, #ds-connect-block, button[wire\\:click*="updateCredentials"], button[wire\\:click*="connect"]',
            popover: {
                title: 'Channel Authorization',
                description: 'Authorize access to your provider account via OAuth, update permissions, or discover new ad accounts.',
                side: 'bottom',
                align: 'end'
            }
        },
        {
            element: '#ds-assets-form, form[wire\\:submit="save"]',
            popover: {
                title: 'Discovered Assets Selection',
                description: 'Enable or disable the specific ad accounts, Google Analytics properties, or stores you want the ingestion engine to synchronize.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '#ds-save-container, button[type="submit"][wire\\:target="save"]',
            popover: {
                title: 'Save & Apply Configuration',
                description: 'Save your asset selections to sync them with your tenant worker container.',
                side: 'top',
                align: 'end'
            }
        }
    ]
};
