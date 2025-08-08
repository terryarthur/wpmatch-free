<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

function wpmf_thread_create() {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_threads';
	$ins = array(
		'created_at' => current_time( 'mysql' ),
		'updated_at' => current_time( 'mysql' ),
	);
	$ok  = $wpdb->insert( $t, $ins, array( '%s', '%s' ) );
	return $ok ? (int) $wpdb->insert_id : 0;
}

function wpmf_message_send( int $thread_id, int $sender_id, int $recipient_id, string $body ) {
	global $wpdb;
	$bt      = $wpdb->prefix . 'wpmf_blocks';
	$blocked = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bt} WHERE (actor_id=%d AND target_user_id=%d) OR (actor_id=%d AND target_user_id=%d)", $recipient_id, $sender_id, $sender_id, $recipient_id ) );
	if ( $blocked ) {
		return 0; }
	$blacklist = get_option( 'wpmf_word_filter', '' );
	if ( $blacklist ) {
		$words = array_filter( array_map( 'trim', explode( ',', $blacklist ) ) );
		foreach ( $words as $w ) {
			if ( $w !== '' && stripos( $body, $w ) !== false ) {
				return 0; }
		}
	}
	$limit = (int) get_option( 'wpmf_messages_per_day', 20 );
	if ( $limit > 0 ) {
		$mt    = $wpdb->prefix . 'wpmf_messages';
		$since = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$mt} WHERE sender_id=%d AND created_at >= %s", $sender_id, $since ) );
		if ( $count >= $limit ) {
			return 0; }
	}
	$t   = $wpdb->prefix . 'wpmf_messages';
	$ins = array(
		'thread_id'    => $thread_id,
		'sender_id'    => $sender_id,
		'recipient_id' => $recipient_id,
		'body'         => wp_kses_post( $body ),
		'status'       => 'sent',
		'created_at'   => current_time( 'mysql' ),
	);
	$ok  = $wpdb->insert( $t, $ins, array( '%d', '%d', '%d', '%s', '%s', '%s' ) );
	if ( $ok ) {
		$wpdb->update( $wpdb->prefix . 'wpmf_threads', array( 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $thread_id ), array( '%s' ), array( '%d' ) );
	}
	return $ok ? (int) $wpdb->insert_id : 0;
}

function wpmf_thread_messages( int $thread_id, int $limit = 50, int $offset = 0 ) {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_messages';
	$sql = $wpdb->prepare( "SELECT * FROM {$t} WHERE thread_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $thread_id, $limit, $offset );
	return $wpdb->get_results( $sql, ARRAY_A );
}
