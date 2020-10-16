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
 * Payment method liqpay controller (admin)
 *
 * @author      Liqpay <support@liqpay.com>
 */
class ControllerExtensionPaymentLiqPay extends Controller
{
	private $error = array();


    /**
     * Index action
     *
     * @return void
     */
 	public function index()
	{
		$this->language->load('extension/payment/liqpay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_liqpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}



		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['public_key'])) {
			$data['error_public_key'] = $this->error['public_key'];
		} else {
			$data['error_public_key'] = '';
		}

		if (isset($this->error['private_key'])) {
			$data['error_private_key'] = $this->error['private_key'];
		} else {
			$data['error_private_key'] = '';
		}

		if (isset($this->error['action'])) {
			$data['error_action'] = $this->error['action'];
		} else {
			$data['error_action'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/liqpay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/liqpay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_liqpay_public_key'])) {
			$data['payment_liqpay_public_key'] = $this->request->post['payment_liqpay_public_key'];
		} else {
			$data['payment_liqpay_public_key'] = $this->config->get('payment_liqpay_public_key');
		}

		if (isset($this->request->post['payment_liqpay_private_key'])) {
			$data['payment_liqpay_private_key'] = $this->request->post['payment_liqpay_private_key'];
		} else {
			$data['payment_liqpay_private_key'] = $this->config->get('payment_liqpay_private_key');
		}

		if (isset($this->request->post['payment_liqpay_action'])) {
			$data['payment_liqpay_action'] = $this->request->post['payment_liqpay_action'];
		} else {
			$data['payment_liqpay_action'] = $this->config->get('payment_liqpay_action');
			if (empty($data['payment_liqpay_action'])) {
				$data['payment_liqpay_action'] = 'https://www.liqpay.ua/api/3/checkout';
			}
		}

		if (isset($this->request->post['payment_liqpay_total'])) {
			$data['payment_liqpay_total'] = $this->request->post['payment_liqpay_total'];
		} else {
			$data['payment_liqpay_total'] = $this->config->get('payment_liqpay_total');
		}

		if (isset($this->request->post['payment_liqpay_order_status_id'])) {
			$data['payment_liqpay_order_status_id'] = $this->request->post['payment_liqpay_order_status_id'];
		} else {
			$data['payment_liqpay_order_status_id'] = $this->config->get('payment_liqpay_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_liqpay_geo_zone_id'])) {
			$data['payment_liqpay_geo_zone_id'] = $this->request->post['payment_liqpay_geo_zone_id'];
		} else {
			$data['payment_liqpay_geo_zone_id'] = $this->config->get('payment_liqpay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_liqpay_status'])) {
			$data['payment_liqpay_status'] = $this->request->post['payment_liqpay_status'];
		} else {
			$data['payment_liqpay_status'] = $this->config->get('payment_liqpay_status');
		}

		if (isset($this->request->post['payment_liqpay_sort_order'])) {
			$data['payment_liqpay_sort_order'] = $this->request->post['payment_liqpay_sort_order'];
		} else {
			$data['payment_liqpay_sort_order'] = $this->config->get('payment_liqpay_sort_order');
		}

		if (isset($this->request->post['payment_liqpay_pay_way'])) {
			$data['payment_liqpay_pay_way'] = $this->request->post['payment_liqpay_pay_way'];
		} else {
			$data['payment_liqpay_pay_way'] = $this->config->get('payment_liqpay_pay_way');
		}

		if (isset($this->request->post['payment_liqpay_language'])) {
			$data['payment_liqpay_language'] = $this->request->post['payment_liqpay_language'];
		} else {
			$data['payment_liqpay_language'] = $this->config->get('payment_liqpay_language');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/liqpay', $data));
		//$this->response->setOutput($this->render());           
    
	}


    /**
     * Validate input data
     *
     * @return boolean
     */
	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/payment/liqpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_liqpay_public_key']) {
			$this->error['public_key'] = $this->language->get('error_public_key');
		}

		if (!$this->request->post['payment_liqpay_private_key']) {
			$this->error['private_key'] = $this->language->get('error_private_key');
		}

		if (!$this->request->post['payment_liqpay_action']) {
			$this->error['action'] = $this->language->get('error_action');
		}

		return !$this->error;
	}
}
