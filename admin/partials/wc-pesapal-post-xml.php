<?php

/**
 * Pesapal POST XML
 *
 *
 * @link       https://github.com/akhenda
 * @since      1.0.0
 *
 * @package    Wc_Pesapal
 * @subpackage Wc_Pesapal/admin/partials
 */
?>

<?php

$post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
  <PesapalDirectOrderInfo
    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
    xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"
    Amount=\"" . $amount . "\"
    Currency=\"" . $currency . "\"
    Description=\"" . $desc . "\"
    Type=\"" . $type . "\"
    Reference=\"" . $reference . "\"
    FirstName=\"" . $first_name . "\"
    LastName=\"" . $last_name . "\"
    Email=\"" . $email . "\"
    PhoneNumber=\"" . $phonenumber."\"
    xmlns=\"http://www.pesapal.com\"";

if (count($line_items) > 0) {
  $post_xml .= "><LineItems>";
  foreach ($line_items as $item) {
    $post_xml .= "<LineItem 
      UniqueId=\"" . $item['product_id'] . "\"
      Particulars=\"" . $item['name'] . "\"
      Quantity=\"" . $item['qty'] . "\"
      UnitCost=\"". $item['line_subtotal'] . "\"
      SubTotal=\"". $item['line_total']."\" />";
  }
  $post_xml .= "</LineItems></PesapalDirectOrderInfo>";
} else {
  $post_xml .= " />";
}

if ( 'yes' == $this->debug ) $this->log->add( 'wc_pesapal', 'About to finish Pesapal XML generation' );

return htmlentities($post_xml);

