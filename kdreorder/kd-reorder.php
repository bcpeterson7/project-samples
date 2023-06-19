<?php
/*
Plugin Name: KD Reorder Posts
Plugin URI: https://kismetwebdesign.com
Description: KD Reorder Posts - Reorder posts and custom post types with a drag and drop interface
Author: Kismet Design
Text Domain: kdreorder
Domain Path: /languages
Version: 1.0.0.3
Author URI: https://kismetwebdesign.com
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KDReorder' ) ) :

  class KDReorder {

    public function __construct()
    {}


    public function init()
    {
      // Make sure post thumbnails are supported
      add_theme_support( 'post-thumbnails' );
      // Conditionally include plugin files
      add_action( 'init', array( $this, 'kd_includes' ) );
      // Conditionally include script/style files
      add_action( 'init', array( $this, 'kd_load_scripts' ) );
      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) , array( $this, 'kd_add_settings_link' ) );
    }



    public function kd_includes()
    {
      // Load the following on admin pages only
      if ( false != is_admin() ) {
        require_once( plugin_dir_path( __FILE__ ) . 'includes/class.backend.php' );
      }

      // Frontend
      if ( false == is_admin() ) {
        require_once( plugin_dir_path( __FILE__ ) . 'includes/class.frontend.php' );
      }
    }


    public function kd_load_scripts()
    {
      // Load Admin Styles and Scripts on all admin pages
      if ( is_admin() ) {
        // CSS
        wp_register_style( 'kd-reorder',  plugins_url( '/assets/css/kd-reorder.css', __FILE__ ),'','1.0.0.0' );
        wp_enqueue_style( 'kd-reorder' );

        // JS
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-effects-core' );
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-droppable' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        $deps = [
          'jquery',
          'jquery-ui-core',
          'jquery-effects-core',
          'jquery-ui-draggable',
          'jquery-ui-droppable',
          'jquery-ui-sortable'
        ];
        wp_enqueue_script( 'kd-reorder', plugins_url( '/assets/js/kd-reorder.js', __FILE__ ), $dep, "1.0.0.0");
        wp_localize_script( 'kd-reorder', 'KDData', array(
          'pluginsUrl' => plugins_url(),
          'ajax_url' 	 => admin_url('admin-ajax.php'),
          'kd_nonce' 	 => wp_create_nonce('dp_nonce'),
          'rest_url'	 => rest_url(),
          'rest_nonce' => wp_create_nonce('wp_rest'),
          'namespace'	 => 'dataport',
          'site_url'	 => site_url(),
        ));
      }
    }



   /**
    * kd_add_settings_link description: add a settings link to the WP Dashboard
    * The settings page is very basic. It enables custom post sorting on the selected post types (and custom post types)
    * @param [type] $links  [description]
    */
    public function kd_add_settings_link( $links )
    {
      // Add a custom link to the settings area.
      $settings_link = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=kdreorder' ) ), _x( 'Settings', 'Plugin settings link on the plugins page', 'kdreorder' ) );
      array_unshift($links, $settings_link);
      return $links;
    }


  } // class KDReorder

endif;  // if ! class_exists( 'KDReorder' )

$KDReorder = new KDReorder;
$KDReorder->init();
