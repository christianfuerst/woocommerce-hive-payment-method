<?php
/**
 * WC_Hive_Exchange_HiveEngine
 *
 * @package WooCommerce Hive Payment Method
 * @category Class
 * @author sagescrub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Hive_Exchange_HiveEngine extends WC_Hive_Exchange {
	
	public function get_exchange_id() {
		return 'hiveengine';
	}

	/**
	 * Query the exchange rates.
	 * Returns bool indicating whether rates were retrieved successfully.
	 *
	 * @since 1.1.0
	 * @return bool
	 */			
	public function query_rates() {
		$url = 'https://api.hive-engine.com/rpc/contracts';
		$ch = curl_init($url);
		$jsonData = array(
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'find',
			"params" => array(
				'contract' => 'tokens',
				'table' => 'tokens',
				'query' => json_decode("{}"),
				'limit' => 1000,
				'offset' => 0,
				'indexes' => []
			)
		);
		$jsonDataEncoded = json_encode($jsonData);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
		$response = curl_exec($ch);
		curl_close($ch);
	
		$jsonResult = json_decode($response, true);

		$currencies = array();
		$precisions = array();

		foreach ($jsonResult['result'] as $r) {
			$currencies[$r['symbol']] = $r['name'];
			$precisions[$r['symbol']] = $r['precision'];
		}

		if (count($currencies) === 0 || count($precisions) === 0)
			return false;

		$url = 'https://api.hive-engine.com/rpc/contracts';
		$ch = curl_init($url);
		$jsonData = array(
			'jsonrpc' => '2.0',
			'id' => 2,
			'method' => 'find',
			"params" => array(
				'contract' => 'market',
				'table' => 'metrics',
				'query' => json_decode("{}"),
				'limit' => 1000,
				'offset' => 0,
				'indexes' => ''
			)
		);
		$jsonDataEncoded = json_encode($jsonData);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
		$response = curl_exec($ch);
		curl_close($ch);
	
		$jsonResult = json_decode($response, true);

		$hiveengine_rates = array();

		foreach ($jsonResult['result'] as $r) {
			$hiveengine_rates[$r['symbol']] = $r['lastPrice'];
		}
		
		// Rates that were expected were not found.
		if (count($hiveengine_rates) === 0)
			return false;
			
		$this->set_rates_hiveengine($hiveengine_rates);
		$this->set_currencies_hiveengine($currencies);
		$this->set_precisions_hiveengine($precisions);
		$this->set_last_successful_query_time(strtotime('now'));
		
		return true;		
	}
	
}