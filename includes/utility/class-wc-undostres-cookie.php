<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/**
 * COOKIE SETTER ON WORDPRESS INIT
 *
 * Sets and refresh cookie based on url parameter, also add stylesheet to block ads.
 */
class WC_UDT_COOKIE
{
    public function __construct()
    {
        add_action('init', [$this, 'check_ref_udt']);
    }

    public function check_ref_udt()
    {
        $time = (30 * 86400);
        if (!isset($_COOKIE["UDT"]) || ($_COOKIE["UDT"] == 'notUDT')) {
            setcookie("UDT", isset($_GET['udtref']) ? 'isUDT' : 'notUDT', time() + ($time), "/");
            setcookie("UDTUSER", $_GET['udtref'] ?? '', time() + ($time), "/");
        } else {
            setcookie("UDT", 'isUDT', time() + ($time), "/");
            setcookie("UDTUSER", $_COOKIE["UDTUSER"], time() + ($time), "/");
        }
        if ($_COOKIE["UDT"] == 'isUDT') wp_enqueue_style('hide-payments-adds', WC_UDT_PLUGIN_URL . '/assets/css/styles.css', false, WC_UDT_VER);
    }
}
