# Text Widget para Zabbix 7.0

Este é um widget personalizado para o Zabbix 7.0 que permite adicionar texto formatado aos dashboards.

## Características

- **Texto Personalizável**: Digite qualquer texto que desejar
- **Controle de Fonte**: Ajuste tamanho, cor, família, peso e estilo da fonte
- **Alinhamento**: Escolha entre esquerda, centro, direita ou justificado
- **Cores**: Configure cor da fonte e cor de fundo
- **Espaçamento**: Controle padding e altura da linha
- **Bordas**: Opção de mostrar bordas com cor e largura personalizáveis
- **Preview em Tempo Real**: Visualize as mudanças enquanto edita

## Instalação

1. Copie todos os arquivos para o diretório de módulos do Zabbix:
   ```
   /usr/share/zabbix/modules/textwidget/
   ```

2. Certifique-se de que as permissões estão corretas:
   ```bash
   chown -R zabbix:zabbix /usr/share/zabbix/modules/textwidget/
   chmod -R 755 /usr/share/zabbix/modules/textwidget/
   ```

3. Reinicie o servidor web (Apache/Nginx)

4. No Zabbix, vá para Administration → General → Modules

5. Clique em "Scan directory" para detectar o novo módulo

6. Ative o módulo "Text Widget"

## Uso

1. Vá para um dashboard
2. Clique em "Edit dashboard"
3. Clique em "Add widget"
4. Selecione "Text Widget" da lista
5. Configure o texto e formatação desejados
6. Clique em "Add" para adicionar o widget ao dashboard

## Configurações Disponíveis

### Conteúdo
- **Text Content**: O texto que será exibido no widget

### Formatação de Fonte
- **Font Size**: Tamanho da fonte em pixels (padrão: 14px)
- **Font Color**: Cor da fonte (padrão: preto)
- **Font Family**: Família da fonte (padrão: Arial, sans-serif)
- **Font Weight**: Normal ou Negrito
- **Font Style**: Normal ou Itálico

### Layout
- **Text Alignment**: Alinhamento do texto (esquerda, centro, direita, justificado)
- **Line Height**: Altura da linha em porcentagem (padrão: 120%)
- **Padding**: Espaçamento interno em pixels (padrão: 10px)

### Aparência
- **Background Color**: Cor de fundo (padrão: branco)
- **Show Border**: Mostrar ou ocultar borda
- **Border Color**: Cor da borda (padrão: cinza claro)
- **Border Width**: Largura da borda em pixels (padrão: 1px)

## Estrutura de Arquivos

```
textwidget/
├── manifest.json              # Configuração do módulo
├── Widget.php                 # Classe principal do widget
├── includes/
│   └── WidgetForm.php        # Formulário de configuração
├── actions/
│   └── WidgetView.php        # Controlador de visualização
├── views/
│   ├── widget.view.php       # Template de visualização
│   └── widget.edit.php       # Template de edição
├── assets/
│   ├── js/
│   │   ├── class.widget.js   # JavaScript principal
│   │   └── widget.edit.js    # JavaScript para edição
│   └── css/
│       └── style.css         # Estilos CSS
└── README.md                 # Este arquivo
```

## Compatibilidade

- Zabbix 7.0+
- PHP 7.4+
- Navegadores modernos (Chrome, Firefox, Safari, Edge)

## Licença

Este widget é distribuído sob a mesma licença do Zabbix (GPL v2).
