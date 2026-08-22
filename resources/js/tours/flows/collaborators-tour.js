export const collaboratorsTour = {
    routePattern: '/collaborators',
    steps: [
        {
            element: '.fi-header-heading, .fi-header, .fi-page-header',
            popover: {
                title: 'Team & Collaborators',
                description: 'Manage team invitations, member roles, and granular asset-level access for this project.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: 'button:has(.heroicon-m-user-plus), .fi-page-header-actions button, .fi-ta-header-actions button, button:has(.heroicon-o-plus)',
            popover: {
                title: 'Invite New Collaborator',
                description: 'Send an invitation by email and assign project roles (Admin, Editor, or Viewer) with optional asset group restrictions.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '.fi-ta, table, .fi-ta-content',
            popover: {
                title: 'Active Members & Pending Invites',
                description: 'Review active team members, edit permissions, resend pending invitation links, or revoke access.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
