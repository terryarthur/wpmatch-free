<?php
/**
 * Privacy and GDPR compliance functions.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register privacy exporters and erasers.
 *
 * @since 0.1.0
 */
function wpmf_register_privacy_hooks() {
	add_filter( 'wp_privacy_personal_data_exporters', 'wpmf_register_privacy_exporters' );
	add_filter( 'wp_privacy_personal_data_erasers', 'wpmf_register_privacy_erasers' );
}
add_action( 'init', 'wpmf_register_privacy_hooks' );

/**
 * Register data exporters for personal data export tool.
 *
 * @param array $exporters Existing exporters.
 * @return array Updated exporters array.
 * @since 0.1.0
 */
function wpmf_register_privacy_exporters( $exporters ) {
	$exporters['wpmatch-free-profile']  = array(
		'exporter_friendly_name' => __( 'WP Match Profile Data', 'wpmatch-free' ),
		'callback'               => 'wpmf_export_profile_data',
	);
	$exporters['wpmatch-free-messages'] = array(
		'exporter_friendly_name' => __( 'WP Match Messages', 'wpmatch-free' ),
		'callback'               => 'wpmf_export_messages_data',
	);
	$exporters['wpmatch-free-likes']    = array(
		'exporter_friendly_name' => __( 'WP Match Likes', 'wpmatch-free' ),
		'callback'               => 'wpmf_export_likes_data',
	);
	return $exporters;
}

/**
 * Register data erasers for personal data erasure tool.
 *
 * @param array $erasers Existing erasers.
 * @return array Updated erasers array.
 * @since 0.1.0
 */
function wpmf_register_privacy_erasers( $erasers ) {
	$erasers['wpmatch-free-profile']  = array(
		'eraser_friendly_name' => __( 'WP Match Profile Data', 'wpmatch-free' ),
		'callback'             => 'wpmf_erase_profile_data',
	);
	$erasers['wpmatch-free-messages'] = array(
		'eraser_friendly_name' => __( 'WP Match Messages', 'wpmatch-free' ),
		'callback'             => 'wpmf_erase_messages_data',
	);
	$erasers['wpmatch-free-likes']    = array(
		'eraser_friendly_name' => __( 'WP Match Likes', 'wpmatch-free' ),
		'callback'             => 'wpmf_erase_likes_data',
	);
	return $erasers;
}

/**
 * Export profile data for privacy tool.
 *
 * @param string $email_address User email address.
 * @param int    $page Page number.
 * @return array Export data response.
 * @since 0.1.0
 */
function wpmf_export_profile_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$profile = wpmf_profile_get_by_user_id( $user->ID );
	if ( ! $profile ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$data_to_export = array();
	$personal_data  = array();

	if ( ! empty( $profile['gender'] ) ) {
		$personal_data[] = array(
			'name'  => __( 'Gender', 'wpmatch-free' ),
			'value' => $profile['gender'],
		);
	}

	if ( ! empty( $profile['orientation'] ) ) {
		$personal_data[] = array(
			'name'  => __( 'Orientation', 'wpmatch-free' ),
			'value' => $profile['orientation'],
		);
	}

	if ( ! empty( $profile['age'] ) ) {
		$personal_data[] = array(
			'name'  => __( 'Age', 'wpmatch-free' ),
			'value' => $profile['age'],
		);
	}

	if ( ! empty( $profile['region'] ) ) {
		$personal_data[] = array(
			'name'  => __( 'Region', 'wpmatch-free' ),
			'value' => $profile['region'],
		);
	}

	if ( ! empty( $profile['headline'] ) ) {
		$personal_data[] = array(
			'name'  => __( 'Headline', 'wpmatch-free' ),
			'value' => $profile['headline'],
		);
	}

	if ( ! empty( $profile['bio'] ) ) {
		$personal_data[] = array(
			'name'  => __( 'Bio', 'wpmatch-free' ),
			'value' => wp_strip_all_tags( $profile['bio'] ),
		);
	}

	$personal_data[] = array(
		'name'  => __( 'Verified Status', 'wpmatch-free' ),
		'value' => $profile['verified'] ? __( 'Verified', 'wpmatch-free' ) : __( 'Not Verified', 'wpmatch-free' ),
	);

	if ( ! empty( $personal_data ) ) {
		$data_to_export[] = array(
			'group_id'    => 'wpmatch-free-profile',
			'group_label' => __( 'WP Match Profile', 'wpmatch-free' ),
			'item_id'     => 'profile-' . $user->ID,
			'data'        => $personal_data,
		);
	}

	return array(
		'data' => $data_to_export,
		'done' => true,
	);
}

/**
 * Export messages data for privacy tool.
 *
 * @param string $email_address User email address.
 * @param int    $page Page number.
 * @return array Export data response.
 * @since 0.1.0
 */
