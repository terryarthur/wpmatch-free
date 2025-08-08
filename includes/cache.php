<?php
/**
 * Caching layer for WP Match Free performance optimization.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache management class for dating plugin.
 *
 * @since 0.1.0
 */
class WPMF_Cache {

	/**
	 * Cache group for dating profiles.
	 *
	 * @since 0.1.0
	 */
	const PROFILE_GROUP = 'wpmatch_profiles';

	/**
	 * Cache group for search results.
	 *
	 * @since 0.1.0
	 */
	const SEARCH_GROUP = 'wpmatch_search';

	/**
	 * Cache group for user interactions.
	 *
	 * @since 0.1.0
	 */
	const INTERACTION_GROUP = 'wpmatch_interactions';

	/**
	 * Cache group for statistics.
	 *
	 * @since 0.1.0
	 */
	const STATS_GROUP = 'wpmatch_stats';

	/**
	 * Default cache duration in seconds.
	 *
	 * @since 0.1.0
	 */
	const DEFAULT_EXPIRATION = 300; // 5 minutes

	/**
	 * Long cache duration for rarely changing data.
	 *
	 * @since 0.1.0
	 */
	const LONG_EXPIRATION = 3600; // 1 hour

	/**
	 * Short cache duration for frequently changing data.
	 *
	 * @since 0.1.0
	 */
	const SHORT_EXPIRATION = 60; // 1 minute

	/**
	 * Get cached data with fallback to generation function.
	 *
	 * @param string   $key Cache key.
	 * @param callable $callback Function to generate data if not cached.
	 * @param string   $group Cache group.
	 * @param int      $expiration Cache duration in seconds.
	 * @return mixed Cached or generated data.
	 * @since 0.1.0
	 */
	public static function get_or_set( $key, $callback, $group = 'default', $expiration = self::DEFAULT_EXPIRATION ) {
		$data = wp_cache_get( $key, $group );
		
		if ( false === $data ) {
			$data = call_user_func( $callback );
			if ( null !== $data ) {
				wp_cache_set( $key, $data, $group, $expiration );
			}
		}
		
		return $data;
	}

	/**
	 * Cache profile data.
	 *
	 * @param int   $user_id User ID.
	 * @param array $profile Profile data.
	 * @since 0.1.0
	 */
	public static function set_profile( $user_id, $profile ) {
		$key = 'profile_' . $user_id;
		wp_cache_set( $key, $profile, self::PROFILE_GROUP, self::DEFAULT_EXPIRATION );
		
		// Also cache by profile ID if available
		if ( isset( $profile['id'] ) ) {
			wp_cache_set( 'profile_id_' . $profile['id'], $profile, self::PROFILE_GROUP, self::DEFAULT_EXPIRATION );
		}
	}

	/**
	 * Get cached profile data.
	 *
	 * @param int $user_id User ID.
	 * @return array|false Profile data or false if not cached.
	 * @since 0.1.0
	 */
	public static function get_profile( $user_id ) {
		$key = 'profile_' . $user_id;
		return wp_cache_get( $key, self::PROFILE_GROUP );
	}

	/**
	 * Delete cached profile data.
	 *
	 * @param int $user_id User ID.
	 * @since 0.1.0
	 */
	public static function delete_profile( $user_id ) {
		$key = 'profile_' . $user_id;
		wp_cache_delete( $key, self::PROFILE_GROUP );
		
		// Also try to delete by profile ID
		$profile = wpmf_profile_get_by_user_id( $user_id );
		if ( $profile && isset( $profile['id'] ) ) {
			wp_cache_delete( 'profile_id_' . $profile['id'], self::PROFILE_GROUP );
		}
	}

	/**
	 * Generate cache key for search results.
	 *
	 * @param array $filters Search filters.
	 * @param int   $user_id Current user ID for personalization.
	 * @return string Cache key.
	 * @since 0.1.0
	 */
	public static function generate_search_key( $filters, $user_id = 0 ) {
		// Sort filters for consistent key generation
		ksort( $filters );
		
		$key_parts = array(
			'search',
			md5( serialize( $filters ) ),
			$user_id,
		);
		
		return implode( '_', $key_parts );
	}

	/**
	 * Cache search results.
	 *
	 * @param array $filters Search filters.
	 * @param array $results Search results.
	 * @param int   $user_id Current user ID.
	 * @param int   $expiration Cache duration.
	 * @since 0.1.0
	 */
	public static function set_search_results( $filters, $results, $user_id = 0, $expiration = self::SHORT_EXPIRATION ) {
		$key = self::generate_search_key( $filters, $user_id );
		wp_cache_set( $key, $results, self::SEARCH_GROUP, $expiration );
	}

