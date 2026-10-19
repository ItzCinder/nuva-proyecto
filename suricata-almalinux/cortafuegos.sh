#!/usr/bin/bash
set -euo pipefail

if [[ "$EUID" -ne 0 ]]; then
    echo "Ejecuta este archivo con sudo."
    exit 1
fi

ACCION="${1:-}"
CONFIGURACION="/etc/suricata/ids-ips"

if [[ "$ACCION" == "retirar" ]]; then
    # Incluso si opciones.conf está roto, siempre se puede retirar nuestra tabla.
    if nft list table inet suricata_local > /dev/null 2>&1; then
        nft delete table inet suricata_local
    fi
elif [[ "$ACCION" == "aplicar" ]]; then
    # shellcheck source=/dev/null
    source "$CONFIGURACION/opciones.conf"
    if [[ "$MODO" == "ids" ]]; then
        exit 0
    fi
    if [[ "$MODO" != "ips" ]]; then
        echo "Modo desconocido: no se aplicaron reglas."
        exit 1
    fi
    if nft list table inet suricata_local > /dev/null 2>&1; then
        echo "Ya existe la tabla suricata_local. Revísala antes de reemplazarla."
        exit 1
    fi
    nft --check --file "$CONFIGURACION/ips.nft"
    nft --file "$CONFIGURACION/ips.nft"
else
    echo "Uso: cortafuegos.sh aplicar | retirar"
    exit 1
fi
