<?php
/**
 * Test REST API endpoints.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

class RestApiTest extends WP_UnitTestCase {

	protected static $user_id;
	protected static $user2_id;
	protected static $profile_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_id = $factory->user->create();
		self::$user2_id = $factory->user->create();
		
		self::$profile_id = wpmf_profile_create(
			array(
				'user_id'     => self::$user_id,
				'gender'      => 'male',
				'region'      => 'europe',
				'bio'         => 'Test bio',
				'age'         => 25,
				'headline'    => 'Test headline',
			)
		);

		// Create second profile
		wpmf_profile_create(
			array(
				'user_id'  => self::$user2_id,
				'gender'   => 'female',
				'region'   => 'europe',
				'age'      => 23,
				'headline' => 'Second profile',
			)
		);
	}

	public function test_profiles_endpoint_returns_data() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles' );
		$response = wpmf_rest_profiles( $request );
		
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertGreaterThan( 0, count( $data ) );
	}

	public function test_profiles_endpoint_filters_by_region() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles' );
		$request->set_param( 'region', 'europe' );
		$response = wpmf_rest_profiles( $request );
		
		$data = $response->get_data();
		foreach ( $data as $profile ) {
			$this->assertEquals( 'europe', $profile['region'] );
		}
	}

	public function test_profiles_endpoint_filters_by_age() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles' );
		$request->set_param( 'age_min', 24 );
		$request->set_param( 'age_max', 26 );
		$response = wpmf_rest_profiles( $request );
		
		$data = $response->get_data();
		foreach ( $data as $profile ) {
			$this->assertGreaterThanOrEqual( 24, $profile['age'] );
			$this->assertLessThanOrEqual( 26, $profile['age'] );
		}
	}

	public function test_profile_detail_endpoint() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles/' . self::$user_id );
		$request->set_param( 'user_id', self::$user_id );
		$response = wpmf_rest_profile_detail( $request );
		
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$data = $response->get_data();
		$this->assertEquals( self::$user_id, $data['user_id'] );
		$this->assertEquals( 'Test headline', $data['headline'] );
	}

	public function test_profile_detail_returns_404_for_nonexistent() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles/99999' );
		$request->set_param( 'user_id', 99999 );
		$response = wpmf_rest_profile_detail( $request );
		
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'not_found', $response->get_error_code() );
	}

	public function test_matches_me_requires_authentication() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/matches/me' );
		$response = wpmf_rest_matches_me( $request );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertEmpty( $data ); // No profile for current user
	}

	public function test_likes_me_requires_authentication() {
		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/likes/me' );
		$response = wpmf_rest_likes_me( $request );
		
		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$data = $response->get_data();
		$this->assertIsArray( $data );
	}

	public function test_profiles_endpoint_excludes_blocked_users() {
		// Set current user for blocking test
		wp_set_current_user( self::$user_id );
		
		// Block the second user
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wpmf_blocks',
			array(
				'actor_id'       => self::$user_id,
				'target_user_id' => self::$user2_id,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		);

		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles' );
		$response = wpmf_rest_profiles( $request );
		
		$data = $response->get_data();
		$user_ids = array_column( $data, 'user_id' );
		$this->assertNotContains( self::$user2_id, $user_ids );

		// Clean up
		$wpdb->delete(
			$wpdb->prefix . 'wpmf_blocks',
			array(
				'actor_id'       => self::$user_id,
				'target_user_id' => self::$user2_id,
			),
			array( '%d', '%d' )
		);
	}

	public function test_profile_detail_blocked_returns_403() {
		// Set current user
		wp_set_current_user( self::$user_id );
		
		// Block the second user
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wpmf_blocks',
			array(
				'actor_id'       => self::$user_id,
				'target_user_id' => self::$user2_id,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		);

		$request = new WP_REST_Request( 'GET', '/wpmatch-free/v1/profiles/' . self::$user2_id );
		$request->set_param( 'user_id', self::$user2_id );
		$response = wpmf_rest_profile_detail( $request );
		
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'forbidden', $response->get_error_code() );

		// Clean up
		$wpdb->delete(
			$wpdb->prefix . 'wpmf_blocks',
			array(
				'actor_id'       => self::$user_id,
				'target_user_id' => self::$user2_id,
			),
			array( '%d', '%d' )
		);
	}
}