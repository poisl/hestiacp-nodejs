#!/bin/bash

set -euo pipefail

GREEN="\e[32m"
BLUE="\e[34m"
YELLOW="\e[33m"
ENDCOLOR="\e[0m"
START="[${GREEN}Poisl/${ENDCOLOR}${BLUE}hestiacp-nodejs${ENDCOLOR}]"

INSTALLER_DIR="/usr/local/hestia/web/src/app/WebApp/Installers/NodeJs"
TEMPLATE_DIR="/usr/local/hestia/data/templates/web/nginx"
BIN_DIR="/usr/local/hestia/bin"
WEB_INC_DIR="/usr/local/hestia/web/inc/nodejs"
WEB_TEMPLATE_INCLUDE_DIR="/usr/local/hestia/web/templates/includes/nodejs"
EDIT_WEB_CONTROLLER="/usr/local/hestia/web/edit/web/index.php"
EDIT_WEB_TEMPLATE="/usr/local/hestia/web/templates/pages/edit_web.php"
DELETE_WEB_CONTROLLER="/usr/local/hestia/web/delete/web/index.php"
BACKUP_ROOT="/usr/local/hestia/install/backups/hestiacp-nodejs-uninstall"
TIMESTAMP="$(date +%Y%m%d%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$TIMESTAMP"
BACKUP_CREATED='no'
REMOVE_TEMPLATES='no'

print_banner() {
	echo "HestiaCP Node.js"
	echo "maintained fork by Poisl"
	echo "──────────────────────────────"
}

ensure_backup_dir() {
	if [ "$BACKUP_CREATED" = 'no' ]; then
		sudo mkdir -p "$BACKUP_DIR"
		BACKUP_CREATED='yes'
	fi
}

backup_path() {
	local path=$1
	local backup_path="$BACKUP_DIR${path}"

	ensure_backup_dir
	sudo mkdir -p "$(dirname "$backup_path")"
	sudo cp -a "$path" "$backup_path"
	echo -e "${START} Backed up ${path} -> ${backup_path}"
}

remove_path() {
	local path=$1
	local label=$2

	if sudo test -e "$path"; then
		backup_path "$path"
		sudo rm -rf "$path"
		echo -e "${START} Removed ${label}"
	else
		echo -e "${START} ${label} already absent"
	fi
}

unpatch_core_file() {
	local file=$1
	local marker=$2
	local pattern=$3

	if ! sudo grep -Fq "$marker" "$file"; then
		echo -e "${START} No core patch present in ${file}"
		return
	fi

	backup_path "$file"
	sudo php -r '
		$file = $argv[1];
		$pattern = $argv[2];
		$contents = file_get_contents($file);
		if ($contents === false) {
			fwrite(STDERR, "Unable to read $file\n");
			exit(1);
		}
		$newContents = preg_replace($pattern, "", $contents, 1, $count);
		if ($count !== 1) {
			fwrite(STDERR, "Patch marker not removed from $file\n");
			exit(2);
		}
		file_put_contents($file, $newContents);
	' "$file" "$pattern"
	echo -e "${START} Removed core patch from ${file}"
}

parse_args() {
	for arg in "$@"; do
		case "$arg" in
			--remove-templates)
				REMOVE_TEMPLATES='yes'
				;;
			--help)
				echo "Usage: ./uninstall.sh [--remove-templates]"
				echo "  Default: remove plugin installer and backend commands, keep nginx templates so existing apps keep working."
				echo "  --remove-templates: also remove NodeJS nginx templates; may break domains still using them."
				exit 0
				;;
			*)
				echo -e "${START} Unknown option: ${arg}"
				exit 1
				;;
		esac
	done
}

main() {
	print_banner
	parse_args "$@"

	remove_path "$INSTALLER_DIR" 'Node.js WebApp installer'
	remove_path "$WEB_INC_DIR/delete_web_controller.php" 'panel delete_web_controller.php'
	remove_path "$WEB_INC_DIR/edit_web_controller.php" 'panel edit_web_controller.php'
	remove_path "$WEB_TEMPLATE_INCLUDE_DIR/edit_web_controls.php" 'panel edit_web_controls.php'
	remove_path "$BIN_DIR/nodejs-app-common" 'nodejs-app-common command helper'
	remove_path "$BIN_DIR/v-add-nodejs-app" 'v-add-nodejs-app'
	remove_path "$BIN_DIR/v-start-nodejs-app" 'v-start-nodejs-app'
	remove_path "$BIN_DIR/v-stop-nodejs-app" 'v-stop-nodejs-app'
	remove_path "$BIN_DIR/v-restart-nodejs-app" 'v-restart-nodejs-app'
	remove_path "$BIN_DIR/v-get-nodejs-app-status" 'v-get-nodejs-app-status'
	remove_path "$BIN_DIR/v-get-nodejs-app-log" 'v-get-nodejs-app-log'
	remove_path "$BIN_DIR/v-delete-nodejs-app" 'v-delete-nodejs-app'
	remove_path "$BIN_DIR/v-add-pm2-app" 'v-add-pm2-app compatibility wrapper'

	unpatch_core_file "$EDIT_WEB_CONTROLLER" '// HESTIA NODEJS PANEL START' '/\n?\/\/ HESTIA NODEJS PANEL START\nrequire_once \$_SERVER\["DOCUMENT_ROOT"\] \. "\/inc\/nodejs\/edit_web_controller\.php";\n\/\/ HESTIA NODEJS PANEL END\n?/'
	unpatch_core_file "$EDIT_WEB_TEMPLATE" '<!-- HESTIA NODEJS PANEL START -->' '/\n?<!-- HESTIA NODEJS PANEL START -->\n<\?php require \$_SERVER\["DOCUMENT_ROOT"\] \. "\/templates\/includes\/nodejs\/edit_web_controls\.php"; \?>\n<!-- HESTIA NODEJS PANEL END -->\n?/'
	unpatch_core_file "$DELETE_WEB_CONTROLLER" '// HESTIA NODEJS DELETE START' '/\n?\/\/ HESTIA NODEJS DELETE START\nrequire_once \$_SERVER\["DOCUMENT_ROOT"\] \. "\/inc\/nodejs\/delete_web_controller\.php";\n\/\/ HESTIA NODEJS DELETE END\n?/'

	if [ "$REMOVE_TEMPLATES" = 'yes' ]; then
		remove_path "$TEMPLATE_DIR/NodeJS.tpl" 'NodeJS.tpl'
		remove_path "$TEMPLATE_DIR/NodeJS.stpl" 'NodeJS.stpl'
	else
		echo -e "${START} Preserving NodeJS nginx templates so existing domains keep working"
	fi

	if [ "$BACKUP_CREATED" = 'yes' ]; then
		echo -e "${START} Backups stored in ${BACKUP_DIR}"
	else
		echo -e "${START} Nothing was removed"
	fi
}

main "$@"
