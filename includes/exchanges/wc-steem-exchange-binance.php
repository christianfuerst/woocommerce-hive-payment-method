<?php
/**
 * WC_Steem_Exchange_Binance
 *
 * @package WooCommerce Steem Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Steem_Exchange_Binance extends WC_Steem_Exchange {
	
	public function get_exchange_id() {
		return 'binance';
	}

	/**
	 * Query the exchange rates.
	 * Returns bool indicating whether rates were retrieved successfully.
	 *
	 * @since 1.1.0
	 * @return bool
	 */		
	public function query_rates() {
		$usd_btc = (float)$this->query_rate('USDT', 'BTC');
		$btc_steem = (float)$this->query_rate('BTC', 'STEEM');
		$btc_sbd = null; // SBD is not yet supported by binance.
		
		// Rates that were expected were not found
		if ($usd_btc === null || $btc_steem === null)
			return false;
		
		$usd_steem = $usd_btc * $btc_steem;
		
		$this->set_rate_usd_steem($usd_steem);		
		$this->set_last_successful_query_time(strtotime('now'));
		
		return true;		
	}
	
	private function query_rate($from_symbol, $to_symbol) {
		// https://github.com/binance-exchange/binance-official-api-docs/blob/master/rest-api.md
		
		$response = wp_remote_get("https://api.binance.com/api/v3/avgPrice?symbol=${to_symbol}{$from_symbol}");
		
		if (!is_array($response))
			return null;
		
		$data = json_decode(wp_remote_retrieve_body($response), true);
				
		if (!isset($data['price']))
			return null;

		return $data['price'];
	}
}