import { driver } from "driver.js";
import "driver.js/dist/driver.css";

class TourManager {
    constructor() {
        this.tours = new Map();
        this.activeDriver = null;
        this.storageKey = 'apis_hub_completed_tours';
    }

    /**
     * Register a new tour configuration
     * @param {string} tourId
     * @param {object} config - { steps: Array, routePattern?: RegExp|string }
     */
    register(tourId, config) {
        this.tours.set(tourId, config);
    }

    /**
     * Get completed tours from localStorage (User-wide)
     * @returns {string[]}
     */
    getCompletedTours() {
        try {
            const raw = localStorage.getItem(this.storageKey);
            return raw ? JSON.parse(raw) : [];
        } catch {
            return [];
        }
    }

    /**
     * Mark a tour as completed
     * @param {string} tourId
     */
    markCompleted(tourId) {
        const completed = this.getCompletedTours();
        if (!completed.includes(tourId)) {
            completed.push(tourId);
            localStorage.setItem(this.storageKey, JSON.stringify(completed));
        }

        // Notify Alpine/Livewire if needed
        window.dispatchEvent(new CustomEvent('tour-status-changed', {
            detail: { tourId, completed: true }
        }));
    }

    /**
     * Check if a tour is already completed
     * @param {string} tourId
     * @returns {boolean}
     */
    isCompleted(tourId) {
        return this.getCompletedTours().includes(tourId);
    }

    /**
     * Check if all onboardings are globally disabled
     * @returns {boolean}
     */
    isAllToursDisabled() {
        try {
            return localStorage.getItem('apis_hub_disable_all_tours') === '1';
        } catch {
            return false;
        }
    }

    /**
     * Disable every onboarding (including the current one) and stop any active tour.
     */
    disableAllTours() {
        try {
            localStorage.setItem('apis_hub_disable_all_tours', '1');
        } catch {
            return;
        }

        for (const tourId of this.tours.keys()) {
            this.markCompleted(tourId);
        }

        if (this.activeDriver) {
            this.activeDriver.destroy();
        }

        window.dispatchEvent(new CustomEvent('tour-status-changed', {
            detail: { disabled: true }
        }));

        this.renderTourTrigger();
    }

    /**
     * List the tours available for the current route (global UI tour included on tenant pages).
     * @param {string} path
     * @returns {Array<{ id: string, config: object }>}
     */
    getAvailableTours(path = window.location.pathname) {
        const available = [];

        for (const [id, config] of this.tours.entries()) {
            if (id === 'global-ui') {
                if (this.isTenantRoute(path)) {
                    available.push({ id, config });
                }
                continue;
            }

            if (!config.routePattern) {
                continue;
            }

            const matches = typeof config.routePattern === 'string'
                ? path.includes(config.routePattern)
                : config.routePattern.test(path);

            if (matches) {
                available.push({ id, config });
            }
        }

        return available;
    }

    /**
     * Human-readable label for a tour (uses config.label, else first step title, else tour id)
     * @param {string} id
     * @param {object} config
     * @returns {string}
     */
    getTourLabel(id, config) {
        if (config.label) {
            return config.label;
        }

        const firstStep = (config.steps ?? [])[0];
        if (firstStep?.popover?.title) {
            return firstStep.popover.title;
        }

        return id;
    }

    /**
     * Remove the floating onboarding trigger button (if present)
     */
    removeTourTrigger() {
        document.getElementById('apis-hub-tour-trigger')?.remove();
    }

    /**
     * Remove any open tour chooser popup
     */
    removeTourChooser() {
        document.getElementById('apis-hub-tour-chooser')?.remove();
        document.querySelector('.apis-hub-tour-chooser-overlay')?.remove();
    }

    /**
     * Fired when the floating trigger is clicked.
     * Single available tour -> run it right away; multiple -> show the chooser.
     * @param {Array<{ id: string, config: object }>} available
     */
    onTourTriggerClick(available) {
        if (available.length === 1) {
            const { id } = available[0];
            this.start(id, true);
            this.removeTourTrigger();
            return;
        }

        this.showTourChooser(available);
    }

