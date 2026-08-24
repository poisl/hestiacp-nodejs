#!/bin/bash

set -euo pipefail

BLUE="\e[34m"
GREEN="\e[32m"
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
BACKUP_ROOT="/usr/local/hestia/install/backups/hestiacp-nodejs"
TIMESTAMP="$(date +%Y%m%d%H%M%S)"
BACKUP_DIR="$BACKUP_ROOT/$TIMESTAMP"
BACKUP_CREATED='no'

print_banner() {
	echo "HestiaCP Node.js"
	echo "maintained fork by Poisl"
	echo "──────────────────────────────"
}

require_dir() {
	local path=$1
	if ! sudo test -d "$path"; then
		echo -e "${START} Missing required directory: $path"
		exit 1
	fi
}

ensure_backup_dir() {
	if [ "$BACKUP_CREATED" = 'no' ]; then
		sudo mkdir -p "$BACKUP_DIR"
		BACKUP_CREATED='yes'
	fi
}

backup_file() {
	local destination=$1
	local backup_path="$BACKUP_DIR${destination}"

	ensure_backup_dir
	sudo mkdir -p "$(dirname "$backup_path")"
	sudo cp -a "$destination" "$backup_path"
	echo -e "${START} Backed up ${destination} -> ${backup_path}"
}

install_managed_file() {
	local source=$1
	local destination=$2
	local mode=$3
	local label=$4

	if sudo test -f "$destination"; then
		if cmp -s "$source" <(sudo cat "$destination"); then
			echo -e "${START} ${label} unchanged"
			sudo chmod "$mode" "$destination"
			return
		fi

		backup_file "$destination"
		echo -e "${START} Updating ${label}"
	else
		echo -e "${START} Installing ${label}"
	fi

	sudo install -D -m "$mode" "$source" "$destination"
}

patch_core_file() {
	local target=$1
	local marker=$2
	local anchor=$3
	local snippet=$4
	local mode=${5:-after}

	if sudo grep -Fq "$marker" "$target"; then
		echo -e "${START} Core patch already present in ${target}"
		return
	fi

	backup_file "$target"
	sudo php -r '
		$file = $argv[1];
		$marker = $argv[2];
		$anchor = $argv[3];
		$snippet = $argv[4];
		$mode = $argv[5];
		$contents = file_get_contents($file);
		if ($contents === false) {
			fwrite(STDERR, "Unable to read $file\n");
			exit(1);
		}
		if (strpos($contents, $marker) !== false) {
			exit(0);
		}
		$replacement = $mode === "before"
			? $snippet . PHP_EOL . $anchor
			: $anchor . PHP_EOL . $snippet;
		$newContents = str_replace($anchor, $replacement, $contents, $count);
		if ($count !== 1) {
			fwrite(STDERR, "Anchor not found exactly once in $file\n");
			exit(2);
		}
		file_put_contents($file, $newContents);
	' "$target" "$marker" "$anchor" "$snippet" "$mode"
	echo -e "${START} Patched ${target}"
}

patch_panel_core_files() {
	local controller_marker="// HESTIA NODEJS PANEL START"
	local template_marker="<!-- HESTIA NODEJS PANEL START -->"
	local controller_snippet
	local template_snippet

	controller_snippet=$(cat <<'EOF'
// HESTIA NODEJS PANEL START
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/nodejs/edit_web_controller.php";
// HESTIA NODEJS PANEL END
EOF
)
	template_snippet=$(cat <<'EOF'
<!-- HESTIA NODEJS PANEL START -->
<?php require $_SERVER["DOCUMENT_ROOT"] . "/templates/includes/nodejs/edit_web_controls.php"; ?>
<!-- HESTIA NODEJS PANEL END -->
EOF
)

	patch_core_file "$EDIT_WEB_CONTROLLER" "$controller_marker" "// Check POST request" "$controller_snippet" before
	patch_core_file "$EDIT_WEB_TEMPLATE" "$template_marker" "<?php show_alert_message(\$_SESSION); ?>" "$template_snippet" after

	local delete_controller_marker="// HESTIA NODEJS DELETE START"
	local delete_controller_snippet
	delete_controller_snippet=$(cat <<'EOF'
// HESTIA NODEJS DELETE START
require_once $_SERVER["DOCUMENT_ROOT"] . "/inc/nodejs/delete_web_controller.php";
// HESTIA NODEJS DELETE END
EOF
)
	patch_core_file "$DELETE_WEB_CONTROLLER" "$delete_controller_marker" "if (!empty(\$_GET[\"domain\"])) {" "$delete_controller_snippet" before
}

