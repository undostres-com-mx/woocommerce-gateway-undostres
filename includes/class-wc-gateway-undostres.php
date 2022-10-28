<?php

/** EXIT IF ACCESSED DIRECTLY **/
if (!defined('ABSPATH')) exit;

use UDT\SDK\SASDK;

/**
 * THE GATEWAY ITSELF ~.~
 */
class UnDosTres extends WC_Payment_Gateway
{
    private $response_url;
    private $return_url;

    /** CONSTRUCTOR - INIT THE PLUGIN CONFIGURATION **/
    public function __construct()
    {
        /** WC DATA **/
        $this->id = WC_UDT_PAYMENT_ID;
        $this->title = WC_UDT_PAYMENT_ID;
        $this->method_title = WC_UDT_PAYMENT_ID;
        $this->method_description = 'Deja tu pago en las manos de UnDosTres.';
        $this->supports = ['products', 'refunds', 'add_payment_method'];
        $this->icon = WC_UDT_PLUGIN_URL . '/assets/images/icon.png';
        /** INITS **/
        $this->init_form_fields();
        $this->init_settings();
        /** COMMUNICATION URLS AND UTILITY **/
        $this->response_url = add_query_arg(['rest_route' => '/udt/callback'], WC_UDT_SITE_URL);
        $this->return_url = add_query_arg(['rest_route' => '/udt/redirect'], WC_UDT_SITE_URL);
        if (!SASDK::$isSet) {
            /** SDK **/
            SASDK::init($this->get_option('key'), $this->get_option('url') === '' ? null : $this->get_option('url'));
            /** HOOKS **/
            add_filter('woocommerce_available_payment_gateways', [$this, 'handle_gateways']);
            add_action('woocommerce_receipt_' . $this->id, [$this, 'udt_order_redirect']);
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            add_action('woocommerce_order_status_changed', [$this, 'order_status_changed'], 1, 3);
        }
    }