    /**
     * Render a subtle floating "?" trigger (right side) on pages whose tours
     * are already completed or globally disabled. Tooltip: "onboarding tutorial".
     */
    renderTourTrigger() {
        this.removeTourTrigger();

        if (this.activeDriver) {
            return;
        }

        const available = this.getAvailableTours(window.location.pathname);
        if (available.length === 0) {
            return;
        }

        const allDone = available.every(({ id }) => this.isCompleted(id));
        if (!allDone && !this.isAllToursDisabled()) {
            return;
        }

        const btn = document.createElement('button');
        btn.id = 'apis-hub-tour-trigger';
        btn.type = 'button';
        btn.dataset.tooltip = 'onboarding tutorial';
        btn.setAttribute('aria-label', 'Onboarding tutorial');
        btn.classList.add('apis-hub-tour-trigger');
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;

        btn.addEventListener('click', () => this.onTourTriggerClick(available));

        document.body.appendChild(btn);
    }

    /**
     * Show a popup listing the onboardings available for the current page.
     * @param {Array<{ id: string, config: object }>} available
     */
    showTourChooser(available) {
        this.removeTourChooser();

        const box = document.createElement('div');
        box.id = 'apis-hub-tour-chooser';
        box.setAttribute('role', 'menu');
        box.classList.add('apis-hub-tour-chooser');

        const heading = document.createElement('div');
        heading.classList.add('apis-hub-tour-chooser-title');
        heading.textContent = 'Available onboardings';
        box.appendChild(heading);

        for (const { id, config } of available) {
            const item = document.createElement('button');
            item.type = 'button';
            item.setAttribute('role', 'menuitem');
            item.classList.add('apis-hub-tour-chooser-item');
            item.textContent = this.getTourLabel(id, config);
            item.addEventListener('click', () => {
                this.removeTourChooser();
                this.removeTourTrigger();
                this.start(id, true);
            });
            box.appendChild(item);
        }

        const overlay = document.createElement('div');
        overlay.className = 'apis-hub-tour-chooser-overlay';
        overlay.addEventListener('click', () => this.removeTourChooser());

        document.body.appendChild(overlay);
        document.body.appendChild(box);
    }

    /**
     * Reset/clear a completed tour state
     * @param {string} tourId
     */
    resetTour(tourId) {
        let completed = this.getCompletedTours();
        completed = completed.filter(id => id !== tourId);
        localStorage.setItem(this.storageKey, JSON.stringify(completed));
    }

    /**
     * Check if current route is inside a tenant project workspace
     * (e.g. /app/my-tenant/... and NOT /app/new-project or /app/register-project)
     * @param {string} path
     * @returns {boolean}
     */
    isTenantRoute(path = window.location.pathname) {
        if (!path.startsWith('/app')) {
            return false;
        }

        // Exclude project creation / registration forms
        const nonTenantPaths = [
            '/app/new-project',
            '/app/register-project',
            '/app/create-project',
            '/app/login',
            '/app/register',
            '/app/password-reset'
        ];

        for (const nonTenant of nonTenantPaths) {
            if (path.startsWith(nonTenant)) {
                return false;
            }
        }

        // Must match /app/{tenantSlug}
        const segments = path.split('/').filter(Boolean);
        return segments.length >= 2;
    }

