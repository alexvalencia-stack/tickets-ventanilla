# Uso: source entorno.sh
# Solo hace falta mientras PostgreSQL NO esté instalado en el sistema: usa la copia local
# descomprimida en ~/.local/share/tickets-pg. Con "sudo apt install postgresql php8.3-pgsql"
# este archivo deja de ser necesario.
PG_LOCAL="$HOME/.local/share/tickets-pg"
if [ -d "$PG_LOCAL/root" ]; then
    export LD_LIBRARY_PATH="$PG_LOCAL/root/usr/lib/x86_64-linux-gnu${LD_LIBRARY_PATH:+:$LD_LIBRARY_PATH}"
    export PHP_INI_SCAN_DIR="/etc/php/8.3/cli/conf.d:$PG_LOCAL/phpini"
    export PATH="$PG_LOCAL/root/usr/lib/postgresql/16/bin:$PATH"
    pg_ctl -D "$PG_LOCAL/data" status >/dev/null 2>&1 ||
        pg_ctl -D "$PG_LOCAL/data" -l "$PG_LOCAL/pg.log" -o "-p 5432 -k $PG_LOCAL -c listen_addresses=127.0.0.1" start
fi
