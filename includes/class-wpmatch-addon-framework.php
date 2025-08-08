<?php
/**
 * Addon Framework for WP Match Free
 *
 * Provides the foundation for premium addons including demo content packs,
 * extended functionality, and premium features.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Addon framework class
 *
 * Manages addon registration, licensing, and integration with the core plugin.
 * Provides hooks and filters for premium extensions.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */
class WPMatch_Addon_Framework {

	/**
	 * Registered addons
	 *
	 * @var array
	 */
	private static $addons = array();

	/**
	 * Initialize addon framework
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'load_addons' ), 5 );
		add_action( 'admin_init', array( $this, 'check_addon_updates' ) );
		add_filter( 'wpmatch_demo_user_limit', array( $this, 'filter_demo_user_limit' ) );
		add_filter( 'wpmatch_available_demo_packs', array( $this, 'get_available_demo_packs' ) );
	}

	/**
	 * Register an addon with the framework
	 *
	 * @since 1.0.0
	 * @param array $addon_data Addon information.
	 * @return bool Registration success.
	 */
	public static function register_addon( $addon_data ) {
		$required_fields = array( 'id', 'name', 'version', 'file' );
		
		foreach ( $required_fields as $field ) {
			if ( empty( $addon_data[ $field ] ) ) {
				return false;
			}
		}

		// Set defaults.
		$addon_data = wp_parse_args(
			$addon_data,
			array(
				'description'      => '',
				'author'           => '',
				'license_required' => true,
				'min_core_version' => '1.0.0',
				'demo_users_limit' => 0, // 0 = unlimited.
				'features'         => array(),
			)
		);

		self::$addons[ $addon_data['id'] ] = $addon_data;
		return true;
	}

	/**
	 * Get all registered addons
	 *
	 * @since 1.0.0
	 * @return array Registered addons.
	 */
	public static function get_addons() {
		return self::$addons;
	}

	/**
	 * Check if an addon is active and licensed
	 *
	 * @since 1.0.0
	 * @param string $addon_id Addon identifier.
	 * @return bool Addon status.
	 */
	public static function is_addon_active( $addon_id ) {
		if ( ! isset( self::$addons[ $addon_id ] ) ) {
			return false;
		}

		$addon = self::$addons[ $addon_id ];
		
		// Check if license is required and valid.
		if ( $addon['license_required'] ) {
			$license_key = get_option( "wpmatch_license_{$addon_id}" );
			return ! empty( $license_key ) && $this->validate_license( $addon_id, $license_key );
		}

		return true;
	}

