/**
 * Menu Widget Edit Form JavaScript
 * Zabbix 7.0.13 Custom Widget
 */

jQuery(function($) {
    'use strict';

    // Initialize menu items management
    const menuItemsManager = {
        items: [],
        container: null,
        
        init: function(initialItems) {
            this.items = initialItems || [];
            this.container = $('#menu_items_table tbody');
            this.render();
            this.attachEvents();
        },
        
        render: function() {
            this.container.empty();
            
            this.items.forEach((item, index) => {
                const row = this.createRow(item, index);
                this.container.append(row);
            });
            
            if (this.items.length === 0) {
                this.container.append(
                    '<tr><td colspan="4" style="text-align: center; color: #999;">Nenhum item de menu adicionado</td></tr>'
                );
            }
        },
        
        createRow: function(item, index) {
            const row = $('<tr>').attr('data-index', index);
            
            // Label cell
            const labelCell = $('<td>').append(
                $('<input>')
                    .attr({
                        type: 'text',
                        name: `menu_items[${index}][label]`,
                        placeholder: 'Label do Menu'
                    })
                    .addClass('form-control')
                    .val(item.label || '')
                    .on('input', () => this.updateItem(index, 'label', event.target.value))
            );
            
            // URL cell
            const urlCell = $('<td>').append(
                $('<input>')
                    .attr({
                        type: 'text',
                        name: `menu_items[${index}][url]`,
                        placeholder: 'https://exemplo.com ou zabbix.php?action=...'
                    })
                    .addClass('form-control')
                    .val(item.url || '')
                    .on('input', () => this.updateItem(index, 'url', event.target.value))
            );
            
            // Image cell
            const imageSelect = this.createImageSelect(item, index);
            const imageCell = $('<td>').append(imageSelect);
            
            // Actions cell
            const actionsCell = $('<td>').append(
                $('<button>')
                    .attr('type', 'button')
                    .addClass('btn-link')
                    .text('Remover')
                    .on('click', () => this.removeItem(index))
            );
            
            row.append(labelCell, urlCell, imageCell, actionsCell);
            return row;
        },
        
        createImageSelect: function(item, index) {
            const select = $('<select>')
                .attr('name', `menu_items[${index}][image]`)
                .addClass('form-control')
                .on('change', (e) => this.updateItem(index, 'image', e.target.value));
            
            // Empty option
            select.append($('<option>').val('').text('-- Sem imagem --'));
            
            // Zabbix default images
            const zabbixImages = [
                'icon_warning.png',
                'icon_info.png',
                'icon_error.png',
                'icon_ok.png',
                'icon_maintenance.png',
                '/images/Unknown.jpg'
            ];
            
            zabbixImages.forEach(img => {
                const option = $('<option>')
                    .val(`images/${img}`)
                    .text(img);
                
                if (item.image === `images/${img}`) {
                    option.prop('selected', true);
                }
                
                select.append(option);
            });
            
            return select;
        },
        
        addItem: function() {
            this.items.push({
                label: '',
                url: '',
                image: ''
            });
            this.render();
        },
        
        removeItem: function(index) {
            if (confirm('Tem certeza que deseja remover este item?')) {
                this.items.splice(index, 1);
                this.render();
            }
        },
        
        updateItem: function(index, field, value) {
            if (this.items[index]) {
                this.items[index][field] = value;
            }
        },
        
        attachEvents: function() {
            $('#menu_item_add').on('click', () => this.addItem());
        },
        
        getItems: function() {
            return this.items;
        }
    };
    
    // Make manager available globally for form submission
    window.menuItemsManager = menuItemsManager;
    
    // Field dependencies
    $(document).on('change', '[name="menu_orientation"]', function() {
        const isVertical = $(this).val() === 'vertical';
        $('[name="menu_position"]').closest('.form-field').toggle(isVertical);
    });
    
    $(document).on('change', '[name="collapsible"]', function() {
        const isCollapsible = $(this).is(':checked');
        $('[name="collapsed_by_default"]').closest('.form-field').toggle(isCollapsible);
    });
    
    $(document).on('change', '[name="show_group_name"]', function() {
        const showGroupName = $(this).is(':checked');
        $('[name="group_name_text"]').closest('.form-field').toggle(showGroupName);
    });
    
    // Trigger initial state
    $('[name="menu_orientation"]').trigger('change');
    $('[name="collapsible"]').trigger('change');
    $('[name="show_group_name"]').trigger('change');
    
    // Form validation
    $(document).on('submit', 'form[name="widget_dialogue_form"]', function(e) {
        const items = window.menuItemsManager.getItems();
        
        // Validate at least one item
        if (items.length === 0) {
            alert('Por favor, adicione pelo menos um item ao menu.');
            e.preventDefault();
            return false;
        }
        
        // Validate each item has a label
        for (let i = 0; i < items.length; i++) {
            if (!items[i].label || items[i].label.trim() === '') {
                alert(`O item ${i + 1} precisa ter um label.`);
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });
});