    /**
     * Start a specific tour by ID
     * @param {string} tourId
     * @param {boolean} force - Start even if already completed
     * @param {Function} onFinish - Optional callback when tour concludes
     */
    start(tourId, force = false, onFinish = null) {
        if (!force && this.isCompleted(tourId)) {
            return;
        }

        const config = this.tours.get(tourId);
        if (!config) {
            console.warn(`[TourManager] No configuration registered for tour: ${tourId}`);
            return;
        }

        const rawSteps = typeof config.steps === 'function' ? config.steps() : config.steps;
        if (!rawSteps || rawSteps.length === 0) {
            console.warn(`[TourManager] No steps found for tour: ${tourId}`);
            return;
        }

        // Filter steps based on showIf conditions and DOM element presence
        const validSteps = rawSteps.filter(step => {
            if (typeof step.showIf === 'function' && !step.showIf()) {
                return false;
            }
            if (!step.element) return true;
            return document.querySelector(step.element) !== null;
        });

        if (validSteps.length === 0) {
            console.warn(`[TourManager] No valid DOM targets present on page for tour: ${tourId}`);
            return;
        }

        if (this.activeDriver) {
            this.activeDriver.destroy();
        }

        this.removeTourTrigger();
        this.removeTourChooser();

        this.activeDriver = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: document.documentElement.classList.contains('dark') ? 0.75 : 0.45,
            stagePadding: 6,
            stageRadius: 10,
            doneBtnText: 'Finish ✓',
            nextBtnText: 'Next →',
            prevBtnText: '← Back',
            onPopoverRender: (popover) => {
                if (popover.footer.querySelector('.driver-popover-disable-tours-btn')) {
                    return;
                }

                const disableBtn = document.createElement('button');
                disableBtn.type = 'button';
                disableBtn.className = 'driver-popover-footer-btn driver-popover-disable-tours-btn';
                disableBtn.textContent = 'Disable all onboardings';
                disableBtn.title = 'Turn off every onboarding, including this one. You can replay them anytime from the "?" help button.';
                disableBtn.addEventListener('click', () => {
                    this.disableAllTours();
                });
                popover.footer.prepend(disableBtn);
            },
            onDestroyed: () => {
                this.markCompleted(tourId);
                this.activeDriver = null;
                this.renderTourTrigger();
                if (typeof onFinish === 'function') {
                    onFinish();
                }
            },
            steps: validSteps
        });

        this.activeDriver.drive();
    }

    /**
     * Find the matching page-specific tour for the current route
     * @param {string} path
     * @returns {string|null}
     */
    getPageTourForRoute(path = window.location.pathname) {
        for (const [tourId, config] of this.tours.entries()) {
            if (tourId === 'global-ui') continue;

            if (config.routePattern) {
                const matches = typeof config.routePattern === 'string'
                    ? path.includes(config.routePattern)
                    : config.routePattern.test(path);

                if (matches) {
                    return tourId;
                }
            }
        }
        return null;
    }

    /**
     * Auto-detect and run the appropriate tour for the current URL pathname
     */
    autoRun() {
        const path = window.location.pathname;

        // 0. Check for explicitly forced tour from cross-page navigation
        const forcedTour = sessionStorage.getItem('force_play_tour');
        if (forcedTour) {
            sessionStorage.removeItem('force_play_tour');
            setTimeout(() => {
                this.start(forcedTour, true);
            }, 600);
            return;
        }

        // 1. Check if Global UI Tour needs to run on first project visit
        if (this.isTenantRoute(path) && !this.isAllToursDisabled() && !this.isCompleted('global-ui') && this.tours.has('global-ui')) {
            setTimeout(() => {
                this.start('global-ui', false, () => {
                    // Once global tour completes, trigger the page-specific tour if applicable
                    const pageTourId = this.getPageTourForRoute(path);
                    if (pageTourId && !this.isCompleted(pageTourId)) {
                        setTimeout(() => {
                            if (!this.isCompleted(pageTourId)) this.start(pageTourId, false);
                            this.renderTourTrigger();
                        }, 500);
                    } else {
                        this.renderTourTrigger();
                    }
                });
            }, 600);
            return;
        }

        // 2. Check for Page-specific tour
        const pageTourId = this.getPageTourForRoute(path);
        if (pageTourId && !this.isCompleted(pageTourId) && !this.isAllToursDisabled()) {
            setTimeout(() => {
                if (!this.isCompleted(pageTourId)) this.start(pageTourId, false);
                this.renderTourTrigger();
            }, 500);
        } else {
            this.renderTourTrigger();
        }
    }

    /**
     * Initialize event listeners
     */
    init() {
        window.addEventListener('start-page-tour', (e) => {
            const tourId = e.detail?.tourId;
            const force = e.detail?.force ?? true;
            if (tourId) {
                this.start(tourId, force);
            } else {
                const path = window.location.pathname;
                const activeTourId = this.getPageTourForRoute(path) || (this.isTenantRoute(path) ? 'global-ui' : null);
                if (activeTourId) {
                    this.start(activeTourId, true);
                }
            }
        });

        // Run on initial load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.autoRun());
        } else {
            this.autoRun();
        }

        // Run on Livewire SPA page transitions
        document.addEventListener('livewire:navigated', () => {
            this.autoRun();
        });

        // Refresh the floating trigger whenever tour state changes
        document.addEventListener('tour-status-changed', () => {
            this.renderTourTrigger();
        });
    }
}

export const tourManager = new TourManager();
