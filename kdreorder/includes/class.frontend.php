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
        // Do not modify ANY backend queries
        return;
      }
      if ( ! $query->is_main_query() ) {
        // Do not modify ANY non main queries
        return;
      }

      // We only want to modify queries where the inventory_tax is present
      if ( !property_exists($query, 'query') )
        return $query;

      $q = $query->query;
      // This is a hard code fix for a custom post type I created for the client's website
      if ( array_key_exists("inventory_tax", $q) ) {
        $taxonomy_slug = $q['inventory_tax'];
      } else {
        return $query;
      }

      $taxonomy = $query->tax_query->queries[0]["taxonomy"];
      $term_slug = $query->tax_query->queries[0]["terms"][0];
      $term = get_term_by( 'slug', $term_slug, $taxonomy );
      $term_id = $term->term_id;
      $option_key = "kdreorder_{$taxonomy}_{$term_id}";
      $post_order = get_option( $option_key );
      $post_ids = array_keys($post_order);
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
