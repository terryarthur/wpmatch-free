<?php
/**
 * Plugin Name: WP Match Free
 * Plugin URI: https://example.com/
 * Description: Privacy-first dating plugin with profiles, discovery, likes, messaging, and moderation.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.5
 * Author: Terry Arthur
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wpmatch-free
 * Domain Path: /languages
 *
 * @package WPMatchFree
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WPMATCH_FREE_VERSION = '0.1.0';
const WPMATCH_FREE_SLUG    = 'wpmatch-free';

// Define plugin constants
define( 'WPMATCH_VERSION', '0.1.0' );
define( 'WPMATCH_URL', plugin_dir_url( __FILE__ ) );
define( 'WPMATCH_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 */
function wpmatch_free_activate() {
	add_option( 'wpmatch_free_version', WPMATCH_FREE_VERSION );
	add_option( 'wpmatch_free_remove_data_on_uninstall', 0 );
	wpmatch_free_maybe_install();
}
register_activation_hook( __FILE__, 'wpmatch_free_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 */
function wpmatch_free_deactivate() {}
register_deactivation_hook( __FILE__, 'wpmatch_free_deactivate' );

/**
 * Plugin uninstall handler.
 *
 * @since 0.1.0
 */
function wpmatch_free_uninstall() {
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		return;
	}
	$remove = (int) get_option( 'wpmatch_free_remove_data_on_uninstall', 0 );
	if ( $remove ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'wpmf_';
		$tables = array( 'profiles', 'profile_meta', 'photos', 'threads', 'messages', 'typing_indicators', 'likes', 'blocks', 'reports', 'verifications', 'interests', 'interest_map', 'interactions', 'profile_views', 'calls', 'statuses' );
		foreach ( $tables as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$t}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		delete_option( 'wpmatch_free_db_version' );
		delete_option( 'wpmatch_free_version' );
		delete_option( 'wpmatch_free_remove_data_on_uninstall' );
	}
}

/**
 * Load plugin text domain for translations.
 *
 * @since 0.1.0
 */