    /** OPTIONS DISPLAYED ON PLUGIN CONFIG **/
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Activa/Desactiva pago',
                'type' => 'checkbox',
                'description' => 'Esto establece si UnDosTres se muestra como método de pago.',
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'key' => [
                'title' => 'Llave secreta',
                'type' => 'password',
                'description' => 'Obtén tu llave con UnDosTres.',
                'default' => '',
                'desc_tip' => true,
            ],
            'url' => [
                'title' => 'Url personalizada',
                'type' => 'text',
                'description' => 'Servidor de peticiones personalizado, déjalo en blanco para apuntar a producción.',
                'default' => '',
                'desc_tip' => true,
            ],
            'logging' => [
                'title' => 'Activar registro',
                'type' => 'checkbox',
                'description' => 'Escribe logs en archivo de texto para poder ser visualizados en logs de WooCommerce.',
                'default' => 'yes',
                'desc_tip' => true,
            ]
        ];
    }

    /**
     * HANDLE HOW GATEWAYS ARE SHOWN
     *
     * Check cookie and show udt and hide others if isUDT, hide udt and show others if notUDT.
     */
    public function handle_gateways($available_gateways)
    {
        if ($this->get_option('enabled') === 'yes' && isset($_COOKIE["UDT"]) && $_COOKIE["UDT"] === 'isUDT') {
            foreach ($available_gateways as $key => $value)
                if ($key !== WC_UDT_PAYMENT_ID)
                    unset($available_gateways[$key]);
        } else if (isset($available_gateways[WC_UDT_PAYMENT_ID])) unset($available_gateways[WC_UDT_PAYMENT_ID]);
        return $available_gateways;
    }

    /**
     * LOG SYSTEM
     *
     * Check if system can log based on user preferences.
     */
    public function log($message)
    {
        if ($this->get_option('logging') === 'yes') WC_UDT_LOGGER::log($message);
    }

    /**
     * HEADERS VERIFICATION
     *
     * Validates if headers match with server authentication.
     * @throws Exception
     */
    public function are_valid_headers($data): bool
    {
        return SASDK::validateRequestHeaders($data->get_header('x-vtex-api-appkey'), $data->get_header('x-vtex-api-apptoken'));
    }

    /** GET ORDER FROM ORDERID **/
    public function get_order($order_id)
    {
        $order = wc_get_order($order_id);
        return $order !== false ? $order : null;
    }

    /** CHECK IF ORDER IS FROM UnDosTres **/
    public function is_udt_order($order): bool
    {
        return $order->get_payment_method() === WC_UDT_PAYMENT_ID;
    }

    /**
     * PROCESSING FOR CALLBACK API
     *
     * Change status of order depending on input.
     */
    public function process_order($order_id, $status): array
    {
        $order = $this->get_order($order_id);
        if ($order === null) return ['code' => 404, 'message' => 'Orden no encontrada.'];
        if (!$this->is_udt_order($order)) return ['code' => 500, 'message' => 'Orden no creada por UnDosTres.'];
        if ($order->get_status() !== 'pending') return ['code' => 500, 'message' => 'Estado previo inválido.'];
        switch ($status) {
            case 'approved':
                $order->payment_complete();
                $order->add_order_note('La orden se ha pagado con UnDosTres.');
                $response = ['code' => 200, 'message' => 'Orden pagada correctamente.'];
                break;
            case 'denied':
                $order->update_meta_data('_udt_user_cancel', true);
                $order->update_status('cancelled');
                $order->add_order_note('La orden fue cancelada por el usuario UnDosTres.');
                $response = ['code' => 200, 'message' => 'Orden cancelada correctamente.'];
                break;
            default:
                $response = ['code' => 500, 'message' => 'Nuevo estado incorrecto.',];
                break;
        }
        $response['paymentId'] = (string)$order_id;
        $response['status'] = $order->get_status();
        $order->save();
        return $response;
    }

    /**
     * GET REDIRECT URL
     *
     * If the order is not from UnDosTres, is invalid or has a status that is not processing or cancelled, redirect to shop
     * If the order is cancelled and from UnDosTres redirect to cart
     * If the order is processing and from UnDosTres redirect to finish payment
     */
    public function get_redirect_url($orderId)
    {
        $order = $this->get_order($orderId);
        if ($order !== null && $this->is_udt_order($order)) {
            if ($order->get_status() === 'processing') return apply_filters('woocommerce_get_return_url', $order->get_checkout_order_received_url(), $order);
            else if ($order->get_status() === 'cancelled') return $order->get_cancel_order_url();
        }
        return get_permalink(wc_get_page_id('shop'));
    }

    /**
     * CREATES THE ORDER
     *
     * Redirect to create api, and then it's intercepted by hook to redirect to UnDosTres.
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        ];
    }

    /**
     * CREATE ORDER ON UNDOSTRES' SIDE AND REDIRECT
     *
     * Create order on udt, handle errors and redirect to needed pages.
     */
    public function udt_order_redirect($order_id)
    {
        $order = $this->get_order($order_id);
        $request = [
            'currency' => $order->get_currency(),
            'callbackUrl' => $this->response_url,
            'returnUrl' => add_query_arg(['orderId' => $order_id], $this->return_url),
            'reference' => (string)$order_id,
            'transactionId' => (string)$order_id,
            'paymentId' => (string)$order_id,
            'orderId' => (string)$order_id,
            'value' => SASDK::formatMoney($order->get_total()),
            'installments' => 0,
            'paymentMethod' => WC_UDT_PAYMENT_ID,
            'miniCart' => [
                'buyer' => [
                    'firstName' => $order->get_billing_first_name(),
                    'email' => $order->get_billing_email(),
                    'lastName' => $order->get_billing_last_name(),
                    'phone' => $order->get_billing_phone()
                ],
                'taxValue' => SASDK::formatMoney($this->get_total_taxes($order->get_tax_totals())),
                'shippingValue' => SASDK::formatMoney($order->get_shipping_total()),
                'shippingAddress' => [
                    'street' => $order->get_shipping_address_1(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'postalCode' => $order->get_shipping_postcode()
                ],
                'items' => $this->get_products($order->get_items())
            ]
        ];
        $response = SASDK::createPayment($request);
        $this->log(sprintf("%s -> Payment url request send: %s \nReceive:\n%s", __METHOD__, json_encode($request), json_encode($response)));
        if ($response["code"] !== 200) {
            $order->update_meta_data('_udt_system_cancel', true);
            $order->update_status("cancelled");
            $order->add_order_note('La orden ha sido cancelada por no poder conectar con UnDosTres.');
            $order->save();
            wc_add_notice('UnDosTres no se encuentra disponible.', 'error');
            if (wp_redirect(add_query_arg(['udt-error' => 'true'], $order->get_cancel_order_url()))) exit;
        }
        $order->add_order_note('La orden ha sido creada por UnDosTres.');
        $order->save();
        if (wp_redirect($response["response"])) exit;
    }

    /** FORMATTING OF PRODUCTS LIST **/
    private function get_products($order_items): array
    {
        $product_details = [];
        foreach ($order_items as $product) {
            $product_details[] = [
                'id' => (string)$product['product_id'],
                'name' => $product['name'],
                'price' => SASDK::formatMoney(wc_get_product($product['product_id'])->get_price()),
                'quantity' => (int)$product['quantity'],
                'discount' => 0,
                'variation_id' => (string)$product['variation_id']
            ];
        }
        return $product_details;
    }

    /** TAX GETTER **/
    private function get_total_taxes($taxes): int
    {
        if (is_array($taxes)) {
            $total = 0;
            foreach ($taxes as $tax) $total += $tax->amount;
            return $total;
        }
        return $taxes;
    }

    /**
     * ADMIN CANCEL
     *
     * Verifies if order is being canceled by admin and then do cancel on UnDosTres, if something it's wrong it reverts to previous status.
     */
    public function order_status_changed($order_id, $old_status, $new_status)
    {
        $order = $this->get_order($order_id);
        if ($new_status === 'cancelled' && !$order->get_meta('_udt_user_cancel') && !$order->get_meta('_udt_system_cancel') && $order->get_payment_method() === WC_UDT_PAYMENT_ID) {
            if ($old_status !== 'pending' && $old_status !== 'processing') {
                $order->update_status($old_status, 'Solo se pueden cancelar órdenes pendientes con UnDosTres.');
                $this->log(sprintf("%s -> Failed admin cancel, order: %s -> Status %s to %s", __METHOD__, $order_id, $old_status, $new_status));
                return;
            }
            $response = SASDK::cancelOrder((string)$order_id);
            $this->log(sprintf("%s -> Admin order cancel, order: %s, received: %s", __METHOD__, $order_id, json_encode($response)));
            if ($response["code"] !== 200)
                $order->update_status($old_status, 'Error al procesar con UnDosTres, se regresó al status anterior.');
            else if ($old_status === 'processing')
                $order->update_status('refunded', 'Cancelar órdenes pagadas crea un reembolso automático.');
        }
    }

    /**
     * PROCESS THE REFUND
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool
    {
        $order = $this->get_order($order_id);
        if ($order->get_payment_method() === WC_UDT_PAYMENT_ID) {
            $response = SASDK::refundOrder((string)$order_id, (string)$order_id, $amount);
            $this->log(sprintf("%s -> Refund request, order: %s, amount: %s, received: %s", __METHOD__, $order_id, $amount, json_encode($response)));
            if ($response["code"] !== 200)
                $order->add_order_note('El reembolso no se ha podido procesar: ' . $response["status"]);
            return $response["code"] === 200;
        }
        return false;
    }
}
