<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

function wpmf_photos_list_by_user( int $user_id ) {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_photos';
	$sql = $wpdb->prepare( "SELECT * FROM {$t} WHERE user_id=%d ORDER BY is_primary DESC, id DESC", $user_id );
	return $wpdb->get_results( $sql, ARRAY_A );
}

function wpmf_photo_add( int $user_id, int $attachment_id, bool $is_primary = false, string $status = 'pending' ) {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_photos';
	$ins = array(
		'user_id'       => $user_id,
		'attachment_id' => $attachment_id,
		'is_primary'    => $is_primary ? 1 : 0,
		'status'        => sanitize_text_field( $status ),
		'created_at'    => current_time( 'mysql' ),
	);
	$ok  = $wpdb->insert( $t, $ins, array( '%d', '%d', '%d', '%s', '%s' ) );
	return $ok ? (int) $wpdb->insert_id : 0;
}

function wpmf_photo_update( int $id, array $data ) {
	global $wpdb;
	$t   = $wpdb->prefix . 'wpmf_photos';
	$upd = array();
	$fmt = array();
	foreach ( array( 'is_primary', 'status', 'moderation_notes' ) as $k ) {
		if ( array_key_exists( $k, $data ) ) {
			$v = $data[ $k ];
			if ( $k === 'is_primary' ) {
				$v     = $v ? 1 : 0;
				$fmt[] = '%d'; } elseif ( $k === 'moderation_notes' ) {
				$v     = sanitize_textarea_field( $v );
				$fmt[] = '%s'; } else {
					$v     = sanitize_text_field( $v );
					$fmt[] = '%s'; }
				$upd[ $k ] = $v;
		}
	}
	return $wpdb->update( $t, $upd, array( 'id' => $id ), $fmt, array( '%d' ) );
}

function wpmf_photo_delete( int $id ) {
	global $wpdb;
	$t = $wpdb->prefix . 'wpmf_photos';
	return $wpdb->delete( $t, array( 'id' => $id ), array( '%d' ) );
}
