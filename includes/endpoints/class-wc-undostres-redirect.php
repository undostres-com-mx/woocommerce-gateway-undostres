<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/**
 * REST CONTROLLER FOR REDIRECTION
 *
 * Validates order.
 * Used to redirect and order to finish page, shop page or cart page.
 */
class WC_REST_UDT_REDIRECT_CONTROLLER extends WP_REST_Controller
{
    private $gateway;

    public function __construct(UnDosTres $gateway)
    {
        $this->gateway = $gateway;
    }

    public function register_routes()
    {
        register_rest_route(
            'udt',
            'redirect',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'redirect'],
            ]
        );
    }

    public function redirect($data)
    {
        $orderId = $data->get_param('orderId');
        $this->gateway->log(sprintf("%s -> Redirect de la orden: %s", __METHOD__, $orderId ?? 'NULL'));
        if (wp_redirect($this->gateway->get_redirect_url($orderId))) exit;
    }
}
