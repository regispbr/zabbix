/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
**/

class WidgetReports extends CWidget {

	onInitialize() {
		this._has_contents = false;
	}

	onActivate() {
		this.initializeCharts();
		this.attachEventHandlers();
	}

	onDeactivate() {
		this.destroyCharts();
	}

	promiseUpdate() {
		return super.promiseUpdate()
			.then(() => {
				this._has_contents = true;
				this.initializeCharts();
				this.attachEventHandlers();
			});
	}

	initializeCharts() {
		const chartContainers = this._content_body.querySelectorAll('.chart-container');
		
		chartContainers.forEach(container => {
			const chartType = container.getAttribute('data-chart-type');
			const chartLabels = JSON.parse(container.getAttribute('data-chart-labels') || '[]');
			const chartValues = JSON.parse(container.getAttribute('data-chart-values') || '[]');
			const chartData = JSON.parse(container.getAttribute('data-chart-data') || '[]');
			
			if (chartType && (chartLabels.length > 0 || chartData.length > 0)) {
				this.renderChart(container, chartType, chartLabels, chartValues, chartData);
			}
		});
	}

	renderChart(container, type, labels, values, data) {
		// Create canvas element
		const canvas = document.createElement('canvas');
		canvas.width = container.offsetWidth || 600;
		canvas.height = 300;
		container.appendChild(canvas);

		const ctx = canvas.getContext('2d');

		switch(type) {
			case 'line':
			case 'area':
				this.renderLineChart(ctx, canvas, labels, values, data, type === 'area');
				break;
			case 'bar':
				this.renderBarChart(ctx, canvas, labels, values);
				break;
			case 'pie':
				this.renderPieChart(ctx, canvas, labels, values);
				break;
			case 'gauge':
				this.renderGaugeChart(ctx, canvas, values);
				break;
		}
	}

	renderLineChart(ctx, canvas, labels, values, data, filled = false) {
		const width = canvas.width;
		const height = canvas.height;
		const padding = 40;
		const chartWidth = width - 2 * padding;
		const chartHeight = height - 2 * padding;

		// Use data if available, otherwise use labels/values
		const chartData = data.length > 0 ? data : values;
		
		if (chartData.length === 0) return;

		// Find min and max values
		let maxValue = Math.max(...chartData.map(d => typeof d === 'object' ? d.value : d));
		let minValue = Math.min(...chartData.map(d => typeof d === 'object' ? d.value : d));
		const valueRange = maxValue - minValue || 1;

		// Draw axes
		ctx.strokeStyle = '#333';
		ctx.lineWidth = 1;
		ctx.beginPath();
		ctx.moveTo(padding, padding);
		ctx.lineTo(padding, height - padding);
		ctx.lineTo(width - padding, height - padding);
		ctx.stroke();

		// Draw grid lines
		ctx.strokeStyle = '#e0e0e0';
		ctx.lineWidth = 0.5;
		for (let i = 0; i <= 5; i++) {
			const y = padding + (chartHeight / 5) * i;
			ctx.beginPath();
			ctx.moveTo(padding, y);
			ctx.lineTo(width - padding, y);
			ctx.stroke();

			// Y-axis labels
			const value = maxValue - (valueRange / 5) * i;
			ctx.fillStyle = '#666';
			ctx.font = '10px Arial';
			ctx.textAlign = 'right';
			ctx.fillText(value.toFixed(2), padding - 5, y + 3);
		}

		// Draw line
		ctx.strokeStyle = '#0066cc';
		ctx.lineWidth = 2;
		ctx.beginPath();

		const points = [];
		chartData.forEach((item, index) => {
			const value = typeof item === 'object' ? item.value : item;
			const x = padding + (chartWidth / (chartData.length - 1)) * index;
			const y = height - padding - ((value - minValue) / valueRange) * chartHeight;
			
			points.push({x, y});
			
			if (index === 0) {
				ctx.moveTo(x, y);
			} else {
				ctx.lineTo(x, y);
			}
		});
		ctx.stroke();

		// Fill area if needed
		if (filled) {
			ctx.lineTo(points[points.length - 1].x, height - padding);
			ctx.lineTo(padding, height - padding);
			ctx.closePath();
			ctx.fillStyle = 'rgba(0, 102, 204, 0.2)';
			ctx.fill();
		}

		// Draw points
		points.forEach(point => {
			ctx.fillStyle = '#0066cc';
			ctx.beginPath();
			ctx.arc(point.x, point.y, 3, 0, 2 * Math.PI);
			ctx.fill();
		});

		// X-axis labels
		if (labels.length > 0) {
			ctx.fillStyle = '#666';
			ctx.font = '10px Arial';
			ctx.textAlign = 'center';
			labels.forEach((label, index) => {
				if (index % Math.ceil(labels.length / 10) === 0) {
					const x = padding + (chartWidth / (labels.length - 1)) * index;
					ctx.fillText(label.substring(0, 15), x, height - padding + 15);
				}
			});
		}
	}

