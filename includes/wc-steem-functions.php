<?php
/**
 * WooCommerce Steem Helpers
 *
 * @package WooCommerce Steem Payment Method
 * @category Helper
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve Steem currencies
 *
 * @since 1.0.0
 * @return array
 */
function wc_steem_get_currencies() {
	return apply_filters('wc_steem_currencies', array(
		'STEEM' => 'Steem',
		'SBD' => 'Steem Backed Dollar',
	));
}

/**
 * Retrieve payment method settings
 *
 * @since 1.0.0
 * @return array
 */
function wc_steem_get_settings() {
	return get_option('woocommerce_wc_steem_settings', array());
}

/**
 * Retrieve single payment method settings
 *
 * @since 1.0.0
 * @return mixed
 */
function wc_steem_get_setting($key) {
	$settings = wc_steem_get_settings();

	return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Retrieve Steem accepted currencies
 *
 * @since 1.0.0
 * @return array
 */
function wc_steem_get_accepted_currencies() {
	$accepted_currencies = wc_steem_get_setting('accepted_currencies');

	return apply_filters('wc_steem_accepted_currencies', $accepted_currencies ? $accepted_currencies : array());
}

/**
 * Check if the Steem payment method settings has accepted currencies
 *
 * @since 1.0.0
 * @return array
 */
function wc_steem_has_accepted_currencies() {
	$currencies = wc_steem_get_accepted_currencies();
	return ( ! empty($currencies));
}

/**
 * Check currency is accepted on Steem payment method
 *
 * @since 1.0.0
 * @param string $currency_symbol
 * @return boolean
 */
function wc_steem_is_accepted_currency($currency_symbol) {
	$currencies = wc_steem_get_accepted_currencies();
	return in_array($currency_symbol, $currencies);
}


# Fiat

/**
 * Retrieve shop's base fiat currency symbol.
 *
 * @since 1.0.1
 * @return string $store_currency_symbol
 */
function wc_steem_get_base_fiat_currency() {
	$store_currency_symbol = wc_steem_get_currency_symbol();
	
	// Allow accepted STEEM currencies (e.g. STEEM or SBD selected in plugin settings) or accepted fiat currencies.
	// If the WooCommerce store currency is neither, then default to USD.
	if ( ! wc_steem_is_accepted_currency( $store_currency_symbol ) && ! in_array($store_currency_symbol, wc_steem_get_accepted_fiat_currencies())) {
		$store_currency_symbol = apply_filters('wc_steem_base_default_fiat_currency', 'USD');
	}

	return apply_filters('wc_steem_base_fiat_currency', $store_currency_symbol);
}

/**
 * Retrieve list of accept fiat currencies
 *
 * @since 1.0.1
 * @return array
 */
function wc_steem_get_accepted_fiat_currencies() {
	return apply_filters('wc_steem_accepted_fiat_currencies', array(
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
function wc_steem_is_accepted_fiat_currency($currency_symbol) {
	$currencies = wc_steem_get_accepted_fiat_currencies();
	return in_array($currency_symbol, $currencies);
}


# Rates

/**
 * Convert the amount from FIAT to crypto amount
 *
 * @since 1.0.0
 * @param float $amount
 * @param string $from_fiat_currency_symbol
 * @param string $to_steem_sbd_currency_symbol
 * @return float
 */
function wc_steem_rate_convert($amount, $from_fiat_currency_symbol, $to_steem_sbd_currency_symbol) {
	// If from and to currency symbols are the same, return the same amount.
	if ( strcmp( strtoupper( $from_fiat_currency_symbol ), strtoupper( $to_steem_sbd_currency_symbol ) ) == 0 )
		return $amount;
	
	$rates_handler = new WC_Steem_Rates_Handler();

	$rate = $rates_handler->get_fiat_to_steem_exchange_rate($from_fiat_currency_symbol, $to_steem_sbd_currency_symbol);

	$rate = apply_filters(
		'wc_steem_rate', 
		$rate, 
		$from_fiat_currency_symbol, 
		$to_steem_sbd_currency_symbol
	);
	
	return apply_filters(
		'wc_steem_rate_convert', 
		($rate > 0 ? round($amount / $rate, 3, PHP_ROUND_HALF_UP) : 0), 
		$amount, 
		$from_fiat_currency_symbol, 
		$to_steem_sbd_currency_symbol
	);
}


# Order functions

/**
 * Retrieve order's Steem payee username
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_steem_payee($order_id) {
	return apply_filters('wc_order_steem_payee', get_post_meta($order_id, '_wc_steem_payee', true), $order_id);
}

/**
 * Retrieve order's Steem memo
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_steem_memo($order_id) {
	return apply_filters('wc_order_steem_memo', get_post_meta($order_id, '_wc_steem_memo', true), $order_id);
}

/**
 * Retrieve order's Steem amount
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_steem_amount($order_id) {
	return apply_filters('wc_order_steem_amount', get_post_meta($order_id, '_wc_steem_amount', true), $order_id);
}

/**
 * Retrieve order's Steem amount currency
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_steem_amount_currency($order_id) {
	return apply_filters('wc_order_steem_amount_currency', get_post_meta($order_id, '_wc_steem_amount_currency', true), $order_id);
}

/**
 * Retrieve order's Steem status
 *
 * @since 1.0.0
 * @param int $order_id
 * @return string
 */
function wc_order_get_steem_status($order_id) {
	return apply_filters('wc_order_steem_status', get_post_meta($order_id, '_wc_steem_status', true), $order_id);
}