function wpmf_export_messages_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	global $wpdb;
	$table  = $wpdb->prefix . 'wpmf_messages';
	$limit  = 100;
	$offset = ( $page - 1 ) * $limit;

	$messages = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE sender_id = %d OR recipient_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$user->ID,
			$user->ID,
			$limit,
			$offset
		),
		ARRAY_A
	);

	$data_to_export = array();
	foreach ( $messages as $message ) {
		$sender    = get_user_by( 'id', $message['sender_id'] );
		$recipient = get_user_by( 'id', $message['recipient_id'] );

		$data_to_export[] = array(
			'group_id'    => 'wpmatch-free-messages',
			'group_label' => __( 'WP Match Messages', 'wpmatch-free' ),
			'item_id'     => 'message-' . $message['id'],
			'data'        => array(
				array(
					'name'  => __( 'Sender', 'wpmatch-free' ),
					'value' => $sender ? $sender->display_name : __( 'Unknown', 'wpmatch-free' ),
				),
				array(
					'name'  => __( 'Recipient', 'wpmatch-free' ),
					'value' => $recipient ? $recipient->display_name : __( 'Unknown', 'wpmatch-free' ),
				),
				array(
					'name'  => __( 'Message', 'wpmatch-free' ),
					'value' => wp_strip_all_tags( $message['body'] ),
				),
				array(
					'name'  => __( 'Date', 'wpmatch-free' ),
					'value' => $message['created_at'],
				),
			),
		);
	}

	$done = count( $messages ) < $limit;
	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Export likes data for privacy tool.
 *
 * @param string $email_address User email address.
 * @param int    $page Page number.
 * @return array Export data response.
 * @since 0.1.0
 */
function wpmf_export_likes_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$likes = wpmf_likes_for_user( $user->ID, 100, ( $page - 1 ) * 100 );

	$data_to_export = array();
	foreach ( $likes as $like ) {
		$actor            = get_user_by( 'id', $like['actor_id'] );
		$data_to_export[] = array(
			'group_id'    => 'wpmatch-free-likes',
			'group_label' => __( 'WP Match Likes', 'wpmatch-free' ),
			'item_id'     => 'like-' . $like['id'],
			'data'        => array(
				array(
					'name'  => __( 'Liked by', 'wpmatch-free' ),
					'value' => $actor ? $actor->display_name : __( 'Unknown', 'wpmatch-free' ),
				),
				array(
					'name'  => __( 'Date', 'wpmatch-free' ),
					'value' => $like['created_at'],
				),
			),
		);
	}

	$done = count( $likes ) < 100;
	return array(
		'data' => $data_to_export,
		'done' => $done,
	);
}

/**
 * Erase profile data for privacy tool.
 *
 * @param string $email_address User email address.
 * @param int    $page Page number.
 * @return array Erasure response.
 * @since 0.1.0
 */
function wpmf_erase_profile_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	$removed = wpmf_profile_delete_by_user_id( $user->ID );

	return array(
		'items_removed'  => $removed ? 1 : 0,
		'items_retained' => 0,
		'messages'       => array(),
		'done'           => true,
	);
}

/**
 * Erase messages data for privacy tool.
 *
 * @param string $email_address User email address.
 * @param int    $page Page number.
 * @return array Erasure response.
 * @since 0.1.0
 */
function wpmf_erase_messages_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wpmf_messages';

	$deleted  = $wpdb->delete( $table, array( 'sender_id' => $user->ID ), array( '%d' ) );
	$deleted += $wpdb->delete( $table, array( 'recipient_id' => $user->ID ), array( '%d' ) );

	return array(
		'items_removed'  => $deleted,
		'items_retained' => 0,
		'messages'       => array(),
		'done'           => true,
	);
}

/**
 * Erase likes data for privacy tool.
 *
 * @param string $email_address User email address.
 * @param int    $page Page number.
 * @return array Erasure response.
 * @since 0.1.0
 */
function wpmf_erase_likes_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		return array(
			'items_removed'  => 0,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wpmf_likes';

	$deleted  = $wpdb->delete( $table, array( 'actor_id' => $user->ID ), array( '%d' ) );
	$deleted += $wpdb->delete( $table, array( 'target_user_id' => $user->ID ), array( '%d' ) );

	return array(
		'items_removed'  => $deleted,
		'items_retained' => 0,
		'messages'       => array(),
		'done'           => true,
	);
}

/**
 * Add privacy policy content suggestion.
 *
 * @since 0.1.0
 */
function wpmf_add_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = sprintf(
		'<h3>%s</h3>' .
		'<p>%s</p>' .
		'<p>%s</p>' .
		'<ul>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'<li>%s</li>' .
		'</ul>' .
		'<p>%s</p>',
		__( 'Dating Profile Information', 'wpmatch-free' ),
		__( 'When you create a dating profile on this site, we collect and store the following information:', 'wpmatch-free' ),
		__( 'This information is used to help you connect with other users and improve our service.', 'wpmatch-free' ),
		__( 'Profile information (gender, age, location, bio, interests)', 'wpmatch-free' ),
		__( 'Photos you upload to your profile', 'wpmatch-free' ),
		__( 'Messages you send and receive', 'wpmatch-free' ),
		__( 'Likes and favorites you give or receive', 'wpmatch-free' ),
		__( 'Activity data such as when you were last active', 'wpmatch-free' ),
		__( 'You can request to export or delete all your dating profile data at any time using the WordPress privacy tools.', 'wpmatch-free' )
	);

	wp_add_privacy_policy_content( 'WP Match Free', $content );
}
add_action( 'admin_init', 'wpmf_add_privacy_policy_content' );
