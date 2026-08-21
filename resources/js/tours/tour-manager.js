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
     * @param {object} config - { steps: Array, routePattern: RegExp|string }
     */
    register(tourId, config) {
        this.tours.set(tourId, config);
    }

    /**
     * Get completed tours from localStorage
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
     * Start a specific tour by ID
     * @param {string} tourId
     * @param {boolean} force - Start even if already completed
     */
    start(tourId, force = false) {
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
            },
            steps: validSteps
        });

        this.activeDriver.drive();
    }

    /**
     * Auto-detect and run the appropriate tour for the current URL pathname
     */
    autoRun() {
        const path = window.location.pathname;

        for (const [tourId, config] of this.tours.entries()) {
            if (config.routePattern) {
                const matches = typeof config.routePattern === 'string'
                    ? path.includes(config.routePattern)
                    : config.routePattern.test(path);

                if (matches && !this.isCompleted(tourId)) {
                    // Slight delay to ensure Livewire/Filament DOM is fully initialized
                    setTimeout(() => {
                        this.start(tourId, false);
                    }, 500);
                    break;
                }
            }
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
                // Find tour for current route and force start
                const path = window.location.pathname;
                for (const [id, config] of this.tours.entries()) {
                    if (config.routePattern) {
                        const matches = typeof config.routePattern === 'string'
                            ? path.includes(config.routePattern)
                            : config.routePattern.test(path);

                        if (matches) {
                            this.start(id, true);
                            break;
                        }
                    }
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
