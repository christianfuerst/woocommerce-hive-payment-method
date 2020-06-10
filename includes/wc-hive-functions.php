<?php
/**
 * WooCommerce Hive Helpers
 *
 * @package WooCommerce Hive Payment Method
 * @category Helper
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve Hive currencies
 *
 * @since 1.0.0
 * @return array
 */
function wc_hive_get_currencies() {
	$exchange = new WC_Hive_Exchange_HiveEngine();
	$hiveengine_currencies = $exchange->get_currencies_hiveengine();

	$currencies = array(
		'HIVE' => 'Hive',
		'HBD' => 'Hive Backed Dollar'
	);

	foreach ($hiveengine_currencies as $index => $element ) {
		$currencies[$index] = $element;
	}

	return apply_filters('wc_hive_currencies', $currencies);
}

/**
 * Retrieve payment method settings
 *
 * @since 1.0.0
 * @return array
 */
function wc_hive_get_settings() {
	return get_option('woocommerce_wc_hive_settings', array());
}

/**
 * Retrieve single payment method settings
 *
 * @since 1.0.0
 * @return mixed
 */
function wc_hive_get_setting($key) {
	$settings = wc_hive_get_settings();

	return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Retrieve Hive accepted currencies
 *
 * @since 1.0.0
 * @return array
 */
function wc_hive_get_accepted_currencies() {
	$accepted_currencies = wc_hive_get_setting('accepted_currencies');

	return apply_filters('wc_hive_accepted_currencies', $accepted_currencies ? $accepted_currencies : array());
}

/**
 * Check if the Hive payment method settings has accepted currencies
 *
 * @since 1.0.0
 * @return array
 */
function wc_hive_has_accepted_currencies() {
	$currencies = wc_hive_get_accepted_currencies();
	return ( ! empty($currencies));
}

/**
 * Check currency is accepted on Hive payment method
 *
 * @since 1.0.0
 * @param string $currency_symbol
 * @return boolean
 */
function wc_hive_is_accepted_currency($currency_symbol) {
	$currencies = wc_hive_get_accepted_currencies();
	return in_array($currency_symbol, $currencies);
}


# Fiat

/**
 * Retrieve shop's base fiat currency symbol.
 *
 * @since 1.0.1
 * @return string $store_currency_symbol
 */
function wc_hive_get_base_fiat_currency() {
	$store_currency_symbol = wc_hive_get_currency_symbol();
	
	// Allow accepted HIVE currencies (e.g. HIVE or HBD selected in plugin settings) or accepted fiat currencies.
	// If the WooCommerce store currency is neither, then default to USD.
	if ( ! wc_hive_is_accepted_currency( $store_currency_symbol ) && ! in_array($store_currency_symbol, wc_hive_get_accepted_fiat_currencies())) {
		$store_currency_symbol = apply_filters('wc_hive_base_default_fiat_currency', 'USD');
	}

	return apply_filters('wc_hive_base_fiat_currency', $store_currency_symbol);
}

/**
 * Retrieve list of accept fiat currencies
 *
 * @since 1.0.1
 * @return array
 */
function wc_hive_get_accepted_fiat_currencies() {
	return apply_filters('wc_hive_accepted_fiat_currencies', array(
		'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'GBP', 'HKD', 'HRK', 'HUF', 'IDR', 'ILS', 'INR', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'ZAR', 'EUR'
	));
}

/**
 * Check fiat currency is accepted on WooCommerce shop
 *
 * @since 1.0.1
 * @param string $currency_symbol
 * @return boolean
 */
function wc_hive_is_accepted_fiat_currency($currency_symbol) {
	$currencies = wc_hive_get_accepted_fiat_currencies();
	return in_array($currency_symbol, $currencies);
}


# Rates

/**
 * Convert the amount from FIAT to crypto amount
 *
 * @since 1.0.0
 * @param float $amount
 * @param string $from_fiat_currency_symbol
 * @param string $to_hive_hbd_currency_symbol
 * @return float
 */
function wc_hive_rate_convert($amount, $from_fiat_currency_symbol, $to_hive_hbd_currency_symbol) {
	// If from and to currency symbols are the same, return the same amount.
	if ( strcmp( strtoupper( $from_fiat_currency_symbol ), strtoupper( $to_hive_hbd_currency_symbol ) ) == 0 )
		return $amount;
	
	$rates_handler = new WC_Hive_Rates_Handler();
	$exchange = new WC_Hive_Exchange_HiveEngine();

	$rate = $rates_handler->get_fiat_to_hive_exchange_rate($from_fiat_currency_symbol, $to_hive_hbd_currency_symbol);

	$rate = apply_filters(
		'wc_hive_rate', 
		$rate, 
		$from_fiat_currency_symbol, 
		$to_hive_hbd_currency_symbol
	);
	
	$precision = 3;
	if ($to_hive_hbd_currency_symbol != 'HIVE' && $to_hive_hbd_currency_symbol != 'HBD') {
		$precisions = $exchange->get_precisions_hiveengine();
		$precision = $precisions[$to_hive_hbd_currency_symbol];
	}

	return apply_filters(
		'wc_hive_rate_convert', 
		($rate > 0 ? round($amount / $rate, $precision, PHP_ROUND_HALF_UP) : 0), 
		$amount, 
		$from_fiat_currency_symbol, 
		$to_hive_hbd_currency_symbol
	);
}

# Order functions

/**
 * Retrieve order's Hive payee username
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_hive_payee($order_id) {
	return apply_filters('wc_order_hive_payee', get_post_meta($order_id, '_wc_hive_payee', true), $order_id);
}

/**
 * Retrieve order's Hive memo
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_hive_memo($order_id) {
	return apply_filters('wc_order_hive_memo', get_post_meta($order_id, '_wc_hive_memo', true), $order_id);
}

/**
 * Retrieve order's Hive amount
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_hive_amount($order_id) {
	return apply_filters('wc_order_hive_amount', get_post_meta($order_id, '_wc_hive_amount', true), $order_id);
}

/**
 * Retrieve order's Hive amount currency
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_hive_amount_currency($order_id) {
	return apply_filters('wc_order_hive_amount_currency', get_post_meta($order_id, '_wc_hive_amount_currency', true), $order_id);
}

/**
 * Retrieve order's Hive status
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_hive_status($order_id) {
	return apply_filters('wc_order_hive_status', get_post_meta($order_id, '_wc_hive_status', true), $order_id);
}
