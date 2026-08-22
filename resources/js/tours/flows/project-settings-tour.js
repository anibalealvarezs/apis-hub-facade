export const projectSettingsTour = {
    routePattern: '/project-settings',
    steps: [
        {
            element: '.fi-header-heading, .fi-header, .fi-page-header',
            popover: {
                title: 'Project Settings',
                description: 'Manage your project parameters, timezone alignment, and dedicated sync worker container lifecycle from this page.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '.fi-page-header-actions button:has(.heroicon-o-pencil-square), .fi-header-actions button:has(.heroicon-o-pencil-square), button[wire\\:click*="edit_settings"]',
            popover: {
                title: 'Timezone & Preferences',
                description: 'Click "Edit Preferences" to configure your reporting timezone and content languages. Aligning timezone ensures daily metrics match your ad platforms.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.fi-page-header-actions button:has(.heroicon-o-rocket-launch), .fi-page-header-actions button:has(.heroicon-o-cloud-arrow-up), button[wire\\:click*="deploy_initial"], button[wire\\:click*="redeploy"]',
            popover: {
                title: 'Deploy & Worker Provisioning',
                description: 'Trigger initial infrastructure deployment or apply configuration changes (redeploy) to build your dedicated worker container in the cloud.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#project-activity-logs, .fi-section:has(table), table',
            popover: {
                title: 'Live Deployment Monitor & Logs',
                description: 'Track deployment build outputs, worker restart events, and synchronization status logs in real-time.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '.topbar-status-text, .topbar-sync-progress, .fi-topbar [wire\\:poll]',
            popover: {
                title: 'Global Sync Status Indicator',
                description: 'This top-bar status widget is available across all pages, showing live worker heartbeat states and total sync progress at a glance.',
                side: 'bottom',
                align: 'center'
            }
        }
    ]
};
