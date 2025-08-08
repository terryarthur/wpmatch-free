<?php
/**
 * Test rate limiting functionality.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

class RateLimitsTest extends WP_UnitTestCase {

	protected static $user_id;
	protected static $user2_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_id = $factory->user->create();
		self::$user2_id = $factory->user->create();
	}

	public function test_like_rate_limit() {
		// Set a low rate limit for testing
		update_option( 'wpmf_likes_per_day', 2 );
		
		// First like should succeed
		$result1 = wpmf_like_toggle( self::$user_id, self::$user2_id );
		$this->assertTrue( $result1 );
		
		// Toggle off
		$result2 = wpmf_like_toggle( self::$user_id, self::$user2_id );
		$this->assertFalse( $result2 );
		
		// Create a third user for more likes
		$user3_id = $this->factory->user->create();
		$user4_id = $this->factory->user->create();
		
		// These should work (count: 1, 2)
		$result3 = wpmf_like_toggle( self::$user_id, $user3_id );
		$this->assertTrue( $result3 );
		
		$result4 = wpmf_like_toggle( self::$user_id, $user4_id );
		$this->assertTrue( $result4 );
		
		// Create another user
		$user5_id = $this->factory->user->create();
		
		// This should fail due to rate limit (count: 3, limit: 2)
		$result5 = wpmf_like_toggle( self::$user_id, $user5_id );
		$this->assertFalse( $result5 );
		
		// Reset option
		update_option( 'wpmf_likes_per_day', 50 );
	}

	public function test_message_rate_limit() {
		// Set a low rate limit for testing
		update_option( 'wpmf_messages_per_day', 2 );
		
		// Create a thread
		$thread_id = wpmf_thread_create();
		$this->assertGreaterThan( 0, $thread_id );
		
		// First message should succeed
		$result1 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'Test message 1' );
		$this->assertGreaterThan( 0, $result1 );
		
		// Second message should succeed
		$result2 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'Test message 2' );
		$this->assertGreaterThan( 0, $result2 );
		
		// Third message should fail due to rate limit
		$result3 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'Test message 3' );
		$this->assertEquals( 0, $result3 );
		
		// Reset option
		update_option( 'wpmf_messages_per_day', 20 );
	}

	public function test_word_filter_blocks_messages() {
		// Set word filter
		update_option( 'wpmf_word_filter', 'badword,spam,test123' );
		
		$thread_id = wpmf_thread_create();
		$this->assertGreaterThan( 0, $thread_id );
		
		// Message with clean content should succeed
		$result1 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'This is a clean message' );
		$this->assertGreaterThan( 0, $result1 );
		
		// Message with filtered word should fail
		$result2 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'This contains badword content' );
		$this->assertEquals( 0, $result2 );
		
		// Message with another filtered word should fail
		$result3 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'This is spam message' );
		$this->assertEquals( 0, $result3 );
		
		// Case insensitive test
		$result4 = wpmf_message_send( $thread_id, self::$user_id, self::$user2_id, 'This contains TEST123 word' );
		$this->assertEquals( 0, $result4 );
		
		// Reset option
		update_option( 'wpmf_word_filter', '' );
	}

	public function test_rate_limit_resets_daily() {
		// This test would need to mock time functions to properly test
		// For now, we just verify the basic rate limiting logic works
		
		// Set very low limit
		update_option( 'wpmf_likes_per_day', 1 );
		
		$user3_id = $this->factory->user->create();
		$user4_id = $this->factory->user->create();
		
		// First like should work
		$result1 = wpmf_like_toggle( self::$user_id, $user3_id );
		$this->assertTrue( $result1 );
		
		// Second like should be blocked
		$result2 = wpmf_like_toggle( self::$user_id, $user4_id );
		$this->assertFalse( $result2 );
		
		// Reset
		update_option( 'wpmf_likes_per_day', 50 );
	}

	public function test_rate_limit_per_user() {
		// Test that rate limits are per-user, not global
		update_option( 'wpmf_likes_per_day', 1 );
		
		$target1 = $this->factory->user->create();
		$target2 = $this->factory->user->create();
		
		// User 1 uses their limit
		$result1 = wpmf_like_toggle( self::$user_id, $target1 );
		$this->assertTrue( $result1 );
		
		// User 1 is now at limit
		$result2 = wpmf_like_toggle( self::$user_id, $target2 );
		$this->assertFalse( $result2 );
		
		// User 2 should still be able to like (separate limit)
		$result3 = wpmf_like_toggle( self::$user2_id, $target1 );
		$this->assertTrue( $result3 );
		
		// Reset
		update_option( 'wpmf_likes_per_day', 50 );
	}
}