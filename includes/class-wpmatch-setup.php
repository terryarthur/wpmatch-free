<?php
/**
 * Setup and Installation Helper for WP Match Free
 *
 * Handles initial setup, default field creation, and plugin configuration
 * to ensure the plugin works out of the box.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup and installation class
 *
 * Provides functionality to set up default profile fields,
 * configure initial plugin settings, and ensure proper operation.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */
class WPMatch_Setup {

	/**
	 * Initialize setup functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_create_default_fields' ) );
	}

	/**
	 * Create default profile fields if none exist
	 *
	 * @since 1.0.0
	 */
	public function maybe_create_default_fields() {
		global $wpdb;

		// Check if we already have profile fields.
		$existing_fields = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wpmatch_profile_fields"
		);

		if ( $existing_fields > 0 ) {
			return; // Fields already exist.
		}

		// Create default profile fields.
		$this->create_default_fields();
	}

	/**
	 * Create comprehensive default profile fields
	 *
	 * @since 1.0.0
	 */
	private function create_default_fields() {
		global $wpdb;

		$default_fields = array(
			// Basic Identity fields.
			array(
				'field_key'      => 'age',
				'field_type'     => 'text',
				'field_label'    => 'Age',
				'field_group'    => 'basic',
				'is_required'    => 1,
				'searchable'     => 1,
				'display_order'  => 1,
			),
			array(
				'field_key'      => 'gender',
				'field_type'     => 'select',
				'field_label'    => 'Gender',
				'field_group'    => 'basic',
				'is_required'    => 1,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Male', 'Female', 'Non-binary', 'Other' ) ),
				'display_order'  => 2,
			),
			array(
				'field_key'      => 'looking_for',
				'field_type'     => 'select',
				'field_label'    => 'Looking For',
				'field_group'    => 'basic',
				'is_required'    => 1,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Long-term relationship', 'Dating and see where it goes', 'Friendship', 'Casual dating', 'Marriage' ) ),
				'display_order'  => 3,
			),
			array(
				'field_key'      => 'about_me',
				'field_type'     => 'textarea',
				'field_label'    => 'About Me',
				'field_group'    => 'basic',
				'is_required'    => 1,
				'searchable'     => 0,
				'display_order'  => 4,
			),

			// Location & Lifestyle fields.
			array(
				'field_key'      => 'location',
				'field_type'     => 'location',
				'field_label'    => 'Location',
				'field_group'    => 'location',
				'is_required'    => 1,
				'searchable'     => 1,
				'display_order'  => 10,
			),
			array(
				'field_key'      => 'occupation',
				'field_type'     => 'text',
				'field_label'    => 'Occupation',
				'field_group'    => 'location',
				'is_required'    => 0,
				'searchable'     => 1,
				'display_order'  => 11,
			),
			array(
				'field_key'      => 'education',
				'field_type'     => 'select',
				'field_label'    => 'Education',
				'field_group'    => 'location',
				'is_required'    => 0,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'High School', 'Some College', 'Bachelor\'s Degree', 'Master\'s Degree', 'Doctorate', 'Trade School' ) ),
				'display_order'  => 12,
			),

			// Appearance fields.
			array(
				'field_key'      => 'height',
				'field_type'     => 'text',
				'field_label'    => 'Height',
				'field_group'    => 'appearance',
				'is_required'    => 0,
				'searchable'     => 1,
				'display_order'  => 20,
			),
			array(
				'field_key'      => 'body_type',
				'field_type'     => 'select',
				'field_label'    => 'Body Type',
				'field_group'    => 'appearance',
				'is_required'    => 0,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Athletic', 'Average', 'Slim', 'Curvy', 'Plus Size', 'Muscular' ) ),
				'display_order'  => 21,
			),
			array(
				'field_key'      => 'eye_color',
				'field_type'     => 'select',
				'field_label'    => 'Eye Color',
				'field_group'    => 'appearance',
				'is_required'    => 0,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Brown', 'Blue', 'Green', 'Hazel', 'Gray', 'Amber' ) ),
				'display_order'  => 22,
			),

			// Lifestyle fields.
			array(
				'field_key'      => 'relationship_status',
				'field_type'     => 'select',
				'field_label'    => 'Relationship Status',
				'field_group'    => 'lifestyle',
				'is_required'    => 1,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Single', 'Divorced', 'Widowed', 'Separated' ) ),
				'display_order'  => 30,
			),
			array(
				'field_key'      => 'smoking',
				'field_type'     => 'select',
				'field_label'    => 'Smoking',
				'field_group'    => 'lifestyle',
				'is_required'    => 0,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Never', 'Socially', 'Regularly', 'Trying to Quit' ) ),
				'display_order'  => 31,
			),
			array(
				'field_key'      => 'drinking',
				'field_type'     => 'select',
				'field_label'    => 'Drinking',
				'field_group'    => 'lifestyle',
				'is_required'    => 0,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 'Never', 'Socially', 'Regularly', 'Occasionally' ) ),
				'display_order'  => 32,
			),

			// Interests fields.
			array(
				'field_key'      => 'interests',
				'field_type'     => 'multiselect',
				'field_label'    => 'Interests',
				'field_group'    => 'interests',
				'is_required'    => 0,
				'searchable'     => 1,
				'options'        => wp_json_encode( array( 
					'Travel', 'Photography', 'Music', 'Movies', 'Reading', 'Cooking', 
					'Fitness', 'Hiking', 'Art', 'Dancing', 'Sports', 'Gaming',
					'Technology', 'Fashion', 'Food', 'Wine', 'Coffee', 'Dogs', 
					'Cats', 'Outdoors', 'Beach', 'Mountains', 'Yoga', 'Meditation'
				) ),
				'display_order'  => 40,
			),
		);

		foreach ( $default_fields as $field_data ) {
			$wpdb->insert(
				$wpdb->prefix . 'wpmatch_profile_fields',
				$field_data,
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d' )
			);
		}
	}

	/**
	 * Get setup completion status
	 *
	 * @since 1.0.0
	 * @return array Setup status information.
	 */
	public function get_setup_status() {
		global $wpdb;

		$profile_fields_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wpmatch_profile_fields"
		);

		$demo_users_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login LIKE %s",
				'wpmatch_demo_%'
			)
		);

		return array(
			'has_profile_fields' => $profile_fields_count > 0,
			'profile_fields_count' => intval( $profile_fields_count ),
			'has_demo_users' => $demo_users_count > 0,
			'demo_users_count' => intval( $demo_users_count ),
			'setup_complete' => $profile_fields_count > 0,
		);
	}
}

// Initialize setup functionality.
add_action('plugins_loaded', function() { new WPMatch_Setup(); });