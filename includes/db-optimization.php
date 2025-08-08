<?php
/**
 * Database optimization for WP Match Free performance.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database optimization class.
 *
 * @since 0.1.0
 */
class WPMF_DB_Optimization {

	/**
	 * Add composite indexes for common search patterns.
	 *
	 * @since 0.1.0
	 */
	public static function add_performance_indexes() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'wpmf_';

		// Profiles table optimizations
		$profiles_indexes = array(
			// Search optimization: status + region + age + last_active
			'search_main'       => "CREATE INDEX IF NOT EXISTS idx_search_main ON {$prefix}profiles (status, region, age, last_active DESC)",

			// Age range searches
			'age_range'         => "CREATE INDEX IF NOT EXISTS idx_age_range ON {$prefix}profiles (status, age, last_active DESC)",

			// Region searches
			'region_active'     => "CREATE INDEX IF NOT EXISTS idx_region_active ON {$prefix}profiles (status, region, last_active DESC)",

			// Verification searches
			'verified_profiles' => "CREATE INDEX IF NOT EXISTS idx_verified ON {$prefix}profiles (status, verified, last_active DESC)",

			// Photo requirement searches (composite with photos table)
			'user_status'       => "CREATE INDEX IF NOT EXISTS idx_user_status ON {$prefix}profiles (user_id, status)",
		);

		// Messages table optimizations
		$messages_indexes = array(
			// Thread conversations
			'thread_time'    => "CREATE INDEX IF NOT EXISTS idx_thread_time ON {$prefix}messages (thread_id, created_at DESC)",

			// User message history
			'sender_time'    => "CREATE INDEX IF NOT EXISTS idx_sender_time ON {$prefix}messages (sender_id, created_at DESC)",
			'recipient_time' => "CREATE INDEX IF NOT EXISTS idx_recipient_time ON {$prefix}messages (recipient_id, created_at DESC)",

			// Rate limiting queries
			'sender_day'     => "CREATE INDEX IF NOT EXISTS idx_sender_day ON {$prefix}messages (sender_id, created_at)",
		);

		// Likes table optimizations
		$likes_indexes = array(
			// User's given likes
			'actor_time'  => "CREATE INDEX IF NOT EXISTS idx_actor_time ON {$prefix}likes (actor_id, created_at DESC)",

			// User's received likes
			'target_time' => "CREATE INDEX IF NOT EXISTS idx_target_time ON {$prefix}likes (target_user_id, created_at DESC)",

			// Rate limiting
			'actor_day'   => "CREATE INDEX IF NOT EXISTS idx_actor_day ON {$prefix}likes (actor_id, created_at)",
		);

		// Photos table optimizations
		$photos_indexes = array(
			// Primary photo lookup
			'user_primary'   => "CREATE INDEX IF NOT EXISTS idx_user_primary ON {$prefix}photos (user_id, is_primary, status)",

			// Moderation workflow
			'status_created' => "CREATE INDEX IF NOT EXISTS idx_status_created ON {$prefix}photos (status, created_at DESC)",
		);

		// Blocks table optimizations
		$blocks_indexes = array(
			// Blocking checks (most common query pattern)
			'block_check'   => "CREATE INDEX IF NOT EXISTS idx_block_check ON {$prefix}blocks (actor_id, target_user_id)",
			'reverse_block' => "CREATE INDEX IF NOT EXISTS idx_reverse_block ON {$prefix}blocks (target_user_id, actor_id)",
		);

		$all_indexes = array_merge(
			$profiles_indexes,
			$messages_indexes,
			$likes_indexes,
			$photos_indexes,
			$blocks_indexes
		);

		$results = array();
		foreach ( $all_indexes as $name => $sql ) {
			$result           = $wpdb->query( $sql );
			$results[ $name ] = $result !== false;
		}

