<?php
/**
 * WP Match Free - Messaging Database Functions
 *
 * Comprehensive messaging system with real-time capabilities,
 * thread management, and security features.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create a new conversation thread.
 *
 * @return int Thread ID on success, 0 on failure.
 * @since 0.1.0
 */
function wpmf_thread_create() {
	global $wpdb;
	$table = $wpdb->prefix . 'wpmf_threads';

	$data = array(
		'created_at' => current_time( 'mysql' ),
		'updated_at' => current_time( 'mysql' ),
	);

	$result = $wpdb->insert( $table, $data, array( '%s', '%s' ) );
	return $result ? (int) $wpdb->insert_id : 0;
}

/**
 * Get or create a thread between two users.
 *
 * @param int $user1_id First user ID.
 * @param int $user2_id Second user ID.
 * @return int Thread ID.
 * @since 0.1.0
 */
function wpmf_thread_get_or_create( int $user1_id, int $user2_id ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';

	// Find existing thread between these users
	$thread_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT DISTINCT thread_id FROM {$messages_table} 
			WHERE (sender_id = %d AND recipient_id = %d) 
			   OR (sender_id = %d AND recipient_id = %d) 
			ORDER BY thread_id DESC LIMIT 1",
			$user1_id,
			$user2_id,
			$user2_id,
			$user1_id
		)
	);

	if ( $thread_id ) {
		return (int) $thread_id;
	}

	// Create new thread if none exists
	return wpmf_thread_create();
}

/**
 * Send a message with comprehensive security and validation.
 *
 * @param int    $thread_id    Thread ID.
 * @param int    $sender_id    Sender user ID.
 * @param int    $recipient_id Recipient user ID.
 * @param string $body         Message content.
 * @return array Result array with success status and message ID.
 * @since 0.1.0
 */
function wpmf_message_send( int $thread_id, int $sender_id, int $recipient_id, string $body ) {
	global $wpdb;

	// Validate input
	if ( empty( trim( $body ) ) ) {
		return array(
			'success' => false,
			'message' => __( 'Message cannot be empty.', 'wpmatch-free' ),
		);
	}

	if ( $sender_id === $recipient_id ) {
		return array(
			'success' => false,
			'message' => __( 'You cannot send messages to yourself.', 'wpmatch-free' ),
		);
	}

	// Check if users are blocked
	$blocks_table = $wpdb->prefix . 'wpmf_blocks';
	$blocked      = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$blocks_table} 
			WHERE (actor_id = %d AND target_user_id = %d) 
			   OR (actor_id = %d AND target_user_id = %d)",
			$recipient_id,
			$sender_id,
			$sender_id,
			$recipient_id
		)
	);

	if ( $blocked ) {
		return array(
			'success' => false,
			'message' => __( 'Message could not be sent.', 'wpmatch-free' ),
		);
	}

	// Check word filter
	$blacklist = get_option( 'wpmf_word_filter', '' );
	if ( $blacklist ) {
		$words = array_filter( array_map( 'trim', explode( ',', $blacklist ) ) );
		foreach ( $words as $word ) {
			if ( ! empty( $word ) && stripos( $body, $word ) !== false ) {
				return array(
					'success' => false,
					'message' => __( 'Message contains prohibited content.', 'wpmatch-free' ),
				);
			}
		}
	}

	// Check rate limiting
	$daily_limit = apply_filters( 'wpmf_messages_per_day', (int) get_option( 'wpmf_messages_per_day', 20 ) );
	if ( $daily_limit > 0 ) {
		$messages_table = $wpdb->prefix . 'wpmf_messages';
		$since          = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$count          = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$messages_table} WHERE sender_id = %d AND created_at >= %s",
				$sender_id,
				$since
			)
		);

		if ( $count >= $daily_limit ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %d: daily message limit */
					__( 'Daily message limit of %d reached. Try again tomorrow.', 'wpmatch-free' ),
					$daily_limit
				),
			);
		}
	}

	// Sanitize message content
	$body = wp_kses_post( trim( $body ) );
	$body = apply_filters( 'wpmf_message_content', $body, $sender_id );

	// Insert message
	$messages_table = $wpdb->prefix . 'wpmf_messages';
	$data           = array(
		'thread_id'    => $thread_id,
		'sender_id'    => $sender_id,
		'recipient_id' => $recipient_id,
		'body'         => $body,
		'status'       => 'sent',
		'created_at'   => current_time( 'mysql' ),
	);

	$result = $wpdb->insert( $messages_table, $data, array( '%d', '%d', '%d', '%s', '%s', '%s' ) );

	if ( ! $result ) {
		return array(
			'success' => false,
			'message' => __( 'Failed to send message. Please try again.', 'wpmatch-free' ),
		);
	}

	$message_id = (int) $wpdb->insert_id;

	// Update thread timestamp
	$threads_table = $wpdb->prefix . 'wpmf_threads';
	$wpdb->update(
		$threads_table,
		array( 'updated_at' => current_time( 'mysql' ) ),
		array( 'id' => $thread_id ),
		array( '%s' ),
		array( '%d' )
	);

	// Fire action for real-time notifications
	do_action( 'wpmf_message_sent', $message_id, $sender_id, $recipient_id, $thread_id );

	return array(
		'success'    => true,
		'message'    => __( 'Message sent successfully.', 'wpmatch-free' ),
		'message_id' => $message_id,
	);
}

