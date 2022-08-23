<?php

class KDWOOZOOM {

  private $version = 1;

  public function __construct()
  {
    add_action( 'save_post_product', array( $this, 'kd_woo_zoom_product_save'), 10, 3 );
  }


  public function init()
  {
    add_action( 'wp_enqueue_scripts', array( $this, 'KD_woo_zoom_scripts') );
    add_shortcode( 'list_woo_zoom_products', array( $this, 'list_woo_zoom_products') );
    add_shortcode( 'list_woo_zoom_products_single', array( $this, 'list_woo_zoom_products_single') );
    add_filter( 'body_class', array( $this, 'KD_woo_zoom_body_class') );
    add_filter( 'stm_zoom_woo_pathes', array( $this, 'KD_stm_zoom_woo_paths') );
    add_action( 'woocommerce_product_options_downloads', array( $this, 'kd_extended_product_options') );
    add_action( 'woocommerce_product_options_pricing', array( $this, 'kd_extended_product_options_pricing') );
    add_filter( 'woocommerce_get_return_url', array( $this, 'kd_override_return_url'), 10, 2 );
    add_action( 'woocommerce_process_product_meta', array( $this, 'kd_save_custom_woo_fields'), 10, 2 );
    // add_shortcode( 'KD_woo_registration_form_short', array( $this, 'KD_woo_registration_form_short') );

    //https://www.tychesoftwares.com/how-to-modify-the-cart-details-on-woocommerce-checkout-page/
    add_filter ('woocommerce_checkout_cart_item_quantity', array( $this, 'remove_quantity_text'), 10, 2 );
    add_filter ('woocommerce_cart_item_name', array( $this, 'add_quantity') , 10, 3 );
    // add_action( 'wp_footer', array( $this, 'add_quanity_js'), 10 );
    add_action( 'init', array( $this, 'load_ajax') );
  }



  public function KD_woo_zoom_scripts()
  {
    wp_register_style( 'kd-woo-zoom', CHILD_THEME_CSS . '/kd-woo-zoom.css', '', $this->version );
    wp_enqueue_style( 'kd-woo-zoom' );
    wp_register_script( 'kd-woo-zoom', CHILD_THEME_JS . '/kd-woo-zoom.js', '', $this->version );
    wp_enqueue_script( 'kd-woo-zoom' );

    if ( is_checkout() ) {
      wp_enqueue_script( 'checkout_script', CHILD_THEME_JS . '/add_quantity.js', '', '', false );
      $localize_script = array(
        'ajax_url' => admin_url( 'admin-ajax.php' )
      );
      wp_localize_script( 'checkout_script', 'add_quantity', $localize_script );
    }
  }



