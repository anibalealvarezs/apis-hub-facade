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
            element: '#ds-connect-block button, #ds-connect-block, #ds-auth-actions, button[wire\\:click*="connect"], button[wire\\:click*="updateCredentials"]',
            popover: {
                title: 'Channel Authorization',
                description: 'Authorize access to your provider account via OAuth, update permissions, or discover new ad accounts.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#ds-assets-form .grid > div:first-child, #ds-assets-form table, #ds-assets-form .fi-fo-repeater, #ds-assets-form',
            popover: {
                title: 'Tracked Assets Selection',
                description: 'Toggle and select the individual properties, ad accounts, pages, or stores you want to synchronize.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '#ds-assets-form .grid > div:last-child, #ds-assets-form .fi-fo-section:has([wire\\:model*="cron_time"]), #ds-assets-form .fi-fo-section:has(input[type="time"]), .fi-fo-section:has(select)',
            popover: {
                title: 'Channel Sync Settings',
                description: 'Configure automated sync schedules, execution times, historic cache depth, and custom calculation settings for this channel.',
                side: 'left',
                align: 'start'
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
