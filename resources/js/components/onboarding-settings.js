export function onboardingSettings(allTourIds = []) {
    return {
        storageKey: 'apis_hub_completed_tours',
        completedTours: [],
        allTourIds: allTourIds,

        init() {
            this.load();
            window.addEventListener('tour-status-changed', () => this.load());
        },

        load() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                this.completedTours = raw ? JSON.parse(raw) : [];
            } catch {
                this.completedTours = [];
            }
        },

        isEnabled(tourId) {
            return !this.completedTours.includes(tourId);
        },

        toggle(tourId) {
            if (this.isEnabled(tourId)) {
                if (!this.completedTours.includes(tourId)) {
                    this.completedTours.push(tourId);
                }
            } else {
                this.completedTours = this.completedTours.filter(id => id !== tourId);
            }
            localStorage.setItem(this.storageKey, JSON.stringify(this.completedTours));
            window.dispatchEvent(new CustomEvent('tour-status-changed', { detail: { tourId } }));
        },

        enableAll() {
            this.completedTours = [];
            localStorage.setItem(this.storageKey, JSON.stringify([]));
            window.dispatchEvent(new CustomEvent('tour-status-changed', { detail: { all: true } }));
        },

        disableAll() {
            this.completedTours = [...this.allTourIds];
            localStorage.setItem(this.storageKey, JSON.stringify(this.completedTours));
            window.dispatchEvent(new CustomEvent('tour-status-changed', { detail: { all: false } }));
        },

        playTour(tourId, targetUrl = null) {
            if (targetUrl && window.location.pathname !== targetUrl) {
                sessionStorage.setItem('force_play_tour', tourId);
                window.location.href = targetUrl;
            } else if (window.apisHubTours) {
                window.apisHubTours.start(tourId, true);
            } else {
                window.dispatchEvent(new CustomEvent('start-page-tour', { detail: { tourId, force: true } }));
            }
        }
    };
}
