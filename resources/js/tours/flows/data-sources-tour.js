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
        // Step 4: Enable channel switch (when connected and switch exists)
        {
            element: '[wire\\:key*="enabled.Filament\\\\Forms\\\\Components\\\\Toggle"], [wire\\:key*="enabled"][class*="Toggle"], [wire\\:key*="enabled"]',
            showIf: () => document.querySelector('[wire\\:key*="enabled.Filament\\\\Forms\\\\Components\\\\Toggle"], [wire\\:key*="enabled"]') !== null,
            popover: {
                title: 'Enable Channel Ingestion',
                description: 'Toggle whether data synchronization for this channel is active or temporarily paused.',
                side: 'bottom',
                align: 'start'
            }
        },
        // Step 5: List of assets to select from
        {
            element: '[wire\\:key*="Repeater"], [wire\\:key*="assets."], .fi-fo-repeater, #ds-assets-form table',
            showIf: () => document.querySelector('[wire\\:key*="Repeater"], [wire\\:key*="assets."], .fi-fo-repeater, #ds-assets-form table') !== null,
            popover: {
                title: 'Tracked Assets Selection',
                description: 'Toggle and select the individual properties, ad accounts, pages, or stores you want to synchronize.',
                side: 'top',
                align: 'center'
            }
        },
        // Step 6: Internal sidebar section with config cards
        {
            element: '#ds-assets-form div.sticky.top-4.self-start, #ds-assets-form div.sticky, #ds-assets-form .sticky.top-4',
            showIf: () => document.querySelector('#ds-assets-form div.sticky.top-4.self-start, #ds-assets-form div.sticky, #ds-assets-form .sticky.top-4') !== null,
            popover: {
                title: 'Channel Sync Settings',
                description: 'Configure automated sync schedules, execution times, historic cache depth, and custom calculation settings for this channel.',
                side: 'left',
                align: 'start'
            }
        },
        // Step 7: Save & apply configuration
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
