export const derivedMetricsTour = {
    routePattern: '/derived-metrics',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Derived Metrics',
                description: 'Build channel-specific custom calculated metrics before cross-channel aggregation.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:model*="formula"], .fi-fo-text-input, .fi-section',
            popover: {
                title: 'Metric Transformation & Testing',
                description: 'Define field transformations and preview calculations against raw provider records.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
