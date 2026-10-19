# Configurar Suricata manualmente en AlmaLinux

Estos pasos permiten instalar la configuración **sin ejecutar `instalar.sh`**. Se parte de AlmaLinux 9/10 con Apache y MariaDB. Copia esta carpeta a tu directorio de trabajo y abre una terminal dentro de ella. No necesita estar en la carpeta publicada por Apache.

Los archivos de `config/` se copian tal como están; solo debes adaptar `opciones.conf` y, si corresponde, los puertos de `ips.nft`. Los scripts pequeños `ejecutar.sh` y `cortafuegos.sh` se instalan como ayudantes del servicio. Al final se explica también cómo iniciar el motor directamente.

## 1. Identificar el servidor

```bash
cat /etc/os-release
ip -br address
ip route
sudo ss -lntp
sudo systemctl status httpd mariadb --no-pager
sudo systemctl is-active suricata suricata-local
sudo systemctl is-enabled suricata suricata-local
```

Que los servicios de Suricata aparezcan como inactivos o inexistentes es normal antes de instalar. Si ya tienes uno activo o habilitado para el arranque, conserva su configuración y revisa cómo migrarlo: ambos entrarían en conflicto. El instalador se detiene en ese caso. Cuando hayas respaldado la instalación anterior y decidido sustituir su servicio por el de esta carpeta, deshabilita el anterior con `sudo systemctl disable --now suricata.service`. Esto conserva sus archivos. No mantengas ambos habilitados al arrancar.

**Para la máquina de la imagen ya está preparado `config/opciones.conf`: `INTERFAZ="eth0"`, la IP `10.0.0.137/32`, loopback y el rango privado habitual de Docker (`172.16.0.0/12`).** El `/32` indica una sola IP protegida; no cambia la máscara `/23` que tiene configurada tu interfaz.

Anota la interfaz por la que llegan las visitas y las IP del servidor. Ejemplo: interfaz `eth0`, IP `10.0.0.137`. Con NAT, incluye la dirección que realmente ve el servidor. Con IPv6, incluye también su IP o prefijo. No copies literalmente las direcciones de ejemplo.

## 2. Instalar los paquetes

```bash
sudo dnf install -y epel-release dnf-plugins-core
sudo dnf config-manager --set-enabled crb
sudo dnf copr enable -y @oisf/suricata-8.0
sudo dnf install -y suricata nftables python3 iproute logrotate policycoreutils
suricata -V
suricata --build-info
```

