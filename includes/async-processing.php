<?php
/**
 * Async processing and lazy loading for WP Match Free.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Async processing manager.
 *
 * @since 0.1.0
 */
class WPMF_Async_Processing {

	/**
	 * Queue heavy operations for background processing.
	 *
	 * @param string $action Action name.
	 * @param array  $data Operation data.
	 * @param int    $priority Task priority (lower = higher priority).
	 * @return int Task ID.
	 * @since 0.1.0
	 */
	public static function queue_task( $action, $data = array(), $priority = 10 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'wpmf_async_tasks';
		
		// Create async tasks table if it doesn't exist
		self::maybe_create_tasks_table();
		
		$task_data = array(
			'action'     => sanitize_text_field( $action ),
			'data'       => wp_json_encode( $data ),
			'priority'   => absint( $priority ),
			'status'     => 'pending',
			'attempts'   => 0,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);
		
		$result = $wpdb->insert( $table, $task_data );
		
		if ( $result ) {
			// Schedule immediate processing for high priority tasks
			if ( $priority <= 5 ) {
				wp_schedule_single_event( time() + 30, 'wpmf_process_async_tasks' );
			}
			
			return $wpdb->insert_id;
		}
		
		return 0;
	}

	/**
	 * Process queued async tasks.
	 *
	 * @param int $batch_size Number of tasks to process per batch.
	 * @since 0.1.0
	 */
	public static function process_tasks( $batch_size = 5 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'wpmf_async_tasks';
		
		// Get pending tasks ordered by priority and creation time
		$tasks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} 
				WHERE status = 'pending' 
				AND attempts < 3 
				ORDER BY priority ASC, created_at ASC 
				LIMIT %d",
				$batch_size
			),
			ARRAY_A
		);
		
		foreach ( $tasks as $task ) {
			self::process_single_task( $task );
		}
		
		// Schedule next batch if there are more pending tasks
		$pending_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE status = 'pending' AND attempts < 3"
		);
		
		if ( $pending_count > 0 ) {
			wp_schedule_single_event( time() + 300, 'wpmf_process_async_tasks' ); // 5 minutes
		}
	}

	/**
	 * Process a single async task.
	 *
	 * @param array $task Task data.
	 * @since 0.1.0
	 */
	private static function process_single_task( $task ) {
		global $wpdb;
		
		$table   = $wpdb->prefix . 'wpmf_async_tasks';
		$task_id = (int) $task['id'];
		
		// Mark task as processing
		$wpdb->update(
			$table,
			array(
				'status'     => 'processing',
				'attempts'   => $task['attempts'] + 1,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $task_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
		
		$data = json_decode( $task['data'], true );
		$success = false;
		$error_message = '';
		
		try {
			switch ( $task['action'] ) {
				case 'update_user_stats':
					$success = self::update_user_stats( $data );
					break;
					
				case 'send_notification_email':
					$success = self::send_notification_email( $data );
					break;
					
				case 'process_photo_moderation':
					$success = self::process_photo_moderation( $data );
					break;
					
				case 'generate_compatibility_scores':
					$success = self::generate_compatibility_scores( $data );
					break;
					
				case 'cleanup_expired_data':
					$success = self::cleanup_expired_data( $data );
					break;
					
				default:
					// Allow custom actions via filter
					$success = apply_filters( 'wpmf_process_async_action', false, $task['action'], $data );
					break;
			}
		} catch ( Exception $e ) {
			$error_message = $e->getMessage();
			error_log( "WP Match Free async task error: {$error_message}" );
		}
		
		// Update task status
		if ( $success ) {
			$wpdb->update(
				$table,
				array(
					'status'     => 'completed',
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $task_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$status = $task['attempts'] >= 2 ? 'failed' : 'pending';
			$wpdb->update(
				$table,
				array(
					'status'        => $status,
					'error_message' => $error_message,
					'updated_at'    => current_time( 'mysql' ),
				),
				array( 'id' => $task_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Update user statistics in background.
	 *
	 * @param array $data Task data containing user_id.
	 * @return bool Success status.
	 * @since 0.1.0
	 */
	private static function update_user_stats( $data ) {
		if ( empty( $data['user_id'] ) ) {
			return false;
		}
		
		$user_id = (int) $data['user_id'];
		global $wpdb;
		
		// Update profile stats
		$profile_table = $wpdb->prefix . 'wpmf_profiles';
		$likes_table   = $wpdb->prefix . 'wpmf_likes';
		$messages_table = $wpdb->prefix . 'wpmf_messages';
		
		// Count likes received
		$likes_received = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$likes_table} WHERE target_user_id = %d", $user_id )
		);
		
		// Count messages sent/received
		$messages_count = $wpdb->get_var(
			$wpdb->prepare( 
				"SELECT COUNT(*) FROM {$messages_table} WHERE sender_id = %d OR recipient_id = %d", 
				$user_id, 
				$user_id 
			)
		);
		
		// Update last active time
		$wpdb->update(
			$profile_table,
			array( 'last_active' => current_time( 'mysql' ) ),
			array( 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		// Cache stats
		$stats = array(
			'likes_received' => $likes_received,
			'messages_count' => $messages_count,
			'last_updated'   => time(),
		);
		
		wp_cache_set( "user_stats_{$user_id}", $stats, 'wpmatch_stats', 3600 );
		
		return true;
	}

	/**
	 * Send notification email in background.
	 *
	 * @param array $data Email data.
	 * @return bool Success status.
	 * @since 0.1.0
	 */
	private static function send_notification_email( $data ) {
		if ( empty( $data['to'] ) || empty( $data['subject'] ) || empty( $data['message'] ) ) {
			return false;
		}
		
		$to      = sanitize_email( $data['to'] );
		$subject = sanitize_text_field( $data['subject'] );
		$message = wp_kses_post( $data['message'] );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		
		if ( ! empty( $data['from_email'] ) && ! empty( $data['from_name'] ) ) {
			$headers[] = 'From: ' . sanitize_text_field( $data['from_name'] ) . ' <' . sanitize_email( $data['from_email'] ) . '>';
		}
		
		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Process photo moderation in background.
	 *
	 * @param array $data Photo data.
	 * @return bool Success status.
	 * @since 0.1.0
	 */
	private static function process_photo_moderation( $data ) {
		if ( empty( $data['photo_id'] ) ) {
			return false;
		}
		
		$photo_id = (int) $data['photo_id'];
		
		// Here you could integrate with external image moderation services
		// For now, we'll do basic checks
		
		global $wpdb;
		$photos_table = $wpdb->prefix . 'wpmf_photos';
		
		$photo = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$photos_table} WHERE id = %d", $photo_id ),
			ARRAY_A
		);
		
		if ( ! $photo ) {
			return false;
		}
		
		// Basic automated checks (placeholder for real moderation logic)
		$status = 'approved'; // Default to approved for now
		$moderation_notes = 'Automatically processed';
		
		// Update photo status
		$result = $wpdb->update(
			$photos_table,
			array(
				'status'           => $status,
				'moderation_notes' => $moderation_notes,
			),
			array( 'id' => $photo_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		
		if ( $result && $status === 'approved' ) {
			// Clear profile cache since photo status changed
			WPMF_Cache::delete_profile( $photo['user_id'] );
		}
		
		return $result !== false;
	}

	/**
	 * Generate compatibility scores in background.
	 *
	 * @param array $data User data.
	 * @return bool Success status.
	 * @since 0.1.0
	 */
	private static function generate_compatibility_scores( $data ) {
		if ( empty( $data['user_id'] ) ) {
			return false;
		}
		
		$user_id = (int) $data['user_id'];
		
		// This is a placeholder for advanced matching algorithm
		// In a real implementation, you might calculate compatibility based on:
		// - Age preferences
		// - Location proximity
		// - Shared interests
		// - Activity patterns
		
		global $wpdb;
		$profiles_table = $wpdb->prefix . 'wpmf_profiles';
		
		$user_profile = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$profiles_table} WHERE user_id = %d", $user_id ),
			ARRAY_A
		);
		
		if ( ! $user_profile ) {
			return false;
		}
		
		// Get other active profiles in the same region
		$potential_matches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$profiles_table} 
				WHERE status = 'active' 
				AND user_id != %d 
				AND region = %s 
				LIMIT 100",
				$user_id,
				$user_profile['region']
			),
			ARRAY_A
		);
		
		$compatibility_scores = array();
		
		foreach ( $potential_matches as $match ) {
			$score = self::calculate_compatibility_score( $user_profile, $match );
			if ( $score > 0 ) {
				$compatibility_scores[ $match['user_id'] ] = $score;
			}
		}
		
		// Cache compatibility scores
		wp_cache_set( 
			"compatibility_scores_{$user_id}", 
			$compatibility_scores, 
			'wpmatch_compatibility', 
			3600 * 24 
		); // Cache for 24 hours
		
		return true;
	}

	/**
	 * Calculate compatibility score between two profiles.
	 *
	 * @param array $profile1 First profile.
	 * @param array $profile2 Second profile.
	 * @return int Compatibility score (0-100).
	 * @since 0.1.0
	 */
	private static function calculate_compatibility_score( $profile1, $profile2 ) {
		$score = 0;
		
		// Same region: +30 points
		if ( $profile1['region'] === $profile2['region'] ) {
			$score += 30;
		}
		
		// Age compatibility: up to 25 points
		if ( ! empty( $profile1['age'] ) && ! empty( $profile2['age'] ) ) {
			$age_diff = abs( $profile1['age'] - $profile2['age'] );
			if ( $age_diff <= 5 ) {
				$score += 25;
			} elseif ( $age_diff <= 10 ) {
				$score += 15;
			} elseif ( $age_diff <= 15 ) {
				$score += 10;
			}
		}
		
		// Both verified: +20 points
		if ( ! empty( $profile1['verified'] ) && ! empty( $profile2['verified'] ) ) {
			$score += 20;
		}
		
		// Both have headlines: +15 points
		if ( ! empty( $profile1['headline'] ) && ! empty( $profile2['headline'] ) ) {
			$score += 15;
		}
		
		// Both have bios: +10 points
		if ( ! empty( $profile1['bio'] ) && ! empty( $profile2['bio'] ) ) {
			$score += 10;
		}
		
		return min( 100, $score );
	}

	/**
	 * Cleanup expired data in background.
	 *
	 * @param array $data Cleanup parameters.
	 * @return bool Success status.
	 * @since 0.1.0
	 */
	private static function cleanup_expired_data( $data ) {
		$days_old = isset( $data['days_old'] ) ? absint( $data['days_old'] ) : 90;
		
		return WPMF_DB_Optimization::cleanup_old_data( $days_old );
	}

	/**
	 * Create async tasks table if it doesn't exist.
	 *
	 * @since 0.1.0
	 */
	private static function maybe_create_tasks_table() {
		global $wpdb;
		
		$table = $wpdb->prefix . 'wpmf_async_tasks';
		
		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table 
		) );
		
		if ( $table_exists ) {
			return;
		}
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			action VARCHAR(50) NOT NULL,
			data LONGTEXT NULL,
			priority TINYINT UNSIGNED NOT NULL DEFAULT 10,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
			error_message TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_status_priority (status, priority, created_at),
			KEY idx_created_at (created_at)
		) {$charset_collate}";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get async task statistics.
	 *
	 * @return array Task statistics.
	 * @since 0.1.0
	 */
	public static function get_task_stats() {
		global $wpdb;
		
		$table = $wpdb->prefix . 'wpmf_async_tasks';
		
		$stats = array(
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
			'total'      => 0,
		);
		
		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
			ARRAY_A
		);
		
		foreach ( $results as $result ) {
			$stats[ $result['status'] ] = (int) $result['count'];
			$stats['total'] += (int) $result['count'];
		}
		
		return $stats;
	}

	/**
	 * Cleanup completed and failed tasks older than specified days.
	 *
	 * @param int $days_old Delete tasks older than this many days.
	 * @return int Number of tasks deleted.
	 * @since 0.1.0
	 */
	public static function cleanup_old_tasks( $days_old = 7 ) {
		global $wpdb;
		
		$table  = $wpdb->prefix . 'wpmf_async_tasks';
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );
		
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} 
				WHERE status IN ('completed', 'failed') 
				AND updated_at < %s",
				$cutoff
			)
		);
	}
}