  public function list_woo_zoom_products( $atts, $content )
  {
    wp_enqueue_style( 'kd-woo-zoom' );
    extract(shortcode_atts(array(
      'cat_id' 	        => '',
      'posts_per_page'  => 0,
      'item'            => 'webinar'
    ), $atts));

    global $wpdb;

    // Search Query -
    $search = false;
    $current_past_webinar = isset($_POST['current_past_webinar']) ? sanitize_text_field($_POST['current_past_webinar']) : 'current';
    if ( isset($_POST['webinar_search_submit']) && 'Submit' == sanitize_text_field($_POST['webinar_search_submit']) ) {
      $search = true;
      if ( ! isset( $_POST['webinar_search_nonce'] ) || ! wp_verify_nonce( $_POST['webinar_search_nonce'], 'webinar_search_action' ) ) {
        // if cannot verify the nonce, then get the default posts
        $posts = self::kd_get_current_woo_webinars();
        $posts_past = self::kd_get_woo_webinar_downloads();
      } else {
        // process form data
        $posts = self::kd_search_woo_webinars();
        $posts_past = self::kd_search_woo_webinar_downloads();
      }
    } else {
      // Default Queries -
      $posts = self::kd_get_current_woo_webinars();
      $posts_past = self::kd_get_woo_webinar_downloads();
    }


    // Current Webinars Results
    $zoom_meeting_ids = [];
    foreach( $posts as $post ) {
      $zoom_meeting_ids[] = $post->ID;
    }

    // Available Downloads Results
    $zoom_meeting_ids_past = [];
    foreach( $posts_past as $post ) {
      $zoom_meeting_ids_past[] = $post->ID;
    }

    // Get the woo products related to the Zoom meetings
    $sql = "SELECT pm.`meta_value` AS 'meeting_id',  p.*
    FROM $wpdb->postmeta pm
    INNER JOIN $wpdb->posts p
    ON pm.`post_id` = p.`ID`
    WHERE `meta_value` IN (".implode(", ", $zoom_meeting_ids).")
    AND `meta_key` = '_meeting_id'";
    $products = $wpdb->get_results($sql, OBJECT_K);

    // Get the woo products related to past Zoom meetings
    $sql = "SELECT pm.`meta_value` AS 'meeting_id',  p.*
    FROM $wpdb->postmeta pm
    INNER JOIN $wpdb->posts p
    ON pm.`post_id` = p.`ID`
    WHERE `meta_value` IN (".implode(", ", $zoom_meeting_ids_past).")
    AND `meta_key` = '_meeting_id'";
    $past_products = $wpdb->get_results($sql, OBJECT_K);


    if ( $current_past_webinar == 'current' ) {
      $current_webinars = 'active';
      $past_webinars = 'inactive';
    } else {
      $current_webinars = 'inactive';
      $past_webinars = 'active';
    }

    $html  ='<div id="kd-woo-zoom-product-list-header">';
    $html .=  '<div id="webinars-toggler">';
    $html .=    '<button id="current-webinars" class="'.$current_webinars.'">Current Webinars</button>';
    $html .=    '<span class="webinar-bar"></span>';
    $html .=    '<button id="past-webinars" class="'.$past_webinars.'">Past Webinars</button>';
    $html .=  '</div>';
    $html .='</div>';

    $html .='<div id="kd-woo-zoom-product-list">';
    $html .= '<div id="kd-woo-zoom-active-webinars" class="product-list '.$current_webinars.'">';
    $pposts=[];
    foreach( $posts as $k => $post ) {
      $post_meta = get_post_meta( $post->ID );
      $stm_date = '';
      $stm_date = isset($post_meta['stm_date'][0]) ? $post_meta['stm_date'][0] : 0;
      if ( $stm_date > 1000 ) {
        $stm_date = $stm_date / 1000;

        if ( $stm_date < time() ) {
          $posts_past[] = $post;
          unset($posts[$k]);
          continue;
        } else {
          $pposts[] = $post;
        }
      }
    }

    if ( count($pposts) == 0 ) {
      if ( $search === true ) {
        $html .=  '<div><h3 class="animator-ready">Search Results: No upcoming '.$item.'s have been scheduled related to your search term(s). Try searching by a different word or check the Past Webinars list.</h3></div>';
      } else {
        $html .=  '<div><h3 class="animator-ready">No upcoming '.$item.'s have been scheduled. Interested in learning more? Schedule a 1-on-1 coaching meeting now!</h3></div>';
      }
    } else {
      foreach( $pposts as $post ) {
        $post_meta = get_post_meta( $post->ID );
        $_thumbnail_id = isset($post_meta['_thumbnail_id'][0]) ? $post_meta['_thumbnail_id'][0] : '';
        $thumb_url = '';
        $thumb_url = ( $_thumbnail_id != '' ) ? wp_get_attachment_image_url(  $_thumbnail_id, 'medium' ) : site_url("/uploads/", "https") . '2022/03/webinar-1024x731.jpg';

        $post_agenda = '';
        $post_agenda = isset($post_meta['stm_agenda'][0]) ? $post_meta['stm_agenda'][0] : '';

        $starting_time = '';
        $starting_time = isset($post_meta['stm_time'][0]) ? $post_meta['stm_time'][0] : '';
        $stm_timezone = '';
        $stm_timezone = isset($post_meta['stm_timezone'][0]) ? $post_meta['stm_timezone'][0] : '';

        $webinar_time_details = '';
        $webinar_time_parts = [];
        $webinar_time_parts = self::kd_woo_zoom_time_string( $starting_time, $stm_timezone );
        $webinar_time_details .= '<span class="time-icon"></span><span class="time-start-label">Starting Hour:</span> <span class="time-start">'.$webinar_time_parts['starting_time'].' '.$webinar_time_parts['timezone'].'</span>';

        $meeting_length = '';
        $meeting_length = isset($post_meta['stm_duration'][0]) ? $post_meta['stm_duration'][0] : '';
        $w_length = '';
        $w_length = self::kd_woo_zoom_meeting_length($meeting_length);
        $webinar_length = '';
        $webinar_length = '<span class="length-icon">24</span><span class="webinar-length-label">Length:</span> <span class="web-length">'.$w_length.'</span>';

        $post_date = new DateTime();
        $post_date->setTimestamp(substr($post_meta['stm_date'][0], 0, 10));

        $product_id = isset($products[$post->ID]) ? $products[$post->ID]->ID : 0;
        $product_meta = get_post_meta($product_id);
        $regular_price = isset($product_meta['_regular_price'][0]) ? $product_meta['_regular_price'][0] : '';
        $sales_price = isset($product_meta['_sale_price'][0]) ? $product_meta['_sale_price'][0] : '';
        $price = isset($product_meta['_price'][0]) ? $product_meta['_price'][0] : '';

        $stock = isset($product_meta['_stock'][0]) ? $product_meta['_stock'][0] : '';
        $availability = '';
        $availability = self::kd_woo_zoom_availability( $stock );

        $html .= '<a href="'.get_the_permalink($product_id).'" class="product-title-link animator-ready" title="Link to purchase '.$item.'" aria-label="Link to purchase access to '.$post->post_title.', a '.$item.'">';
        $html .= '<div id="kdwzi-'.$post->ID.'" class="kd-woo-zoom-item animator-ready" data-zoom_ID="'.$post->ID.'" data-product_ID="'.$product_id.'">
        <div class="date-container">
        <span class="date-number-big">'.$post_date->format('j').'</span>
        <span class="date-month-year">'.$post_date->format('F Y').'</span>
        <span class="orig-date"><span>Webinar Date:</span> '.$post_date->format('F jS, Y').'</span>
        </div>
        <div class="featured-img-container" style="background: transparent url('.$thumb_url.') no-repeat center / cover"><!-- zoom item thumbnail image --></div>
        <div class="webinar-info">
        <span class="product-title">'.$post->post_title.'</span>
        <span class="webinar-description">'.$post_agenda.'</span>
        <div class="webinar-details">
        <span class="webinar-time-details">'.$webinar_time_details.'</span>
        <span class="webinar-length">'.$webinar_length.'</span>
        </div>
        </div>
        <div class="enroll-container">';
        // Is this item on sale?
        if ( $price == $sales_price ) {
          // Yes it's on sale!
          // $html .=     '<span class="webinar-cost"><span class="on-sale"></span> $'.$price.'</span>';
          $html .=     '<span class="webinar-cost">
          <span class="on-sale">Sale!</span>
          <span class="original-price">$'.$regular_price.'</span>
          <span class="sales-price">$'.$sales_price.'</span>
          </span>';
        } else {
          // No it's the regular price.
          $html .=     '<span class="webinar-cost">$'.$price.'</span>';
        }
        $html .=     '<span class="webinar-availability">'.$availability.'</span>
        </div>
        </div></a>';
      }
    }
    $html .= '</div>'; // #kd-woo-zoom-active-webinars

    $html .= '<div id="kd-woo-zoom-inactive-webinars" class="product-list '.$past_webinars.'">';
    if ( count($posts_past) == 0 ) {
      if ( $search === true ) {
        $html .=  '<div><h3 class="animator-ready">SEARCH RESULTS: No past '.$item.'s have been found matching your search term(s). Please try searching by another keyword, or schedule a 1-on-1 coaching meeting now!</h3></div>';
      } else {
        $html .=  '<div><h3 class="animator-ready">No past '.$item.'s have been found. Interested in learning more? Schedule a 1-on-1 coaching meeting now!</h3></div>';
      }
    } else {
      foreach( $posts_past as $post ) {
        $past_webinar = false;
        $post_meta = [];
        if ( $post->post_type == "stm-zoom" ) {
          $post_converted = self::get_past_product_from_zoom_id( $post->ID );
          if ( false != $post_converted ) {
            $post = $post_converted;
            $post_meta = get_post_meta( $post_converted->ID );
            $past_webinar = true;
          }
        } else {
          $post_meta = get_post_meta( $post->ID );
        }

        $_thumbnail_id = isset($post_meta['_thumbnail_id'][0]) ? $post_meta['_thumbnail_id'][0] : '';
        $thumb_url = '';
        $thumb_url = ( $_thumbnail_id != '' ) ? wp_get_attachment_image_url(  $_thumbnail_id, 'medium' ) : 'https://nbst.kismetwebdevelopment.com/uploads/2022/03/webinar-1024x731.jpg';

        $post_agenda = '';
        $post_agenda = $post->post_excerpt;

        $meeting_length = '';
        $meeting_length = isset($post_meta['product_length'][0]) ? $post_meta['product_length'][0] : '';
        $w_length = '';
        $w_length = self::kd_woo_zoom_meeting_length($meeting_length);
        $webinar_length = '';
        $webinar_length = '<span class="length-icon">24</span><span class="webinar-length-label">Length:</span> <span class="web-length">'.$w_length.'</span>';

        $post_date = new DateTime();
        if ( $past_webinar === true ) {
          $webinar_date = isset($post_meta['_stm_date'][0]) ? $post_meta['_stm_date'][0] : '';
          $webinar_date = $webinar_date / 1000;
          $post_date->setTimestamp($webinar_date);
        } else {
          $webinar_date = isset($post_meta['webinar_date'][0]) ? $post_meta['webinar_date'][0] : '';
          $post_date->setTimestamp(strtotime($webinar_date));
        }

        $regular_price = isset($post_meta['_regular_price'][0]) ? $post_meta['_regular_price'][0] : '';
        $sales_price = isset($post_meta['_sale_price'][0]) ? $post_meta['_sale_price'][0] : '';
        $price = isset($post_meta['_price'][0]) ? $post_meta['_price'][0] : '';


        $html .= '<a href="'.get_the_permalink($post->ID).'" class="product-title-link animator-ready" title="Link to purchase '.$item.'" aria-label="Link to purchase access to '.$post->post_title.', a '.$item.'">';
        $html .= '<div id="kdwzi-'.$post->ID.'" class="kd-woo-zoom-item animator-ready">
        <div class="date-container">
        <span class="date-number-big">'.$post_date->format('j').'</span>
        <span class="date-month-year">'.$post_date->format('F Y').'</span>
        <span class="orig-date"><span>Recorded on:</span> '.$post_date->format('F jS, Y').'</span>
        </div>
        <div class="featured-img-container" style="background: transparent url('.$thumb_url.') no-repeat center / cover"><!-- zoom item thumbnail image --></div>
        <div class="webinar-info">
        <span class="product-title">'.$post->post_title.'</span>
        <span class="webinar-description">'.$post_agenda.'</span>
        <div class="webinar-details">
        <span class="webinar-length">'.$webinar_length.'</span>
        <span class="webinar-platform"><span class="platform-icon">Platform</span> <span class="details-info">Download Video</span></span>
        </div>
        </div>
        <div class="enroll-container">';
        // Is this item on sale?
        if ( $price == $sales_price ) {
          // Yes it's on sale!
          // $html .=     '<span class="webinar-cost"><span class="on-sale"></span> $'.$price.'</span>';
          $html .=     '<span class="webinar-cost">
          <span class="on-sale">Sale!</span>
          <span class="original-price">$'.$regular_price.'</span>
          <span class="sales-price">$'.$sales_price.'</span>
          </span>';
        } else {
          // No it's the regular price.
          $html .=     '<span class="webinar-cost">$'.$price.'</span>';
        }
        if ( $past_webinar === true ) {
          $html .=     '<span class="stream-webinar">Past Webinar Item</span></span>';
        } else {
          $html .=     '<span class="stream-webinar">Downloadable<span> Video</span></span>';
        }
        $html .= '</div>
        </div></a>';
      }
    }
    $html .= '</div>'; // #kd-woo-zoom-inactive-webinars

    $html .= '</div>'; // #kd-woo-zoom-product-list
    return $html;
  }



