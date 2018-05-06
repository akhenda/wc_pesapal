<?php

if(!defined('ABSPATH')) exit;

if(!class_exists('Wc_Pesapal_Check_Status')) {
	/**
	 * Pesapal Status Checker
	 * 
	 * This class does all Pesapal status checks.
	 *
	 * @since      1.0.0
	 * @package    Wc_Pesapal
	 * @subpackage Wc_Pesapal/includes
	 * @author     Joseph Akhenda <akhenda@gmail.com>
	 */
	class Wc_Pesapal_Check_Status {
		public $token;
		public $params;
		public $signature_method;
		public $demo;
		
		public $QueryPaymentStatus;
		public $QueryPaymentStatusByMerchantRef;
		public $querypaymentdetails;
		
		public function __construct($isDemo, $key, $secret) {
			$this->token = $this->params = NULL;

			// Pesapal Credentials
			$consumer_key 		= $key; 
			$consumer_secret 	= $secret;
			
			$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
			$this->consumer         = new OAuthConsumer($consumer_key, $consumer_secret);

			$this->demo = $isDemo;

			// check whether we are in sandbox mode or production mode and set url accordingly
			if($isDemo) {
				$api = 'https://demo.pesapal.com';
			} else {
				$api = 'https://www.pesapal.com';
			}
				
			$this->QueryPaymentStatus              = $api.'/API/QueryPaymentStatus';
			$this->QueryPaymentStatusByMerchantRef = $api.'/API/QueryPaymentStatusByMerchantRef';
			$this->querypaymentdetails             = $api.'/API/querypaymentdetails';
		}
		
		/**
     * Get Transaction Details
     *
     * @return COMPLETED/PENDING/FAILED/INVALID
     */
		function getTransactionDetails($pesapalMerchantReference, $pesapalTrackingId) {
			try {
				$request_status = OAuthRequest::from_consumer_and_token(
					$this->consumer, 
					$this->token, 
					"GET", 
					$this->querypaymentdetails, 
					$this->params
				);
				$request_status->set_parameter("pesapal_merchant_reference", $pesapalMerchantReference);
				$request_status->set_parameter("pesapal_transaction_tracking_id", $pesapalTrackingId);
				$request_status->sign_request($this->signature_method, $this->consumer, $this->token);
			
				$responseData = $this->curlRequest($request_status);
				
				$pesapalResponse      = explode(",", $responseData);
				$pesapalResponseArray = array(
					'pesapal_transaction_tracking_id' => $pesapalResponse[0],
					'payment_method'                  => $pesapalResponse[1],
					'status'                          => $pesapalResponse[2],
					'pesapal_merchant_reference'      => $pesapalResponse[3]
				);
			} catch(Exception $ex) {
				$pesapalResponseArray = array(
					'pesapal_transaction_tracking_id' => '',
					'payment_method'                  => '',
					'status'                          => 'ERROR',
					'pesapal_merchant_reference'      => ''
				);
			}

			return $pesapalResponseArray;
		}

		function checkStatusByMerchantRef($pesapalMerchantReference) {
			$request_status = OAuthRequest::from_consumer_and_token(
				$this->consumer, 
				$this->token, 
				"GET", 
				$this->QueryPaymentStatusByMerchantRef, 
				$this->params
			);
			$request_status->set_parameter("pesapal_merchant_reference", $pesapalMerchantReference);
			$request_status->sign_request($this->signature_method, $this->consumer, $this->token);
		
			$status = $this->curlRequest($request_status);
		
			return is_wp_error($status) ? 'ERROR' : $status;
		}
		
		/**
     * Makes curl requests 
     *
     * @return ARRAY
     */
		function curlRequest($request_status) {
			try {
				if (in_array('curl', get_loaded_extensions())) {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $request_status);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					if (defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True') {
						$proxy_tunnel_flag = (
							defined('CURL_PROXY_TUNNEL_FLAG') 
							&& strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE'
						) ? false : true;
						curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
						curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
						curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
					}
					
					$response    = curl_exec($ch);
					$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					$raw_header  = substr($response, 0, $header_size - 4);
					$headerArray = explode("\r\n\r\n", $raw_header);
					$header      = $headerArray[count($headerArray) - 1];
					
					// transaction status
					$elements = preg_split("/=/", substr($response, $header_size));
					$pesapal_response_data = $elements[1];
				} else {
					return new WP_Error(
						'Missing extension', __(
							"Curl appears to be disabled on your server."
						)
					);
				}
			} catch(Exception $ex) {
				$pesapal_response_data = 'ERROR';
			}

			return $pesapal_response_data;
		}
	}
}