		return $results;
	}

	/**
	 * Optimize search query with better structure.
	 *
	 * @param array $filters Search filters.
	 * @param int   $user_id Current user ID for blocking.
	 * @param int   $limit Result limit.
	 * @param int   $offset Result offset.
	 * @return array Search results.
	 * @since 0.1.0
	 */
	public static function optimized_search( $filters = array(), $user_id = 0, $limit = 50, $offset = 0 ) {
		global $wpdb;
		$profiles_table = $wpdb->prefix . 'wpmf_profiles';
		$photos_table   = $wpdb->prefix . 'wpmf_photos';
		$blocks_table   = $wpdb->prefix . 'wpmf_blocks';

		// Base conditions
		$where_conditions = array( "p.status = 'active'" );
		$join_clauses     = array();
		$params           = array();

		// Age filters
		if ( ! empty( $filters['age_min'] ) ) {
			$where_conditions[] = 'p.age >= %d';
			$params[]           = (int) $filters['age_min'];
		}
		if ( ! empty( $filters['age_max'] ) ) {
			$where_conditions[] = 'p.age <= %d';
			$params[]           = (int) $filters['age_max'];
		}

		// Region filter
		if ( ! empty( $filters['region'] ) ) {
			$where_conditions[] = 'p.region = %s';
			$params[]           = sanitize_text_field( $filters['region'] );
		}

		// Verification filter
		if ( ! empty( $filters['verified'] ) ) {
			$where_conditions[] = 'p.verified = 1';
		}

		// Photo requirement filter
		if ( ! empty( $filters['has_photo'] ) ) {
			$join_clauses[]     = "INNER JOIN {$photos_table} ph ON p.user_id = ph.user_id";
			$where_conditions[] = "ph.is_primary = 1 AND ph.status = 'approved'";
		}

		// Blocking filter for logged-in users
		if ( $user_id > 0 ) {
			// Exclude users that current user has blocked
			$where_conditions[] = "NOT EXISTS (
				SELECT 1 FROM {$blocks_table} b1 
				WHERE b1.actor_id = %d AND b1.target_user_id = p.user_id
			)";
			$params[]           = $user_id;

			// Exclude users that have blocked current user
			$where_conditions[] = "NOT EXISTS (
				SELECT 1 FROM {$blocks_table} b2 
				WHERE b2.target_user_id = %d AND b2.actor_id = p.user_id
			)";
			$params[]           = $user_id;

			// Exclude current user from results
			$where_conditions[] = 'p.user_id != %d';
			$params[]           = $user_id;
		}

		// Build the query
		$joins = implode( ' ', $join_clauses );
		$where = implode( ' AND ', $where_conditions );

		$sql = "
			SELECT DISTINCT p.*
			FROM {$profiles_table} p
			{$joins}
			WHERE {$where}
			ORDER BY p.last_active DESC, p.created_at DESC
			LIMIT %d OFFSET %d
		";

		$params[] = $limit;
		$params[] = $offset;

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Get optimized profile count for search filters.
	 *
	 * @param array $filters Search filters.
	 * @param int   $user_id Current user ID.
	 * @return int Total matching profiles.
	 * @since 0.1.0
	 */
	public static function get_search_count( $filters = array(), $user_id = 0 ) {
		global $wpdb;
		$profiles_table = $wpdb->prefix . 'wpmf_profiles';
		$photos_table   = $wpdb->prefix . 'wpmf_photos';
		$blocks_table   = $wpdb->prefix . 'wpmf_blocks';

		// Base conditions
		$where_conditions = array( "p.status = 'active'" );
		$join_clauses     = array();
		$params           = array();

		// Age filters
		if ( ! empty( $filters['age_min'] ) ) {
			$where_conditions[] = 'p.age >= %d';
			$params[]           = (int) $filters['age_min'];
		}
		if ( ! empty( $filters['age_max'] ) ) {
			$where_conditions[] = 'p.age <= %d';
			$params[]           = (int) $filters['age_max'];
		}

		// Region filter
		if ( ! empty( $filters['region'] ) ) {
			$where_conditions[] = 'p.region = %s';
			$params[]           = sanitize_text_field( $filters['region'] );
		}

		// Verification filter
		if ( ! empty( $filters['verified'] ) ) {
			$where_conditions[] = 'p.verified = 1';
		}

		// Photo requirement filter
		if ( ! empty( $filters['has_photo'] ) ) {
			$join_clauses[]     = "INNER JOIN {$photos_table} ph ON p.user_id = ph.user_id";
			$where_conditions[] = "ph.is_primary = 1 AND ph.status = 'approved'";
		}

		// Blocking filter for logged-in users
		if ( $user_id > 0 ) {
			$where_conditions[] = "NOT EXISTS (
				SELECT 1 FROM {$blocks_table} b1 
				WHERE b1.actor_id = %d AND b1.target_user_id = p.user_id
			)";
			$params[]           = $user_id;

			$where_conditions[] = "NOT EXISTS (
				SELECT 1 FROM {$blocks_table} b2 
				WHERE b2.target_user_id = %d AND b2.actor_id = p.user_id
			)";
			$params[]           = $user_id;

			$where_conditions[] = 'p.user_id != %d';
			$params[]           = $user_id;
		}

		// Build the query
		$joins = implode( ' ', $join_clauses );
		$where = implode( ' AND ', $where_conditions );

		$sql = "
			SELECT COUNT(DISTINCT p.id)
			FROM {$profiles_table} p
			{$joins}
			WHERE {$where}
		";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Optimize rate limiting queries.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type Rate limit type ('messages' or 'likes').
	 * @return int Count of actions today.
	 * @since 0.1.0
	 */
	public static function get_daily_action_count( $user_id, $type ) {
		global $wpdb;

		$today_start = gmdate( 'Y-m-d 00:00:00' );
		$today_end   = gmdate( 'Y-m-d 23:59:59' );

		if ( 'messages' === $type ) {
			$table = $wpdb->prefix . 'wpmf_messages';
			$sql   = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE sender_id = %d AND created_at BETWEEN %s AND %s",
				$user_id,
				$today_start,
				$today_end
			);
		} elseif ( 'likes' === $type ) {
			$table = $wpdb->prefix . 'wpmf_likes';
			$sql   = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE actor_id = %d AND created_at BETWEEN %s AND %s",
				$user_id,
				$today_start,
				$today_end
			);
		} else {
			return 0;
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Batch profile updates for better performance.
	 *
	 * @param array $updates Array of profile updates with user_id as key.
	 * @return int Number of profiles updated.
	 * @since 0.1.0
	 */
	public static function batch_profile_updates( $updates ) {
		if ( empty( $updates ) ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wpmf_profiles';
		$count = 0;

		// Start transaction for consistency
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $updates as $user_id => $data ) {
				$result = wpmf_profile_update_by_user_id( $user_id, $data );
				if ( $result ) {
					++$count;
				}
			}

			$wpdb->query( 'COMMIT' );
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( 'WP Match Free batch update error: ' . $e->getMessage() );
		}

		return $count;
	}

	/**
	 * Clean up old data for performance.
	 *
	 * @param int $days_old Delete data older than this many days.
	 * @since 0.1.0
	 */
	public static function cleanup_old_data( $days_old = 90 ) {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

		$cleanup_counts = array();

		// Clean old messages (keep conversations but remove very old ones)
		$messages_table             = $wpdb->prefix . 'wpmf_messages';
		$deleted                    = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$messages_table} WHERE created_at < %s AND status = 'deleted'",
				$cutoff
			)
		);
		$cleanup_counts['messages'] = $deleted;

		// Clean old unverified profiles
		$profiles_table             = $wpdb->prefix . 'wpmf_profiles';
		$deleted                    = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$profiles_table} WHERE created_at < %s AND status = 'inactive' AND last_active IS NULL",
				$cutoff
			)
		);
		$cleanup_counts['profiles'] = $deleted;

		// Clean old failed verification attempts
		$verifications_table             = $wpdb->prefix . 'wpmf_verifications';
		$deleted                         = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$verifications_table} WHERE created_at < %s AND status = 'rejected'",
				$cutoff
			)
		);
		$cleanup_counts['verifications'] = $deleted;

		return $cleanup_counts;
	}

	/**
	 * Analyze database performance.
	 *
	 * @return array Performance analysis.
	 * @since 0.1.0
	 */
	public static function analyze_performance() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'wpmf_';

		$analysis = array(
			'table_sizes'  => array(),
			'index_usage'  => array(),
			'slow_queries' => array(),
		);

		// Get table sizes
		$tables = array( 'profiles', 'messages', 'likes', 'photos', 'blocks', 'reports', 'verifications' );
		foreach ( $tables as $table ) {
			$result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT 
					COUNT(*) as row_count,
					ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
				FROM information_schema.TABLES 
				WHERE table_schema = %s AND table_name = %s',
					DB_NAME,
					$prefix . $table
				)
			);

			if ( $result ) {
				$analysis['table_sizes'][ $table ] = array(
					'rows'    => (int) $result->row_count,
					'size_mb' => (float) $result->size_mb,
				);
			}
		}

		// Check for missing indexes (simplified check)
		$index_checks = array(
			'profiles_search' => "SHOW INDEX FROM {$prefix}profiles WHERE Key_name = 'idx_search_main'",
			'messages_thread' => "SHOW INDEX FROM {$prefix}messages WHERE Key_name = 'idx_thread_time'",
			'likes_actor'     => "SHOW INDEX FROM {$prefix}likes WHERE Key_name = 'idx_actor_time'",
		);

		foreach ( $index_checks as $name => $sql ) {
			$result                           = $wpdb->get_results( $sql );
			$analysis['index_usage'][ $name ] = ! empty( $result );
		}

		return $analysis;
	}

	/**
	 * Get database statistics for admin dashboard.
	 *
	 * @return array Database statistics.
	 * @since 0.1.0
	 */
	public static function get_stats() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'wpmf_';

		$stats = array();

		// Profile statistics
		$stats['profiles'] = array(
			'total'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}profiles" ),
			'active'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}profiles WHERE status = 'active'" ),
			'verified' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}profiles WHERE verified = 1" ),
		);

		// Recent activity
		$week_ago          = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$stats['activity'] = array(
			'messages_week'     => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$prefix}messages WHERE created_at >= %s",
					$week_ago
				)
			),
			'likes_week'        => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$prefix}likes WHERE created_at >= %s",
					$week_ago
				)
			),
			'active_users_week' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$prefix}profiles WHERE last_active >= %s",
					$week_ago
				)
			),
		);

		return $stats;
	}
}

