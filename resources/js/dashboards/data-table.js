export function dataTable(config = {}) {
    return {
        rows: [],
        searchKeys: config.searchKeys || ['name'],
        searchQuery: config.searchQuery || '',
        sortCol: config.sortCol || '',
        sortDir: config.sortDir || 'desc',
        currentPage: config.currentPage || 1,
        pageSize: config.pageSize || 10,
        valueOf: config.valueOf || null,

        sortBy(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'desc' ? 'asc' : 'desc';
            } else {
                this.sortCol = col;
                this.sortDir = 'desc';
            }
            this.currentPage = 1;
        },

        get sortedRows() {
            let data = [...this.rows];
            const valueOf = this.valueOf || ((row, col) => row[col]);

            const query = (this.searchQuery || '').toLowerCase().trim();
            if (query) {
                data = data.filter(row => this.searchKeys.some(key => String(row[key] || '').toLowerCase().includes(query)));
            }

            return data.sort((a, b) => {
                let valA = Number(valueOf(a, this.sortCol));
                let valB = Number(valueOf(b, this.sortCol));

                if (isNaN(valA) || isNaN(valB)) {
                    valA = String(valueOf(a, this.sortCol) || '').toLowerCase();
                    valB = String(valueOf(b, this.sortCol) || '').toLowerCase();
                }

                if (valA === valB) return 0;
                if (this.sortDir === 'desc') return valA < valB ? 1 : -1;
                return valA > valB ? 1 : -1;
            });
        },

        get totalPages() {
            return Math.ceil(this.sortedRows.length / this.pageSize) || 1;
        },

        get paginatedRows() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + Number(this.pageSize);
            return this.sortedRows.slice(start, end);
        },

        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },

        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
        },
    };
}
