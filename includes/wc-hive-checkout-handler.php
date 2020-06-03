<?php
/**
 * WC_Hive_Checkout_Handler
 *
 * @package WooCommerce Hive Payment Method
 * @category Class Handler
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Checkout_Handler {

	public static function init() {
		$instance = __CLASS__;

		add_action('wp_enqueue_scripts', array($instance, 'enqueue_scripts'));
	}

	public static function enqueue_scripts() {
		// Calculate Totals and initialize totals in WC_Hive if we are coming from the 
		// order-pay endpoint. This will handle the scenario of paying for subscription
		// renewals that do not have a cart initialized, but instead have an order established
		// for the renewal.
		// TODO: Currently this is placed in enqueue_scripts to that WC_Hive is initialized before the script
		// data is passed below. This could be moved higher up the stack.
		$order_pay = get_query_var('order-pay');
		if (is_wc_endpoint_url( 'order-pay' ) && !empty($order_pay)) {
			$order = wc_get_order($order_pay);

			if (isset($order) && !is_wp_error($order)) {
				// Get fresh exchange rate and amount for this order
				WC_Gateway_Hive::update_order_exchange_rate_and_amount($order->get_id());

				// Calculate totals and store them in WC_Hive
				WC_Hive_Cart_Handler::calculate_totals_from_order($order);
			}
		}

		// Plugin
		wp_enqueue_script('wc-hive', WC_HIVE_DIR_URL . '/assets/js/plugin.js', array('jquery'), WC_HIVE_VERSION);

		// Localize plugin script data
		wp_localize_script('wc-hive', 'wc_hive', array(
			'cart' => array(
				'base_currency' => wc_hive_get_base_fiat_currency(),
				'amounts' => WC_Hive::get_amounts(),
			),
		));
	}
}

WC_Hive_Checkout_Handler::init();

