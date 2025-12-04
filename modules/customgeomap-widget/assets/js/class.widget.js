/**
 * Custom GeoMap Widget for Zabbix 7.0.13
 * Uses MapLibre GL JS v5.6.0
 * 
 * Widget is 100% JavaScript: backend provides JSON via widget.view.php
 * This class extends CWidgetBase (Zabbix 7) and uses the widget's body container.
 */

class CustomGeoMap extends CWidgetBase {
    static TYPE_GEOLOCATION_HOSTS = 0;

    onInitialize() {
        this._map = null;
        this._markers = [];
        this._expanded_markers = [];
        this._hosts = [];
        this._maptiler_key = '';
        this._style_url = '';
        this._severity_colors = {};
        this._severity_sizes = {};
        this._uniqueid = 'geomap-' + Math.random().toString(36).substr(2, 9);
        this._maplibre_loaded = false;
        this._container = null;
        this._retryCount = 0;
        this._maxRetries = 5;
        this._data_received = false;
    }

    onStart() {
        this._events = {
            ...this._events,
            acknowledge: (e, data) => {
                this.updateWidgetContent();
            }
        };
    }

    onActivate() {
        console.log('GeoMap: activated, attempting container detection');
        this._retryCount = 0;
        this._findAndRenderContainer();
    }

    _findAndRenderContainer() {
        this._container = this._getContainer();
        if (this._container) {
            console.log('GeoMap: container found, rendering map');
            // If we already have data, render immediately
            if (this._data_received) {
                this._renderMap();
            } else {
                // Show loading state
                this._showLoading();
            }
        } else if (this._retryCount < this._maxRetries) {
            console.warn(`GeoMap: container not found, retry ${this._retryCount + 1}/${this._maxRetries}`);
            this._retryCount++;
            setTimeout(() => this._findAndRenderContainer(), 100);
        } else {
            console.error('GeoMap: container not found after max retries');
        }
    }

    // Ensure MapLibre GL JS is loaded
    promiseReady() {
        const readiness = [super.promiseReady()];
        if (typeof maplibregl === 'undefined') {
            readiness.push(this._loadScript('https://unpkg.com/maplibre-gl@5.6.0/dist/maplibre-gl.js'));
            readiness.push(this._loadStylesheet('https://unpkg.com/maplibre-gl@5.6.0/dist/maplibre-gl.css'));
        }
        return Promise.all(readiness);
    }

