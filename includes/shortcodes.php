<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

function wpmf_sc_profile_edit() {
	if ( ! is_user_logged_in() ) {
		return esc_html__( 'Please log in.', 'wpmatch-free' ); }
	$u        = get_current_user_id();
	$p        = wpmf_profile_get_by_user_id( $u );
	$gender   = esc_attr( $p['gender'] ?? '' );
	$region   = esc_attr( $p['region'] ?? '' );
	$headline = esc_attr( $p['headline'] ?? '' );
	$bio      = esc_textarea( $p['bio'] ?? '' );
	$nonce    = wp_create_nonce( 'wpmf_profile_save' );
	$out      = '<form class="wpmf-profile-edit" method="post">';
	$out     .= '<input type="hidden" name="wpmf_nonce" value="' . esc_attr( $nonce ) . '" />';
	$out     .= '<p><label>' . esc_html__( 'Gender', 'wpmatch-free' ) . ' <input name="gender" value="' . $gender . '"/></label></p>';
	$out     .= '<p><label>' . esc_html__( 'Region', 'wpmatch-free' ) . ' <input name="region" value="' . $region . '"/></label></p>';
	$out     .= '<p><label>' . esc_html__( 'Headline', 'wpmatch-free' ) . ' <input name="headline" value="' . $headline . '"/></label></p>';
	$out     .= '<p><label>' . esc_html__( 'Bio', 'wpmatch-free' ) . '<br/><textarea name="bio" rows="5">' . $bio . '</textarea></label></p>';
	$out     .= '<p><button type="submit">' . esc_html__( 'Save', 'wpmatch-free' ) . '</button></p>';
	$out     .= '</form>';
	return $out;
}
add_shortcode( 'wpmf_profile_edit', 'wpmf_sc_profile_edit' );

function wpmf_handle_profile_edit_post() {
	if ( ! is_user_logged_in() ) {
		return; }
	if ( empty( $_POST['wpmf_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wpmf_nonce'] ), 'wpmf_profile_save' ) ) {
		return; }
	$u      = get_current_user_id();
	$data   = array(
		'gender'   => sanitize_text_field( wp_unslash( $_POST['gender'] ?? '' ) ),
		'region'   => sanitize_text_field( wp_unslash( $_POST['region'] ?? '' ) ),
		'headline' => sanitize_text_field( wp_unslash( $_POST['headline'] ?? '' ) ),
		'bio'      => wp_kses_post( wp_unslash( $_POST['bio'] ?? '' ) ),
	);
	$exists = wpmf_profile_get_by_user_id( $u );
	if ( $exists ) {
		wpmf_profile_update_by_user_id( $u, $data ); } else {
		$data['user_id'] = $u;
		wpmf_profile_create( $data ); }
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
	$current_page = WPMF_Pagination::get_current_page();
	$per_page     = WPMF_Pagination::get_per_page();
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
		$out .= '<div class="wpmf-card-headline">' . esc_html( $r['headline'] ?? '' ) . '</div>';
		$out .= '<div class="wpmf-card-region">' . esc_html( $r['region'] ?? '' ) . '</div>';
		if ( ! empty( $r['age'] ) ) {
			$out .= '<div class="wpmf-card-age">' . esc_html( $r['age'] ) . ' years old</div>';
		}
		if ( ! empty( $r['verified'] ) ) {
			$out .= '<div class="wpmf-card-verified">✓ ' . esc_html__( 'Verified', 'wpmatch-free' ) . '</div>';
		}
		$out .= '</div>';
	}
	
	if ( empty( $pagination_data['results'] ) ) {
		$out .= '<div class="wpmf-no-results">';
		$out .= esc_html__( 'No profiles found matching your criteria. Try adjusting your search filters.', 'wpmatch-free' );
		$out .= '</div>';
	}
	
	$out .= '</div>'; // .wpmf-search-results
	
	// Pagination
	$url_args = array_filter( array(
		'region'    => $_GET['region'] ?? '',
		'age_min'   => $_GET['age_min'] ?? '',
		'age_max'   => $_GET['age_max'] ?? '',
		'verified'  => $_GET['verified'] ?? '',
		'has_photo' => $_GET['has_photo'] ?? '',
	) );
	
	$out .= WPMF_Pagination::render_pagination( $pagination_data, '', $url_args );
	
	$out .= '</div>'; // .wpmf-search-container
	
	return $out;
}
add_shortcode( 'wpmf_search_results', 'wpmf_sc_search_results' );
