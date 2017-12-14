<?php

/**
 * Provide the form fields
 *
 *
 * @link       https://github.com/akhenda
 * @since      1.0.0
 *
 * @package    Wc_Pesapal
 * @subpackage Wc_Pesapal/admin/partials
 */
        
return array(
  'enabled' => array(
    'title'   => __( 'Enable/Disable', $this->plugin_name ),
    'type'    => 'checkbox',
    'label'   => __( 'Enable Pesapal Payment', $this->plugin_name ),
    'default' => 'no'
  ),
  'title' => array(
    'title'       => __( 'Title', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'This controls the title which the user sees during checkout.', $this->plugin_name ),
    'default'     => __( 'Pesapal Checkout', $this->plugin_name )
  ),
  'description' => array(
    'title'       => __( 'Description', $this->plugin_name ),
    'type'        => 'textarea',
    'description' => __( 'This is the description which the user sees during checkout.', $this->plugin_name ),
    'default'     => __("Payment via Pesapal Gateway allows you to either pay using Mobile Money option such as Airtel Money or your Credit/Debit card.", $this->plugin_name)
  ),
  'sandbox' => array(
    'title' => __( 'Sandbox Mode', $this->plugin_name ),
    'type' => 'checkbox',
    'label' => __( 'Use Demo Gateway', $this->plugin_name ),
    'description' => __( 'Use Pesapal\'s Sandbox gateway for testing at <a href="https://demo.pesapal.com">https://demo.pesapal.com</a>', $this->plugin_name ),
    'default' => 'no'
  ),
  'sandbox_consumer_key' => array(
    'title'       => __( 'Pesapal Sandbox Consumer Key', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'Your Pesapal demo consumer key which can be found on your dashboard at demo.pesapal.com.', $this->plugin_name ),
    'default'     => ''
  ),
  'sandbox_consumer_secret' => array(
    'title'       => __( 'Pesapal Sandbox Consumer Secret Key', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'Your Pesapal demo consumer secret key which can be found on your dashboard at demo.pesapal.com.', $this->plugin_name ),
    'default'     => ''
  ),
  'consumer_key' => array(
    'title'       => __( 'Pesapal Consumer Key', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'Your Pesapal consumer key which should have been emailed to you.', $this->plugin_name ),
    'default'     => ''
  ),
  'consumer_secret' => array(
    'title'       => __( 'Pesapal Secret Key', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'Your Pesapal secret key which should have been emailed to you.', $this->plugin_name ),
    'default'     => ''
  ),
  'protocol' => array(
    'title'       => __( 'IPN HTTP Protocol', $this->plugin_name ),
    'type'        => 'select',
    'label'       => __( 'Protocol', $this->plugin_name ),
    'options'     => array(
  		'Auto'  => __( 'Auto', $this->plugin_name ),
  		'HTTP'  => __( 'HTTP', $this->plugin_name ),
  		'HTTPS' => __( 'HTTPS', $this->plugin_name )
    ),
    'description' => __( 'Protocol to use for Pesapal notififications. HTTPS works only for sites with SSL and dedicated IP.', $this->plugin_name),
    'desc_tip'    => true,
    'default'     => 'HTTPS'
  ),
  'pesapal_order_prefix' => array(
    'title'       => __( 'Order Prefix', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'Much as a default prefix has been set for you, for security\'s sake, putt a custom one <strong>NOT LESS THAN'.
    ' 4 CHARACTERS</strong> and <strong>NOT MORE THAN 8 CHARACTERS LONG</strong>.', $this->plugin_name ),
    'default'     => __( 'hc_woo_', $this->plugin_name ),
    'desc_tip'    => true
  ),
  'pesapal_order_type' => array(
    'title'       => __( 'Goods have to be shipped and customer verified', $this->plugin_name ),
    'type'        => 'select',
    'description' => __( 'For goods that have to be shipped and verified by the customer as satifactory before transaction is considered complete, select YES and remember to go back to your PesaPal Merchant console to manually update each bought good this way as SHIPPED and DELIVERED. For an easier option which requires no manual updates but does not deal with shipped goods satisfactorily, choose NO', $this->plugin_name ),
    'default'     => __( 'MERCHANT', $this->plugin_name ),
    'desc_tip'    => false,
    'options'     => array(
      'MERCHANT' => __( 'NO', $this->plugin_name ),
      'ORDER'    => __( 'YES', $this->plugin_name )
    )
  ),
  'wc_shop_url' => array(
    'title'       => __( 'Shop URL (Optional)', $this->plugin_name ),
    'type'        => 'text',
    'description' => __( 'This will be used where we need to include your Shop URL', $this->plugin_name ),
    'default'     => __( '/shop', $this->plugin_name ),
    'desc_tip'    => true
  ),
  'debug' => array(
    'title'       => __( 'Debug Log', $this->plugin_name ),
    'type'        => 'checkbox',
    'label'       => __( 'Enable logging', $this->plugin_name ),
    'default'     => 'no',
    'description' => sprintf( __( 'Log PesaPal events, such as IPN requests, inside <code>woocommerce/logs/pesapal-%s.txt</code>', $this->plugin_name ), sanitize_file_name( wp_hash( 'pesapal' ) ) )
  )
);
