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
        if (!config || !config.steps || config.steps.length === 0) {
            console.warn(`[TourManager] No steps found for tour: ${tourId}`);
            return;
        }

        // Filter steps to only those with existing elements in the DOM
        const validSteps = config.steps.filter(step => {
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
            onDestroyed: () => {
                this.markCompleted(tourId);
                this.activeDriver = null;
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
        if (this.isTenantRoute(path) && !this.isCompleted('global-ui') && this.tours.has('global-ui')) {
            setTimeout(() => {
                this.start('global-ui', false, () => {
                    // Once global tour completes, trigger the page-specific tour if applicable
                    const pageTourId = this.getPageTourForRoute(path);
                    if (pageTourId && !this.isCompleted(pageTourId)) {
                        setTimeout(() => {
                            this.start(pageTourId, false);
                        }, 500);
                    }
                });
            }, 600);
            return;
        }

        // 2. Check for Page-specific tour
        const pageTourId = this.getPageTourForRoute(path);
        if (pageTourId && !this.isCompleted(pageTourId)) {
            setTimeout(() => {
                this.start(pageTourId, false);
            }, 500);
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
    }
}

export const tourManager = new TourManager();