    _loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector('script[src="'+src+'"]')) return resolve();
            const s = document.createElement('script');
            s.src = src;
            s.onload = () => resolve();
            s.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(s);
        });
    }

    _loadStylesheet(href) {
        return new Promise((resolve, reject) => {
            if (document.querySelector('link[href="'+href+'"]')) return resolve();
            const l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = href;
            l.onload = () => resolve();
            l.onerror = () => reject(new Error('Failed to load ' + href));
            document.head.appendChild(l);
        });
    }

    // Simplified container detection for Zabbix 7
    _getContainer() {
        // Strategy 1: Direct widget container access (most reliable)
        if (this._content_body && this._content_body instanceof HTMLElement) {
            console.log('GeoMap: found container via _content_body');
            return this._content_body;
        }

        // Strategy 2: Widget body container
        if (this._body && this._body instanceof HTMLElement) {
            console.log('GeoMap: found container via _body');
            return this._body;
        }

        // Strategy 3: jQuery wrapped containers
        if (this.$body && this.$body[0]) {
            console.log('GeoMap: found container via $body');
            return this.$body[0];
        }
        
        if (this.$container && this.$container[0]) {
            console.log('GeoMap: found container via $container');
            return this.$container[0];
        }
        
        // Strategy 4: Main container method
        if (typeof this.getMainContainer === 'function') {
            try {
                const container = this.getMainContainer();
                if (container) {
                    console.log('GeoMap: found container via getMainContainer');
                    return container;
                }
            } catch(e) {
                console.warn('GeoMap: getMainContainer failed', e);
            }
        }

        // Strategy 5: Find by widget ID in DOM
        if (this._widgetid) {
            const widgetElement = document.querySelector(`[data-widgetid="${this._widgetid}"]`);
            if (widgetElement) {
                const bodyContainer = widgetElement.querySelector('.dashboard-widget-body') || 
                                    widgetElement.querySelector('.widget-body');
                if (bodyContainer) {
                    console.log('GeoMap: found container via _widgetid');
                    return bodyContainer;
                }
            }
        }

        // Strategy 6: Find by unique ID if already created
        const existingContainer = document.getElementById(this._uniqueid);
        if (existingContainer && existingContainer.parentElement) {
            console.log('GeoMap: found existing container via unique ID');
            return existingContainer.parentElement;
        }

        console.warn('GeoMap: no container found in this attempt');
        return null;
    }

    _showLoading() {
        if (!this._container) return;
        this._container.innerHTML = '<div style="padding:20px;text-align:center;color:#999;height:100%;display:flex;align-items:center;justify-content:center;"><div style="display:inline-block;width:40px;height:40px;border:4px solid #f3f3f3;border-top:4px solid #3498db;border-radius:50%;animation:spin 1s linear infinite;"></div></div><style>@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>';
    }

    processUpdateResponse(response) {
        console.log('GeoMap: processUpdateResponse called', response);
        if (!response) return;
        this._applyResponse(response);
    }

    onDataUpdate(response) {
        console.log('GeoMap: onDataUpdate called', response);
        this._applyResponse(response);
    }

    _applyResponse(response) {
        console.log('GeoMap: _applyResponse - raw response:', response);
        
        // Handle different response structures in Zabbix 7.0
        let payload = null;
        
        // Try multiple ways to access the data
        if (response && response.data) {
            payload = response.data;
        } else if (response && response.body) {
            // Sometimes data comes in body
            try {
                payload = typeof response.body === 'string' ? JSON.parse(response.body) : response.body;
                if (payload && payload.data) {
                    payload = payload.data;
                }
            } catch(e) {
                console.warn('GeoMap: failed to parse body', e);
            }
        } else if (response) {
            // Response itself might be the data
            payload = response;
        }

        console.log('GeoMap: extracted payload:', payload);

        if (!payload) {
            console.error('GeoMap: no valid payload found');
            return;
        }

        this._hosts = payload.hosts || [];
        this._maptiler_key = payload.maptiler_key || this._maptiler_key || '';
        this._style_url = payload.style_url || this._style_url || '';
        this._severity_colors = payload.severity_colors || this._severity_colors || {};
        this._severity_sizes = payload.severity_sizes || this._severity_sizes || {};
        this._data_received = true;

        console.log('GeoMap: data extracted - hosts:', this._hosts.length, 'key:', this._maptiler_key ? 'present' : 'missing');

        // Ensure we have a container before rendering
        if (!this._container) {
            this._retryCount = 0;
            this._findAndRenderContainer();
        } else {
            console.log('GeoMap: rendering with data');
            requestAnimationFrame(() => this._renderMap());
        }
    }

    _renderMap() {
        if (!this._container) {
            console.error('GeoMap: container not found, aborting render');
            return;
        }

        // Apply proper container styling
        this._container.style.width = '100%';
        this._container.style.height = '100%';
        this._container.style.minHeight = '400px';
        this._container.style.overflow = 'hidden';
        this._container.style.position = 'relative';

        // Create interior wrapper if not present
        let inner = this._container.querySelector('#' + this._uniqueid);
        if (!inner) {
            inner = document.createElement('div');
            inner.id = this._uniqueid;
            inner.style.width = '100%';
            inner.style.height = '100%';
            inner.style.minHeight = '400px';
            this._container.innerHTML = ''; // Clear previous content
            this._container.appendChild(inner);
        }

        // Ensure previous map is removed
        if (this._map) {
            try { 
                this._map.remove(); 
            } catch(e){ 
                console.warn('GeoMap: remove previous map failed', e); 
            }
            this._map = null;
            this._markers.forEach(m => { try { m.remove(); } catch(e){} });
            this._markers = [];
            this._expanded_markers.forEach(m => { try { m.remove(); } catch(e){} });
            this._expanded_markers = [];
        }

        // Check for required configuration
        if (!this._maptiler_key) {
            inner.innerHTML = '<div style="padding:20px;text-align:center;color:#f0ad4e;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;"><strong>⚠️ MapTiler API Key not configured</strong><br><small>Configure the MapTiler API Key in widget settings.</small></div>';
            return;
        }

        if (!this._hosts || this._hosts.length === 0) {
            inner.innerHTML = '<div style="padding:20px;text-align:center;color:#5bc0de;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;"><strong>ℹ️ No hosts with coordinates found</strong><br><small>Configure latitude and longitude in host inventory.</small></div>';
            return;
        }

        // Check if MapLibre is available
        if (typeof maplibregl === 'undefined') {
            console.error('GeoMap: maplibregl not available');
            inner.innerHTML = '<div style="padding:20px;text-align:center;color:#d9534f;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;"><strong>Error loading MapLibre GL JS</strong><br><small>Please refresh the page.</small></div>';
            return;
        }

        // Determine style URL
        let style = this._style_url;
        if (style) {
            if (!/^https?:\/\//.test(style) && !style.includes('style.json')) {
                style = `https://api.maptiler.com/maps/${style}/style.json?key=${this._maptiler_key}`;
            }
        } else {
            style = `https://api.maptiler.com/maps/streets-v2/style.json?key=${this._maptiler_key}`;
        }

        const center = this._getInitialCenter();
        const zoom = this._fields && this._fields.initial_zoom ? parseFloat(this._fields.initial_zoom) : 4;

        try {
            this._map = new maplibregl.Map({
                container: inner,
                style: style,
                center: [center.lon, center.lat],
                zoom: zoom
            });

            this._map.on('load', () => {
                console.log('GeoMap: map loaded successfully');
                this._addMarkers();
            });

            this._map.on('moveend', () => this._updateMarkersClustering());
            this._map.on('zoomend', () => this._updateMarkersClustering());
            this._map.on('error', (e) => console.error('GeoMap: map error', e));
            
        } catch (e) {
            console.error('GeoMap: error creating map', e);
            inner.innerHTML = '<div style="padding:20px;text-align:center;color:#d9534f;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;"><strong>Error creating map</strong><br><small>' + e.message + '</small></div>';
        }
    }

    _getInitialCenter() {
        if (this._fields && this._fields.initial_lat && this._fields.initial_lon) {
            return { lat: parseFloat(this._fields.initial_lat), lon: parseFloat(this._fields.initial_lon) };
        }
        return { lat: -14.235, lon: -51.925 }; // Default to Brazil center
    }

    _addMarkers() {
        if (!this._map) return;
        this._markers.forEach(m => { try { m.remove(); } catch(e){} });
        this._markers = [];

        const groups = this._groupByLocation();
        groups.forEach(g => this._createMarker(g));
    }

    _groupByLocation() {
        const groups = new Map();
        const threshold = this._fields && this._fields.cluster_threshold ? parseFloat(this._fields.cluster_threshold) : 0.001;

        (this._hosts || []).forEach(h => {
            if (!h.latitude || !h.longitude) return;
            const lat = parseFloat(h.latitude);
            const lon = parseFloat(h.longitude);
            let found = null;
            for (const [k, grp] of groups) {
                const d = Math.sqrt(Math.pow(grp.lat - lat, 2) + Math.pow(grp.lon - lon, 2));
                if (d < threshold) { found = grp; break; }
            }
            if (found) found.hosts.push(h);
            else groups.set(`${lat.toFixed(4)}_${lon.toFixed(4)}`, {lat, lon, hosts:[h]});
        });

        return Array.from(groups.values());
    }

    _createMarker(group) {
        const severity = this._getMaxSeverity(group.hosts);
        const color = this._getSeverityColor(severity);
        const size = this._getSeveritySize(severity);

        const el = document.createElement('div');
        el.className = 'custom-marker';
        el.style.cssText = `
            width: ${size}px; height: ${size}px; background:${color}; border:3px solid white; border-radius:50%;
            display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold; cursor:pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        `;

        const label = document.createElement('div');
        label.className = 'marker-label';
        label.style.cssText = 'position:absolute; top:-26px; background:rgba(0,0,0,0.75); color:white; padding:4px 6px; border-radius:4px; font-size:12px; display:none; white-space:nowrap;';
        label.textContent = group.hosts.length === 1 ? this._applyRegex(group.hosts[0].hostname) : (`${group.hosts.length} hosts`);
        el.appendChild(label);

        if (group.hosts.length > 1) el.textContent = group.hosts.length;

        const popup = this._createPopup(group.hosts);

        const marker = new maplibregl.Marker({element: el})
            .setLngLat([group.lon, group.lat])
            .setPopup(popup)
            .addTo(this._map);

        // Add events
        el.addEventListener('mouseenter', () => { 
            label.style.display = 'block'; 
            el.style.transform = 'scale(1.2)'; 
            el.style.zIndex = '1000'; 
        });
        el.addEventListener('mouseleave', () => { 
            label.style.display = 'none'; 
            el.style.transform = 'scale(1)'; 
            el.style.zIndex = '1'; 
        });

        if (group.hosts.length > 1) {
            el.addEventListener('click', (e) => { 
                e.stopPropagation(); 
                this._expandMarkers(group, marker); 
            });
        }

        this._markers.push(marker);
    }

    _expandMarkers(group, centerMarker) {
        this._expanded_markers.forEach(m => { try { m.remove(); } catch(e){} });
        this._expanded_markers = [];
        try { centerMarker.getElement().style.display = 'none'; } catch(e){}

        const radius = 0.002;
        const angle = (2*Math.PI)/group.hosts.length;
        group.hosts.forEach((h,i) => {
            const a = angle * i;
            const lat = group.lat + radius * Math.sin(a);
            const lon = group.lon + radius * Math.cos(a);
            const severity = h.max_severity || 0;
            const color = this._getSeverityColor(severity);
            const size = this._getSeveritySize(severity);

            const el = document.createElement('div');
            el.className = 'expanded-marker';
            el.style.cssText = `width:${size}px;height:${size}px;background:${color};border:3px solid white;border-radius:50%;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.3);`;
            const label = document.createElement('div');
            label.className='marker-label';
            label.style.cssText='position:absolute;top:-26px;background:rgba(0,0,0,0.75);color:#fff;padding:4px 6px;border-radius:4px;font-size:12px;white-space:nowrap;';
            label.textContent = this._applyRegex(h.hostname);
            el.appendChild(label);

            const popup = this._createPopup([h]);
            const marker = new maplibregl.Marker({element:el})
                .setLngLat([lon, lat])
                .setPopup(popup)
                .addTo(this._map);
            this._expanded_markers.push(marker);
        });

        const collapseBtn = document.createElement('div');
        collapseBtn.className='collapse-button';
        collapseBtn.textContent='×';
        collapseBtn.style.cssText='position:absolute;width:30px;height:30px;background:#ff4444;color:#fff;border:2px solid #fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;';

        collapseBtn.addEventListener('click', () => {
            this._expanded_markers.forEach(m => { try { m.remove(); } catch(e){} });
            this._expanded_markers = [];
            try { centerMarker.getElement().style.display = 'flex'; } catch(e){}
            try { collapseMarker.remove(); } catch(e){}
        });

        const collapseMarker = new maplibregl.Marker({element:collapseBtn})
            .setLngLat([group.lon, group.lat])
            .addTo(this._map);
        this._expanded_markers.push(collapseMarker);
    }

    _createPopup(hosts) {
        const show_maintenance = this._fields && this._fields.show_maintenance !== '0';
        const show_acknowledged = this._fields && this._fields.show_acknowledged !== '0';
        let html = '<div style="max-height:300px;overflow:auto;min-width:250px;"><table style="width:100%;border-collapse:collapse;"><tr><th style="text-align:left;padding:6px;border-bottom:1px solid #ddd">Host</th><th style="text-align:left;padding:6px;border-bottom:1px solid #ddd">Group</th><th style="text-align:right;padding:6px;border-bottom:1px solid #ddd">Alerts</th></tr>';
        hosts.forEach(h => {
            if (!show_maintenance && h.maintenance_status == 1) return;
            let count = h.problem_count || 0;
            if (!show_acknowledged) count = h.unacknowledged_count || count;
            const color = count>0 ? '#ff4444' : '#44ff44';
            html += `<tr><td style="padding:6px;border-bottom:1px solid #eee">${h.hostname}</td><td style="padding:6px;border-bottom:1px solid #eee;font-size:11px;color:#666">${h.groupname}</td><td style="padding:6px;text-align:right;border-bottom:1px solid #eee"><a href="zabbix.php?action=problem.view&filter_hostids[]=${h.hostid}" style="color:${color};text-decoration:none;font-weight:bold">${count}</a></td></tr>`;
        });
        html += '</table></div>';
        
        return new maplibregl.Popup({closeButton:true,closeOnClick:false,maxWidth:500}).setHTML(html);
    }

    _getMaxSeverity(hosts) {
        let m = 0;
        (hosts||[]).forEach(h => { if (h.max_severity > m) m = h.max_severity; });
        return m;
    }

    _getSeverityColor(sev) {
        const colors = (this._severity_colors && Object.keys(this._severity_colors).length) ? this._severity_colors : (this._fields && this._fields.severity_colors) || {'0':'#97AAB3','1':'#7499FF','2':'#FFC859','3':'#FFA059','4':'#E97659','5':'#E45959'};
        return colors[sev.toString()] || colors['0'];
    }

    _getSeveritySize(sev) {
        const sizes = (this._severity_sizes && Object.keys(this._severity_sizes).length) ? this._severity_sizes : (this._fields && this._fields.severity_sizes) || {'0':20,'1':25,'2':30,'3':35,'4':40,'5':45};
        return sizes[sev.toString()] || sizes['0'];
    }

    _applyRegex(name) {
        if (!this._fields || !this._fields.name_regex) return name;

        const raw = this._fields.name_regex.trim();
        let pattern = raw;
        let flags = 'g';

        try {
            const match = raw.match(/^\/(.+)\/([a-z]*)$/i);
            if (match) {
                pattern = match[1];
                flags = match[2] || 'g';
            }
            const r = new RegExp(pattern, flags);
            const rep = this._fields.name_replacement || '';
            return name.replace(r, rep);
        }
        catch (e) {
            console.error('Invalid regex in widget:', raw, e);
            return name;
        }
    }

    _updateMarkersClustering() {
        this._addMarkers();
    }

    onResize() {
        if (this._map && typeof this._map.resize === 'function') {
            try { this._map.resize(); } catch(e){}
        }
    }

    onClearContents() {
        if (this._map) { 
            try { this._map.remove(); } catch(e){} 
            this._map = null; 
        }
        this._markers = [];
        this._expanded_markers = [];
        if (this._container) {
            this._container.innerHTML = '';
        }
    }
}

// Minimal styles injection
const style = document.createElement('style');
style.textContent = `
.custom-marker { position: relative; }
.marker-label { display: none; }
@keyframes expandMarker { from { transform: scale(0); opacity:0 } to { transform: scale(1); opacity:1 } }
`;
document.head.appendChild(style);
