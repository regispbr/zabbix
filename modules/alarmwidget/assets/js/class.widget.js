/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
** ...
*/

class WidgetAlarm extends CWidget {

    static ZBX_STYLE_CLASS = 'alarm-widget';

    onInitialize() {
        this._table = this._target.querySelector('table.list-table');
        this._refresh_interval = this._fields.refresh_interval || 60;
        this._sort_column = this._fields.sort_by || 'eventid';
        this._sort_order = 'desc';

        this.initializeTable();
        this.startAutoRefresh();
        
        this._target.classList.remove('is-loading');
    }

    onResize() {
        // Handle resize if needed
    }

    initializeTable() {
        if (!this._table) return;

        const headers = this._table.querySelectorAll('th[data-column]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', (e) => {
                const column = e.target.getAttribute('data-column');
                this.sortTable(column);
            });
        });

        const rows = this._table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', (e) => {
                // Lógica de clique na linha (opcional)
            });
        });

        // Classificação inicial
        this.sortTable(this._sort_column, true);
    }

    sortTable(column, initial_sort = false) {
        if (!this._table) return;

        if (this._sort_column === column && !initial_sort) {
            this._sort_order = (this._sort_order === 'asc') ? 'desc' : 'asc';
        } else {
            this._sort_column = column;
            this._sort_order = (column === 'host') ? 'asc' : 'desc';
        }

        const tbody = this._table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const headers = Array.from(this._table.querySelectorAll('th[data-column]'));
        
        // Fallback para 'time' se a coluna não existir visualmente
        let columnIndex = headers.findIndex(h => h.getAttribute('data-column') === column);
        if (columnIndex === -1 && column === 'eventid') {
            columnIndex = headers.findIndex(h => h.getAttribute('data-column') === 'time');
        }
        if (columnIndex === -1) return;

        rows.sort((a, b) => {
            const a_cell = a.cells[columnIndex];
            const b_cell = b.cells[columnIndex];

            let aValue = a_cell.dataset.sortValue || a_cell.textContent.trim();
            let bValue = b_cell.dataset.sortValue || b_cell.textContent.trim();

            let comparison = 0;
            
            // Verifica se é numérico (Time, Age, Severity)
            if (column === 'age' || column === 'time' || column === 'severity') {
                comparison = parseFloat(aValue) - parseFloat(bValue);
            } else {
                comparison = aValue.localeCompare(bValue);
            }

            return this._sort_order === 'asc' ? comparison : -comparison;
        });

        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
        this.updateSortIndicators(column);
    }

    updateSortIndicators(column) {
        const headers = this._table.querySelectorAll('th[data-column]');
        headers.forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
        });

        const currentHeader = this._table.querySelector(`th[data-column="${column}"]`);
        if (currentHeader) {
            currentHeader.classList.add(`sort-${this._sort_order}`);
        }
    }

    startAutoRefresh() {
        if (this._refresh_interval > 0) {
            this._refresh_timer = setInterval(() => {
                this.refresh();
            }, this._refresh_interval * 1000);
        }
    }

    stopAutoRefresh() {
        if (this._refresh_timer) {
            clearInterval(this._refresh_timer);
            this._refresh_timer = null;
        }
    }

    refresh() {
        this._startUpdating();
    }

    getUpdateRequestData() {
        return {
            ...super.getUpdateRequestData(),
            groupids: this._fields.groupids || [],
            hostids: this._fields.hostids || [],
            exclude_hostids: this._fields.exclude_hostids || [],
            severities: this._fields.severities || [],
            exclude_maintenance: this._fields.exclude_maintenance || 0,
            
            // Tags (Tarefa 1)
            evaltype: this._fields.evaltype || 0,
            tags: this._fields.tags || [],
            
            problem_status: this._fields.problem_status || 0,
            show_ack: this._fields.show_ack || 0,
            show_suppressed: this._fields.show_suppressed || 0,
            sort_by: this._fields.sort_by || 0, // 0 = Time

            // Colunas
            show_column_host: this._fields.show_column_host || 0,
            show_column_severity: this._fields.show_column_severity || 0,
            show_column_status: this._fields.show_column_status || 0,
            show_column_problem: this._fields.show_column_problem || 0,
            show_column_operational_data: this._fields.show_column_operational_data || 0,
            show_column_ack: this._fields.show_column_ack || 0,
            show_column_age: this._fields.show_column_age || 0,
            show_column_time: this._fields.show_column_time || 0,

            refresh_interval: this._fields.refresh_interval || 60,
            show_lines: this._fields.show_lines || 25
        };
    }

    setContents(response) {
        super.setContents(response);
        
        // --- LOG DE DEBUG ---
        // Só imprime se estiver no Debug Mode do Zabbix e houver logs
        if (response.debug_log && response.debug_log.length > 0) {
            console.group(`%c AlarmWidget Debug [${this._uniqueid}]`, 'color: #e0e0e0; background: #333; padding: 4px;');
            response.debug_log.forEach(msg => console.log(msg));
            console.groupEnd();
        }
        // --------------------

        if (response.fields_values) {
            this._fields = response.fields_values;
        }

        this._target.classList.remove('is-loading');
        
        this._table = this._target.querySelector('table.list-table');
        this.initializeTable();
        
        // Mantém a classificação ao atualizar
        if (this._sort_column) {
            this.sortTable(this._sort_column, true);
        }
    }

    onDestroy() {
        this.stopAutoRefresh();
    }

    hasPadding() {
        return true;
    }
}
