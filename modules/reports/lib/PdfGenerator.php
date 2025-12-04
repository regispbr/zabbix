<?php declare(strict_types = 0);

namespace Modules\Reports\Lib;

/**
 * PDF Generator for Reports Widget
 * 
 * This class handles PDF generation for reports using TCPDF library.
 * It supports custom JRXML templates and various report formats.
 */
class PdfGenerator {

	private $pdf;
	private $report_data;
	private $jrxml_template;

	public function __construct(array $report_data, string $jrxml_template = '') {
		$this->report_data = $report_data;
		$this->jrxml_template = $jrxml_template;
	}

	/**
	 * Generate PDF report
	 * 
	 * @return string PDF content as string
	 */
	public function generate(): string {
		// Check if TCPDF is available
		if (!class_exists('TCPDF')) {
			throw new \Exception('TCPDF library is not installed. Please install it via composer: composer require tecnickcom/tcpdf');
		}

		// Initialize TCPDF
		$this->pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// Set document information
		$this->pdf->SetCreator('Zabbix Reports Module');
		$this->pdf->SetAuthor('Infrastructure Team');
		$this->pdf->SetTitle($this->report_data['report_title'] ?? 'Infrastructure Report');
		$this->pdf->SetSubject('Infrastructure Monitoring Report');

		// Set margins
		$this->pdf->SetMargins(15, 15, 15);
		$this->pdf->SetHeaderMargin(10);
		$this->pdf->SetFooterMargin(10);

		// Set auto page breaks
		$this->pdf->SetAutoPageBreak(true, 15);

		// Set font
		$this->pdf->SetFont('helvetica', '', 10);

		// Check if JRXML template is provided
		if ($this->jrxml_template && file_exists($this->jrxml_template)) {
			$this->generateFromJRXML();
		} else {
			$this->generateStandardReport();
		}

		// Return PDF as string
		return $this->pdf->Output('report.pdf', 'S');
	}

	/**
	 * Generate report from JRXML template
	 */
	private function generateFromJRXML(): void {
		// Parse JRXML template
		$jrxml_content = file_get_contents($this->jrxml_template);
		$xml = simplexml_load_string($jrxml_content);

		if ($xml === false) {
			throw new \Exception('Invalid JRXML template file');
		}

		// Add page
		$this->pdf->AddPage();

		// Process JRXML elements
		// Note: This is a simplified implementation
		// Full JRXML support would require JasperReports Java library
		
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell(0, 10, 'Report Generated from JRXML Template', 0, 1, 'C');
		$this->pdf->Ln(5);

		// Add report data
		$this->addReportContent();
	}

	/**
	 * Generate standard report without template
	 */
	private function generateStandardReport(): void {
		// Add first page
		$this->pdf->AddPage();

		// Add header
		if ($this->report_data['show_header'] ?? true) {
			$this->addHeader();
		}

		// Add report content
		$this->addReportContent();

		// Add footer with page numbers
		if ($this->report_data['show_page_numbers'] ?? true) {
			$this->addPageNumbers();
		}
	}

	/**
	 * Add report header
	 */
	private function addHeader(): void {
		$this->pdf->SetFont('helvetica', 'B', 20);
		$this->pdf->SetTextColor(0, 102, 204);
		$this->pdf->Cell(0, 15, $this->report_data['report_title'] ?? 'Infrastructure Report', 0, 1, 'C');

		$this->pdf->SetFont('helvetica', '', 10);
		$this->pdf->SetTextColor(102, 102, 102);
		$period_text = sprintf('Period: %s to %s', 
			date('Y-m-d H:i', strtotime($this->report_data['date_from'])),
			date('Y-m-d H:i', strtotime($this->report_data['date_to']))
		);
		$this->pdf->Cell(0, 8, $period_text, 0, 1, 'C');
		
		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->Ln(5);

		// Add line separator
		$this->pdf->SetLineStyle(['width' => 0.5, 'color' => [0, 102, 204]]);
		$this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
		$this->pdf->Ln(5);
	}

	/**
	 * Add main report content
	 */
	private function addReportContent(): void {
		$display_format = $this->report_data['display_format'] ?? 0;

		// Availability Report
		if (!empty($this->report_data['availability_data'])) {
			$this->addAvailabilitySection($display_format);
		}

		// Performance Report
		if (!empty($this->report_data['performance_data'])) {
			$this->addPerformanceSection($display_format);
		}

		// Alerts Report
		if (!empty($this->report_data['alerts_data'])) {
			$this->addAlertsSection($display_format);
		}
	}

