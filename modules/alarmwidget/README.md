# Alarm Widget para Zabbix 7.0.13

Widget customizado para exibição de alarmes do Zabbix com funcionalidades avançadas de filtragem e visualização.

## Funcionalidades

### Exibição de Dados
- **Colunas configuráveis**: Host, Severity, Status, Problem, Operational data, Ack, Age, Time
- **Ordenação**: Clique em qualquer cabeçalho de coluna para ordenar (crescente/decrescente)
- **Cores de severidade**: 
  - Disaster (vermelho escuro)
  - High (vermelho/laranja)
  - Average (laranja)
  - Warning (amarelo)
  - Information (azul)
  - Not classified (cinza)

### Filtros Disponíveis
1. **Grupos de Hosts**: Filtre por um ou mais grupos de hosts
2. **Hosts**: Filtre por hosts específicos
3. **Severidade**: Selecione quais níveis de severidade exibir
4. **Status do Problema**: Todos, Problema ou Resolvido
5. **Reconhecimento**: Todos, Não reconhecidos ou Reconhecidos
6. **Manutenção**: Opção para excluir hosts em manutenção

### Configurações
- **Colunas visíveis**: Escolha quais colunas exibir
- **Intervalo de atualização**: Configure o tempo de refresh automático (mínimo 10 segundos)
- **Número de linhas**: Defina quantas linhas exibir (máximo 100)

## Instalação

1. Copie a pasta `alarmwidget` para o diretório de módulos do Zabbix:
   ```bash
   cp -r alarmwidget /usr/share/zabbix/modules/
   ```

2. Ajuste as permissões:
   ```bash
   chown -R www-data:www-data /usr/share/zabbix/modules/alarmwidget
   chmod -R 755 /usr/share/zabbix/modules/alarmwidget
   ```

3. No Zabbix frontend, vá para:
   - Administration → General → Modules
   - Clique em "Scan directory"
   - Encontre "Alarm Widget" e clique em "Enable"

4. Adicione o widget ao seu dashboard:
   - Vá para Monitoring → Dashboard
   - Clique em "Edit dashboard"
   - Clique em "Add widget"
   - Selecione "Alarm Widget"
   - Configure os filtros e opções desejadas
   - Clique em "Add"

## Uso

### Configuração Básica
1. Selecione os grupos de hosts ou hosts específicos que deseja monitorar
2. Escolha as severidades que deseja exibir
3. Configure as colunas visíveis
4. Defina o intervalo de atualização

### Ordenação
- Clique em qualquer cabeçalho de coluna para ordenar
- Clique novamente para inverter a ordem (crescente/decrescente)
- Um indicador de seta aparecerá mostrando a direção da ordenação

### Interação
- Passe o mouse sobre as linhas para destacá-las
- Clique em uma linha para selecioná-la
- O widget atualiza automaticamente baseado no intervalo configurado

## Compatibilidade

- Zabbix 7.0.13
- PHP 7.4 ou superior
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

## Estrutura de Arquivos

```
alarmwidget/
├── manifest.json              # Configuração do módulo
├── Widget.php                 # Classe principal do widget
├── README.md                  # Esta documentação
├── actions/
│   └── WidgetView.php        # Controlador de visualização
├── includes/
│   └── WidgetForm.php        # Formulário de configuração
├── views/
│   ├── widget.view.php       # Template de visualização
│   └── widget.edit.php       # Template de edição
└── assets/
    ├── css/
    │   └── style.css         # Estilos do widget
    └── js/
        ├── class.widget.js   # Lógica JavaScript do widget
        └── widget.edit.js    # Lógica do formulário de edição
```

## Classes Zabbix Utilizadas

O widget utiliza as seguintes classes da API do Zabbix 7.0:

- `CWidget`: Classe base para widgets
- `CWidgetForm`: Formulário de configuração
- `CWidgetField`: Campos do formulário
- `CWidgetFieldMultiSelectGroup`: Seleção de grupos
- `CWidgetFieldMultiSelectHost`: Seleção de hosts
- `CWidgetFieldSeverities`: Seleção de severidades
- `CWidgetFieldCheckBox`: Campos checkbox
- `CWidgetFieldCheckBoxList`: Lista de checkboxes
- `CWidgetFieldRadioButtonList`: Lista de radio buttons
- `CWidgetFieldIntegerBox`: Campos numéricos
- `API::Problem()`: API de problemas
- `CTableInfo`: Tabela de dados

## Personalização

### Cores de Severidade
Edite o arquivo `assets/css/style.css` para personalizar as cores:

```css
.alarm-severity-high {
    background-color: #e97659 !important;
    color: #ffffff !important;
}
```

### Intervalo de Atualização Padrão
Edite o arquivo `includes/WidgetForm.php`:

```php
(new CWidgetFieldIntegerBox('refresh_interval', _('Refresh interval (seconds)')))
    ->setDefault(60)  // Altere este valor
```

## Troubleshooting

### Widget não aparece na lista
- Verifique se o módulo está habilitado em Administration → General → Modules
- Verifique as permissões dos arquivos
- Verifique os logs do Zabbix em `/var/log/zabbix/`

### Dados não são exibidos
- Verifique se os filtros estão configurados corretamente
- Verifique se o usuário tem permissão para visualizar os hosts/grupos
- Verifique se existem problemas ativos que atendam aos filtros

### Erros de JavaScript
- Limpe o cache do navegador
- Verifique o console do navegador (F12) para erros
- Verifique se todos os arquivos JavaScript foram carregados corretamente

## Suporte

Para problemas ou sugestões, consulte a documentação oficial do Zabbix:
- https://www.zabbix.com/documentation/7.0/

## Licença

Este widget segue a mesma licença do Zabbix (GPL v2).