export function kpiBrowser(config = {}) {
    return {
        kpis: config.kpis || [],
        categoryGroups: config.categoryGroups || {},
        search: '',
        selectedCategories: [],

        toggleCategory(cat) {
            const idx = this.selectedCategories.indexOf(cat);
            if (idx === -1) {
                this.selectedCategories.push(cat);
            } else {
                this.selectedCategories.splice(idx, 1);
            }
        },

        get filteredKpis() {
            return this.kpis.filter(kpi => {
                const q = this.search.toLowerCase().trim();
                if (q) {
                    const haystack = [
                        kpi.name,
                        kpi.type_label,
                        kpi.explanation,
                        kpi.use_case,
                        kpi.interpretation,
                    ].join(' ').toLowerCase();
                    if (!haystack.includes(q)) return false;
                }

                if (this.selectedCategories.length > 0) {
                    for (const cat of this.selectedCategories) {
                        if (!kpi.categories.includes(cat)) return false;
                    }
                }

                return true;
            });
        }
    };
}

if (typeof window !== 'undefined') {
    window.kpiBrowser = kpiBrowser;
}