	/**
	 * Get cached search results.
	 *
	 * @param array $filters Search filters.
	 * @param int   $user_id Current user ID.
	 * @return array|false Search results or false if not cached.
	 * @since 0.1.0
	 */
	public static function get_search_results( $filters, $user_id = 0 ) {
		$key = self::generate_search_key( $filters, $user_id );
		return wp_cache_get( $key, self::SEARCH_GROUP );
	}

	/**
	 * Invalidate search cache for all users.
	 *
	 * This is called when profiles are updated to ensure fresh search results.
	 *
	 * @since 0.1.0
	 */
	public static function invalidate_search_cache() {
		// WordPress doesn't have group invalidation, so we use a cache key version
		$version = wp_cache_get( 'search_version', self::SEARCH_GROUP );
		if ( false === $version ) {
			$version = 1;
		} else {
			$version++;
		}
		wp_cache_set( 'search_version', $version, self::SEARCH_GROUP, self::LONG_EXPIRATION );
	}

	/**
	 * Get versioned cache key for search results.
	 *
	 * @param array $filters Search filters.
	 * @param int   $user_id Current user ID.
	 * @return string Versioned cache key.
	 * @since 0.1.0
	 */
	public static function get_versioned_search_key( $filters, $user_id = 0 ) {
		$version = wp_cache_get( 'search_version', self::SEARCH_GROUP );
		if ( false === $version ) {
			$version = 1;
			wp_cache_set( 'search_version', $version, self::SEARCH_GROUP, self::LONG_EXPIRATION );
		}
		
		$base_key = self::generate_search_key( $filters, $user_id );
		return $base_key . '_v' . $version;
	}

	/**
	 * Cache user interaction data (likes, blocks, etc.).
	 *
	 * @param int    $user_id User ID.
	 * @param string $type Interaction type (likes, blocks, matches).
	 * @param array  $data Interaction data.
	 * @since 0.1.0
	 */
	public static function set_user_interactions( $user_id, $type, $data ) {
		$key = "user_{$user_id}_{$type}";
		wp_cache_set( $key, $data, self::INTERACTION_GROUP, self::DEFAULT_EXPIRATION );
	}

	/**
	 * Get cached user interaction data.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type Interaction type.
	 * @return array|false Interaction data or false if not cached.
	 * @since 0.1.0
	 */
	public static function get_user_interactions( $user_id, $type ) {
		$key = "user_{$user_id}_{$type}";
		return wp_cache_get( $key, self::INTERACTION_GROUP );
	}

	/**
	 * Delete user interaction cache.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type Interaction type.
	 * @since 0.1.0
	 */
	public static function delete_user_interactions( $user_id, $type ) {
		$key = "user_{$user_id}_{$type}";
		wp_cache_delete( $key, self::INTERACTION_GROUP );
	}

	/**
	 * Cache statistics data.
	 *
	 * @param string $stat_key Statistics key.
	 * @param mixed  $data Statistics data.
	 * @param int    $expiration Cache duration.
	 * @since 0.1.0
	 */
	public static function set_stats( $stat_key, $data, $expiration = self::LONG_EXPIRATION ) {
		wp_cache_set( $stat_key, $data, self::STATS_GROUP, $expiration );
	}

	/**
	 * Get cached statistics data.
	 *
	 * @param string $stat_key Statistics key.
	 * @return mixed|false Statistics data or false if not cached.
	 * @since 0.1.0
	 */
	public static function get_stats( $stat_key ) {
		return wp_cache_get( $stat_key, self::STATS_GROUP );
	}