/**
 * Lazy loading helper class.
 *
 * @since 0.1.0
 */
class WPMF_Lazy_Loading {

	/**
	 * Register lazy loading hooks.
	 *
	 * @since 0.1.0
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wpmf_lazy_load_profiles', array( self::class, 'ajax_load_profiles' ) );
		add_action( 'wp_ajax_nopriv_wpmf_lazy_load_profiles', array( self::class, 'ajax_load_profiles' ) );
	}

	/**
	 * Enqueue lazy loading scripts.
	 *
	 * @since 0.1.0
	 */
	public static function enqueue_scripts() {
		wp_enqueue_script( 'wpmf-lazy-loading', plugins_url( 'assets/lazy-loading.js', dirname( __FILE__ ) ), array( 'jquery' ), WPMATCH_FREE_VERSION, true );
		
		wp_localize_script( 'wpmf-lazy-loading', 'wpmf_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wpmf_lazy_loading' ),
		) );
	}

	/**
	 * AJAX handler for lazy loading profiles.
	 *
	 * @since 0.1.0
	 */
	public static function ajax_load_profiles() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wpmf_lazy_loading' ) ) {
			wp_die( 'Security check failed' );
		}
		
		$offset = absint( $_POST['offset'] ?? 0 );
		$limit  = min( 20, absint( $_POST['limit'] ?? 10 ) );
		
		$filters = array();
		$allowed_filters = array( 'region', 'age_min', 'age_max', 'verified', 'has_photo' );
		foreach ( $allowed_filters as $filter ) {
			if ( ! empty( $_POST[ $filter ] ) ) {
				$filters[ $filter ] = sanitize_text_field( $_POST[ $filter ] );
			}
		}
		
		$user_id = get_current_user_id();
		$profiles = WPMF_DB_Optimization::optimized_search( $filters, $user_id, $limit, $offset );
		
		$html = '';
		foreach ( $profiles as $profile ) {
			$html .= '<div class="wpmf-card wpmf-lazy-loaded" data-user-id="' . esc_attr( $profile['user_id'] ) . '">';
			$html .= '<div class="wpmf-card-headline">' . esc_html( $profile['headline'] ?? '' ) . '</div>';
			$html .= '<div class="wpmf-card-region">' . esc_html( $profile['region'] ?? '' ) . '</div>';
			if ( ! empty( $profile['age'] ) ) {
				$html .= '<div class="wpmf-card-age">' . esc_html( $profile['age'] ) . ' years old</div>';
			}
			$html .= '</div>';
		}
		
		wp_send_json_success( array(
			'html'      => $html,
			'has_more'  => count( $profiles ) === $limit,
			'new_offset' => $offset + count( $profiles ),
		) );
	}
}

