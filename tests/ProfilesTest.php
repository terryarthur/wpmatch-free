<?php
class ProfilesTest extends WP_UnitTestCase {
	protected static $user_id;
	protected static $profile_id;

	public static function wpSetUpBeforeClass($factory) {
		self::$user_id = $factory->user->create();
		self::$profile_id = wpmf_profile_create([
			'user_id' => self::$user_id,
			'gender' => 'male',
			'region' => 'europe',
			'bio' => 'Test bio'
		]);
	}

	public function test_profile_creation() {
		$profile = wpmf_profile_get_by_user_id(self::$user_id);
		$this->assertEquals('male', $profile['gender']);
		$this->assertEquals('europe', $profile['region']);
		$this->assertEquals('Test bio', $profile['bio']);
	}

	public function test_profile_update() {
		$updated = wpmf_profile_update_by_user_id(self::$user_id, ['region' => 'asia']);
		$this->assertSame(1, $updated);
		$profile = wpmf_profile_get_by_user_id(self::$user_id);
		$this->assertEquals('asia', $profile['region']);
	}

	public function test_profile_deletion() {
		$deleted = wpmf_profile_delete_by_user_id(self::$user_id);
		$this->assertSame(1, $deleted);
		$profile = wpmf_profile_get_by_user_id(self::$user_id);
		$this->assertNull($profile);
	}
}