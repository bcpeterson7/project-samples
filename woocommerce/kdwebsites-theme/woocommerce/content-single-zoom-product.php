<?php
/**
 * Custom Template for Zoom Items
 */

 defined( 'ABSPATH' ) || exit;

 global $product, $meeting;

 if ( post_password_required() ) {
 	echo get_the_password_form(); // WPCS: XSS ok.
 	return;
}

$product_id = isset($product->id) ? $product->id : 0;
$product_meta = get_post_meta($product_id);


$post_date = new DateTime();
if ( null !== $meeting->stm_date && $meeting->stm_date > 0 )
  $post_date->setTimestamp(substr($meeting->stm_date, 0, 10));

$regular_price = isset($product_meta['_regular_price'][0]) ? $product_meta['_regular_price'][0] : '';
$sales_price = isset($product_meta['_sale_price'][0]) ? $product_meta['_sale_price'][0] : '';
$price = isset($product_meta['_price'][0]) ? $product_meta['_price'][0] : '';
$regular_price = isset($product_meta['_regular_price'][0]) ? $product_meta['_regular_price'][0] : '';

$stock = isset($product_meta['_stock'][0]) ? $product_meta['_stock'][0] : '';
$stock_quantity = isset($product_meta['stock_quantity'][0]) ? $product_meta['stock_quantity'][0] : '';
$availability = '';
$availability = KDWOOZOOM::kd_woo_zoom_availability( $stock );

$post_agenda = '';
$post_agenda = isset($meeting->stm_agenda) ? $meeting->stm_agenda : '';

$starting_time = '';
$starting_time = isset($meeting->stm_time) ? $meeting->stm_time : '';
$stm_timezone = '';
$stm_timezone = isset($meeting->stm_timezone) ? $meeting->stm_timezone : '';

$webinar_time_data = [];
$webinar_time_data = KDWOOZOOM::kd_woo_zoom_time_string( $starting_time, $stm_timezone );

$meeting_length = '';
$meeting_length = isset($meeting->stm_duration) ? $meeting->stm_duration : '';
$w_length = '';
$w_length = KDWOOZOOM::kd_woo_zoom_meeting_length($meeting_length);


?>
<div id="product-zoom-single-<?php the_ID(); ?>" <?php wc_product_class( ' woo-zoom-product ', $product ); ?>>
  <div class="product-left product-details">
    <div class="zoom-single-details-header animator-ready">
      <div class="meeting-details">
        <div class="meeting-details-row meeting-details-header-row">Webinar Details</div>
        <div class="meeting-details-row max-participants"><span class="details-label">Max. number of participants</span><span class="details-info"><?php echo $product->stock_quantity; ?> People</span></div>
        <div class="meeting-details-row web-length"><span class="details-label"><span class="detail-icon length-icon">24</span> Webinar Length</span><span class="details-info"><?php echo $w_length; ?></span></div>
        <div class="meeting-details-row start-time"><span class="details-label"><span class="detail-icon time-icon"></span> Starting Hour</span><span class="details-info"><?php echo $webinar_time_data['starting_time'] ." ". $webinar_time_data['timezone']; ?></span></div>
        <div class="meeting-details-row platform"><span class="details-label">Platform</span><span class="details-info">Zoom</span></div>
      </div>
      <div class="meeting-date-details">
        <div class="meeting-date">
          <span class="date-number-big"><?php echo $post_date->format('j'); ?></span>
          <span class="date-month-year"><?php echo $post_date->format('F Y'); ?></span>
          <span class="orig-date"><?php echo $post_date->format('F j, Y'); ?></span>
        </div>
      </div>
    </div>
    <div class="zoom-single-details-header-spacer animator-ready"></div>
    <div class="product-overview animator-ready">
      <?php echo do_shortcode($product->description); ?>
    </div>
  </div>
  <div class="product-right">
    <?php KDWOOZOOM::KD_woo_single_right(); ?>
  </div><?php // .product-right ?>
</div>
<?php
