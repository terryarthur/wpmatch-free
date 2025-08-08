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

/**
 * Get photo by ID with user verification.
 *
 * @param int $photo_id Photo ID to retrieve.
 * @param int $user_id User ID for ownership verification (optional).
 * @return array|null Photo data or null if not found.
 * @since 0.1.0
 */
function wpmf_photo_get( int $photo_id, int $user_id = 0 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'wpmf_photos';

	$where  = array( 'id = %d' );
	$values = array( $photo_id );

	if ( $user_id > 0 ) {
		$where[]  = 'user_id = %d';
		$values[] = $user_id;
	}

	$sql = $wpdb->prepare(
		"SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ),
		$values
	);

	return $wpdb->get_row( $sql, ARRAY_A );
}

/**
 * Set primary photo for user (ensures only one primary photo).
 *
 * @param int $user_id User ID.
 * @param int $photo_id Photo ID to set as primary.
 * @return bool Success status.
 * @since 0.1.0
 */
function wpmf_photo_set_primary( int $user_id, int $photo_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'wpmf_photos';

	// Start transaction.
	$wpdb->query( 'START TRANSACTION' );

	try {
		// Remove primary flag from all user's photos.
		$wpdb->update(
			$table,
			array( 'is_primary' => 0 ),
			array( 'user_id' => $user_id ),
			array( '%d' ),
			array( '%d' )
		);

		// Set the specified photo as primary.
		$result = $wpdb->update(
			$table,
			array( 'is_primary' => 1 ),
			array(
				'id'      => $photo_id,
				'user_id' => $user_id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);

		if ( false === $result ) {
			throw new Exception( 'Failed to set primary photo' );
		}

		$wpdb->query( 'COMMIT' );
		return true;

	} catch ( Exception $e ) {
		$wpdb->query( 'ROLLBACK' );
		return false;
	}
}

/**
 * Handle secure photo upload with WordPress media library integration.
 *
 * @param array $file $_FILES array element for the uploaded file.
 * @param int   $user_id User ID uploading the photo.
 * @param bool  $set_primary Whether to set as primary photo.
 * @return array Result array with success status and data.
 * @since 0.1.0
 */
function wpmf_photo_upload_secure( array $file, int $user_id, bool $set_primary = false ) {
	// Verify user capabilities.
	if ( ! current_user_can( 'dating_upload_photo' ) ) {
		return array(
			'success' => false,
			'message' => __( 'You do not have permission to upload photos.', 'wpmatch-free' ),
		);
	}

	// Verify file was uploaded.
	if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
		return array(
			'success' => false,
			'message' => __( 'Photo upload failed. Please try again.', 'wpmatch-free' ),
		);
	}

	// Validate file type.
	$allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' );
	$finfo         = finfo_open( FILEINFO_MIME_TYPE );
	$mime_type     = finfo_file( $finfo, $file['tmp_name'] );
	finfo_close( $finfo );

	if ( ! in_array( $mime_type, $allowed_types, true ) ) {
		return array(
			'success' => false,
			'message' => __( 'Please upload a valid image file (JPEG, PNG, GIF, or WebP).', 'wpmatch-free' ),
		);
	}

	// Validate file size (max 10MB).
	$max_size = apply_filters( 'wpmf_photo_max_size', 10 * 1024 * 1024 );
	if ( $file['size'] > $max_size ) {
		return array(
			'success' => false,
			'message' => sprintf(
				/* translators: %s: Maximum file size */
				__( 'Photo must be smaller than %s.', 'wpmatch-free' ),
				size_format( $max_size )
			),
		);
	}

	// Check user photo limit.
	$current_photos = wpmf_photos_list_by_user( $user_id );
	$photo_limit    = apply_filters( 'wpmf_photo_limit_per_user', 10 );
	if ( count( $current_photos ) >= $photo_limit ) {
		return array(
			'success' => false,
			'message' => sprintf(
				/* translators: %d: Photo limit */
				__( 'You can upload a maximum of %d photos. Please delete a photo before adding a new one.', 'wpmatch-free' ),
				$photo_limit
			),
		);
	}

	// Validate image dimensions and content.
	$image_info = getimagesize( $file['tmp_name'] );
	if ( false === $image_info ) {
		return array(
			'success' => false,
			'message' => __( 'Invalid image file. Please upload a valid image.', 'wpmatch-free' ),
		);
	}

	$min_width  = apply_filters( 'wpmf_photo_min_width', 200 );
	$min_height = apply_filters( 'wpmf_photo_min_height', 200 );

	if ( $image_info[0] < $min_width || $image_info[1] < $min_height ) {
		return array(
			'success' => false,
			'message' => sprintf(
				/* translators: 1: minimum width, 2: minimum height */
				__( 'Photo must be at least %1$dx%2$d pixels.', 'wpmatch-free' ),
				$min_width,
				$min_height
			),
		);
	}

	// Handle the upload using WordPress media library.
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$upload_overrides = array(
		'test_form'                => false,
		'unique_filename_callback' => 'wpmf_photo_unique_filename',
	);

	$uploaded_file = wp_handle_upload( $file, $upload_overrides );

	if ( isset( $uploaded_file['error'] ) ) {
		return array(
			'success' => false,
			'message' => $uploaded_file['error'],
		);
	}

	// Create attachment post.
	$attachment_data = array(
		'post_mime_type' => $uploaded_file['type'],
		'post_title'     => sprintf(
			/* translators: %d: User ID */
			__( 'Dating Profile Photo - User %d', 'wpmatch-free' ),
			$user_id
		),
		'post_content'   => '',
		'post_status'    => 'inherit',
		'post_author'    => $user_id,
	);

	$attachment_id = wp_insert_attachment( $attachment_data, $uploaded_file['file'] );

	if ( is_wp_error( $attachment_id ) ) {
		// Clean up uploaded file on error.
		wp_delete_file( $uploaded_file['file'] );
		return array(
			'success' => false,
			'message' => __( 'Failed to create attachment. Please try again.', 'wpmatch-free' ),
		);
	}

	// Generate attachment metadata.
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
	wp_update_attachment_metadata( $attachment_id, $attachment_metadata );

	// Determine photo status based on moderation settings.
	$moderation_mode = get_option( 'wpmf_photo_moderation_mode', 'post' );
	$photo_status    = ( 'pre' === $moderation_mode ) ? 'pending' : 'approved';

	// Set as primary if user has no photos yet.
	if ( empty( $current_photos ) ) {
		$set_primary = true;
	}

	// Add photo to our custom table.
	$photo_id = wpmf_photo_add( $user_id, $attachment_id, $set_primary, $photo_status );

	if ( ! $photo_id ) {
		// Clean up on database error.
		wp_delete_attachment( $attachment_id, true );
		return array(
			'success' => false,
			'message' => __( 'Failed to save photo information. Please try again.', 'wpmatch-free' ),
		);
	}

	// Fire action for other plugins/features to hook into.
	do_action( 'wpmf_photo_uploaded', $photo_id, $user_id, $attachment_id );

	return array(
		'success'       => true,
		'message'       => ( 'pending' === $photo_status ) ?
			__( 'Photo uploaded successfully and is pending moderation.', 'wpmatch-free' ) :
			__( 'Photo uploaded successfully!', 'wpmatch-free' ),
		'photo_id'      => $photo_id,
		'attachment_id' => $attachment_id,
		'status'        => $photo_status,
	);
}

