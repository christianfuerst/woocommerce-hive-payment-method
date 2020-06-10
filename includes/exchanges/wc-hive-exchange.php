<?php
/**
 * WC_Hive_Exchange
 *
 * @package WooCommerce Hive Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

abstract class WC_Hive_Exchange {

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
	 * Force update the exchange rates.
	 * Returns bool indicating whether the rates have been updated.
	 *
	 * @since 1.1.0
	 * @return bool
	 */	
	public function update_rates_force() {	
		$success = $this->query_rates();
		
		return $success;
	}

	/**
	 * Update the exchange rates. Do not update if it has been queried already within the last ten minutes.
	 * Returns bool indicating whether the rates have been updated.
	 *
	 * @since 1.1.0
	 * @return bool
	 */	
	public function update_rates() {
		$last_successful_query_time = $this->get_last_successful_query_time();
		$ten_minutes_ago = strtotime("-10 minutes");
		
		// If already queried less than ten minutes ago, do not query again, so that API is not over queried.
		if ($last_successful_query_time !== null && $last_successful_query_time > $ten_minutes_ago)
			return false;
		
		$success = $this->query_rates();
		
		if ($success)
			$this->set_last_successful_query_time(strtotime('now'));
		
		return $success;
	}
	
	public function get_last_successful_query_time() {
		return $this->get('last_successful_query_time');
	}	
	
	public function get_rate_usd_hive() {
		return $this->get('USD_HIVE');
	}
	
	public function get_rate_usd_hbd() {
		return $this->get('USD_HBD');
	}

	public function get_currencies_hiveengine() {
		return $this->get('CURRENCIES');
	}

	public function get_rates_hiveengine() {
		return $this->get('RATES');
	}

	public function get_precisions_hiveengine() {
		return $this->get('PRECISIONS');
	}
	
	protected function set_last_successful_query_time($time) {
		$this->set('last_successful_query_time', $time);
	}	
	
	protected function set_rate_usd_hive($rate) {
		$this->set('USD_HIVE', $rate);
	}
	
	protected function set_rate_usd_hbd($rate) {
		$this->set('USD_HBD', $rate);
	}	

	protected function set_currencies_hiveengine($currencies) {
		$this->set('CURRENCIES', $currencies);
	}

	protected function set_rates_hiveengine($rates) {
		$this->set('RATES', $rates);
	}

	protected function set_precisions_hiveengine($precisions) {
		$this->set('PRECISIONS', $precisions);
	}	
	
	public function get($key, $default = null) {
		if (!function_exists('get_option'))
			return null;
		
		$exchange_id = $this->get_exchange_id();
		
		return get_option("wc_hive_exchange_{$exchange_id}_{$key}", $default);		
	}

	public function set($key, $value) {
		if (!function_exists('update_option'))
			return false;
		
		$exchange_id = $this->get_exchange_id();
		
		return update_option("wc_hive_exchange_{$exchange_id}_{$key}", $value);		
	}	
}	
