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
				$out .= esc_html__( 'Delete', 'wpmatch-free' ) . '</button>';
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
	// Enqueue profile views tracking for automatic logging
	if ( is_user_logged_in() ) {
		wp_enqueue_script( 'wpmf-profile-views' );
		wp_localize_script(
			'wpmf-profile-views',
			'wpmfProfileViews',
			array(
				'apiUrl' => rest_url( 'wpmatch-free/v1' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'userId' => get_current_user_id(),
			)
		);
	}

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
		$out .= '<div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 overflow-hidden border border-gray-200 dark:border-gray-700 cursor-pointer" data-user-id="' . esc_attr( $r['user_id'] ) . '" data-profile-user-id="' . esc_attr( $r['user_id'] ) . '" data-view-source="search">';

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

		// Call to action with call buttons
		$out .= '<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">';
		
		// Add call buttons if user is logged in
		if ( is_user_logged_in() && get_current_user_id() !== (int) $r['user_id'] ) {
			$out .= '<div class="flex gap-2 mb-3">';
			$out .= '<button class="wpmf-call-btn audio-call flex-1" data-recipient-id="' . esc_attr( $r['user_id'] ) . '" data-call-type="audio" style="font-size: 0.8em; padding: 8px 12px;">';
			$out .= '<i class="fas fa-phone"></i> Audio';
			$out .= '</button>';
			$out .= '<button class="wpmf-call-btn video-call flex-1" data-recipient-id="' . esc_attr( $r['user_id'] ) . '" data-call-type="video" style="font-size: 0.8em; padding: 8px 12px;">';
			$out .= '<i class="fas fa-video"></i> Video';
			$out .= '</button>';
			$out .= '</div>';
			
			// Enqueue WebRTC assets when call buttons are present
			wp_enqueue_script( 'wpmf-webrtc-calls' );
			wp_enqueue_style( 'wpmf-webrtc-calls' );
		}
		
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
					<path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>';
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
							<path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14a1 1 0 00-.36-1.58Z"></path>
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

/**
 * Interaction buttons shortcode for profile pages.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function wpmf_sc_interaction_buttons( $atts ) {
	$atts = shortcode_atts(
		array(
			'user_id' => 0,
			'show'    => 'all', // all, like, wink, gift, super_like
		),
		$atts
	);

	$user_id = (int) $atts['user_id'];
	if ( ! $user_id || ! get_userdata( $user_id ) ) {
		return '';
	}

	// Don't show buttons for current user
	$current_user_id = get_current_user_id();
	if ( $current_user_id === $user_id || ! $current_user_id ) {
		return '';
	}

	// Check if users are blocked
	if ( wpmf_users_blocked( $current_user_id, $user_id ) ) {
		return '';
	}

	// Enqueue scripts and styles
	wp_enqueue_script( 'wpmf-interactions' );
	wp_enqueue_style( 'wpmf-interactions' );

	$show_buttons = explode( ',', $atts['show'] );
	$show_buttons = array_map( 'trim', $show_buttons );

	$buttons = array();

	if ( in_array( 'all', $show_buttons ) || in_array( 'like', $show_buttons ) ) {
		$buttons[] = sprintf(
			'<button class="wpmf-interaction-btn wpmf-like-btn" data-user-id="%d" type="button">
				<span>❤️</span>
				%s
			</button>',
			$user_id,
			esc_html__( 'Like', 'wpmatch-free' )
		);
	}

	if ( in_array( 'all', $show_buttons ) || in_array( 'super_like', $show_buttons ) ) {
		$buttons[] = sprintf(
			'<button class="wpmf-interaction-btn wpmf-super-like-btn" data-user-id="%d" type="button">
				<span>💖</span>
				%s
			</button>',
			$user_id,
			esc_html__( 'Super Like', 'wpmatch-free' )
		);
	}

	if ( in_array( 'all', $show_buttons ) || in_array( 'wink', $show_buttons ) ) {
		$buttons[] = sprintf(
			'<button class="wpmf-interaction-btn wpmf-wink-btn" data-user-id="%d" type="button">
				<span>😉</span>
				%s
			</button>',
			$user_id,
			esc_html__( 'Wink', 'wpmatch-free' )
		);
	}

	if ( in_array( 'all', $show_buttons ) || in_array( 'gift', $show_buttons ) ) {
		$buttons[] = sprintf(
			'<button class="wpmf-interaction-btn wpmf-gift-btn" data-user-id="%d" type="button">
				<span>🎁</span>
				%s
			</button>',
			$user_id,
			esc_html__( 'Send Gift', 'wpmatch-free' )
		);
	}

	if ( empty( $buttons ) ) {
		return '';
	}

	return sprintf(
		'<div class="wpmf-interaction-buttons">%s</div>',
		implode( '', $buttons )
	);
}
add_shortcode( 'wpmf_interaction_buttons', 'wpmf_sc_interaction_buttons' );

/**
 * Received interactions display shortcode.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function wpmf_sc_interactions_received( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'Please log in to view your interactions.', 'wpmatch-free' ) . '</p>';
	}

	$atts = shortcode_atts(
		array(
			'limit'  => 20,
			'type'   => '', // like, wink, gift, super_like
			'status' => '', // sent, seen
			'title'  => __( 'Recent Interactions', 'wpmatch-free' ),
		),
		$atts
	);

	// Enqueue scripts and styles
	wp_enqueue_script( 'wpmf-interactions' );
	wp_enqueue_style( 'wpmf-interactions' );

	$output = '<div class="wpmf-interactions-widget">';

	if ( ! empty( $atts['title'] ) ) {
		$output .= sprintf( '<h3 class="wpmf-interactions-title">%s</h3>', esc_html( $atts['title'] ) );
	}

	$output .= '<div class="wpmf-interactions-received" data-limit="' . esc_attr( $atts['limit'] ) . '" data-type="' . esc_attr( $atts['type'] ) . '" data-status="' . esc_attr( $atts['status'] ) . '">';
	$output .= '<div class="wpmf-interactions-loading">' . esc_html__( 'Loading interactions...', 'wpmatch-free' ) . '</div>';
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}
add_shortcode( 'wpmf_interactions_received', 'wpmf_sc_interactions_received' );

/**
 * Interaction stats shortcode.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function wpmf_sc_interaction_stats( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'show_title' => 'yes',
		),
		$atts
	);

	$user_id = get_current_user_id();
	$stats   = wpmf_get_interaction_stats( $user_id );

	$output = '<div class="wpmf-interaction-stats">';

	if ( 'yes' === $atts['show_title'] ) {
		$output .= '<h3 class="wpmf-stats-title">' . esc_html__( 'Your Interaction Stats', 'wpmatch-free' ) . '</h3>';
	}

	$output .= '<div class="wpmf-stats-grid">';

	// Sent stats
	$sent_total = array_sum( $stats['sent'] );
	$output    .= sprintf(
		'<div class="wpmf-stat-card">
			<div class="wpmf-stat-number">%d</div>
			<div class="wpmf-stat-label">%s</div>
		</div>',
		$sent_total,
		esc_html__( 'Sent', 'wpmatch-free' )
	);

	// Received stats
	$received_total = array_sum( $stats['received'] );
	$output        .= sprintf(
		'<div class="wpmf-stat-card">
			<div class="wpmf-stat-number">%d</div>
			<div class="wpmf-stat-label">%s</div>
		</div>',
		$received_total,
		esc_html__( 'Received', 'wpmatch-free' )
	);

	// Matches
	$output .= sprintf(
		'<div class="wpmf-stat-card wpmf-stat-highlight">
			<div class="wpmf-stat-number">%d</div>
			<div class="wpmf-stat-label">%s</div>
		</div>',
		$stats['matches'],
		esc_html__( 'Matches', 'wpmatch-free' )
	);

	$output .= '</div>';
	$output .= '</div>';

	return $output;
}
add_shortcode( 'wpmf_interaction_stats', 'wpmf_sc_interaction_stats' );

/**
 * Profile view statistics shortcode.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function wpmf_sc_profile_view_stats( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'Please log in to view your profile statistics.', 'wpmatch-free' ) . '</p>';
	}

	$atts = shortcode_atts(
		array(
			'days'       => 30,
			'show_title' => 'yes',
		),
		$atts
	);

	$user_id = get_current_user_id();
	$days    = absint( $atts['days'] );

	// Enqueue profile views assets
	wp_enqueue_script( 'wpmf-profile-views', plugin_dir_url( __DIR__ ) . 'assets/profile-views.js', array(), '1.0.0', true );
	wp_enqueue_style( 'wpmf-profile-views', plugin_dir_url( __DIR__ ) . 'assets/profile-views.css', array(), '1.0.0' );
	wp_localize_script(
		'wpmf-profile-views',
		'wpmfProfileViews',
		array(
			'apiUrl' => rest_url( 'wpmatch-free/v1' ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'userId' => $user_id,
		)
	);

	$output = '<div class="wpmf-view-stats">';

	if ( 'yes' === $atts['show_title'] ) {
		$output .= '<div class="wpmf-views-section-header">';
		$output .= '<h3 class="wpmf-views-section-title">' . esc_html__( 'Profile View Statistics', 'wpmatch-free' ) . '</h3>';
		$output .= '<div class="wpmf-view-filters">';
		$output .= '<div class="wpmf-view-filter-group">';
		$output .= '<label>' . esc_html__( 'Period', 'wpmatch-free' ) . '</label>';
		$output .= '<select class="wpmf-view-filter wpmf-days-filter">';
		$output .= '<option value="7"' . selected( $days, 7, false ) . '>' . esc_html__( 'Last 7 days', 'wpmatch-free' ) . '</option>';
		$output .= '<option value="30"' . selected( $days, 30, false ) . '>' . esc_html__( 'Last 30 days', 'wpmatch-free' ) . '</option>';
		$output .= '<option value="90"' . selected( $days, 90, false ) . '>' . esc_html__( 'Last 3 months', 'wpmatch-free' ) . '</option>';
		$output .= '<option value="0"' . selected( $days, 0, false ) . '>' . esc_html__( 'All time', 'wpmatch-free' ) . '</option>';
		$output .= '</select>';
		$output .= '</div>';
		$output .= '<button type="button" class="wpmf-refresh-views">' . esc_html__( 'Refresh', 'wpmatch-free' ) . '</button>';
		$output .= '</div>';
	}

	// Stats will be loaded via JavaScript
	$output .= '<div class="wpmf-loading">' . esc_html__( 'Loading statistics...', 'wpmatch-free' ) . '</div>';
	$output .= '</div>';

	return $output;
}
add_shortcode( 'wpmf_profile_view_stats', 'wpmf_sc_profile_view_stats' );

/**
 * "Who viewed me" shortcode.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function wpmf_sc_who_viewed_me( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'Please log in to see who viewed your profile.', 'wpmatch-free' ) . '</p>';
	}

	$atts = shortcode_atts(
		array(
			'days'       => 30,
			'limit'      => 20,
			'show_title' => 'yes',
		),
		$atts
	);

	$user_id = get_current_user_id();

	// Enqueue profile views assets
	wp_enqueue_script( 'wpmf-profile-views', plugin_dir_url( __DIR__ ) . 'assets/profile-views.js', array(), '1.0.0', true );
	wp_enqueue_style( 'wpmf-profile-views', plugin_dir_url( __DIR__ ) . 'assets/profile-views.css', array(), '1.0.0' );
	wp_localize_script(
		'wpmf-profile-views',
		'wpmfProfileViews',
		array(
			'apiUrl' => rest_url( 'wpmatch-free/v1' ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'userId' => $user_id,
		)
	);

	$output = '<div class="wpmf-who-viewed-me">';

	if ( 'yes' === $atts['show_title'] ) {
		$output .= '<div class="wpmf-views-section-header">';
		$output .= '<h3 class="wpmf-views-section-title">' . esc_html__( 'Who Viewed My Profile', 'wpmatch-free' ) . '</h3>';
		$output .= '</div>';
	}

	// Filter controls
	$output .= '<div class="wpmf-view-filters">';
	$output .= '<div class="wpmf-view-filter-group">';
	$output .= '<label>' . esc_html__( 'Period', 'wpmatch-free' ) . '</label>';
	$output .= '<select class="wpmf-view-filter wpmf-days-filter">';
	$output .= '<option value="7">' . esc_html__( 'Last 7 days', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="30" selected>' . esc_html__( 'Last 30 days', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="90">' . esc_html__( 'Last 3 months', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="0">' . esc_html__( 'All time', 'wpmatch-free' ) . '</option>';
	$output .= '</select>';
	$output .= '</div>';

	$output .= '<div class="wpmf-view-filter-group">';
	$output .= '<label>' . esc_html__( 'Results', 'wpmatch-free' ) . '</label>';
	$output .= '<select class="wpmf-view-filter wpmf-limit-filter">';
	$output .= '<option value="10">10</option>';
	$output .= '<option value="20" selected>20</option>';
	$output .= '<option value="50">50</option>';
	$output .= '</select>';
	$output .= '</div>';

	$output .= '<div class="wpmf-view-filter-group">';
	$output .= '<label>' . esc_html__( 'Source', 'wpmatch-free' ) . '</label>';
	$output .= '<select class="wpmf-view-filter wpmf-source-filter">';
	$output .= '<option value="">' . esc_html__( 'All sources', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="profile">' . esc_html__( 'Profile page', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="search">' . esc_html__( 'Search results', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="messaging">' . esc_html__( 'Messaging', 'wpmatch-free' ) . '</option>';
	$output .= '</select>';
	$output .= '</div>';

	$output .= '<button type="button" class="wpmf-refresh-views">' . esc_html__( 'Refresh', 'wpmatch-free' ) . '</button>';
	$output .= '</div>';

	// Content will be loaded via JavaScript
	$output .= '<div class="wpmf-loading">' . esc_html__( 'Loading profile viewers...', 'wpmatch-free' ) . '</div>';
	$output .= '</div>';

	return $output;
}
add_shortcode( 'wpmf_who_viewed_me', 'wpmf_sc_who_viewed_me' );

/**
 * "My profile views" shortcode.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function wpmf_sc_my_profile_views( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'Please log in to see your profile view history.', 'wpmatch-free' ) . '</p>';
	}

	$atts = shortcode_atts(
		array(
			'days'       => 30,
			'limit'      => 20,
			'show_title' => 'yes',
		),
		$atts
	);

	$user_id = get_current_user_id();

	// Enqueue profile views assets
	wp_enqueue_script( 'wpmf-profile-views', plugin_dir_url( __DIR__ ) . 'assets/profile-views.js', array(), '1.0.0', true );
	wp_enqueue_style( 'wpmf-profile-views', plugin_dir_url( __DIR__ ) . 'assets/profile-views.css', array(), '1.0.0' );
	wp_localize_script(
		'wpmf-profile-views',
		'wpmfProfileViews',
		array(
			'apiUrl' => rest_url( 'wpmatch-free/v1' ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'userId' => $user_id,
		)
	);

	$output = '<div class="wpmf-my-views">';

	if ( 'yes' === $atts['show_title'] ) {
		$output .= '<div class="wpmf-views-section-header">';
		$output .= '<h3 class="wpmf-views-section-title">' . esc_html__( 'Profiles I Viewed', 'wpmatch-free' ) . '</h3>';
		$output .= '</div>';
	}

	// Filter controls
	$output .= '<div class="wpmf-view-filters">';
	$output .= '<div class="wpmf-view-filter-group">';
	$output .= '<label>' . esc_html__( 'Period', 'wpmatch-free' ) . '</label>';
	$output .= '<select class="wpmf-view-filter wpmf-days-filter">';
	$output .= '<option value="7">' . esc_html__( 'Last 7 days', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="30" selected>' . esc_html__( 'Last 30 days', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="90">' . esc_html__( 'Last 3 months', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="0">' . esc_html__( 'All time', 'wpmatch-free' ) . '</option>';
	$output .= '</select>';
	$output .= '</div>';

	$output .= '<div class="wpmf-view-filter-group">';
	$output .= '<label>' . esc_html__( 'Results', 'wpmatch-free' ) . '</label>';
	$output .= '<select class="wpmf-view-filter wpmf-limit-filter">';
	$output .= '<option value="10">10</option>';
	$output .= '<option value="20" selected>20</option>';
	$output .= '<option value="50">50</option>';
	$output .= '</select>';
	$output .= '</div>';

	$output .= '<div class="wpmf-view-filter-group">';
	$output .= '<label>' . esc_html__( 'Source', 'wpmatch-free' ) . '</label>';
	$output .= '<select class="wpmf-view-filter wpmf-source-filter">';
	$output .= '<option value="">' . esc_html__( 'All sources', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="profile">' . esc_html__( 'Profile page', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="search">' . esc_html__( 'Search results', 'wpmatch-free' ) . '</option>';
	$output .= '<option value="messaging">' . esc_html__( 'Messaging', 'wpmatch-free' ) . '</option>';
	$output .= '</select>';
	$output .= '</div>';

	$output .= '<button type="button" class="wpmf-refresh-views">' . esc_html__( 'Refresh', 'wpmatch-free' ) . '</button>';
	$output .= '</div>';

	// Content will be loaded via JavaScript
	$output .= '<div class="wpmf-loading">' . esc_html__( 'Loading viewed profiles...', 'wpmatch-free' ) . '</div>';
	$output .= '</div>';

	return $output;
}
add_shortcode( 'wpmf_my_profile_views', 'wpmf_sc_my_profile_views' );

/**
 * Status feed shortcode.
 * Usage: [wpmf_status_feed user_id="" per_page="10" show_composer="yes"]
 */
function wpmf_sc_status_feed( $atts ) {
	$atts = shortcode_atts(
		array(
			'user_id'       => 0,
			'per_page'      => 10,
			'show_composer' => 'yes',
		),
		$atts
	);

	$user_filter   = (int) $atts['user_id'];
	$per_page      = max( 1, min( 50, (int) $atts['per_page'] ) );
	$show_composer = ( 'yes' === strtolower( $atts['show_composer'] ) );
	$current_user  = get_current_user_id();

	// Enqueue JS/CSS (reuse existing blocks css for now, could add dedicated file later)
	wp_enqueue_script( 'wpmf-statuses', plugin_dir_url( __DIR__ ) . 'assets/statuses.js', array( 'jquery' ), WPMATCH_FREE_VERSION, true );
	wp_localize_script(
		'wpmf-statuses',
		'wpmfStatuses',
		array(
			'apiUrl'            => rest_url( 'wpmatch-free/v1' ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'viewerId'          => $current_user,
			'perPage'           => $per_page,
			'userFilter'        => $user_filter,
			'visibilityOptions' => apply_filters( 'wpmf_status_visibility_options', array( 'public', 'members', 'friends', 'private' ) ),
			'messages'          => array(
				'postSuccess'   => __( 'Status posted!', 'wpmatch-free' ),
				'postError'     => __( 'Could not post status.', 'wpmatch-free' ),
				'loadError'     => __( 'Failed to load statuses.', 'wpmatch-free' ),
				'deleteConfirm' => __( 'Delete this status?', 'wpmatch-free' ),
			),
		)
	);

	$output = '<div class="wpmf-status-feed-wrapper" data-user="' . esc_attr( $user_filter ) . '" data-per-page="' . esc_attr( $per_page ) . '">';

	if ( $show_composer && is_user_logged_in() && ( 0 === $user_filter || $user_filter === $current_user ) ) {
		$output .= wpmf_status_composer_html();
	}

	$output .= '<div class="wpmf-status-list space-y-4 mt-4" aria-live="polite" aria-busy="true">';
	$output .= '<div class="text-center text-gray-500 dark:text-gray-400 py-6 loading-indicator">' . esc_html__( 'Loading statuses...', 'wpmatch-free' ) . '</div>';
	$output .= '</div>';
	$output .= '<div class="wpmf-status-feed-actions mt-6 text-center hidden">';
	$output .= '<button type="button" class="wpmf-load-more-statuses px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold shadow hover:from-indigo-600 hover:to-purple-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/30 transition">' . esc_html__( 'Load More', 'wpmatch-free' ) . '</button>';
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}
add_shortcode( 'wpmf_status_feed', 'wpmf_sc_status_feed' );

/**
 * Composer HTML helper (reusable).
 *
 * @return string
 */
function wpmf_status_composer_html() {
	$visibility_options = apply_filters( 'wpmf_status_visibility_options', array( 'public', 'members', 'friends', 'private' ) );
	$out                = '<div class="wpmf-status-composer bg-white dark:bg-gray-800 rounded-2xl shadow p-5 border border-gray-200 dark:border-gray-700">';
	$out               .= '<form class="wpmf-status-form space-y-4" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '">';
	$out               .= '<div class="flex items-start space-x-3">';
	$out               .= '<div class="flex-1">';
	$out               .= '<textarea name="content" maxlength="' . esc_attr( (int) apply_filters( 'wpmf_status_max_length', 500 ) ) . '" rows="3" class="wpmf-status-text w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none" placeholder="' . esc_attr__( 'Share what you are doing or feeling...', 'wpmatch-free' ) . '" required></textarea>';
	$out               .= '<div class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">';
	$out               .= '<span class="char-count">0/' . esc_html( (int) apply_filters( 'wpmf_status_max_length', 500 ) ) . '</span>';
	$out               .= '<span class="daily-limit text-right">' . esc_html__( 'Daily limit applies', 'wpmatch-free' ) . '</span>';
	$out               .= '</div>';
	$out               .= '</div>';
	$out               .= '</div>';
	$out               .= '<div class="flex flex-wrap gap-3 items-center">';
	$out               .= '<select name="visibility" class="wpmf-status-visibility px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">';
	foreach ( $visibility_options as $vis ) {
		$out .= '<option value="' . esc_attr( $vis ) . '">' . esc_html( ucfirst( $vis ) ) . '</option>';
	}
	$out .= '</select>';
	$out .= '<input type="text" name="mood" class="wpmf-status-mood px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm" placeholder="' . esc_attr__( 'Mood (optional)', 'wpmatch-free' ) . '" />';
	$out .= '<button type="submit" class="wpmf-status-submit inline-flex items-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold shadow hover:from-emerald-600 hover:to-teal-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/30 transition">';
	$out .= '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M2.94 2.94a1.5 1.5 0 0 1 1.58-.36l12 4a1.5 1.5 0 0 1 0 2.84l-5.55 1.85a1.5 1.5 0 0 0-.95.95l-1.85 5.55a1.5 1.5 0 0 1-2.84 0l-4-12a1.5 1.5 0 0 1 .36-1.58Z"/></svg>';
	$out .= esc_html__( 'Post Status', 'wpmatch-free' );
	$out .= '</button>';
	$out .= '</div>';
	$out .= '<div class="wpmf-status-feedback mt-2 text-sm" aria-live="polite"></div>';
	$out .= '</form>';
	$out .= '</div>';
	return $out;
}

/**
 * Standalone composer shortcode [wpmf_status_composer]
 */
function wpmf_sc_status_composer( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'You must be logged in to post a status.', 'wpmatch-free' ) . '</p>';
	}
	// Ensure script localized once
	if ( ! wp_script_is( 'wpmf-statuses', 'enqueued' ) ) {
		wp_enqueue_script( 'wpmf-statuses', plugin_dir_url( __DIR__ ) . 'assets/statuses.js', array( 'jquery' ), WPMATCH_FREE_VERSION, true );
		wp_localize_script(
			'wpmf-statuses',
			'wpmfStatuses',
			array(
				'apiUrl'            => rest_url( 'wpmatch-free/v1' ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'viewerId'          => get_current_user_id(),
				'perPage'           => 10,
				'userFilter'        => 0,
				'visibilityOptions' => apply_filters( 'wpmf_status_visibility_options', array( 'public', 'members', 'friends', 'private' ) ),
				'messages'          => array(
					'postSuccess'   => __( 'Status posted!', 'wpmatch-free' ),
					'postError'     => __( 'Could not post status.', 'wpmatch-free' ),
					'loadError'     => __( 'Failed to load statuses.', 'wpmatch-free' ),
					'deleteConfirm' => __( 'Delete this status?', 'wpmatch-free' ),
				),
			)
		);
	}
	return wpmf_status_composer_html();
}
add_shortcode( 'wpmf_status_composer', 'wpmf_sc_status_composer' );

/**
 * Call Button Shortcode
 * 
 * Displays audio/video call buttons for a user
 * Usage: [wpmf_call_buttons user_id="123"]
 */
function wpmf_sc_call_buttons( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>Please log in to access calling features.</p>';
	}
	
	$atts = shortcode_atts( array(
		'user_id' => '',
		'show_audio' => 'true',
		'show_video' => 'true',
		'size' => 'normal',
	), $atts );
	
	$user_id = (int) $atts['user_id'];
	if ( empty( $user_id ) || $user_id === get_current_user_id() ) {
		return '<p>Invalid user for calling.</p>';
	}
	
	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return '<p>User not found.</p>';
	}
	
	// Check if users can communicate
	if ( function_exists( 'wpmf_can_users_communicate' ) && 
		 ! wpmf_can_users_communicate( get_current_user_id(), $user_id ) ) {
		return '<p>Calling is not available with this user.</p>';
	}
	
	// Enqueue assets
	wp_enqueue_script( 'wpmf-webrtc-calls' );
	wp_enqueue_style( 'wpmf-webrtc-calls' );
	
	$size_class = $atts['size'] === 'small' ? 'btn-sm' : '';
	$output = '<div class="wpmf-call-buttons ' . esc_attr( $size_class ) . '">';
	
	if ( $atts['show_audio'] === 'true' ) {
		$output .= '<button class="wpmf-call-btn audio-call" data-recipient-id="' . esc_attr( $user_id ) . '" data-call-type="audio">';
		$output .= '<i class="fas fa-phone"></i> Audio Call';
		$output .= '</button>';
	}
	
	if ( $atts['show_video'] === 'true' ) {
		$output .= '<button class="wpmf-call-btn video-call" data-recipient-id="' . esc_attr( $user_id ) . '" data-call-type="video">';
		$output .= '<i class="fas fa-video"></i> Video Call';
		$output .= '</button>';
	}
	
	$output .= '</div>';
	
	return $output;
}
add_shortcode( 'wpmf_call_buttons', 'wpmf_sc_call_buttons' );

