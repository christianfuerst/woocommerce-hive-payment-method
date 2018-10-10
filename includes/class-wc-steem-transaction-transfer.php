<?php
/**
 * WC_Steem_Transaction_Transfer
 *
 * @package WooCommerce Steem Payment Method
 * @category Class
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Steem_Transaction_Transfer {

	/**
	 * Retrieve "Steem Transaction Transfer" via Steemful API
	 *
	 * @since 1.0.0
	 * @param WC_Order $order
	 * @return $transfer
	 */
	public static function get($order) {
		$transfer = null;

		if (is_int($order)) {
			$order = wc_get_order($order);
		}
		elseif (isset($order->post_type) && $order->post_type == 'shop_order') {
			$order = wc_get_order($order);
		}

		if (empty($order) || is_wp_error($order) || $order->payment_method != 'wc_steem') {
			return $transfer;
		}

		$data = array(
			'to' => wc_order_get_steem_payee($order->id),
			'memo' => wc_order_get_steem_memo($order->id),
			'amount' => wc_order_get_steem_amount($order->id),
			'amount_currency' => wc_order_get_steem_amount_currency($order->id),
		);

		if (empty($data['to']) || empty($data['memo']) || empty($data['amount'] || empty($data['amount_currency']))) {
			return $transfer;
		}

		/*
		$response = wp_remote_get(
			add_query_arg(
				array(
					'to' => $data['to'],
					'memo' => $data['memo'],
					'amount' => $data['amount'],
					'amount_symbol' => $data['amount_currency'],
					'limit' => 1,
				),
				'http://steemful.com/api/v1/transactions/transfers'
			)
		);

		if (is_array($response)) {
			$response_body = json_decode(wp_remote_retrieve_body($response), true);

			if (isset($response_body['data'][0]) && $response_body['data'][0]) {
				$transfer = $response_body['data'][0];
			}
		}
		*/
		
		$tx = json_decode(file_get_contents("https://steakovercooked.com/api/steemit/transfer-history/?id=" . $data['to']), true);
		foreach ($tx as $r) {
			// Match amount sent to this user in this currency
			$transaction_message_1 = "Received " . $data['amount'] . " " . $data['amount_currency'] . " from ";
			// Match amount sent to this user in this currency by this same user
			$transaction_message_2 = "Transfer " . $data['amount'] . " " . $data['amount_currency'] . " to " . $data['to'];
			
			if ($data['memo'] === $r['memo'] &&
				(
					substr($r['transaction'], 0, strlen($transaction_message_1)) === $transaction_message_1 ||
					substr($r['transaction'], 0, strlen($transaction_message_2)) === $transaction_message_2
				)
			) {
				$transfer = $r;
				break;
			}
		}
		
		return $transfer;
	}
}