Se instala la versión actual de la rama 8 disponible para tu sistema desde el repositorio OISF. Para IPS, la información de compilación debe mostrar `NFQueue support: yes`. Si el repositorio no ofrece paquetes para tu versión o arquitectura, detente y consulta las [instrucciones RPM oficiales](https://docs.suricata.io/en/suricata-8.0.0/install/rpm.html); no instales paquetes de otra versión de AlmaLinux.

No habilites `nftables.service` ni reemplaces `/etc/sysconfig/nftables.conf`: el servicio de esta carpeta carga únicamente su tabla. Puedes conservar firewalld activo.

## 3. Preparar las opciones

```bash
cp -n config/opciones.conf.example config/opciones.conf
nano config/opciones.conf
```

Ejemplo que debes adaptar:

```bash
MODO="ids"
INTERFAZ="eth0"
RED_LOCAL="[10.0.0.137/32,127.0.0.0/8,172.16.0.0/12,::1/128]"
```

`RED_LOCAL` se convierte en `HOME_NET`: indica qué direcciones pertenecen al servidor o a la red protegida para que las firmas distingan el destino local. Una IP individual usa `/32` en IPv4 o `/128` en IPv6. Puedes escribir varias redes separadas por comas, dentro de los mismos corchetes.

`opciones.conf` es un archivo de Bash leído por un servicio privilegiado: conserva solo estas asignaciones, sin comandos. Debe quedar en manos de root y sin permisos de escritura para otros usuarios.

## 4. Copiar la configuración

En una instalación nueva, ejecuta:

```bash
sudo install -d -m 0750 -o root -g suricata /etc/suricata/ids-ips
sudo cp -n /etc/suricata/suricata.yaml /etc/suricata/ids-ips/base.yaml
sudo cp -i config/suricata.yaml /etc/suricata/ids-ips/suricata.yaml
sudo cp -i config/opciones.conf /etc/suricata/ids-ips/opciones.conf
sudo cp -i config/local.rules /etc/suricata/ids-ips/local.rules
sudo cp -i config/drop.conf /etc/suricata/ids-ips/drop.conf
sudo cp -i config/ips.nft /etc/suricata/ids-ips/ips.nft
sudo chown root:suricata /etc/suricata/ids-ips/*
sudo chmod 0640 /etc/suricata/ids-ips/*
sudo chown root:root /etc/suricata/ids-ips/opciones.conf
sudo chmod 0600 /etc/suricata/ids-ips/opciones.conf
```

`cp -n` conserva una copia base anterior. `cp -i` pregunta antes de sobrescribir: si retomas una instalación o ya adaptaste un archivo del servidor, revísalo primero. No se modifica `/etc/suricata/suricata.yaml`. Nuestra configuración incluye `base.yaml` y define encima las reglas y salidas de este servicio.

Si Apache o los puertos publicados por Docker usan valores diferentes de 80/443/8080, edita `/etc/suricata/ids-ips/ips.nft`. Cambia **todas las listas**: destino en entrada, origen en salida y puerto original de las conexiones Docker. No añadas SSH ni MariaDB a esas listas para este despliegue.

## 5. Copiar los ayudantes y preparar registros

```bash
sudo install -d -m 0755 /usr/local/libexec/suricata-local
sudo cp -i ejecutar.sh /usr/local/libexec/suricata-local/ejecutar.sh
sudo cp -i cortafuegos.sh /usr/local/libexec/suricata-local/cortafuegos.sh
sudo chown root:root /usr/local/libexec/suricata-local/*.sh
sudo chmod 0755 /usr/local/libexec/suricata-local/*.sh
sudo install -d -m 0750 -o suricata -g suricata /var/log/suricata/ids-ips
sudo install -d -m 0750 -o suricata -g suricata /var/lib/suricata/ids-ips/rules
sudo restorecon -RF /etc/suricata/ids-ips /usr/local/libexec/suricata-local /var/log/suricata/ids-ips /var/lib/suricata/ids-ips
```

Las rutas del sistema son fijas y no dependen de dónde hayas copiado esta carpeta. Los ayudantes validan el modo, la interfaz y las IP antes de iniciar Suricata. El proceso comienza con los permisos de captura y después utiliza el usuario/grupo `suricata`.

## 6. Descargar las reglas y validar

```bash
sudo suricata-update --suricata-conf /etc/suricata/ids-ips/base.yaml --output /var/lib/suricata/ids-ips/rules --drop-conf /etc/suricata/ids-ips/drop.conf --no-test --no-reload
sudo chown -R suricata:suricata /var/lib/suricata/ids-ips
sudo restorecon -RF /var/lib/suricata/ids-ips
sudo /usr/local/libexec/suricata-local/ejecutar.sh validar
```

`suricata-update` descarga las firmas ET Open. Las opciones `--no-test --no-reload` evitan que el actualizador valide o recargue el servicio del paquete; la validación completa se ejecuta inmediatamente después sobre nuestra configuración. **Si falla, no continúes con el inicio.** Si no hay conexión, vuelve a descargar al recuperarla: no reemplaces las firmas con un archivo vacío para simular una instalación correcta.

## 7. Copiar y arrancar el servicio

```bash
sudo cp -i config/suricata-local.service /etc/systemd/system/suricata-local.service
sudo cp -i config/logrotate.conf /etc/logrotate.d/suricata-local
sudo chown root:root /etc/systemd/system/suricata-local.service /etc/logrotate.d/suricata-local
sudo chmod 0644 /etc/systemd/system/suricata-local.service /etc/logrotate.d/suricata-local
sudo restorecon /etc/systemd/system/suricata-local.service /etc/logrotate.d/suricata-local
sudo systemctl daemon-reload
sudo systemctl enable --now suricata-local.service
sudo systemctl status suricata-local.service --no-pager
sudo journalctl -u suricata-local.service -n 60 --no-pager
```

Usa `suricata-local.service`, no `suricata.service`. El servicio comprueba la configuración antes de cada inicio. En IPS carga la tabla después de iniciar el proceso; al detenerse la retira. `systemctl is-active` por sí solo no demuestra que esté inspeccionando: comprueba también los paquetes y las alertas.

## 8. Comprobar el IDS

Desde **otro equipo de tu red de pruebas**, sustituyendo la IP por la del servidor:

```bash
curl --max-time 10 http://10.0.0.137/__suricata_alerta__
```

Si usas un virtual host, añade `-H 'Host: tu-dominio'`. Puede responder 404 o una redirección: no necesitas crear esa ruta en Apache. Desde el servidor, busca el mensaje `LOCAL IDS comprobacion controlada`:

```bash
sudo tail -n 30 /var/log/suricata/ids-ips/fast.log
sudo tail -n 10 /var/log/suricata/ids-ips/eve.json
sudo tail -n 30 /var/log/suricata/ids-ips/stats.log
```

Usa HTTP para esta comprobación. En HTTPS la ruta va cifrada y la firma HTTP no puede verla. Si no hay alerta, revisa interfaz, HOME_NET, contadores y que la petición alcance el servidor. Una petición desde el propio servidor puede ir por loopback y no pasar por la interfaz observada.

## 9. Activar el IPS

Primero verifica el IDS. Comprueba que la cola **100** no esté siendo usada por otra instalación:

```bash
sudo cat /proc/net/netfilter/nfnetlink_queue
```

Si el archivo no existe aún, puede significar que el módulo NFQUEUE no está cargado; el servicio y nftables lo solicitarán al usarlo. Si otra herramienta usa esa cola, elige otra y cambia el número tanto en `ips.nft` como en el argumento `-q` de `ejecutar.sh`.

```bash
sudo nano /etc/suricata/ids-ips/opciones.conf
# Cambia solo MODO="ids" por MODO="ips".
sudo /usr/local/libexec/suricata-local/ejecutar.sh validar
sudo systemctl restart suricata-local.service
sudo systemctl status suricata-local.service --no-pager
sudo nft list table inet suricata_local
```

Los contadores de esa tabla deben aumentar con visitas web. Se inspeccionan solicitudes y respuestas de los puertos 80/443/8080 en el host y en conexiones publicadas por Docker. La cadena FORWARD reconoce el puerto original anterior a la traducción de Docker; las cadenas INPUT/OUTPUT cubren también el proxy local hacia el contenedor. La prioridad 20 coloca la inspección después de los filtros habituales de firewalld; si tienes reglas propias con otras prioridades, revisa el orden con `sudo nft list ruleset`.

Después de reiniciar el servidor, recargar el firewall o cambiar las redes de Docker, vuelve a comprobar el servicio, la tabla y sus contadores. Si una herramienta externa eliminó nuestra tabla, reinicia `suricata-local.service` para restaurarla y verifica otra vez el bloqueo controlado.

Desde el otro equipo, compara:

```bash
curl --max-time 10 http://10.0.0.137/__suricata_alerta__
curl --max-time 10 http://10.0.0.137/__suricata_bloqueo__
```

La primera petición sigue llegando a Apache y deja una alerta. La segunda debe bloquearse, normalmente con tiempo de espera agotado, y dejar `LOCAL IPS bloqueo controlado` en los registros. En `eve.json`, la alerta correspondiente debe indicar `action: blocked`. Comprueba también que una visita HTTP/HTTPS normal funcione y que puedas seguir usando SSH y la aplicación conectada a MariaDB.

### Elegir firmas reales de bloqueo

Busca primero la alerta real en `eve.json`, identifica su `signature_id` y revisa su regla en `/var/lib/suricata/ids-ips/rules/suricata.rules`. Añade **el SID numérico real** en una línea de `/etc/suricata/ids-ips/drop.conf`. No escribas el nombre ni un SID inventado.

Después actualiza y comprueba:

```bash
sudo nano /etc/suricata/ids-ips/drop.conf
sudo suricata-update --suricata-conf /etc/suricata/ids-ips/base.yaml --output /var/lib/suricata/ids-ips/rules --drop-conf /etc/suricata/ids-ips/drop.conf --no-test --no-reload
sudo chown -R suricata:suricata /var/lib/suricata/ids-ips
sudo restorecon -RF /var/lib/suricata/ids-ips
sudo /usr/local/libexec/suricata-local/ejecutar.sh validar
sudo systemctl restart suricata-local.service
```

La selección inicial está vacía: **no se convierte todo ET Open en bloqueos**. Algunas detecciones son informativas o pueden coincidir con visitas legítimas. El mismo comando sirve para actualizar las firmas periódicamente; estas instrucciones no crean una actualización automática. Conserva una copia de las reglas anteriores si necesitas poder volver a ellas.

### Apache del servidor y Apache dentro de Docker

En una instalación con el puerto del contenedor publicado solamente en `127.0.0.1:8080`, las visitas entran por el Apache del servidor, que actúa como proxy hacia el contenedor. Las reglas de esta carpeta contemplan ese recorrido. No publiques 8080 a Internet para comprobar Suricata.

Consulta las redes reales:

```bash
sudo docker network ls
sudo docker network inspect NOMBRE_DE_LA_RED
```

Sustituye `NOMBRE_DE_LA_RED` por el nombre del listado. Revisa `IPAM.Config.Subnet`. El rango `172.16.0.0/12` de la configuración preparada abarca las redes Docker habituales 172.17, 172.18, etc.; puedes sustituirlo por las subredes exactas de tus contenedores. Si tienes redes en otro rango o IPv6, añádelas a `RED_LOCAL`.

Con HTTPS terminado en el Apache del servidor, Suricata no puede leer la petición cifrada exterior, pero el tramo posterior **HTTP** hacia 8080 puede inspeccionarse en IPS. Si también usas HTTPS en ese tramo, seguirá cifrado. Verifica ambos tramos con los contadores: no basta con ver el servicio activo. Conserva los filtros de firewalld y Docker; estas reglas no autorizan conexiones que ellos bloqueen.

## 10. Registros y espacio en disco

| Registro | Contenido |
| --- | --- |
| `fast.log` | Alertas breves legibles. |
| `eve.json` | Eventos estructurados, firma, direcciones y acción permitida/bloqueada. |
| `stats.log` | Contadores del motor, útiles para verificar captura y pérdidas. |
| `journalctl -u suricata-local` | Inicio, errores de configuración y fallos del servicio. |

Los tres archivos están en `/var/log/suricata/ids-ips`. No se habilitan volcados de payload ni capturas completas. Aun así, sus IP y metadatos deben tratarse como información privada.

La configuración de logrotate conserva 14 archivos rotados y comprime los antiguos. `maxsize 50M` se revisa cuando se ejecuta logrotate; **no es un límite estricto en tiempo real**. Comprueba que tu servidor ejecute logrotate regularmente:

```bash
sudo logrotate --debug /etc/logrotate.d/suricata-local
systemctl list-timers --all | grep logrotate
ls /etc/cron.daily/logrotate
sudo du -sh /var/log/suricata/ids-ips
```

Según la instalación habrá temporizador o tarea cron; alguno de los comandos de consulta puede no encontrar su archivo. Si no hay ninguno, configura la ejecución periódica de logrotate. `copytruncate` permite rotar sin reiniciar Suricata, con una pequeña ventana en la que podrían perderse líneas. El diario de systemd tiene su propia política de retención.

## 11. Retirar el IPS o recuperarse de un problema

Para detener y deshabilitar esta instalación, conservando archivos y registros:

```bash
sudo systemctl disable --now suricata-local.service
sudo /usr/local/libexec/suricata-local/cortafuegos.sh retirar
```

Si el servicio o el ayudante no funcionan, la retirada manual afecta **solo** a nuestra tabla:

```bash
sudo nft list table inet suricata_local
sudo nft delete table inet suricata_local
```

Si la tabla ya no existe, no hay nada que retirar. No vacíes todo el firewall. Para volver al IDS, cambia `MODO="ids"` en `/etc/suricata/ids-ips/opciones.conf` y vuelve a iniciar el servicio. `bypass` deja pasar tráfico si no hay motor escuchando; `fail-open` lo deja pasar al saturarse la cola. Durante esas situaciones no se garantiza inspección: revisa fallos, reinicios y contadores de pérdidas.

Ante un fallo:

```bash
sudo journalctl -u suricata-local.service -n 100 --no-pager
sudo /usr/local/libexec/suricata-local/ejecutar.sh validar
sudo systemctl status firewalld --no-pager
sudo ausearch -m AVC -ts recent
```

Si SELinux deniega una operación, revisa los contextos con `restorecon` y la política del paquete; no lo desactives para ocultar el problema. Si está disponible, `ausearch` pertenece a las herramientas de auditoría del servidor. Tampoco reinicies Apache o MariaDB para resolver errores de sintaxis de Suricata.

## 12. Iniciar directamente, sin los ayudantes del servicio

Esto sirve para diagnóstico en primer plano, **con el servicio detenido**, manteniendo las configuraciones copiadas y reglas descargadas. Sustituye interfaz y HOME_NET por los tuyos:

```bash
sudo systemctl stop suricata-local.service
sudo suricata -T -c /etc/suricata/ids-ips/suricata.yaml --set 'vars.address-groups.HOME_NET=[10.0.0.137/32,127.0.0.0/8,172.16.0.0/12,::1/128]' --init-errors-fatal
sudo suricata -c /etc/suricata/ids-ips/suricata.yaml --set 'vars.address-groups.HOME_NET=[10.0.0.137/32,127.0.0.0/8,172.16.0.0/12,::1/128]' --af-packet=eth0 --user suricata --group suricata --init-errors-fatal
```

Para IPS, en lugar del último comando usa:

```bash
sudo suricata -c /etc/suricata/ids-ips/suricata.yaml --set 'vars.address-groups.HOME_NET=[10.0.0.137/32,127.0.0.0/8,172.16.0.0/12,::1/128]' -q 100 --user suricata --group suricata --init-errors-fatal
```

Cuando el motor esté listo, abre otra terminal y carga **solo** la tabla incluida, comprobando antes que no exista:

```bash
sudo nft --check --file /etc/suricata/ids-ips/ips.nft
sudo nft list table inet suricata_local
# Si la tabla NO existe:
sudo nft --file /etc/suricata/ids-ips/ips.nft
```

Al terminar, usa Ctrl+C para detener el motor y retira la tabla con `sudo nft delete table inet suricata_local`. El inicio directo no instala persistencia ni ejecuta la retirada automática del servicio.

## Referencias

Consulta las [fuentes oficiales enlazadas en README.md](README.md#referencias-oficiales) para ampliar los conceptos. Esta carpeta proporciona una configuración de partida; la captura real, los bloqueos, SELinux, el reinicio del servidor y la convivencia con las reglas propias deben verificarse en el AlmaLinux de destino.
