<?php
/*
 * Plugin Name: VLoad Digital Card
 * Plugin URI: https://vloadcards.com/
 * Description: Take VLoad Digital Card on your store.
 * Author: Global Primex
 * Author URI: https://vloadcards.com/
 * Version: 1.5.0
 */

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'vload_cards_class');
function vload_cards_class($payment)
{
	$payment[] = 'WC_vload_cards'; // your class name is here
	return $payment;
}


add_action('plugins_loaded', 'vload_cards_init_class');
function vload_cards_init_class()
{

	class WC_vload_cards extends WC_Payment_Gateway
	{

		public function __construct()
		{

			$this->id = 'vload_cards';
			$this->icon = trailingslashit(WP_PLUGIN_URL) . plugin_basename(dirname('')) . 'woo-vloadcards-plugin/assets/logo-VDC-full-lightbg.svg'; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true;
			$this->method_title = 'VLoad Digital Card Payment';
			$this->method_description = 'Description of VLoad Digital Card Payment'; // will be displayed on the options page


			$this->supports = array(
				'products'
			);

			// Method with all the options fields
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Load the settings.
			$this->init_settings();
			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->enabled = $this->get_option('enabled');
			$this->testmode = 'yes' === $this->get_option('testmode');
			$this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
			$this->redirect_flow = 'yes' === $this->get_option('redirect_flow');
			$this->merchant_id = $this->get_option('merchant_id');
			$this->is_quick_pay = false;


			// This action hook saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

			add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

			add_action('woocommerce_thankyou', array($this, 'order_received'), 10, 1);

			// This action hook shows custom fields(Redeem) in admin order detail
			add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'additional_field_display_admin_order_meta'), 10, 1);
		}

		public function init_form_fields()
		{

			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable VLoad Digital Card Payment',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'VLoad Digital Card',
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Pay with your VLoad Digital Card',
					'desc_tip'    => true,
				),

				'testmode' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the VLoad Digital Card payment in test mode by checking the box which will use the test API access Key.  If not check, it will use the production API access Key',
					'default'     => 'yes',
					'desc_tip'    => true,
				),

				'test_private_key' => array(
					'title'       => 'Test Access Key',
					'type'        => 'text',
					'description' => 'Input the Test API Key provided by VLoad Digital Card for Test environment',
					'desc_tip'    => true,
				),

				'private_key' => array(
					'title'       => 'Live Access Key',
					'type'        => 'text',
					'description' => 'Input the Live API Key provided by VLoad Digital Card for Live environment',
					'desc_tip'    => true,
				),

				'redirect_flow' => array(
					'title'       => 'Redirect Flow',
					'label'       => 'Enable Redirect Flow',
					'type'        => 'checkbox',
					'description' => 'Place enable Vload Redirect Flow by checking the box.',
					'default'     => 'no',
					'desc_tip'    => true,
				),

				'merchant_id' => array(
					'title'       => 'Merchant ID',
					'type'        => 'text',
					'description' => 'Input the Merchant ID for Redirect Flow Mode',
					'desc_tip'    => true,
				),
			);
		}


		public function payment_fields()
		{
			echo '<div style="padding:1.5rem;padding-bottom:0;"><img src="' . $this->icon . '" alt="VLoad Digital Card" style="max-width:150px;"></div>';
			if ($this->redirect_flow && $this->enabled == 'yes') {
				echo '<div style="padding:1.5rem;"><label>' . $this->description . '</label>';
				echo '<div class="clear"></div>
				<input type="hidden" name="is_quick_pay" value="0" autocomplete="off" id="is_quick_pay" />
				<button class="button" id="quick_pay" value="VLOAD QUICKPAY!" data-value="VLOAD QUICKPAY!">VLOAD QUICKPAY!</button>';
				echo '<p>1. Login or Register an account</p>';
				echo '<p>2. Select or add a payment method</p>';
				echo '<p>3. Buy your VLoad Digital Card and instantly pay</p>';
				echo '<div class="separator">OR</div></div>';
			}
			if ($this->testmode) {
				echo '<p style="padding:1.5rem;padding-bottom:0;">Test Mode Enabled</p>';
				$this->description  = trim($this->description);
			}
			echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

			do_action('woocommerce_credit_card_form_start', $this->id);

			echo '<div class="form-row form-row-wide">

				<label>Already have your VLOAD Digital Card?<br>
				Paste your VLOAD PIN to instantly pay&nbsp;<span class="required">*</span></label>
				<input id="vload_cards" name="vload_cards" type="text" autocomplete="off">
				</div>

				<div class="clear"></div>';

			do_action('woocommerce_credit_card_form_end', $this->id);
			echo '<div class="clear"></div><button type="submit" class="button" id="pay_with_vload" value="PAY WITH VLOAD" data-value="PAY WITH VLOAD">PAY WITH VLOAD</button></fieldset>';
		}

		public function payment_scripts()
		{
			if (is_checkout() && $this->enabled == 'yes' && $this->redirect_flow) {
				wp_register_script('woocommerce_vload_cards_payment_script', plugins_url('/assets/js/vload_cards.js', __FILE__));
				wp_enqueue_script('woocommerce_vload_cards_payment_script');

				wp_register_style('woocommerce_vload_cards_payment_style', plugins_url('/assets/css/style.css', __FILE__));
				wp_enqueue_style('woocommerce_vload_cards_payment_style');
			} else {
				return;
			}
		}

		public function validate_fields()
		{
			$this->is_quick_pay = $_POST['is_quick_pay'] == '1';

			if (!$this->is_quick_pay) {
				if (empty($_POST['vload_cards'])) {
					wc_add_notice('PIN is required!', 'error');
					return false;
				}
				if (strlen($_POST['vload_cards']) < 16) {
					wc_add_notice('No such VLoad Digital Card', 'error');
					return false;
				}
				if (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $_POST['vload_cards'])) {
					wc_add_notice('No such VLoad Digital Card', 'error');
					return false;
				}
			}
			return true;
		}


		public function process_payment($order_id)
		{
			global $woocommerce;

			// we need it to get any order detailes
			$order = wc_get_order($order_id);

			$names = new WC_Order($order_id);
			$billing_first_name = get_user_meta($names->user_id, 'billing_first_name', true);
			$billing_last_name = get_user_meta($names->user_id, 'billing_last_name', true);
			$billing_email = get_user_meta($names->user_id, 'billing_email', true);
			$billing_phone = get_user_meta($names->user_id, 'billing_phone', true);
			$current_user_id = get_current_user_id();
			$cart_total_amount = WC()->cart->total * 100;
			$trnx_id = 'VL ' . $order_id;
			$pin = $_POST['vload_cards'];

			// Check PAY WITH VLOAD OR PAY WITH PIN
			if ($this->is_quick_pay) {
				$vload_pay_url = "https://testpay.vloadcards.com";
				$billing_address_1  = WC()->customer->get_billing_address_1();
				$billing_postcode   = WC()->customer->get_billing_postcode();
				$billing_city       = WC()->customer->get_billing_city();
				$billing_state      = WC()->customer->get_billing_state();
				$billing_country    = WC()->customer->get_billing_country();
				$billing_email    	= WC()->customer->get_billing_email();
				$billing_phone = "+" . str_replace("+", "", $billing_phone);
				$params = array(
					"amount" => $cart_total_amount,
					"email" => $billing_email,
					"merchant_id" => $this->merchant_id,
					"payer[id]" => $current_user_id,
					"return_url" => $this->get_return_url($order),
				);
				if (isset($billing_first_name) && $billing_first_name != "")
					$params['first_name'] = $billing_first_name;
				if (isset($billing_last_name) && $billing_last_name != "")
					$params['last_name'] = $billing_last_name;
				if (isset($billing_phone) && $billing_phone != "")
					$params['phone'] = $billing_phone;
				if (isset($billing_address_1) && $billing_address_1 != "")
					$params['address_line'] = $billing_address_1;
				if (isset($billing_postcode) && $billing_postcode != "")
					$params['address_zip'] = $billing_postcode;
				if (isset($billing_city) && $billing_city != "")
					$params['address_city'] = $billing_city;
				if (isset($billing_state) && $billing_state != "")
					$params['address_state'] = $billing_state;
				if (isset($billing_country) && $billing_country != "")
					$params['address_country'] = $billing_country;
				$params = http_build_query($params);
				return array(
					'result' => 'success',
					'redirect' => $vload_pay_url . "?" . $params
				);
			} else {
				//VLoad_Cards API Parameters
				$redeem_url = 'https://api.vloadcards.com/v2/charges';
				$validate_url = 'https://api.vloadcards.com/v2/vouchers/validate';
				$auth = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');

				$headers = array(
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Authorization' => 'Bearer ' . $auth
				);

				$validate_body = array(
					'pin' => $_POST['vload_cards']
				);

				$redeem_body = array(
					'source' => $_POST['vload_cards'],
					'payer[id]' => $current_user_id,
					'payer[firstname]' => $billing_first_name,
					'payer[lastname]' => $billing_last_name,
					'payer[email]' => $billing_email,
					'payer[ip]' => gethostbyname($_SERVER['HTTP_HOST']),
					'amount' => $cart_total_amount,
					'metadata[merchant_trx_id]' => $trnx_id
				);


				$validate_response = wp_remote_post(
					$validate_url,
					array(
						'method'      => 'POST',
						'timeout'     => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking'    => true,
						'headers'     => $headers,
						'body'        => $validate_body,
						'cookies'     => array()
					)
				);

				$body_validate = wp_remote_retrieve_body($validate_response);
				$validate_body_decode = json_decode($body_validate);

				if (isset($validate_body_decode->error)) {
					wc_add_notice("VLoad Digital Card not found", 'error');
				} else {
					$api_validate_amount = $validate_body_decode->value;
					$api_validate_currency = $validate_body_decode->currency;
					$woo_currency = get_woocommerce_currency();

					if ($api_validate_currency != $woo_currency) {
						wc_add_notice("VLoad Digital Card currency does not match cart currency", 'error');
					} elseif ($api_validate_amount < $cart_total_amount) {
						foreach ($validate_body_decode as $validate_decode) {
							if (isset($validate_decode->type) && $validate_decode->type == 'invalid_request_error') {
								wc_add_notice("VLoad Digital Card not found", 'error');
							} else {
								wc_add_notice("VLoad Digital Card amount is less than total amount", 'error');
								break;
							}
						}
					} else {
						$redeem_response = wp_remote_request(
							$redeem_url,
							array(
								'method' => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers' => $headers,
								'body' => $redeem_body,
								'cookies'     => array()
							)
						);

						$body_redeem = wp_remote_retrieve_body($redeem_response);
						$redeem_json = json_decode($body_redeem);

						if (isset($redeem_json->status) && $redeem_json->status == 'succeeded') {
							$order->payment_complete();
							$order->reduce_order_stock();
							$order->update_status('completed');
							//$order->add_order_note( 'Hey, your order is on hold! Thank you!', true );
							$woocommerce->cart->empty_cart();
							return array(
								'result' => 'success',
								'redirect' => $this->get_return_url($order)
							);
						} else {
							foreach ($redeem_json as $redeem_key) {
								if (isset($redeem_key->message) && $redeem_key->message != "") {

									if ($redeem_key->message == 'No such voucher: ************' . substr($pin, -4)) {
										wc_add_notice("VLoad Digital Card not found", 'error');
									}
									if ($redeem_key->message == 'Duplicate voucher holder account') {
										wc_add_notice("Please contact VLoad Digital Card/Merchant support for further assistance", 'error');
									}
									if ($redeem_key->message == 'Duplicate voucher holder account same redeem account') {
										wc_add_notice("Please contact VLoad Digital Card/Merchant support for further assistance", 'error');
									}
									if ($redeem_key->message == 'Country not permitted by voucher acceptor shop') {
										wc_add_notice("Please contact VLoad Digital Card/Merchant support for further assistance", 'error');
									}
									if ($redeem_key->message == 'Voucher holder account disabled') {
										wc_add_notice("Please contact VLoad Digital Card/Merchant support for further assistance", 'error');
									}
									if ($redeem_key->message == 'Recipient name mismatch with account on file.') {
										wc_add_notice("Please contact VLoad Digital Card/Merchant support for further assistance", 'error');
									}
								}
								if (isset($redeem_key->type) && $redeem_key->type != "") {
									if ($redeem_key->type == 'invalid_request_error') {
										wc_add_notice("Please contact VLoad Digital Card/Merchant support for further assistance", 'error');
									}
								}
							}
						}
					}
				}
			}
		}

		public function webhook()
		{

			$order = wc_get_order($_GET['id']);
			$order->payment_complete();
			$order->reduce_order_stock();

			update_option('webhook_debug', $_GET);
		}

		public function order_received($order_id)
		{
			global $woocommerce;
			$order = new WC_Order($order_id);
			$error = filter_input(INPUT_GET, 'error');
			if (isset($_GET['redeem']) && $_GET['redeem'] != '') {
				update_post_meta($order_id, 'redeem', $_GET['redeem']);
				$order->payment_complete();
				$order->reduce_order_stock();
				$order->update_status('completed');
			} else if ($error == 'canceled') {
				$order->update_status('cancelled');
			} else {
				$order->update_status('failed');
			}
			$woocommerce->cart->empty_cart();
		}


		public function additional_field_display_admin_order_meta($order)
		{
			$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
			$order_status = $order->get_status();
			$redeem = get_post_meta($order_id, 'redeem', true);
			if ($order_status == 'completed' && $redeem != '') {
				echo '<p><strong>' . __('Redeem') . ':</strong> ' . $redeem . '</p>';
			}
		}
	}
}
