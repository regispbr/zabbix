IP Puplico : 177.69.152.45


## Gerar Chave PSK
~~~bash
openssl rand -hex 32 > nomedocliente.psk
cat nomedocliente.psk
~~~
## Configuração Zabbix Proxy(zabbix_proxy.conf) ou Zabbix Agent (zabbix_agent.conf)
~~~bash
TLSConnect=psk
TLSAccept=psk
TLSPSKIdentity=nomedocliente
TLSPSKFile=/etc/zabbix/nomedocliente.psk
~~~
## Permisão do PSK
~~~bash
chmod 640 nomedocliente.psk
chown zabbix: nomedocliente.psk
~~~
