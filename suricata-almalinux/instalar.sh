#!/usr/bin/bash
set -euo pipefail

# Ejecutar únicamente en el servidor AlmaLinux, después de editar opciones.conf.
CARPETA="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
DESTINO="/etc/suricata/ids-ips"

if [[ "$EUID" -ne 0 ]]; then
    echo "Uso: sudo bash instalar.sh"
    exit 1
fi

# shellcheck source=/dev/null
source /etc/os-release
if [[ "$ID" != "almalinux" ]] || [[ "${VERSION_ID%%.*}" != "9" && "${VERSION_ID%%.*}" != "10" ]]; then
    echo "Este instalador está preparado para AlmaLinux 9 o 10."
    exit 1
fi

if [[ ! -f "$CARPETA/config/opciones.conf" ]]; then
    echo "Copia config/opciones.conf.example como config/opciones.conf y complétalo."
    exit 1
fi

if [[ -e "$DESTINO" || -e /etc/systemd/system/suricata-local.service ]]; then
    echo "Ya existe una instalación. No se sobrescribirá; usa INSTALACION-MANUAL.md."
    exit 1
fi

if systemctl is-active --quiet suricata.service || systemctl is-enabled --quiet suricata.service; then
    echo "Ya hay otro Suricata activo o habilitado. Revisa su configuración y la guía antes de instalar este."
    exit 1
fi

echo "1. Instalar Suricata 8 y sus herramientas desde el repositorio de OISF."
dnf install -y epel-release dnf-plugins-core
dnf config-manager --set-enabled crb
dnf copr enable -y @oisf/suricata-8.0
dnf install -y suricata nftables python3 iproute logrotate policycoreutils

if systemctl is-active --quiet suricata.service || systemctl is-enabled --quiet suricata.service; then
    echo "El servicio del paquete está activo o habilitado. Revisa la guía antes de continuar."
    exit 1
fi

echo "2. Copiar la configuración sin modificar la del paquete."
install -d -m 0750 -o root -g suricata "$DESTINO"
cp -n /etc/suricata/suricata.yaml "$DESTINO/base.yaml"
cp "$CARPETA/config/suricata.yaml" "$DESTINO/suricata.yaml"
cp "$CARPETA/config/local.rules" "$DESTINO/local.rules"
cp "$CARPETA/config/drop.conf" "$DESTINO/drop.conf"
cp "$CARPETA/config/ips.nft" "$DESTINO/ips.nft"
cp "$CARPETA/config/opciones.conf" "$DESTINO/opciones.conf"
chown root:suricata "$DESTINO/"*
chmod 0640 "$DESTINO/"*
chown root:root "$DESTINO/opciones.conf"
chmod 0600 "$DESTINO/opciones.conf"

install -d -m 0755 /usr/local/libexec/suricata-local
install -m 0755 "$CARPETA/ejecutar.sh" /usr/local/libexec/suricata-local/ejecutar.sh
install -m 0755 "$CARPETA/cortafuegos.sh" /usr/local/libexec/suricata-local/cortafuegos.sh
install -d -m 0750 -o suricata -g suricata /var/log/suricata/ids-ips
install -d -m 0750 -o suricata -g suricata /var/lib/suricata/ids-ips/rules

echo "3. Descargar reglas ET Open y comprobar la configuración."
suricata-update --suricata-conf "$DESTINO/base.yaml" \
    --output /var/lib/suricata/ids-ips/rules \
    --drop-conf "$DESTINO/drop.conf" --no-test --no-reload
chown -R suricata:suricata /var/lib/suricata/ids-ips
restorecon -RF "$DESTINO" /var/log/suricata/ids-ips /var/lib/suricata/ids-ips /usr/local/libexec/suricata-local
/usr/local/libexec/suricata-local/ejecutar.sh validar

echo "4. Registrar el servicio y la rotación de logs."
install -m 0644 "$CARPETA/config/suricata-local.service" /etc/systemd/system/suricata-local.service
install -m 0644 "$CARPETA/config/logrotate.conf" /etc/logrotate.d/suricata-local
restorecon /etc/systemd/system/suricata-local.service /etc/logrotate.d/suricata-local
systemctl daemon-reload
systemctl enable --now suricata-local.service

echo "Suricata instalado. Consulta: systemctl status suricata-local.service"
echo "Guía de comprobación, cambio de modo y recuperación: INSTALACION-MANUAL.md"
