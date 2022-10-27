<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/** ENUM FOR FRONT-END ALERTS **/
abstract class WC_UDT_TMessage
{
    const Success = 0;
    const Info = 1;
    const Warning = 2;
    const Error = 3;
}

/** NOTIFICATION FOR ADMIN PANEL **/
function show_udt_alert(string $msg, int $type, bool $isDismissible = false): void
{
    $class = '';
    if ($type === WC_UDT_TMessage::Success) $class = 'notice notice-success';
    else if ($type === WC_UDT_TMessage::Info) $class = 'notice notice-info';
    else if ($type === WC_UDT_TMessage::Warning) $class = 'notice notice-warning';
    else if ($type === WC_UDT_TMessage::Error) $class = 'notice notice-error';
    echo '<div class="' . esc_attr($class) . ($isDismissible ? esc_attr(' is-dismissible') : '') . '"><p><strong>' . esc_html($msg) . '</strong></p></div>';
}