	/**
	 * Warm up cache with frequently accessed data.
	 *
	 * @since 0.1.0
	 */
	public static function warm_cache() {
		// Cache popular statistics
		self::get_or_set( 'total_profiles', function() {
			global $wpdb;
			$table = $wpdb->prefix . 'wpmf_profiles';
			return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'active' ) );
		}, self::STATS_GROUP, self::LONG_EXPIRATION );

		// Cache recent active users
		self::get_or_set( 'recent_active', function() {
			global $wpdb;
			$table = $wpdb->prefix . 'wpmf_profiles';
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
			return $wpdb->get_results( $wpdb->prepare( 
				"SELECT user_id FROM {$table} WHERE status = %s AND last_active >= %s ORDER BY last_active DESC LIMIT 20",
				'active', 
				$cutoff 
			), ARRAY_A );
		}, self::STATS_GROUP, self::DEFAULT_EXPIRATION );
	}

	/**
	 * Clear all plugin caches.
	 *
	 * @since 0.1.0
	 */
	public static function flush_all() {
		// WordPress doesn't support group flushing, so we increment versions
		$groups = array( self::PROFILE_GROUP, self::SEARCH_GROUP, self::INTERACTION_GROUP, self::STATS_GROUP );
		
		foreach ( $groups as $group ) {
			$version = wp_cache_get( 'version', $group );
			if ( false === $version ) {
				$version = 1;
			} else {
				$version++;
			}
			wp_cache_set( 'version', $version, $group, self::LONG_EXPIRATION );
		}
	}

	/**
	 * Get cache statistics for debugging.
	 *
	 * @return array Cache statistics.
	 * @since 0.1.0
	 */
	public static function get_stats_debug() {
		$stats = array(
			'cache_enabled' => wp_using_ext_object_cache(),
			'cache_type' => wp_using_ext_object_cache() ? 'external' : 'internal',
			'groups' => array(
				self::PROFILE_GROUP => array(
					'version' => wp_cache_get( 'version', self::PROFILE_GROUP ),
				),
				self::SEARCH_GROUP => array(
					'version' => wp_cache_get( 'search_version', self::SEARCH_GROUP ),
				),
				self::INTERACTION_GROUP => array(
					'version' => wp_cache_get( 'version', self::INTERACTION_GROUP ),
				),
				self::STATS_GROUP => array(
					'version' => wp_cache_get( 'version', self::STATS_GROUP ),
				),
			),
		);
		
		return $stats;
	}
}

/**
 * Initialize caching system.
 *
 * @since 0.1.0
 */
function wpmf_cache_init() {
	// Add cache groups to global cache groups for multisite
	if ( function_exists( 'wp_cache_add_global_groups' ) ) {
		wp_cache_add_global_groups( array(
			WPMF_Cache::PROFILE_GROUP,
			WPMF_Cache::SEARCH_GROUP,
			WPMF_Cache::INTERACTION_GROUP,
			WPMF_Cache::STATS_GROUP,
		) );
	}
	
	// Warm cache on low-traffic requests
	if ( is_admin() && current_user_can( 'manage_options' ) ) {
		add_action( 'admin_init', array( 'WPMF_Cache', 'warm_cache' ), 20 );
	}
}
add_action( 'init', 'wpmf_cache_init' );

/**
 * Clear profile cache when profile is updated.
 *
 * @param int   $profile_id Profile ID.
 * @param int   $user_id User ID.
 * @param array $old_data Old profile data.
 * @param array $new_data New profile data.
 * @since 0.1.0
 */
function wpmf_cache_profile_updated( $profile_id, $user_id, $old_data, $new_data ) {
	WPMF_Cache::delete_profile( $user_id );
	WPMF_Cache::invalidate_search_cache();
}
add_action( 'wpmf_profile_updated', 'wpmf_cache_profile_updated', 10, 4 );

/**
 * Clear profile cache when profile is created.
 *
 * @param int $profile_id Profile ID.
 * @param int $user_id User ID.
 * @since 0.1.0
 */
function wpmf_cache_profile_created( $profile_id, $user_id ) {
	WPMF_Cache::delete_profile( $user_id );
	WPMF_Cache::invalidate_search_cache();
}
add_action( 'wpmf_profile_created', 'wpmf_cache_profile_created', 10, 2 );

/**
 * Clear interaction cache when interactions change.
 *
 * @param int $like_id Like ID.
 * @param int $actor_id User who liked.
 * @param int $target_user_id User who was liked.
 * @since 0.1.0
 */
function wpmf_cache_like_added( $like_id, $actor_id, $target_user_id ) {
	WPMF_Cache::delete_user_interactions( $actor_id, 'likes_given' );
	WPMF_Cache::delete_user_interactions( $target_user_id, 'likes_received' );
}
add_action( 'wpmf_like_added', 'wpmf_cache_like_added', 10, 3 );

/**
 * Add cache debug information to admin footer.
 *
 * @since 0.1.0
 */
function wpmf_cache_debug_info() {
	if ( ! current_user_can( 'manage_options' ) || ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}
	
	$stats = WPMF_Cache::get_stats_debug();
	echo '<!-- WP Match Cache Debug: ' . wp_json_encode( $stats ) . ' -->';
}
add_action( 'wp_footer', 'wpmf_cache_debug_info' );
add_action( 'admin_footer', 'wpmf_cache_debug_info' );