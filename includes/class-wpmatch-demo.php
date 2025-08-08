<?php
/**
 * Demo Users and Content Management for WP Match Free
 *
 * Handles creation, management, and cleanup of demo users and content
 * for testing and demonstration purposes.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Demo content management class
 *
 * Provides functionality to create realistic demo users with profiles,
 * manage demo content, and clean up when needed. Includes support for
 * addon packs with additional demo content.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */
class WPMatch_Demo {

	/**
	 * Maximum free demo users
	 *
	 * @var int
	 */
	const FREE_DEMO_LIMIT = 20;

	/**
	 * Demo user prefix for identification
	 *
	 * @var string
	 */
	const DEMO_USER_PREFIX = 'wpmatch_demo_';

	/**
	 * Initialize demo functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpmatch_create_demo_users', array( $this, 'ajax_create_demo_users' ) );
		add_action( 'wp_ajax_wpmatch_cleanup_demo_users', array( $this, 'ajax_cleanup_demo_users' ) );
		add_action( 'wp_ajax_wpmatch_get_demo_stats', array( $this, 'ajax_get_demo_stats' ) );
	}

	/**
	 * Get demo user statistics
	 *
	 * @since 1.0.0
	 * @return array Demo statistics.
	 */
	public function get_demo_stats() {
		global $wpdb;

		$demo_users = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->users} WHERE user_login LIKE %s",
				self::DEMO_USER_PREFIX . '%'
			)
		);

		$demo_profiles = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT pv.user_id) 
				 FROM {$wpdb->prefix}wpmatch_profile_values pv 
				 INNER JOIN {$wpdb->users} u ON pv.user_id = u.ID 
				 WHERE u.user_login LIKE %s",
				self::DEMO_USER_PREFIX . '%'
			)
		);

		return array(
			'total_demo_users'    => intval( $demo_users ),
			'users_with_profiles' => intval( $demo_profiles ),
			'free_limit'          => self::FREE_DEMO_LIMIT,
			'can_create_more'     => intval( $demo_users ) < self::FREE_DEMO_LIMIT,
		);
	}

	/**
	 * Create demo users with realistic profiles
	 *
	 * @since 1.0.0
	 * @param int $count Number of users to create.
	 * @return array|WP_Error Results or error.
	 */
	public function create_demo_users( $count = 10 ) {
		$current_stats = $this->get_demo_stats();

		if ( $current_stats['total_demo_users'] + $count > self::FREE_DEMO_LIMIT ) {
			return new WP_Error(
				'demo_limit_exceeded',
				sprintf(
					/* translators: %d: Demo user limit */
					__( 'Cannot create demo users. Free limit is %d users.', 'wpmatch-free' ),
					self::FREE_DEMO_LIMIT
				)
			);
		}

		$created_users = array();
		$demo_profiles = $this->get_demo_profile_data();

		for ( $i = 0; $i < $count; $i++ ) {
			$profile_data = $demo_profiles[ array_rand( $demo_profiles ) ];
			$user_id      = $this->create_single_demo_user( $profile_data );

			if ( ! is_wp_error( $user_id ) ) {
				$created_users[] = $user_id;
				$this->populate_user_profile( $user_id, $profile_data );
			}
		}

		return $created_users;
	}

	/**
	 * Create a single demo user
	 *
	 * @since 1.0.0
	 * @param array $profile_data Profile information.
	 * @return int|WP_Error User ID or error.
	 */
	private function create_single_demo_user( $profile_data ) {
		$username = self::DEMO_USER_PREFIX . sanitize_user( $profile_data['first_name'] ) . '_' . wp_rand( 1000, 9999 );
		$email    = sanitize_email( strtolower( $profile_data['first_name'] . '.' . $profile_data['last_name'] . wp_rand( 100, 999 ) . '@example.com' ) );

		$user_data = array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password(),
			'first_name'   => $profile_data['first_name'],
			'last_name'    => $profile_data['last_name'],
			'display_name' => $profile_data['first_name'] . ' ' . $profile_data['last_name'],
			'description'  => $profile_data['bio'],
			'role'         => 'subscriber',
		);

		$user_id = wp_insert_user( $user_data );

		if ( ! is_wp_error( $user_id ) ) {
			// Mark as demo user.
			update_user_meta( $user_id, 'wpmatch_is_demo_user', true );
			update_user_meta( $user_id, 'wpmatch_demo_created', current_time( 'mysql' ) );
		}

		return $user_id;
	}

	/**
	 * Populate user profile with field data
	 *
	 * @since 1.0.0
	 * @param int   $user_id      User ID.
	 * @param array $profile_data Profile information.
	 */
	private function populate_user_profile( $user_id, $profile_data ) {
		global $wpdb;

		// Get available profile fields.
		$fields = $wpdb->get_results(
			"SELECT field_id, field_key, field_type FROM {$wpdb->prefix}wpmatch_profile_fields"
		);

		foreach ( $fields as $field ) {
			$value = $this->get_demo_field_value( $field->field_key, $field->field_type, $profile_data );

			if ( ! empty( $value ) ) {
				$wpdb->replace(
					$wpdb->prefix . 'wpmatch_profile_values',
					array(
						'user_id'     => $user_id,
						'field_id'    => $field->field_id,
						'field_value' => $value,
					),
					array( '%d', '%d', '%s' )
				);
			}
		}
	}

	/**
	 * Get demo field value based on field key and type
	 *
	 * @since 1.0.0
	 * @param string $field_key   Field identifier.
	 * @param string $field_type  Field type.
	 * @param array  $profile_data Profile information.
	 * @return string Field value.
	 */
	private function get_demo_field_value( $field_key, $field_type, $profile_data ) {
		// Map common field keys to profile data.
		$field_mapping = array(
			'age'          => $profile_data['age'],
			'gender'       => $profile_data['gender'],
			'location'     => $profile_data['location'],
			'city'         => $profile_data['city'],
			'occupation'   => $profile_data['occupation'],
			'education'    => $profile_data['education'],
			'height'       => $profile_data['height'],
			'relationship' => $profile_data['relationship_status'],
			'interests'    => implode( ',', $profile_data['interests'] ),
			'about_me'     => $profile_data['bio'],
			'looking_for'  => $profile_data['looking_for'],
		);

		if ( isset( $field_mapping[ $field_key ] ) ) {
			return $field_mapping[ $field_key ];
		}

		// Generate field-type appropriate demo data.
		switch ( $field_type ) {
			case 'select':
				return $this->get_random_select_value( $field_key );
			case 'date':
				return $this->get_random_date( $field_key );
			case 'text':
				return $this->get_random_text_value( $field_key );
			default:
				return '';
		}
	}

	/**
	 * Get random select field value
	 *
	 * @since 1.0.0
	 * @param string $field_key Field identifier.
	 * @return string Random value.
	 */
	private function get_random_select_value( $field_key ) {
		$select_values = array(
			'body_type'  => array( 'Athletic', 'Average', 'Slim', 'Curvy', 'Plus Size' ),
			'eye_color'  => array( 'Brown', 'Blue', 'Green', 'Hazel', 'Gray' ),
			'hair_color' => array( 'Black', 'Brown', 'Blonde', 'Red', 'Gray' ),
			'ethnicity'  => array( 'Caucasian', 'Hispanic', 'African American', 'Asian', 'Mixed' ),
			'religion'   => array( 'Christian', 'Jewish', 'Muslim', 'Buddhist', 'Agnostic', 'Atheist' ),
			'smoking'    => array( 'Never', 'Socially', 'Regularly', 'Trying to Quit' ),
			'drinking'   => array( 'Never', 'Socially', 'Regularly', 'Occasionally' ),
		);

		if ( isset( $select_values[ $field_key ] ) ) {
			return $select_values[ $field_key ][ array_rand( $select_values[ $field_key ] ) ];
		}

		return '';
	}

	/**
	 * Get random date value
	 *
	 * @since 1.0.0
	 * @param string $field_key Field identifier.
	 * @return string Date value.
	 */
	private function get_random_date( $field_key ) {
		if ( 'birthday' === $field_key || 'birth_date' === $field_key ) {
			$min_age     = 18;
			$max_age     = 65;
			$birth_year  = gmdate( 'Y' ) - wp_rand( $min_age, $max_age );
			$birth_month = wp_rand( 1, 12 );
			$birth_day   = wp_rand( 1, 28 );

			return sprintf( '%04d-%02d-%02d', $birth_year, $birth_month, $birth_day );
		}

		return '';
	}

	/**
	 * Get random text value
	 *
	 * @since 1.0.0
	 * @param string $field_key Field identifier.
	 * @return string Text value.
	 */
	private function get_random_text_value( $field_key ) {
		$text_values = array(
			'favorite_quote' => array(
				'Life is what happens to you while you\'re busy making other plans.',
				'The only way to do great work is to love what you do.',
				'Innovation distinguishes between a leader and a follower.',
				'Stay hungry, stay foolish.',
			),
			'perfect_date'   => array(
				'A cozy dinner at home with good wine and conversation',
				'Adventure hiking followed by a picnic with a view',
				'Museum hopping and coffee at a local café',
				'Beach sunset with takeout and stargazing',
			),
		);

		if ( isset( $text_values[ $field_key ] ) ) {
			return $text_values[ $field_key ][ array_rand( $text_values[ $field_key ] ) ];
		}

		return '';
	}

	/**
	 * Clean up all demo users and their data
	 *
	 * @since 1.0.0
	 * @return array Cleanup results.
	 */
	public function cleanup_demo_users() {
		global $wpdb;

		// Get all demo users.
		$demo_user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s",
				self::DEMO_USER_PREFIX . '%'
			)
		);

		$deleted_count = 0;
		$errors        = array();

		foreach ( $demo_user_ids as $user_id ) {
			// Delete profile values.
			$wpdb->delete(
				$wpdb->prefix . 'wpmatch_profile_values',
				array( 'user_id' => $user_id ),
				array( '%d' )
			);

			// Delete user.
			if ( wp_delete_user( $user_id ) ) {
				++$deleted_count;
			} else {
				$errors[] = sprintf(
					/* translators: %d: User ID */
					__( 'Failed to delete user ID: %d', 'wpmatch-free' ),
					$user_id
				);
			}
		}

		return array(
			'deleted_count' => $deleted_count,
			'errors'        => $errors,
		);
	}

	/**
	 * AJAX handler for creating demo users
	 *
	 * @since 1.0.0
	 */
	public function ajax_create_demo_users() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'wpmatch-free' ) );
		}

		$count = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 10;
		$count = min( $count, 20 ); // Limit to prevent timeouts.

		$result = $this->create_demo_users( $count );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success(
			array(
				'message'     => sprintf(
					/* translators: %d: Number of users created */
					__( 'Successfully created %d demo users!', 'wpmatch-free' ),
					count( $result )
				),
				'created_ids' => $result,
				'stats'       => $this->get_demo_stats(),
			)
		);
	}

	/**
	 * AJAX handler for cleaning up demo users
	 *
	 * @since 1.0.0
	 */
	public function ajax_cleanup_demo_users() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'wpmatch-free' ) );
		}

		$result = $this->cleanup_demo_users();

		wp_send_json_success(
			array(
				'message'       => sprintf(
					/* translators: %d: Number of users deleted */
					__( 'Successfully deleted %d demo users!', 'wpmatch-free' ),
					$result['deleted_count']
				),
				'deleted_count' => $result['deleted_count'],
				'errors'        => $result['errors'],
				'stats'         => $this->get_demo_stats(),
			)
		);
	}

	/**
	 * AJAX handler for getting demo statistics
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_demo_stats() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'wpmatch-free' ) );
		}

		wp_send_json_success( $this->get_demo_stats() );
	}

	/**
	 * Get realistic demo profile data
	 *
	 * @since 1.0.0
	 * @return array Array of demo profiles.
	 */
	private function get_demo_profile_data() {
		return array(
			array(
				'first_name'          => 'Emma',
				'last_name'           => 'Johnson',
				'age'                 => '28',
				'gender'              => 'Female',
				'location'            => 'San Francisco, CA',
				'city'                => 'San Francisco',
				'occupation'          => 'Software Engineer',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'6"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Long-term relationship',
				'interests'           => array( 'Photography', 'Hiking', 'Coffee', 'Travel' ),
				'bio'                 => 'Tech enthusiast who loves exploring new places and capturing moments. Looking for someone to share adventures with.',
			),
			array(
				'first_name'          => 'Michael',
				'last_name'           => 'Chen',
				'age'                 => '32',
				'gender'              => 'Male',
				'location'            => 'New York, NY',
				'city'                => 'New York',
				'occupation'          => 'Marketing Manager',
				'education'           => 'Master\'s Degree',
				'height'              => '5\'11"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Serious relationship',
				'interests'           => array( 'Fitness', 'Cooking', 'Music', 'Reading' ),
				'bio'                 => 'Passionate about fitness and good food. Love trying new restaurants and staying active. Seeking genuine connection.',
			),
			array(
				'first_name'          => 'Sarah',
				'last_name'           => 'Williams',
				'age'                 => '26',
				'gender'              => 'Female',
				'location'            => 'Austin, TX',
				'city'                => 'Austin',
				'occupation'          => 'Graphic Designer',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'4"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Dating and see where it goes',
				'interests'           => array( 'Art', 'Music', 'Yoga', 'Dogs' ),
				'bio'                 => 'Creative soul with a love for art and design. Dog mom to a golden retriever. Always up for live music and new experiences.',
			),
			array(
				'first_name'          => 'David',
				'last_name'           => 'Rodriguez',
				'age'                 => '35',
				'gender'              => 'Male',
				'location'            => 'Los Angeles, CA',
				'city'                => 'Los Angeles',
				'occupation'          => 'Film Producer',
				'education'           => 'Master\'s Degree',
				'height'              => '6\'0"',
				'relationship_status' => 'Divorced',
				'looking_for'         => 'Companionship',
				'interests'           => array( 'Movies', 'Travel', 'Wine', 'Golf' ),
				'bio'                 => 'Film industry professional with a passion for storytelling. Love exploring new cultures and fine dining.',
			),
			array(
				'first_name'          => 'Jessica',
				'last_name'           => 'Davis',
				'age'                 => '29',
				'gender'              => 'Female',
				'location'            => 'Chicago, IL',
				'city'                => 'Chicago',
				'occupation'          => 'Doctor',
				'education'           => 'Doctorate',
				'height'              => '5\'7"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Long-term relationship',
				'interests'           => array( 'Medicine', 'Running', 'Books', 'Volunteering' ),
				'bio'                 => 'Dedicated physician who believes in work-life balance. Marathon runner and bookworm seeking intellectual connection.',
			),
			array(
				'first_name'          => 'Ryan',
				'last_name'           => 'Thompson',
				'age'                 => '31',
				'gender'              => 'Male',
				'location'            => 'Seattle, WA',
				'city'                => 'Seattle',
				'occupation'          => 'Teacher',
				'education'           => 'Master\'s Degree',
				'height'              => '5\'10"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Meaningful relationship',
				'interests'           => array( 'Education', 'Outdoors', 'Gaming', 'Coffee' ),
				'bio'                 => 'High school teacher passionate about education and the outdoors. Weekend hiker and coffee connoisseur.',
			),
			array(
				'first_name'          => 'Ashley',
				'last_name'           => 'Brown',
				'age'                 => '27',
				'gender'              => 'Female',
				'location'            => 'Miami, FL',
				'city'                => 'Miami',
				'occupation'          => 'Nurse',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'5"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Someone special',
				'interests'           => array( 'Healthcare', 'Beach', 'Dancing', 'Family' ),
				'bio'                 => 'Caring nurse who loves helping others. Beach lover and salsa dancer looking for someone with a kind heart.',
			),
			array(
				'first_name'          => 'James',
				'last_name'           => 'Wilson',
				'age'                 => '33',
				'gender'              => 'Male',
				'location'            => 'Denver, CO',
				'city'                => 'Denver',
				'occupation'          => 'Engineer',
				'education'           => 'Master\'s Degree',
				'height'              => '6\'1"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Life partner',
				'interests'           => array( 'Skiing', 'Technology', 'Craft Beer', 'Mountains' ),
				'bio'                 => 'Mountain enthusiast and tech lover. Spend winters on the slopes and summers hiking. Looking for adventure partner.',
			),
			array(
				'first_name'          => 'Megan',
				'last_name'           => 'Martinez',
				'age'                 => '25',
				'gender'              => 'Female',
				'location'            => 'Portland, OR',
				'city'                => 'Portland',
				'occupation'          => 'Writer',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'3"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Creative connection',
				'interests'           => array( 'Writing', 'Coffee', 'Books', 'Indie Films' ),
				'bio'                 => 'Freelance writer with a love for storytelling. Coffee shop regular and indie film enthusiast seeking creative spark.',
			),
			array(
				'first_name'          => 'Kevin',
				'last_name'           => 'Lee',
				'age'                 => '30',
				'gender'              => 'Male',
				'location'            => 'Boston, MA',
				'city'                => 'Boston',
				'occupation'          => 'Lawyer',
				'education'           => 'Law Degree',
				'height'              => '5\'9"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Serious commitment',
				'interests'           => array( 'Law', 'History', 'Sports', 'Politics' ),
				'bio'                 => 'Corporate lawyer with a passion for justice and history. Sports fan who enjoys intellectual debates.',
			),
			// Add 10 more profiles to reach 20 total.
			array(
				'first_name'          => 'Amanda',
				'last_name'           => 'Garcia',
				'age'                 => '24',
				'gender'              => 'Female',
				'location'            => 'Nashville, TN',
				'city'                => 'Nashville',
				'occupation'          => 'Musician',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'8"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Musical soulmate',
				'interests'           => array( 'Music', 'Songwriting', 'Guitar', 'Live Shows' ),
				'bio'                 => 'Singer-songwriter living the Nashville dream. Love live music and late-night songwriting sessions.',
			),
			array(
				'first_name'          => 'Christopher',
				'last_name'           => 'Anderson',
				'age'                 => '36',
				'gender'              => 'Male',
				'location'            => 'Phoenix, AZ',
				'city'                => 'Phoenix',
				'occupation'          => 'Real Estate Agent',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '6\'2"',
				'relationship_status' => 'Divorced',
				'looking_for'         => 'New beginning',
				'interests'           => array( 'Real Estate', 'Golf', 'Travel', 'Fitness' ),
				'bio'                 => 'Successful real estate professional ready for a new chapter. Love golf, travel, and staying fit.',
			),
			array(
				'first_name'          => 'Nicole',
				'last_name'           => 'Taylor',
				'age'                 => '28',
				'gender'              => 'Female',
				'location'            => 'San Diego, CA',
				'city'                => 'San Diego',
				'occupation'          => 'Veterinarian',
				'education'           => 'Doctorate',
				'height'              => '5\'6"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Animal lover',
				'interests'           => array( 'Animals', 'Surfing', 'Hiking', 'Conservation' ),
				'bio'                 => 'Veterinarian with a passion for animal welfare. Surfer and hiker who loves the outdoors and conservation.',
			),
			array(
				'first_name'          => 'Brandon',
				'last_name'           => 'Moore',
				'age'                 => '29',
				'gender'              => 'Male',
				'location'            => 'Atlanta, GA',
				'city'                => 'Atlanta',
				'occupation'          => 'Chef',
				'education'           => 'Culinary School',
				'height'              => '5\'11"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Foodie partner',
				'interests'           => array( 'Cooking', 'Food', 'Travel', 'Wine' ),
				'bio'                 => 'Professional chef who loves creating culinary experiences. Always exploring new cuisines and flavors.',
			),
			array(
				'first_name'          => 'Rachel',
				'last_name'           => 'Jackson',
				'age'                 => '26',
				'gender'              => 'Female',
				'location'            => 'Minneapolis, MN',
				'city'                => 'Minneapolis',
				'occupation'          => 'Social Worker',
				'education'           => 'Master\'s Degree',
				'height'              => '5\'4"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Kind hearted person',
				'interests'           => array( 'Social Work', 'Yoga', 'Meditation', 'Community' ),
				'bio'                 => 'Social worker dedicated to helping others. Practice yoga and meditation, seeking someone with a compassionate heart.',
			),
			array(
				'first_name'          => 'Tyler',
				'last_name'           => 'White',
				'age'                 => '34',
				'gender'              => 'Male',
				'location'            => 'Las Vegas, NV',
				'city'                => 'Las Vegas',
				'occupation'          => 'Business Owner',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '6\'0"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Business partner in life',
				'interests'           => array( 'Business', 'Entrepreneurship', 'Networking', 'Success' ),
				'bio'                 => 'Entrepreneur who built a successful business from scratch. Looking for an ambitious partner to share success with.',
			),
			array(
				'first_name'          => 'Stephanie',
				'last_name'           => 'Harris',
				'age'                 => '27',
				'gender'              => 'Female',
				'location'            => 'Philadelphia, PA',
				'city'                => 'Philadelphia',
				'occupation'          => 'Psychologist',
				'education'           => 'Doctorate',
				'height'              => '5\'5"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Emotional connection',
				'interests'           => array( 'Psychology', 'Mental Health', 'Reading', 'Therapy' ),
				'bio'                 => 'Clinical psychologist passionate about mental health. Enjoy deep conversations and helping others grow.',
			),
			array(
				'first_name'          => 'Andrew',
				'last_name'           => 'Clark',
				'age'                 => '31',
				'gender'              => 'Male',
				'location'            => 'Salt Lake City, UT',
				'city'                => 'Salt Lake City',
				'occupation'          => 'Park Ranger',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'10"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Nature lover',
				'interests'           => array( 'Nature', 'Camping', 'Wildlife', 'Conservation' ),
				'bio'                 => 'Park ranger who lives for the outdoors. Love camping, wildlife photography, and protecting our natural spaces.',
			),
			array(
				'first_name'          => 'Lauren',
				'last_name'           => 'Lewis',
				'age'                 => '25',
				'gender'              => 'Female',
				'location'            => 'Charlotte, NC',
				'city'                => 'Charlotte',
				'occupation'          => 'Interior Designer',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'7"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Creative partnership',
				'interests'           => array( 'Design', 'Art', 'Home Decor', 'DIY' ),
				'bio'                 => 'Interior designer with an eye for beautiful spaces. Love DIY projects and creating homes that tell stories.',
			),
			array(
				'first_name'          => 'Jonathan',
				'last_name'           => 'Young',
				'age'                 => '33',
				'gender'              => 'Male',
				'location'            => 'Richmond, VA',
				'city'                => 'Richmond',
				'occupation'          => 'Photographer',
				'education'           => 'Bachelor\'s Degree',
				'height'              => '5\'11"',
				'relationship_status' => 'Single',
				'looking_for'         => 'Muse and partner',
				'interests'           => array( 'Photography', 'Art', 'Travel', 'Stories' ),
				'bio'                 => 'Professional photographer who captures life\'s beautiful moments. Always looking for the next great story to tell.',
			),
		);
	}
}

// Initialize demo functionality.
add_action(
	'plugins_loaded',
	function () {
		new WPMatch_Demo();
	}
);
