<?php
/**
 * Plugin Name: WooCommerce Hive Payment Method
 * Plugin URI: https://github.com/roomservice/woocommerce-hive-payment-method
 * Description: Accept Hive payments directly to your shop (Currencies: HIVE, HBD, HIVE-ENGINE).
 * Version: 1.3.0
 * Author: <a href="https://peakd.com/@roomservice">roomservice</a>, <a href="https://peakd.com/@sagescrub">sagescrub</a>, <a href="https://peakd.com/@recrypto">ReCrypto</a>
 * Requires at least: 4.1
 * Tested up to: 5.4.1
 *
 * WC requires at least: 3.1
 * WC tested up to: 4.2.0
 *
 * Text Domain: wc-hive-payment-method
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define('WC_HIVE_VERSION', '1.2.0');
define('WC_HIVE_DIR_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('WC_HIVE_DIR_URL', trailingslashit(plugin_dir_url(__FILE__)));

register_activation_hook(__FILE__, 'wc_hive_activate');
register_deactivation_hook(__FILE__, 'wc_hive_deactivate');

/** 
 * Plugin activation
 *
 * @since 1.0.0
 */
function wc_hive_activate() {
	do_action('wc_hive_activated');

	$settings = get_option('woocommerce_wc_hive_settings', array());

	if ( ! isset($settings['accepted_currencies'])) {
		$settings['accepted_currencies'] = array(
			'HIVE',
			'HBD',
		);
	}

	update_option('woocommerce_wc_hive_settings', $settings);
}

/**
 * Plugin deactivation
 *
 * @since 1.0.0
 */
function wc_hive_deactivate() {
	do_action('wc_hive_deactivated');

	// Remove the options from the database
	delete_option('wc_hive_fiat_to_fiat_exchange_rates');
	delete_option('wc_hive_fiat_to_fiat_exchange_rates_last_successful_query_time');
	delete_option('wc_hive_fiat_to_hive_exchange_rates');

	delete_option('wc_hive_exchange_bittrex_USD_HBD');
	delete_option('wc_hive_exchange_bittrex_USD_HIVE');

	delete_option('wc_hive_exchange_binance_USD_HBD');
	delete_option('wc_hive_exchange_binance_USD_HIVE');

	delete_option('wc_hive_exchange_hiveengine_RATES');
	delete_option('wc_hive_exchange_hiveengine_CURRENCIES');
	delete_option('wc_hive_exchange_hiveengine_PRECISIONS');

	delete_option('wc_hive_exchange_binance_last_successful_query_time');
	delete_option('wc_hive_exchange_bittrex_last_successful_query_time');
	
	// Remove legacy rates option from pre 1.1.0 version of this plugin.
	delete_option('wc_hive_rates');
}

/**
 * Plugin init
 * 
 * @since 1.0.0
 */
function wc_hive_init() {

	/**
	 * Fires before including the files
	 *
	 * @since 1.0.0
	 */
	do_action('wc_hive_pre_init');

	require_once(WC_HIVE_DIR_PATH . 'libraries/wordpress.php');
	require_once(WC_HIVE_DIR_PATH . 'libraries/woocommerce.php');

	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-functions.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/class-wc-hive.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/class-wc-hive-transaction-transfer.php');

	require_once(WC_HIVE_DIR_PATH . 'includes/class-wc-gateway-hive.php');

	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-handler.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-cart-handler.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-checkout-handler.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-order-handler.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-product-handler.php');
	require_once(WC_HIVE_DIR_PATH . 'includes/wc-hive-rates-handler.php');	
	require_once(WC_HIVE_DIR_PATH . 'includes/exchanges/wc-hive-exchange.php');	
	require_once(WC_HIVE_DIR_PATH . 'includes/exchanges/wc-hive-exchange-bittrex.php');	
	require_once(WC_HIVE_DIR_PATH . 'includes/exchanges/wc-hive-exchange-binance.php');	
	require_once(WC_HIVE_DIR_PATH . 'includes/exchanges/wc-hive-exchange-hiveengine.php');	

	/**
	 * Fires after including the files
	 *
	 * @since 1.0.0
	 */
	do_action('wc_hive_init');
}
add_action('plugins_loaded', 'wc_hive_init');



/**
 * Register "WooCommerce Hive" as payment gateway in WooCommerce
 *
 * @since 1.0.0
 *
 * @param array $gateways
 * @return array $gateways
 */
function wc_hive_register_gateway($gateways) {
	$gateways[] = 'WC_Gateway_Hive';

	return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_hive_register_gateway');
