<?php
/**
 * WC_Steem_Exchange
 *
 * @package WooCommerce Steem Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class WC_Steem_Exchange {

	abstract public function get_exchange_id();

	/**
	 * Query the exchange rates.
	 * Returns bool indicating whether rates were retrieved successfully.
	 *
	 * @since 1.1.0
	 * @return bool
	 */		
	abstract protected function query_rates();
	
	/**
	 * Update the exchange rates. Do not update if it has been queried already within the last hour.
	 * Returns bool indicating whether the rates have been updated.
	 *
	 * @since 1.1.0
	 * @return bool
	 */	
	public function update_rates() {
		$last_successful_query_time = $this->get_last_successful_query_time();
		$one_hour_ago = strtotime("-1 hours");
		
		// If already queried less than an hour ago, do not query again, so that API is not over queried.
		if ($last_successful_query_time !== null && $last_successful_query_time > $one_hour_ago)
			return false;
		
		$success = $this->query_rates();
		
		if ($success)
			$this->set_last_successful_query_time(strtotime('now'));
		
		return $success;
	}
	
	public function get_last_successful_query_time() {
		return $this->get('last_successful_query_time');
	}	
	
	public function get_rate_usd_steem() {
		return $this->get('USD_STEEM');
	}
	
	public function get_rate_usd_sbd() {
		return $this->get('USD_SBD');
	}
	
	protected function set_last_successful_query_time($time) {
		$this->set('last_successful_query_time', $time);
	}	
	
	protected function set_rate_usd_steem($rate) {
		$this->set('USD_STEEM', $rate);
	}
	
	protected function set_rate_usd_sbd($rate) {
		$this->set('USD_SBD', $rate);
	}	
	
	public function get($key, $default = null) {
		if (!function_exists('get_option'))
			return null;
		
		$exchange_id = $this->get_exchange_id();
		
		return get_option("wc_steem_exchange_{$exchange_id}_{$key}", $default);		
	}

	public function set($key, $value) {
		if (!function_exists('update_option'))
			return false;
		
		$exchange_id = $this->get_exchange_id();
		
		return update_option("wc_steem_exchange_{$exchange_id}_{$key}", $value);		
	}	
}	
