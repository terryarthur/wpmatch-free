<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

function wpmf_like_toggle( int $actor_id, int $target_user_id ) {
	global $wpdb;
	$t        = $wpdb->prefix . 'wpmf_likes';
	$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE actor_id=%d AND target_user_id=%d", $actor_id, $target_user_id ) );
	if ( $existing ) {
		$wpdb->delete( $t, array( 'id' => $existing ), array( '%d' ) );
		return false;
	}
	$limit = (int) get_option( 'wpmf_likes_per_day', 50 );
	if ( $limit > 0 ) {
		$since = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE actor_id=%d AND created_at >= %s", $actor_id, $since ) );
		if ( $count >= $limit ) {
			return false; }
	}
	$ins = array(
		'actor_id'       => $actor_id,
		'target_user_id' => $target_user_id,
		'created_at'     => current_time( 'mysql' ),
	);
	$ok  = $wpdb->insert( $t, $ins, array( '%d', '%d', '%s' ) );
	return (bool) $ok;
}

function wpmf_likes_for_user( int $user_id, int $limit = 50, int $offset = 0 ) {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_likes';
	$sql = $wpdb->prepare( "SELECT * FROM {$t} WHERE target_user_id=%d ORDER BY id DESC LIMIT %d OFFSET %d", $user_id, $limit, $offset );
	return $wpdb->get_results( $sql, ARRAY_A );
}
