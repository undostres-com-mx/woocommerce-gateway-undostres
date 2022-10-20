# UnDosTres Payment Module For WooCommerce

## Plugin information

This plugin has been tested from WooCommerce 6.5 to 7.0 and WordPress from 5.9 to 6.0.3.

The plugin's dependencies are declared in the composer.json file.

---

## Installation

To install the plugin:

- Go to `Plugins`.
- Click on `Add new` at the top of the page.
- Click on `Upload plugin` at the top of the page.
- Upload the plugin.
- Click on `Install now` at the side of the uploader.
- Click on `Activate plugin`.

---

## Update

To update it's only necessary to do the installation process again.

---

## Uninstall

To uninstall the plugin:

- Go to `Plugins`.
- Click on `Deactivate` under `WooCommerce UnDosTres Gateway`.
- Click on `Delete` under `WooCommerce UnDosTres Gateway`.
- Accept the alert.

---

## Configuration

To configure the plugin do the following:

- Go to `Plugins`.
- Click on `Settings` under `WooCommerce UnDosTres Gateway`.
- Set the parameters then click the `Save` button.

---

## Logs

The logging system writes to a file at $WORDPRESS_ROOT/wp-content/uploads/wc-logs. The file's name follows the format:

`UnDosTres-YYYY-MM-DD-\[random-string\]`

WooCommerce also lets you see the logs in the admin panel, by going to `WooCommerce > Status > Logs`.

---

## Debugging with VsCode

The debugging configuration can be found in the *launch.json* file.

### Server

- launch.json specifies port 9009 for debugging with Xdebug (*php-fpm*).

- Activate ssh tunneling:
    - On the server, set `GatewayPorts yes` in the sshd configuration file (`/etc/ssh/sshd_config`).
    - Restart the ssh server: `sudo service sshd restart` or `sudo systemctl restart sshd`

- Increase nginx response time by adding the following lines to your site's section in the `sites-enabled` configuration file:

```
proxy_connect_timeout       600;
proxy_send_timeout          600;
proxy_read_timeout          600;
send_timeout                600;
```

### Local

Install the following extensions in the local machine:

- PHP Debug, in VsCode.
- Xdebug helper, in the web browser.

Do ssh tunneling:

```
ssh user@server -N -R 9009:localhost:9009 -v
```

Click on the extension icon in the browser and select the debug option.

---

## GitHub and Wiki

https://github.com/undostres-com-mx/woocommerce-gateway-undostres

---

## License

This is proprietary software of UnDosTres.
