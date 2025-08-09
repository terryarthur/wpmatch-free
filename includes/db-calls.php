<?php
/**
 * Database functions for WebRTC call management
 *
 * @package WPMatch_Free
 * @subpackage Includes
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create a new call record
 *
 * @param int    $caller_id      The ID of the user initiating the call
 * @param int    $recipient_id   The ID of the user being called
 * @param string $call_type      Type of call ('audio' or 'video')
 * @param string $call_id        Unique call identifier (UUID)
 * @return int|false Call ID on success, false on failure
 */
function wpmf_create_call( $caller_id, $recipient_id, $call_type = 'video', $call_id = null ) {
    global $wpdb;
    
    // Validate inputs
    if ( empty( $caller_id ) || empty( $recipient_id ) || $caller_id === $recipient_id ) {
        return false;
    }
    
    // Validate call type
    $valid_types = array( 'audio', 'video' );
    if ( ! in_array( $call_type, $valid_types, true ) ) {
        $call_type = 'video';
    }
    
    // Generate call ID if not provided
    if ( empty( $call_id ) ) {
        $call_id = wp_generate_uuid4();
    }
    
    // Check if users exist and are not blocked
    if ( ! wpmf_can_users_communicate( $caller_id, $recipient_id ) ) {
        return false;
    }
    
    // Check for existing pending call between these users
    $existing_call = $wpdb->get_row( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}wpmf_calls 
         WHERE (caller_id = %d AND recipient_id = %d) 
            OR (caller_id = %d AND recipient_id = %d)
         AND status = 'pending' 
         AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
        $caller_id, $recipient_id, $recipient_id, $caller_id
    ) );
    
    if ( $existing_call ) {
        return false; // Prevent duplicate calls
    }
    
    $current_time = current_time( 'mysql' );
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'wpmf_calls',
        array(
            'call_id'      => $call_id,
            'caller_id'    => $caller_id,
            'recipient_id' => $recipient_id,
            'call_type'    => $call_type,
            'status'       => 'pending',
            'created_at'   => $current_time,
            'updated_at'   => $current_time,
        ),
        array(
            '%s', // call_id
            '%d', // caller_id
            '%d', // recipient_id
            '%s', // call_type
            '%s', // status
            '%s', // created_at
            '%s', // updated_at
        )
    );
    
    if ( $result ) {
        $call_db_id = $wpdb->insert_id;
        
        // Fire action hook for call created
        do_action( 'wpmf_call_created', $call_db_id, $call_id, $caller_id, $recipient_id, $call_type );
        
        return $call_db_id;
    }
    
    return false;
}

/**
 * Update call status
 *
 * @param string $call_id   Unique call identifier
 * @param string $status    New status ('pending', 'ringing', 'active', 'ended', 'cancelled', 'declined', 'missed')
 * @param string $end_reason Optional reason for ending call
 * @return bool Success status
 */
function wpmf_update_call_status( $call_id, $status, $end_reason = null ) {
    global $wpdb;
    
    if ( empty( $call_id ) || empty( $status ) ) {
        return false;
    }
    
    $valid_statuses = array( 'pending', 'ringing', 'active', 'ended', 'cancelled', 'declined', 'missed' );
    if ( ! in_array( $status, $valid_statuses, true ) ) {
        return false;
    }
    
    $update_data = array(
        'status'     => $status,
        'updated_at' => current_time( 'mysql' ),
    );
    
    $update_format = array( '%s', '%s' );
    
    // Handle status-specific updates
    if ( $status === 'active' ) {
        $update_data['started_at'] = current_time( 'mysql' );
        $update_format[] = '%s';
    } elseif ( in_array( $status, array( 'ended', 'cancelled', 'declined', 'missed' ), true ) ) {
        $current_call = wpmf_get_call_by_id( $call_id );
        if ( $current_call && $current_call->started_at ) {
            $update_data['ended_at'] = current_time( 'mysql' );
            $update_data['duration_seconds'] = strtotime( $update_data['ended_at'] ) - strtotime( $current_call->started_at );
            $update_format[] = '%s';
            $update_format[] = '%d';
        } else {
            $update_data['ended_at'] = current_time( 'mysql' );
            $update_format[] = '%s';
        }
        
        if ( $end_reason ) {
            $update_data['end_reason'] = $end_reason;
            $update_format[] = '%s';
        }
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'wpmf_calls',
        $update_data,
        array( 'call_id' => $call_id ),
        $update_format,
        array( '%s' )
    );
    
    if ( $result !== false ) {
        // Fire action hook for status change
        do_action( 'wpmf_call_status_changed', $call_id, $status, $end_reason );
        return true;
    }
    
    return false;
}