function wpmatch_free_load_textdomain() {
	load_plugin_textdomain( 'wpmatch-free', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wpmatch_free_load_textdomain' );

/**
 * Install/upgrade database tables on activation or version change.
 *
 * @since 0.1.0
 */
function wpmatch_free_maybe_install() {
	global $wpdb;
	$installed_ver = get_option( 'wpmatch_free_db_version' );
	$target_ver    = '2'; // Bump when schema changes.
	if ( $installed_ver === $target_ver ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset_collate = $wpdb->get_charset_collate();
	$prefix          = $wpdb->prefix . 'wpmf_';
	$sql             = '';
	$sql            .= "CREATE TABLE {$prefix}profiles (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"user_id BIGINT UNSIGNED NOT NULL,\n" .
		"status VARCHAR(20) NOT NULL DEFAULT 'active',\n" .
		"visibility VARCHAR(20) NOT NULL DEFAULT 'members',\n" .
		"gender VARCHAR(50) NULL,\n" .
		"orientation VARCHAR(50) NULL,\n" .
		"age SMALLINT UNSIGNED NULL,\n" .
		"region VARCHAR(100) NULL,\n" .
		"headline VARCHAR(255) NULL,\n" .
		"bio TEXT NULL,\n" .
		"verified TINYINT(1) NOT NULL DEFAULT 0,\n" .
		"last_active DATETIME NULL,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"updated_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"UNIQUE KEY user_id (user_id),\n" .
		"KEY idx_region (region),\n" .
		"KEY idx_last_active (last_active)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}profile_meta (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"profile_id BIGINT UNSIGNED NOT NULL,\n" .
		"meta_key VARCHAR(191) NOT NULL,\n" .
		"meta_value LONGTEXT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"KEY profile_id (profile_id),\n" .
		"KEY meta_key (meta_key)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}photos (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"user_id BIGINT UNSIGNED NOT NULL,\n" .
		"attachment_id BIGINT UNSIGNED NULL,\n" .
		"is_primary TINYINT(1) NOT NULL DEFAULT 0,\n" .
		"status VARCHAR(20) NOT NULL DEFAULT 'pending',\n" .
		"moderation_notes TEXT NULL,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"KEY user_id (user_id),\n" .
		"KEY status (status)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}threads (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"updated_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}messages (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"thread_id BIGINT UNSIGNED NOT NULL,\n" .
		"sender_id BIGINT UNSIGNED NOT NULL,\n" .
		"recipient_id BIGINT UNSIGNED NOT NULL,\n" .
		"body TEXT NOT NULL,\n" .
		"status VARCHAR(20) NOT NULL DEFAULT 'sent',\n" .
		"created_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"KEY thread_id (thread_id),\n" .
		"KEY recipient_id (recipient_id)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}typing_indicators (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"thread_id BIGINT UNSIGNED NOT NULL,\n" .
		"user_id BIGINT UNSIGNED NOT NULL,\n" .
		"is_typing TINYINT(1) NOT NULL DEFAULT 0,\n" .
		"last_updated DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"UNIQUE KEY thread_user (thread_id, user_id),\n" .
		"KEY user_id (user_id),\n" .
		"KEY last_updated (last_updated)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}likes (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"actor_id BIGINT UNSIGNED NOT NULL,\n" .
		"target_user_id BIGINT UNSIGNED NOT NULL,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"UNIQUE KEY actor_target (actor_id, target_user_id),\n" .
		"KEY target_user_id (target_user_id)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}blocks (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"actor_id BIGINT UNSIGNED NOT NULL,\n" .
		"target_user_id BIGINT UNSIGNED NOT NULL,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"UNIQUE KEY actor_target (actor_id, target_user_id)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}reports (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"reporter_id BIGINT UNSIGNED NOT NULL,\n" .
		"target_type VARCHAR(20) NOT NULL,\n" .
		"target_id BIGINT UNSIGNED NOT NULL,\n" .
		"reason VARCHAR(50) NOT NULL,\n" .
		"notes TEXT NULL,\n" .
		"status VARCHAR(20) NOT NULL DEFAULT 'open',\n" .
		"created_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"KEY reporter_id (reporter_id),\n" .
		"KEY target (target_type, target_id)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}verifications (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"user_id BIGINT UNSIGNED NOT NULL,\n" .
		"data_ref VARCHAR(255) NULL,\n" .
		"status VARCHAR(20) NOT NULL DEFAULT 'pending',\n" .
		"reviewer_id BIGINT UNSIGNED NULL,\n" .
		"notes TEXT NULL,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"updated_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"KEY user_id (user_id),\n" .
		"KEY status (status)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}interests (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"slug VARCHAR(191) NOT NULL,\n" .
		"name VARCHAR(191) NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"UNIQUE KEY slug (slug)\n" .
		") {$charset_collate};\n";
	$sql            .= "CREATE TABLE {$prefix}interest_map (\n" .
		"profile_id BIGINT UNSIGNED NOT NULL,\n" .
		"interest_id BIGINT UNSIGNED NOT NULL,\n" .
		"PRIMARY KEY  (profile_id, interest_id),\n" .
		"KEY interest_id (interest_id)\n" .
		") {$charset_collate};\n";

	// Profile fields tables for custom field system
	$sql .= "CREATE TABLE {$wpdb->prefix}wpmatch_profile_fields (\n" .
		"field_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"field_key VARCHAR(64) NOT NULL,\n" .
		"field_type VARCHAR(20) NOT NULL,\n" .
		"field_label VARCHAR(128) NOT NULL,\n" .
		"field_group VARCHAR(64) NOT NULL,\n" .
		"is_required TINYINT(1) NOT NULL DEFAULT 0,\n" .
		"searchable TINYINT(1) NOT NULL DEFAULT 0,\n" .
		"options LONGTEXT NULL,\n" .
		"display_order INT NOT NULL DEFAULT 0,\n" .
		"PRIMARY KEY (field_id),\n" .
		"UNIQUE KEY field_key (field_key),\n" .
		"KEY field_group (field_group),\n" .
		"KEY display_order (display_order)\n" .
		") {$charset_collate};\n";

	$sql .= "CREATE TABLE {$wpdb->prefix}wpmatch_profile_values (\n" .
		"value_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"user_id BIGINT UNSIGNED NOT NULL,\n" .
		"field_id BIGINT UNSIGNED NOT NULL,\n" .
		"field_value LONGTEXT NULL,\n" .
		"privacy_level VARCHAR(20) NOT NULL DEFAULT 'public',\n" .
		"verified TINYINT(1) NOT NULL DEFAULT 0,\n" .
		"PRIMARY KEY (value_id),\n" .
		"UNIQUE KEY user_field (user_id, field_id),\n" .
		"KEY user_id (user_id),\n" .
		"KEY field_id (field_id)\n" .
		") {$charset_collate};\n";

	// Interactions table for winks, gifts, and enhanced likes
	$sql .= "CREATE TABLE {$prefix}interactions (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"sender_id BIGINT UNSIGNED NOT NULL,\n" .
		"recipient_id BIGINT UNSIGNED NOT NULL,\n" .
		"interaction_type VARCHAR(20) NOT NULL DEFAULT 'like',\n" .
		"metadata LONGTEXT NULL,\n" .
		"status VARCHAR(20) NOT NULL DEFAULT 'sent',\n" .
		"created_at DATETIME NOT NULL,\n" .
		"seen_at DATETIME NULL,\n" .
		"PRIMARY KEY (id),\n" .
		"KEY sender_id (sender_id),\n" .
		"KEY recipient_id (recipient_id),\n" .
		"KEY interaction_type (interaction_type),\n" .
		"KEY status (status),\n" .
		"KEY created_at (created_at),\n" .
		"UNIQUE KEY unique_daily_interaction (sender_id, recipient_id, interaction_type, DATE(created_at))\n" .
		") {$charset_collate};\n";

	// Profile views table for tracking who viewed profiles
	$sql .= "CREATE TABLE {$prefix}profile_views (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"viewer_id BIGINT UNSIGNED NOT NULL,\n" .
		"viewed_user_id BIGINT UNSIGNED NOT NULL,\n" .
		"view_date DATE NOT NULL,\n" .
		"view_count TINYINT UNSIGNED NOT NULL DEFAULT 1,\n" .
		"last_viewed_at DATETIME NOT NULL,\n" .
		"first_viewed_at DATETIME NOT NULL,\n" .
		"source VARCHAR(50) NULL DEFAULT 'profile',\n" .
		"PRIMARY KEY (id),\n" .
		"KEY viewer_id (viewer_id),\n" .
		"KEY viewed_user_id (viewed_user_id),\n" .
		"KEY view_date (view_date),\n" .
		"KEY last_viewed_at (last_viewed_at),\n" .
		"UNIQUE KEY unique_daily_view (viewer_id, viewed_user_id, view_date)\n" .
		") {$charset_collate};\n";

	// WebRTC calls table for call management and history
	$sql .= "CREATE TABLE {$prefix}calls (
" .
		'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
' .
		'call_id VARCHAR(36) NOT NULL,
' .
		'caller_id BIGINT UNSIGNED NOT NULL,
' .
		'recipient_id BIGINT UNSIGNED NOT NULL,
' .
		"call_type VARCHAR(10) NOT NULL DEFAULT 'video',
" .
		"status VARCHAR(20) NOT NULL DEFAULT 'pending',
" .
		'started_at DATETIME NULL,
' .
		'ended_at DATETIME NULL,
' .
		'duration_seconds INT UNSIGNED NULL,
' .
		'end_reason VARCHAR(50) NULL,
' .
		'signaling_data LONGTEXT NULL,
' .
		'created_at DATETIME NOT NULL,
' .
		'updated_at DATETIME NOT NULL,
' .
		'PRIMARY KEY (id),
' .
		'UNIQUE KEY call_id (call_id),
' .
		'KEY caller_id (caller_id),
' .
		'KEY recipient_id (recipient_id),
' .
		'KEY status (status),
' .
		'KEY created_at (created_at)
' .
		") {$charset_collate};
";

	// NEW: Status updates table (since DB version 2).
	$sql .= "CREATE TABLE {$prefix}statuses (\n" .
		"id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
		"user_id BIGINT UNSIGNED NOT NULL,\n" .
		"content TEXT NOT NULL,\n" .
		"mood VARCHAR(32) NULL,\n" .
		"visibility VARCHAR(16) NOT NULL DEFAULT 'public',\n" .
		"status VARCHAR(16) NOT NULL DEFAULT 'active',\n" .
		"flags_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,\n" .
		"expires_at DATETIME NULL,\n" .
		"created_at DATETIME NOT NULL,\n" .
		"updated_at DATETIME NOT NULL,\n" .
		"PRIMARY KEY  (id),\n" .
		"KEY user_created (user_id, created_at),\n" .
		"KEY visibility_created (visibility, created_at),\n" .
		"KEY status (status),\n" .
		"KEY expires_at (expires_at)\n" .
		") {$charset_collate};\n";

	dbDelta( $sql );
	update_option( 'wpmatch_free_db_version', $target_ver );
}



require_once __DIR__ . '/includes/admin/pages.php';


/**
 * Add custom user roles on activation.
 *
 * @since 0.1.0
 */
function wpmatch_free_add_roles() {
	add_role( 'wpmf_member', __( 'Member', 'wpmatch-free' ), array( 'read' => true ) );
	add_role( 'wpmf_moderator', __( 'Moderator', 'wpmatch-free' ), array( 'read' => true ) );
}
register_activation_hook( __FILE__, 'wpmatch_free_add_roles' );

/**
 * Add custom capabilities to user roles.
 *
 * @since 0.1.0
 */
function wpmatch_free_add_caps() {
	$roles = array( 'administrator', 'editor', 'wpmf_moderator' );
	$caps  = array(
		'dating_edit_profile',
		'dating_upload_photo',
		'dating_message',
		'dating_like',
		'dating_block',
		'dating_report',
		'dating_moderate',
		'dating_verify',
		'dating_view_reports',
		'dating_use_advanced_filters',
	);
	foreach ( $roles as $r ) {
		$role = get_role( $r );
		if ( $role ) {
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
}
register_activation_hook( __FILE__, 'wpmatch_free_add_caps' );


/**
 * Register plugin settings and fields.
 *
 * @since 0.1.0
 */
function wpmatch_free_register_settings() {
	register_setting(
		'wpmatch_free_settings',
		'wpmatch_free_remove_data_on_uninstall',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		)
	);

	add_settings_section( 'wpmatch_free_general', __( 'General', 'wpmatch-free' ), '__return_false', 'wpmatch_free' );

	add_settings_section( 'wpmatch_free_moderation', __( 'Moderation & Safety', 'wpmatch-free' ), '__return_false', 'wpmatch_free' );
	add_settings_section( 'wpmatch_free_limits', __( 'Rate Limits', 'wpmatch-free' ), '__return_false', 'wpmatch_free' );
	add_settings_section( 'wpmatch_free_verification', __( 'Verification', 'wpmatch-free' ), '__return_false', 'wpmatch_free' );

	add_settings_field(
		'wpmatch_free_remove_data_on_uninstall',
		__( 'Remove data on uninstall', 'wpmatch-free' ),
		'wpmatch_free_render_remove_data_field',
		'wpmatch_free',
		'wpmatch_free_general'
	);

	register_setting(
		'wpmatch_free_settings',
		'wpmf_photo_moderation_mode',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'post',
		)
	);
	register_setting(
		'wpmatch_free_settings',
		'wpmf_word_filter',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		)
	);
	register_setting(
		'wpmatch_free_settings',
		'wpmf_messages_per_day',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 20,
		)
	);
	register_setting(
		'wpmatch_free_settings',
		'wpmf_likes_per_day',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 50,
		)
	);
	register_setting(
		'wpmatch_free_settings',
		'wpmf_verification_enabled',
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 1,
		)
	);

	add_settings_field( 'wpmf_photo_moderation_mode', __( 'Photo moderation', 'wpmatch-free' ), 'wpmf_render_select_photo_moderation', 'wpmatch_free', 'wpmatch_free_moderation' );
	add_settings_field( 'wpmf_word_filter', __( 'Word blacklist (comma-separated)', 'wpmatch-free' ), 'wpmf_render_word_filter', 'wpmatch_free', 'wpmatch_free_moderation' );
	add_settings_field( 'wpmf_messages_per_day', __( 'Messages per day', 'wpmatch-free' ), 'wpmf_render_messages_per_day', 'wpmatch_free', 'wpmatch_free_limits' );
	add_settings_field( 'wpmf_likes_per_day', __( 'Likes per day', 'wpmatch-free' ), 'wpmf_render_likes_per_day', 'wpmatch_free', 'wpmatch_free_limits' );
	add_settings_field( 'wpmf_verification_enabled', __( 'Enable verification', 'wpmatch-free' ), 'wpmf_render_verification_enabled', 'wpmatch_free', 'wpmatch_free_verification' );
}
add_action( 'admin_init', 'wpmatch_free_register_settings' );

