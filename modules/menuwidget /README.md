# Widget de Menu para Zabbix 7.0.13

Widget personalizável de menu para Zabbix 7.0.13 com suporte completo a orientação horizontal/vertical, URLs dinâmicas e imagens.

## 🎯 Características Principais

### Layout e Posicionamento
- ✅ **Menu Horizontal**: Barra de menu no topo com conteúdo abaixo
- ✅ **Menu Vertical**: Menu lateral (esquerda ou topo) com conteúdo ao lado
- ✅ **Posição Personalizável**: Escolha entre esquerda ou topo para menu vertical

### Personalização Visual
- 🎨 **Fonte Personalizável**: Configure família, tamanho e cor da fonte
- 🎨 **Cores Customizáveis**: Defina cores de fundo, texto e hover
- 🎨 **Suporte a Imagens**: Adicione ícones do Zabbix aos itens do menu

### Funcionalidades
- 📋 **Múltiplos Itens**: Adicione quantos itens de menu precisar
- 🔗 **URLs Dinâmicas**: Cada item carrega uma URL diferente (interna ou externa)
- 📊 **Navegação por Setas**: Para menus com muitos itens
- 🔄 **Menu Retrátil**: Opção de encolher/expandir o menu
- 📏 **Limite de Exibição**: Configure quantos itens são visíveis por vez

## 📦 Instalação

### Método 1: Script Automático (Recomendado)

```bash
# Navegue até a pasta do módulo
cd /caminho/para/menuwidget

# Execute o script de instalação
sudo ./install.sh
```

### Método 2: Instalação Manual

```bash
# Copie a pasta do módulo
sudo cp -r menuwidget /usr/share/zabbix/modules/

# Configure permissões
sudo chown -R www-data:www-data /usr/share/zabbix/modules/menuwidget
sudo chmod -R 755 /usr/share/zabbix/modules/menuwidget
```

### Ativação no Zabbix

1. Acesse a interface web do Zabbix
2. Vá para: **Administração → Geral → Módulos**
3. Clique em **"Scan directory"** (Escanear diretório)
4. Localize **"Menu Widget"** na lista
5. Clique em **"Enable"** (Ativar)
6. Limpe o cache do navegador (Ctrl+F5 ou Cmd+Shift+R)

## 🚀 Guia de Uso

### Adicionar Widget ao Dashboard

1. Vá para **Monitoring → Dashboards**
2. Edite um dashboard existente ou crie um novo
3. Clique em **"Add widget"**
4. Selecione **"Menu Widget"** na lista de widgets
5. Configure as opções conforme necessário

### ⚙️ Opções de Configuração

#### 1. Layout do Menu

**Menu Orientation (Orientação do Menu)**
- `Horizontal`: Menu posicionado horizontalmente no topo, conteúdo exibido abaixo
- `Vertical`: Menu posicionado verticalmente na lateral, conteúdo exibido ao lado

**Menu Position (Posição do Menu)** - Apenas para orientação vertical
- `Left`: Menu posicionado à esquerda
- `Top`: Menu posicionado no topo

#### 2. Aparência

**Font Family (Família da Fonte)**
- Exemplos: `Arial, sans-serif`, `Helvetica, sans-serif`, `Georgia, serif`
- Padrão: `Arial, sans-serif`

**Font Size (Tamanho da Fonte)**
- Valor em pixels
- Padrão: 14px
- Recomendado: 12-18px

**Font Color (Cor da Fonte)**
- Formato: código hexadecimal sem o símbolo #
- Exemplo: `333333` (cinza escuro)
- Padrão: `333333`

**Background Color (Cor de Fundo)**
- Formato: código hexadecimal sem o símbolo #
- Exemplo: `F5F5F5` (cinza claro)
- Padrão: `F5F5F5`

**Hover Color (Cor ao Passar o Mouse)**
- Formato: código hexadecimal sem o símbolo #
- Exemplo: `E0E0E0` (cinza médio)
- Padrão: `E0E0E0`

#### 3. Funcionalidades

**Max Visible Items (Máximo de Itens Visíveis)**
- Número de itens exibidos simultaneamente
- Padrão: 5
- Quando excedido, aparecem setas de navegação

**Collapsible Menu (Menu Retrátil)**
- ☑️ Ativado: Mostra botão para encolher/expandir menu
- ☐ Desativado: Menu sempre expandido

**Collapsed by Default (Encolhido por Padrão)**
- ☑️ Ativado: Menu inicia encolhido
- ☐ Desativado: Menu inicia expandido