	/**
	 * Load registered addons
	 *
	 * @since 1.0.0
	 */
	public function load_addons() {
		$addon_dir = WPMATCH_PATH . 'addons/';
		
		if ( ! is_dir( $addon_dir ) ) {
			return;
		}

		$addons = glob( $addon_dir . '*/*.php' );
		
		foreach ( $addons as $addon_file ) {
			if ( is_readable( $addon_file ) ) {
				include_once $addon_file;
			}
		}

		/**
		 * Hook after addons are loaded
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpmatch_addons_loaded' );
	}

	/**
	 * Check for addon updates
	 *
	 * @since 1.0.0
	 */
	public function check_addon_updates() {
		// Implementation for checking addon updates.
		// This would typically connect to a licensing server.
	}

	/**
	 * Filter demo user limit based on active addons
	 *
	 * @since 1.0.0
	 * @param int $limit Current limit.
	 * @return int Modified limit.
	 */
	public function filter_demo_user_limit( $limit ) {
		$max_addon_limit = 0;
		
		foreach ( self::$addons as $addon_id => $addon_data ) {
			if ( self::is_addon_active( $addon_id ) && $addon_data['demo_users_limit'] > $max_addon_limit ) {
				$max_addon_limit = $addon_data['demo_users_limit'];
			}
		}

		// Return unlimited if any addon provides unlimited.
		if ( $max_addon_limit === -1 ) {
			return -1;
		}

		return max( $limit, $max_addon_limit );
	}

	/**
	 * Get available demo content packs
	 *
	 * @since 1.0.0
	 * @param array $packs Existing packs.
	 * @return array Demo content packs.
	 */
	public function get_available_demo_packs( $packs = array() ) {
		// Define available premium demo packs.
		$premium_packs = array(
			'extended' => array(
				'id'          => 'extended',
				'name'        => __( 'Extended Demo Pack', 'wpmatch-free' ),
				'description' => __( '100 additional diverse demo users with enhanced profiles and photos', 'wpmatch-free' ),
				'price'       => '$19.99',
				'user_count'  => 100,
				'features'    => array(
					__( '100 realistic demo profiles', 'wpmatch-free' ),
					__( 'Enhanced profile photos', 'wpmatch-free' ),
					__( 'Diverse demographics', 'wpmatch-free' ),
					__( 'Custom interests and hobbies', 'wpmatch-free' ),
				),
				'available'   => false, // Set to true when addon is developed.
			),
			'professional' => array(
				'id'          => 'professional',
				'name'        => __( 'Professional Demo Pack', 'wpmatch-free' ),
				'description' => __( '500 demo users plus demo messages and interactions', 'wpmatch-free' ),
				'price'       => '$49.99',
				'user_count'  => 500,
				'features'    => array(
					__( '500 professional demo profiles', 'wpmatch-free' ),
					__( 'Demo messages and conversations', 'wpmatch-free' ),
					__( 'Simulated user interactions', 'wpmatch-free' ),
					__( 'Advanced demographics', 'wpmatch-free' ),
					__( 'Industry-specific profiles', 'wpmatch-free' ),
				),
				'available'   => false,
			),
			'enterprise' => array(
				'id'          => 'enterprise',
				'name'        => __( 'Enterprise Demo Pack', 'wpmatch-free' ),
				'description' => __( 'Unlimited demo users with custom profile generation', 'wpmatch-free' ),
				'price'       => '$99.99',
				'user_count'  => -1, // Unlimited.
				'features'    => array(
					__( 'Unlimited demo user generation', 'wpmatch-free' ),
					__( 'Custom profile templates', 'wpmatch-free' ),
					__( 'Bulk import/export tools', 'wpmatch-free' ),
					__( 'Advanced customization options', 'wpmatch-free' ),
					__( 'Priority support', 'wpmatch-free' ),
				),
				'available'   => false,
			),
		);

		return array_merge( $packs, $premium_packs );
	}

	/**
	 * Validate addon license
	 *
	 * @since 1.0.0
	 * @param string $addon_id   Addon identifier.
	 * @param string $license_key License key.
	 * @return bool License validity.
	 */
	private function validate_license( $addon_id, $license_key ) {
		// This would typically connect to a licensing server.
		// For demonstration, return false (unlicensed).
		return false;
	}

	/**
	 * Get addon licensing information
	 *
	 * @since 1.0.0
	 * @param string $addon_id Addon identifier.
	 * @return array License information.
	 */
	public function get_license_info( $addon_id ) {
		if ( ! isset( self::$addons[ $addon_id ] ) ) {
			return array();
		}

		$license_key = get_option( "wpmatch_license_{$addon_id}" );
		$license_status = $this->validate_license( $addon_id, $license_key );

		return array(
			'license_key'    => $license_key,
			'license_status' => $license_status,
			'expires'        => get_option( "wpmatch_license_expires_{$addon_id}" ),
			'last_checked'   => get_option( "wpmatch_license_checked_{$addon_id}" ),
		);
	}

	/**
	 * Activate addon license
	 *
	 * @since 1.0.0
	 * @param string $addon_id   Addon identifier.
	 * @param string $license_key License key.
	 * @return array Activation result.
	 */
	public function activate_license( $addon_id, $license_key ) {
		// This would typically connect to a licensing server.
		// For demonstration purposes, we'll simulate the response.
		
		if ( empty( $license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter a valid license key.', 'wpmatch-free' ),
			);
		}

		// Simulate license validation.
		// In a real implementation, this would make an API call.
		return array(
			'success' => false,
			'message' => __( 'License validation server not configured. Contact support.', 'wpmatch-free' ),
		);
	}
}

// Initialize addon framework.
add_action('plugins_loaded', function() { new WPMatch_Addon_Framework(); });