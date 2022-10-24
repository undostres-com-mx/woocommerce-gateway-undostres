<?php

/**
 * Plugin Name: Gateway UnDosTres
 * Plugin URI: https://undostres.com.mx/
 * Description: Receive payments using UnDosTres payments provider.
 * Author: UnDosTres
 * Author URI: https://github.com/undostres-com-mx/woocommerce-gateway-undostres
 * Version: 1.0.0
 * Requires at least: 6.0.0
 * Tested up to: 6.0.3
 * WC requires at least: 6.5.0
 * WC tested up to: 7.0.0
 * Text Domain: gateway-undostres
 */

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/** MISCELLANEOUS **/
const WC_UDT_VER = '1.0.0';
const WC_UDT_MIN_WC_VER = '6.5';
const WC_UDT_PAYMENT_ID = 'UnDosTres';
define('WC_UDT_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
define('WC_UDT_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WC_UDT_SITE_URL', get_site_url());
require_once(WC_UDT_PLUGIN_PATH . '/includes/utility/class-wc-undostres-cookie.php');
require_once(WC_UDT_PLUGIN_PATH . '/includes/utility/class-wc-undostres-utility.php');

/**
 * PLUGIN INIT AS SINGLETON
 */
function woocommerce_gateway_undostres()
{
    static $plugin;
    if (!isset($plugin)) {
        class WC_UDT
        {
            /** THE SINGLETON OF THE CLASS **/
            private static $instance = null;

            /** RETURNS SINGLETON **/
            public static function get_instance(): WC_UDT
            {
                if (self::$instance === null) self::$instance = new self();
                return self::$instance;
            }

            /** UDT GATEWAY INSTANCE **/
            protected $undostres_gateway = null;

            /** OVERRIDE CLONE METHOD TO PREVENT CLASS CLONE **/
            public function __clone()
            {
            }

            /** OVERRIDE WAKEUP METHOD TO PREVENT UNSERIALIZE **/
            public function __wakeup()
            {
            }

            /** OVERRIDE WAKEUP METHOD TO PREVENT SERIALIZE **/
            public function __sleep()
            {
            }

            /** CONSTRUCTOR PROTECTION TO PREVENT CREATING INSTANCE OF THIS CLASS OUTSIDE THE CLASS **/
            public function __construct()
            {
                $this->init();
                add_action('rest_api_init', [$this, 'register_routes']);
            }

            /** INITIALIZATION OF PLUGIN **/
            public function init()
            {
                require_once(WC_UDT_PLUGIN_PATH . '/includes/utility/class-wc-undostres-logger.php');
                require_once(WC_UDT_PLUGIN_PATH . '/includes/class-wc-gateway-undostres.php');
                require_once(__DIR__ . '/vendor/autoload.php');
                $this->undostres_gateway = new UnDosTres();
                add_filter('woocommerce_payment_gateways', [$this, 'add_gateway']);
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);
            }

            /** REGISTER REST API ROUTES **/
            public function register_routes()
            {
                require_once(WC_UDT_PLUGIN_PATH . '/includes/endpoints/class-wc-undostres-callback.php');
                require_once(WC_UDT_PLUGIN_PATH . '/includes/endpoints/class-wc-undostres-redirect.php');
                require_once(WC_UDT_PLUGIN_PATH . '/includes/endpoints/class-wc-undostres-status.php');
                $callback = new WC_REST_UDT_CALLBACK_CONTROLLER($this->get_gateway());
                $redirect = new WC_REST_UDT_REDIRECT_CONTROLLER($this->get_gateway());
                $status = new WC_REST_UDT_STATUS_CONTROLLER($this->get_gateway());
                $callback->register_routes();
                $redirect->register_routes();
                $status->register_routes();
            }

            /** ADD UNDOSTRES AS PAYMENT GATEWAY **/
            public function add_gateway($methods)
            {
                $methods[] = UnDosTres::class;
                return $methods;
            }

            /** PLUGIN ACTION LINKS **/
            public function plugin_action_links($links): array
            {
                $settings_url = add_query_arg(['page' => 'wc-settings', 'tab' => 'checkout', 'section' => 'undostres'], admin_url('admin.php'));
                $link = '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'woocommerce-gateway-undostres') . '</a>';
                return array_merge([$link], $links);
            }

            /** RETURNS THE MAIN UDT PAYMENT GATEWAY **/
            public function get_gateway()
            {
                return $this->undostres_gateway;
            }
        }

        $plugin = WC_UDT::get_instance();
    }
}

/**
 * PLUGIN VERIFIER AND LOADER
 */
add_action('plugins_loaded', 'woocommerce_undostres_init');
function woocommerce_undostres_init()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            show_udt_alert('UnDosTres necesita a WooCommerce instalado y activo.', WC_UDT_TMessage::Error);
        });
        return;
    }
    if (version_compare(WC_VERSION, WC_UDT_MIN_WC_VER, '<')) {
        add_action('admin_notices', function () {
            show_udt_alert('UnDosTres necesita mínimo la versión ' . WC_UDT_MIN_WC_VER . ' de WooCommerce.', WC_UDT_TMessage::Error);
        });
        return;
    }
    if (get_woocommerce_currency() !== 'MXN') {
        add_action('admin_notices', function () {
            show_udt_alert('UnDosTres solo funciona si la tienda está configurada en MXN.', WC_UDT_TMessage::Error);
        });
        return;
    }
    if (!isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https')) {
        add_action('admin_notices', function () {
            show_udt_alert('UnDosTres necesita una conexión HTTPS para funcionar.', WC_UDT_TMessage::Error);
        });
        return;
    }
    woocommerce_gateway_undostres();
}

new WC_UDT_COOKIE();
