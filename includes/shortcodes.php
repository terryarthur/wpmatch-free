<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

function wpmf_sc_profile_edit() {
	if ( ! is_user_logged_in() ) {
		return esc_html__( 'Please log in.', 'wpmatch-free' );
	}

	$user_id = get_current_user_id();
	$profile = wpmf_profile_get_by_user_id( $user_id );
	$photos  = wpmf_photos_list_by_user( $user_id );

	// Profile form data.
	$gender      = esc_attr( $profile['gender'] ?? '' );
	$region      = esc_attr( $profile['region'] ?? '' );
	$headline    = esc_attr( $profile['headline'] ?? '' );
	$bio         = esc_textarea( $profile['bio'] ?? '' );
	$nonce       = wp_create_nonce( 'wpmf_profile_save' );
	$photo_nonce = wp_create_nonce( 'wpmf_photo_upload' );

	$out = '<div class="wpmf-profile-edit-container">';

	// Profile basic info form.
	$out .= '<div class="wpmf-profile-basic-info">';
	$out .= '<h3>' . esc_html__( 'Basic Information', 'wpmatch-free' ) . '</h3>';
	$out .= '<form class="wpmf-profile-edit" method="post">';
	$out .= '<input type="hidden" name="wpmf_nonce" value="' . esc_attr( $nonce ) . '" />';
	$out .= '<p><label>' . esc_html__( 'Gender', 'wpmatch-free' ) . ' <input name="gender" value="' . $gender . '"/></label></p>';
	$out .= '<p><label>' . esc_html__( 'Region', 'wpmatch-free' ) . ' <input name="region" value="' . $region . '"/></label></p>';
	$out .= '<p><label>' . esc_html__( 'Headline', 'wpmatch-free' ) . ' <input name="headline" value="' . $headline . '"/></label></p>';
	$out .= '<p><label>' . esc_html__( 'Bio', 'wpmatch-free' ) . '<br/><textarea name="bio" rows="5">' . $bio . '</textarea></label></p>';
	$out .= '<p><button type="submit">' . esc_html__( 'Save Profile', 'wpmatch-free' ) . '</button></p>';
	$out .= '</form>';
	$out .= '</div>';

	// Photo management section.
	$out .= '<div class="wpmf-photo-management">';
	$out .= '<h3>' . esc_html__( 'Your Photos', 'wpmatch-free' ) . '</h3>';

	// Display current photos.
	if ( ! empty( $photos ) ) {
		$out .= '<div class="wpmf-current-photos">';
		foreach ( $photos as $photo ) {
			$attachment_id = (int) $photo['attachment_id'];
			$image_data    = wp_get_attachment_image_src( $attachment_id, 'medium' );
			$image_url     = $image_data ? $image_data[0] : '';

			$status_class  = 'wpmf-photo-' . esc_attr( $photo['status'] );
			$primary_class = $photo['is_primary'] ? ' wpmf-photo-primary' : '';

			$out .= '<div class="wpmf-photo-item ' . $status_class . $primary_class . '" data-photo-id="' . esc_attr( $photo['id'] ) . '">';

			if ( $image_url ) {
				$out .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr__( 'Profile photo', 'wpmatch-free' ) . '" />';
			}

			$out .= '<div class="wpmf-photo-info">';
			$out .= '<div class="wpmf-photo-status">' . esc_html( ucfirst( $photo['status'] ) ) . '</div>';

			if ( $photo['is_primary'] ) {
				$out .= '<div class="wpmf-photo-primary-badge">' . esc_html__( 'Primary Photo', 'wpmatch-free' ) . '</div>';
			}

			$out .= '<div class="wpmf-photo-actions">';
			if ( ! $photo['is_primary'] ) {
				$out .= '<button type="button" class="wpmf-set-primary" data-photo-id="' . esc_attr( $photo['id'] ) . '">';
				$out .= esc_html__( 'Set as Primary', 'wpmatch-free' );
				$out .= '</button>';
			}
			$out .= '<button type="button" class="wpmf-delete-photo" data-photo-id="' . esc_attr( $photo['id'] ) . '">';
			$out .= esc_html__( 'Delete', 'wpmatch-free' );
			$out .= '</button>';
			$out .= '</div>';
			$out .= '</div>';
			$out .= '</div>';
		}
		$out .= '</div>';
	} else {
		$out .= '<p class="wpmf-no-photos">' . esc_html__( 'You haven\'t uploaded any photos yet.', 'wpmatch-free' ) . '</p>';
	}

	// Photo upload form.
	$photo_limit = apply_filters( 'wpmf_photo_limit_per_user', 10 );
	$can_upload  = count( $photos ) < $photo_limit;

	if ( $can_upload ) {
		$out .= '<div class="wpmf-photo-upload">';
		$out .= '<h4>' . esc_html__( 'Add New Photo', 'wpmatch-free' ) . '</h4>';
		$out .= '<form class="wpmf-photo-upload-form" method="post" enctype="multipart/form-data">';
		$out .= '<input type="hidden" name="wpmf_photo_nonce" value="' . esc_attr( $photo_nonce ) . '" />';
		$out .= '<input type="hidden" name="wpmf_action" value="upload_photo" />';
		$out .= '<p>';
		$out .= '<label>' . esc_html__( 'Choose Photo', 'wpmatch-free' ) . '<br/>';
		$out .= '<input type="file" name="wpmf_photo" accept="image/*" required /></label>';
		$out .= '</p>';
		$out .= '<p class="wpmf-upload-info">';
		$out .= sprintf(
			/* translators: 1: current count, 2: max limit */
			esc_html__( 'You can upload %1$d more photos (limit: %2$d)', 'wpmatch-free' ),
			$photo_limit - count( $photos ),
			$photo_limit
		);
		$out .= '</p>';
		$out .= '<p><button type="submit">' . esc_html__( 'Upload Photo', 'wpmatch-free' ) . '</button></p>';
		$out .= '</form>';
		$out .= '</div>';
	} else {
		$out .= '<p class="wpmf-photo-limit-reached">';
		$out .= sprintf(
			/* translators: %d: photo limit */
			esc_html__( 'You have reached the maximum of %d photos. Delete a photo to add a new one.', 'wpmatch-free' ),
			$photo_limit
		);
		$out .= '</p>';
	}

	$out .= '</div>'; // .wpmf-photo-management
	$out .= '</div>'; // .wpmf-profile-edit-container

	// Add JavaScript for photo management.
	$out .= wpmf_photo_management_scripts();

	return $out;
}
add_shortcode( 'wpmf_profile_edit', 'wpmf_sc_profile_edit' );