/**
 * Get call by call ID
 *
 * @param string $call_id Unique call identifier
 * @return object|null Call object or null if not found
 */
function wpmf_get_call_by_id( $call_id ) {
    global $wpdb;
    
    if ( empty( $call_id ) ) {
        return null;
    }
    
    $call = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpmf_calls WHERE call_id = %s",
        $call_id
    ) );
    
    return $call ? $call : null;
}

/**
 * Get call history for a user
 *
 * @param int $user_id      User ID
 * @param int $limit        Number of calls to retrieve (default: 20)
 * @param int $offset       Offset for pagination (default: 0)
 * @param string $type      Filter by call type ('all', 'audio', 'video')
 * @return array Array of call objects
 */
function wpmf_get_user_call_history( $user_id, $limit = 20, $offset = 0, $type = 'all' ) {
    global $wpdb;
    
    if ( empty( $user_id ) ) {
        return array();
    }
    
    $where_conditions = array( '(caller_id = %d OR recipient_id = %d)' );
    $where_values = array( $user_id, $user_id );
    
    if ( $type !== 'all' && in_array( $type, array( 'audio', 'video' ), true ) ) {
        $where_conditions[] = 'call_type = %s';
        $where_values[] = $type;
    }
    
    $where_clause = implode( ' AND ', $where_conditions );
    
    $sql = $wpdb->prepare(
        "SELECT c.*, 
                CASE 
                    WHEN c.caller_id = %d THEN 'outgoing'
                    ELSE 'incoming'
                END as call_direction,
                caller.display_name as caller_name,
                recipient.display_name as recipient_name
         FROM {$wpdb->prefix}wpmf_calls c
         LEFT JOIN {$wpdb->users} caller ON c.caller_id = caller.ID
         LEFT JOIN {$wpdb->users} recipient ON c.recipient_id = recipient.ID
         WHERE {$where_clause}
         ORDER BY c.created_at DESC
         LIMIT %d OFFSET %d",
        array_merge( array( $user_id ), $where_values, array( $limit, $offset ) )
    );
    
    $calls = $wpdb->get_results( $sql );
    
    return $calls ? $calls : array();
}

/**
 * Get active calls for a user
 *
 * @param int $user_id User ID
 * @return array Array of active call objects
 */
function wpmf_get_user_active_calls( $user_id ) {
    global $wpdb;
    
    if ( empty( $user_id ) ) {
        return array();
    }
    
    $calls = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.*, 
                caller.display_name as caller_name,
                recipient.display_name as recipient_name
         FROM {$wpdb->prefix}wpmf_calls c
         LEFT JOIN {$wpdb->users} caller ON c.caller_id = caller.ID
         LEFT JOIN {$wpdb->users} recipient ON c.recipient_id = recipient.ID
         WHERE (caller_id = %d OR recipient_id = %d) 
         AND status IN ('pending', 'ringing', 'active')
         ORDER BY c.created_at DESC",
        $user_id, $user_id
    ) );
    
    return $calls ? $calls : array();
}

/**
 * Update call signaling data
 *
 * @param string $call_id        Unique call identifier
 * @param array  $signaling_data Signaling data (ICE candidates, SDP offers, etc.)
 * @return bool Success status
 */
function wpmf_update_call_signaling( $call_id, $signaling_data ) {
    global $wpdb;
    
    if ( empty( $call_id ) || ! is_array( $signaling_data ) ) {
        return false;
    }
    
    // Get existing signaling data
    $current_call = wpmf_get_call_by_id( $call_id );
    if ( ! $current_call ) {
        return false;
    }
    
    $existing_data = array();
    if ( ! empty( $current_call->signaling_data ) ) {
        $existing_data = json_decode( $current_call->signaling_data, true );
        if ( ! is_array( $existing_data ) ) {
            $existing_data = array();
        }
    }
    
    // Merge new data with existing
    $merged_data = array_merge( $existing_data, $signaling_data );
    
    $result = $wpdb->update(
        $wpdb->prefix . 'wpmf_calls',
        array(
            'signaling_data' => wp_json_encode( $merged_data ),
            'updated_at'     => current_time( 'mysql' ),
        ),
        array( 'call_id' => $call_id ),
        array( '%s', '%s' ),
        array( '%s' )
    );
    
    return $result !== false;
}

