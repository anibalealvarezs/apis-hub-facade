export function dataTable(config = {}) {
    return {
        rows: [],
        searchKeys: config.searchKeys || ['name'],
        searchQuery: config.searchQuery || '',
        sortCol: config.sortCol || '',
        sortDir: config.sortDir || 'desc',
        currentPage: config.currentPage || 1,
        pageSize: config.pageSize || 10,
        valueOf: Object.prototype.hasOwnProperty.call(config, 'valueOf') ? config.valueOf : null,

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

        exportCsv(filename = 'table_export') {
            const data = this.sortedRows;
            if (!data || data.length === 0) return;

            const firstRow = data[0];
            const keys = Object.keys(firstRow).filter(k => typeof firstRow[k] !== 'function' && !k.startsWith('_'));
            if (keys.length === 0) return;

            const csvLines = [];
            csvLines.push(keys.map(k => `"${String(k).replace(/"/g, '""')}"`).join(','));

            for (const row of data) {
                const rowValues = keys.map(k => {
                    let val = row[k];
                    if (val === null || val === undefined) {
                        return '""';
                    }
                    if (typeof val === 'object') {
                        val = JSON.stringify(val);
                    }
                    return `"${String(val).replace(/"/g, '""')}"`;
                });
                csvLines.push(rowValues.join(','));
            }

            const csvString = '\uFEFF' + csvLines.join('\r\n');
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const safeName = (filename || 'export').replace(/[^a-zA-Z0-9_-]/g, '_');
            a.download = safeName.endsWith('.csv') ? safeName : `${safeName}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },
    };
}
