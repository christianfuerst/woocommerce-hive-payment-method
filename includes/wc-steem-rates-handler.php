<?php
/**
 * WC_Steem_Rates_Handler
 *
 * @package WooCommerce Steem Payment Method
 * @category Class Handler
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Steem_Rates_Handler {

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
			new WC_Steem_Exchange_Poloniex(),
			new WC_Steem_Exchange_Bittrex(),
			new WC_Steem_Exchange_Binance()
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
		
		$steem_usd_average = 0;
		$sbd_usd_average = 0;
		
		$steem_count = 0;
		$sbd_count = 0;
		
		// Sum up the STEEM/SBD exchange rates from each crypto exchange, to be averaged.
		foreach ($exchanges as $exchange) {
			$rate_usd_steem = $exchange->get_rate_usd_steem();
			$rate_usd_sbd = $exchange->get_rate_usd_sbd();
			
			if ($rate_usd_steem != null && $rate_usd_steem > 0) {
				$steem_usd_average += $rate_usd_steem;
				$steem_count++;
			}
			
			if ($rate_usd_sbd != null && $rate_usd_sbd > 0) {
				$sbd_usd_average += $rate_usd_sbd;
				$sbd_count++;
			}	
		}
		
		// If no STEEM or SBD rates were discovered
		if ($steem_count == 0 && $sbd_count == 0)
			return false;
		
		if ($steem_count > 0)
			// Calculate average USD to STEEM rate
			$steem_usd_average = $steem_usd_average / $steem_count;
		
		if ($sbd_count > 0)
			// Calculate average USD to SBD rate
			$sbd_usd_average = $sbd_usd_average / $sbd_count;

		// Get most recent FIAT to FIAT exchange rates
		$fiat_to_fiat_exchange_rates = $this->get_fiat_to_fiat_exchange_rates();
		// Get most recent FIAT to STEEM/SBD exchange rates
		$fiat_to_steem_exchange_rates = $this->get_fiat_to_steem_exchange_rates();
		
		// Build the FIAT to STEEM/SBD exchange rates
		foreach ($fiat_to_fiat_exchange_rates as $to_fiat_currency_symbol => $to_fiat_currency_value) {
			$fiat_to_steem_exchange_rates["USD_{$to_fiat_currency_symbol}"] = $to_fiat_currency_value;

			$fiat_to_steem_exchange_rates["{$to_fiat_currency_symbol}_STEEM"] = $steem_usd_average * $to_fiat_currency_value;

			$fiat_to_steem_exchange_rates["{$to_fiat_currency_symbol}_SBD"] = $sbd_usd_average * $to_fiat_currency_value;
		}			
		
		// Cache rates
		$this->set_fiat_to_steem_exchange_rates($fiat_to_steem_exchange_rates);
				
		return true;
	}
	
	public function get_fiat_to_steem_exchange_rate($from_fiat_symbol, $to_steem_sbd_symbol) {
		$fiat_to_steem_exchange_rates = $this->get_fiat_to_steem_exchange_rates();
		
		if ($fiat_to_steem_exchange_rates === null || !is_array($fiat_to_steem_exchange_rates))
			return null;
		
		if (!isset($fiat_to_steem_exchange_rates["{$from_fiat_symbol}_{$to_steem_sbd_symbol}"]))
			return null;
		
		return $fiat_to_steem_exchange_rates["{$from_fiat_symbol}_{$to_steem_sbd_symbol}"];
	}

	private function get_fiat_to_steem_exchange_rates() {
		return get_option('wc_steem_fiat_to_steem_exchange_rates', array());
	}	
	
	private function get_fiat_to_fiat_exchange_rates() {
		return get_option('wc_steem_fiat_to_fiat_exchange_rates', array());
	}
	
	private function get_fiat_exchange_rates_last_successful_query_time() {
		return get_option('wc_steem_fiat_to_fiat_exchange_rates_last_successful_query_time', null);
	}
	
	private function set_fiat_to_steem_exchange_rates($fiat_to_steem_exchange_rates) {
		update_option('wc_steem_fiat_to_steem_exchange_rates', $fiat_to_steem_exchange_rates);
	}	

	private function set_fiat_to_fiat_exchange_rates($fiat_to_fiat_exchange_rates) {
		update_option('wc_steem_fiat_to_fiat_exchange_rates', $fiat_to_fiat_exchange_rates);
	}
	
	private function set_fiat_to_fiat_exchange_rates_last_successful_query_time($time) {
		update_option('wc_steem_fiat_to_fiat_exchange_rates_last_successful_query_time', $time);
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