install_files() {
	install_managed_file "quickinstall-app/NodeJs/NodeJsSetup.php" "$INSTALLER_DIR/NodeJsSetup.php" 644 'NodeJsSetup.php'
	install_managed_file "quickinstall-app/NodeJs/NodeJsUtils/NodeJsPaths.php" "$INSTALLER_DIR/NodeJsUtils/NodeJsPaths.php" 644 'NodeJsPaths.php'
	install_managed_file "quickinstall-app/NodeJs/NodeJsUtils/NodeJsUtil.php" "$INSTALLER_DIR/NodeJsUtils/NodeJsUtil.php" 644 'NodeJsUtil.php'
	install_managed_file "quickinstall-app/NodeJs/templates/web/entrypoint.tpl" "$INSTALLER_DIR/templates/web/entrypoint.tpl" 644 'entrypoint.tpl'
	install_managed_file "quickinstall-app/NodeJs/templates/nginx/nodejs-app.tpl" "$INSTALLER_DIR/templates/nginx/nodejs-app.tpl" 644 'nodejs-app.tpl'
	install_managed_file "quickinstall-app/NodeJs/templates/nginx/nodejs-app-fallback.tpl" "$INSTALLER_DIR/templates/nginx/nodejs-app-fallback.tpl" 644 'nodejs-app-fallback.tpl'
	install_managed_file "quickinstall-app/NodeJs/nodejs.png" "$INSTALLER_DIR/nodejs.png" 644 'nodejs.png'

	install_managed_file "templates/NodeJS.tpl" "$TEMPLATE_DIR/NodeJS.tpl" 644 'NodeJS.tpl'
	install_managed_file "templates/NodeJS.stpl" "$TEMPLATE_DIR/NodeJS.stpl" 644 'NodeJS.stpl'

	install_managed_file "bin/nodejs-app-common" "$BIN_DIR/nodejs-app-common" 755 'nodejs-app-common'
	install_managed_file "bin/v-add-nodejs-app" "$BIN_DIR/v-add-nodejs-app" 755 'v-add-nodejs-app'
	install_managed_file "bin/v-start-nodejs-app" "$BIN_DIR/v-start-nodejs-app" 755 'v-start-nodejs-app'
	install_managed_file "bin/v-stop-nodejs-app" "$BIN_DIR/v-stop-nodejs-app" 755 'v-stop-nodejs-app'
	install_managed_file "bin/v-restart-nodejs-app" "$BIN_DIR/v-restart-nodejs-app" 755 'v-restart-nodejs-app'
	install_managed_file "bin/v-get-nodejs-app-status" "$BIN_DIR/v-get-nodejs-app-status" 755 'v-get-nodejs-app-status'
	install_managed_file "bin/v-get-nodejs-app-log" "$BIN_DIR/v-get-nodejs-app-log" 755 'v-get-nodejs-app-log'
	install_managed_file "bin/v-delete-nodejs-app" "$BIN_DIR/v-delete-nodejs-app" 755 'v-delete-nodejs-app'
	install_managed_file "bin/v-add-pm2-app" "$BIN_DIR/v-add-pm2-app" 755 'v-add-pm2-app compatibility wrapper'

	install_managed_file "panel/edit_web_controller.php" "$WEB_INC_DIR/edit_web_controller.php" 644 'panel edit_web_controller.php'
	install_managed_file "panel/edit_web_controls.php" "$WEB_TEMPLATE_INCLUDE_DIR/edit_web_controls.php" 644 'panel edit_web_controls.php'
	install_managed_file "panel/delete_web_controller.php" "$WEB_INC_DIR/delete_web_controller.php" 644 'panel delete_web_controller.php'
	patch_panel_core_files
}

main() {
	print_banner
	require_dir "/usr/local/hestia/web/src/app/WebApp/Installers"
	require_dir "/usr/local/hestia/data/templates/web/nginx"
	require_dir "/usr/local/hestia/bin"
	require_dir "/usr/local/hestia/web/edit/web"
	require_dir "/usr/local/hestia/web/delete/web"
	require_dir "/usr/local/hestia/web/templates/pages"
	install_files

	if [ "$BACKUP_CREATED" = 'yes' ]; then
		echo -e "${START} Backups stored in ${BACKUP_DIR}"
	else
		echo -e "${START} No backups were needed"
	fi

	echo -e "${START} Installation completed"
}

main "$@"