/**
 * Call History Shortcode
 * 
 * Displays user's call history
 * Usage: [wpmf_call_history limit="10" type="all"]
 */
function wpmf_sc_call_history( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>Please log in to view your call history.</p>';
	}
	
	$atts = shortcode_atts( array(
		'limit' => 20,
		'type' => 'all', // all, audio, video
		'show_pagination' => 'true',
	), $atts );
	
	$user_id = get_current_user_id();
	$limit = max( 1, min( 100, (int) $atts['limit'] ) ); // Limit between 1-100
	$offset = 0;
	
	// Handle pagination
	if ( $atts['show_pagination'] === 'true' && isset( $_GET['call_page'] ) ) {
		$page = max( 1, (int) $_GET['call_page'] );
		$offset = ( $page - 1 ) * $limit;
	}
	
	// Get call history
	$calls = wpmf_get_user_call_history( $user_id, $limit, $offset, $atts['type'] );
	
	// Enqueue assets
	wp_enqueue_style( 'wpmf-webrtc-calls' );
	
	if ( empty( $calls ) ) {
		return '<div class="wpmf-call-history"><p>No call history found.</p></div>';
	}
	
	$output = '<div class="wpmf-call-history">';
	$output .= '<h3>Call History</h3>';
	
	foreach ( $calls as $call ) {
		$call_date = wp_date( 'M j, Y g:i A', strtotime( $call->created_at ) );
		$duration = '';
		
		if ( $call->duration_seconds > 0 ) {
			$duration = gmdate( 'H:i:s', $call->duration_seconds );
			$duration = ltrim( $duration, '0:' ); // Remove leading zeros
		}
		
		$status_text = ucfirst( str_replace( '_', ' ', $call->status ) );
		$icon_class = 'fas fa-phone';
		$item_class = $call->direction;
		
		// Determine icon and status
		if ( $call->call_type === 'video' ) {
			$icon_class = 'fas fa-video';
		}
		
		if ( $call->status === 'missed' || $call->status === 'declined' ) {
			$item_class .= ' missed';
		}
		
		$output .= '<div class="wpmf-call-item">';
		$output .= '<div class="wpmf-call-details">';
		$output .= '<div class="wpmf-call-icon ' . esc_attr( $item_class ) . '">';
		$output .= '<i class="' . esc_attr( $icon_class ) . '"></i>';
		$output .= '</div>';
		$output .= '<div class="wpmf-call-info">';
		$output .= '<h4>' . esc_html( $call->other_user['display_name'] ) . '</h4>';
		$output .= '<p>' . esc_html( ucfirst( $call->call_type ) . ' call • ' . ucfirst( $call->direction ) ) . '</p>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="wpmf-call-meta">';
		$output .= '<div>' . esc_html( $call_date ) . '</div>';
		if ( $duration ) {
			$output .= '<div>Duration: ' . esc_html( $duration ) . '</div>';
		}
		$output .= '<div class="call-status">' . esc_html( $status_text ) . '</div>';
		$output .= '</div>';
		$output .= '</div>';
	}
	
	// Pagination
	if ( $atts['show_pagination'] === 'true' && count( $calls ) === $limit ) {
		$current_page = isset( $_GET['call_page'] ) ? max( 1, (int) $_GET['call_page'] ) : 1;
		$next_page = $current_page + 1;
		$prev_page = max( 1, $current_page - 1 );
		
		$output .= '<div class="wpmf-pagination">';
		
		if ( $current_page > 1 ) {
			$prev_url = add_query_arg( 'call_page', $prev_page );
			$output .= '<a href="' . esc_url( $prev_url ) . '" class="btn btn-secondary">Previous</a>';
		}
		
		if ( count( $calls ) === $limit ) {
			$next_url = add_query_arg( 'call_page', $next_page );
			$output .= '<a href="' . esc_url( $next_url ) . '" class="btn btn-secondary">Next</a>';
		}
		
		$output .= '</div>';
	}
	
	$output .= '</div>';
	
	return $output;
}
add_shortcode( 'wpmf_call_history', 'wpmf_sc_call_history' );

/**
 * Active Calls Widget Shortcode
 * 
 * Shows any active calls for the current user
 * Usage: [wpmf_active_calls]
 */
function wpmf_sc_active_calls( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	
	// This will be handled by JavaScript to show real-time active calls
	wp_enqueue_script( 'wpmf-webrtc-calls' );
	wp_enqueue_style( 'wpmf-webrtc-calls' );
	
	return '<div id="wpmf-active-calls-widget" class="wpmf-active-calls-widget"></div>';
}
add_shortcode( 'wpmf_active_calls', 'wpmf_sc_active_calls' );
