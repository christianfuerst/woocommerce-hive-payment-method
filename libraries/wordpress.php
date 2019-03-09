<?php
/**
 * WordPress Helpers
 *
 * @package WooCommerce Steem Payment Method
 * @category Library
 * @author ReCrypto
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** WordPress helper functions *****************************************************************/

/**
 * Register new WordPress schedules
 *
 * @since 1.0.0
 * @param array $schedules
 * @return array $schedules
 */
function wc_steem_register_schedules($schedules) {
	$schedules['5min'] = array(
		'interval' => 5 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 5 minutes' )
	);
	
	$schedules['2min'] = array(
		'interval' => 2 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 2 minutes' )
	);	

	return $schedules;
}
add_filter('cron_schedules', 'wc_steem_register_schedules');