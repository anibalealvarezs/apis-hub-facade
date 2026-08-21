export const globalUiTour = {
    steps: [
        {
            element: '.fi-tenant-menu, [data-tenant-menu], .fi-sidebar-header .fi-dropdown, .fi-sidebar-header',
            popover: {
                title: 'Project & Workspace Selector',
                description: 'Switch between client workspaces and view the attached billing tier for your active project.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '.fi-topbar .flex-1, .topbar-status-text, .topbar-sync-progress, .fi-topbar [wire\\:poll]',
            popover: {
                title: 'Tenant Status & Timezone Clock',
                description: 'These live top-bar widgets display tenant-specific metrics, worker synchronization health, and the live datetime in your configured project timezone.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.fi-topbar-end, div:has(.fi-user-menu), .fi-user-menu, .fi-topbar nav',
            popover: {
                title: 'Account & Personalization Controls',
                description: 'Access your personal account & billing panel, switch language (EN | ES), toggle dark/light mode, and inspect notifications.',
                side: 'bottom',
                align: 'end'
            }
        },
        {
            element: '.fi-sidebar-nav, aside.fi-sidebar nav, .fi-sidebar',
            popover: {
                title: 'Main Navigation & Platform Layers',
                description: 'Explore the core platform layers: Administration (Settings & Team), Integrations (Data Sources & Telemetry), Data (Asset Groups & Explorer), Analytics (Dashboards & Alerts), and Knowledge Base.',
                side: 'right',
                align: 'start'
            }
        }
    ]
};