function wpmf_handle_profile_edit_post() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();

	// Handle photo upload.
	if ( isset( $_POST['wpmf_action'] ) && 'upload_photo' === $_POST['wpmf_action'] ) {
		if ( empty( $_POST['wpmf_photo_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wpmf_photo_nonce'] ), 'wpmf_photo_upload' ) ) {
			return;
		}

		if ( isset( $_FILES['wpmf_photo'] ) && UPLOAD_ERR_OK === $_FILES['wpmf_photo']['error'] ) {
			$upload_result = wpmf_photo_upload_secure( $_FILES['wpmf_photo'], $user_id );

			// Store result in session for display.
			if ( ! session_id() ) {
				session_start();
			}
			$_SESSION['wpmf_photo_upload_result'] = $upload_result;
		}

		// Redirect to prevent double submission.
		wp_safe_redirect( wp_get_referer() );
		exit;
	}

	// Handle profile basic info update.
	if ( empty( $_POST['wpmf_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wpmf_nonce'] ), 'wpmf_profile_save' ) ) {
		return;
	}

	$data = array(
		'gender'   => sanitize_text_field( wp_unslash( $_POST['gender'] ?? '' ) ),
		'region'   => sanitize_text_field( wp_unslash( $_POST['region'] ?? '' ) ),
		'headline' => sanitize_text_field( wp_unslash( $_POST['headline'] ?? '' ) ),
		'bio'      => wp_kses_post( wp_unslash( $_POST['bio'] ?? '' ) ),
	);

	$existing_profile = wpmf_profile_get_by_user_id( $user_id );
	if ( $existing_profile ) {
		wpmf_profile_update_by_user_id( $user_id, $data );
	} else {
		$data['user_id'] = $user_id;
		wpmf_profile_create( $data );
	}
}
add_action( 'template_redirect', 'wpmf_handle_profile_edit_post' );

