# UnDosTres Payment Module For WooCommerce

## Plugin information

This plugin has been tested from WooCommerce 6.5 to 7.0 and WordPress from 5.9 to 6.0.3.

The composer.json have the dependencies of the plugin.

---

## Installation

To install the plugin:

- Go to `Plugins`.
- Click on `Add new` at the top of the page.
- Click on `Upload plugin` at the top of the page.
- Upload a plugin.
- Click on `Install now` at the side of uploader.
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

To configure the plugin do as following:

- Go to `Plugins`.
- Click on `Settings` under `WooCommerce UnDosTres Gateway`.
- Set the parameters then click the `Save` button.

---

## Logs

The logging system logs into a file, WooCommerce let you can see the logs on:

Go to `WooCommerce > Status > Logs`.

There you will find all the UnDosTres logs.

---

## Debugging with VsCode

The file (*launch.json*) already have the configuration to debug.

### Server

- It's needed to configure xdebug (*php-fpm*) to use 9009 port.

- It's needed to do ssh tunneling:
    - Activate ssh gateway support, edit the following on file `/etc/ssh/sshd_config` set `GatewayPorts yes`.
    - Restart ssh doing: `sudo service sshd restart`

- It's needed to increase nginx response time:

```
proxy_connect_timeout       600;
proxy_send_timeout          600;
proxy_read_timeout          600;
send_timeout                600;
```

### Local

To start de the debug its needed the following extensions on the local machine:

- PHP Debug, on VsCode.
- Xdebug helper, on web browser.

Do ssh tunneling:

```
ssh -i key.pem user@0.0.0.0 -N -R 9009:localhost:9009 -v
```

On the page click on the extension icon and select debug option.

---

## GitHub and Wiki

https://github.com/undostres-com-mx/woocommerce-gateway-undostres

---

## License

This is proprietary software of UnDosTres.
