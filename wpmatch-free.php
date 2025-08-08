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
		$tables = array( 'profiles', 'profile_meta', 'photos', 'threads', 'messages', 'likes', 'blocks', 'reports', 'verifications', 'interests', 'interest_map' );
		foreach ( $tables as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$t}" );
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
	$target_ver    = '1';
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
		register_rest_route(
			$ns,
			'/profiles',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_profiles',
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'      => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'per_page'  => array(
						'type'    => 'integer',
						'default' => 20,
					),
					'age_min'   => array( 'type' => 'integer' ),
					'age_max'   => array( 'type' => 'integer' ),
					'region'    => array( 'type' => 'string' ),
					'verified'  => array( 'type' => 'boolean' ),
					'has_photo' => array( 'type' => 'boolean' ),
				),
			)
		);
		register_rest_route(
			$ns,
			'/profiles/(?P<user_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_profile_detail',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$ns,
			'/matches/me',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_matches_me',
				'permission_callback' => function ( $request ) {
					return is_user_logged_in(); },
			)
		);
		register_rest_route(
			$ns,
			'/likes/me',
			array(
				'methods'             => 'GET',
				'callback'            => 'wpmf_rest_likes_me',
				'permission_callback' => function ( $request ) {
					return is_user_logged_in(); },
			)
		);
	}
);

/**
 * REST API endpoint for profiles list.
 *
 * @param WP_REST_Request $req Request object.
 * @return WP_REST_Response Response object.
 * @since 0.1.0
 */
function wpmf_rest_profiles( WP_REST_Request $req ) {
	global $wpdb;
	$t     = $wpdb->prefix . 'wpmf_profiles';
	$page  = max( 1, (int) $req['page'] );
	$per   = min( 50, max( 1, (int) $req['per_page'] ) );
	$where = array( "status='active'" );
	$vars  = array();
	if ( isset( $req['age_min'] ) ) {
		$where[] = 'age >= %d';
		$vars[]  = (int) $req['age_min']; }
	if ( isset( $req['age_max'] ) ) {
		$where[] = 'age <= %d';
		$vars[]  = (int) $req['age_max']; }
	if ( ! empty( $req['region'] ) ) {
		$where[] = 'region = %s';
		$vars[]  = sanitize_text_field( $req['region'] ); }
	if ( isset( $req['verified'] ) ) {
		$where[] = 'verified = %d';
		$vars[]  = $req['verified'] ? 1 : 0; }
	$has_photo = $req['has_photo'] ?? null;
	$offset    = ( $page - 1 ) * $per;
	$sql       = "SELECT * FROM {$t} WHERE " . implode( ' AND ', $where ) . $wpdb->prepare( ' ORDER BY last_active DESC LIMIT %d OFFSET %d', $per, $offset );
	if ( $vars ) {
		$sql = $wpdb->prepare( "SELECT * FROM {$t} WHERE " . implode( ' AND ', $where ) . ' ORDER BY last_active DESC LIMIT %d OFFSET %d', array( ...$vars, $per, $offset ) ); }
	$list = $wpdb->get_results( $sql, ARRAY_A );
	if ( $has_photo !== null ) {
		$uids = array_map( fn( $r )=> (int) $r['user_id'], $list );
		if ( $uids ) {
			$pt   = $wpdb->prefix . 'wpmf_photos';
			$in   = implode( ',', array_fill( 0, count( $uids ), '%d' ) );
			$q    = $wpdb->prepare( "SELECT DISTINCT user_id FROM {$pt} WHERE is_primary=1 AND status='approved' AND user_id IN ($in)", $uids );
			$have = $wpdb->get_col( $q );
			$list = array_values( array_filter( $list, fn( $r )=> in_array( (string) $r['user_id'], $have, true ) ) );
		}
	}
	$current = get_current_user_id();
	if ( $current ) {
		$bt   = $wpdb->prefix . 'wpmf_blocks';
		$uids = array_map( fn( $r )=> (int) $r['user_id'], $list );
		if ( $uids ) {
			$in            = implode( ',', array_fill( 0, count( $uids ), '%d' ) );
			$q             = $wpdb->prepare( "SELECT DISTINCT target_user_id FROM {$bt} WHERE actor_id=%d AND target_user_id IN ($in)", array_merge( array( $current ), $uids ) );
			$blocked_by_me = $wpdb->get_col( $q );
			$q2            = $wpdb->prepare( "SELECT DISTINCT actor_id FROM {$bt} WHERE target_user_id=%d AND actor_id IN ($in)", array_merge( array( $current ), $uids ) );
			$blocked_me    = $wpdb->get_col( $q2 );
			$list          = array_values(
				array_filter(
					$list,
					function ( $r ) use ( $blocked_by_me, $blocked_me ) {
						$uid = (string) $r['user_id'];
						return ! in_array( $uid, $blocked_by_me, true ) && ! in_array( $uid, $blocked_me, true );
					}
				)
			);
		}
	}
	return rest_ensure_response( $list );
}

