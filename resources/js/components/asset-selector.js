export function assetSelector(config = {}) {
    return {
        state: {},
        options: config.options || {},
        searchQueries: {},

        init() {
            let raw = config.existingSelections;
            let parsed = {};
            if (typeof raw === 'string') {
                try {
                    parsed = JSON.parse(raw);
                } catch (e) {
                    parsed = {};
                }
            } else if (raw && typeof raw === 'object') {
                parsed = raw;
            }
            if (Array.isArray(parsed)) parsed = {};
            this.state = parsed;

            for (const key in this.options) {
                this.searchQueries[key] = '';
                if (this.state[key] === undefined) {
                    this.state[key] = [];
                }
            }

            this.syncState();
        },

        syncState() {
            this.$wire.call('setAssetSelections', JSON.stringify(this.state));
        },

        toggleAsset(channelKey, assetId) {
            const current = this.state[channelKey] || [];
            const idStr = String(assetId);
            const idx = current.indexOf(idStr);

            if (idx > -1) {
                this.state[channelKey] = current.filter((id) => id !== idStr);
            } else {
                this.state[channelKey] = [...current, idStr];
            }

            this.syncState();
        },

        selectAll(channelKey) {
            this.state[channelKey] = Object.entries(this.options[channelKey].assets)
                .filter(([id, obj]) => obj.enabled)
                .map(([id, obj]) => String(id));
            this.syncState();
        },

        clearAll(channelKey) {
            this.state[channelKey] = [];
            this.syncState();
        },
    };
}