	/**
	 * Add availability section
	 */
	private function addAvailabilitySection(int $format): void {
		$this->pdf->SetFont('helvetica', 'B', 14);
		$this->pdf->SetTextColor(0, 102, 204);
		$this->pdf->Cell(0, 10, 'Availability Report', 0, 1, 'L');
		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->Ln(3);

		if ($format == 0) { // Operational
			$this->addAvailabilityTableOperational();
		} else { // Executive
			$this->addAvailabilityExecutive();
		}

		$this->pdf->Ln(5);
	}

	/**
	 * Add operational availability table
	 */
	private function addAvailabilityTableOperational(): void {
		$this->pdf->SetFont('helvetica', 'B', 9);
		$this->pdf->SetFillColor(0, 102, 204);
		$this->pdf->SetTextColor(255, 255, 255);

		// Table header
		$this->pdf->Cell(60, 8, 'Host', 1, 0, 'L', true);
		$this->pdf->Cell(30, 8, 'Status', 1, 0, 'C', true);
		$this->pdf->Cell(40, 8, 'Availability %', 1, 0, 'C', true);
		$this->pdf->Cell(50, 8, 'Current State', 1, 1, 'C', true);

		$this->pdf->SetFont('helvetica', '', 8);
		$this->pdf->SetTextColor(0, 0, 0);

		// Table rows
		foreach ($this->report_data['availability_data'] as $host) {
			$availability = $host['availability'];
			
			// Set color based on availability
			if ($availability >= 99) {
				$this->pdf->SetTextColor(0, 204, 0);
			} elseif ($availability >= 95) {
				$this->pdf->SetTextColor(255, 153, 0);
			} else {
				$this->pdf->SetTextColor(204, 0, 0);
			}

			$this->pdf->Cell(60, 7, $host['hostname'], 1, 0, 'L');
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->Cell(30, 7, $host['status'] == 0 ? 'Enabled' : 'Disabled', 1, 0, 'C');
			
			if ($availability >= 99) {
				$this->pdf->SetTextColor(0, 204, 0);
			} elseif ($availability >= 95) {
				$this->pdf->SetTextColor(255, 153, 0);
			} else {
				$this->pdf->SetTextColor(204, 0, 0);
			}
			$this->pdf->Cell(40, 7, number_format($availability, 2) . '%', 1, 0, 'C');
			
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->Cell(50, 7, $host['available'] == 1 ? 'Available' : 'Unavailable', 1, 1, 'C');
		}
	}

	/**
	 * Add executive availability summary
	 */
	private function addAvailabilityExecutive(): void {
		$total_hosts = count($this->report_data['availability_data']);
		$avg_availability = array_sum(array_column($this->report_data['availability_data'], 'availability')) / $total_hosts;
		$available_hosts = count(array_filter($this->report_data['availability_data'], function($h) { 
			return $h['available'] == 1; 
		}));

		$this->pdf->SetFont('helvetica', '', 10);
		
		// Summary boxes
		$box_width = 55;
		$box_height = 25;
		
		$this->pdf->SetFillColor(0, 102, 204);
		$this->pdf->SetTextColor(255, 255, 255);
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $box_width, $box_height, 'F');
		$this->pdf->Cell($box_width, 8, 'Total Hosts', 0, 0, 'C');
		$this->pdf->Ln(8);
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell($box_width, 12, (string)$total_hosts, 0, 0, 'C');
		
