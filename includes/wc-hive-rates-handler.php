<?php
/**
 * WC_Hive_Rates_Handler
 *
 * @package WooCommerce Hive Payment Method
 * @category Class Handler
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Rates_Handler {

	/**
	 * Update the exchange rates. Do not update if it has been queried already within the last hour.
	 * Returns bool indicating whether the rates have been updated.
	 *
	 * @since 1.1.0
	 * @return bool
	 */	
	public function update_rates() {
		$exchanges = array();
		
		// Queue up exchanges that will be queried for rates
		array_push($exchanges,
			new WC_Hive_Exchange_Bittrex(),
			new WC_Hive_Exchange_Binance()
		);
		
		// Will indicate whether any of the exchanges were queried in this round.
		// If only cached data was retreived rather than querying the exchanges directly, 
		// $crypto_exchanges_updated will be false.
		$crypto_exchanges_updated = false;
		
		// Query the rates for each exchange, if cache has expired
		foreach ($exchanges as $exchange) {
			// If the cache was expired and rates were successfully retrieved directly from the exchange
			if ($exchange->update_rates())
				$crypto_exchanges_updated = true;
		}
		
		// Indicates whether the fiat exchange rates were queried directly, rather than retrieved from cached.
		$fiat_to_fiat_exchange_rates_updated = $this->query_fiat_to_fiat_exchange_rates();
		
		// If none of the exchange rates were updated, return false to indicate that no updates have been made.
		if (!$crypto_exchanges_updated && !$fiat_to_fiat_exchange_rates_updated)
			return false;
		
		$hive_usd_average = 0;
		$hbd_usd_average = 0;
		
		$hive_count = 0;
		$hbd_count = 0;
		
		// Sum up the HIVE/HBD exchange rates from each crypto exchange, to be averaged.
		foreach ($exchanges as $exchange) {
			$rate_usd_hive = $exchange->get_rate_usd_hive();
			$rate_usd_hbd = $exchange->get_rate_usd_hbd();
			
			if ($rate_usd_hive != null && $rate_usd_hive > 0) {
				$hive_usd_average += $rate_usd_hive;
				$hive_count++;
			}
			
			if ($rate_usd_hbd != null && $rate_usd_hbd > 0) {
				$hbd_usd_average += $rate_usd_hbd;
				$hbd_count++;
			}	
		}
		
		// If no HIVE or HBD rates were discovered
		if ($hive_count == 0 && $hbd_count == 0)
			return false;
		
		if ($hive_count > 0)
			// Calculate average USD to HIVE rate
			$hive_usd_average = $hive_usd_average / $hive_count;
		
		if ($hbd_count > 0)
			// Calculate average USD to HBD rate
			$hbd_usd_average = $hbd_usd_average / $hbd_count;

		// Get most recent FIAT to FIAT exchange rates
		$fiat_to_fiat_exchange_rates = $this->get_fiat_to_fiat_exchange_rates();
		// Get most recent FIAT to HIVE/HBD exchange rates
		$fiat_to_hive_exchange_rates = $this->get_fiat_to_hive_exchange_rates();
		
		// Build the FIAT to HIVE/HBD exchange rates
		foreach ($fiat_to_fiat_exchange_rates as $to_fiat_currency_symbol => $to_fiat_currency_value) {
			$fiat_to_hive_exchange_rates["USD_{$to_fiat_currency_symbol}"] = $to_fiat_currency_value;

			$fiat_to_hive_exchange_rates["{$to_fiat_currency_symbol}_HIVE"] = $hive_usd_average * $to_fiat_currency_value;

			$fiat_to_hive_exchange_rates["{$to_fiat_currency_symbol}_HBD"] = $hbd_usd_average * $to_fiat_currency_value;
		}			
		
		// Cache rates
		$this->set_fiat_to_hive_exchange_rates($fiat_to_hive_exchange_rates);
				
		return true;
	}
	
	public function get_fiat_to_hive_exchange_rate($from_fiat_symbol, $to_hive_hbd_symbol) {
		$fiat_to_hive_exchange_rates = $this->get_fiat_to_hive_exchange_rates();
		
		if ($fiat_to_hive_exchange_rates === null || !is_array($fiat_to_hive_exchange_rates))
			return null;
		
		if (!isset($fiat_to_hive_exchange_rates["{$from_fiat_symbol}_{$to_hive_hbd_symbol}"]))
			return null;
		
		return $fiat_to_hive_exchange_rates["{$from_fiat_symbol}_{$to_hive_hbd_symbol}"];
	}

	private function get_fiat_to_hive_exchange_rates() {
		return get_option('wc_hive_fiat_to_hive_exchange_rates', array());
	}	
	
	private function get_fiat_to_fiat_exchange_rates() {
		return get_option('wc_hive_fiat_to_fiat_exchange_rates', array());
	}
	
	private function get_fiat_exchange_rates_last_successful_query_time() {
		return get_option('wc_hive_fiat_to_fiat_exchange_rates_last_successful_query_time', null);
	}
	
	private function set_fiat_to_hive_exchange_rates($fiat_to_hive_exchange_rates) {
		update_option('wc_hive_fiat_to_hive_exchange_rates', $fiat_to_hive_exchange_rates);
	}	

	private function set_fiat_to_fiat_exchange_rates($fiat_to_fiat_exchange_rates) {
		update_option('wc_hive_fiat_to_fiat_exchange_rates', $fiat_to_fiat_exchange_rates);
	}
	
	private function set_fiat_to_fiat_exchange_rates_last_successful_query_time($time) {
		update_option('wc_hive_fiat_to_fiat_exchange_rates_last_successful_query_time', $time);
	}		
	
	/**
	 * Query the fiat exchange rates. Do not query if it has been queried already within the last hour.
	 * Returns bool indicating whether the rates have been updated.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	private function query_fiat_to_fiat_exchange_rates() {
		$fiat_to_fiat_exchange_rates_last_successful_query_time = $this->get_fiat_exchange_rates_last_successful_query_time();
		$one_hour_ago = strtotime("-1 hours");

		// If already queried less than an hour ago, do not query again, so that API is not over queried.
		if ($fiat_to_fiat_exchange_rates_last_successful_query_time !== null && $fiat_to_fiat_exchange_rates_last_successful_query_time > $one_hour_ago)
			return false;
		
		$fiat_to_fiat_exchange_rates = $this->get_fiat_to_fiat_exchange_rates();		

 		$response = wp_remote_get('https://api.exchangeratesapi.io/latest?base=USD');

		if (!is_array($response))
			return false;
		
		$data = json_decode(wp_remote_retrieve_body($response), true);

		if (!isset($data['rates']) || !$data['rates'])
			return false;
			
		$fiat_to_fiat_exchange_rates = $data['rates'];

		$this->set_fiat_to_fiat_exchange_rates($fiat_to_fiat_exchange_rates);
		$this->set_fiat_to_fiat_exchange_rates_last_successful_query_time(strtotime('now'));
		
		return true;
	}
}	
