<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/**
 * REST CONTROLLER FOR CALLBACK
 *
 * Validates order.
 * Used to set the order status and notes as needed from UnDosTres payment page.
 */
class WC_REST_UDT_CALLBACK_CONTROLLER extends WP_REST_Controller
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
            'callback',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'callback'],
            ]
        );
    }

    public function callback($data): WP_REST_Response
    {
        try {
            $orderId = $data->get_param('paymentId');
            $status = $data->get_param('status');
            if (!$this->gateway->are_valid_headers($data)) throw new Exception('Autenticación erronea.');
            if ($orderId === null || $status == null) throw new Exception('Datos recibidos incorrectos.');
            $this->gateway->log(sprintf("%s -> Callback de la orden: %s, con el estatus: %s", __METHOD__, $orderId, $status));
            $response = $this->gateway->process_order($orderId, $status);
            return new WP_REST_Response($response);
        } catch (Exception $e) {
            $this->gateway->log(sprintf("%s -> Excepción: %s", __METHOD__, $e->getMessage()));
            return new WP_REST_Response(['code' => 500, 'message' => $e->getMessage()]);
        }
    }
}
