export const alertsTour = {
    routePattern: '/alerts',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Automated Threshold & Anomaly Alerts',
                description: 'Set proactive rules that monitor normalized metrics in the background and notify your team whenever thresholds or anomalies occur.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:model*="calculation_lines"], [wire\\:key*="formula-builder"], .fi-fo-repeater, .fi-fo-wizard',
            popover: {
                title: 'Calculation Lines & Formulas',
                description: 'Define mathematical conditions (e.g. spend > 500 && roas < 1.8) evaluated against live synced data streams.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '[wire\\:model*="threshold"], [wire\\:model*="anomaly"], .fi-fo-select',
            popover: {
                title: 'Thresholds & Bounds',
                description: 'Configure minimum, maximum, or statistical deviation limits that trigger an alert.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '[wire\\:model*="frequency"], select[name*="frequency"], [wire\\:model*="cron"]',
            popover: {
                title: 'Evaluation Schedule',
                description: 'Choose how often the worker evaluates your rule (e.g. Hourly or Daily cron cadence).',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '[wire\\:model*="channels"], [wire\\:key*="notification-channels"]',
            popover: {
                title: 'Notification Channels',
                description: 'Toggle In-App notifications and specify email recipient lists for instant dispatch when alerts fire.',
                side: 'left',
                align: 'center'
            }
        }
    ]
};
