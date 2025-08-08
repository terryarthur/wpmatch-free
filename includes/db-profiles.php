<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Get dating profile by WordPress user ID.
 *
 * @param int  $user_id WordPress user ID.
 * @param bool $force_refresh Skip cache and fetch from database.
 * @return array|null Profile data or null if not found.
 * @since 0.1.0
 */
function wpmf_profile_get_by_user_id( int $user_id, bool $force_refresh = false ) {
	if ( ! $force_refresh ) {
		$cached = WPMF_Cache::get_profile( $user_id );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	global $wpdb;
	$t      = $wpdb->prefix . 'wpmf_profiles';
	$sql    = $wpdb->prepare( "SELECT * FROM {$t} WHERE user_id=%d", $user_id );
	$result = $wpdb->get_row( $sql, ARRAY_A );
	
	// Cache the result (even if null/empty)
	if ( $result ) {
		WPMF_Cache::set_profile( $user_id, $result );
	}
	
	return $result;
}

/**
 * Create a new dating profile.
 *
 * @param array $data Profile data.
 * @return int Profile ID on success, 0 on failure.
 * @since 0.1.0
 */
function wpmf_profile_create( array $data ) {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_profiles';
	$now = current_time( 'mysql' );
	$ins = array(
		'user_id'     => (int) $data['user_id'],
		'status'      => sanitize_text_field( $data['status'] ?? 'active' ),
		'visibility'  => sanitize_text_field( $data['visibility'] ?? 'members' ),
		'gender'      => isset( $data['gender'] ) ? sanitize_text_field( $data['gender'] ) : null,
		'orientation' => isset( $data['orientation'] ) ? sanitize_text_field( $data['orientation'] ) : null,
		'age'         => isset( $data['age'] ) ? absint( $data['age'] ) : null,
		'region'      => isset( $data['region'] ) ? sanitize_text_field( $data['region'] ) : null,
		'headline'    => isset( $data['headline'] ) ? sanitize_text_field( $data['headline'] ) : null,
		'bio'         => isset( $data['bio'] ) ? wp_kses_post( $data['bio'] ) : null,
		'verified'    => ! empty( $data['verified'] ) ? 1 : 0,
		'last_active' => $now,
		'created_at'  => $now,
		'updated_at'  => $now,
	);
	$f   = array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' );
	$ok  = $wpdb->insert( $t, $ins, $f );
	
	if ( $ok ) {
		$profile_id = (int) $wpdb->insert_id;
		
		// Trigger action hook for cache invalidation
		do_action( 'wpmf_profile_created', $profile_id, (int) $data['user_id'] );
		
		return $profile_id;
	}
	
	return 0;
}

/**
 * Update dating profile by user ID.
 *
 * @param int   $user_id User ID.
 * @param array $data Profile data to update.
 * @return int|false Number of rows updated, false on error.
 * @since 0.1.0
 */
function wpmf_profile_update_by_user_id( int $user_id, array $data ) {
	global $wpdb;
	$t      = $wpdb->prefix . 'wpmf_profiles';
	$upd    = array();
	$fmt    = array();
	$fields = array( 'status', 'visibility', 'gender', 'orientation', 'age', 'region', 'headline', 'bio', 'verified', 'last_active' );
	foreach ( $fields as $key ) {
		if ( array_key_exists( $key, $data ) ) {
			$val = $data[ $key ];
			if ( $key === 'age' ) {
				$val   = absint( $val );
				$fmt[] = '%d'; } elseif ( $key === 'verified' ) {
				$val   = $val ? 1 : 0;
				$fmt[] = '%d'; } elseif ( $key === 'bio' ) {
					$val   = wp_kses_post( $val );
					$fmt[] = '%s'; } else {
					$val   = is_null( $val ) ? null : sanitize_text_field( $val );
					$fmt[] = '%s'; }
					$upd[ $key ] = $val;
		}
	}
	$upd['updated_at'] = current_time( 'mysql' );
	$fmt[]             = '%s';
	$where             = array( 'user_id' => $user_id );
	
	// Get old data for hook
	$old_data = wpmf_profile_get_by_user_id( $user_id, true );
	
	$result = $wpdb->update( $t, $upd, $where, $fmt, array( '%d' ) );
	
	if ( $result && $old_data ) {
		// Trigger action hook for cache invalidation
		do_action( 'wpmf_profile_updated', $old_data['id'], $user_id, $old_data, $upd );
	}
	
	return $result;
}

/**
 * Delete dating profile by user ID.
 *
 * @param int $user_id User ID.
 * @return int|false Number of rows deleted, false on error.
 * @since 0.1.0
 */
function wpmf_profile_delete_by_user_id( int $user_id ) {
	global $wpdb;
	$t = $wpdb->prefix . 'wpmf_profiles';
	return $wpdb->delete( $t, array( 'user_id' => $user_id ), array( '%d' ) );
}
