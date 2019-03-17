<?php
/**
 * WC_Steem_Exchange_Poloniex
 *
 * @package WooCommerce Steem Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Steem_Exchange_Poloniex extends WC_Steem_Exchange {
	
	public function get_exchange_id() {
		return 'poloniex';
	}

	/**
	 * Query the exchange rates.
	 * Returns bool indicating whether rates were retrieved successfully.
	 *
	 * @since 1.1.0
	 * @return bool
	 */			
	public function query_rates() {
		$response = wp_remote_get('https://poloniex.com/public?command=returnTicker');

		if (!is_array($response))
			return false;
	
		$data = json_decode(wp_remote_retrieve_body($response), true);

		$usd_btc = (float)$this->get_rate($data, 'USDT', 'BTC');
		$btc_steem = (float)$this->get_rate($data, 'BTC', 'STEEM');
		$btc_sbd = (float)$this->get_rate($data, 'BTC', 'SBD');
		
		// Rates that were expected were not found
		if ($usd_btc === null || $btc_steem === null || $btc_sbd === null)
			return false;
		
		$usd_steem = $usd_btc * $btc_steem;
		$usd_sbd = $usd_btc * $btc_sbd;
		
		$this->set_rate_usd_steem($usd_steem);		
		$this->set_rate_usd_sbd($usd_sbd);
		$this->set_last_successful_query_time(strtotime('now'));
		
		return true;
	}
	
	private function get_rate($data, $from_symbol, $to_symbol) {
		if (!isset($data["{$from_symbol}_{$to_symbol}"]))
			return null;
		
		if (!isset($data["{$from_symbol}_{$to_symbol}"]['last']))
			return null;

		return $data["{$from_symbol}_{$to_symbol}"]['last'];
	}	
}