<?php
/**
 * KD Reorder Posts Plugin - class.backend.php
 * Handles the functions to render and save the custom post type sorting pages in the dasboard
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'KDReorder' ) ) :

  class KDReorderDashboard {

    public function __construct() {}


    // Initialize the class
    public function init()
    {
      // Create menu pages
      add_action( 'admin_menu', array( $this, 'kd_reorder_menu_page' ) );
      // Add custom class to re-order screen
      add_filter( 'admin_body_class', array( $this, 'kd_admin_body_class') );
    }


    /**
     * kd_reorder_menu_page description: adds a settings page, and also links under post types for custom ordering.
     * @return
     */
    public function kd_reorder_menu_page()
    {
      // Adds a very basic settings page to choose what post types should have sorting enabled
      add_menu_page(
        __( 'KD Reorder Page Settings', 'kdreorder' ),
        'Reorder Settings',
        'manage_options',
        'kd-reorder/kd-reorder-admin.php',
        array( $this, 'kd_options_page'),
        'dashicons-sort',
        10
      );

      // Below adds a link to load the custom sorting page for the post type, if it is enabled on the plugin's settings page/
      $kd_reorder_post_types = maybe_unserialize(get_option("kd_reorder_post_types", $post_types));
      foreach( $kd_reorder_post_types as $key => $value ) {
        if ( $value == true ) {
          $obj = get_post_type_object( $key );
          $label = '';
          $label = $obj->labels->name;
          $parent_slug = '';
          $parent_slug = ( $key == "post") ? 'edit.php' : 'edit.php?post_type='.$key;
          add_submenu_page(
            $parent_slug,
            __( 'Re-order Items', 'kdreorder' ),
            __( 'Re-order '.ucwords($label), 'kdreorder' ),
            'manage_options',
            $key.'-kdreorder',
            array( $this, 'kd_reorder_items_page' ),
            99
          );
        }
      }
    }



    /**
     * kd_reorder_items_page description: renders the output for the post sorting pages
     * @return null
     */
    public function kd_reorder_items_page()
    {
      if ( !empty( $_POST ) && array_key_exists("submit", $_POST) )
        $this->kd_reorder_items_save();
      $post_type = ( isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';

      $lookup_term = ( isset($_POST['category'])) ? $_POST['category'] : '';
      $taxonomies = get_object_taxonomies($post_type, 'objects');

      // Begin Output
      ?>
      <div class="wrap">
        <h1 class="wp-heading-inline">Reorder <?php echo ucwords($plural_label); ?></h1>
        <form action="" method="post" id="kdreorder-choose-cat-form" class="kdreorder-form">
          <div class="tax-selector-container">
            <?php
            // Create a drop-down select menu to show the post type categories. This plugin creates a specific sort order for
            // each category and the first step is for the user to select the category.
            foreach ( $taxonomies as $tax ) {
              if ( $tax->labels->name == "Tags" || $tax->labels->name == "Formats" )
                continue;

              $tax_name = $tax->name;
              $tax_label = $tax->labels->name;

              ?>
              <label for="category">
                <span class="tax-selector-label">Choose <?php echo $tax_label; ?> To Re-Order</span>
                <select name="category" id="tax_selector" class="tax-selector">
                  <option value="">- Choose Category -</option>
                <?php

                $args = [
                  'taxonomy' => $tax_name,
                  'hide_empty' => false
                ];
                $tax_terms = get_terms( $args );
                foreach ( $tax_terms as $term ) {
                  $selected = '';
                  $selected = ( $lookup_term == $term->term_id ) ? ' selected="selected"': '';
                  ?>
                  <option value="<?php echo $term->term_id; ?>"<?php echo $selected; ?>><?php echo $term->name; ?></option>
                  <?php
                }

                ?>
                </select>
              </label>
              <?php
            }
            ?>
            <button id="kdreorder-cat-submit" class="form-submit" form="kdreorder-choose-cat-form" name="cat_submit" value="cat_submit" type="submit">Load Settings</button>
          </div>
        </form>
        <?php

        // Once a user selects a category, or term, to re-order the code below loads all of the posts (or custom posts) and
        // prepares them to be re-ordered in a drag-and-drop interface.
        if ( $lookup_term != '' ) {
          $option_key = "kdreorder_{$tax_name}_{$lookup_term}";
          $post_order = get_option( $option_key, [] );
          $post_ids = array_keys($post_order);
          $obj = get_post_type_object( $post_type );
          $plural_label = '';
          $plural_label = $obj->labels->name;

          // Get all posts in taxonomy
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
          $query_all_posts = new WP_Query( $args );
          $all_posts = $query_all_posts->posts;

          if ( is_null( $post_ids ) || "NULL" == $post_ids ) {
            unset( $args['post__in'] );
            unset( $args['orderby'] );
          } else {
            $args['orderby'] = 'post__in';
            $args['post__in'] = $post_ids;
          }
          $query = new WP_Query( $args );
          $reorder_posts = $query->posts;

          ?>
          <form action="" method="post" id="kdreorder-form" class="kdreorder-form">
            <input type="hidden" name="category" value="<?php echo $lookup_term; ?>" />
            <input type="hidden" name="taxonomy" value="<?php echo $tax_name; ?>" />
            <input type="hidden" name="term" value="<?php echo $lookup_term; ?>" />
            <div id="kdreorder-items-container">
              <div id="kdreorder-sortable">
            <?php
            foreach( $reorder_posts as $post ) {
              $post_id = 0;
              $post_id = $post->ID;
              foreach( $all_posts as $key => $apost ) {
                $apost_id = 0;
                $apost_id = $apost->ID;
                if ( $post_id == $apost_id ) {
                  unset($all_posts[$key]);
                  break;
                }
              }
              $this->echo_reorder_post_item_html( $post );
            }
            foreach( $all_posts as $post ) {
              $this->echo_reorder_post_item_html( $post );
            }
            ?>
              </div>
            </div>
            <button id="kdreorder-form-submit" class="form-submit" form="kdreorder-form" name="submit" value="submit" type="submit">Save Order</button>
          </form>
        <?php
        }
        ?>
      </div>
      <?php
    }



    /**
     * echo_reorder_post_item_html description: creates the HTML output for a single post item that can be grabbed and dragged around
     * @param  [object] $post  - WP_POST object
     * @return null
     */
    public function echo_reorder_post_item_html( $post )
    {
      $post_id = $post->ID;
      $thumbnail = '';
      $attr = [
        'class' => 'thumb-img attachment-' . $post_id,
        'alt' => trim(strip_tags( $post->post_excerpt )),
        'title' => trim(strip_tags( $post->post_title )),
      ];
      $thumbnail = get_the_post_thumbnail($post_id, [150,120], $attr);
      $thumbnail = get_the_post_thumbnail_url($post_id, [150,120]);
      $inventory_hull_num = '';
      $inventory_hull_num = get_post_meta($post_id, 'inventory_hull_num', true);
      if ( $thumbnail != '' ) {
      ?>
        <div class="thumbnail-item" title="<?php echo $post->post_title . " - {$inventory_hull_num}" ?>">
          <div class="thumbnail-img" style="background: #efefef url(<?php echo $thumbnail; ?>) no-repeat center / cover"></div>
          <span class="thumbnail-title"><?php echo $post->post_title; ?></span>
          <input class="menu-order" value="" type="text" name="menu_order[<?php echo $post_id; ?>]" />
        </div>
      <?php
      }
    }



    /**
     * kd_options_page description: this is the settings page for the plugin. It lists a checkbox for all the post_types
     * and allows the user to select
     * @return null
     */
    public function kd_options_page()
    {
      // Get a list of post_types for the website (post, page, and any custom post_types)
      $post_types = $this->kd_create_default_option_values();

      // Get saved values: what post types are we enabling sorting for
      $kd_reorder_post_types_saved = maybe_unserialize(get_option("kd_reorder_post_types", $post_types));
      $kd_reorder_post_types = [];
      foreach($post_types as $pt => $pt_) {
        $checked = ( array_key_exists($pt, $kd_reorder_post_types_saved) ) ? $kd_reorder_post_types_saved[$pt] : false;
        $kd_reorder_post_types[$pt] = $checked;
      }

      // Save New Values - if the form was submitted save the values
      if ( !empty($_POST) )
        $kd_reorder_post_types = $this->kd_save_reporder_post_types($kd_reorder_post_types);

      //Create Form Ouput:
      ?>
      <div class="wrap">
        <h2><?php echo esc_html( _x( 'KD Reorder Posts', 'Plugin Name / Settings Page Title', 'kdreorder' ) ); ?></h2>
        <form action="" method="POST">
          <?php // settings_fields( 'kd_reorder_posts' ); // Settings are so basic use a simple custom function below instead of WP built in settings_field ?>
          <?php $this->kd_reorder_posts_form_content($kd_reorder_post_types) ?>
          <?php submit_button(); ?>
        </form>
      </div>
      <?php

    }



    /**
     * kd_create_default_option_values description: returns an array of registered post_types
     * @return array description: an array of post types
     */
    public function kd_create_default_option_values() : array
    {
      $post_types = ["post" => ""];
      $custom_post_types = get_post_types(['public' => true, '_builtin' => false]);
      foreach( $custom_post_types as $type ) {
        $post_types[$type] = "";
      }
      return $post_types;
    }



    function kd_reorder_posts_form_content($kd_reorder_post_types)
    {
      echo '<div id="kd-reorder-post-types">';
      echo '<p>Choose post types to enable custom ordering for:</p>';
      foreach( $kd_reorder_post_types as $key => $value ) {
        if ( $key == "page") continue;
        $checked = '';
        $checked = ( true == $value ) ? ' checked="checked"' : '';
        echo  '<p><label for="chbx-'.$key.'"><input type="checkbox" id="chbx-'.$key.'" name="kd_reorder_post_types['.$key.']" value="Yes"'.$checked.'><span class="label">'.ucwords($key).'</label></p>';
      }
      echo '</div>';
    }



    /**
     * kd_save_reporder_post_types description: save function since I didn't use the usual settings_fields implementation
     * @param  [type] $kd_save_reporder_post_types - the old post types to allow reordering on
     * @return array                               - the new post types to allow reordering on
     */
    public function kd_save_reporder_post_types($kd_save_reporder_post_types) : array
    {
      $save_values = [];
      $kd_post_reorder_post_types = isset($_POST['kd_reorder_post_types']) ? $_POST['kd_reorder_post_types'] : [];
      if ( empty($kd_post_reorder_post_types) ) {
        $save_values = $this->kd_create_default_option_values();
      } else {
        foreach( $kd_save_reporder_post_types as $post_type => $value ) {
          if ( array_key_exists($post_type, $kd_post_reorder_post_types) ) {
            $save_values[$post_type] = true;
          } else {
            $save_values[$post_type] = false;
          }
        }
      }
      update_option("kd_reorder_post_types", maybe_serialize($save_values));
      return maybe_unserialize($save_values);
    }



    /**
     * kd_reorder_items_save description: saves the actual order that the post items were drag and dropped into
     * @return null
     */
    public function kd_reorder_items_save()
    {
      $taxonomy   = isset( $_POST['taxonomy'] )   ? $_POST['taxonomy']   : [];
      $term       = isset( $_POST['term'] )       ? $_POST['term']       : [];
      $menu_order = isset( $_POST['menu_order'] ) ? $_POST['menu_order'] : [];

      // fix tax issue for default posts
      if ( $taxonomy == "category" )
        $taxonomy = "cat";

      $option_key = "kdreorder_{$taxonomy}_{$term}";
      update_option( $option_key, $menu_order);
      return;
    }



    /**
     * kd_admin_body_class description: on the admin reordering page a class is added to the body that makes it easier to apply styles
     * and not mess up the rest of the dashboard
     * @param  [type] $classes               [description]
     * @return [type]          [description]
     */
    public function kd_admin_body_class( $classes ) {
      $screen = get_current_screen();
      $screen_id = $screen->id;
      if ( false !== strpos( $screen_id, 'kdreorder') ) {
        $classes .= " kdreorder-page";
      }
      return $classes;
    }



  } // class KDReorderDashboard

endif;  // if ! class_exists( 'KDReorder' )

$KDReorderDashboard = new KDReorderDashboard;
$KDReorderDashboard->init();
