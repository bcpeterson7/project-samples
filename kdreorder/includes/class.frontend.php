<?php
/**
 * KD Reorder Posts Plugin - class.frontend.php
 * Modifies the order of posts and custom post types based on the saved settings from the backend
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'KDReorder' ) ) :

  class KDReorderFrontend {

    public function __construct()
    {}



    public function init()
    {
      add_action('pre_get_posts', array( $this, 'kd_pre_get_posts' ) );
    }


    /**
     * kd_pre_get_posts description: display posts according to the custom order the user has set
     * @param  [type] $query
     * @return [type]
     */
    public function kd_pre_get_posts($query)
    {
      // We only want to alter the queries for the post types that we enabled  re-ordering for, in the plugin's settings page
      if ( is_admin() ) {
        // We're in the Dashboard/wp-admin area - do not modify ANY backend queries
        return;
      }
      if ( ! $query->is_main_query() ) {
        // Do not modify ANY non main queries
        return;
      }

      if ( !property_exists($query, 'query') )
        return $query;

      $kd_reorder_post_types = get_option("kd_reorder_post_types", '');
      $kd_reorder_post_types = ( is_string($kd_reorder_post_types) && $kd_reorder_post_types != '' ) ? maybe_unserialize($kd_reorder_post_types) : [];

      // Get the query array for taxonomy and term_id info
      $q = $query->query;
      if ( empty($q) )
        return;

      // Assign tax and term_id to vars
      $post_type = 'post';
      $taxonomy = 'cat';
      $term_id =  0;
      foreach( $q as $tax => $termID ) {
        $taxonomy = $tax;
        $term_id = $termID;
      }

      // For custom post types, we want the term_id, not term slug (term slug is returned by default for custom post types).
      // For regular posts this is not an issue as the category id is returned
      // We will also grab the post_type here for the query below
      if ( !is_numeric($term_id) ) {
        $term = get_term_by( 'slug', $term_id, $taxonomy );
        $post_type = get_taxonomy( $term->taxonomy )->object_type[0];
        $term_id = $term->term_id;
      }

      // Check if sorting is enabled for this post type
      if ( !in_array($post_type, $kd_reorder_post_types) || !isset($kd_reorder_post_types[$post_type]) || $kd_reorder_post_types[$post_type] !== true )
        return $query;

      // Retreive stored sorted order
      $option_key = "kdreorder_{$taxonomy}_{$term_id}";
      $post_order = get_option( $option_key, [] );
      $post_ids = array_keys($post_order);

      // Generate ordering query
      $post_type = 'inventory';
      $args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => [
          [
            'taxonomy' => $tax_name,
            'field'    => 'term_id',
            'terms'    => (int) $lookup_term
          ]
        ]
      ];
      if ( is_null( $post_ids ) || "NULL" == $post_ids ) {
        unset( $args['post__in'] );
        unset( $args['orderby'] );
      } else {
        $args['orderby'] = 'post__in';
        $args['post__in'] = $post_ids;
      }
      $query->set( 'post__in', $post_ids );
      $query->query_vars['orderby'] = 'post__in';
      return $query;
    }


  } // class KDReorderFrontend

endif;  // if ! class_exists( 'KDReorder' )

$KDReorderFrontend = new KDReorderFrontend;
$KDReorderFrontend->init();
