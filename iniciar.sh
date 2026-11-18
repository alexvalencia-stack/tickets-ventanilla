#!/usr/bin/env bash
# Arranca el sistema de turnos. Uso: bash iniciar.sh
cd "$(dirname "$0")" || exit 1
source ./entorno.sh
# 0.0.0.0 para que el kiosco, la TV y las ventanillas entren desde la red local: http://IP-DE-ESTE-EQUIPO:8000
php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