/**
 * Generate unique filename for photo uploads.
 *
 * @param string $dir Upload directory.
 * @param string $name Original filename.
 * @param string $ext File extension.
 * @return string Unique filename.
 * @since 0.1.0
 */
function wpmf_photo_unique_filename( $dir, $name, $ext ) {
	$prefix = 'wpmatch-photo-' . get_current_user_id() . '-';
	$name   = $prefix . wp_generate_password( 12, false, false ) . $ext;
	return $name;
}

/**
 * Delete photo with proper cleanup.
 *
 * @param int  $photo_id Photo ID to delete.
 * @param int  $user_id User ID for ownership verification.
 * @param bool $force_delete Whether to permanently delete attachment.
 * @return bool Success status.
 * @since 0.1.0
 */
function wpmf_photo_delete_secure( int $photo_id, int $user_id, bool $force_delete = true ) {
	// Get photo data and verify ownership.
	$photo = wpmf_photo_get( $photo_id, $user_id );
	if ( ! $photo ) {
		return false;
	}

	// Verify user capabilities.
	if ( ! current_user_can( 'dating_upload_photo' ) && $photo['user_id'] !== get_current_user_id() ) {
		return false;
	}

	// Delete from our custom table.
	$deleted = wpmf_photo_delete( $photo_id );
	if ( ! $deleted ) {
		return false;
	}

	// Delete WordPress attachment if requested.
	if ( $force_delete && $photo['attachment_id'] ) {
		wp_delete_attachment( $photo['attachment_id'], true );
	}

	// If this was the primary photo, set another as primary.
	if ( $photo['is_primary'] ) {
		$remaining_photos = wpmf_photos_list_by_user( $user_id );
		if ( ! empty( $remaining_photos ) ) {
			wpmf_photo_set_primary( $user_id, $remaining_photos[0]['id'] );
		}
	}

	// Fire action for cleanup.
	do_action( 'wpmf_photo_deleted', $photo_id, $user_id, $photo['attachment_id'] );

	return true;
}