function wpmatch_free_render_remove_data_field() {
	$value = (int) get_option( 'wpmatch_free_remove_data_on_uninstall', 0 );
	echo '<label><input type="checkbox" name="wpmatch_free_remove_data_on_uninstall" value="1" ' . checked( 1, $value, false ) . ' /> ' . esc_html__( 'Delete all plugin data on uninstall', 'wpmatch-free' ) . '</label>';
}

function wpmf_render_select_photo_moderation() {
	$val = get_option( 'wpmf_photo_moderation_mode', 'post' );
	echo '<select name="wpmf_photo_moderation_mode">';
	foreach ( array(
		'pre'  => __( 'Require approval before visible', 'wpmatch-free' ),
		'post' => __( 'Visible immediately, review later', 'wpmatch-free' ),
	) as $k => $label ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

function wpmf_render_word_filter() {
	$val = get_option( 'wpmf_word_filter', '' );
	echo '<textarea name="wpmf_word_filter" rows="3" cols="60">' . esc_textarea( $val ) . '</textarea>';
}

function wpmf_render_messages_per_day() {
	$val = (int) get_option( 'wpmf_messages_per_day', 20 );
	echo '<input type="number" min="0" name="wpmf_messages_per_day" value="' . esc_attr( $val ) . '" />';
}

function wpmf_render_likes_per_day() {
	$val = (int) get_option( 'wpmf_likes_per_day', 50 );
	echo '<input type="number" min="0" name="wpmf_likes_per_day" value="' . esc_attr( $val ) . '" />';
}

function wpmf_render_verification_enabled() {
	$val = (int) get_option( 'wpmf_verification_enabled', 1 );
	echo '<label><input type="checkbox" name="wpmf_verification_enabled" value="1" ' . checked( 1, $val, false ) . ' /> ' . esc_html__( 'Enable verification workflow', 'wpmatch-free' ) . '</label>';
}

/**
 * Render the main settings page.
 *
 * @since 0.1.0
 */
function wpmatch_free_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'WP Match Settings', 'wpmatch-free' ) . '</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'wpmatch_free_settings' );
	do_settings_sections( 'wpmatch_free' );
	submit_button();
	echo '</form>';
	echo '</div>';
}