  public static function get_past_product_from_zoom_id( $post_ID )
  {
    global $wpdb;
    $post = new stdClass;
    $sql = "SELECT *
    FROM $wpdb->posts p
    INNER JOIN $wpdb->postmeta pm
    ON p.`ID` = pm.`post_id`
    WHERE p.`post_type` = 'product'
    AND p.`post_status` = 'publish'
    AND ( pm.`meta_key` = '_meeting_id' AND pm.`meta_value` = {$post_ID} )
    ORDER BY p.ID DESC
    LIMIT 0, 1";
    $post = $wpdb->get_results($sql, OBJECT_K);
    if ( is_null($post) ) {
      return false;
    } else {
      if ( is_array( $post ) ) {
        $post = array_values($post);
        return $post[0];
      } else {
        return $post;
      }
    }
    return $post;
  }



  /**
  * kd_get_current_woo_webinars description - returns the active/upcoming default webinar (Zoom meeting) post itmes
  * @return [type] [description]
  */
  public static function kd_get_current_woo_webinars()
  {
    $args = array(
      'posts_per_page' => 0,
      'post_type'      => 'stm-zoom',
      'meta_query' => array(
        'relation' => 'AND',
        array(
          'relation' => 'OR',
          array(
            'key'     => '_meeting_id',
            'value'   => '',
            'compare' => '!='
          ),
          array(
            'key'     => 'stm_waiting_room',
            'compare' => 'EXISTS',
          ),
        ),
        array(
          'key'     => 'stm_date',
          'value'   => round(microtime(true) * 1000),
          'type'    => 'numeric',
          'compare' => '>='
        )
      )
    );
    $query = new WP_Query( $args );
    $posts = $query->posts;
    wp_reset_postdata();
    return $posts;
  } // kd_get_current_woo_webinars


