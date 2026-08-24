# HestiaCP add multiple NodeJS apps using QuickApp Installer.

This project is a maintained fork of `JLFdzDev/hestiacp-nodejs`.

This project is an independent community-maintained extension for HestiaCP and is not affiliated with or endorsed by the HestiaCP project.

You can add multiple websites to your HestiaCP using diferent ports to each one.

## Current features

- HestiaCP 1.10.3+ compatible Node.js Quick Install App
- automatic `NodeJS` nginx proxy template assignment during install
- per-domain Node.js metadata stored under the domain `private/` tree
- PM2-managed applications running as the owning Hestia user
- panel controls on **Edit Web Domain** for:
  - status
  - start
  - stop
  - restart
  - bounded log viewing
- backend commands for:
  - add
  - delete integration
  - start
  - stop
  - restart
  - status
  - log retrieval
- automatic Node.js integration cleanup during normal Hestia web-domain deletion

When you create create the app with the installer it automatically create:
* **/home/%USER%/%DOMAIN%/private/nodeapp** directory
* Config for **nginx** to use the selected port
* **ecosystem.config.js** with the necessary command to connect **pm2** and run your app Ex. `npm run start`
* **.nvmrc** file with node version if you use NVM

## How to install

1. Install Node: (Using one of these options)
   *  [NodeJS](https://github.com/nodesource/distributions)
   *  [NVM](https://github.com/nvm-sh/nvm#installing-and-updating)
2. Install [PM2](https://pm2.keymetrics.io/)
3. Clone this repository:
	```bash
	cd ~/tmp
	git clone https://github.com/Poisl/hestiacp-nodejs.git
	cd hestiacp-nodejs
	```

4. Use **install.sh**
	```bash
	sudo chmod 755 install.sh
	sudo ./install.sh
	```

5. 🚀 You are ready to install an App!!!

## How to use

1. Create new **user** (If you have one no need to create)
2. User needs bash access for app to work, go to **User edit** > **advanced options** > **SSH Access** > **bash**
3. **Add** new web (Ex. acme.com)
4. Upload your app with filemanager, clone with git, or otherwise place it in `/home/<user>/web/<domain.com>/private/nodeapp`
5. Go to **edit** this web and open **Quick Install App**
6. Select **NodeJS**
   * **Node Version**: If you manage node with nvm, it put a `.nvmrc file in root of nodeapp with selected version. If you installed node without nvm you can remove this file.

   * **Start Script**: It creates a `ecosystem.config.js` file in root of nodeapp with the script that you fill (it should be the one you have in your `package.json`) for PM2 can manage the app.

   * **Port**: You can manage multiple apps with different ports, put different port for each app you have (Ex. 3000).
   It creates `.env` file in root of nodeapp with the selected port, if your app don't use this `.env` file you can remove.

   * **PHP Version**: This is only for HestiaCP you can put any value (**NOT IMPORTANT**)
7. The installer applies the `NodeJS` proxy template automatically.
8. After installation, open **Edit Web Domain** to see the Node.js application status and the **Start / Stop / Restart** controls, plus **View logs**.
9. 🎉 Congratulations you're done!!!

## Migration from the original forked plugin

If you are migrating from the older `JLFdzDev/hestiacp-nodejs` install or an earlier revision of this fork:

1. Back up your current Node.js applications and Hestia configuration.
2. Check whether your domains currently use the `NodeJS` proxy template.
3. Install this repository with the current `install.sh`.
4. For each existing Node.js domain, open **Quick Install App**, select **NodeJS**, and save the current settings again.
5. Confirm the domain now has generated config under `web/<domain>/private/hestiacp_nodejs_config`.
6. Confirm the app appears on **Edit Web Domain** with Node.js status and controls.
7. If the old plugin left config under `/home/<user>/hestiacp_nodejs_config`, keep it until you have verified the new domain-scoped config is working, then remove the old leftover directory manually.
8. If an old PM2 process exists, stop it first or use the new panel/backend controls so the app is recreated with the current deterministic PM2 naming and app-local `.pm2` state.

Recommended migration check list:

- the app responds through nginx after reinstalling the Quick Install App
- **Edit Web Domain** shows the Node.js panel block
- **Start / Stop / Restart** work from the panel
- `v-get-nodejs-app-status <user> <domain> json` returns the expected port and PM2 name
- logs are visible from **View logs** or `v-get-nodejs-app-log`

## FAQ

### How to change the port if i have a web running

Re-run the Quick Install App for the domain with the new port. The installer updates the Node.js config and reapplies the `NodeJS` proxy template automatically.

### How to stop managing an installed Node.js app

Use the backend command:

```bash
sudo /usr/local/hestia/bin/v-delete-nodejs-app <user> <domain>
```

This removes the plugin-managed integration for the domain:
- removes the PM2-managed process
- restores the default proxy template
- removes the generated Node.js config directory

It does not delete the application code in `private/nodeapp`.

### I want to remove the domain

Remove it normally through Hestia. The plugin now cleans up Node.js integration automatically during web-domain deletion.

## Uninstall plugin

To remove the plugin files while keeping existing Node.js sites operational:

```bash
sudo ./uninstall.sh
```

This removes the WebApp installer and backend commands, but keeps the `NodeJS` nginx templates in place.

To also remove the nginx templates:

```bash
sudo ./uninstall.sh --remove-templates
```

Only use `--remove-templates` if no domains still depend on the `NodeJS` proxy template.

## Logs

The panel shows a **View logs** action on the web domain edit page for managed Node.js apps.

You can also fetch logs from the command line:

```bash
sudo /usr/local/hestia/bin/v-get-nodejs-app-log <user> <domain> [out|err] [1-200]
```

Examples:

```bash
sudo /usr/local/hestia/bin/v-get-nodejs-app-log demo example.com out 100
sudo /usr/local/hestia/bin/v-get-nodejs-app-log demo example.com err 50
```