/**
 * Enqueue admin assets.
 *
 * @since 0.1.0
 */
function wpmatch_free_assets() {
	$screen = is_admin() ? get_current_screen() : null;
	if ( is_admin() && $screen && 'toplevel_page_wpmatch-free' === $screen->id ) {
		wp_enqueue_style( 'wpmatch-free-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), WPMATCH_FREE_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'wpmatch_free_assets' );

require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/db-optimization.php';
require_once __DIR__ . '/includes/pagination.php';
require_once __DIR__ . '/includes/async-processing.php';
require_once __DIR__ . '/includes/db-profiles.php';
require_once __DIR__ . '/includes/db-photos.php';
require_once __DIR__ . '/includes/db-messages.php';
require_once __DIR__ . '/includes/db-likes.php';
require_once __DIR__ . '/includes/db-interactions.php';
require_once __DIR__ . '/includes/db-profile-views.php';
require_once __DIR__ . '/includes/db-statuses.php';
require_once __DIR__ . '/includes/db-calls.php';
require_once __DIR__ . '/includes/shortcodes.php';
require_once __DIR__ . '/includes/blocks.php';
require_once __DIR__ . '/includes/privacy.php';

// Include our new admin and frontend classes
require_once __DIR__ . '/admin/class-wpmatch-admin.php';
require_once __DIR__ . '/includes/class-wpmatch-frontend.php';
require_once __DIR__ . '/includes/class-wpmatch-demo.php';
require_once __DIR__ . '/includes/class-wpmatch-setup.php';
require_once __DIR__ . '/includes/class-wpmatch-addon-framework.php';
add_action(
	'wp_enqueue_scripts',
	function () {
		// Enqueue Tailwind CSS CDN
		wp_enqueue_style(
			'wpmf-tailwind',
			'https://cdn.tailwindcss.com',
			array(),
			'3.4.0'
		);

		// Enqueue our custom styles after Tailwind
		wp_enqueue_style( 'wpmf-blocks' );
	}
);
add_action(
	'enqueue_block_editor_assets',
	function () {
		wp_enqueue_script( 'wpmf-blocks' );
		wp_enqueue_style( 'wpmf-blocks' );
	}
);

add_action(
	'rest_api_init',
	function () {
		$ns = 'wpmatch-free/v1';

		// Statuses: list global
		register_rest_route(
			$ns,
			'/statuses',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_statuses_list',
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 10,
					),
					'user_id'  => array( 'type' => 'integer' ), // optional filter by user.
				),
			)
		);

		// Statuses: current user's (shortcut)
		register_rest_route(
			$ns,
			'/statuses/mine',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_statuses_mine',
				'permission_callback' => function () {
					return is_user_logged_in(); },
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 10,
					),
				),
			)
		);

		// Status detail
		register_rest_route(
			$ns,
			'/statuses/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_status_detail',
				'permission_callback' => '__return_true',
			)
		);

		// Create status
		register_rest_route(
			$ns,
			'/statuses',
			array(
				'methods'             => 'POST',
				'callback'            => 'wpmf_rest_status_create',
				'permission_callback' => function () {
					return is_user_logged_in(); },
				'args'                => array(
					'content'    => array(
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => function ( $param ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							return is_string( $param ) && strlen( trim( wp_strip_all_tags( $param ) ) ) >= (int) apply_filters( 'wpmf_status_min_length', 2 );
						},
					),
					'visibility' => array( 'type' => 'string' ),
					'mood'       => array( 'type' => 'string' ),
					'expires_at' => array( 'type' => 'string' ),
				),
			)
		);

		// Delete status
		register_rest_route(
			$ns,
			'/statuses/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => 'wpmf_rest_status_delete',
				'permission_callback' => function () {
					return is_user_logged_in(); },
			)
		);

		// Flag status
		register_rest_route(
			$ns,
			'/statuses/(?P<id>\d+)/flag',
			array(
				'methods'             => 'POST',
				'callback'            => 'wpmf_rest_status_flag',
				'permission_callback' => function () {
					return is_user_logged_in(); },
				'args'                => array(
					'reason' => array( 'type' => 'string' ),
				),
			)
		);

		// WebRTC Calls: Create call
		register_rest_route(
			$ns,
			'/calls',
			array(
				'methods'             => 'POST',
				'callback'            => 'wpmf_rest_call_create',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'recipient_id' => array( 
						'type' => 'integer',
						'required' => true 
					),
					'call_type'    => array( 
						'type' => 'string',
						'default' => 'video' 
					),
				),
			)
		);

		// WebRTC Calls: Update call status
		register_rest_route(
			$ns,
			'/calls/(?P<call_id>[a-f0-9-]+)/status',
			array(
				'methods'             => 'PUT',
				'callback'            => 'wpmf_rest_call_update_status',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'status'     => array( 
						'type' => 'string',
						'required' => true 
					),
					'end_reason' => array( 
						'type' => 'string' 
					),
				),
			)
		);

		// WebRTC Calls: Update signaling data
		register_rest_route(
			$ns,
			'/calls/(?P<call_id>[a-f0-9-]+)/signaling',
			array(
				'methods'             => 'PUT',
				'callback'            => 'wpmf_rest_call_update_signaling',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'signaling_data' => array( 
						'type' => 'object',
						'required' => true 
					),
				),
			)
		);

		// WebRTC Calls: Get call details
		register_rest_route(
			$ns,
			'/calls/(?P<call_id>[a-f0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_call_get',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// WebRTC Calls: Get user's active calls
		register_rest_route(
			$ns,
			'/calls/active',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_calls_active',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// WebRTC Calls: Get user's call history
		register_rest_route(
			$ns,
			'/calls/history',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_calls_history',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'limit'  => array( 
						'type' => 'integer',
						'default' => 20 
					),
					'offset' => array( 
						'type' => 'integer',
						'default' => 0 
					),
					'type'   => array( 
						'type' => 'string',
						'default' => 'all' 
					),
				),
			)
		);

		// WebRTC Calls: Get pending calls for current user
		register_rest_route(
			$ns,
			'/calls/pending',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_calls_pending',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}
);

