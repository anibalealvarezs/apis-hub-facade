export const projectSettingsTour = {
    routePattern: '/project-settings',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Project Settings & Deployment',
                description: 'Configure your project parameters, timezone alignment, and manage your dedicated sync container deployment.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:model*="timezone"], select[name*="timezone"], .fi-fo-select',
            popover: {
                title: 'Timezone Configuration',
                description: 'Set the primary timezone for your project. This ensures normalized daily metrics match your ad network reporting windows.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: 'button[wire\\:click*="deploy"], button[wire\\:click*="redeploy"], .fi-btn',
            popover: {
                title: 'Deploy & Worker Provisioning',
                description: 'Trigger the deployment process to build and run your dedicated tenant worker instance in our cloud infrastructure.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '[wire\\:poll], .fi-badge, .fi-section-header',
            popover: {
                title: 'Live Deployment Monitor',
                description: 'Monitor deployment progress, container health, and worker heartbeat indicators in real-time.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
