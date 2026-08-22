export const globalUiTour = {
    steps: [
        {
            element: '.fi-tenant-menu, .fi-tenant-menu-trigger, .fi-sidebar-header .fi-dropdown, .fi-sidebar-header button[aria-haspopup="menu"], .fi-sidebar-header > div:nth-child(2)',
            popover: {
                title: 'Project & Workspace Selector',
                description: 'Switch between client workspaces and view the attached billing tier for your active project.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: 'nav.fi-sidebar-nav > ul, .fi-sidebar-nav ul.fi-sidebar-nav-groups, .fi-sidebar-nav > ul, ul.fi-sidebar-nav-groups',
            popover: {
                title: 'Main Navigation & Platform Layers',
                description: 'Explore the core platform layers: Administration (Settings & Team), Integrations (Data Sources & Telemetry), Data (Asset Groups & Explorer), Analytics (Dashboards & Alerts), and Knowledge Base.',
                side: 'right',
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
            element: '[x-persist*="topbar.end"], .fi-topbar-end, header.fi-topbar .flex.items-center:last-child, .fi-topbar-actions',
            popover: {
                title: 'Account & Personalization Controls',
                description: 'Access your personal account & billing panel, switch language (EN | ES), toggle dark/light mode, and inspect notifications.',
                side: 'bottom',
                align: 'end'
            }
        }
    ]
};
