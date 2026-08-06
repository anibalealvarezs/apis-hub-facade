export function dataSources(config = {}) {
    return {
        activeTab: config.activeTab,
        isOwner: config.isOwner,
        ownerLimit: config.ownerLimit,
        globalLedgerCount: config.globalLedgerCount,
        lockedAssets: config.lockedAssets || [],
        lockStates: config.lockStates || {},
        cycleBounds: config.cycleBounds || {},
        projectDeploymentTime: config.projectDeploymentTime,
        assetGroupsData: config.assetGroupsData || {},
        providers: config.providers || {},
        currentTime: new Date().getTime(),
        cycleLabel: config.cycleLabel || 'Cycle',
        quotaLockedLabel: config.quotaLockedLabel || 'Quota Locked',
        lockedUntilCycleEndLabel: config.lockedUntilCycleEndLabel || 'Locked until cycle end',
        gracePeriodPausedLabel: config.gracePeriodPausedLabel || 'Grace Period paused (Waiting for deployment)',
        gracePeriodEndedLabel: config.gracePeriodEndedLabel || 'Grace Period (Ended)',
        gracePeriodLabel: config.gracePeriodLabel || 'Grace Period (Ends in',
        groupsLabel: config.groupsLabel || 'Groups:',

        init() {
            setInterval(() => {
                this.currentTime = new Date().getTime();
            }, 1000);

            const apply = () => document.querySelectorAll('.fi-fo-repeater-item').forEach(el => {
                if (el.style.position !== 'relative') el.style.position = 'relative';
            });

            this.$nextTick(apply);
            new MutationObserver(apply).observe(document.body, { childList: true, subtree: true });
        },

        getAssetBadgeColor(id) {
            if (!id || !this.lockStates[id]) return null;
            const lock = this.lockStates[id];

            if (lock.status === 'locked') return '#22c55e';
            if (lock.status === 'pending_release') return '#ef4444';
            if (lock.status === 'staged') {
                if (!this.projectDeploymentTime) return '#9ca3af';
                const stagedAt = new Date(lock.staged_at).getTime();
                const endsAt = stagedAt + (2 * 60 * 60 * 1000);
                if (endsAt - this.currentTime <= 0) return '#ef4444';
                return '#f97316';
            }
            return null;
        },

        getBadgeStyle(id) {
            const color = this.getAssetBadgeColor(id);
            if (!color) return 'display:none';
            return `position:absolute;top:0.75rem;right:0.75rem;width:1rem;height:1rem;border-radius:9999px;box-shadow:0 0 0 2px white;background-color:${color}`;
        },

        getAssetBadgeLabel(id) {
            if (!id || !this.lockStates[id]) return '';
            const lock = this.lockStates[id];

            if (lock.status === 'locked') return this.quotaLockedLabel;
            if (lock.status === 'pending_release') return `${this.lockedUntilCycleEndLabel} (${this.cycleBounds.ends_at})`;
            if (lock.status === 'staged') {
                if (!this.projectDeploymentTime) return this.gracePeriodPausedLabel;
                const stagedAt = new Date(lock.staged_at).getTime();
                const endsAt = stagedAt + (2 * 60 * 60 * 1000);
                const remainingMs = endsAt - this.currentTime;
                if (remainingMs <= 0) return this.gracePeriodEndedLabel;
                const totalSec = Math.floor(remainingMs / 1000);
                const h = Math.floor(totalSec / 3600);
                const m = Math.floor((totalSec % 3600) / 60);
                const s = totalSec % 60;
                const timeStr = h > 0 ? `${h}h ${m}m ${s}s` : `${m}m ${s}s`;
                return `${this.gracePeriodLabel} ${timeStr})`;
            }
            return '';
        },

        getAssetGroups(id) {
            if (!this.assetGroupsData || !this.assetGroupsData.assetMap) return [];
            return this.assetGroupsData.assetMap[id] || [];
        },

        getAssetGroupsTooltip(id) {
            const groups = this.getAssetGroups(id);
            if (groups.length === 0) return '';
            const title = this.groupsLabel;
            const list = groups.map(g => `<li class='ml-2'>&bull; ${g}</li>`).join('');
            return `<div class='text-left font-sans'><strong class='block mb-1 font-medium text-gray-200'>${title}</strong><ul class='text-xs text-gray-300 leading-tight'>${list}</ul></div>`;
        },

        getAssetBadgeText(id) {
            if (!id || !this.lockStates[id]) return '';
            const lock = this.lockStates[id];
            if (lock.status === 'staged' && this.projectDeploymentTime) {
                const stagedAt = new Date(lock.staged_at).getTime();
                const endsAt = stagedAt + (2 * 60 * 60 * 1000);
                const remainingMs = endsAt - this.currentTime;
                if (remainingMs > 0) {
                    const totalSec = Math.floor(remainingMs / 1000);
                    const h = Math.floor(totalSec / 3600);
                    const m = Math.floor((totalSec % 3600) / 60);
                    const s = totalSec % 60;
                    return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                }
            }
            return '';
        },

        getAssetBadgeTextColor(id) {
            if (!id || !this.lockStates[id]) return '';
            const lock = this.lockStates[id];
            if (lock.status === 'staged' && this.projectDeploymentTime) {
                const stagedAt = new Date(lock.staged_at).getTime();
                const endsAt = stagedAt + (2 * 60 * 60 * 1000);
                if (endsAt - this.currentTime > 0) return 'text-orange-500 dark:text-orange-400';
            }
            return '';
        },

        get formAssets() {
            const formAssets = new Set();
            const data = this.$wire.get('data') || {};

            const scan = (obj) => {
                if (typeof obj === 'object' && obj !== null) {
                    if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access') || obj.hasOwnProperty('platformId'))) {
                        if (obj.enabled && !obj.lost_access) {
                            const id = obj.id || obj.url || obj.platformId;
                            if (id) formAssets.add(id);
                        }
                        return;
                    }
                    for (const key in obj) scan(obj[key]);
                }
            };

            for (const key in data) scan(data[key]);
            return formAssets;
        },

        get currentProjectUsage() {
            const locked = new Set(this.lockedAssets);
            const form = this.formAssets;
            return new Set([...locked, ...form]).size;
        },

        get usageData() {
            const lockedSize = new Set(this.lockedAssets).size;
            const newlyStaged = this.currentProjectUsage - lockedSize;

            if (this.isOwner) {
                return {
                    usage: this.globalLedgerCount + newlyStaged,
                    limit: this.ownerLimit,
                };
            }

            const availableGlobalQuota = Math.max(0, this.ownerLimit - this.globalLedgerCount);
            return {
                usage: this.currentProjectUsage,
                limit: lockedSize + availableGlobalQuota,
            };
        },

        get selectedCount() {
            return this.usageData.usage;
        },

        get maxAssets() {
            return this.usageData.limit;
        },

        getChannelCount(channelKey) {
            let count = 0;
            const data = this.$wire.get('data') || {};
            const channelData = data[channelKey] || {};

            const scan = (obj) => {
                if (typeof obj === 'object' && obj !== null) {
                    if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access') || obj.hasOwnProperty('platformId'))) {
                        if (obj.enabled && !obj.lost_access) count++;
                        return;
                    }
                    for (const key in obj) scan(obj[key]);
                }
            };

            scan(channelData);
            return count;
        },

        getProviderCount(providerKey) {
            let count = 0;
            const providers = this.providers;

            if (providerKey && providers[providerKey]) {
                providers[providerKey].channels.forEach(ch => {
                    count += this.getChannelCount(ch.key);
                });
            } else if (!providerKey) {
                for (const pk in providers) {
                    providers[pk].channels.forEach(ch => {
                        count += this.getChannelCount(ch.key);
                    });
                }
            }
            return count;
        },
    };
}
