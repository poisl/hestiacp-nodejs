# Changelog

All notable changes to this project will be documented in this file.

## 1.2.2

### Added

- Update instructions in `README.md` for upgrading existing plugin installations.

### Changed

- Improved Node.js uptime formatting to a short human-readable form such as `45s`, `12m 10s`, `3h 12m`, or `2d 4h`.

### Fixed

- Fixed panel log viewing so the **View logs** action no longer falls through to the normal web-domain save handler.
- Added automatic Node.js integration cleanup to the Hestia web-domain delete flow.
- Added panel log viewing support backed by bounded `v-get-nodejs-app-log` retrieval.

## 1.2.0

### Added

- HestiaCP 1.10.3+ compatible WebApp installer support for Node.js applications.
- Dedicated backend commands for Node.js app lifecycle management:
  - `v-add-nodejs-app`
  - `v-delete-nodejs-app`
  - `v-start-nodejs-app`
  - `v-stop-nodejs-app`
  - `v-restart-nodejs-app`
  - `v-get-nodejs-app-status`
  - `v-get-nodejs-app-log`
- Hestia panel integration on the web-domain edit page for:
  - status
  - start
  - stop
  - restart
  - log viewing
- Automatic Node.js cleanup during normal Hestia web-domain deletion.
- `uninstall.sh` for reversible plugin removal.
- Backup-aware, rerunnable installation behavior in `install.sh`.

### Changed

- Migrated the Node.js installer to the current Hestia WebApp API.
- Moved plugin-generated per-domain config into the writable domain `private/` tree.
- Switched PM2 state storage from the user home root to the app-local `.pm2` directory.
- Applied the `NodeJS` proxy template automatically during installation.
- Updated repository documentation to describe current installation, migration, logs, and uninstall flows.

### Fixed

- Fixed fatal loader failures caused by outdated `BaseSetup` namespace and installer contracts.
- Fixed malformed fallback nginx proxy configuration.
- Fixed PM2 startup under current Hestia home-directory permissions.
- Fixed restart/start handling for stopped and errored PM2-managed processes.

### Notes

- This project remains a maintained fork of `JLFdzDev/hestiacp-nodejs`.
- This project is an independent community-maintained extension for HestiaCP and is not affiliated with or endorsed by the HestiaCP project.