  /**
  * kd_get_woo_webinar_downloads description - returns the default webinar "downloadables" - videos that have been uploaded
  * @return [type] [description]
  */
  public static function kd_get_woo_webinar_downloads()
  {
    $qry = "SELECT p.* FROM $wpdb->posts p
    INNER JOIN $wpdb->postmeta pm
    ON p.ID = pm.post_id
    WHERE pm";
    $args = array(
      'posts_per_page' => 10,
      'post_type' => 'product',
      'orderby' => 'title',
      'tax_query' => array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'product_cat',
          'field' => 'term_id',
          'terms' => 23
        ),
      )
    );
    $query_past = new WP_Query( $args );
    wp_reset_postdata();
    $posts_past = $query_past->posts;
    return $posts_past;
  } // kd_get_woo_webinar_downloads




  function kd_search_woo_webinars()
  {
    $current_past_webinar = isset($_POST['current_past_webinar']) ? sanitize_text_field($_POST['current_past_webinar']) : 'current'; // display tab
    // Search by text
    $webinar_search = isset($_POST['webinar_search']) ? sanitize_text_field($_POST['webinar_search']) : ''; // search text value

    // Search all (past and upcoming), or only upcoming?
    $search_all = isset($_POST['show_available']) && sanitize_text_field($_POST['show_available']) == 'on' ? false : true; // search all or search active (defaults to all)

    global $wpdb;
    if ( $search_all === false ) {
      // SEARCH PRESENT ZOOM MEETINGS
      $sql = "SELECT *
      FROM $wpdb->posts p
      INNER JOIN $wpdb->postmeta pm
      ON p.`ID` = pm.`post_id`
      WHERE p.`post_type` = 'stm-zoom'
      AND p.`post_status` = 'publish'
      AND ( p.`post_content` LIKE '%{$webinar_search}%' OR p.`post_title` LIKE '%{$webinar_search}%' )
      AND ( pm.`meta_key` = 'stm_date' AND pm.`meta_value` != '' )
      AND pm.`meta_value` > UNIX_TIMESTAMP(NOW())*1000";
      $posts = $wpdb->get_results($sql, OBJECT_K);

    } else {
      // SEARCH ALL PAST AND PRESENT MEETINGS
      $sql = "SELECT * FROM $wpdb->posts
      WHERE `post_type` = 'stm-zoom'
      AND `post_status` = 'publish'
      AND (     `post_title` LIKE '%$webinar_search%'
        OR  `post_content` LIKE '%$webinar_search%'
      )
      ORDER BY `ID` DESC";
      $posts = $wpdb->get_results($sql, OBJECT_K);
    }

    return array_values($posts);
  } // kd_search_woo_webinars


  public function KD_wp_query_title_filter( $where, &$wp_query )
  {
    global $wpdb;
    if ( $search_term = $wp_query->get( 'search_post_title') ) {
      $where .= ' OR ' . $wpdb->posts . '.post_title LIKE \'%' . $wpdb->esc_like( $search_term ) . '%\'';
    }
    return $where;
  }




  function kd_search_woo_webinar_downloads()
  {
    $current_past_webinar = isset($_POST['current_past_webinar']) ? sanitize_text_field($_POST['current_past_webinar']) : 'current'; // display tab
    // Search by text
    $webinar_search = isset($_POST['webinar_search']) ? sanitize_text_field($_POST['webinar_search']) : ''; // search text value

    // Search all (past and upcoming), or only upcoming?
    $search_all = isset($_POST['show_available']) && sanitize_text_field($_POST['show_available']) == 'on' ? true : false; // search all or search active (defaults to all)
    $compare = '>=';
    if ( $search_all == false ) {
      $comp = [];
    } else {
      $comp = array(
        'key'     => 'webinar_date',
        'value'   => round(microtime(true) * 1000),
        'type'    => 'numeric',
        'compare' => $compare
      );
    }
    $qry = "SELECT p.* FROM $wpdb->posts p
    INNER JOIN $wpdb->postmeta pm
    ON p.ID = pm.post_id
    WHERE pm";
    $args = array(
      'posts_per_page' => 10,
      'post_type' => 'product',
      'orderby' => 'title',
      'tax_query' => array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'product_cat',
          'field' => 'term_id',
          'terms' => 23
        ),
      ),
      's' => $webinar_search
    );
    add_filter( 'posts_where', [ $this, 'KD_wp_query_title_filter' ], 10, 2);
    $query_past = new WP_Query( $args );
    remove_filter( 'posts_where', [ $this, 'KD_wp_query_title_filter' ]);
    wp_reset_postdata();
    $posts_past = $query_past->posts;
    return $posts_past;
  }





  public static function kd_woo_zoom_meeting_length($meeting_length)
  {
    $w_length = '';
    if ( $meeting_length != '' ) {
      if ( $meeting_length < 60 ) {
        $w_length = "$meeting_length minutes";
      } elseif ( $meeting_length > 60 ) {
        $hours = floor( $meeting_length / 60 );
        $minutes = $meeting_length % 60;
        $w_length = "$hours hours";
        if ( $minutes != 0 )
        $w_length .= " $minutes  minutes";
      } elseif ( $meeting_length == 60 ) {
        $w_length = "1 hour";
      }
    }
    return $w_length;
  }




  public function kd_convert_woo_zoom_datetime( $microtime)
  {
    if ( isset($microtime) && $microtime > 0 )
    return round($microtime * 1000);
    else
    return 0;
  }



  public static function kd_woo_zoom_availability( $stock )
  {
    $availability = '';
    if ( $stock > 0 ) {
      if ( $stock > 0 && $stock < 3 ) {
        $availability = '<span class="web-availability limited-availability">'.$stock.' Places available</span>';
      } else {
        $availability = '<span class="web-availability large-availability">'.$stock.' Places available</span>';
      }
    } else {
      $availability = '<span class="web-availability no-availability">Fully Booked</span>';
    }
    return $availability;
  }



  public static function kd_woo_zoom_time_string( $starting_time, $stm_timezone ) : array
  {
    $webinar_time_parts = [];
    if ( str_contains( $starting_time, ":" ) ) {
      $ampm = '';
      $time_parts = explode(":", $starting_time);
      $hour = $time_parts[0];
      $minutes = $time_parts[1];
      if ( $hour > 12 ) {
        $hour -= 12;
        $ampm = 'PM';
      } elseif ( $hour == 12 ) {
        $ampm = 'PM';
      } else {
        $ampm = 'AM';
      }
      if ( $hour == 0 )
      $hour = 12;
      $starting_time = "$hour:$minutes $ampm";
      $webinar_time_parts['starting_time'] = $starting_time;
      $webinar_time_parts['hour'] = $hour;
      $webinar_time_parts['minutes'] = $minutes;
      $webinar_time_parts['ampm'] = $ampm;
      switch($stm_timezone) {
        case 'America/Los_Angeles':
        $stm_timezone = 'PST';
        break;
        case 'America/Denver':
        $stm_timezone = 'MST';
        break;
        case 'America/Chicago':
        $stm_timezone = 'CST';
        break;
        case 'America/New_York':
        $stm_timezone = 'EST';
        break;
      }
      $webinar_time_parts['timezone'] = $stm_timezone;
    }
    return $webinar_time_parts;
  }




  public function list_woo_zoom_products_single( $atts, $content ) : string
  {
    wp_enqueue_style( 'kd-woo-zoom' );
    wp_enqueue_script( 'kd-woo-zoom' );
    extract(shortcode_atts(array(
      'product_id'      => 0,
    ), $atts));

    if ( $product_id < 0 || $product_id == '' )
    return '';

    $args = array(
      'post_type'=> 'product',
      'page_id' => $product_id
    );

    $query = new WP_Query( $args );
    $posts = $query->posts;
    $product = $posts[0];
    $woo_featured_image = '';
    while ( $query->have_posts() ) : $query->the_post();
    ob_start();
    echo woocommerce_get_product_thumbnail();
    $woo_featured_image = ob_get_contents();
    ob_end_clean();
  endwhile;
  $product_meta = get_post_meta($product->ID);
  $regular_price = isset($product_meta['_regular_price'][0]) ? $product_meta['_regular_price'][0] : '';
  $sales_price = isset($product_meta['_sale_price'][0]) ? $product_meta['_sale_price'][0] : '';
  $price = isset($product_meta['_price'][0]) ? $product_meta['_price'][0] : '';

  if ( empty($product) )
  return "Product #{$product_id} not found. Please check your settings.";

  $html  = '<div id="product-'.$product->ID.'" class="kd-single-product animator-ready">';
  $html .= '<h3 id="'.$product->post_title.'"><a href="'.get_the_permalink($product->ID).'" class="product-title-link">'.$product->post_title.'</a></h3>';
  $html .= '<div class="product-image-price-container">';
  $html .= '<a href="'.get_the_permalink($product->ID).'" class="product-featured-image-link">';
  $html .= $woo_featured_image;
  // Is this item on sale?
  if ( $price == $sales_price ) {
    // Yes it's on sale!
    $html .=     '<span class="product-cost"><span class="on-sale"></span> $'.$price.'</span>';
  } else {
    // No it's the regular price.
    $html .=     '<span class="product-cost">$'.$price.'</span>';
  }
  $html .= '</a>'; // .product-featured-image-link
  $html .= '</div>'; // .product-image-price-container
  $html .= '<a href="'.get_the_permalink($product->ID).'" class="product-booking-link solid-red-button"><svg><rect width="100%" height="100%" x="0" y="0" rx="8" ry="8"></rect></svg><span>Book Now</span></a>';

  $html .= '</div>'; // .single-product
  return $html;
}




