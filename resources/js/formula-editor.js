console.log('[FE] formula-editor.js evaluated');
if (!window.__formulaEditorRegistered) {
    window.__formulaEditorRegistered = true;
    console.log('[FE] Registered guard passed, adding alpine:init listener');

    document.addEventListener('alpine:init', () => {
        console.log('[FE] alpine:init fired — registering formulaEditor component');
        Alpine.data('formulaEditor', (config) => {
        console.log('[FE] formulaEditor component factory called');
        return {
        astStatePath: config.astStatePath,
        initialSeriesKeys: config.initialSeriesKeys || [],
        seriesData: config.seriesData || [],
        initialAst: config.initialAst || null,
        wire: config.wire,
        operators: config.operators,
        flatNodes: {},
        jsonAst: (config.initialAst && config.initialAst.type) ? JSON.stringify(config.initialAst) : '{}',
        nodeCounter: 0,
        seriesKeys: [],

        getSeriesKeys() {
            let items = [];

            if (this.seriesData && typeof this.seriesData === 'object') {
                if (Array.isArray(this.seriesData)) {
                    items = this.seriesData;
                } else {
                    items = Object.values(this.seriesData);
                }
            }

            if (items.length > 0) {
                return items.map((s, i) => s.key || String.fromCharCode(97 + i));
            }

            if (this.initialSeriesKeys && this.initialSeriesKeys.length > 0) {
                return this.initialSeriesKeys;
            }

            try {
                const raw = this.wire.get(this.astStatePath.replace('.ast', '.source_series'));
                if (raw && typeof raw === 'object') {
                    const arr = Array.isArray(raw) ? raw : Object.values(raw);
                    if (arr.length > 0) {
                        return arr.map((s, i) => s.key || String.fromCharCode(97 + i));
                    }
                }
            } catch (e) {}

            return this.initialSeriesKeys || [];
        },

        hydrateFromServer() {
            this.seriesKeys = this.getSeriesKeys();

            let raw = this.initialAst;

            if (!raw || typeof raw !== 'object' || !raw.type) {
                try {
                    raw = this.wire.get(this.astStatePath);
                } catch {}
            }
            if (typeof raw === 'string') {
                try { raw = JSON.parse(raw); } catch { raw = null; }
            }
            if (!raw || typeof raw !== 'object' || !raw.type) {
                this.flatNodes = {};
                this.jsonAst = '{}';
                return;
            }
            this.flatNodes = {};
            this.nodeCounter = 0;
            this.buildFlatNodes(raw, 'root', 0, 'root');
            this.jsonAst = JSON.stringify(raw);

            this.$nextTick(() => {
                this._syncSelects();
            });
        },

        _syncSelects() {
            this.$el.querySelectorAll('select').forEach(sel => {
                const model = sel.getAttribute('x-model');
                if (!model) return;
                const parts = model.split('.');
                if (parts[0] !== 'entry') return;
                const prop = parts[1];
                const row = sel.closest('[data-flat-key]');
                if (!row) return;
                const key = row.getAttribute('data-flat-key');
                const entry = this.flatNodes[key];
                if (!entry) return;
                const val = entry[prop];
                if (val !== undefined && val !== null && sel.value !== String(val)) {
                    sel.value = String(val);
                }
            });
        },

        buildFlatNodes(node, path, depth, side) {
            if (!node || !node.type) return;
            const entry = this.astToEntry(node, path);
            entry.depth = depth;
            entry.side = side;
            this.flatNodes[path] = entry;
            if (node.type === 'operator' && node.left && node.left.type === 'operator') {
                const childPath = path + '.left';
                this.buildFlatNodes(node.left, childPath, depth + 1, 'Left');
            }
        },

        astToEntry(node, path) {
            if (!node || node.type === undefined) return null;
            const entry = { path, depth: 0, side: 'root', leftType: '', leftValue: 0, operator: '', rightType: '', rightValue: 0, childPaths: [] };

            if (node.type === 'metric') {
                entry.leftType = 'metric:' + (node.metric || '');
            } else if (node.type === 'value') {
                entry.leftType = 'value';
                entry.leftValue = node.value ?? 0;
                entry.operator = '';
                entry.rightType = '';
            } else if (node.type === 'operator') {
                entry.leftType = 'operator';
                entry.operator = node.operator || '';
                if (node.left) {
                    if (node.left.type === 'metric') {
                        entry.leftType = 'metric:' + (node.left.metric || '');
                    } else if (node.left.type === 'value') {
                        entry.leftType = 'value';
                        entry.leftValue = node.left.value ?? 0;
                    } else if (node.left.type === 'operator') {
                        entry.leftType = 'operator';
                        entry.childPaths.push(path + '.left');
                    }
                }
                if (node.right) {
                    if (node.right.type === 'metric') {
                        entry.rightType = 'metric:' + (node.right.metric || '');
                    } else if (node.right.type === 'value') {
                        entry.rightType = 'value';
                        entry.rightValue = node.right.value ?? 0;
                    }
                }
            }
            return entry;
        },

        entryToAst(entry) {
            if (!entry) return null;
            if (entry.leftType === 'operator') {
                const leftChild = this.flatNodes[entry.childPaths[0]] || null;
                return {
                    type: 'operator',
                    operator: entry.operator,
                    left: leftChild ? this.entryToAst(leftChild) : { type: 'value', value: 0 },
                    right: this.makeOperand(entry.rightType, entry.rightValue),
                };
            }
            return {
                type: 'operator',
                operator: entry.operator,
                left: this.makeOperand(entry.leftType, entry.leftValue),
                right: this.makeOperand(entry.rightType, entry.rightValue),
            };
        },

        makeOperand(type, value) {
            if (!type) return { type: 'value', value: 0 };
            if (type === 'value') return { type: 'value', value: value ?? 0 };
            if (type.startsWith('metric:')) return { type: 'metric', metric: type.substring(7) };
            return { type: 'value', value: 0 };
        },

        onNodeChange(entry) {
            if (entry.leftType === 'operator' && entry.childPaths.length === 0) {
                const childPath = entry.path + '.left';
                this.nodeCounter++;
                this.flatNodes[childPath] = {
                    path: childPath,
                    depth: entry.depth + 1,
                    side: 'Left',
                    leftType: '', leftValue: 0, operator: '', rightType: '', rightValue: 0,
                    childPaths: [],
                };
                entry.childPaths = [childPath];
            }
            if (entry.leftType !== 'operator' && entry.childPaths.length > 0) {
                for (const cp of entry.childPaths) {
                    delete this.flatNodes[cp];
                }
                entry.childPaths = [];
            }
            this.rebuildAst();
        },

        rebuildAst() {
            const root = this.flatNodes['root'];
            if (!root || !root.leftType || !root.operator || !root.rightType) {
                this.jsonAst = '{}';
                return;
            }
            const ast = this.entryToAst(root);
            this.jsonAst = JSON.stringify(ast);

            try {
                this.wire.set(this.astStatePath, ast);
            } catch {}
        },

        addRootNode() {
            this.flatNodes['root'] = {
                path: 'root', depth: 0, side: 'root',
                leftType: '', leftValue: 0, operator: '', rightType: '', rightValue: 0,
                childPaths: [],
            };
        },

        refreshKeys() {
            this.seriesKeys = this.getSeriesKeys();
        },

        reset() {
            this.flatNodes = {};
            this.jsonAst = '{}';
            try {
                this.wire.set(this.astStatePath, null);
            } catch {}
        },
        };
    });
});
}