#### 4. Itens do Menu

Clique em **"Add menu item"** para adicionar novos itens. Para cada item:

**Label (Rótulo)**
- Texto exibido no menu
- Exemplo: "Dashboard Principal", "Problemas", "Hosts"

**URL**
- Endereço a ser carregado ao clicar no item
- Pode ser:
  - URL externa: `https://exemplo.com`
  - Dashboard do Zabbix: `zabbix.php?action=dashboard.view&dashboardid=1`
  - Página de problemas: `zabbix.php?action=problem.view`
  - Página de hosts: `zabbix.php?action=host.view`
  - Página de mapas: `zabbix.php?action=map.view&sysmapid=1`

**Image (Imagem)**
- Selecione um ícone do Zabbix (opcional)
- Ícones disponíveis:
  - icon_warning.png (⚠️ Aviso)
  - icon_info.png (ℹ️ Informação)
  - icon_error.png (❌ Erro)
  - icon_ok.png (✅ OK)
  - icon_maintenance.png (🔧 Manutenção)

## 💡 Exemplos Práticos

### Exemplo 1: Menu de Navegação de Dashboards

**Configuração:**
- Menu Orientation: Vertical
- Menu Position: Left
- Max Visible Items: 6
- Collapsible: Sim

**Itens:**

| Label | URL | Image |
|-------|-----|-------|
| Dashboard Geral | zabbix.php?action=dashboard.view&dashboardid=1 | icon_ok.png |
| Problemas Ativos | zabbix.php?action=problem.view | icon_warning.png |
| Todos os Hosts | zabbix.php?action=host.view | icon_info.png |
| Mapa de Rede | zabbix.php?action=map.view&sysmapid=1 | - |
| Relatórios | zabbix.php?action=report.view | - |

### Exemplo 2: Menu Horizontal de Links Rápidos

**Configuração:**
- Menu Orientation: Horizontal
- Max Visible Items: 8
- Font Size: 13
- Collapsible: Não

**Itens:**

| Label | URL | Image |
|-------|-----|-------|
| Documentação | https://www.zabbix.com/documentation | icon_info.png |
| Grafana | http://grafana.local:3000 | - |
| Wiki | http://wiki.empresa.com | - |
| Tickets | http://tickets.empresa.com | - |

### Exemplo 3: Menu Compacto de Monitoramento

**Configuração:**
- Menu Orientation: Vertical
- Menu Position: Left
- Max Visible Items: 4
- Collapsible: Sim
- Collapsed by Default: Sim
- Font Size: 12

**Itens:**

| Label | URL | Image |
|-------|-----|-------|
| Críticos | zabbix.php?action=problem.view&severities[]=5 | icon_error.png |
| Avisos | zabbix.php?action=problem.view&severities[]=3 | icon_warning.png |
| Status OK | zabbix.php?action=dashboard.view&dashboardid=2 | icon_ok.png |
| Manutenção | zabbix.php?action=maintenance.list | icon_maintenance.png |

### Exemplo 4: Menu de Ferramentas Externas

**Configuração:**
- Menu Orientation: Vertical
- Menu Position: Top
- Max Visible Items: 5

**Itens:**

| Label | URL |
|-------|-----|
| Grafana - Métricas | http://grafana.local:3000/d/metrics |
| Kibana - Logs | http://kibana.local:5601 |
| Prometheus | http://prometheus.local:9090 |
| Alertmanager | http://alertmanager.local:9093 |
| Netdata | http://netdata.local:19999 |

## 🎨 Personalização Avançada

### Adicionar Suas Próprias Imagens

1. Copie suas imagens para o diretório de assets do Zabbix:
   ```bash
   sudo cp minhas-imagens/*.png /usr/share/zabbix/assets/img/
   ```

2. Edite o arquivo `views/widget.edit.js.php`

3. Localize o array `zabbixImages` e adicione suas imagens:
   ```javascript
   const zabbixImages = [
       'icon_warning.png',
       'icon_info.png',
       'icon_error.png',
       'icon_ok.png',
       'icon_maintenance.png',
       'minha_imagem.png',  // Adicione aqui
       'outro_icone.png'    // E aqui
   ];
   ```

4. Salve o arquivo e limpe o cache do navegador

### Esquemas de Cores Sugeridos

**Tema Escuro**
- Font Color: `FFFFFF`
- Background Color: `2C2C2C`
- Hover Color: `3C3C3C`

