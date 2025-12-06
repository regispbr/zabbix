# 1º Criar arquivo do script
~~~bash
nano /opt/sync_modules.sh
~~~
~~~bash
#!/bin/bash

REPO_DIR="/opt/zabbix-github"
PASTA_ALVO="modules"
DESTINO="/usr/share/zabbix/modules"
BRANCH="main"

while true; do
    cd "$REPO_DIR" || exit

    # Atualiza info do repositório remoto
    git fetch origin $BRANCH > /dev/null 2>&1

    # Verifica mudanças apenas na pasta
    MUDOU=$(git diff --name-only HEAD origin/$BRANCH -- $PASTA_ALVO)

    if [ -n "$MUDOU" ]; then
        echo "[ $(date) ] Mudança detectada — sincronizando..."

        git pull origin $BRANCH > /dev/null 2>&1
        echo "[ $(date) ] Sincronização inciada.."
        rsync -av --delete "$REPO_DIR/$PASTA_ALVO/" "$DESTINO/" > /dev/null 2>&1
        echo "[ $(date) ] Atualizando Permissões.."
        chown -R www-data:www-data "$DESTINO/"
        chmod -R 755 "$DESTINO/"
        echo "[ $(date) ] Sincronização concluída."
    fi

    # Espera 2 segundos
    sleep 2
done

~~~

# 2º Dar permissão de execução 
~~~bash
chmod +x /opt/sync_modules.sh
~~~
# 3º realizar o clone do git
git clone https://github.com/regispbr/zabbix.git /opt/zabbix-github

# 4º Criar systemd para o serviço
~~~bash
nano /etc/systemd/system/zabbix-modules-sync.service
~~~
~~~bash
[Unit]
Description=Sync modules from GitHub every 2 seconds
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
ExecStart=/opt/sync_modules_loop.sh
Restart=always
RestartSec=2
User=root

# Evita que o systemd trave o serviço caso falhe repetidamente
StartLimitIntervalSec=0

[Install]
WantedBy=multi-user.target
~~~

## Ativar deamon

~~~bash
systemctl daemon-reload
systemctl enable zabbix-modules-sync
systemctl start zabbix-modules-sync
~~~
 ### Ver Logs
 ~~~bash
journalctl -u zabbix-modules-sync -f
~~~
