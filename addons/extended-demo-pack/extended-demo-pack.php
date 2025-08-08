<?php
/**
 * Extended Demo Pack Addon for WP Match Free
 *
 * Adds 100 additional demo users with enhanced profiles and features.
 * This is a sample implementation showing how addons integrate with the core plugin.
 *
 * @package WPMatchFree
 * @subpackage ExtendedDemoPack
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Addon registration
add_action( 'wpmatch_addons_loaded', 'wpmatch_register_extended_demo_pack' );

/**
 * Register the Extended Demo Pack addon
 *
 * @since 1.0.0
 */
function wpmatch_register_extended_demo_pack() {
	WPMatch_Addon_Framework::register_addon(
		array(
			'id'               => 'extended_demo_pack',
			'name'             => __( 'Extended Demo Pack', 'wpmatch-free' ),
			'version'          => '1.0.0',
			'description'      => __( 'Adds 100 additional diverse demo users with enhanced profiles', 'wpmatch-free' ),
			'author'           => 'WP Match Team',
			'file'             => __FILE__,
			'license_required' => true,
			'min_core_version' => '1.0.0',
			'demo_users_limit' => 120, // 20 free + 100 from this addon
			'features'         => array(
				'enhanced_profiles',
				'diverse_demographics',
				'custom_interests',
				'professional_photos',
			),
		)
	);
}

/**
 * Extended Demo Pack class
 *
 * Provides additional demo users and enhanced functionality
 * when the addon is properly licensed and activated.
 */
class WPMatch_Extended_Demo_Pack {

	/**
	 * Initialize the addon
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Only initialize if addon is active and licensed
		if ( WPMatch_Addon_Framework::is_addon_active( 'extended_demo_pack' ) ) {
			add_filter( 'wpmatch_demo_profiles', array( $this, 'add_extended_profiles' ) );
			add_filter( 'wpmatch_demo_user_limit', array( $this, 'increase_user_limit' ) );
		}
	}

	/**
	 * Add extended demo profiles
	 *
	 * @since 1.0.0
	 * @param array $profiles Existing profiles.
	 * @return array Extended profiles.
	 */
	public function add_extended_profiles( $profiles ) {
		// This would contain 100+ additional realistic profiles
		// For demonstration, we'll add a few sample ones
		$extended_profiles = array(
			array(
				'first_name'        => 'Olivia',
				'last_name'         => 'Mitchell',
				'age'               => '29',
				'gender'            => 'Female',
				'location'          => 'Toronto, ON',
				'city'              => 'Toronto',
				'occupation'        => 'Data Scientist',
				'education'         => 'Master\'s Degree',
				'height'            => '5\'7"',
				'relationship_status' => 'Single',
				'looking_for'       => 'Long-term relationship',
				'interests'         => array( 'Machine Learning', 'Rock Climbing', 'Wine Tasting', 'Travel' ),
				'bio'               => 'Data scientist passionate about AI and outdoor adventures. Love combining analytical thinking with creative pursuits.',
			),
			// Add more profiles here...
		);

		return array_merge( $profiles, $extended_profiles );
	}

	/**
	 * Increase demo user limit
	 *
	 * @since 1.0.0
	 * @param int $limit Current limit.
	 * @return int New limit.
	 */
	public function increase_user_limit( $limit ) {
		return 120; // 20 free + 100 from this addon
	}
}

// Initialize the addon if the framework is available
if ( class_exists( 'WPMatch_Addon_Framework' ) ) {
	add_action('plugins_loaded', function() { new WPMatch_Extended_Demo_Pack(); });
}