**Tema Claro**
- Font Color: `333333`
- Background Color: `F5F5F5`
- Hover Color: `E0E0E0`

**Tema Azul**
- Font Color: `FFFFFF`
- Background Color: `1E3A8A`
- Hover Color: `2563EB`

**Tema Verde**
- Font Color: `FFFFFF`
- Background Color: `065F46`
- Hover Color: `059669`

## 🔧 Solução de Problemas

### Widget não aparece na lista de widgets

**Possíveis causas:**
1. Módulo não instalado corretamente
2. Permissões incorretas
3. Módulo não ativado

**Solução:**
```bash
# Verifique se o módulo está no local correto
ls -la /usr/share/zabbix/modules/menuwidget

# Corrija as permissões
sudo chown -R www-data:www-data /usr/share/zabbix/modules/menuwidget
sudo chmod -R 755 /usr/share/zabbix/modules/menuwidget

# Reinicie o Apache/Nginx
sudo systemctl restart apache2  # ou nginx
```

Então:
1. Vá para Administração → Módulos
2. Clique em "Scan directory"
3. Ative o módulo
4. Limpe o cache (Ctrl+F5)

### Conteúdo não carrega no iframe

**Possíveis causas:**
1. URL incorreta
2. Página bloqueia iframe (X-Frame-Options)
3. Problema de CORS

**Solução:**
- Verifique se a URL está correta
- Para páginas do Zabbix, use URLs relativas (sem http://)
- Para páginas externas, verifique se permitem iframe
- Teste a URL diretamente no navegador

### Menu não exibe corretamente

**Solução:**
1. Limpe o cache do navegador (Ctrl+Shift+Delete)
2. Tente em modo anônimo/privado
3. Verifique o console do navegador (F12) para erros
4. Ajuste o tamanho do widget no dashboard

### Imagens não aparecem

**Solução:**
1. Verifique se as imagens existem em `/usr/share/zabbix/assets/img/`
2. Verifique as permissões das imagens
3. Use o caminho completo: `images/nome_da_imagem.png`

### Setas de navegação não funcionam

**Solução:**
1. Verifique se Max Visible Items é menor que o total de itens
2. Limpe o cache do navegador
3. Verifique o console do navegador para erros JavaScript

## 📁 Estrutura de Arquivos

```
menuwidget/
├── manifest.json                      # Manifesto do módulo
├── Widget.php                         # Classe principal do widget
├── README.md                          # Documentação em inglês
├── LEIAME.md                          # Documentação em português
├── install.sh                         # Script de instalação
├── actions/
│   └── WidgetView.php                # Controller da visualização
├── includes/
│   ├── WidgetForm.php                # Formulário de configuração
│   ├── CWidgetFieldMenuItems.php     # Campo customizado para itens
│   └── CWidgetFieldMenuItemsView.php # View do campo customizado
└── views/
    ├── widget.edit.php               # Interface de edição
    ├── widget.edit.js.php            # JavaScript de edição
    └── widget.view.php               # Interface de visualização
```

## 🔍 Logs e Debug

Para verificar logs de erro:

```bash
# Logs do Apache
sudo tail -f /var/log/apache2/error.log

# Logs do Nginx
sudo tail -f /var/log/nginx/error.log

# Logs do PHP
sudo tail -f /var/log/php7.4-fpm.log  # Ajuste a versão do PHP
```

## ✅ Requisitos do Sistema

- Zabbix 7.0.13
- PHP 7.4 ou superior
- Apache 2.4+ ou Nginx 1.18+
- Navegadores suportados:
  - Google Chrome 90+
  - Mozilla Firefox 88+
  - Microsoft Edge 90+
  - Safari 14+

## 📝 Changelog

### Versão 1.0.0 (2024)
- Lançamento inicial
- Suporte a menu horizontal e vertical
- Navegação por setas
- Menu retrátil
- Suporte a imagens do Zabbix
- Personalização completa de cores e fontes

## 📄 Licença

Este módulo é fornecido "como está", sem garantias de qualquer tipo, expressas ou implícitas.

## 🤝 Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para:
- Reportar bugs
- Sugerir novas funcionalidades
- Enviar pull requests
- Melhorar a documentação

## 📞 Suporte

Para dúvidas ou problemas:
1. Consulte este README
2. Verifique a documentação oficial do Zabbix
3. Consulte os logs de erro
4. Entre em contato com o desenvolvedor

## 🌟 Agradecimentos

Desenvolvido com base na estrutura de widgets do Zabbix 7.0.13.