/**
 * REST API endpoint for single profile detail.
 *
 * @param WP_REST_Request $req Request object.
 * @return WP_REST_Response|WP_Error Response object or error.
 * @since 0.1.0
 */
function wpmf_rest_profile_detail( WP_REST_Request $req ) {
	$user_id = (int) $req['user_id'];
	$profile = wpmf_profile_get_by_user_id( $user_id );
	if ( ! $profile || $profile['status'] !== 'active' ) {
		return new WP_Error( 'not_found', __( 'Profile not found', 'wpmatch-free' ), array( 'status' => 404 ) ); }
	$current = get_current_user_id();
	if ( $current ) {
		global $wpdb;
		$bt      = $wpdb->prefix . 'wpmf_blocks';
		$blocked = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bt} WHERE (actor_id=%d AND target_user_id=%d) OR (actor_id=%d AND target_user_id=%d)", $current, $user_id, $user_id, $current ) );
		if ( $blocked ) {
			return new WP_Error( 'forbidden', __( 'You cannot view this profile', 'wpmatch-free' ), array( 'status' => 403 ) ); }
	}
	return rest_ensure_response( $profile );
}

/**
 * REST API endpoint for current user's matches.
 *
 * @param WP_REST_Request $req Request object.
 * @return WP_REST_Response Response object.
 * @since 0.1.0
 */
function wpmf_rest_matches_me( WP_REST_Request $req ) {
	$user_id = get_current_user_id();
	$me      = wpmf_profile_get_by_user_id( $user_id );
	if ( ! $me ) {
		return rest_ensure_response( array() ); }
	global $wpdb;
	$t     = $wpdb->prefix . 'wpmf_profiles';
	$where = array( "status='active'", $wpdb->prepare( 'user_id != %d', $user_id ) );
	if ( $me['region'] ) {
		$where[] = $wpdb->prepare( 'region = %s', $me['region'] ); }
	$sql  = 'SELECT * FROM ' . $t . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY last_active DESC LIMIT 20';
	$list = $wpdb->get_results( $sql, ARRAY_A );
	$bt   = $wpdb->prefix . 'wpmf_blocks';
	$uids = array_map( fn( $r )=> (int) $r['user_id'], $list );
	if ( $uids ) {
		$in            = implode( ',', array_fill( 0, count( $uids ), '%d' ) );
		$q             = $wpdb->prepare( "SELECT DISTINCT target_user_id FROM {$bt} WHERE actor_id=%d AND target_user_id IN ($in)", array_merge( array( $user_id ), $uids ) );
		$blocked_by_me = $wpdb->get_col( $q );
		$q2            = $wpdb->prepare( "SELECT DISTINCT actor_id FROM {$bt} WHERE target_user_id=%d AND actor_id IN ($in)", array_merge( array( $user_id ), $uids ) );
		$blocked_me    = $wpdb->get_col( $q2 );
		$list          = array_values(
			array_filter(
				$list,
				function ( $r ) use ( $blocked_by_me, $blocked_me ) {
					$uid = (string) $r['user_id'];
					return ! in_array( $uid, $blocked_by_me, true ) && ! in_array( $uid, $blocked_me, true );
				}
			)
		);
	}
	return rest_ensure_response( $list );
}

/**
 * REST API endpoint for users who liked current user.
 *
 * @param WP_REST_Request $req Request object.
 * @return WP_REST_Response Response object.
 * @since 0.1.0
 */
function wpmf_rest_likes_me( WP_REST_Request $req ) {
	$user_id = get_current_user_id();
	global $wpdb;
	$t    = $wpdb->prefix . 'wpmf_likes';
	$sql  = $wpdb->prepare( "SELECT actor_id, created_at FROM {$t} WHERE target_user_id=%d ORDER BY id DESC LIMIT 50", $user_id );
	$list = $wpdb->get_results( $sql, ARRAY_A );
	return rest_ensure_response( $list );
}
