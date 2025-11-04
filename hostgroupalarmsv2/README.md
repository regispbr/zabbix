# Host Group Alarms Widget

Um widget personalizado para o Zabbix 7.0 que exibe o status de alarmes por grupo de hosts em formato de cartão.

## Características

- **Formato de cartão**: Design compacto similar a um cartão de visita
- **Monitoramento por grupo**: Selecione grupos de hosts para monitorar
- **Cores por severidade**: Utiliza as cores padrão do Zabbix para cada nível de severidade
- **Filtros de severidade**: Escolha quais severidades considerar
- **Seleção de hosts**: Opção de selecionar hosts específicos dentro dos grupos
- **Nome personalizável**: Exiba o nome do grupo ou um texto personalizado
- **Responsivo**: Adapta-se a diferentes tamanhos de tela
- **Interativo**: Clique para navegar para a página de problemas

## Instalação

1. Copie a pasta `hostgroupalarms` para o diretório de módulos do Zabbix:
   ```
   /usr/share/zabbix/modules/
   ```

2. Configure as permissões adequadas:
   ```bash
   chown -R apache:apache /usr/share/zabbix/modules/hostgroupalarms
   chmod -R 755 /usr/share/zabbix/modules/hostgroupalarms
   ```

3. Reinicie o servidor web (Apache/Nginx)

4. No Zabbix frontend, vá para Administration → General → Modules

5. Clique em "Scan directory" para detectar o novo módulo

6. Ative o módulo "Host Group Alarms"

## Configuração

### Campos principais:
- **Host groups**: Selecione os grupos de hosts para monitorar (obrigatório)
- **Hosts**: Selecione hosts específicos (opcional - se vazio, considera todos os hosts dos grupos)
- **Show group name**: Exibir o nome do grupo no widget
- **Custom group name**: Nome personalizado para exibir no lugar do nome do grupo

### Filtros de severidade:
- **Show Not classified**: Incluir alarmes não classificados
- **Show Information**: Incluir alarmes informativos
- **Show Warning**: Incluir alarmes de aviso
- **Show Average**: Incluir alarmes médios
- **Show High**: Incluir alarmes altos
- **Show Disaster**: Incluir alarmes de desastre

### Aparência:
- **Font Size**: Tamanho da fonte em pixels
- **Font Family**: Família da fonte
- **Show Border**: Exibir borda no widget
- **Border Width**: Largura da borda em pixels
- **Padding**: Espaçamento interno em pixels

## Cores por Severidade

O widget utiliza as cores padrão do Zabbix:

- **OK (sem alarmes)**: Cinza (#97AAB3)
- **Not classified**: Cinza (#97AAB3)
- **Information**: Azul (#7499FF)
- **Warning**: Amarelo (#FFC859)
- **Average**: Laranja (#FFA059)
- **High**: Vermelho claro (#E97659)
- **Disaster**: Vermelho (#E45959)

## Funcionalidades

### Auto-refresh
O widget atualiza automaticamente a cada 30 segundos.

### Interatividade
- Clique no widget para abrir a página de problemas filtrada pelo grupo
- Efeito hover com zoom e sombra
- Cursor pointer indica que é clicável

### Responsividade
- Adapta fonte e tamanhos para telas pequenas
- Mantém proporções de cartão em diferentes resoluções
- Suporte a temas escuros

## Estrutura de Arquivos

```
hostgroupalarms/
├── manifest.json              # Configuração do módulo
├── Widget.php                 # Classe principal do widget
├── README.md                  # Documentação
├── actions/
│   └── WidgetView.php        # Controlador da visualização
├── includes/
│   └── WidgetForm.php        # Formulário de configuração
├── views/
│   ├── widget.edit.php       # Visualização do formulário
│   └── widget.view.php       # Visualização do widget
└── assets/
    ├── css/
    │   └── style.css         # Estilos CSS
    └── js/
        ├── class.widget.js   # Classe JavaScript do widget
        └── widget.edit.js    # JavaScript do formulário
```

## Requisitos

- Zabbix 7.0 ou superior
- PHP 7.4 ou superior
- Permissões de leitura na API do Zabbix

## Suporte

Para reportar bugs ou solicitar funcionalidades, entre em contato com o desenvolvedor.

## Licença

Este módulo é distribuído sob a mesma licença do Zabbix (GPL v2).