/**
 * Prepare a status row for REST response.
 *
 * @param array $row DB row.
 * @param int   $viewer_id Viewer ID.
 * @return array
 */
function wpmf_status_prepare_for_response( $row, $viewer_id ) {
	if ( empty( $row ) ) {
		return array();
	}
	$viewer_id = (int) $viewer_id;
	$user      = get_user_by( 'id', (int) $row['user_id'] );
	$mine      = $viewer_id === (int) $row['user_id'];
	$item      = array(
		'id'         => (int) $row['id'],
		'user_id'    => (int) $row['user_id'],
		'content'    => $row['content'],
		'mood'       => $row['mood'],
		'visibility' => $row['visibility'],
		'created_at' => $row['created_at'],
		'updated_at' => $row['updated_at'],
		'expires_at' => $row['expires_at'],
		'mine'       => $mine,
	);
	if ( $user ) {
		$item['user'] = array(
			'id'           => $user->ID,
			'display_name' => $user->display_name,
			'avatar'       => get_avatar_url( $user->ID, array( 'size' => 64 ) ),
		);
	}
	// Include moderation meta only for owner or users with moderate cap.
	if ( $mine || current_user_can( 'dating_moderate' ) ) {
		$item['flags_count']  = (int) $row['flags_count'];
		$item['status_state'] = $row['status'];
	}
	return $item;
}

