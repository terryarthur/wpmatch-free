<?php
/**
 * Plugin uninstall script.
 *
 * This file is called when the plugin is deleted via the WordPress admin.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user wants to remove data on uninstall.
$remove_data = (int) get_option( 'wpmatch_free_remove_data_on_uninstall', 0 );

if ( $remove_data ) {
	global $wpdb;

	// Remove custom database tables.
	$prefix = $wpdb->prefix . 'wpmf_';
	$tables = array(
		'profiles',
		'profile_meta',
		'photos',
		'threads',
		'messages',
		'likes',
		'blocks',
		'reports',
		'verifications',
		'interests',
		'interest_map',
	);

	foreach ( $tables as $table ) {
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $prefix . $table ) );
	}

	// Remove plugin options.
	delete_option( 'wpmatch_free_db_version' );
	delete_option( 'wpmatch_free_version' );
	delete_option( 'wpmatch_free_remove_data_on_uninstall' );
	delete_option( 'wpmf_photo_moderation_mode' );
	delete_option( 'wpmf_word_filter' );
	delete_option( 'wpmf_messages_per_day' );
	delete_option( 'wpmf_likes_per_day' );
	delete_option( 'wpmf_verification_enabled' );
}