// Initialize async processing
add_action( 'wpmf_process_async_tasks', array( 'WPMF_Async_Processing', 'process_tasks' ) );

// Schedule regular task processing
if ( ! wp_next_scheduled( 'wpmf_process_async_tasks' ) ) {
	wp_schedule_event( time(), 'hourly', 'wpmf_process_async_tasks' );
}

// Initialize lazy loading
WPMF_Lazy_Loading::init();

// Clean up old async tasks weekly
add_action( 'wpmf_cleanup_old_data', array( 'WPMF_Async_Processing', 'cleanup_old_tasks' ) );

/**
 * Queue user stats update when profile is viewed.
 *
 * @param int $user_id User ID.
 * @since 0.1.0
 */
function wpmf_queue_stats_update( $user_id ) {
	WPMF_Async_Processing::queue_task( 'update_user_stats', array( 'user_id' => $user_id ), 8 );
}

/**
 * Queue photo moderation when photo is uploaded.
 *
 * @param int $photo_id Photo ID.
 * @since 0.1.0
 */
function wpmf_queue_photo_moderation( $photo_id ) {
	WPMF_Async_Processing::queue_task( 'process_photo_moderation', array( 'photo_id' => $photo_id ), 5 );
}

/**
 * Queue compatibility score generation when profile is updated.
 *
 * @param int $profile_id Profile ID.
 * @param int $user_id User ID.
 * @since 0.1.0
 */
function wpmf_queue_compatibility_update( $profile_id, $user_id ) {
	WPMF_Async_Processing::queue_task( 'generate_compatibility_scores', array( 'user_id' => $user_id ), 15 );
}
add_action( 'wpmf_profile_created', 'wpmf_queue_compatibility_update', 10, 2 );
add_action( 'wpmf_profile_updated', 'wpmf_queue_compatibility_update', 10, 2 );