/**
 * Add performance indexes on plugin activation.
 *
 * @since 0.1.0
 */
function wpmf_add_performance_indexes() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$results = WPMF_DB_Optimization::add_performance_indexes();

	// Log results for debugging
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		$success_count = array_sum( $results );
		$total_count   = count( $results );
		error_log( "WP Match Free: Added {$success_count}/{$total_count} performance indexes" );
	}
}
register_activation_hook( WPMATCH_FREE_SLUG, 'wpmf_add_performance_indexes' );

/**
 * Schedule cleanup task.
 *
 * @since 0.1.0
 */
function wpmf_schedule_cleanup() {
	if ( ! wp_next_scheduled( 'wpmf_cleanup_old_data' ) ) {
		wp_schedule_event( time(), 'weekly', 'wpmf_cleanup_old_data' );
	}
}
add_action( 'wp', 'wpmf_schedule_cleanup' );

/**
 * Cleanup old data hook.
 *
 * @since 0.1.0
 */
function wpmf_cleanup_old_data_hook() {
	$cleanup_counts = WPMF_DB_Optimization::cleanup_old_data( 90 );

	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		error_log( 'WP Match Free cleanup: ' . wp_json_encode( $cleanup_counts ) );
	}
}
add_action( 'wpmf_cleanup_old_data', 'wpmf_cleanup_old_data_hook' );