	renderBarChart(ctx, canvas, labels, values) {
		const width = canvas.width;
		const height = canvas.height;
		const padding = 40;
		const chartWidth = width - 2 * padding;
		const chartHeight = height - 2 * padding;

		if (values.length === 0) return;

		const maxValue = Math.max(...values);
		const barWidth = chartWidth / values.length * 0.8;
		const barSpacing = chartWidth / values.length * 0.2;

		// Draw axes
		ctx.strokeStyle = '#333';
		ctx.lineWidth = 1;
		ctx.beginPath();
		ctx.moveTo(padding, padding);
		ctx.lineTo(padding, height - padding);
		ctx.lineTo(width - padding, height - padding);
		ctx.stroke();

		// Draw bars
		values.forEach((value, index) => {
			const barHeight = (value / maxValue) * chartHeight;
			const x = padding + index * (barWidth + barSpacing) + barSpacing / 2;
			const y = height - padding - barHeight;

			// Gradient fill
			const gradient = ctx.createLinearGradient(0, y, 0, height - padding);
			gradient.addColorStop(0, '#0066cc');
			gradient.addColorStop(1, '#0099ff');
			
			ctx.fillStyle = gradient;
			ctx.fillRect(x, y, barWidth, barHeight);

			// Value label
			ctx.fillStyle = '#333';
			ctx.font = '10px Arial';
			ctx.textAlign = 'center';
			ctx.fillText(value.toFixed(1), x + barWidth / 2, y - 5);

			// X-axis label
			if (labels[index]) {
				ctx.save();
				ctx.translate(x + barWidth / 2, height - padding + 10);
				ctx.rotate(-Math.PI / 4);
				ctx.textAlign = 'right';
				ctx.fillText(labels[index].substring(0, 20), 0, 0);
				ctx.restore();
			}
		});
	}

	renderPieChart(ctx, canvas, labels, values) {
		const width = canvas.width;
		const height = canvas.height;
		const centerX = width / 2;
		const centerY = height / 2;
		const radius = Math.min(width, height) / 2 - 40;

		if (values.length === 0) return;

		const total = values.reduce((sum, val) => sum + val, 0);
		const colors = [
			'#0066cc', '#00cc66', '#cc6600', '#cc0066',
			'#6600cc', '#cccc00', '#00cccc', '#cc00cc'
		];

		let currentAngle = -Math.PI / 2;

		values.forEach((value, index) => {
			const sliceAngle = (value / total) * 2 * Math.PI;
			
			// Draw slice
			ctx.fillStyle = colors[index % colors.length];
			ctx.beginPath();
			ctx.moveTo(centerX, centerY);
			ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
			ctx.closePath();
			ctx.fill();

			// Draw border
			ctx.strokeStyle = '#fff';
			ctx.lineWidth = 2;
			ctx.stroke();

			// Draw label
			const labelAngle = currentAngle + sliceAngle / 2;
			const labelX = centerX + Math.cos(labelAngle) * (radius * 0.7);
			const labelY = centerY + Math.sin(labelAngle) * (radius * 0.7);
			
			ctx.fillStyle = '#fff';
			ctx.font = 'bold 12px Arial';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			const percentage = ((value / total) * 100).toFixed(1);
			ctx.fillText(percentage + '%', labelX, labelY);

			currentAngle += sliceAngle;
		});

		// Draw legend
		const legendX = width - 150;
		let legendY = 20;
		
		labels.forEach((label, index) => {
			ctx.fillStyle = colors[index % colors.length];
			ctx.fillRect(legendX, legendY, 15, 15);
			
			ctx.fillStyle = '#333';
			ctx.font = '11px Arial';
			ctx.textAlign = 'left';
			ctx.fillText(label.substring(0, 20), legendX + 20, legendY + 11);
			
			legendY += 20;
		});
	}

