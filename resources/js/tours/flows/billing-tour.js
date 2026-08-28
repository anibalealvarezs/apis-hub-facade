export const billingTour = {
    routePattern: '/account/account-subscription',
    steps: [
        {
            element: '.fi-header-heading, .fi-header, .fi-page-header',
            popover: {
                title: 'Billing & Subscriptions Manager',
                description: 'Manage subscription tiers, billing profiles, payment methods, and invoices across all your workspaces.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: 'select[wire\\:model*="selectedProfileId"], .mb-6:has(select)',
            popover: {
                title: 'Select Active Billing Profile',
                description: 'Switch between personal or business billing profiles to manage independent subscriptions and payment methods.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.flex.justify-center:has(button)',
            popover: {
                title: 'Monthly vs. Annual Billing',
                description: 'Toggle between Monthly billing and Annual billing to save with 2 months free.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '.grid.grid-cols-1.md\\:grid-cols-3, [wire\\:key*="plan"]',
            popover: {
                title: 'Subscription Tiers & Upgrades',
                description: 'Compare plan capacities (Pro, Founder, Enterprise), view your currently active plan, or checkout with Stripe / PayPal.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
