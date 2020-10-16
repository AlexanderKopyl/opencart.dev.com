<?php
/**
 * Liqpay Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category        Liqpay
 * @package         Payment
 * @version         3.0
 * @author          Liqpay
 * @copyright       Copyright (c) 2014 Liqpay
 * @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * EXTENSION INFORMATION
 *
 * OpenCart         1.5.6
 * LiqPay API       https://www.liqpay.com/ru/doc
 *
 */

/**
 * Payment method liqpay controller (catalog)
 *
 * @author      Liqpay <support@liqpay.com>
 */
class ControllerExtensionPaymentLiqpay extends Controller
{

    /**
     * Index action
     *
     * @return void
     */
    public function index()
    {
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];
        /*set order_id to global variable*/
        $data['order_id'] = $this->session->data['order_id'];

        $order_info = $this->model_checkout_order->getOrder($order_id);

        $description = 'Order #'.$order_id;

        $order_id .= '#'.time();
        $result_url = $this->url->link('extension/payment/liqpay/server', '', 'SSL');
        $server_url = $this->url->link('extension/payment/liqpay/server', '', 'SSL');
        if($this->customer->getGroupId() == '2' || $this->customer->getGroupId() == '3'){
            $private_key = $this->config->get('payment_liqpay_sub_private_key');
            $public_key = $this->config->get('payment_liqpay_sub_public_key');
        }else{
            $private_key = $this->config->get('payment_liqpay_private_key');
            $public_key = $this->config->get('payment_liqpay_public_key');
        }

        $type = 'buy';
        $currency = $order_info['currency_code'];
        if ($currency == 'RUR') { $currency = 'RUB'; }
        $amount = $this->currency->format(
            $order_info['total'],
            $order_info['currency_code'],
            $order_info['currency_value'],
            false
        );
        $version  = '3';

        switch ($this->language->get('code')) {
            case 'ua-uk':
            case 'uk-ua':
            case 'ua':
            case 'uk':
            case 'ukrainian':
                $language = 'uk';
                break;
            case 'en-gb':
            case 'en-us':
            case 'en':
            case 'english':
                $language = 'en';
                break;
            case 'ru-ru':
            case 'ru':
            case 'russian':
                $language = 'ru';
                break;
            default:
                $language = $this->config->get('payment_liqpay_language');
        }


        $pay_way  = $this->config->get('payment_liqpay_pay_way');
        //$language = $this->config->get('payment_liqpay_language');

        $send_data = array('version'    => $version,
                           //'sandbox'    => '1',
                          'action'      => 'pay',
                          'public_key'  => $public_key,
                          'amount'      => $amount,
                          'currency'    => $currency,
                          'description' => $description,
                          'order_id'    => $order_id,
                          'type'        => $type,
                          'language'    => $language,
                          'server_url'  => $server_url,
                          'result_url'  => $result_url);
        if(isset($pay_way)){
          $send_data['paytypes'] = $pay_way;
        }

        $data_final = base64_encode(json_encode($send_data));

        $signature = base64_encode(sha1($private_key.$data_final.$private_key, 1));

        $data['action']         = $this->config->get('payment_liqpay_action');
        $data['signature']      = $signature;
        $data['data']           = $data_final;
        $data['button_confirm'] = 'Оплатить';
        $data['url_confirm']    = $this->url->link('extension/payment/liqpay/confirm');


        return $this->load->view('extension/payment/liqpay', $data);

    }


    /**
     * Confirm action
     *
     * @return void
     */
    public function confirm()
    {
        $this->load->model('checkout/order');
        $order = $this->session->data['order_id'];
        //$this->model_checkout_order->confirm($order, $this->config->get('config_order_status_id'), 'unpaid');
        $this->model_checkout_order->confirm($this->session->data['order_id'], 2);
    }


    /**
     * Check and return posts data
     *
     * @return array
     */
    private function getPosts()
    {
        $success =
            isset($_POST['data']) &&
            isset($_POST['signature']);

        if ($success) {
            return array(
                $_POST['data'],
                $_POST['signature'],
            );
        }
        return array();
    }


    /**
     * get real order ID
     *
     * @return string
     */
    public function getRealOrderID($order_id)
    {
        $real_order_id = explode('#', $order_id);
        return $real_order_id[0];
    }


    /**
     * Server action
     *
     * @return void
     */
    public function server()
    {
        if (!$posts = $this->getPosts()) { die('Posts error'); }

        list(
            $data,
            $signature
        ) = $posts;

        if(!$data || !$signature) {die("No data or signature");}

        $parsed_data = json_decode(base64_decode($data), true);

        $received_public_key = (isset($parsed_data['public_key']))?$parsed_data['public_key']:'';
        $order_id            = (isset($parsed_data['order_id']))?$parsed_data['order_id']:'';
        $status              = (isset($parsed_data['status']))?$parsed_data['status']:'';
        $sender_phone        = (isset($parsed_data['sender_phone']))?$parsed_data['sender_phone']:'';
        $amount              = (isset($parsed_data['amount']))?$parsed_data['amount']:'';
        $currency            = (isset($parsed_data['currency']))?$parsed_data['currency']:'';
        $transaction_id      = (isset($parsed_data['transaction_id']))?$parsed_data['transaction_id']:'';

        $real_order_id = $this->getRealOrderID($order_id);

        if ($real_order_id <= 0) { die("Order_id real_order_id < 0"); }

        $this->load->model('checkout/order');
        if (!$this->model_checkout_order->getOrder($real_order_id)) { die("Order_id fail");}

        if($this->customer->getGroupId() == '2' || $this->customer->getGroupId() == '3'){
//            $private_key = "Fti92rQuAUGcD9xZIdqy1BfYRwiJva7DzMMRGhyV";
//            $public_key = "i73880405479";

            $private_key = $this->config->get('payment_liqpay_sub_private_key');
            $public_key = $this->config->get('payment_liqpay_sub_public_key');
        }else{
            $private_key = $this->config->get('payment_liqpay_sub_private_key');
            $public_key = $this->config->get('payment_liqpay_sub_public_key');
        }

        $generated_signature = base64_encode(sha1($private_key.$data.$private_key, 1));

        if ($signature  != $generated_signature) { die("Signature secure fail"); }
        if ($public_key != $received_public_key) { die("public_key secure fail"); }

        if ($status == 'success' || $status == 'sandbox') {
            $this->model_checkout_order->addOrderHistory($real_order_id, $this->config->get('payment_liqpay_order_status_id'));
            $this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
        } elseif (preg_match('/wait|hold|processing/',$status)) {
            $this->model_checkout_order->addOrderHistory($real_order_id, 1);
            $this->response->redirect($this->url->link('checkout/wait', '', 'SSL'));
        } else {
            //$this->session->data['error'] = $this->session->data['status'];
            $this->response->redirect($this->url->link('checkout/failure', '', 'SSL'));
        }

    }

}
