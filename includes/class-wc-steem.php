<?php
/**
 * WC_Steem
 *
 * @package WooCommerce Steem Payment Method
 * @category Class
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WC_Steem {

	public static function init() {
		$instance = __CLASS__;

		add_action('woocommerce_set_cart_cookies', array($instance, 'wc_set_cart_cookies'), 30);		
	}
	
	public static function wc_set_cart_cookies($set) {
		// If the session variables have not ben set yet for this cart
		if (self::get('amount_currency') == null) {
			// Initialize WC_Steem variables for the new cart.
			WC_Steem::reset();
		}
	}	

	# Setters

	public static function set_payee($payee) {
		self::set('payee', $payee);
	}

	public static function set_amount($amount) {
		self::set('amount', $amount);
	}

	public static function set_amount_currency($amount_currency) {
		self::set('amount_currency', $amount_currency);
	}

	public static function set_memo($memo = '') {
		self::set('memo', $memo ? $memo : self::generate_memo());
	}
	
	public static function set_from_amount($from_amount) {
		self::set('from_amount', $from_amount);
	}	
	
	public static function set_from_currency($from_currency) {
		self::set('from_currency', $from_currency);
	}

	public static function set_exchange_rate($exchange_rate) {
		self::set('exchange_rate', $exchange_rate);
	}	
	
	public static function set_amounts($amounts) {
		self::set('amounts', $amounts);
	}		

	public static function generate_memo($length = 10) {
		$memo = null;

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$maximum_indexes = strlen($characters) - 1;

		for ($i = 0; $i < $length; $i++) {
			$memo .= $characters[mt_rand(0, $maximum_indexes)];
		}

		return apply_filters('wc_steem_generate_memo', $memo);
	}


	# Getters

	public static function get_payee() {
		return self::get('payee');
	}

	public static function get_amount() {
		return self::get('amount');
	}

	public static function get_amount_currency() {
		return self::get('amount_currency');
	}

	public static function get_memo() {
		return self::get('memo');
	}
	
	public static function get_from_amount() {
		return self::get('from_amount');
	}	

	public static function get_from_currency() {
		return self::get('from_currency');
	}

	public static function get_exchange_rate() {
		return self::get('exchange_rate');
	}
	
	public static function get_amounts() {
		return self::get('amounts');
	}
	

	# Helpers

	public static function get($key) {
		return WC()->session->get("wc_steem_{$key}");
	}

	public static function set($key, $value) {
		return WC()->session->set("wc_steem_{$key}", $value);
	}

	public static function reset() {
		$keys = array(
			'payee',
			'amount',
			'amount_currency',
			'memo',
			'amounts',
			'from_amount',
			'from_currency',
			'exchange_rate',
		);

		foreach ($keys as $key) {
			self::set($key, '');
		}

		self::set('amount_currency', 'STEEM');
	}
}

WC_Steem::init();