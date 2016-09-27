<?php
/**
 * Plugin Name: The Events Calendar Extension: Members-Only Calendar
 * Description: This snippet will only let logged-in users see The Events Calendar’s views and widgets.
 * Version: 1.0.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1971
 * License: GPLv2 or later
 */

defined( 'WPINC' ) or die;

class Tribe__Extension__Members_Only_Calendar {

    /**
     * The semantic version number of this extension; should always match the plugin header.
     */
    const VERSION = '1.0.0';

    /**
     * Each plugin required by this extension
     *
     * @var array Plugins are listed in 'main class' => 'minimum version #' format
     */
    public $plugins_required = array(
        'Tribe__Events__Main' => '4.2'
    );

    /**
     * The constructor; delays initializing the extension until all other plugins are loaded.
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 100 );
    }

    /**
     * Extension hooks and initialization; exits if the extension is not authorized by Tribe Common to run.
     */
    public function init() {

        // Exit early if our framework is saying this extension should not run.
        if ( ! function_exists( 'tribe_register_plugin' ) || ! tribe_register_plugin( __FILE__, __CLASS__, self::VERSION, $this->plugins_required ) ) {
            return;
        }

        add_filter( 'posts_where', array( $this, 'restrict_events' ), 100 );
        add_filter( 'tribe_events_pre_get_posts', array( $this, 'redirect_from_events' ) );
    }

    /**
     * Redirect non-logged-in users away from events views. 
     *
     * @return void
     */
    public function redirect_from_events( $query ) {
    
        if ( is_user_logged_in() ) {
            return;
        }

        if ( ! $query->is_main_query() || ! $query->get( 'eventDisplay' ) ) {
            return;
        }

        // Look for a page with a slug of logged-in-users-only.
        $target_page = get_posts( array(
            'post_type' => 'page',
            'name'      => 'logged-in-users-only'
        ));

        // Use the target page URL if found, else use the home page URL
        if ( empty( $target_page ) ) {
            $url = get_home_url();
        } else {
            $target_page = current( $target_page );
            $url         = get_permalink( $target_page->ID );
        }

        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Remove events from queries; key for hiding events from widgets and shortcodes.
     *
     * @return string
     */ 
    public function restrict_events( $where_sql ) {
        
        global $wpdb;

        if ( is_user_logged_in() || ! class_exists( 'Tribe__Events__Main' ) ) {
            return $where_sql;
        }

        return $wpdb->prepare( " $where_sql AND $wpdb->posts.post_type <> %s ", Tribe__Events__Main::POSTTYPE );
    }
}

new Tribe__Extension__Members_Only_Calendar();
