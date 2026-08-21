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
        // Step 3A: When channel is NOT connected
        {
            element: '#ds-connect-block button, #ds-connect-block',
            showIf: () => document.querySelector('#ds-connect-block') !== null,
            popover: {
                title: 'Connect Provider Account',
                description: 'Click "Connect Account" to authorize access via OAuth. Once connected, your ad accounts, properties, or stores will be discovered automatically.',
                side: 'bottom',
                align: 'center'
            }
        },
        // Step 3B: When channel IS already connected
        {
            element: '#ds-auth-actions',
            showIf: () => document.querySelector('#ds-assets-form') !== null,
            popover: {
                title: 'Channel Authorization & Asset Discovery',
                description: 'Update OAuth credentials or click "Refresh / Discover" at any time to sync newly created properties or ad accounts.',
                side: 'bottom',
                align: 'end'
            }
        },
        // Step 4: Asset selection (only when connected)
        {
            element: '#ds-assets-form .grid > div:first-child, #ds-assets-form table, #ds-assets-form .fi-fo-repeater, #ds-assets-form',
            showIf: () => document.querySelector('#ds-assets-form') !== null,
            popover: {
                title: 'Tracked Assets Selection',
                description: 'Toggle and select the individual properties, ad accounts, pages, or stores you want to synchronize.',
                side: 'top',
                align: 'center'
            }
        },
        // Step 5: Channel sync settings (only when connected)
        {
            element: '#ds-assets-form .grid > div:last-child, #ds-assets-form .fi-fo-section:has([wire\\:model*="cron_time"]), #ds-assets-form .fi-fo-section:has(input[type="time"]), .fi-fo-section:has(select)',
            showIf: () => document.querySelector('#ds-assets-form') !== null,
            popover: {
                title: 'Channel Sync Settings',
                description: 'Configure automated sync schedules, execution times, historic cache depth, and custom calculation settings for this channel.',
                side: 'left',
                align: 'start'
            }
        },
        // Step 6: Save changes (only when connected)
        {
            element: '#ds-save-container, button[type="submit"][wire\\:target="save"]',
            showIf: () => document.querySelector('#ds-assets-form') !== null,
            popover: {
                title: 'Save & Apply Configuration',
                description: 'Save your asset selections to sync them with your tenant worker container.',
                side: 'top',
                align: 'end'
            }
        }
    ]
};
