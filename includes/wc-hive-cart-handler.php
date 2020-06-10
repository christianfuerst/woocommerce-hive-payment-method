<?php
/**
 * WC_Hive_Cart_Handler
 *
 * @package WooCommerce Hive Payment Method
 * @category Class Handler
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Cart_Handler {

	public static function init() {
		$instance = __CLASS__;

		add_action('woocommerce_after_calculate_totals', array($instance, 'calculate_totals_from_cart'), 30);
	}

	public static function calculate_totals_from_cart($cart) {
		if (empty($cart) || is_wp_error($cart)) {
			return;
		}

		$total = $cart->get_total('edit');

		WC_Hive_Cart_Handler::calculate_totals($total);
	}

	public static function calculate_totals_from_order($order) {
		if (empty($order) || is_wp_error($order)) {
			return;
		}

		$total = $order->get_total('edit');

		WC_Hive_Cart_Handler::calculate_totals($total);
	}

	public static function calculate_totals($total) {
		$amounts = array();
		$from_currency_symbol = wc_hive_get_base_fiat_currency();

		if ($currencies = wc_hive_get_currencies()) {
			foreach ($currencies as $to_currency_symbol => $currency) {
				if (wc_hive_is_accepted_currency($to_currency_symbol)) {
					$amount = wc_hive_rate_convert($total, $from_currency_symbol, $to_currency_symbol);
				
					if ($amount <= 0) {
						continue;
					}
	
					if (WC_Hive::get_amount_currency() == $to_currency_symbol) {
						WC_Hive::set_amount($amount);
					}
	
					$amounts["{$to_currency_symbol}_{$from_currency_symbol}"] = $amount;
				}
			}

			foreach ($currencies as $to_currency_symbol => $currency) {
				if (wc_hive_is_accepted_currency($to_currency_symbol)) {
					if ( ! isset($amounts["{$to_currency_symbol}_{$from_currency_symbol}"])) {
						$amounts["{$to_currency_symbol}_{$from_currency_symbol}"] = $total;
						WC_Hive::set_amount($total);
					}
				}
			}
			
			WC_Hive::set_from_amount($total);
			WC_Hive::set_amounts($amounts);
		}
	}
}

WC_Hive_Cart_Handler::init();