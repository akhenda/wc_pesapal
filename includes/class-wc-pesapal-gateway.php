<?php

/**
	* Pesapal Payment Gateway
	*
	* This class provides a Pesapal Payment Gateway.
	*
	* @class 		  Wc_Pesapal_Gateway
	* @extends		WC_Payment_Gateway
	* @since      1.0.0
  * @package    Wc_Pesapal
  * @subpackage Wc_Pesapal/includes
  * @author     Joseph Akhenda <akhenda@gmail.com>
	*/

add_action('plugins_loaded', 'wc_pesapal_gateway_init', 0);

function wc_pesapal_gateway_init() {
	if ( !class_exists('WC_Payment_Gateway') ) return;

	/**
	 * PesaPal uses OAuth to communicate with the API. We require it here
	 */
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'libs/OAuth.php';

	/**
	 * Require the status checker class for helping in the querying of PesaPal
	 */
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-pesapal-check-status.php';

	class Wc_Pesapal_Gateway extends WC_Payment_Gateway {

		/**
	   * Constructor for the gateway.
	   *
	   * @access public
	   * @return void
	   */
		public function __construct() {
			global $woocommerce;

			// Plugin settings
			$this->plugin_name = 'wc_pesapal';
			$this->version     = '1.1.0';

			$this->id                 = $this->plugin_name;
			$this->icon               = apply_filters( 'wc_pesapal_icon',  plugin_dir_url(__FILE__) . '../admin/images/pesapal.png');
			$this->title              = __( 'Pesapal', $this->plugin_name );
			$this->has_fields         = false;
			$this->method_title       = __( 'Pesapal', $this->plugin_name );
			$this->supports           = array('products');

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->enabled                 = $this->settings['enabled'];
			$this->title                   = $this->settings['title'];
			$this->description             = $this->settings['description'];
			$this->sandbox                 = $this->settings['sandbox'];
			$this->consumer_key            = $this->settings['consumer_key'];
			$this->consumer_secret         = $this->settings['consumer_secret'];
			$this->sandbox_consumer_key    = $this->settings['sandbox_consumer_key'];
			$this->sandbox_consumer_secret = $this->settings['sandbox_consumer_secret'];
			$this->protocol                = $this->settings['protocol'];
			$this->pesapal_order_type      = $this->settings['pesapal_order_type'];
	  	$this->pesapal_order_prefix    = $this->settings['pesapal_order_prefix'];
			$this->wc_shop_url 						 = $this->settings['wc_shop_url'];
			$this->debug                   = $this->settings['debug'];

			if( 'yes' == $this->sandbox ) {
				$this->pesapal_post_url = 'https://demo.pesapal.com/api/PostPesapalDirectOrderV4';
				$this->pesapal_status_url = 'https://demo.pesapal.com/api/querypaymentstatus';
				$this->consumer_key = $this->sandbox_consumer_key;
				$this->consumer_secret = $this->sandbox_consumer_secret;
		  } else {
				$this->pesapal_post_url = 'https://www.pesapal.com/API/PostPesapalDirectOrderV4';
				$this->pesapal_status_url = 'https://www.pesapal.com/API/QueryPaymentStatus';
		  }

			//OAuth Signatures
			$this->consumer         = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
			$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
			$this->token            = $this->params = NULL;

			switch ( $this->protocol ) {
				case 'HTTP':
					$this->pesapal_ipn_url   = str_ireplace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Pesapal', home_url( '/' ) ) );
					break;
				case 'HTTPS':
					$this->pesapal_ipn_url   = str_ireplace( 'http:', 'https:', add_query_arg( 'wc-api', 'WC_Gateway_Pesapal', home_url( '/' ) ) );
					break;
				default:
					$this->pesapal_ipn_url = add_query_arg( 'wc-api', 'WC_Gateway_Pesapal', home_url( '/' ) );
					break;
			}

			$this->method_description = sprintf(__( "This plugin enables integration with Pesapal. Pesapal provides a simple, safe and secure way for individuals and businesses to make and accept payments in East Africa. PesaPal sets up an iframe for customers to enter their payment information directly to PesaPal. PesaPal IPN requires cURL support to update order statuses after payment. Check the %sSystem Status%s page for more details.<br /><br />PesaPal requires that a domain e.g. example.com has only one ipn listener url regardless of the number of payment plugins/options. If you therefore have one for this domain already, use that one. <strong>Make sure it is the exact one you used for any other PesaPal payment options for this domain.</strong> Log in to your PesaPal Merchant account on the <a href=\"https://www.pesapal.com\" target=\"_blank\">real pesapal site</a> or <a href=\"https://demo.pesapal.com\" target=\"_blank\">demo pesapal site</a> depending on whether you are in sandbox mode or not. On dashboard, click on the 'IPN Settings' menu, and enter the following URL without the quotes '<strong>" . $this->pesapal_ipn_url . "</strong>'.", $this->plugin_name ), '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );

			// Logs
			if ( 'yes' == $this->debug ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
					$this->log = $woocommerce->logger();
				} else {
					if ( class_exists('WC_Logger') ) {
						$this->log = new WC_Logger();
					}
				}
			}

			// Actions
			add_action('woocommerce_receipt_' . $this->id, array(&$this, 'payment_page'));

	  	// Hooking into the action that checks all GET requests ipn listener url
	  	add_action('woocommerce_api_wc_gateway_pesapal', array($this, 'pesapal_ipn_response'));

			// Hooking into the thankyou page specific to pesapal on loading to retrieve appropriate data from PesaPal
			add_action('woocommerce_thankyou_' . $this->id, array(&$this, 'update_pesapal_transaction'));

	  	// Save settings
			if ( is_admin() ) {
				// we have not defined 'process_admin_options' in this class so the method in the parent
				// class will be used instead
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			}

		}

		/**
     * Initialise Gateway Settings Form Fields
     *
     * @return void
     */
		function init_form_fields() {
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Initiliazing Form Fields...' );

	  	$this->form_fields = include(
				plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/wc-pesapal-form-fields.php'
			);
	  }

		/**
     * Create XML order post request
     *
     * @return string
     */
		function pesapal_xml($order, $order_id) {
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Generating Pesapal XML' );
			// Get order details
			$amount = number_format($order->get_total(), 2); // Format amount to 2 decimal places
			$currency = get_woocommerce_currency();
			$desc = 'Order from ' . get_bloginfo('name');
			$type = $this->pesapal_order_type; // Default value = MERCHANT
			$reference = $this->prefixed_order_id($order_id, $this->pesapal_order_prefix); // Unique order id of the transaction
			$first_name = $order->get_billing_first_name();
			$last_name = $order->get_billing_last_name();
			$email = $order->get_billing_email();
			$phonenumber = $order->get_billing_phone(); // One of email or phonenumber is required
			$line_items = $order->get_items();

			return include(
				plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/wc-pesapal-post-xml.php'
			);
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Done generating Pesapal XML' );
    }

		/**
		 * Create iframe URL
		 *
		 * @return string
		 */
		function create_url($order_id){
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Creating iFrame URL' );
			// Get the order
			$order = wc_get_order($order_id);

			// Creation of the PesaPal XML order information;
			$order_xml = $this->pesapal_xml($order, $order_id);

			// Redirect url, the page that will handle the response from pesapal.
			$callback_url = $this->get_return_url($order);

			// Creation of the iframe url
			$iframe_src = OAuthRequest::from_consumer_and_token(
				$this->consumer, $this->token, "GET", $this->pesapal_post_url, $this->params
			);
			$iframe_src->set_parameter("oauth_callback", $callback_url);
			$iframe_src->set_parameter("pesapal_request_data", $order_xml);
			$iframe_src->sign_request($this->signature_method, $this->consumer, $this->token);

			return $iframe_src;
		}

		/**
     * Payment page, creates pesapal oauth request and shows the gateway iframe
     *
     * @return void
     */
		public function payment_page($order_id) {
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Starting Payment Submission Page' );
			include(
				plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/wc-pesapal-iframe.php'
			);
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Ending Payment Submission Page' );
    }

	  /**
		 *	Function to process the payment and redirect to
		 *	PesaPal iframe i.e. the payment_page
		 *
		 * @return array
		 */
		public function process_payment($order_id) {
			global $woocommerce;
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Processing Payment...' );

			// Get the order by creating a new order object
			$order = wc_get_order($order_id);

			// Update order date for use in email
			update_post_meta($order_id, '_pesapal_order_time', current_time('mysql'));

			// End function with a redirect to PesaPal iframe page
			// if ($order->get_status() === 'completed') {
			// 	// Redirect to payment page
			// 	return array(
			// 		'result'   => 'success',
			// 		'redirect' => $this->get_return_url($order)
			// 	);
			// } else {
			// 	return array(
			// 		'result'   => 'success',
			// 		'redirect' => $order->get_checkout_payment_url(true)
			// 	);
			// }

			return array(
        'result'    => 'success',
        'redirect'  => add_query_arg('key', $order->get_order_key(), add_query_arg('order', $order_id, get_permalink(wc_get_page_id('checkout'))))
      );

			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Stoping Payment Processing...' );
		}

		/**
     * Update txns and show Thank you page
     *
     * Runs when Pesapal redirects back to the site
     *
     * @return void
     */
		function update_pesapal_transaction($order_id) {
			global $woocommerce;

			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Starting Thank You Page' );

			/* To make sure the try and thus the error runs only when the thankyou
        page is from PesaPal and not from another gateway */
			if (isset($_REQUEST['pesapal_merchant_reference'])) {
				try {
					//After transaction is done, this redirects back to order received page with the transaction id and the order number
					if (isset($_GET['pesapal_transaction_tracking_id'])) {
						$pesapal_tracking_id = wc_clean(stripslashes($_GET['pesapal_transaction_tracking_id']));
						$pre_order_id        = wc_clean(stripslashes($_GET['pesapal_merchant_reference']));
						$order_id            = $this->remove_pesapal_order_prefix($pre_order_id, $this->pesapal_order_prefix);

						//return if $order_id didn't have the appropriate prefix
						if ($order_id == false)
							return;

						$order             = wc_get_order($order_id);
						$screen_text       = ''; //Text to output on the thankyou page
						$shop_checkout_url = $woocommerce->cart->get_checkout_url();

						// Store the tracking_id in the db alongside its order number
						add_post_meta($order_id, '_pesapal_transaction_id', $pesapal_tracking_id, true);

						// Check status of the order here to get those that fail, or pass immediately
						$check_status = new Wc_Pesapal_Check_Status(
							(strtolower($this->sandbox) != 'no'), $this->consumer_key, $this->consumer_secret
						);

						// Check status for the prefixed id;
						$status = $check_status->getTransactionDetails($pre_order_id, $pesapal_tracking_id)['status'];

						add_post_meta(
							$order_id, '_order_pesapal_payment_method', $transaction_details['payment_method']
						);

						if ( 'yes' == $this->debug ) {
							$this->log->add( 'wc_pesapal', 'Txn Details at "update_pesapal_transaction": '. json_encode($check_status->getTransactionDetails($pre_order_id, $pesapal_tracking_id)) );
							$this->log->add( 'wc_pesapal', 'Status at "update_pesapal_transaction": '. $status );
						}

						switch ($status) {
							case 'COMPLETED':
								// Add order note and update the text to output on Thankyou page
  							$order->add_order_note( sprintf( __( '%s payment approved immediately! Transaction ID: %s', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
  							$screen_text = __('Payment Received. Thank you.', $this->plugin_name);
								$order->update_status('processing', sprintf(__('%s payment approved. Order moved to processing.', $this->plugin_name), $this->title));
								$order->payment_complete();

								// Reduce stock levels
								$order->reduce_order_stock();

								if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Payment Received. Thank you.' );
								break;
							case 'PENDING':
								// Check order already completed
								if ( $order->status == 'completed' ) {
									 if ( 'yes' == $this->debug )
										$this->log->add( 'wc_pesapal', 'Aborting, Order #' . $order->id . ' is already complete.' );
									 exit;
								}
								// Update status to on-hold as the transaction cannot be complete until PesaPal confirms; Update the screen text appropriately
								$order->update_status('on-hold', sprintf(__('%s payment pending.', $this->plugin_name), $this->title));
								$woocommerce->cart->empty_cart(); // To allow one to continue shopping without having to empty cart maually in case payment takes long
								$screen_text = __('Payment is being processed by Pesapal. We will let you know via email as soon as we are done', $this->plugin_name);

								if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Payment is being processed by Pesapal. We will let you know via email as soon as we are done' );
								break;
							default:
								// Assuming there is an error or the transaction fails, create a fail order note and inform the user on the checkout page
								$order->update_status('failed', sprintf(__('%s Payment Failed', $this->plugin_name), $this->title));
								wc_add_notice(__('Error processing payment with Pesapal. Try again.', $this->plugin_name), 'failure');
								$screen_text = __('Error processing payment with Pesapal.', $this->plugin_name).
								'<a href="'. $shop_checkout_url . '"><strong>Try Again</strong></a>.' ;

								if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Error processing payment with Pesapal' );

								// Redirect back to the checkout page with the error message
								return array(
									'result' => 'failure',
									'redirect' =>  $shop_checkout_url
								);
								break;
							}

							//Output the screen text to the screen
							echo '<p>'. $screen_text . '<br><a href="'. $this->wc_shop_url .
							'" ><strong>Shop Some More!</strong></a></p>';

							// Do not redirect if the transaction is successful
							return array(
								'result' => 'success',
								'redirect' =>  $this->get_return_url( $order )
							);
						} else { //If the pesapal_transaction_tracking_id hasn't been set, throw an exception
							throw new Exception("PesaPal Transaction Failed: invalid parameters ");
						}
					} catch(Exception $ex) {
						wc_add_notice(  $ex->getMessage(), 'error' );
						// Return to checkout page with the error message
						return array(
							'result' => 'failure',
							'redirect' =>  $shop_checkout_url
						);
					}
					if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Ending Thank You Page' );
			} else {
				// TODO: handle this case
				if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Error loading Thank You Page' );
			}
		}

		/**
		 * The ipn listening function thanks to the hook check_woopesapal:
		 * It handles the updating of the status of the order
		 *
		 * @return string
		 */
		public function pesapal_ipn_response() {
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Starting Pesapal IPN Listtener' );
			// If the expected queries are unavailable, quit immediately
			if (!isset($_GET['pesapal_merchant_reference']) || !isset($_GET['pesapal_notification_type']) || !isset($_GET['pesapal_transaction_tracking_id'])) {
				if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Expected queries are unavailable, quiting immediately' );
				return;
			}

			// Otherwise...
			global $woocommerce;

			// Get the order_id from the url
			$pesapal_merchant_reference = ((isset($_GET['pesapal_merchant_reference'])) ? wc_clean(stripslashes($_GET['pesapal_merchant_reference'])) : NULL);
			$order_id = $this->remove_pesapal_order_prefix($pesapal_merchant_reference, $this->pesapal_order_prefix);

			// Return if $order_id is invalid
			if ($order_id == false || $order_id == 0 || $order_id == NULL)
				return;

			$order = wc_get_order($order_id);

			// Get the other variables from the $_GET
			$pesapal_notification = ((isset($_GET['pesapal_notification_type'])) ? wc_clean(stripslashes($_GET['pesapal_notification_type'])) : null);
			$pesapal_tracking_id = ((isset($_GET['pesapal_transaction_tracking_id'])) ? wc_clean(stripslashes($_GET['pesapal_transaction_tracking_id'])) : null);

			// Let's log a few things here
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'wc_pesapal', 'Pesapal Merchant Reference' . $pesapal_merchant_reference );
				$this->log->add( 'wc_pesapal', 'Order Id' . $order_id );
				$this->log->add( 'wc_pesapal', 'The Order' . json_encode($order) );
				$this->log->add( 'wc_pesapal', 'Pesapal Notification' . $pesapal_notification );
				$this->log->add( 'wc_pesapal', 'Pesapal Tracking Id' . $pesapal_tracking_id );
			}

			// Exit if the transaction_id is null
			if($pesapal_tracking_id == null) {
				return;
			}

			// If there is a mismatch of transaction_ids then log a message and exit immediately
			if ($this->match_order_transaction_ids($order_id, $pesapal_tracking_id) === false) {
				$order->add_order_note( sprintf( __( '%s payment attempted to complete payment with wrong Transaction ID: %s', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
				return;
			}

			// Now the fun part starts
			if (($pesapal_tracking_id != null) && ($pesapal_merchant_reference != null)) {
				// Test that we have reached here: Uncomment the statements below for testing purposes only
				if ( 'yes' == $this->debug ) {
					$this->log->add( 'wc_pesapal', 'IPN succes. Tracking: '. $pesapal_tracking_id . ' order_id: ' . $pesapal_merchant_reference );
					$this->send_test_email('IPN success','Tracking: '. $pesapal_tracking_id. ' order_id: '. $pesapal_merchant_reference);
				}

				//Check status of the order
				$check_status = new Wc_Pesapal_Check_Status(
					(strtolower($this->sandbox) != 'no'),
					$this->consumer_key,
					$this->consumer_secret
				);
				$status = $check_status->getTransactionDetails($pesapal_merchant_reference, $pesapal_tracking_id)['status'];

				if ( 'yes' == $this->debug ) {
					$this->log->add( 'wc_pesapal', 'Txn Details at "pesapal_ipn_response": ' . $check_status->getTransactionDetails($pesapal_merchant_reference, $pesapal_tracking_id) );
					$this->log->add( 'wc_pesapal', 'Status at "pesapal_ipn_response": '. $status );
				}

				// True if update to DB is successful
				$db_status_update = false;

				// Status can be INVALID, PENDING, FAILED OR COMPLETED
				switch ($status) {
					case 'FAILED':
					case 'INVALID':
						// The payment was invalid; update db, send message
						$db_status_update = $order->update_status('failed', sprintf( __( '%s payment is invalid! Transaction ID: %d', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
						$woocommerce->cart->empty_cart();
						break;
					case 'COMPLETED':
						// Check order not already completed
						if ( $order->get_status() == 'completed' ) {
							 if ( 'yes' == $this->debug )
								$this->log->add( 'wc_pesapal', 'Aborting IPN Process, Order #' . $order->id . ' is already complete.' );
								$order->add_order_note( sprintf( __( 'Aborting IPN Process, Order #%s is already complete.', $this->plugin_name ), $order->id ) );
							 exit;
						}

						$order->payment_complete( $pesapal_tracking_id );
						$db_status_update = true;
						// Add order note
						$order->add_order_note( sprintf( __( '%s payment approved! Transaction ID: %s', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
						$order->update_status('processing', sprintf(__('%s payment approved. Order moved to processing.', $this->plugin_name), $this->title)); 

						// Reduce stock levels
						$order->reduce_order_stock();

						$woocommerce->cart->empty_cart();
						break;
					case 'PENDING':
						// Check order not already completed
						if ( $order->get_status() == 'completed' ) {
							 if ( 'yes' == $this->debug )
								$this->log->add( 'wc_pesapal', 'Aborting IPN Process, Order #' . $order->id . ' is already complete.' );
								$order->add_order_note( sprintf( __( 'Aborting IPN Process, Order #%s is already complete.', $this->plugin_name ), $order->id ) );
							 exit;
						}

						// Do nothing until there is a change to either failed or complete
						$db_status_update = $order->update_status('on-hold', sprintf( __( '%s payment is pending. Transaction ID: %d', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
						break;
					case 'ERROR':
						// Do nothing until there is a change to either failed or complete
						$db_status_update = $order->update_status('on-hold', sprintf( __( 'Curl Error on server but %s Payment notification received. Transaction ID: %d', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
						break;
					default:
						// wrong response; do nothing except add order note with status of unknown
						$order->add_order_note( sprintf( __( '%s payment returned Unknown! Transaction ID: %s', $this->plugin_name ), $this->title, $pesapal_tracking_id ) );
						break;
				}

				$newstatus  = $order->get_status();
				$dbupdated = (strtolower($status) === strtolower($newstatus));
				if ( 'yes' == $this->debug ) $this->log->add( 'DB Status Update: ' . $db_status_update );

				// send back to pesapal the content we receieved otherwise they will keep sending more ipns
				if (($pesapal_notification == "CHANGE") && $db_status_update && (strtoupper($status) != 'PENDING') && (strtoupper($status) != 'ERROR')) {
					// Test email to say we have reached here: Uncomment statement below for testing purposes only
					if ( 'yes' == $this->debug ) $this->log->add( 'IPN reached response' );
					$this->send_test_email('IPN reached response');

					// alert the customer via email
					$to            = $order->get_billing_email();
					$customer_name = $order->get_billing_firstname() . ' ' . $order->get_billing_last_name();
					$order_time    = get_post_meta($order_id, '_pesapal_order_time', true);
					$order_time    = isset($order_time)? $order_time : 'an earlier date';
					$subject       = sprintf(__('%s Payment for Order: %s %s', $this->plugin_name), $this->title, $order_id, strtolower($status));
					$body          = __('PesaPal has processed your online payment for order: ' . $order_id . ' transacted at ' .
										get_bloginfo('name') . ' on ' . $order_time . '. We hereby inform you that the final status of your payment request is '. $status . '.
 										It was a pleasure doing business with you. <br><br>Please do not reply this email', $this->plugin_name);

					// Need for an appropriate filter here to change look and appearance of email
					wc_mail( $to, $subject, $body ); // send email to user

					// Send PesaPal communication that we have received the message
					$resp = "pesapal_notification_type=$pesapal_notification&pesapal_transaction_tracking_id=$pesapal_tracking_id&pesapal_merchant_reference=$pesapal_merchant_reference";
					ob_start();
					echo $resp;
					ob_flush();
					exit;
				}
			}
			if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'Stoping Pesapal IPN Listtener' );

			return;
		}

		/**
		 * Function to create prefixed orders unique to this pesapal option
		 *
		 * @param $order_id to be prefixed
		 * @param $prefix to append to $order_id
		 *
		 * @return string $order_id with prefix
		 */
		protected function prefixed_order_id($order_id, $prefix) {
			return $prefix . $order_id;
		}

		/**
		 * Remove the prefix attached to the reference order_id when sending payment request to PesaPal for unique id
		 *
		 * @param $prefixed_order_id
		 * @param $prefix
		 *
		 * @return false if prefix doesn't exist; return the order_id without prefix if the prefix did exist as a prefix
		 */
		protected function remove_pesapal_order_prefix($prefixed_order_id, $prefix='') {
			// If the prefix is not set, return the whole $prefixed_order_id
			if (!isset($prefix) || $prefix == '')
				return $prefixed_order_id;

			$prefix_psn = strpos($prefixed_order_id, $prefix);

			//return false if the order_id didnt have the prefix
			if($prefix_psn === false || $prefix_psn != 0) {
				return false;
			} else {
				return substr($prefixed_order_id, strlen($prefix));
			}
		}

		/**
		 * Function to test the output of a variable, function or anything in the browser
		 *
		 * @param $output; the variable to output
		 *
		 * @return void
		 */
		protected function test($output) {
			echo '<script type="text/javascript"> console.log('. (string)$output. '); alert("Console Log: '. (string)$output. '");</script>';
		}

		/**
		 * To check if the transaction id corresponds to a given order_id
		 *
		 * @param string $order_id; the non-prefixed id of the order
		 * @param string $transaction_id; the transaction_id to check for
		 *
		 * @return bool; true if match, false if mismatch
		 * @return $transaction_id if the $order_id has no attached $transaction_id
		 */
		protected function match_order_transaction_ids($order_id, $transaction_id) {
			$saved_transaction_id = get_post_meta((int)$order_id, '_transaction_id', true);
			// If the order corresponding to order_id has no transaction id saved yet, return $transaction_id
			if (!isset($saved_transaction_id) || $saved_transaction_id == '')
				return $transaction_id;

			// If transaction id supplied matches the one attached to the order_id, return true
			if(strcmp($transaction_id, $saved_transaction_id) == 0) {
				return true;
			}

			return false;
		}

		/**
		 * Test email sender: Basically for testing
		 *
		 * @param string $subject
		 * @param string $ body
		 *
		 * @return
		 */
		protected function send_test_email($subject='Basic', $body = 'Nothing to show') {
			$to      = get_option('admin_email');
			$subject = 'PesaPal Trial' . (string)$subject;
			$message = 'Message from PesaPal woo Plugin: '. (string)$body;
			wc_mail($to, $subject, $message);
		}
	}

	/**
   * Add the Pesapal Gateway to WooCommerce
   */
  function woocommerce_add_gateway_pesapal($methods) {
      $methods[] = 'Wc_Pesapal_Gateway';
      return $methods;
  }
  add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_pesapal' );

	/**
	 *	Create the hook to call the ipn listener
	 */
	add_action( 'init', 'check_wc_pesapal_ipn' );
	function check_wc_pesapal_ipn() {
		if( $_SERVER['REQUEST_METHOD'] === 'GET') {
			// Start the payment gateways such that the pesapal gateway can check for ipn
			WC()->payment_gateways();

			do_action( 'pesapal_ipn_response' );
		}
	}
}