function KD_woo_zoom_body_class( $classes )
{
  global $post;
  if ( str_contains( $post->post_content, 'list_woo_zoom_products') ) {
    return array_merge( $classes, array( 'kd-woo-zoom-list' ) );
  }
  return $classes;
}



public function KD_stm_zoom_woo_paths( $templates )
{
  return $templates;
}



public static function KD_get_product_meeting( $post_ID=0 )
{
  $meeting_post = new stdClass;
  if ( $post_ID == 0 )
  return $meeting;

  $post_meta = [];
  $post_meta = get_post_meta($post_ID);
  $meeting_id = 0;
  if ( isset($post_meta['_meeting_id']))
  $meeting_id = $post_meta['_meeting_id'][0];
  if ( $meeting_id > 0 )
  $meeting_post = get_post($meeting_id);
  if ( is_object($meeting_post) && property_exists( $meeting_post, 'post_type') && $meeting_post->post_type == 'stm-zoom' ) {
    $meeting_post_meta = get_post_meta($meeting_post->ID);
    if ( is_array( $meeting_post_meta ) && isset($meeting_post_meta) ) {
      foreach( $meeting_post_meta as $key => $mpm ) {
        if ( is_array($mpm) ) {
          if ( count($mpm) == 1 ) {
            $meeting_post->$key = $mpm[0];
          } elseif ( count($mpm) > 1 ) {
            $meeting_post->$key = $mpm;
          }
        } elseif ( is_string($mpm) ) {
          $meeting_post->$key = $mpm;
        }
      }
    }
  }
  $GLOBALS['meeting'] = $meeting_post;
  return $meeting_post;
}



