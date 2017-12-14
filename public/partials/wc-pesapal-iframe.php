<?php

/**
 * Render the Pesapal iFrame
 *
 *
 * @link       https://github.com/akhenda
 * @since      1.0.0
 *
 * @package    Wc_Pesapal
 * @subpackage Wc_Pesapal/public/partials
 */
$url = $this->create_url($order_id);
?>
<div class="pesapal_container" style="position: relative;">
  <img class="pesapal_loading_preloader" src="<?php echo plugin_dir_url(__FILE__); ?>../../public/images/preloader.svg" alt="loading" style="position: absolute;" />
  <iframe class="pesapal_loading_frame" src="<?php echo $url; ?>" width="100%" height="700px"  scrolling="yes" frameBorder="0">
    <p><?php _e('Browser unable to load iFrame', 'wc_pesapal'); ?></p>
  </iframe>
</div>
<script>
  jQuery(document).ready(function () {
    jQuery('.pesapal_loading_frame').on('load', function () {
      jQuery('.pesapal_loading_preloader').hide();
    });
  });
</script>
<?php
