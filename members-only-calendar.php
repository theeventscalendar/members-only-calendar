<?php
/**
 * Plugin Name: The Events Calendar — Members-Only Calendar
 * Description: This snippet will only let logged-in users see The Events Calendar’s views and widgets.
 * Version: 1.0.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1x
 * License: GPLv2 or later
 */
 
defined( 'WPINC' ) or die;

/**
 * Redirect non-logged-in users away from events views. 
 *
 * @return void
 */
function redirect_from_events( $query ) {

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

add_filter( 'tribe_events_pre_get_posts', 'redirect_from_events' );

/**
 * Remove events from queries; key for hiding events from widgets and shortcodes.
 *
 * @return string
 */ 
function restrict_events( $where_sql ) {
	
	global $wpdb;
	
	if ( is_user_logged_in() || ! class_exists( 'Tribe__Events__Main' ) ) {
		return $where_sql;
	}

	return $wpdb->prepare( " $where_sql AND $wpdb->posts.post_type <> %s ", Tribe__Events__Main::POSTTYPE );
}

add_filter( 'posts_where', 'restrict_events', 100 );
