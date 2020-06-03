<?php
/**
 * WC_Hive_Exchange_Bittrex
 *
 * @package WooCommerce Hive Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Exchange_Bittrex extends WC_Hive_Exchange {
	
	public function get_exchange_id() {
		return 'bittrex';
	}

	/**
	 * Query the exchange rates.
	 * Returns bool indicating whether rates were retrieved successfully.
	 *
	 * @since 1.1.0
	 * @return bool
	 */			
	public function query_rates() {
		$usd_btc = (float)$this->query_rate('USD', 'BTC');
		$btc_hive = (float)$this->query_rate('BTC', 'HIVE');
		$btc_hbd = (float)$this->query_rate('BTC', 'HBD');
		
		// Rates that were expected were not found.
		if ($usd_btc === null || $btc_hive === null || $btc_hbd === null)
			return false;
		
		$usd_hive = $usd_btc * $btc_hive;
		$usd_hbd = $usd_btc * $btc_hbd;
		
		$this->set_rate_usd_hive($usd_hive);		
		$this->set_rate_usd_hbd($usd_hbd);
		$this->set_last_successful_query_time(strtotime('now'));
		
		return true;		
	}
	
	private function query_rate($from_symbol, $to_symbol) {
		// https://bittrex.github.io/api/v1-1
		
		$response = wp_remote_get("https://api.bittrex.com/api/v1.1/public/getticker?market={$from_symbol}-${to_symbol}");
		
		if (!is_array($response))
			return null;

		$data = json_decode(wp_remote_retrieve_body($response), true);
		
		if (!isset($data['success']) || $data['success'] != 'true')
			return null;
		
		if (!isset($data['result']))
			return null;

		if (!isset($data['result']['Last']))
			return null;
		
		return $data['result']['Last'];
	}
}