public static function KD_woo_registration_form_short()
{
  ?>
  <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

    <?php do_action( 'woocommerce_register_form_start' ); ?>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
      <label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
      <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
      <label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
      <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
      <label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
      <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
    </p>

    <?php // do_action( 'woocommerce_register_form' ); ?>

    <p class="woocommerce-form-row form-row">
      <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
      <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
    </p>

    <?php // do_action( 'woocommerce_register_form_end' ); ?>
    <?php
  }




  function kd_extended_product_options()
  {
    echo '<div class="options_group">';
    woocommerce_wp_text_input( array(
      'id'      => 'product_length',
      'value'   => get_post_meta( get_the_ID(), 'product_length', true ),
      'label'   => 'Video Length',
      'desc_tip' => true,
      'description' => 'If this is a downloadable video, this is the length in minutes of the video.',
    ) );
    echo '</div>';
    echo '<div class="options_group">';
    woocommerce_wp_text_input( array(
      'id'      => 'webinar_date',
      'value'   => get_post_meta( get_the_ID(), 'webinar_date', true ),
      'label'   => __( 'Webinar Date', 'woocommerce' ),
      'required' => false,
      'class' => 'hasDatepicker',
      'desc_tip' => false,
      'description' => 'The date of the original webinar.',
      'placeholder' => 'mm/dd/yyyy'
    ) );
    echo '</div>';
  }




  function kd_extended_product_options_pricing()
  {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox( array(
      'id'      => 'is_video_product',
      'value'   => get_post_meta( get_the_ID(), 'is_video_product', true ),
      'label'   => 'Video Product',
      'desc_tip' => false,
      'description' => 'Is this a downloadable video product (past webinar, instructional video, etc.)?',
    ) );
    echo '</div>';
    echo '<div class="options_group">';
    woocommerce_wp_checkbox( array(
      'id'      => 'is_consultation',
      'value'   => get_post_meta( get_the_ID(), 'is_consultation', true ),
      'label'   => 'Consultation',
      'desc_tip' => false,
      'description' => 'Is this a consultation (one-on-one meeting, coaching, private instruction)?',
    ) );
    echo '</div>';
  }




  function kd_save_custom_woo_fields( $id, $post ){
    // if( !empty( $_POST['product_length'] ) ) {
    update_post_meta( $id, 'product_length', $_POST['product_length'] );
    update_post_meta( $id, 'is_video_product', $_POST['is_video_product'] );
    update_post_meta( $id, 'is_consultation', $_POST['is_consultation'] );
    update_post_meta( $id, 'webinar_date', $_POST['webinar_date'] );
    //} else {
    //	delete_post_meta( $id, '	if( !empty( $_POST['product_length'] ) ) {' );
    //}
  }



  public static function KD_woo_single_right()
  {
    if ( post_password_required() ) {
      echo "A specific page password is required to access this content.";
      return;
    }

    global $product, $meeting, $woocommerce;
    $product_id = isset($product->id) ? $product->id : 0;
    $downloadable = $product->get_downloadable(); // is it downloadable? bool
    $is_virtual = $product->is_virtual(); // is it virtual? bool
    $product_meta = get_post_meta($product_id);

    // Cancel transaction?
    $cancel = isset($_GET['c']) ? sanitize_text_field($_GET['c']) : "false";
    if ( $cancel == 'true' ) {
      if ( $product_id > 0) {
        $product_cart_id = WC()->cart->generate_cart_id( $product_id );
        $cart_item_key = WC()->cart->find_product_in_cart( $product_cart_id );
        WC()->cart->remove_cart_item( $cart_item_key );
      }
    }

    // Transaction completed?
    $completed = isset($_GET['order_status']) ? sanitize_text_field($_GET['order_status']) : "false";
    if ( $completed == 'complete' ) {
      $order_id = isset($_GET['oid']) ? sanitize_text_field($_GET['oid']) : 0;
      $order = wc_get_order( $order_id );
      foreach($order->get_items() as $key => $item)
      {
        $product_id = 0;
        $product_id = $item['product_id'];
        if ( $product_id > 0 ) {
          $product = wc_get_product( $product_id );
          $product_meta = get_post_meta($product_id);
          $downloadable = $product->get_downloadable(); // is it downloadable? bool
          $is_video_product = isset($product_meta['is_video_product'][0]) ? $product_meta['is_video_product'][0] : '';
          $is_consultation = isset($product_meta['is_consultation'][0]) ? $product_meta['is_consultation'][0] : '';
        }
      }
      ?>
      <div class="enrollment-step purchase-complete">
        <div class="enrollment-header animator-ready">
          <div class="enrollment-header animator-ready">Purchase Complete</div>
        </div>
        <div class="product-enrollment animator-ready">
          <p class="mb0">Payment successful, thank you!<br>
            <?php if ( $is_video_product == "yes" ) {
              echo " A link where you can download the contents of the webinar will be sent to your email address.";
              ?>
            <?php } elseif ( $is_consultation == "yes" ) {
              echo " I will call you, or send you an email, in the next two business days to arrange a time for your consultation.";
            } else {
              echo " You will recieve an email with details about your order.";
            }?>
          </div>
        </div>
        <?php
        return;
      } // if ( $completed == 'complete' )

      $post_date = new DateTime();
      if ( null !== $meeting->stm_date && $meeting->stm_date > 0 )
      $post_date->setTimestamp(substr($meeting->stm_date, 0, 10));
      if ( array_key_exists('_meeting_id', $product_meta) && isset($product_meta['_meeting_id'][0]) && ($meeting->stm_date / 1000) < time() ) {
        echo '<div class="enrollment-step step-1">';
        echo   '<div class="product-enrollment">';
        echo     '<div class="enrollment-top-info simple-product">';
        echo        '<p style="font-size: 24px; text-align: center; width: 100%;" class="center mb0">Past Webinar No Longer For Sale</p>';
        echo     '</div>';
        echo   '</div>';
        echo '</div>';
        return;
      }

      $regular_price = isset($product_meta['_regular_price'][0]) ? $product_meta['_regular_price'][0] : '';
      $sales_price = isset($product_meta['_sale_price'][0]) ? $product_meta['_sale_price'][0] : '';
      $price = isset($product_meta['_price'][0]) ? $product_meta['_price'][0] : '';
      $regular_price = isset($product_meta['_regular_price'][0]) ? $product_meta['_regular_price'][0] : '';

      $stock = isset($product_meta['_stock'][0]) ? $product_meta['_stock'][0] : '';
      $stock_quantity = isset($product_meta['stock_quantity'][0]) ? $product_meta['stock_quantity'][0] : '';

      $availability = '';
      $availability = self::kd_woo_zoom_availability( $stock );

      $post_agenda = '';
      $post_agenda = isset($meeting->stm_agenda) ? $meeting->stm_agenda : '';

      $starting_time = '';
      $starting_time = isset($meeting->stm_time) ? $meeting->stm_time : '';
      $stm_timezone = '';
      $stm_timezone = isset($meeting->stm_timezone) ? $meeting->stm_timezone : '';

      $webinar_time_data = [];
      $webinar_time_data = self::kd_woo_zoom_time_string( $starting_time, $stm_timezone );

      $meeting_length = '';
      $meeting_length = isset($meeting->stm_duration) ? $meeting->stm_duration : '';
      $w_length = '';
      $w_length = self::kd_woo_zoom_meeting_length($meeting_length);

      // Is this item on sale?
      $cost='';
      if ( $price == $sales_price ) {
        // Yes it's on sale!
        $cost .= '<span class="webinar-cost"><span class="on-sale">Sale!</span><span class="original-price">$'.$regular_price.'</span><span class="sales-price">$'.$sales_price.'</span></span>';
      } else {
        // No it's the regular price.
        $cost .= '<span class="webinar-cost">$'.$price.'</span>';
      }

      $is_video_product = isset($product_meta['is_video_product'][0]) ? $product_meta['is_video_product'][0] : '';

      // Process any add to cart saves
      if ( isset( $_POST['woo_add_to_cart_value'] ) && wp_verify_nonce( $_POST['woo_add_to_cart_value'], 'woo_add_to_cart' ) ) {
        $product_id = isset($_POST['product_id']) ? (int) sanitize_text_field($_POST['product_id']) : 0;
        if ( $product_id > 0 )
        $woocommerce->cart->add_to_cart( $product_id );
      }


      $product_ids = array_merge(
        wp_list_pluck(WC()->cart->get_cart_contents(), 'variation_id'),
        wp_list_pluck(WC()->cart->get_cart_contents(), 'product_id')
      );
      if ( !in_array($product_id, $product_ids) ) {
        ?>
        <div class="enrollment-step step-1">
          <form id="add-item-to-cart" action="<?php echo get_the_permalink() ?>"  method="post">
            <?php wp_nonce_field('woo_add_to_cart', 'woo_add_to_cart_value'); ?>
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="downloadable" value="<?php echo ( true === $downloadable ) ? 'true' : 'false'; ?>">
            <?php if ( $downloadable === true ) {
              if ( $is_video_product == "yes" ) {
                ?>
                <div class="enrollment-header animator-ready">Purchase Video</div>
                <?php
              } else {
                ?>
                <div class="enrollment-header animator-ready">Purchase Product</div>
                <?php
              }
              ?>
              <div class="product-enrollment animator-ready">
                <div class="enrollment-top-info">
                  <div class="enrollment-price"><?php echo $cost; ?></div>
                  <div class="enrollment-date">
                    <span class="enrollment-the-day"><?php echo $post_date->format('l'); ?></span>
                    <span class="enrollment-the-date"><?php echo $post_date->format('F d, Y'); ?></span>
                  </div>
                </div>

              </div>
              <div class="product-enrollment-link animator-ready">
                <button type="submit" form="add-item-to-cart" value="add-downloadable" class="enrollment-button pink">Buy Now</button>
              </div>
            <?php } else { // not a downloadable product
              if ( $is_virtual == "yes" ) {
                ?>
                <div class="enrollment-header animator-ready">Reserve your spot</div>
                <?php
              } else {
                ?>
                <div class="enrollment-header animator-ready">Purchase Product</div>
                <?php
              }
              ?>
              <div class="product-enrollment animator-ready">
                <div class="enrollment-top-info simple-product">
                  <div class="enrollment-price"><span class="simple-price-label">Price:</span><?php echo $cost; ?></div>
                </div>
              </div>
              <div class="product-enrollment-link animator-ready">
                <button type="submit" form="add-item-to-cart" value="add-product" class="enrollment-button pink">Buy Now</button>
              </div>
            <?php } ?>
          </form>
        </div>
      <?php } else { ?>
        <div class="enrollment-step step-2">
          <?php if ( ! is_user_logged_in() ) {
            // Login & Registration Forms
            if ( isset($_GET['login']) && "failed" == sanitize_text_field($_GET['login']) && !isset($_POST['register']) ) {
              wc_add_notice( '<strong>Error:</strong> Login was unsuccessful. Please try again.', 'error' );
            }
            $pane_login = " checked";
            $pane_register = "";
            if ( isset($_POST['register']) ) {
              $pane_login = "";
              $pane_register = " checked";
              if ( isset($_POST['username']) && "" == sanitize_text_field($_POST['username']) )
              wc_add_notice( '<strong>Error:</strong> Please enter a username.', 'error' );
              // if ( isset($_POST['email']) && "" == sanitize_text_field($_POST['email']) )
              //   wc_add_notice( '<strong>Error:</strong> Please enter an email.', 'error' );
              if ( isset($_POST['password']) && "" == sanitize_text_field($_POST['password']) )
              wc_add_notice( '<strong>Error:</strong> Please enter a password.', 'error' );
            }

            ?>
            <div class="enrollment-header animator-ready">
              <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_URI']; ?>" class="go-back">Go back</a>
              <a href="?step-1&c=true" class="cancel">Cancel</a>
            </div>
            <div class="forms-container animator-ready">
              <div class="form-toggler">
                <input id="login-pane-rad" type="radio" name="form-selector" value="Login" class="login-pane active"<?php echo $pane_login; ?>>
                <input id="register-pane-rad" type="radio" name="form-selector" value="Register" class="register-pane"<?php echo $pane_register; ?>>
                <label class="pane-choice login-pane-label" for="login-pane-rad">Login</label>
                <label class="pane-choice register-pane-label" for="register-pane-rad">Register</label>
                <div class="login-pane_ user-login-form active">
                  <?php
                  if ( function_exists( 'woocommerce_login_form' ) &&
                  function_exists( 'woocommerce_output_all_notices' ) ) {
                    //render the WooCommerce login form
                    if ( $pane_register == "" && isset($_GET['login']) && "failed" == sanitize_text_field($_GET['login']) ) {
                      echo woocommerce_output_all_notices();
                    }
                    echo woocommerce_login_form( array( 'redirect' => $_SERVER['REQUEST_URI'] ) );
                    // ob_get_clean();
                  } else {
                    //render the WordPress login form
                    // return wp_login_form( array( 'echo' => false ));
                    wp_login_form( array( 'echo' => true ));
                  }
                  ?>
                </div>
                <?php
                // Registration Form
                ?>
                <div class="register-pane_ user-registration-form">
                  <?php
                  if ( function_exists( 'woocommerce_output_all_notices' ) ) {
                    if ( $pane_register == " checked" ) {
                      echo woocommerce_output_all_notices();
                    }
                  }
                  ?>
                  <?php self::KD_woo_registration_form_short(); ?>
                </div>
              </div>
              <?php
            } else {
              // User is logged in at this point
              $is_nbst_member = '';
              $user_id = get_current_user_id();
              // Was the membership form submitted? Save the form data.
              if ( isset($_POST['submit_button']) && "Continue to Checkout" == sanitize_text_field($_POST['submit_button']) ) {
                // Verify Nonce
                if ( isset( $_POST['is_nbst_member_nonce_value'] ) && wp_verify_nonce( $_POST['is_nbst_member_nonce_value'], 'is_nbst_member_nonce' ) ) {
                  $is_nbst_member = isset($_POST['is_nbst_member']) ? sanitize_text_field($_POST['is_nbst_member']) : 'off';
                  update_user_meta( $user_id, 'is_nbst_member', $is_nbst_member );
                }
              }
              // Load user membership agreement setting:
              $is_nbst_member = get_user_meta( $user_id, 'is_nbst_member', true );
              $checked = ( "on" == $is_nbst_member ) ? ' checked' : '';
              // Check if the user has agreed to be a member
              if ( 'on' == $is_nbst_member ) {
                // redirect
              } else {
                ?>
                <div id="membership-agreement-container" class="animator-ready">
                  <form action="<?php echo get_the_permalink() ?>" method="post" id="membership-agreement-form">
                    <?php echo wp_nonce_field('is_nbst_member_nonce', 'is_nbst_member_nonce_value'); ?>
                    <p class="mb10">Complete Membership Agreement:</p>
                    <div class="agreement-row"><div id="membership-agreement"><?php echo do_shortcode('[membership_agreement]'); ?></div></div>
                    <div class="agreement-row">
                      <p class="mb10">By checking the box below, or by accessing any membership resources on the website including written content, videos, webinars, or online meetings sponsored by Nebi-Star Trading, and its affiliates, you are agreeing to abide by the terms and conditions of the membership agreement as listed above.</p>
                      <input type="checkbox" id="is_nbst_member" name="is_nbst_member" class="agree-chbx" value="on"<?php echo $checked;?>><label for="is_nbst_member" value="agree" class="agree-label">Agree</label>
                    </div>
                    <div class="agreement-row">
                      <input type="submit" name="submit_button" value="Continue to Checkout">
                    </div>
                  </form>
                </div>
                <?php
              }
            }
            ?>
          </div>

          <?php if ( is_user_logged_in() ) {
            // $sb = WC()->cart->subtotal;
            // $sub_total = WC()->cart->get_total();
            // $tax_total = WC()->cart->get_fee_tax();
            // $total = $sb + $tax_total;
            ?>
            <?php /*
            <div class="enrollment-step step-3">
            <div class="enrollment-header animator-ready">
            <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_URI']; ?>" class="go-back">Go back</a>
            <a href="?step-1" class="cancel">Cancel</a>
            </div>
            <div class="enrollment-total-payment-choice animator-ready">
            <div class="cost-totals">
            <div class="cost-row sub-total"><span class="totals-label">Subtotal</span><span class="totals-value"><?php echo $sub_total; ?></span></div>
            <div class="cost-row taxes"><span class="totals-label">Tax</span><span class="totals-value"><bdi><span class="woocommerce-Price-currencySymbol">$</span><?php echo $tax_total; ?></bdi></span></div>
            <div class="cost-row total"><span class="totals-label">Total</span><span class="totals-value"><bdi><span class="woocommerce-Price-currencySymbol">$</span><?php echo $total; ?></bdi></span></div>
            </div>
            <div class="payment-methods animator-ready">
            <legend>Please select one payment method</legend>
            <?php
            $enabled_gateways = KD_WOO::kd_get_woo_payment_methods();
            foreach( $enabled_gateways as $eg ) { ?>
            <label for="rad_<?php echo $eg->id; ?>"><input type="radio" id="rad_<?php echo $eg->id; ?>" name="payment_method" value="<?php echo $eg->id; ?>"><?php echo $eg->title; ?></label>
            <?php } ?>
            </div>
            </div>
            <div class="product-enrollment-link animator-ready">
            <button class="enrollment-button pink">CONTINUE</button>
            </div>
            </div>
            */
            ?>

            <div class="enrollment-step step-3 animator-ready">
              <div class="enrollment-header animator-ready">
                <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['REQUEST_URI']; ?>" class="go-back">Go back</a>
                <a href="?step-1&c=true" class="cancel">Cancel</a>
              </div>
              <?php $checkout = WC()->checkout; ?>
              <?php // do_action( 'woocommerce_review_order_before_payment' ); ?>
              <?php // do_action( 'woocommerce_before_checkout_form', $checkout ); ?>
              <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
                <?php if ( $checkout->get_checkout_fields() ) : ?>
                  <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
                  <div class="col2-set" id="customer_details">
                    <div class="col-1">
                      <?php do_action( 'woocommerce_checkout_billing' ); ?>
                    </div>
                    <div class="col-2">
                      <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                    </div>
                  </div>
                  <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
                <?php endif; ?>
                <?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
                <h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
                <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
                <div id="order_review" class="woocommerce-checkout-review-order">
                  <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                </div>
                <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
              </form>

            </div>
            <?php echo do_shortcode('[ops_section checkout]'); ?>
          <?php }
        }
      }





      function kd_override_return_url( $return_url, $order )
      {
        if ( is_null($order) )
        return $return_url;

        // retrive products in order
        $order_id  = $order->get_id();
        foreach($order->get_items() as $key => $item)
        {
          $product_id = 0;
          $product_id = $item['product_id'];
          if ( $product_id > 0 )
          return get_the_permalink($product_id) . '?order_status=complete&oid=' . $order_id;
        }
        return $return_url;
      }




      // Add Zoom Webinar Data to Product Item meta data
      public function kd_woo_zoom_product_save( $post_id, $post, $update )
      {
        $product_type = isset($_POST['product-type']) ? $_POST['product-type'] : '';
        if ( $product_type == "stm_zoom" ) {
          $meeting_id = isset($_POST['_meeting_id']) ? $_POST['_meeting_id'] : 0;
          if ( isset($_POST['_meeting_id']) && $_POST['_meeting_id'] > 0 ) {
            $zoom_meta = get_post_meta($meeting_id);
            $stm_date = isset($zoom_meta['stm_date']) ? $zoom_meta['stm_date'] : '';
            if ( $stm_date != '' ) {
              update_post_meta( $post_id, '_stm_date', $stm_date );
            }
            foreach( $zoom_meta as $zm_key => $zm ) {
              if ( "stm_" == substr( $zm_key, 0, 4) ) {
                update_post_meta( $post_id, "_".$zm_key, $zm[0] );
              }
            }
          }
        }
      }



      //https://www.tychesoftwares.com/how-to-modify-the-cart-details-on-woocommerce-checkout-page/
      function remove_quantity_text( $cart_item, $cart_item_key )
      {
        $product_quantity= '';
        return $product_quantity;
      }
      function add_quantity( $product_title, $cart_item, $cart_item_key ) {

        /* Checkout page check */
        if (  is_checkout() ) {
          /* Get Cart of the user */
          $cart     = WC()->cart->get_cart();
          foreach ( $cart as $cart_key => $cart_value ){
            if ( $cart_key == $cart_item_key ){
              $product_id = $cart_item['product_id'];
              $_product   = $cart_item['data'] ;

              /* Step 1 : Add delete icon */
              $return_value = sprintf(
                '<a href="%s" class="remove" title="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                esc_url( WC()->cart->get_remove_url( $cart_key ) ),
                __( 'Remove this item', 'woocommerce' ),
                esc_attr( $product_id ),
                esc_attr( $_product->get_sku() )
              );

              /* Step 2 : Add product name */
              $return_value .= '&nbsp; <span class = "product_name" ><a style="color: white;" href="'.get_the_permalink($product_id).'">' . $product_title . '</a></span>' ;

              /* Step 3 : Add quantity selector */
              if ( $_product->is_sold_individually() ) {
                $return_value .= sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_key );
              } else {
                $return_value .= woocommerce_quantity_input( array(
                  'input_name'  => "cart[{$cart_key}][qty]",
                  'input_value' => $cart_item['quantity'],
                  'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
                  'min_value'   => '1'
                ), $_product, false );
              }
              return $return_value;
            }
          }
        }else{
          /*
          * It will return the product name on the cart page.
          * As the filter used on checkout and cart are same.
          */
          $_product   = $cart_item['data'] ;
          $product_permalink = $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '';
          if ( ! $product_permalink ) {
            $return_value = $_product->get_title() . '&nbsp;';
          } else {
            $return_value = sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_title());
          }
          return $return_value;
        }
      }


      /* Add js at the footer */
      function add_quanity_js(){
        if ( is_checkout() ) {
          wp_enqueue_script( 'checkout_script', plugins_url( '/js/add_quantity.js', __FILE__ ), '', '', false );
          $localize_script = array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
          );
          wp_localize_script( 'checkout_script', 'add_quantity', $localize_script );
        }
      }



      function load_ajax() {
        if ( !is_user_logged_in() ){
          add_action( 'wp_ajax_nopriv_update_order_review', array( $this, 'update_order_review' ) );
        } else{
          add_action( 'wp_ajax_update_order_review',        array( $this, 'update_order_review' ) );
        }
      }


      function update_order_review() {
        $values = array();
        parse_str($_POST['post_data'], $values);
        $cart = $values['cart'];
        foreach ( $cart as $cart_key => $cart_value ){
          WC()->cart->set_quantity( $cart_key, $cart_value['qty'], false );
          WC()->cart->calculate_totals();
          woocommerce_cart_totals();
        }
        wp_die();
      }



    } // class KDWOOZOOM
    $KDWOOZOOM = new KDWOOZOOM;
    $KDWOOZOOM->init();
