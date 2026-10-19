#!/usr/bin/bash
set -euo pipefail

# Solo utiliza archivos instalados por esta carpeta, nunca los de una web.
CONFIGURACION="/etc/suricata/ids-ips"
ACCION="${1:-validar}"

if [[ "$EUID" -ne 0 ]]; then
    echo "Ejecuta este archivo con sudo."
    exit 1
fi

if [[ "$ACCION" != "validar" && "$ACCION" != "iniciar" ]]; then
    echo "Uso: ejecutar.sh validar | iniciar"
    exit 1
fi

# opciones.conf debe pertenecer a root y no ser escribible por otros usuarios.
# shellcheck source=/dev/null
source "$CONFIGURACION/opciones.conf"

if [[ "$MODO" != "ids" && "$MODO" != "ips" ]]; then
    echo 'MODO debe ser "ids" o "ips".'
    exit 1
fi

if [[ ! "$INTERFAZ" =~ ^[a-zA-Z0-9_.:-]{1,15}$ ]] || [[ "$INTERFAZ" == "CAMBIAR" ]]; then
    echo "Completa INTERFAZ con el nombre real de la interfaz."
    exit 1
fi
ip link show dev "$INTERFAZ" > /dev/null

# ipaddress comprueba cada dirección/red, evitando aceptar texto cualquiera.
python3 - "$RED_LOCAL" <<'PY'
import ipaddress
import sys

try:
    network_text = sys.argv[1]
    if not network_text.startswith('[') or not network_text.endswith(']'):
        raise ValueError('faltan los corchetes')
    for network in network_text[1:-1].split(','):
        ipaddress.ip_network(network.strip(), strict=False)
except ValueError:
    sys.exit('RED_LOCAL debe ser una lista de IP o redes CIDR entre corchetes.')
PY

SURICATA="$(command -v suricata)"
ARGUMENTOS=(-c "$CONFIGURACION/suricata.yaml"
    --set "vars.address-groups.HOME_NET=$RED_LOCAL"
    --user suricata --group suricata --init-errors-fatal)

if [[ "$MODO" == "ips" ]]; then
    INFORMACION="$("$SURICATA" --build-info)"
    if ! grep -Eiq 'NFQueue support:[[:space:]]+yes' <<< "$INFORMACION"; then
        echo "Este paquete de Suricata no tiene soporte NFQUEUE para IPS."
        exit 1
    fi
    ARGUMENTOS+=(-q 100)
else
    ARGUMENTOS+=(--af-packet="$INTERFAZ")
fi

if [[ "$ACCION" == "validar" ]]; then
    "$SURICATA" "${ARGUMENTOS[@]}" -T
    if [[ "$MODO" == "ips" ]]; then
        nft --check --file "$CONFIGURACION/ips.nft"
    fi
else
    exec "$SURICATA" "${ARGUMENTOS[@]}"
fi