		$this->pdf->SetXY($this->pdf->GetX() + 10, $this->pdf->GetY() - 20);
		$this->pdf->SetFont('helvetica', '', 10);
		$this->pdf->SetFillColor(0, 102, 204);
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $box_width, $box_height, 'F');
		$this->pdf->Cell($box_width, 8, 'Avg Availability', 0, 0, 'C');
		$this->pdf->Ln(8);
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell($box_width, 12, number_format($avg_availability, 2) . '%', 0, 0, 'C');
		
		$this->pdf->SetXY($this->pdf->GetX() + 10, $this->pdf->GetY() - 20);
		$this->pdf->SetFont('helvetica', '', 10);
		$this->pdf->SetFillColor(0, 102, 204);
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $box_width, $box_height, 'F');
		$this->pdf->Cell($box_width, 8, 'Currently Available', 0, 0, 'C');
		$this->pdf->Ln(8);
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell($box_width, 12, $available_hosts . ' / ' . $total_hosts, 0, 1, 'C');
		
		$this->pdf->SetTextColor(0, 0, 0);
	}

	/**
	 * Add performance section
	 */
	private function addPerformanceSection(int $format): void {
		$this->pdf->Ln(5);
		$this->pdf->SetFont('helvetica', 'B', 14);
		$this->pdf->SetTextColor(0, 102, 204);
		$this->pdf->Cell(0, 10, 'Performance Report', 0, 1, 'L');
		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->Ln(3);

		if ($format == 0) { // Operational
			$this->addPerformanceTableOperational();
		} else { // Executive
			$this->addPerformanceExecutive();
		}

		$this->pdf->Ln(5);
	}

	/**
	 * Add operational performance table
	 */
	private function addPerformanceTableOperational(): void {
		$this->pdf->SetFont('helvetica', 'B', 8);
		$this->pdf->SetFillColor(0, 102, 204);
		$this->pdf->SetTextColor(255, 255, 255);

		// Table header
		$this->pdf->Cell(45, 8, 'Host', 1, 0, 'L', true);
		$this->pdf->Cell(50, 8, 'Item', 1, 0, 'L', true);
		$this->pdf->Cell(25, 8, 'Last Value', 1, 0, 'C', true);
		$this->pdf->Cell(20, 8, 'Min', 1, 0, 'C', true);
		$this->pdf->Cell(20, 8, 'Max', 1, 0, 'C', true);
		$this->pdf->Cell(20, 8, 'Avg', 1, 1, 'C', true);

		$this->pdf->SetFont('helvetica', '', 7);
		$this->pdf->SetTextColor(0, 0, 0);

		// Table rows
		foreach ($this->report_data['performance_data'] as $item) {
			$stats = $item['statistics'];
			
			$this->pdf->Cell(45, 6, substr($item['hostname'], 0, 25), 1, 0, 'L');
			$this->pdf->Cell(50, 6, substr($item['item_name'], 0, 30), 1, 0, 'L');
			$this->pdf->Cell(25, 6, $item['last_value'] . ' ' . $item['units'], 1, 0, 'C');
			$this->pdf->Cell(20, 6, number_format($stats['min'], 2), 1, 0, 'C');
			$this->pdf->Cell(20, 6, number_format($stats['max'], 2), 1, 0, 'C');
			$this->pdf->Cell(20, 6, number_format($stats['avg'], 2), 1, 1, 'C');
		}
	}

	/**
	 * Add executive performance summary
	 */
	private function addPerformanceExecutive(): void {
		$this->pdf->SetFont('helvetica', '', 9);
		
		// Show top 6 metrics
		$metrics = array_slice($this->report_data['performance_data'], 0, 6);
		$col_width = 60;
		$row_height = 20;
		$col = 0;
		
		foreach ($metrics as $item) {
			if ($col == 3) {
				$col = 0;
				$this->pdf->Ln($row_height);
			}
			
			$x = 15 + ($col * ($col_width + 5));
			$y = $this->pdf->GetY();
			
			$this->pdf->SetXY($x, $y);
			$this->pdf->SetFillColor(249, 249, 249);
			$this->pdf->Rect($x, $y, $col_width, $row_height, 'F');
			$this->pdf->SetDrawColor(0, 102, 204);
			$this->pdf->Line($x, $y, $x, $y + $row_height);
			
			$this->pdf->SetXY($x + 2, $y + 2);
			$this->pdf->SetFont('helvetica', '', 7);
			$this->pdf->SetTextColor(102, 102, 102);
			$this->pdf->Cell($col_width - 4, 5, substr($item['hostname'], 0, 25), 0, 1, 'L');
			
			$this->pdf->SetX($x + 2);
			$this->pdf->SetFont('helvetica', '', 8);
			$this->pdf->SetTextColor(51, 51, 51);
			$this->pdf->Cell($col_width - 4, 5, substr($item['item_name'], 0, 25), 0, 1, 'L');
			
			$this->pdf->SetX($x + 2);
			$this->pdf->SetFont('helvetica', 'B', 12);
			$this->pdf->SetTextColor(0, 102, 204);
			$this->pdf->Cell($col_width - 4, 6, $item['last_value'] . ' ' . $item['units'], 0, 0, 'L');
			
			$col++;
		}
		
		$this->pdf->SetTextColor(0, 0, 0);
	}

	/**
	 * Add alerts section
	 */
	private function addAlertsSection(int $format): void {
		$this->pdf->Ln(10);
		$this->pdf->SetFont('helvetica', 'B', 14);
		$this->pdf->SetTextColor(0, 102, 204);
		$this->pdf->Cell(0, 10, 'Alerts Report', 0, 1, 'L');
		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->Ln(3);

		if ($format == 0) { // Operational
			$this->addAlertsTableOperational();
		} else { // Executive
			$this->addAlertsExecutive();
		}
	}

	/**
	 * Add operational alerts table
	 */
	private function addAlertsTableOperational(): void {
		$this->pdf->SetFont('helvetica', 'B', 8);
		$this->pdf->SetFillColor(0, 102, 204);
		$this->pdf->SetTextColor(255, 255, 255);

		// Table header
		$this->pdf->Cell(35, 8, 'Host', 1, 0, 'L', true);
		$this->pdf->Cell(50, 8, 'Problem', 1, 0, 'L', true);
		$this->pdf->Cell(25, 8, 'Severity', 1, 0, 'C', true);
		$this->pdf->Cell(35, 8, 'Start Time', 1, 0, 'C', true);
		$this->pdf->Cell(20, 8, 'Duration', 1, 0, 'C', true);
		$this->pdf->Cell(15, 8, 'Status', 1, 1, 'C', true);

		$this->pdf->SetFont('helvetica', '', 7);
		$this->pdf->SetTextColor(0, 0, 0);

		// Table rows (limit to 20 for PDF)
		$alerts = array_slice($this->report_data['alerts_data'], 0, 20);
		
		foreach ($alerts as $alert) {
			// Set severity color
			$severity_colors = [
				0 => [151, 170, 179],
				1 => [116, 153, 255],
				2 => [255, 200, 89],
				3 => [255, 160, 89],
				4 => [233, 118, 89],
				5 => [228, 89, 89]
			];
			
			$this->pdf->Cell(35, 6, substr($alert['hostname'], 0, 20), 1, 0, 'L');
			$this->pdf->Cell(50, 6, substr($alert['problem_name'], 0, 30), 1, 0, 'L');
			
			$color = $severity_colors[$alert['severity']] ?? [0, 0, 0];
			$this->pdf->SetTextColor($color[0], $color[1], $color[2]);
			$this->pdf->Cell(25, 6, $alert['severity_name'], 1, 0, 'C');
			
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->Cell(35, 6, date('Y-m-d H:i', $alert['start_time']), 1, 0, 'C');
			$this->pdf->Cell(20, 6, $this->formatDuration($alert['duration']), 1, 0, 'C');
			$this->pdf->Cell(15, 6, $alert['end_time'] > 0 ? 'OK' : 'Active', 1, 1, 'C');
		}
	}

	/**
	 * Add executive alerts summary
	 */
	private function addAlertsExecutive(): void {
		$total_alerts = count($this->report_data['alerts_data']);
		$active_alerts = count(array_filter($this->report_data['alerts_data'], function($a) { 
			return $a['end_time'] == 0; 
		}));
		$high_severity = count(array_filter($this->report_data['alerts_data'], function($a) { 
			return in_array($a['severity'], [4, 5]); 
		}));

		$this->pdf->SetFont('helvetica', '', 10);
		
		// Summary boxes
		$box_width = 55;
		$box_height = 25;
		
		$this->pdf->SetFillColor(102, 102, 102);
		$this->pdf->SetTextColor(255, 255, 255);
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $box_width, $box_height, 'F');
		$this->pdf->Cell($box_width, 8, 'Total Alerts', 0, 0, 'C');
		$this->pdf->Ln(8);
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell($box_width, 12, (string)$total_alerts, 0, 0, 'C');
		
		$this->pdf->SetXY($this->pdf->GetX() + 10, $this->pdf->GetY() - 20);
		$this->pdf->SetFont('helvetica', '', 10);
		$this->pdf->SetFillColor(233, 118, 89);
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $box_width, $box_height, 'F');
		$this->pdf->Cell($box_width, 8, 'Active Alerts', 0, 0, 'C');
		$this->pdf->Ln(8);
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell($box_width, 12, (string)$active_alerts, 0, 0, 'C');
		
		$this->pdf->SetXY($this->pdf->GetX() + 10, $this->pdf->GetY() - 20);
		$this->pdf->SetFont('helvetica', '', 10);
		$this->pdf->SetFillColor(228, 89, 89);
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $box_width, $box_height, 'F');
		$this->pdf->Cell($box_width, 8, 'High/Disaster', 0, 0, 'C');
		$this->pdf->Ln(8);
		$this->pdf->SetFont('helvetica', 'B', 16);
		$this->pdf->Cell($box_width, 12, (string)$high_severity, 0, 1, 'C');
		
		$this->pdf->SetTextColor(0, 0, 0);
	}

	/**
	 * Add page numbers to footer
	 */
	private function addPageNumbers(): void {
		$this->pdf->setFooterCallback(function($pdf) {
			$pdf->SetY(-15);
			$pdf->SetFont('helvetica', 'I', 8);
			$pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'C');
		});
	}

	/**
	 * Format duration in human readable format
	 */
	private function formatDuration(int $seconds): string {
		$days = floor($seconds / 86400);
		$hours = floor(($seconds % 86400) / 3600);
		$minutes = floor(($seconds % 3600) / 60);
		
		$parts = [];
		if ($days > 0) $parts[] = $days . 'd';
		if ($hours > 0) $parts[] = $hours . 'h';
		if ($minutes > 0) $parts[] = $minutes . 'm';
		
		return !empty($parts) ? implode(' ', $parts) : '< 1m';
	}
}