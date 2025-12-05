/**
 * Menu Widget JavaScript Class
 * Zabbix 7.0.13 Custom Widget
 */

class WidgetMenuWidget extends CWidget {
    
    constructor(type, uniqueid, is_editable, dashboard_page_unique_id) {
        super(type, uniqueid, is_editable, dashboard_page_unique_id);
        
        this._has_contents = false;
        this._menu_data = null;
        this._current_index = 0;
        this._active_item_index = 0;
    }

    onInitialize() {
        // Initialize widget
        this._has_contents = true;
    }

    onActivate() {
        // Called when widget becomes active
    }

    onDeactivate() {
        // Called when widget becomes inactive
    }

    onResize() {
        // Handle widget resize
        this.adjustLayout();
    }

    onEdit() {
        // Called when entering edit mode
    }

    onEditEnd() {
        // Called when exiting edit mode
    }

    onUpdate(data) {
        // Update widget with new data
        if (data && data.menu_items) {
            this._menu_data = data;
            this.renderMenu();
        }
    }

    renderMenu() {
        const container = this._content_body.querySelector('.menu-widget-container');
        if (!container || !this._menu_data) {
            return;
        }

        const nav = this.createNavigation();
        const content = this.createContentArea();

        container.innerHTML = '';
        container.appendChild(nav);
        container.appendChild(content);

        this.updateMenuItems();
        this.attachEventListeners();
    }

    createNavigation() {
        const nav = document.createElement('div');
        nav.className = 'menu-nav';

        if (this._menu_data.collapsible) {
            const toggleBtn = document.createElement('div');
            toggleBtn.className = 'menu-toggle';
            toggleBtn.innerHTML = '☰';
            toggleBtn.addEventListener('click', () => this.toggleMenu());
            nav.appendChild(toggleBtn);
        }

        if (this._menu_data.items.length > this._menu_data.maxVisible) {
            const prevArrow = document.createElement('div');
            prevArrow.className = 'menu-nav-arrow menu-prev';
            prevArrow.innerHTML = '◀';
            prevArrow.addEventListener('click', () => this.navigatePrev());
            nav.appendChild(prevArrow);
        }

        const itemsWrapper = document.createElement('div');
        itemsWrapper.className = 'menu-items-wrapper';
        nav.appendChild(itemsWrapper);

        if (this._menu_data.items.length > this._menu_data.maxVisible) {
            const nextArrow = document.createElement('div');
            nextArrow.className = 'menu-nav-arrow menu-next';
            nextArrow.innerHTML = '▶';
            nextArrow.addEventListener('click', () => this.navigateNext());
            nav.appendChild(nextArrow);
        }

        return nav;
    }

    createContentArea() {
        const content = document.createElement('div');
        content.className = 'menu-content';
        content.id = 'menu-content-area-' + this._uniqueid;
        return content;
    }

    updateMenuItems() {
        const itemsWrapper = this._content_body.querySelector('.menu-items-wrapper');
        if (!itemsWrapper) {
            return;
        }

        itemsWrapper.innerHTML = '';

        const visibleItems = this._menu_data.items.slice(
            this._current_index,
            this._current_index + this._menu_data.maxVisible
        );

        visibleItems.forEach((item, index) => {
            const globalIndex = this._current_index + index;
            const menuItem = this.createMenuItem(item, globalIndex);
            itemsWrapper.appendChild(menuItem);
        });

        this.updateArrowStates();

        // Load first item by default
        if (this._menu_data.items.length > 0 && this._active_item_index === 0) {
            this.loadContent(this._menu_data.items[0].url);
        }
    }

    createMenuItem(item, globalIndex) {
        const menuItem = document.createElement('div');
        menuItem.className = 'menu-item';
        menuItem.dataset.index = globalIndex;

        if (item.image) {
            const img = document.createElement('img');
            img.className = 'menu-item-image';
            img.src = item.image;
            img.alt = item.label;
            menuItem.appendChild(img);
        }

        const label = document.createElement('span');
        label.className = 'menu-item-label';
        label.textContent = item.label;
        menuItem.appendChild(label);

        if (globalIndex === this._active_item_index) {
            menuItem.classList.add('active');
        }

        menuItem.addEventListener('click', () => {
            this._active_item_index = globalIndex;
            this.updateActiveItem();
            this.loadContent(item.url);
        });

        return menuItem;
    }

    updateActiveItem() {
        const items = this._content_body.querySelectorAll('.menu-item');
        items.forEach(item => {
            const index = parseInt(item.dataset.index);
            if (index === this._active_item_index) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    updateArrowStates() {
        const prevArrow = this._content_body.querySelector('.menu-prev');
        const nextArrow = this._content_body.querySelector('.menu-next');

        if (prevArrow) {
            if (this._current_index === 0) {
                prevArrow.classList.add('disabled');
            } else {
                prevArrow.classList.remove('disabled');
            }
        }

        if (nextArrow) {
            if (this._current_index >= this._menu_data.items.length - this._menu_data.maxVisible) {
                nextArrow.classList.add('disabled');
            } else {
                nextArrow.classList.remove('disabled');
            }
        }
    }

    navigatePrev() {
        if (this._current_index > 0) {
            this._current_index--;
            this.updateMenuItems();
        }
    }

    navigateNext() {
        if (this._current_index < this._menu_data.items.length - this._menu_data.maxVisible) {
            this._current_index++;
            this.updateMenuItems();
        }
    }

    toggleMenu() {
        const container = this._content_body.querySelector('.menu-widget-container');
        if (container) {
            container.classList.toggle('menu-collapsed');
        }
    }

    loadContent(url) {
        const contentArea = this._content_body.querySelector('#menu-content-area-' + this._uniqueid);
        if (!contentArea) {
            return;
        }

        if (url) {
            contentArea.classList.add('loading');
            
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.onload = () => {
                contentArea.classList.remove('loading');
            };
            iframe.onerror = () => {
                contentArea.classList.remove('loading');
                contentArea.innerHTML = '<div class="menu-content-empty">Erro ao carregar conteúdo</div>';
            };
            
            contentArea.innerHTML = '';
            contentArea.appendChild(iframe);
        } else {
            contentArea.innerHTML = '<div class="menu-content-empty">Nenhuma URL configurada para este item</div>';
        }
    }

    adjustLayout() {
        // Adjust layout on resize
        const container = this._content_body.querySelector('.menu-widget-container');
        if (container) {
            const width = container.offsetWidth;
            if (width < 480) {
                container.classList.add('mobile-view');
            } else {
                container.classList.remove('mobile-view');
            }
        }
    }

    attachEventListeners() {
        // Attach any additional event listeners
        window.addEventListener('resize', () => this.adjustLayout());
    }

    getUpdateRequestData() {
        return {
            uniqueid: this._uniqueid,
            ...this._fields
        };
    }

    hasPadding() {
        return true;
    }

    setEditMode() {
        super.setEditMode();
    }

    onClearContents() {
        super.onClearContents();
        this._current_index = 0;
        this._active_item_index = 0;
    }
}