	renderGaugeChart(ctx, canvas, values) {
		const width = canvas.width;
		const height = canvas.height;
		const centerX = width / 2;
		const centerY = height - 40;
		const radius = Math.min(width, height) / 2 - 40;

		const value = values[0] || 0;
		const maxValue = 100;

		// Draw gauge background
		ctx.strokeStyle = '#e0e0e0';
		ctx.lineWidth = 20;
		ctx.beginPath();
		ctx.arc(centerX, centerY, radius, Math.PI, 2 * Math.PI);
		ctx.stroke();

		// Draw gauge value
		const angle = Math.PI + (value / maxValue) * Math.PI;
		const gradient = ctx.createLinearGradient(0, 0, width, 0);
		
		if (value < 70) {
			gradient.addColorStop(0, '#cc0000');
			gradient.addColorStop(1, '#ff6600');
		} else if (value < 90) {
			gradient.addColorStop(0, '#ff6600');
			gradient.addColorStop(1, '#ffcc00');
		} else {
			gradient.addColorStop(0, '#00cc00');
			gradient.addColorStop(1, '#00ff00');
		}

		ctx.strokeStyle = gradient;
		ctx.lineWidth = 20;
		ctx.beginPath();
		ctx.arc(centerX, centerY, radius, Math.PI, angle);
		ctx.stroke();

		// Draw value text
		ctx.fillStyle = '#333';
		ctx.font = 'bold 36px Arial';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.fillText(value.toFixed(1) + '%', centerX, centerY - 20);

		// Draw labels
		ctx.font = '12px Arial';
		ctx.fillText('0', centerX - radius - 20, centerY);
		ctx.fillText('100', centerX + radius + 20, centerY);
	}

	attachEventHandlers() {
		const exportBtn = this._content_body.querySelector('.export-pdf-btn');
		if (exportBtn) {
			exportBtn.addEventListener('click', () => this.exportToPDF());
		}
	}

	exportToPDF() {
		const widgetId = this._content_body.querySelector('.export-pdf-btn')
			?.getAttribute('data-widget-id');
		
		if (!widgetId) {
			console.error('Widget ID not found');
			return;
		}

		// Show loading indicator
		const btn = this._content_body.querySelector('.export-pdf-btn');
		const originalText = btn.textContent;
		btn.textContent = t('Generating PDF...');
		btn.disabled = true;

		// Make AJAX request to generate PDF
		fetch('zabbix.php?action=reports.export.pdf', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				widgetid: widgetId,
				fields: this._fields
			})
		})
		.then(response => response.blob())
		.then(blob => {
			// Create download link
			const url = window.URL.createObjectURL(blob);
			const a = document.createElement('a');
			a.href = url;
			a.download = 'report_' + new Date().getTime() + '.pdf';
			document.body.appendChild(a);
			a.click();
			window.URL.revokeObjectURL(url);
			document.body.removeChild(a);

			btn.textContent = originalText;
			btn.disabled = false;
		})
		.catch(error => {
			console.error('Error generating PDF:', error);
			btn.textContent = originalText;
			btn.disabled = false;
			alert(t('Error generating PDF. Please try again.'));
		});
	}

	destroyCharts() {
		const chartContainers = this._content_body.querySelectorAll('.chart-container');
		chartContainers.forEach(container => {
			container.innerHTML = '';
		});
	}

	getUpdateRequestData() {
		return {
			...super.getUpdateRequestData(),
			has_custom_time_period: 1
		};
	}

	setEditMode() {
		super.setEditMode();
	}

	hasPadding() {
		return true;
	}
}