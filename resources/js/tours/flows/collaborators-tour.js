export const collaboratorsTour = {
    routePattern: '/manage-collaborators',
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
            element: '#active-members-section',
            popover: {
                title: 'Active Members',
                description: 'Review the active members of this project. Edit their roles and permissions, or revoke access.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '#pending-invites-section',
            popover: {
                title: 'Pending Invites',
                description: 'Below the active members you will find the pending invitations. Resend or cancel them, or grant access to new collaborators.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '#share-codes-section',
            popover: {
                title: 'Generate Share Codes',
                description: 'Generate share codes so any user can join this project from the registration form. Each code can only be used once.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};