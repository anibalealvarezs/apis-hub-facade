export const customKpisTour = {
    routePattern: '/custom-kpis',
    steps: [
        {
            element: '.fi-page-header, .fi-header',
            popover: {
                title: 'Custom KPIs (Blended AST Formulas)',
                description: 'Create multi-channel blended business metrics (e.g. Blended ROAS = Total Revenue / (Meta Spend + Google Spend)).',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '[wire\\:key*="series-repeater"], .fi-fo-repeater',
            popover: {
                title: 'Series Assignment (a, b, c...)',
                description: 'Assign letter variables to specific channel queries and metric streams.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '[wire\\:model*="formula"], .formula-editor-container, .fi-fo-text-input',
            popover: {
                title: 'AST Formula Expression',
                description: 'Write mathematical expressions with live syntax validation and test formula evaluation with historical data.',
                side: 'top',
                align: 'center'
            }
        }
    ]
};
