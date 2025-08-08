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

	$out = '<div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">';

	// Profile basic info form.
	$out .= '<div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 px-6 py-4">';
	$out .= '<h3 class="text-2xl font-bold text-white flex items-center">';
	$out .= '<svg class="w-7 h-7 mr-3" fill="currentColor" viewBox="0 0 20 20">';
	$out .= '<path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>';
	$out .= '</svg>';
	$out .= esc_html__( 'Basic Information', 'wpmatch-free' ) . '</h3>';
	$out .= '</div>';
	$out .= '<div class="p-6">';
	$out .= '<form class="space-y-6" method="post">';
	$out .= '<input type="hidden" name="wpmf_nonce" value="' . esc_attr( $nonce ) . '" />';

	$out .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
	$out .= '<div>';
	$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'Gender', 'wpmatch-free' ) . '</label>';
	$out .= '<input name="gender" value="' . $gender . '" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"/>';
	$out .= '</div>';
	$out .= '<div>';
	$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'Region', 'wpmatch-free' ) . '</label>';
	$out .= '<input name="region" value="' . $region . '" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"/>';
	$out .= '</div>';
	$out .= '</div>';

	$out .= '<div>';
	$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'Headline', 'wpmatch-free' ) . '</label>';
	$out .= '<input name="headline" value="' . $headline . '" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200" placeholder="' . esc_attr__( 'Tell others about yourself in one line...', 'wpmatch-free' ) . '"/>';
	$out .= '</div>';

	$out .= '<div>';
	$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'Bio', 'wpmatch-free' ) . '</label>';
	$out .= '<textarea name="bio" rows="5" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200 resize-none" placeholder="' . esc_attr__( 'Share more about yourself, your interests, and what you\'re looking for...', 'wpmatch-free' ) . '">' . $bio . '</textarea>';
	$out .= '</div>';

	$out .= '<div class="flex justify-end">';
	$out .= '<button type="submit" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-indigo-500/30 flex items-center">';
	$out .= '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">';
	$out .= '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>';
	$out .= '</svg>';
	$out .= esc_html__( 'Save Profile', 'wpmatch-free' ) . '</button>';
	$out .= '</div>';
	$out .= '</form>';
	$out .= '</div>';

	// Photo management section.
	$out .= '</div>';
	$out .= '<div class="border-t border-gray-200 dark:border-gray-700">';
	$out .= '<div class="bg-gradient-to-r from-green-500 via-blue-500 to-purple-600 px-6 py-4">';
	$out .= '<h3 class="text-2xl font-bold text-white flex items-center">';
	$out .= '<svg class="w-7 h-7 mr-3" fill="currentColor" viewBox="0 0 20 20">';
	$out .= '<path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>';
	$out .= '</svg>';
	$out .= esc_html__( 'Your Photos', 'wpmatch-free' ) . '</h3>';
	$out .= '</div>';
	$out .= '<div class="p-6">';

	// Display current photos.
	if ( ! empty( $photos ) ) {
		$out .= '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">';
		foreach ( $photos as $photo ) {
			$attachment_id = (int) $photo['attachment_id'];
			$image_data    = wp_get_attachment_image_src( $attachment_id, 'medium' );
			$image_url     = $image_data ? $image_data[0] : '';

			$status_color = '';
			switch ( $photo['status'] ) {
				case 'approved':
					$status_color = 'bg-green-500';
					break;
				case 'pending':
					$status_color = 'bg-yellow-500';
					break;
				case 'rejected':
					$status_color = 'bg-red-500';
					break;
				default:
					$status_color = 'bg-gray-500';
			}

			$out .= '<div class="relative group bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden transition-transform duration-200 hover:scale-105" data-photo-id="' . esc_attr( $photo['id'] ) . '">';

			if ( $image_url ) {
				$out .= '<div class="aspect-square relative overflow-hidden">';
				$out .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr__( 'Profile photo', 'wpmatch-free' ) . '" class="w-full h-full object-cover" />';

				// Status badge
				$out .= '<div class="absolute top-2 left-2 ' . $status_color . ' text-white text-xs font-bold px-2 py-1 rounded-full">';
				$out .= esc_html( ucfirst( $photo['status'] ) );
				$out .= '</div>';

				// Primary badge
				if ( $photo['is_primary'] ) {
					$out .= '<div class="absolute top-2 right-2 bg-indigo-500 text-white text-xs font-bold px-2 py-1 rounded-full flex items-center">';
					$out .= '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">';
					$out .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>';
					$out .= '</svg>';
					$out .= esc_html__( 'Primary', 'wpmatch-free' );
					$out .= '</div>';
				}

				// Action overlay
				$out .= '<div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">';
				$out .= '<div class="flex space-x-2">';
				if ( ! $photo['is_primary'] ) {
					$out .= '<button type="button" class="wpmf-set-primary bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center" data-photo-id="' . esc_attr( $photo['id'] ) . '">';
					$out .= '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">';
					$out .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>';
					$out .= '</svg>';
					$out .= esc_html__( 'Primary', 'wpmatch-free' );
					$out .= '</button>';
				}
				$out .= '<button type="button" class="wpmf-delete-photo bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center" data-photo-id="' . esc_attr( $photo['id'] ) . '">';
				$out .= '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">';
				$out .= '<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>';
				$out .= '</svg>';
				$out .= esc_html__( 'Delete', 'wpmatch-free' );
				$out .= '</button>';
				$out .= '</div>';
				$out .= '</div>';
				$out .= '</div>';
			}
			$out .= '</div>';
		}
		$out .= '</div>';
	} else {
		$out .= '<div class="text-center py-12">';
		$out .= '<div class="text-6xl mb-4">📷</div>';
		$out .= '<h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'No photos yet', 'wpmatch-free' ) . '</h4>';
		$out .= '<p class="text-gray-500 dark:text-gray-400 mb-6">' . esc_html__( 'Upload your first photo to get started!', 'wpmatch-free' ) . '</p>';
		$out .= '</div>';
	}

	// Photo upload form.
	$photo_limit = apply_filters( 'wpmf_photo_limit_per_user', 10 );
	$can_upload  = count( $photos ) < $photo_limit;

	if ( $can_upload ) {
		$out .= '<div class="border-t border-gray-200 dark:border-gray-700 pt-6">';
		$out .= '<div class="bg-gradient-to-r from-emerald-50 to-cyan-50 dark:from-emerald-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-800">';
		$out .= '<h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center">';
		$out .= '<svg class="w-5 h-5 mr-2 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= esc_html__( 'Add New Photo', 'wpmatch-free' ) . '</h4>';
		$out .= '<form class="space-y-4" method="post" enctype="multipart/form-data">';
		$out .= '<input type="hidden" name="wpmf_photo_nonce" value="' . esc_attr( $photo_nonce ) . '" />';
		$out .= '<input type="hidden" name="wpmf_action" value="upload_photo" />';
		$out .= '<div>';
		$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'Choose Photo', 'wpmatch-free' ) . '</label>';
		$out .= '<div class="relative">';
		$out .= '<input type="file" name="wpmf_photo" accept="image/*" required class="block w-full text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer bg-white dark:bg-gray-700 focus:outline-none focus:border-indigo-500 file:mr-4 file:py-3 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-500 file:text-white hover:file:bg-indigo-600 transition-colors" />';
		$out .= '</div>';
		$out .= '</div>';
		$out .= '<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">';
		$out .= '<div class="flex items-center">';
		$out .= '<svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= '<p class="text-sm text-blue-800 dark:text-blue-300">';
		$out .= sprintf(
			/* translators: 1: current count, 2: max limit */
			esc_html__( 'You can upload %1$d more photos (limit: %2$d)', 'wpmatch-free' ),
			$photo_limit - count( $photos ),
			$photo_limit
		);
		$out .= '</p>';
		$out .= '</div>';
		$out .= '</div>';
		$out .= '<div class="flex justify-end">';
		$out .= '<button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-emerald-500/30 flex items-center">';
		$out .= '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= esc_html__( 'Upload Photo', 'wpmatch-free' ) . '</button>';
		$out .= '</div>';
		$out .= '</form>';
		$out .= '</div>';
		$out .= '</div>';
	} else {
		$out .= '<div class="border-t border-gray-200 dark:border-gray-700 pt-6">';
		$out .= '<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 text-center">';
		$out .= '<div class="text-4xl mb-4">🚫</div>';
		$out .= '<h4 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-2">' . esc_html__( 'Photo limit reached', 'wpmatch-free' ) . '</h4>';
		$out .= '<p class="text-red-600 dark:text-red-400">';
		$out .= sprintf(
			/* translators: %d: photo limit */
			esc_html__( 'You have reached the maximum of %d photos. Delete a photo to add a new one.', 'wpmatch-free' ),
			$photo_limit
		);
		$out .= '</p>';
		$out .= '</div>';
		$out .= '</div>';
	}

	$out .= '</div>'; // Photo management content
	$out .= '</div>'; // Photo management section
	$out .= '</div>'; // Main container

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
	$out = '<div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">';

	// Header
	$out .= '<div class="bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600 px-6 py-4">';
	$out .= '<h3 class="text-2xl font-bold text-white flex items-center">';
	$out .= '<svg class="w-7 h-7 mr-3" fill="currentColor" viewBox="0 0 20 20">';
	$out .= '<path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>';
	$out .= '</svg>';
	$out .= esc_html__( 'Find Your Match', 'wpmatch-free' ) . '</h3>';
	$out .= '</div>';

	// Form content
	$out .= '<div class="p-6">';
	$out .= '<form class="space-y-6" method="get">';

	// Region and photo filter row
	$out .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
	$out .= '<div>';
	$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">' . esc_html__( 'Region', 'wpmatch-free' ) . '</label>';
	$out .= '<input name="region" value="' . esc_attr( wp_unslash( $_GET['region'] ?? '' ) ) . '" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200" placeholder="' . esc_attr__( 'Enter region or city...', 'wpmatch-free' ) . '" />';
	$out .= '</div>';
	$out .= '<div class="flex items-end">';
	$out .= '<label class="flex items-center space-x-3 cursor-pointer p-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors w-full">';
	$out .= '<input type="checkbox" name="has_photo" ' . checked( ! empty( $_GET['has_photo'] ), true, false ) . ' class="w-5 h-5 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 focus:ring-2" />';
	$out .= '<div>';
	$out .= '<div class="text-sm font-semibold text-gray-900 dark:text-gray-100">' . esc_html__( 'Has Photos', 'wpmatch-free' ) . '</div>';
	$out .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . esc_html__( 'Show only verified profiles with photos', 'wpmatch-free' ) . '</div>';
	$out .= '</div>';
	$out .= '</label>';
	$out .= '</div>';
	$out .= '</div>';

	// Age range section
	$out .= '<div>';
	$out .= '<label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">' . esc_html__( 'Age Range', 'wpmatch-free' ) . '</label>';
	$out .= '<div class="grid grid-cols-2 gap-4">';
	$out .= '<div>';
	$out .= '<input type="number" name="age_min" value="' . esc_attr( wp_unslash( $_GET['age_min'] ?? '' ) ) . '" placeholder="' . esc_attr__( 'Min age', 'wpmatch-free' ) . '" min="18" max="100" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200" />';
	$out .= '</div>';
	$out .= '<div>';
	$out .= '<input type="number" name="age_max" value="' . esc_attr( wp_unslash( $_GET['age_max'] ?? '' ) ) . '" placeholder="' . esc_attr__( 'Max age', 'wpmatch-free' ) . '" min="18" max="100" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200" />';
	$out .= '</div>';
	$out .= '</div>';
	$out .= '</div>';

	// Search button
	$out .= '<div class="flex justify-center pt-4">';
	$out .= '<button type="submit" class="bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600 hover:from-blue-600 hover:via-indigo-600 hover:to-purple-700 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-indigo-500/30 flex items-center text-lg">';
	$out .= '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">';
	$out .= '<path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>';
	$out .= '</svg>';
	$out .= esc_html__( 'Search Profiles', 'wpmatch-free' ) . '</button>';
	$out .= '</div>';

	$out .= '</form>';
	$out .= '</div>';
	$out .= '</div>';
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
	$out = '<div class="max-w-7xl mx-auto">';

	// Results summary
	if ( $pagination_data['total_items'] > 0 ) {
		$out .= '<div class="mb-8">';
		$out .= '<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">';
		$out .= '<div class="flex items-center justify-between">';
		$out .= '<div class="flex items-center space-x-3">';
		$out .= '<div class="bg-gradient-to-r from-green-500 to-emerald-600 p-3 rounded-full">';
		$out .= '<svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= '</div>';
		$out .= '<div>';
		$out .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">';
		$out .= sprintf(
			/* translators: 1: total results */
			esc_html__( 'Found %d matching profiles', 'wpmatch-free' ),
			$pagination_data['total_items']
		);
		$out .= '</h3>';
		$out .= '<p class="text-sm text-gray-500 dark:text-gray-400">' . esc_html__( 'Browse through these amazing profiles and find your perfect match!', 'wpmatch-free' ) . '</p>';
		$out .= '</div>';
		$out .= '</div>';
		$out .= '<div class="text-sm text-gray-600 dark:text-gray-300 flex items-center">';
		$out .= '<span class="bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 px-3 py-1 rounded-full font-medium">';
		$out .= sprintf( esc_html__( 'Page %1$d of %2$d', 'wpmatch-free' ), $pagination_data['current_page'], $pagination_data['total_pages'] );
		$out .= '</span>';
		$out .= '</div>';
		$out .= '</div>';
		$out .= '</div>';
	}

	// Results grid
	$out .= '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">';
	foreach ( $pagination_data['results'] as $r ) {
		$out .= '<div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 overflow-hidden border border-gray-200 dark:border-gray-700 cursor-pointer" data-user-id="' . esc_attr( $r['user_id'] ) . '">';

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

		// Photo section
		$out .= '<div class="relative aspect-square overflow-hidden">';
		if ( $primary_photo ) {
			$attachment_id = (int) $primary_photo['attachment_id'];
			$image_data    = wp_get_attachment_image_src( $attachment_id, 'medium' );
			if ( $image_data ) {
				$out .= '<img src="' . esc_url( $image_data[0] ) . '" alt="' . esc_attr( $r['headline'] ?? __( 'Profile photo', 'wpmatch-free' ) ) . '" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />';
			}
		} else {
			// Default placeholder if no photo
			$out .= '<div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">';
			$out .= '<div class="text-6xl opacity-50">📷</div>';
			$out .= '</div>';
		}

		// Verified badge overlay
		if ( ! empty( $r['verified'] ) ) {
			$out .= '<div class="absolute top-3 right-3 bg-green-500 text-white p-2 rounded-full shadow-lg">';
			$out .= '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">';
			$out .= '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>';
			$out .= '</svg>';
			$out .= '</div>';
		}
		$out .= '</div>';

		// Card content
		$out .= '<div class="p-4">';
		$out .= '<div class="space-y-2">';

		// Headline
		if ( ! empty( $r['headline'] ) ) {
			$out .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">' . esc_html( $r['headline'] ) . '</h3>';
		}

		// Location and age
		$out .= '<div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">';
		if ( ! empty( $r['region'] ) ) {
			$out .= '<div class="flex items-center">';
			$out .= '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">';
			$out .= '<path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>';
			$out .= '</svg>';
			$out .= '<span>' . esc_html( $r['region'] ) . '</span>';
			$out .= '</div>';
		}
		if ( ! empty( $r['age'] ) ) {
			$out .= '<div class="flex items-center">';
			$out .= '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">';
			$out .= '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>';
			$out .= '</svg>';
			$out .= '<span>' . esc_html( $r['age'] ) . ' ' . esc_html__( 'years', 'wpmatch-free' ) . '</span>';
			$out .= '</div>';
		}
		$out .= '</div>';

		// Call to action
		$out .= '<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">';
		$out .= '<div class="flex items-center justify-between">';
		$out .= '<span class="text-xs text-gray-500 dark:text-gray-400">' . esc_html__( 'Click to view profile', 'wpmatch-free' ) . '</span>';
		$out .= '<svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= '</div>';
		$out .= '</div>';

		$out .= '</div>'; // space-y-2
		$out .= '</div>'; // p-4
		$out .= '</div>'; // card
	}

	if ( empty( $pagination_data['results'] ) ) {
		$out .= '<div class="col-span-full">';
		$out .= '<div class="text-center py-16">';
		$out .= '<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 max-w-md mx-auto border border-gray-200 dark:border-gray-700">';
		$out .= '<div class="text-6xl mb-6">🔍</div>';
		$out .= '<h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">' . esc_html__( 'No matches found', 'wpmatch-free' ) . '</h3>';
		$out .= '<p class="text-gray-600 dark:text-gray-400 mb-6">' . esc_html__( 'No profiles found matching your criteria. Try adjusting your search filters to discover more people.', 'wpmatch-free' ) . '</p>';
		$out .= '<div class="space-y-3 text-sm text-gray-500 dark:text-gray-400">';
		$out .= '<p class="flex items-center justify-center">';
		$out .= '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= esc_html__( 'Try expanding your age range', 'wpmatch-free' );
		$out .= '</p>';
		$out .= '<p class="flex items-center justify-center">';
		$out .= '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= esc_html__( 'Consider different regions', 'wpmatch-free' );
		$out .= '</p>';
		$out .= '<p class="flex items-center justify-center">';
		$out .= '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">';
		$out .= '<path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>';
		$out .= '</svg>';
		$out .= esc_html__( 'Remove photo requirement', 'wpmatch-free' );
		$out .= '</p>';
		$out .= '</div>';
		$out .= '</div>';
		$out .= '</div>';
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
	<div id="wpmf-messages-container" class="max-w-7xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
		<!-- Header -->
		<div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 px-6 py-4 flex items-center justify-between">
			<h3 class="text-2xl font-bold text-white flex items-center">
				<svg class="w-7 h-7 mr-3" fill="currentColor" viewBox="0 0 20 20">
					<path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
				</svg>
				<?php esc_html_e( 'Messages', 'wpmatch-free' ); ?>
			</h3>
			<div id="wpmf-unread-count" class="hidden bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
				<span>0</span>
			</div>
		</div>
		
		<div class="flex h-96 lg:h-[600px]">
			<!-- Sidebar -->
			<div class="w-full lg:w-1/3 border-r border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900" id="wpmf-conversations-list">
				<div class="flex items-center justify-center h-32 text-gray-500 dark:text-gray-400">
					<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500 mr-3"></div>
					<?php esc_html_e( 'Loading conversations...', 'wpmatch-free' ); ?>
				</div>
			</div>
			
			<!-- Main Messages Area -->
			<div class="hidden lg:flex flex-1 flex-col" id="wpmf-messages-main">
				<div class="flex-1 flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
					<div class="text-center p-8">
						<div class="text-6xl mb-4">💬</div>
						<h4 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
							<?php esc_html_e( 'Select a conversation', 'wpmatch-free' ); ?>
						</h4>
						<p class="text-gray-500 dark:text-gray-400">
							<?php esc_html_e( 'Choose a conversation from the sidebar to start messaging.', 'wpmatch-free' ); ?>
						</p>
					</div>
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
	<div id="wpmf-conversation-container" class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[600px]" data-thread-id="<?php echo esc_attr( $thread_id ); ?>">
		<!-- Conversation Header -->
		<div id="wpmf-conversation-header" class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 px-6 py-4 border-b border-white/20">
			<div class="flex items-center justify-center h-12 text-white">
				<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-3"></div>
				<?php esc_html_e( 'Loading conversation...', 'wpmatch-free' ); ?>
			</div>
		</div>
		
		<!-- Messages List -->
		<div id="wpmf-messages-list" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gradient-to-b from-gray-50 to-white dark:from-gray-700 dark:to-gray-800">
			<div class="flex items-center justify-center h-32 text-gray-500 dark:text-gray-400">
				<div class="animate-pulse flex items-center">
					<div class="rounded-full bg-gray-300 dark:bg-gray-600 h-4 w-4 mr-3"></div>
					<?php esc_html_e( 'Loading messages...', 'wpmatch-free' ); ?>
				</div>
			</div>
		</div>
		
		<!-- Message Compose -->
		<div id="wpmf-message-compose" class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
			<form id="wpmf-send-message-form" class="flex items-end space-x-3">
				<div class="flex-1">
					<textarea 
						id="wpmf-message-input" 
						name="message" 
						placeholder="<?php esc_attr_e( 'Type your message...', 'wpmatch-free' ); ?>"
						rows="2"
						maxlength="2000"
						class="w-full resize-none rounded-2xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
						required
					></textarea>
				</div>
				<button type="submit" class="wpmf-send-button bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white px-6 py-3 rounded-2xl font-semibold transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-indigo-500/30 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
					<span class="wpmf-send-text flex items-center">
						<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
							<path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
						</svg>
						<?php esc_html_e( 'Send', 'wpmatch-free' ); ?>
					</span>
					<span class="wpmf-send-loading hidden flex items-center">
						<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
						<?php esc_html_e( 'Sending...', 'wpmatch-free' ); ?>
					</span>
				</button>
			</form>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'wpmf_conversation', 'wpmf_sc_conversation' );
