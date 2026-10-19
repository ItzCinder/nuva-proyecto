# Suricata IDS / IPS para AlmaLinux

Carpeta independiente para **AlmaLinux 9 o 10**, con Apache y MariaDB ya instalados. Puedes copiarla sola a otro servidor. No utiliza archivos, credenciales, tablas ni rutas de una aplicación web.

Suricata cumple las dos funciones:

- **IDS:** observa la interfaz de red y registra alertas. Es el modo inicial.
- **IPS:** inspecciona las conexiones web del servidor y sus contenedores mediante NFQUEUE y permite bloquear firmas `drop` o `reject`.

## Instalación con el asistente

Desde esta carpeta, en el servidor AlmaLinux:

```bash
cp -n config/opciones.conf.example config/opciones.conf
ip -br address
nano config/opciones.conf
sudo bash instalar.sh
```

**Ya se incluye `config/opciones.conf` preparado para `eth0`, la IP `10.0.0.137`, loopback y el rango privado habitual de Docker (`172.16.0.0/12`).** Comprueba que sigan siendo esos valores. La máscara `/32` identifica solo el servidor; la interfaz puede conservar su máscara de red `/23`.

En otra máquina, completa `INTERFAZ` con el nombre real, por ejemplo `eth0`, y `RED_LOCAL` con la IP o red del servidor, por ejemplo `[10.0.0.137/32]`. Conserva `MODO="ids"` para comenzar. Si hay IPv6 público, inclúyelo en `RED_LOCAL`.

El instalador habilita el repositorio COPR de OISF para Suricata 8, instala las dependencias, descarga ET Open, comprueba la configuración y registra `suricata-local.service`. Requiere Internet y permisos administrativos. Se detiene si encuentra una instalación propia anterior o un Suricata del paquete activo o habilitado, para permitir revisar la configuración existente.

## Instalación manual y archivos de configuración

Lee [INSTALACION-MANUAL.md](INSTALACION-MANUAL.md). Incluye todos los comandos `cp`, las rutas de destino, el cambio de modo, las comprobaciones y la recuperación. Si el instalador quedó a mitad, continúa desde el paso pendiente; no sobrescribas configuraciones que ya hayas adaptado.

| Archivo | Función |
| --- | --- |
| `config/opciones.conf.example` | Modo, interfaz y direcciones del servidor. |
| `config/suricata.yaml` | Configuración de Suricata, reglas y registros. |
| `config/ips.nft` | Envío de los puertos web 80/443/8080 a NFQUEUE 100, incluido Docker. |
| `config/local.rules` | Dos firmas controladas para verificar alertas y bloqueo. |
| `config/drop.conf` | Firmas ET Open elegidas para bloqueo. Inicialmente vacío. |
| `config/suricata-local.service` | Servicio que inicia Suricata y administra su tabla del firewall. |
| `config/logrotate.conf` | Rotación de los registros. |
| `ejecutar.sh` | Validación e inicio del motor; lee la configuración instalada. |
| `cortafuegos.sh` | Aplica o retira solamente la tabla `suricata_local`. |

## Alcance

La configuración IPS cubre **Apache en el host y Apache dentro de Docker con puertos publicados 80/443/8080**, para IPv4 e IPv6. La tabla usa INPUT/OUTPUT para conexiones del host y FORWARD con seguimiento de conexiones para el tráfico publicado por Docker. Reconoce el puerto original aunque Docker traduzca 8080 a 80. Incluye las conexiones del proxy local hacia Docker, con DNAT o con un proxy de usuario.

No modifica Apache, MariaDB, SSH, SELinux ni las reglas existentes de firewalld/Docker. Tampoco abre puertos. MariaDB no se incluye en la cola IPS. Si cambian los puertos web, adapta las listas de `ips.nft`. Redes Docker distintas de `172.16.0.0/12`, direcciones IPv6 de contenedores, una VPN, otro proxy o un router requieren revisar `RED_LOCAL` y el recorrido del tráfico.

En IDS se observa la interfaz elegida: las conexiones entre host y contenedor pueden circular por loopback o un puente diferente. Las visitas externas llegan por `eth0`; para comprobar el IDS usa otro equipo. En IPS se sigue el tráfico seleccionado por nftables, independientemente de esa interfaz.

**HTTPS cifra las peticiones.** Suricata puede analizar información de red/TLS, pero no leer las rutas, formularios o consultas SQL dentro de HTTPS sin descifrado. Si el Apache del host termina HTTPS y reenvía por HTTP local al puerto 8080 de Docker, ese tramo interno sí puede inspeccionarse con las reglas incluidas. HTTPS directo hasta el contenedor permanece cifrado. Las firmas HTTP de comprobación se verifican sobre HTTP desde otro equipo, en una red de pruebas. La validación de entradas y las consultas preparadas siguen correspondiendo a la aplicación.

**Activar IPS no convierte todas las alertas en bloqueos.** Por defecto ET Open proporciona detecciones; el bloqueo se limita a las firmas que ya tengan acción `drop`/`reject`, la ruta controlada de bloqueo y las firmas que selecciones en `drop.conf`. La guía muestra cómo elegirlas.

Se usa `bypass` y `fail-open`: si Suricata no escucha o la cola se satura, se permite continuar el tráfico web. Esto favorece la disponibilidad, pero durante ese período no hay protección IPS. Revisa el estado del servicio y sus contadores. Los límites y verificaciones pendientes están documentados en la guía.

## Referencias oficiales

- [Instalación RPM de Suricata 8 por OISF](https://docs.suricata.io/en/suricata-8.0.0/install/rpm.html).
- [Suricata inline / NFQUEUE](https://docs.suricata.io/en/suricata-8.0.0/setting-up-ipsinline-for-linux.html).
- [Configuración YAML](https://docs.suricata.io/en/suricata-8.0.0/configuration/suricata-yaml.html) y [opciones del motor](https://docs.suricata.io/en/suricata-8.0.0/command-line-options.html).
- [Suricata-Update](https://suricata-update.readthedocs.io/en/latest/quickstart.html).
- [Repositorios y configuración de AlmaLinux](https://wiki.almalinux.org/documentation/after-installation-guide.html).
