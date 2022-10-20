<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

/**
 * REST CONTROLLER FOR STATUS MANAGEMENT
 *
 * Validates order.
 * Used to make an abstraction of the order status.
 */
class WC_REST_UDT_STATUS_CONTROLLER extends WP_REST_Controller
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
            'status',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'status'],
            ]
        );
    }

    public function status($data): WP_REST_Response
    {
        try {
            $order_id = $data->get_param('orderId');
            $order = $this->gateway->get_order($order_id);
            if (!$this->gateway->are_valid_headers($data)) throw new Exception('Autenticación errónea.');
            if ($order_id === null) throw new Exception('Datos recibidos incorrectos.');
            $this->gateway->log(sprintf("%s -> Se consultó status de la orden: %s", __METHOD__, $order_id));
            if ($order === null) $response = ['code' => 404, 'message' => 'Orden no encontrada.'];
            else if (!$this->gateway->is_udt_order($order)) $response = ['code' => 500, 'message' => 'Orden no creada por UnDosTres.'];
            else $response = ['code' => 200, 'message' => 'Ok.', 'status' => $order->get_status()];
            return new WP_REST_Response($response);
        } catch (Exception $e) {
            return new WP_REST_Response(['code' => 500, 'message' => $e->getMessage()]);
        }
    }
}
