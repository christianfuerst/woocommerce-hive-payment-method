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

		if (empty($order) || is_wp_error($order) || $order->get_payment_method() != 'wc_steem') {
			return $transfer;
		}

		$data = array(
			'to' => wc_order_get_steem_payee($order->get_id()),
			'memo' => wc_order_get_steem_memo($order->get_id()),
			'amount' => wc_order_get_steem_amount($order->get_id()),
			'amount_currency' => wc_order_get_steem_amount_currency($order->get_id()),
		);

		if (empty($data['to']) || empty($data['memo']) || empty($data['amount'] || empty($data['amount_currency']))) {
			// Initial transaction data not found in this order. Mark the order as searched so that it is not queried again.
			update_post_meta($order->get_id(), '_wc_steem_last_searched_for_transaction', date('m/d/Y h:i:s a', time()));
			
			return $transfer;
		}
		
		$file_contents = file_get_contents("https://steakovercooked.com/api/steemit/transfer-history/?id=" . $data['to']);
		
		// If failure in retrieving url
		if ($file_contents === false)
			return $transfer;
		
		$tx = json_decode($file_contents, true);
		
		// If error decoding JSON
		if (JSON_ERROR_NONE !== json_last_error()) {
			return $transfer;
		}
				
		foreach ($tx as $r) {
			// Format the amount as a string to ensure 3 decimal places, no thousand seperator in order to find a match.
			$amount = number_format( $data['amount'] , 3, "." , "" );
			
			// Match amount sent to this user in this currency
			$transaction_message_1 = "Received " . $amount . " " . $data['amount_currency'] . " from ";
			// Match amount sent to this user in this currency by this same user
			$transaction_message_2 = "Transfer " . $amount . " " . $data['amount_currency'] . " to " . $data['to'];
			
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
		
		// Successfully (no errors in retrieving JSON) searched transaction history for the record.
		update_post_meta($order->get_id(), '_wc_steem_last_searched_for_transaction', date('m/d/Y h:i:s a', time()));
		
		return $transfer;
	}
}
