# Host Counter Widget

## Descrição
Widget personalizado para Zabbix que conta hosts com filtros avançados e suporte a ícone personalizado.

## Funcionalidades

### Contadores Disponíveis
- **Total de Hosts**: Conta todos os hosts dos grupos selecionados
- **Hosts Ativos**: Hosts com status "Monitorado"
- **Hosts Desativados**: Hosts com status "Não monitorado" (opcional)
- **Hosts em Manutenção**: Hosts atualmente em manutenção (opcional)
- **Problemas**: Total de problemas dos hosts (opcional)
- **Itens**: Total de itens dos hosts (opcional)
- **Triggers**: Total de triggers dos hosts (opcional)

### Filtros Configuráveis
- **Grupos de Hosts**: Selecione quais grupos incluir na contagem
- **Contar Problemas**: Incluir/excluir contagem de problemas
- **Contar Itens**: Incluir/excluir contagem de itens
- **Contar Triggers**: Incluir/excluir contagem de triggers
- **Contar Hosts Desativados**: Incluir/excluir hosts desativados
- **Contar Hosts em Manutenção**: Incluir/excluir hosts em manutenção
- **Mostrar Problemas Suprimidos**: Incluir problemas suprimidos na contagem

### Ícone Personalizado
- Upload de ícone personalizado em PNG, JPG, GIF ou SVG
- Tamanho máximo: 2MB
- Ícone exibido no canto superior direito do widget
- Redimensionamento automático (máx. 48x48px)

## Instalação

1. Copie a pasta `hostcounter` para o diretório `modules` do Zabbix
2. Acesse Administration → General → Modules
3. Clique em "Scan directory"
4. Encontre "Host Counter" e clique em "Enable"
5. O widget estará disponível no dashboard

## Configuração

1. No dashboard, adicione um novo widget
2. Selecione "Host Counter" na lista
3. Configure os filtros desejados:
   - Selecione os grupos de hosts
   - Marque/desmarque os contadores desejados
   - Faça upload de um ícone personalizado (opcional)
4. Salve as configurações

## Estrutura de Arquivos

```
hostcounter/
├── manifest.json          # Manifesto do módulo
├── Widget.php            # Lógica principal do widget
├── upload_icon.php       # Script para upload de ícones
├── assets/               # Diretório para ícones uploadados
├── views/
│   ├── widget.edit.php   # Formulário de configuração
│   └── widget.view.php   # Visualização do widget
└── README.md            # Este arquivo
```

## Recursos Técnicos

- **Auto-refresh**: Widget atualiza automaticamente a cada 30 segundos
- **Responsivo**: Layout adapta-se ao tamanho do widget
- **Hover Effects**: Efeitos visuais ao passar o mouse
- **Cores Temáticas**: Cada contador tem sua cor identificadora
- **Gerenciamento de Ícones**: Limpeza automática de ícones antigos (mantém últimos 10)

## Compatibilidade

- Zabbix 6.0+
- PHP 7.4+
- Navegadores modernos com suporte a JavaScript ES6+

## Personalização

O widget pode ser facilmente personalizado editando:
- **Cores**: Modifique as classes CSS em `widget.view.php`
- **Layout**: Ajuste a estrutura HTML no mesmo arquivo
- **Contadores**: Adicione novos tipos de contagem em `Widget.php`
- **Filtros**: Inclua novos filtros no formulário de configuração

## Suporte

Para problemas ou sugestões, verifique:
1. Logs do Zabbix em `/var/log/zabbix/`
2. Console do navegador para erros JavaScript
3. Permissões do diretório `assets/` (deve ser gravável pelo servidor web)