function wpmf_sc_search_form() {
	$out  = '<form class="wpmf-search-form" method="get">';
	$out .= '<p><label>' . esc_html__( 'Region', 'wpmatch-free' ) . ' <input name="region" value="' . esc_attr( wp_unslash( $_GET['region'] ?? '' ) ) . '"/></label></p>';
	$out .= '<p><label>' . esc_html__( 'Age min', 'wpmatch-free' ) . ' <input type="number" name="age_min" value="' . esc_attr( wp_unslash( $_GET['age_min'] ?? '' ) ) . '"/></label></p>';
	$out .= '<p><label>' . esc_html__( 'Age max', 'wpmatch-free' ) . ' <input type="number" name="age_max" value="' . esc_attr( wp_unslash( $_GET['age_max'] ?? '' ) ) . '"/></label></p>';
	$out .= '<p><label><input type="checkbox" name="has_photo" ' . checked( ! empty( $_GET['has_photo'] ), true, false ) . ' /> ' . esc_html__( 'With photo', 'wpmatch-free' ) . '</label></p>';
	$out .= '<p><button type="submit">' . esc_html__( 'Search', 'wpmatch-free' ) . '</button></p>';
	$out .= '</form>';
	return $out;
}
add_shortcode( 'wpmf_search_form', 'wpmf_sc_search_form' );

function wpmf_sc_search_results() {
	// Prepare search filters
	$filters = array();
	if ( ! empty( $_GET['region'] ) ) {
		$filters['region'] = sanitize_text_field( $_GET['region'] );
	}
	if ( isset( $_GET['age_min'] ) && $_GET['age_min'] !== '' ) {
		$filters['age_min'] = absint( $_GET['age_min'] );
	}
	if ( isset( $_GET['age_max'] ) && $_GET['age_max'] !== '' ) {
		$filters['age_max'] = absint( $_GET['age_max'] );
	}
	if ( ! empty( $_GET['verified'] ) ) {
		$filters['verified'] = true;
	}
	if ( ! empty( $_GET['has_photo'] ) ) {
		$filters['has_photo'] = true;
	}

	// Get pagination parameters
	$current_page    = WPMF_Pagination::get_current_page();
	$per_page        = WPMF_Pagination::get_per_page();
	$current_user_id = get_current_user_id();

	// Get paginated results
	$pagination_data = WPMF_Pagination::get_paginated_search( $filters, $current_page, $per_page, $current_user_id );

	// Build output
	$out = '<div class="wpmf-search-container">';

	// Results summary
	if ( $pagination_data['total_items'] > 0 ) {
		$out .= '<div class="wpmf-search-summary">';
		$out .= sprintf(
			/* translators: 1: total results */
			esc_html__( 'Found %d matching profiles', 'wpmatch-free' ),
			$pagination_data['total_items']
		);
		$out .= '</div>';
	}

	// Results grid
	$out .= '<div class="wpmf-search-results">';
	foreach ( $pagination_data['results'] as $r ) {
		$out .= '<div class="wpmf-card" data-user-id="' . esc_attr( $r['user_id'] ) . '">';

		// Add profile photo if available
		$user_photos   = wpmf_photos_list_by_user( (int) $r['user_id'] );
		$primary_photo = null;

		// Find approved primary photo
		foreach ( $user_photos as $photo ) {
			if ( $photo['is_primary'] && 'approved' === $photo['status'] ) {
				$primary_photo = $photo;
				break;
			}
		}

		// If no primary, get first approved photo
		if ( ! $primary_photo ) {
			foreach ( $user_photos as $photo ) {
				if ( 'approved' === $photo['status'] ) {
					$primary_photo = $photo;
					break;
				}
			}
		}

		if ( $primary_photo ) {
			$attachment_id = (int) $primary_photo['attachment_id'];
			$image_data    = wp_get_attachment_image_src( $attachment_id, 'medium' );
			if ( $image_data ) {
				$out .= '<div class="wpmf-card-photo">';
				$out .= '<img src="' . esc_url( $image_data[0] ) . '" alt="' . esc_attr( $r['headline'] ?? __( 'Profile photo', 'wpmatch-free' ) ) . '" />';
				$out .= '</div>';
			}
		} else {
			// Default placeholder if no photo
			$out .= '<div class="wpmf-card-photo wpmf-card-no-photo">';
			$out .= '<div class="wpmf-photo-placeholder">📷</div>';
			$out .= '</div>';
		}

		$out .= '<div class="wpmf-card-content">';
		$out .= '<div class="wpmf-card-headline">' . esc_html( $r['headline'] ?? '' ) . '</div>';
		$out .= '<div class="wpmf-card-region">' . esc_html( $r['region'] ?? '' ) . '</div>';
		if ( ! empty( $r['age'] ) ) {
			$out .= '<div class="wpmf-card-age">' . esc_html( $r['age'] ) . ' years old</div>';
		}
		if ( ! empty( $r['verified'] ) ) {
			$out .= '<div class="wpmf-card-verified">✓ ' . esc_html__( 'Verified', 'wpmatch-free' ) . '</div>';
		}
		$out .= '</div>';
		$out .= '</div>';
	}

	if ( empty( $pagination_data['results'] ) ) {
		$out .= '<div class="wpmf-no-results">';
		$out .= esc_html__( 'No profiles found matching your criteria. Try adjusting your search filters.', 'wpmatch-free' );
		$out .= '</div>';
	}

	$out .= '</div>'; // .wpmf-search-results

	// Pagination
	$url_args = array_filter(
		array(
			'region'    => $_GET['region'] ?? '',
			'age_min'   => $_GET['age_min'] ?? '',
			'age_max'   => $_GET['age_max'] ?? '',
			'verified'  => $_GET['verified'] ?? '',
			'has_photo' => $_GET['has_photo'] ?? '',
		)
	);

	$out .= WPMF_Pagination::render_pagination( $pagination_data, '', $url_args );

	$out .= '</div>'; // .wpmf-search-container

	return $out;
}
add_shortcode( 'wpmf_search_results', 'wpmf_sc_search_results' );