/**
 * Get messages for a thread with user access verification.
 *
 * @param int $thread_id Thread ID.
 * @param int $user_id   User ID requesting the messages.
 * @param int $limit     Maximum number of messages to retrieve.
 * @param int $offset    Offset for pagination.
 * @return array Messages or empty array if no access.
 * @since 0.1.0
 */
function wpmf_thread_messages( int $thread_id, int $user_id = 0, int $limit = 50, int $offset = 0 ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';

	// If user_id is provided, verify access
	if ( $user_id > 0 ) {
		$access_check = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$messages_table} 
				WHERE thread_id = %d AND (sender_id = %d OR recipient_id = %d)",
				$thread_id,
				$user_id,
				$user_id
			)
		);

		if ( ! $access_check ) {
			return array();
		}
	}

	$sql = $wpdb->prepare(
		"SELECT * FROM {$messages_table} 
		WHERE thread_id = %d 
		ORDER BY id ASC 
		LIMIT %d OFFSET %d",
		$thread_id,
		$limit,
		$offset
	);

	return $wpdb->get_results( $sql, ARRAY_A );
}

/**
 * Get user's conversation list with latest message preview.
 *
 * @param int $user_id User ID.
 * @param int $limit   Maximum conversations to retrieve.
 * @param int $offset  Offset for pagination.
 * @return array Array of conversations with metadata.
 * @since 0.1.0
 */
function wpmf_user_conversations( int $user_id, int $limit = 20, int $offset = 0 ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';
	$threads_table  = $wpdb->prefix . 'wpmf_threads';

	$sql = $wpdb->prepare(
		"SELECT t.id as thread_id, t.updated_at,
		        CASE WHEN m.sender_id = %d THEN m.recipient_id ELSE m.sender_id END as other_user_id,
		        m.body as last_message,
		        m.created_at as last_message_time,
		        m.sender_id as last_sender_id,
		        (SELECT COUNT(*) FROM {$messages_table} m2 
		         WHERE m2.thread_id = t.id 
		           AND m2.recipient_id = %d 
		           AND m2.status != 'read') as unread_count
		FROM {$threads_table} t
		JOIN {$messages_table} m ON t.id = m.thread_id
		WHERE (m.sender_id = %d OR m.recipient_id = %d)
		  AND m.id = (SELECT MAX(id) FROM {$messages_table} m3 WHERE m3.thread_id = t.id)
		ORDER BY t.updated_at DESC
		LIMIT %d OFFSET %d",
		$user_id,
		$user_id,
		$user_id,
		$user_id,
		$limit,
		$offset
	);

	return $wpdb->get_results( $sql, ARRAY_A );
}

/**
 * Mark messages as read.
 *
 * @param int $thread_id Thread ID.
 * @param int $user_id   User ID marking messages as read.
 * @return bool Success status.
 * @since 0.1.0
 */
function wpmf_messages_mark_read( int $thread_id, int $user_id ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';

	$result = $wpdb->update(
		$messages_table,
		array( 'status' => 'read' ),
		array(
			'thread_id'    => $thread_id,
			'recipient_id' => $user_id,
		),
		array( '%s' ),
		array( '%d', '%d' )
	);

	if ( $result ) {
		do_action( 'wpmf_messages_marked_read', $thread_id, $user_id );
	}

	return (bool) $result;
}

/**
 * Get total unread message count for a user.
 *
 * @param int $user_id User ID.
 * @return int Unread message count.
 * @since 0.1.0
 */
function wpmf_user_unread_count( int $user_id ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$messages_table} 
			WHERE recipient_id = %d AND status != 'read'",
			$user_id
		)
	);

	return (int) $count;
}

/**
 * Delete a message (soft delete by updating status).
 *
 * @param int $message_id Message ID.
 * @param int $user_id    User ID requesting deletion.
 * @return bool Success status.
 * @since 0.1.0
 */
function wpmf_message_delete( int $message_id, int $user_id ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';

	// Verify user owns the message
	$message = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$messages_table} WHERE id = %d AND sender_id = %d",
			$message_id,
			$user_id
		),
		ARRAY_A
	);

	if ( ! $message ) {
		return false;
	}

	// Update status to deleted
	$result = $wpdb->update(
		$messages_table,
		array( 'status' => 'deleted' ),
		array( 'id' => $message_id ),
		array( '%s' ),
		array( '%d' )
	);

	if ( $result ) {
		do_action( 'wpmf_message_deleted', $message_id, $user_id );
	}

	return (bool) $result;
}

/**
 * Search messages for a user.
 *
 * @param int    $user_id User ID.
 * @param string $query   Search query.
 * @param int    $limit   Maximum results.
 * @return array Search results.
 * @since 0.1.0
 */
function wpmf_messages_search( int $user_id, string $query, int $limit = 50 ) {
	global $wpdb;
	$messages_table = $wpdb->prefix . 'wpmf_messages';

	$search_term = '%' . $wpdb->esc_like( $query ) . '%';

	$sql = $wpdb->prepare(
		"SELECT m.*, 
		        CASE WHEN m.sender_id = %d THEN m.recipient_id ELSE m.sender_id END as other_user_id
		FROM {$messages_table} m
		WHERE (m.sender_id = %d OR m.recipient_id = %d)
		  AND m.body LIKE %s
		  AND m.status != 'deleted'
		ORDER BY m.created_at DESC
		LIMIT %d",
		$user_id,
		$user_id,
		$user_id,
		$search_term,
		$limit
	);

	return $wpdb->get_results( $sql, ARRAY_A );
}
