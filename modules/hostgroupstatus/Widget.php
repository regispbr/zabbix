<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
** ... (Licença) ...
**/

namespace Modules\HostGroupStatus;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	// --- MUDANÇA AQUI: Adicionado getAssets() ---
	public function getAssets(): array {
		return [
			// Arquivos para o Dashboard
			'js' => ['class.widget.js'],
			
			// --- ESTA É A CORREÇÃO ---
			// A chave 'css' DEVE existir, mas pode ser vazia
			'css' => [],
			// --- FIM DA CORREÇÃO ---
			
			// Arquivo para a tela de Edição (ESSA LINHA CORRIGE O BUG DE SALVAR)
			'edit_js' => ['widget.edit.js']
		];
	}
	// --- FIM DA MUDANÇA ---

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'Host Group Status' => _('Host Group Status'),
				'No host group selected' => _('No host group selected'),
				'hosts' => _('hosts'),
				'host' => _('host'),
				'Show group name' => _('Show group name'),
				'Custom group name' => _('Custom group name'),
				'Enable URL redirect' => _('Enable URL redirect'),
				'Redirect URL' => _('Redirect URL'),
				'Open in new tab' => _('Open in new tab'),
				'Font Size (px)' => _('Font Size (px)'),
				'Font Family' => _('Font Family'),
				'Show Border' => _('Show Border'),
				'Border Width (px)' => _('Border Width (px)'),
				'Padding (px)' => _('Padding (px)'),
				'Widget Color' => _('Widget Color'),
				'Count Mode' => _('Count Mode'),
				'Hosts with alarms' => _('Hosts with alarms'),
				'Hosts without alarms' => _('Hosts without alarms'),
				'All hosts' => _('All hosts'),
				'total' => _('total')
			]
		];
	}
}

