<?php
/**
 * WC_Steem_Handler
 *
 * @package WooCommerce Steem Payment Method
 * @category Class Handler
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Steem_Handler {

	public static function init() {
		$instance = __CLASS__;

		add_action('init', array($instance, 'register_schedulers'));
        add_action('woocommerce_email_before_order_table', array($instance, 'action_woocommerce_email_order_details'), 10, 4); 			
	}

	public static function register_schedulers() {
		$instance = __CLASS__;

		if ( ! wp_next_scheduled('wc_steem_update_rates')) {
			wp_schedule_event(time(), 'hourly', 'wc_steem_update_rates');
		}

		if ( ! wp_next_scheduled('wc_steem_update_orders')) {
			wp_schedule_event(time(), '2min', 'wc_steem_update_orders');
		}
		
		if ( ! wp_next_scheduled('wc_steem_send_pending_payment_emails')) {
			wp_schedule_event(time(), '5min', 'wc_steem_send_pending_payment_emails');
		}		

		if (empty(get_option('wc_steem_rates'))) {
			self::update_rates();
		}

		add_action('wc_steem_update_rates', array($instance, 'update_rates'));
		add_action('wc_steem_update_orders', array($instance, 'update_orders'));
		add_action('wc_steem_send_pending_payment_emails', array($instance, 'send_pending_payment_emails'));
	}

	public static function update_rates() {
		$rates = get_option('wc_steem_rates', array());

		$response = wp_remote_get('https://poloniex.com/public?command=returnTicker');

		if (is_array($response)) {
			$tickers = json_decode(wp_remote_retrieve_body($response), true);

			if (isset($tickers['USDT_BTC']['last'])) {
				$rates['BTC_USD'] = $tickers['USDT_BTC']['last'];

				if (isset($tickers['BTC_STEEM']['last'])) {
					$rates['STEEM_USD'] = $tickers['BTC_STEEM']['last'] * $rates['BTC_USD'];
				}

				if (isset($tickers['BTC_SBD']['last'])) {
					$rates['SBD_USD'] = $tickers['BTC_SBD']['last'] * $rates['BTC_USD'];
				}
			}
		}

 		//$response = wp_remote_get('http://api.fixer.io/latest?base=USD');
 		$response = wp_remote_get('https://api.exchangeratesapi.io/latest?base=USD');

		if (is_array($response)) {
			$tickers = json_decode(wp_remote_retrieve_body($response), true);

			if (isset($tickers['rates']) && $tickers['rates']) {
				foreach ($tickers['rates'] as $to_currency_symbol => $to_currency_value) {
					$rates["USD_{$to_currency_symbol}"] = $to_currency_value;

					if (isset($rates['STEEM_USD'])) {
						$rates["STEEM_{$to_currency_symbol}"] = $rates['STEEM_USD'] * $to_currency_value;
					}

					if (isset($rates['SBD_USD'])) {
						$rates["SBD_{$to_currency_symbol}"] = $rates['SBD_USD'] * $to_currency_value;
					}
				}
			}
		}

		update_option('wc_steem_rates', $rates);
	}

	public static function update_orders() {

		// Only search for transactions for orders that were placed 30 minutes ago.
		// Orders within the last 30 minutes and no matching transaction has been found yet.
		$query1 = new WP_Query(array(
			'post_type' => 'shop_order',
			'post_status' => 'wc-pending',
			'posts_per_page' => 100,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_payment_method',
					'value' => 'wc_steem',
					'compare' => '=',
				),
				array(
					'key' => '_wc_steem_transaction_transfer',
					'compare' => 'NOT EXISTS',
				),
			),
			// Only include orders that were placed within the last 30 minutes
			'date_query'    => array(
				'column'  => 'post_date',
				'after'   => '30 minutes ago',
				'inclusive' => true,
			),
			'fields' => 'ids',
		));
		
		// Orders greater than 30 minutes ago and transactions have not been searched yet for this order.
		// e.g. cron was disabled or search failed previously.
		// These older orders will only be queried once and them marked that they have been queried.
		$query2 = new WP_Query(array(
			'post_type' => 'shop_order',
			'post_status' => 'wc-pending',
			'posts_per_page' => 100,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_payment_method',
					'value' => 'wc_steem',
					'compare' => '=',
				),
				// Transaction has not been queried yet. 
				array(
					'key' => '_wc_steem_last_searched_for_transaction',
					'compare' => 'NOT EXISTS',
				),
			),
			// Only include orders that were placed before the last 30 minutes
			'date_query'    => array(
				'column'  => 'post_date',
				'before'   => '30 minutes ago',
				'inclusive' => false,
			),
			'fields' => 'ids',
		));
		
		$order_post_ids = array_merge( $query1->posts, $query2->posts );

		if (empty($order_post_ids) || is_wp_error($order_post_ids)) {
			return;
		}
		
		foreach ($order_post_ids as $key => $order_post_id) {
			$order = new WC_Order($order_post_id);
			self::update_order($order);
		}
	}

	public static function update_order($order) {

		if (empty($order) || is_wp_error($order)) {
			return;
		}

		if ($order->get_payment_method() != 'wc_steem') {
			return;
		}

		if ( ! empty(get_post_meta($order->get_id(), '_wc_steem_transaction_transfer', true))) {
			return;
		}

		$transfer = WC_Steem_Transaction_Transfer::get($order);
		
		if ($transfer != null) {
			// Mark payment as completed
			$order->payment_complete();
			
			$payee = wc_order_get_steem_payee($order->get_id());

			// Add intuitive order note
			$order->add_order_note(
				sprintf(
					__('Steem payment <strong>Received</strong><br />Time: %s<br />Memo: %s<br />Payee: %s<br />%s', 'wc-steem'), 
					$transfer['time'], 
					$transfer['memo'], 
					$payee,
					$transfer['transaction']
				)				
			);

			update_post_meta($order->get_id(), '_wc_steem_status', 'paid');
			update_post_meta($order->get_id(), '_wc_steem_transaction_transfer', $transfer);
		}		
	}

	public static function send_pending_payment_emails() {

		$orders = get_posts(array(
			'post_type' => 'shop_order',
			'post_status' => 'wc-pending',
			'posts_per_page' => 20,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_payment_method',
					'value' => 'wc_steem',
					'compare' => '=',
				),
				array(
					'key' => '_wc_steem_pending_payment_email_sent',
					'compare' => 'NOT EXISTS',
				),
			),
		));

		if (empty($orders) || is_wp_error($orders)) {
			return;
		}

		foreach ($orders as $order) {
			$order = wc_get_order($order);
			self::send_pending_payment_email($order);
		}
	}
	
	public static function send_pending_payment_email($order) {
		if (empty($order) || is_wp_error($order)) return;

		if ($order->get_payment_method() != 'wc_steem') return;
		
		if( ! $order->has_status( 'pending' ) ) return;
		
		$order_modified_date = $order->get_date_modified();
		
		$now = new DateTime('now');
		// If the order was set to pending less than 10 minutes ago don't send the email yet.
		// Orders are updated every 5 minutes. This will allow time for the order to be automatically updated.
		if($now->diff($order_modified_date)->i < 10) return;

		self::new_pending_order_emails($order);
		
		update_post_meta($order->get_id(), '_wc_steem_pending_payment_email_sent', 'true');
	}
	
	public static function new_pending_order_emails( $order ) {
		self::new_pending_order_email_to_admin($order);
		self::new_pending_order_email_to_customer($order);
	}	
		
	public static function new_pending_order_email_to_admin( $order ) {
		if ($order->get_payment_method() != 'wc_steem') return;		
		
		if( ! $order->has_status( 'pending' ) ) return;
		
		$wc_emails = WC()->mailer()->get_emails();
		
		$email = $wc_emails['WC_Email_New_Order'];		
		
		$heading = __('New Pending Order'); 
		$subject = sprintf('New Pending Order %s', $order->get_order_number());
		$recipient = get_option('woocommerce_email_from_address');
		
		$template_path = "emails\admin-new-order.php";
		$content_html = wc_get_template_html( $template_path, array(
            'order'         => $order,
            'email_heading' => $heading,
            'sent_to_admin' => true,
            'plain_text'    => false,
            'email'         => $email,
        ) );
		
		$email->send( $recipient, $subject, $content_html, $email->get_headers(), $email->get_attachments() );
	}
	
	public static function new_pending_order_email_to_customer( $order ) {
		if ($order->get_payment_method() != 'wc_steem') return;
		
		if( ! $order->has_status( 'pending' ) ) return;
				
		$wc_emails = WC()->mailer()->get_emails();
				
		$email = $wc_emails['WC_Email_Customer_Processing_Order'];
		
		$heading = __('Your Order Is Pending Payment'); 
		$subject = sprintf('Your %s Order Is Pending Payment', get_bloginfo( 'name' ));
		$recipient = $order->get_billing_email();
		
		$template_path = "emails\customer-processing-order.php";
		
		$content_html = wc_get_template_html( $template_path, array(
            'order'         => $order,
            'email_heading' => $heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $email,
        ) );
		
		$email->send( $recipient, $subject, $content_html, $email->get_headers(), $email->get_attachments() );
	}

    public static function action_woocommerce_email_order_details( $order, $sent_to_admin, $plain_text, $email ) { 
		if ($order->get_payment_method() != 'wc_steem') return;	
		
		if( ! $order->has_status( 'pending' ) ) return;
		
		$order_id = $order->get_order_number();
		
		echo '
			<h2>
				Payment Is Needed To Process The Order
			</h2>
			
			<p style="margin: 0 0 16px;">We have not received payment. <em>When sending your payment be sure to specify</em> the exact <strong>Memo</strong>, <strong>Recipient</strong>, <strong>Total</strong> and <strong>Currency</strong> found below so that we can identify your payment and process your order. If you already sent payment, please confirm in your wallet history if this information is correct.</p>

			<div style="margin-bottom: 40px;">
				<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;" border="1">
				<tbody>
					<tr>
						<th class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							__('Payee', 'wc-steem') . '</th>
						<td class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							wc_order_get_steem_payee($order_id) . '</td>
					</tr>
					<tr>
						<th class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							__('Memo', 'wc-steem') . '</th>
						<td class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							wc_order_get_steem_memo($order_id) . '</td>
					</tr>
					<tr>
						<th class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							__('Amount', 'wc-steem') . '</th>
						<td class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							wc_order_get_steem_amount($order_id) . '</td>
					</tr>
					<tr>
						<th class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							__('Currency', 'wc-steem') . '</th>
						<td class="td" style="text-align: left; vertical-align: middle; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; border: 1px solid #e5e5e5; padding: 12px;">' . 
							wc_order_get_steem_amount_currency($order_id) . '</td>
					</tr>
				</tbody>
				</table>
			</div>
		';	
    }	
}	

WC_Steem_Handler::init();
