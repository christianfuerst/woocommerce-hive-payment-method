<?php
/**
 * WC_Hive_Transaction_Transfer
 *
 * @package WooCommerce Hive Payment Method
 * @category Class
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Transaction_Transfer {

	/**
	 * Retrieve "Hive Transaction Transfer" via Hiveful API
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

		if (empty($order) || is_wp_error($order) || $order->get_payment_method() != 'wc_hive') {
			return $transfer;
		}

		$data = array(
			'to' => wc_order_get_hive_payee($order->get_id()),
			'memo' => wc_order_get_hive_memo($order->get_id()),
			'amount' => wc_order_get_hive_amount($order->get_id()),
			'amount_currency' => wc_order_get_hive_amount_currency($order->get_id()),
		);

		if (empty($data['to']) || empty($data['memo']) || empty($data['amount'] || empty($data['amount_currency']))) {
			// Initial transaction data not found in this order. Mark the order as searched so that it is not queried again.
			update_post_meta($order->get_id(), '_wc_hive_last_searched_for_transaction', date('m/d/Y h:i:s a', time()));
			
			return $transfer;
		}
		
		$file_contents = file_get_contents("https://xapi.esteem.app/get_account_history?from=-1&limit=1000&account=" . $data['to']);
		
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
			$amount = number_format( $data['amount'] , 3, "." , "" ) . " " . $data['amount_currency'];

			if ($r[1]['op'][0] === 'transfer') {
				$transaction = $r[1]['op'][1];
				$transaction['time'] = $r[1]['timestamp'];
				$transaction['transaction'] = $r[1]['trx_id'];

				if ($data['to'] === $transaction['to'] && $data['memo'] === $transaction['memo'] && $amount === $transaction['amount']) {

					$transfer = $transaction;
					break;
				}
			}
		}
		
		// Successfully (no errors in retrieving JSON) searched transaction history for the record.
		update_post_meta($order->get_id(), '_wc_hive_last_searched_for_transaction', date('m/d/Y h:i:s a', time()));
		
		return $transfer;
	}
}