/**
 * Render performance admin page.
 *
 * @since 0.1.0
 */
function wpmf_performance_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$stats       = WPMF_DB_Optimization::get_stats();
	$analysis    = WPMF_DB_Optimization::analyze_performance();
	$cache_stats = WPMF_Cache::get_stats_debug();

	?>
	<div class="wrap">
		<h1>WP Match Free - Performance Dashboard</h1>
		
		<div class="notice notice-info">
			<p><strong>Database Performance:</strong> This page shows performance statistics and optimization recommendations.</p>
		</div>
		
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
			
			<div class="postbox">
				<h2 class="hndle">Database Statistics</h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th>Total Profiles:</th>
							<td><?php echo esc_html( number_format( $stats['profiles']['total'] ) ); ?></td>
						</tr>
						<tr>
							<th>Active Profiles:</th>
							<td><?php echo esc_html( number_format( $stats['profiles']['active'] ) ); ?></td>
						</tr>
						<tr>
							<th>Verified Profiles:</th>
							<td><?php echo esc_html( number_format( $stats['profiles']['verified'] ) ); ?></td>
						</tr>
						<tr>
							<th>Messages (7 days):</th>
							<td><?php echo esc_html( number_format( $stats['activity']['messages_week'] ) ); ?></td>
						</tr>
						<tr>
							<th>Likes (7 days):</th>
							<td><?php echo esc_html( number_format( $stats['activity']['likes_week'] ) ); ?></td>
						</tr>
						<tr>
							<th>Active Users (7 days):</th>
							<td><?php echo esc_html( number_format( $stats['activity']['active_users_week'] ) ); ?></td>
						</tr>
					</table>
				</div>
			</div>
			
			<div class="postbox">
				<h2 class="hndle">Cache Performance</h2>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th>Cache Status:</th>
							<td>
								<?php if ( $cache_stats['cache_enabled'] ) : ?>
									<span style="color: green;">✓ External Cache Enabled</span>
								<?php else : ?>
									<span style="color: orange;">⚠ Internal Cache Only</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th>Cache Type:</th>
							<td><?php echo esc_html( ucfirst( $cache_stats['cache_type'] ) ); ?></td>
						</tr>
						<tr>
							<th>Profile Cache Version:</th>
							<td><?php echo esc_html( $cache_stats['groups']['wpmatch_profiles']['version'] ?? 'Not set' ); ?></td>
						</tr>
						<tr>
							<th>Search Cache Version:</th>
							<td><?php echo esc_html( $cache_stats['groups']['wpmatch_search']['version'] ?? 'Not set' ); ?></td>
						</tr>
					</table>
					
					<p>
						<a href="<?php echo esc_url( add_query_arg( 'action', 'flush_cache', admin_url( 'admin.php?page=wpmf-performance' ) ) ); ?>" 
							class="button">Flush All Caches</a>
					</p>
				</div>
			</div>
			
		</div>
		
		<div class="postbox">
			<h2 class="hndle">Table Sizes</h2>
			<div class="inside">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Table</th>
							<th>Rows</th>
							<th>Size (MB)</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $analysis['table_sizes'] as $table => $data ) : ?>
						<tr>
							<td><?php echo esc_html( $table ); ?></td>
							<td><?php echo esc_html( number_format( $data['rows'] ) ); ?></td>
							<td><?php echo esc_html( number_format( $data['size_mb'], 2 ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		
		<div class="postbox">
			<h2 class="hndle">Index Status</h2>
			<div class="inside">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>Index</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $analysis['index_usage'] as $index => $exists ) : ?>
						<tr>
							<td><?php echo esc_html( $index ); ?></td>
							<td>
								<?php if ( $exists ) : ?>
									<span style="color: green;">✓ Present</span>
								<?php else : ?>
									<span style="color: red;">✗ Missing</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<p>
					<a href="<?php echo esc_url( add_query_arg( 'action', 'rebuild_indexes', admin_url( 'admin.php?page=wpmf-performance' ) ) ); ?>" 
						class="button">Rebuild Performance Indexes</a>
				</p>
			</div>
		</div>
		
	</div>
	<?php
}

/**
 * Handle performance admin actions.
 *
 * @since 0.1.0
 */
function wpmf_handle_performance_actions() {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['page'] ) || 'wpmf-performance' !== $_GET['page'] ) {
		return;
	}

	if ( isset( $_GET['action'] ) ) {
		switch ( $_GET['action'] ) {
			case 'flush_cache':
				WPMF_Cache::flush_all();
				wp_cache_flush();
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-success"><p>All caches have been flushed.</p></div>';
					}
				);
				break;

			case 'rebuild_indexes':
				$results       = WPMF_DB_Optimization::add_performance_indexes();
				$success_count = array_sum( $results );
				$total_count   = count( $results );
				add_action(
					'admin_notices',
					function () use ( $success_count, $total_count ) {
						echo "<div class='notice notice-success'><p>Rebuilt {$success_count}/{$total_count} performance indexes.</p></div>";
					}
				);
				break;
		}
	}
}
add_action( 'admin_init', 'wpmf_handle_performance_actions' );