/**
 * Get call statistics for a user
 *
 * @param int $user_id User ID
 * @param int $days    Number of days to look back (default: 30)
 * @return array Statistics array
 */
function wpmf_get_user_call_stats( $user_id, $days = 30 ) {
    global $wpdb;
    
    if ( empty( $user_id ) ) {
        return array();
    }
    
    $stats = $wpdb->get_row( $wpdb->prepare(
        "SELECT 
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END) as completed_calls,
            SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed_calls,
            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_calls,
            SUM(CASE WHEN caller_id = %d THEN 1 ELSE 0 END) as outgoing_calls,
            SUM(CASE WHEN recipient_id = %d THEN 1 ELSE 0 END) as incoming_calls,
            AVG(CASE WHEN duration_seconds > 0 THEN duration_seconds ELSE NULL END) as avg_duration,
            SUM(CASE WHEN duration_seconds > 0 THEN duration_seconds ELSE 0 END) as total_duration
         FROM {$wpdb->prefix}wpmf_calls 
         WHERE (caller_id = %d OR recipient_id = %d) 
         AND created_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
        $user_id, $user_id, $user_id, $user_id, $days
    ) );
    
    return $stats ? (array) $stats : array();
}

/**
 * Clean up old call records
 *
 * @param int $days_old Number of days to keep records (default: 90)
 * @return int Number of records deleted
 */
function wpmf_cleanup_old_calls( $days_old = 90 ) {
    global $wpdb;
    
    $deleted = $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}wpmf_calls 
         WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY) 
         AND status IN ('ended', 'cancelled', 'declined', 'missed')",
        $days_old
    ) );
    
    return $deleted !== false ? $deleted : 0;
}

/**
 * Check if two users can communicate (not blocked)
 *
 * @param int $user1_id First user ID
 * @param int $user2_id Second user ID
 * @return bool True if users can communicate
 */
function wpmf_can_users_communicate( $user1_id, $user2_id ) {
    // Check if users exist
    $user1 = get_user_by( 'id', $user1_id );
    $user2 = get_user_by( 'id', $user2_id );
    
    if ( ! $user1 || ! $user2 ) {
        return false;
    }
    
    // Check if either user has blocked the other
    if ( function_exists( 'wpmf_is_user_blocked' ) ) {
        if ( wpmf_is_user_blocked( $user1_id, $user2_id ) || wpmf_is_user_blocked( $user2_id, $user1_id ) ) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get pending calls for a user (calls waiting for response)
 *
 * @param int $user_id User ID
 * @return array Array of pending call objects
 */
function wpmf_get_user_pending_calls( $user_id ) {
    global $wpdb;
    
    if ( empty( $user_id ) ) {
        return array();
    }
    
    $calls = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.*, 
                caller.display_name as caller_name,
                caller.user_email as caller_email
         FROM {$wpdb->prefix}wpmf_calls c
         LEFT JOIN {$wpdb->users} caller ON c.caller_id = caller.ID
         WHERE c.recipient_id = %d 
         AND c.status IN ('pending', 'ringing')
         AND c.created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
         ORDER BY c.created_at DESC",
        $user_id
    ) );
    
    return $calls ? $calls : array();
}

/**
 * Cancel expired calls
 *
 * @param int $timeout_minutes Minutes after which pending calls expire (default: 2)
 * @return int Number of calls cancelled
 */
function wpmf_cancel_expired_calls( $timeout_minutes = 2 ) {
    global $wpdb;
    
    $cancelled = $wpdb->update(
        $wpdb->prefix . 'wpmf_calls',
        array(
            'status'     => 'missed',
            'end_reason' => 'timeout',
            'ended_at'   => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ),
        array( 'status' => 'pending' ),
        array( '%s', '%s', '%s', '%s' ),
        array( '%s' )
    );
    
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}wpmf_calls 
         SET status = 'missed', end_reason = 'timeout', ended_at = NOW(), updated_at = NOW()
         WHERE status IN ('pending', 'ringing') 
         AND created_at < DATE_SUB(NOW(), INTERVAL %d MINUTE)",
        $timeout_minutes
    ) );
    
    return $cancelled !== false ? $cancelled : 0;
}