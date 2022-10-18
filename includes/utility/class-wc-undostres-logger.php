<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/**
 * LOGGER INTERFACE
 *
 * Logs into file through woocommerce logger.
 */
class WC_UDT_LOGGER
{
    public static $logger;

    public static function log($message, bool $encode = false)
    {
        if (!class_exists('WC_Logger')) return;
        if (empty(self::$logger)) self::$logger = wc_get_logger();
        if ($encode) $message = json_encode($message);
        $log_entry  = "\n" . '=============== UDT LOG ===============' . "\n" . $message . "\n" .  '=============== UDT END ===============' . "\n\n";
        self::$logger->debug($log_entry, ['source' => WC_UDT_PAYMENT_ID]);
    }
}
