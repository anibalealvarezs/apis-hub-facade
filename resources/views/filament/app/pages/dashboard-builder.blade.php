<x-filament-panels::page>
    <div x-data="dashboardBuilder()" x-init="init()" class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 rounded-xl bg-gray-50 dark:bg-gray-900 p-4">
            <div class="flex items-center gap-2">
                <x-filament::icon name="heroicon-o-squares-2x2" class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                <span class="text-lg font-medium text-gray-900 dark:text-white">{{ $this->dashboard->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                <x-filament::button wire:click="saveLayout(gridState)" color="primary" icon="heroicon-o-check">
                    Save Layout
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4">
            {{-- Widget Palette (sidebar) --}}
            <div class="col-span-2 hidden lg:block">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-900 p-4 space-y-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Widget Palette</h3>
                    <div class="space-y-2">
                        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-3 text-center cursor-pointer hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                             x-on:click="openAddWidgetModal()">
                            <x-filament::icon name="heroicon-o-plus-circle" class="w-8 h-8 mx-auto text-gray-400 dark:text-gray-500" />
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Add Widget</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Drag widgets to reposition. Resize using the handle at the bottom-right corner.</p>
                    </div>
                </div>
            </div>

            {{-- Grid Area --}}
            <div class="col-span-12 lg:col-span-10">
                <div id="grid-container" class="rounded-xl bg-white dark:bg-gray-950 p-4 border border-gray-200 dark:border-gray-800">
                    <div id="grid-stack" class="grid-stack">
                        <template x-for="(widget, index) in widgets" :key="widget.id">
                            <div class="grid-stack-item"
                                 :gs-id="widget.id"
                                 :gs-x="widget.grid_x"
                                 :gs-y="widget.grid_y"
                                 :gs-w="widget.grid_w"
                                 :gs-h="widget.grid_h"
                                 :gs-min-w="2"
                                 :gs-min-h="2">
                                <div class="grid-stack-item-content rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
                                    {{-- Widget Header --}}
                                    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-t-lg">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="widget.title || widget.name"></span>
                                        <div class="flex items-center gap-1">
                                            <button class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                    x-on:click="configureWidget(widget.id)"
                                                    title="Configure">
                                                <x-filament::icon name="heroicon-o-cog-6-tooth" class="w-4 h-4" />
                                            </button>
                                            <button class="p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                    x-on:click="duplicateWidget(widget.id)"
                                                    title="Duplicate">
                                                <x-filament::icon name="heroicon-o-document-duplicate" class="w-4 h-4" />
                                            </button>
                                            <button class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-500"
                                                    x-on:click="deleteWidget(widget.id)"
                                                    title="Remove">
                                                <x-filament::icon name="heroicon-o-trash" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                    {{-- Widget Content (placeholder) --}}
                                    <div class="p-4 flex items-center justify-center h-[calc(100%-2.5rem)]">
                                        <div class="text-center">
                                            <x-filament::icon :name="'heroicon-o-chart-bar'" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" />
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                                <span x-text="widget.widget_type"></span>
                                                <template x-if="widget.source_type === 'kpi'">
                                                    <span> &middot; KPI</span>
                                                </template>
                                                <template x-if="widget.source_type === 'metric'">
                                                    <span> &middot; Metric</span>
                                                </template>
                                                <template x-if="widget.source_type === 'entity'">
                                                    <span> &middot; Entity</span>
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <template x-if="!widgets || widgets.length === 0">
                        <div class="flex flex-col items-center justify-center py-24 text-center">
                            <x-filament::icon name="heroicon-o-squares-2x2" class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" />
                            <p class="text-gray-500 dark:text-gray-400 text-lg">No widgets yet</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Click "Add Widget" in the palette to get started.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Add Widget Modal --}}
        <div x-show="showAddWidgetModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showAddWidgetModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add Widget</h2>

                {{-- Step 1: Source Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Source</label>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="(label, type) in sourceTypes" :key="type">
                            <button class="p-3 rounded-lg border text-center text-sm transition-colors"
                                    :class="addWidgetForm.source_type === type
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                    x-on:click="addWidgetForm.source_type = type; addWidgetForm.widget_type = ''">
                                <span x-text="label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Step 2: KPI (if kpi source) --}}
                <template x-if="addWidgetForm.source_type === 'kpi'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select KPI</label>
                        <select x-model="addWidgetForm.custom_kpi_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                            <option value="">Choose a KPI...</option>
                            <template x-for="(name, id) in kpis" :key="id">
                                <option :value="id" x-text="name"></option>
                            </template>
                        </select>
                    </div>
                </template>

                {{-- Step 3: Widget Type --}}
                <template x-if="addWidgetForm.source_type">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Widget Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="(label, type) in availableWidgetTypes" :key="type">
                                <button class="p-2 rounded-lg border text-center text-xs transition-colors"
                                        :class="addWidgetForm.widget_type === type
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                        x-on:click="addWidgetForm.widget_type = type">
                                    <span x-text="label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Step 4: Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Widget Name</label>
                    <input type="text" x-model="addWidgetForm.name"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                           placeholder="My Widget" />
                </div>

                <div class="flex justify-end gap-3">
                    <button class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                            x-on:click="showAddWidgetModal = false">Cancel</button>
                    <button class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-500 disabled:opacity-50"
                            :disabled="!canAddWidget()"
                            x-on:click="confirmAddWidget()">Add Widget</button>
                </div>
            </div>
        </div>

        {{-- Configure Widget Modal --}}
        <div x-show="showConfigureModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" x-on:click="showConfigureModal = false"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="'Configure: ' + (configureWidgetData.title || configureWidgetData.name)"></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Widget controls will be implemented in Phase 3 (Controls & Inheritance).</p>
                <div class="flex justify-end">
                    <button class="px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                            x-on:click="showConfigureModal = false">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack-all.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@7.2.3/dist/gridstack.min.css" />

        <script>
            function dashboardBuilder() {
                return {
                    widgets: @json($this->widgets ?? []),
                    gridLayout: @json($this->gridState ?? []),
                    grid: null,
                    showAddWidgetModal: false,
                    showConfigureModal: false,
                    configureWidgetData: {},
                    sourceTypes: @json($this->getAvailableSourceTypes()),
                    kpis: @json($this->getKpisForWidgetPicker()),
                    addWidgetForm: {
                        source_type: '',
                        custom_kpi_id: '',
                        widget_type: '',
                        name: '',
                    },

                    get availableWidgetTypes() {
                        if (!this.addWidgetForm.source_type) return {};
                        const types = @json($this->getAvailableWidgetTypes());
                        const filtered = {};
                        for (const [type, label] of Object.entries(types)) {
                            const compatible = @json(collect(\App\Services\WidgetTypeRegistry::getWidgetTypesForSource('kpi'))->values());
                            // Client-side check: filter by source_type
                            filtered[type] = label;
                        }
                        // We'll use Livewire to get compatible types
                        return types;
                    },

                    init() {
                        this.$nextTick(() => {
                            this.initGrid();
                        });
                    },

                    initGrid() {
                        const container = document.getElementById('grid-stack');
                        if (!container) return;

                        this.grid = GridStack.init({
                            column: 12,
                            cellHeight: 100,
                            verticalMargin: 12,
                            float: true,
                            acceptWidgets: true,
                            removable: false,
                            resizable: {
                                handles: 'se',
                            },
                            draggable: {
                                handle: '.grid-stack-item-content',
                            },
                        });

                        // Load existing widgets
                        if (this.widgets && this.widgets.length > 0) {
                            const items = this.widgets.map(w => ({
                                id: w.id,
                                x: w.grid_x,
                                y: w.grid_y,
                                w: w.grid_w,
                                h: w.grid_h,
                                'gs-id': String(w.id),
                            }));
                            this.grid.load(items);
                        }

                        // Track changes
                        this.grid.on('change', (event, items) => {
                            this.gridLayout = items.map(item => ({
                                id: parseInt(item.getAttribute('gs-id')) || item.id,
                                x: item.x,
                                y: item.y,
                                w: item.w,
                                h: item.h,
                            }));
                        });
                    },

                    openAddWidgetModal() {
                        this.addWidgetForm = { source_type: '', custom_kpi_id: '', widget_type: '', name: '' };
                        this.showAddWidgetModal = true;
                    },

                    canAddWidget() {
                        if (!this.addWidgetForm.source_type) return false;
                        if (!this.addWidgetForm.widget_type) return false;
                        if (this.addWidgetForm.source_type === 'kpi' && !this.addWidgetForm.custom_kpi_id) return false;
                        return true;
                    },

                    confirmAddWidget() {
                        if (!this.canAddWidget()) return;

                        const form = this.addWidgetForm;
                        const data = {
                            name: form.name || form.widget_type,
                            title: form.name || form.widget_type,
                            source_type: form.source_type,
                            source_config: form.source_type === 'kpi' ? { custom_kpi_id: form.custom_kpi_id } : {},
                            widget_type: form.widget_type,
                            controls: {},
                            grid_x: 0,
                            grid_y: 0,
                            grid_w: 4,
                            grid_h: 3,
                        };

                        @this.addWidget(data);
                        this.showAddWidgetModal = false;
                    },

                    configureWidget(id) {
                        const widget = this.widgets.find(w => w.id === id);
                        if (widget) {
                            this.configureWidgetData = widget;
                            this.showConfigureModal = true;
                        }
                    },

                    deleteWidget(id) {
                        if (confirm('Remove this widget?')) {
                            @this.deleteWidget(id);
                        }
                    },

                    duplicateWidget(id) {
                        @this.duplicateWidget(id);
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