/**
 * Generate JavaScript for photo management functionality.
 *
 * @return string JavaScript code for photo management.
 * @since 0.1.0
 */
function wpmf_photo_management_scripts() {
	$ajax_url = admin_url( 'admin-ajax.php' );
	$nonce    = wp_create_nonce( 'wpmf_photo_action' );

	$script = '<script>
	(function() {
		// Handle set primary photo
		document.addEventListener("click", function(e) {
			if (e.target && e.target.classList.contains("wpmf-set-primary")) {
				e.preventDefault();
				var photoId = e.target.dataset.photoId;
				var button = e.target;
				
				if (!photoId) return;
				
				button.disabled = true;
				button.textContent = "' . esc_js( __( 'Setting...', 'wpmatch-free' ) ) . '";
				
				fetch("' . esc_url( $ajax_url ) . '", {
					method: "POST",
					headers: {
						"Content-Type": "application/x-www-form-urlencoded",
					},
					body: new URLSearchParams({
						action: "wpmf_set_primary_photo",
						photo_id: photoId,
						nonce: "' . esc_js( $nonce ) . '"
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						location.reload();
					} else {
						alert(data.data || "' . esc_js( __( 'Error setting primary photo', 'wpmatch-free' ) ) . '");
						button.disabled = false;
						button.textContent = "' . esc_js( __( 'Set as Primary', 'wpmatch-free' ) ) . '";
					}
				})
				.catch(error => {
					console.error("Error:", error);
					alert("' . esc_js( __( 'Network error occurred', 'wpmatch-free' ) ) . '");
					button.disabled = false;
					button.textContent = "' . esc_js( __( 'Set as Primary', 'wpmatch-free' ) ) . '";
				});
			}
		});
		
		// Handle delete photo
		document.addEventListener("click", function(e) {
			if (e.target && e.target.classList.contains("wpmf-delete-photo")) {
				e.preventDefault();
				var photoId = e.target.dataset.photoId;
				var button = e.target;
				
				if (!photoId) return;
				
				if (!confirm("' . esc_js( __( 'Are you sure you want to delete this photo?', 'wpmatch-free' ) ) . '")) {
					return;
				}
				
				button.disabled = true;
				button.textContent = "' . esc_js( __( 'Deleting...', 'wpmatch-free' ) ) . '";
				
				fetch("' . esc_url( $ajax_url ) . '", {
					method: "POST",
					headers: {
						"Content-Type": "application/x-www-form-urlencoded",
					},
					body: new URLSearchParams({
						action: "wpmf_delete_photo",
						photo_id: photoId,
						nonce: "' . esc_js( $nonce ) . '"
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						location.reload();
					} else {
						alert(data.data || "' . esc_js( __( 'Error deleting photo', 'wpmatch-free' ) ) . '");
						button.disabled = false;
						button.textContent = "' . esc_js( __( 'Delete', 'wpmatch-free' ) ) . '";
					}
				})
				.catch(error => {
					console.error("Error:", error);
					alert("' . esc_js( __( 'Network error occurred', 'wpmatch-free' ) ) . '");
					button.disabled = false;
					button.textContent = "' . esc_js( __( 'Delete', 'wpmatch-free' ) ) . '";
				});
			}
		});
		
		// Display upload result message if exists
		' . wpmf_get_upload_result_script() . '
	})();
	</script>';

	return $script;
}

/**
 * Get upload result message script from session.
 *
 * @return string JavaScript for displaying upload result.
 * @since 0.1.0
 */
function wpmf_get_upload_result_script() {
	if ( ! session_id() ) {
		session_start();
	}

	if ( isset( $_SESSION['wpmf_photo_upload_result'] ) ) {
		$result = $_SESSION['wpmf_photo_upload_result'];
		unset( $_SESSION['wpmf_photo_upload_result'] );

		$message = esc_js( $result['message'] );
		$type    = $result['success'] ? 'success' : 'error';

		return '
		// Display upload result
		var messageDiv = document.createElement("div");
		messageDiv.className = "wpmf-message wpmf-message-' . $type . '";
		messageDiv.textContent = "' . $message . '";
		messageDiv.style.cssText = "padding:10px;margin:10px 0;border-radius:3px;' .
			( 'success' === $type ? 'background:#d4edda;border:1px solid #c3e6cb;color:#155724;' : 'background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;' ) . '";
		
		var container = document.querySelector(".wpmf-profile-edit-container");
		if (container) {
			container.insertBefore(messageDiv, container.firstChild);
			setTimeout(function() {
				messageDiv.style.opacity = "0";
				setTimeout(function() {
					messageDiv.remove();
				}, 300);
			}, 5000);
		}';
	}

	return '';
}

/**
 * AJAX handler for setting primary photo.
 *
 * @since 0.1.0
 */
function wpmf_ajax_set_primary_photo() {
	check_ajax_referer( 'wpmf_photo_action', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to perform this action.', 'wpmatch-free' ) );
	}

	$photo_id = absint( $_POST['photo_id'] ?? 0 );
	$user_id  = get_current_user_id();

	if ( ! $photo_id ) {
		wp_send_json_error( __( 'Invalid photo ID.', 'wpmatch-free' ) );
	}

	// Verify photo ownership.
	$photo = wpmf_photo_get( $photo_id, $user_id );
	if ( ! $photo ) {
		wp_send_json_error( __( 'Photo not found or access denied.', 'wpmatch-free' ) );
	}

	$success = wpmf_photo_set_primary( $user_id, $photo_id );
	if ( $success ) {
		wp_send_json_success( __( 'Primary photo updated successfully.', 'wpmatch-free' ) );
	} else {
		wp_send_json_error( __( 'Failed to update primary photo.', 'wpmatch-free' ) );
	}
}
add_action( 'wp_ajax_wpmf_set_primary_photo', 'wpmf_ajax_set_primary_photo' );

/**
 * AJAX handler for deleting photo.
 *
 * @since 0.1.0
 */
function wpmf_ajax_delete_photo() {
	check_ajax_referer( 'wpmf_photo_action', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to perform this action.', 'wpmatch-free' ) );
	}

	$photo_id = absint( $_POST['photo_id'] ?? 0 );
	$user_id  = get_current_user_id();

	if ( ! $photo_id ) {
		wp_send_json_error( __( 'Invalid photo ID.', 'wpmatch-free' ) );
	}

	$success = wpmf_photo_delete_secure( $photo_id, $user_id );
	if ( $success ) {
		wp_send_json_success( __( 'Photo deleted successfully.', 'wpmatch-free' ) );
	} else {
		wp_send_json_error( __( 'Failed to delete photo.', 'wpmatch-free' ) );
	}
}
add_action( 'wp_ajax_wpmf_delete_photo', 'wpmf_ajax_delete_photo' );

/**
 * Messaging inbox shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 * @since 0.1.0
 */
function wpmf_sc_messages_inbox( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . __( 'You must be logged in to view messages.', 'wpmatch-free' ) . '</p>';
	}

	$atts = shortcode_atts(
		array(
			'per_page' => 20,
		),
		$atts
	);

	wp_enqueue_script( 'wpmf-messaging', plugin_dir_url( __DIR__ ) . 'assets/messaging.js', array( 'jquery' ), '0.1.0', true );
	wp_localize_script(
		'wpmf-messaging',
		'wpmf_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'rest_url' => rest_url( 'wpmatch-free/v1/' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'user_id'  => get_current_user_id(),
		)
	);

	ob_start();
	?>
	<div id="wpmf-messages-container" class="wpmf-messages-container">
		<div class="wpmf-messages-header">
			<h3><?php esc_html_e( 'Messages', 'wpmatch-free' ); ?></h3>
			<div class="wpmf-unread-count-badge" id="wpmf-unread-count" style="display: none;">
				<span>0</span>
			</div>
		</div>
		
		<div class="wpmf-messages-sidebar" id="wpmf-conversations-list">
			<div class="wpmf-loading"><?php esc_html_e( 'Loading conversations...', 'wpmatch-free' ); ?></div>
		</div>
		
		<div class="wpmf-messages-main" id="wpmf-messages-main">
			<div class="wpmf-no-conversation">
				<div class="wpmf-no-conversation-content">
					<div class="wpmf-icon">💬</div>
					<h4><?php esc_html_e( 'Select a conversation', 'wpmatch-free' ); ?></h4>
					<p><?php esc_html_e( 'Choose a conversation from the sidebar to start messaging.', 'wpmatch-free' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'wpmf_messages_inbox', 'wpmf_sc_messages_inbox' );

/**
 * Message conversation shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 * @since 0.1.0
 */
function wpmf_sc_conversation( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . __( 'You must be logged in to view conversations.', 'wpmatch-free' ) . '</p>';
	}

	$atts = shortcode_atts(
		array(
			'thread_id'    => 0,
			'recipient_id' => 0,
		),
		$atts
	);

	$thread_id    = absint( $atts['thread_id'] );
	$recipient_id = absint( $atts['recipient_id'] );

	// If no thread_id but we have recipient_id, try to find/create thread
	if ( ! $thread_id && $recipient_id ) {
		$current_user_id = get_current_user_id();
		$thread_id       = wpmf_thread_get_or_create( $current_user_id, $recipient_id );
	}

	if ( ! $thread_id ) {
		return '<p>' . __( 'Invalid conversation.', 'wpmatch-free' ) . '</p>';
	}

	wp_enqueue_script( 'wpmf-messaging', plugin_dir_url( __DIR__ ) . 'assets/messaging.js', array( 'jquery' ), '0.1.0', true );
	wp_localize_script(
		'wpmf-messaging',
		'wpmf_ajax',
		array(
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'rest_url'  => rest_url( 'wpmatch-free/v1/' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'user_id'   => get_current_user_id(),
			'thread_id' => $thread_id,
		)
	);

	ob_start();
	?>
	<div id="wpmf-conversation-container" class="wpmf-conversation-container" data-thread-id="<?php echo esc_attr( $thread_id ); ?>">
		<div class="wpmf-conversation-header" id="wpmf-conversation-header">
			<div class="wpmf-loading"><?php esc_html_e( 'Loading conversation...', 'wpmatch-free' ); ?></div>
		</div>
		
		<div class="wpmf-messages-list" id="wpmf-messages-list">
			<div class="wpmf-loading"><?php esc_html_e( 'Loading messages...', 'wpmatch-free' ); ?></div>
		</div>
		
		<div class="wpmf-message-compose" id="wpmf-message-compose">
			<form id="wpmf-send-message-form">
				<div class="wpmf-compose-input">
					<textarea 
						id="wpmf-message-input" 
						name="message" 
						placeholder="<?php esc_attr_e( 'Type your message...', 'wpmatch-free' ); ?>"
						rows="2"
						maxlength="2000"
						required
					></textarea>
				</div>
				<div class="wpmf-compose-actions">
					<button type="submit" class="wpmf-send-button">
						<span class="wpmf-send-text"><?php esc_html_e( 'Send', 'wpmatch-free' ); ?></span>
						<span class="wpmf-send-loading" style="display: none;"><?php esc_html_e( 'Sending...', 'wpmatch-free' ); ?></span>
					</button>
				</div>
			</form>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'wpmf_conversation', 'wpmf_sc_conversation' );