/**
 * GET /statuses handler.
 */
function wpmf_rest_statuses_list( WP_REST_Request $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$user_filter = isset( $request['user_id'] ) ? (int) $request['user_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$viewer_id   = get_current_user_id();
	$page        = max( 1, (int) $request['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page    = (int) $request['per_page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page    = max( 1, min( 50, $per_page ) );
	$args        = array(
		'page'     => $page,
		'per_page' => $per_page,
	);
	if ( $user_filter > 0 ) {
		$rows  = wpmf_status_list_by_user( $user_filter, $viewer_id, $args );
		$total = wpmf_status_count_by_user( $user_filter, $viewer_id );
	} else {
		$rows  = wpmf_status_list_global( $viewer_id, $args );
		$total = wpmf_status_count_global( $viewer_id );
	}
	$data = array_map(
		function ( $r ) use ( $viewer_id ) {
			return wpmf_status_prepare_for_response( $r, $viewer_id );
		},
		$rows
	);
	$response = rest_ensure_response( $data );
	$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;
	$response->header( 'X-WPMF-Total', (string) $total );
	$response->header( 'X-WPMF-TotalPages', (string) $total_pages );
	// Standard WordPress style headers for compatibility.
	$response->header( 'X-WP-Total', (string) $total );
	$response->header( 'X-WP-TotalPages', (string) $total_pages );
	// RFC5988 Link headers for pagination.
	if ( $total_pages > 0 ) {
		$links = array();
		$base  = rest_url( 'wpmatch-free/v1/statuses' );
		$query_args = array( 'per_page' => $per_page );
		if ( $user_filter > 0 ) {
			$query_args['user_id'] = $user_filter;
		}
		if ( $page > 1 ) {
			$prev_args = $query_args;
			$prev_args['page'] = $page - 1;
			$prev_url  = add_query_arg( $prev_args, $base );
			/* translators: %s: URL for previous page of results. */
			$links[]   = sprintf( '<%s>; rel="prev"', esc_url_raw( $prev_url ) );
		}
		if ( $page < $total_pages ) {
			$next_args = $query_args;
			$next_args['page'] = $page + 1;
			$next_url  = add_query_arg( $next_args, $base );
			/* translators: %s: URL for next page of results. */
			$links[]   = sprintf( '<%s>; rel="next"', esc_url_raw( $next_url ) );
		}
		if ( ! empty( $links ) ) {
			$response->header( 'Link', implode( ', ', $links ) );
		}
	}
	return $response;
}

/**
 * GET /statuses/mine
 */
function wpmf_rest_statuses_mine( WP_REST_Request $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$viewer_id = get_current_user_id();
	$page      = max( 1, (int) $request['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page  = (int) $request['per_page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$per_page  = max( 1, min( 50, $per_page ) );
	$args      = array(
		'page'     => $page,
		'per_page' => $per_page,
	);
	$rows      = wpmf_status_list_by_user( $viewer_id, $viewer_id, $args );
	$total     = wpmf_status_count_by_user( $viewer_id, $viewer_id );
	$data      = array_map(
		function ( $r ) use ( $viewer_id ) {
			return wpmf_status_prepare_for_response( $r, $viewer_id );
		},
		$rows
	);
	$response = rest_ensure_response( $data );
	$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;
	$response->header( 'X-WPMF-Total', (string) $total );
	$response->header( 'X-WPMF-TotalPages', (string) $total_pages );
	$response->header( 'X-WP-Total', (string) $total );
	$response->header( 'X-WP-TotalPages', (string) $total_pages );
	// RFC5988 Link headers for pagination.
	if ( $total_pages > 0 ) {
		$links = array();
		$base  = rest_url( 'wpmatch-free/v1/statuses/mine' );
		$query_args = array( 'per_page' => $per_page );
		if ( $page > 1 ) {
			$prev_args = $query_args;
			$prev_args['page'] = $page - 1;
			$prev_url  = add_query_arg( $prev_args, $base );
			/* translators: %s: URL for previous page of results. */
			$links[]   = sprintf( '<%s>; rel="prev"', esc_url_raw( $prev_url ) );
		}
		if ( $page < $total_pages ) {
			$next_args = $query_args;
			$next_args['page'] = $page + 1;
			$next_url  = add_query_arg( $next_args, $base );
			/* translators: %s: URL for next page of results. */
			$links[]   = sprintf( '<%s>; rel="next"', esc_url_raw( $next_url ) );
		}
		if ( ! empty( $links ) ) {
			$response->header( 'Link', implode( ', ', $links ) );
		}
	}
	return $response;
}

/**
 * GET /statuses/{id}
 */
function wpmf_rest_status_detail( WP_REST_Request $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$viewer_id = get_current_user_id();
	$status_id = (int) $request['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$result    = wpmf_status_get_by_id( $status_id, $viewer_id );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	return rest_ensure_response( wpmf_status_prepare_for_response( $result, $viewer_id ) );
}

/**
 * POST /statuses
 */
function wpmf_rest_status_create( WP_REST_Request $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$user_id = get_current_user_id();
	$content = (string) $request['content']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$args    = array();
	if ( isset( $request['visibility'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['visibility'] = sanitize_text_field( $request['visibility'] );
	}
	if ( isset( $request['mood'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['mood'] = sanitize_text_field( $request['mood'] );
	}
	if ( isset( $request['expires_at'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['expires_at'] = sanitize_text_field( $request['expires_at'] );
	}
	$status_id = wpmf_status_create( $user_id, $content, $args );
	if ( is_wp_error( $status_id ) ) {
		return $status_id;
	}
	$row = wpmf_status_get_by_id( $status_id, $user_id );
	return rest_ensure_response( wpmf_status_prepare_for_response( $row, $user_id ) );
}

/**
 * DELETE /statuses/{id}
 */
function wpmf_rest_status_delete( WP_REST_Request $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$user_id   = get_current_user_id();
	$status_id = (int) $request['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$result    = wpmf_status_delete( $status_id, $user_id );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	return rest_ensure_response(
		array(
			'deleted' => true,
			'id'      => $status_id,
		)
	);
}

/**
 * POST /statuses/{id}/flag
 */
function wpmf_rest_status_flag( WP_REST_Request $request ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$user_id   = get_current_user_id();
	$status_id = (int) $request['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$reason    = isset( $request['reason'] ) ? sanitize_text_field( $request['reason'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$result    = wpmf_status_flag( $status_id, $user_id, $reason );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	$row = wpmf_status_get_by_id( $status_id, $user_id );
	if ( is_wp_error( $row ) ) {
		// If now flagged / hidden and cannot view, still return success minimal.
		return rest_ensure_response(
			array(
				'flagged' => true,
				'id'      => $status_id,
			)
		);
	}
	return rest_ensure_response(
		array(
			'flagged' => true,
			'status'  => wpmf_status_prepare_for_response( $row, $user_id ),
		)
	);
}

/**
 * REST API: Create a new call
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_call_create( WP_REST_Request $request ) {
	$user_id      = get_current_user_id();
	$recipient_id = (int) $request['recipient_id'];
	$call_type    = sanitize_text_field( $request['call_type'] );
	
	// Validate recipient
	if ( empty( $recipient_id ) || $recipient_id === $user_id ) {
		return new WP_Error( 'invalid_recipient', 'Invalid recipient ID', array( 'status' => 400 ) );
	}
	
	// Create the call
	$call_db_id = wpmf_create_call( $user_id, $recipient_id, $call_type );
	
	if ( ! $call_db_id ) {
		return new WP_Error( 'call_creation_failed', 'Failed to create call', array( 'status' => 500 ) );
	}
	
	// Get the created call
	global $wpdb;
	$call = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}wpmf_calls WHERE id = %d",
		$call_db_id
	) );
	
	if ( ! $call ) {
		return new WP_Error( 'call_not_found', 'Call not found after creation', array( 'status' => 500 ) );
	}
	
	return rest_ensure_response( wpmf_prepare_call_for_response( $call, $user_id ) );
}

/**
 * REST API: Update call status
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_call_update_status( WP_REST_Request $request ) {
	$user_id    = get_current_user_id();
	$call_id    = sanitize_text_field( $request['call_id'] );
	$status     = sanitize_text_field( $request['status'] );
	$end_reason = isset( $request['end_reason'] ) ? sanitize_text_field( $request['end_reason'] ) : null;
	
	// Get the call to check permissions
	$call = wpmf_get_call_by_id( $call_id );
	if ( ! $call ) {
		return new WP_Error( 'call_not_found', 'Call not found', array( 'status' => 404 ) );
	}
	
	// Check if user is part of this call
	if ( (int) $call->caller_id !== $user_id && (int) $call->recipient_id !== $user_id ) {
		return new WP_Error( 'access_denied', 'You are not authorized to modify this call', array( 'status' => 403 ) );
	}
	
	// Update the status
	$success = wpmf_update_call_status( $call_id, $status, $end_reason );
	
	if ( ! $success ) {
		return new WP_Error( 'update_failed', 'Failed to update call status', array( 'status' => 500 ) );
	}
	
	// Get updated call
	$updated_call = wpmf_get_call_by_id( $call_id );
	
	return rest_ensure_response( wpmf_prepare_call_for_response( $updated_call, $user_id ) );
}

/**
 * REST API: Update call signaling data
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_call_update_signaling( WP_REST_Request $request ) {
	$user_id        = get_current_user_id();
	$call_id        = sanitize_text_field( $request['call_id'] );
	$signaling_data = $request['signaling_data'];
	
	// Get the call to check permissions
	$call = wpmf_get_call_by_id( $call_id );
	if ( ! $call ) {
		return new WP_Error( 'call_not_found', 'Call not found', array( 'status' => 404 ) );
	}
	
	// Check if user is part of this call
	if ( (int) $call->caller_id !== $user_id && (int) $call->recipient_id !== $user_id ) {
		return new WP_Error( 'access_denied', 'You are not authorized to modify this call', array( 'status' => 403 ) );
	}
	
	// Update signaling data
	$success = wpmf_update_call_signaling( $call_id, $signaling_data );
	
	if ( ! $success ) {
		return new WP_Error( 'update_failed', 'Failed to update signaling data', array( 'status' => 500 ) );
	}
	
	return rest_ensure_response( array( 'success' => true ) );
}

/**
 * REST API: Get call details
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_call_get( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$call_id = sanitize_text_field( $request['call_id'] );
	
	$call = wpmf_get_call_by_id( $call_id );
	if ( ! $call ) {
		return new WP_Error( 'call_not_found', 'Call not found', array( 'status' => 404 ) );
	}
	
	// Check if user is part of this call
	if ( (int) $call->caller_id !== $user_id && (int) $call->recipient_id !== $user_id ) {
		return new WP_Error( 'access_denied', 'You are not authorized to view this call', array( 'status' => 403 ) );
	}
	
	return rest_ensure_response( wpmf_prepare_call_for_response( $call, $user_id ) );
}

/**
 * REST API: Get user's active calls
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_calls_active( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	
	$calls = wpmf_get_user_active_calls( $user_id );
	
	$response_data = array_map( function( $call ) use ( $user_id ) {
		return wpmf_prepare_call_for_response( $call, $user_id );
	}, $calls );
	
	return rest_ensure_response( $response_data );
}

/**
 * REST API: Get user's call history
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_calls_history( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$limit   = (int) $request['limit'];
	$offset  = (int) $request['offset'];
	$type    = sanitize_text_field( $request['type'] );
	
	$calls = wpmf_get_user_call_history( $user_id, $limit, $offset, $type );
	
	$response_data = array_map( function( $call ) use ( $user_id ) {
		return wpmf_prepare_call_for_response( $call, $user_id );
	}, $calls );
	
	return rest_ensure_response( $response_data );
}

/**
 * REST API: Get user's pending calls
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function wpmf_rest_calls_pending( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	
	$calls = wpmf_get_user_pending_calls( $user_id );
	
	$response_data = array_map( function( $call ) use ( $user_id ) {
		return wpmf_prepare_call_for_response( $call, $user_id );
	}, $calls );
	
	return rest_ensure_response( $response_data );
}

/**
 * Prepare call data for REST response
 *
 * @param object $call    Call object from database.
 * @param int    $user_id Current user ID.
 * @return array Formatted call data.
 */
function wpmf_prepare_call_for_response( $call, $user_id ) {
	$signaling_data = null;
	if ( ! empty( $call->signaling_data ) ) {
		$signaling_data = json_decode( $call->signaling_data, true );
	}
	
	$is_caller = (int) $call->caller_id === $user_id;
	$other_user_id = $is_caller ? (int) $call->recipient_id : (int) $call->caller_id;
	$other_user = get_user_by( 'id', $other_user_id );
	
	return array(
		'id'             => $call->id,
		'call_id'        => $call->call_id,
		'caller_id'      => (int) $call->caller_id,
		'recipient_id'   => (int) $call->recipient_id,
		'call_type'      => $call->call_type,
		'status'         => $call->status,
		'started_at'     => $call->started_at,
		'ended_at'       => $call->ended_at,
		'duration_seconds' => (int) $call->duration_seconds,
		'end_reason'     => $call->end_reason,
		'created_at'     => $call->created_at,
		'updated_at'     => $call->updated_at,
		'direction'      => $is_caller ? 'outgoing' : 'incoming',
		'other_user'     => array(
			'id'           => $other_user_id,
			'display_name' => $other_user ? $other_user->display_name : '',
		),
		'signaling_data' => $signaling_data,
	);
}
