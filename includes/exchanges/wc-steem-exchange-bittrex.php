<?php
/**
 * WC_Steem_Exchange_Bittrex
 *
 * @package WooCommerce Steem Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Steem_Exchange_Bittrex extends WC_Steem_Exchange {
	
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
		$btc_steem = (float)$this->query_rate('BTC', 'STEEM');
		$btc_sbd = (float)$this->query_rate('BTC', 'SBD');
		
		// Rates that were expected were not found.
		if ($usd_btc === null || $btc_steem === null || $btc_sbd === null)
			return false;
		
		$usd_steem = $usd_btc * $btc_steem;
		$usd_sbd = $usd_btc * $btc_sbd;
		
		$this->set_rate_usd_steem($usd_steem);		
		$this->set_rate_usd_sbd($usd_sbd);
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