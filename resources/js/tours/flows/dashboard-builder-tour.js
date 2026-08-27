function scrollHeaderActionIntoView(el) {
    if (!el) return;
    const topbar = document.querySelector('.fi-topbar');
    const offset = (topbar ? topbar.getBoundingClientRect().height : 64) + 16;
    const rect = el.getBoundingClientRect();
    if (rect.top < offset) {
        window.scrollBy({ top: rect.top - offset, behavior: 'smooth' });
    }
}

export const dashboardBuilderTour = {
    routePattern: '/builder',
    steps: [
        {
            element: '.builder-toolbar',
            popover: {
                title: 'Dashboards & Visual Analytics',
                description: 'Build responsive, multi-channel analytics dashboards with custom KPI cards, blended charts, and tables.',
                side: 'bottom',
                align: 'start'
            }
        },
        {
            element: '#dashboard-controls-button',
            popover: {
                title: 'Global Controls & Asset Group Selector',
                description: 'Set global date ranges and asset group filters. Enable "Show Asset Group Selector" in dashboard settings to let viewers filter on the fly.',
                side: 'bottom',
                align: 'center'
            }
        },
        {
            element: '#grid-stack',
            popover: {
                title: 'Drag, Resize & Layout',
                description: 'Drag tiles to reposition them and drag corner handles to resize. Tick the selection box on a tile to multi-select widgets and align them together.',
                side: 'top',
                align: 'center'
            }
        },
        {
            element: '.bd-palette-left',
            popover: {
                title: 'Size Palette — Pick a Shape',
                description: 'Click a shape category to reveal the available widget sizes.',
                side: 'right',
                align: 'center'
            },
            onHighlighted: (el) => {
                setTimeout(() => {
                    const btn = el?.querySelector('.bd-palette-cat-btn');
                    if (btn) btn.click();
                }, 350);
            }
        },
        {
            element: '.bd-size-option',
            popover: {
                title: 'Drag a Size onto the Grid',
                description: 'Click and hold a size tile, then drag it onto the canvas. A dashed preview will appear showing where the widget will land.',
                side: 'right',
                align: 'center'
            },
            onDeselected: () => {
                document.querySelectorAll('.bd-palette-sizes-panel').forEach(p => p.style.display = 'none');
                document.querySelectorAll('.bd-palette-cat-btn').forEach(b => b.classList.remove('active'));
            }
        },
        {
            element: '.builder-toolbar button:last-of-type',
            popover: {
                title: 'Save Layout & Snapshot Versioning',
                description: 'Persist your grid layout and access snapshot version history to restore previous dashboard arrangements at any time.',
                side: 'left',
                align: 'center'
            }
        },
        {
            element: '#builder-view-dashboard-btn',
            popover: {
                title: 'View Dashboard',
                description: 'Switch to view mode to see your dashboard exactly as your audience will — including live data, filters, and exports.',
                side: 'bottom',
                align: 'center'
            },
            onHighlighted: (el) => {
                setTimeout(() => scrollHeaderActionIntoView(el), 50);
            }
        },
        {
            element: '#builder-save-version-btn',
            popover: {
                title: 'Version Control',
                description: 'Create a labeled snapshot to lock in a stable version of the dashboard. Return to any historical snapshot later.',
                side: 'bottom',
                align: 'center'
            },
            onHighlighted: (el) => {
                setTimeout(() => scrollHeaderActionIntoView(el), 50);
            }
        },
        {
            element: '#builder-version-history-btn',
            popover: {
                title: 'Version History',
                description: 'Browse, restore, duplicate, or prune past versions — so you can always roll back to a stable arrangement.',
                side: 'left',
                align: 'center'
            },
            onHighlighted: (el) => {
                setTimeout(() => scrollHeaderActionIntoView(el), 50);
            }
        },
        {
            element: '.grid-stack-item:first-of-type .widget-header input[type="checkbox"]',
            showIf: () => document.querySelector('.grid-stack-item') !== null,
            popover: {
                title: 'Select a Widget',
                description: 'Tick a widget\'s selection box to enable multi-widget tools. Tick several boxes to select multiple widgets at once.',
                side: 'bottom',
                align: 'center'
            },
            onHighlighted: (el) => {
                setTimeout(() => {
                    const cb = document.querySelector('.grid-stack-item:first-of-type .widget-header input[type="checkbox"]');
                    if (cb && !cb.checked) cb.click();
                }, 350);
            }
        },
        {
            element: '#multi-select-action-bar',
            showIf: () => document.querySelector('.grid-stack-item') !== null,
            popover: {
                title: 'Multi-Widget Action Bar',
                description: 'Once widgets are selected, this bar lets you select all, clear, duplicate, or delete multiple widgets in one move.',
                side: 'left',
                align: 'center'
            },
            onHighlighted: (el) => {
                const cb = document.querySelector('.grid-stack-item:first-of-type .widget-header input[type="checkbox"]');
                if (cb && !cb.checked) {
                    cb.click();
                    if (el) el.style.display = 'flex';
                }
            },
            onDeselected: () => {
                document.querySelectorAll('.grid-stack-item .widget-header input[type="checkbox"]').forEach((cb) => {
                    if (cb.checked) cb.click();
                });
            }
        }
    ]
};
