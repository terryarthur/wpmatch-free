<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

function wpmf_register_blocks() {
	register_block_type( 'wpmf/profile-edit', array( 'render_callback' => 'wpmf_block_profile_edit' ) );
	register_block_type( 'wpmf/search-form', array( 'render_callback' => 'wpmf_block_search_form' ) );
	register_block_type( 'wpmf/search-results', array( 'render_callback' => 'wpmf_block_search_results' ) );
}
add_action( 'init', 'wpmf_register_blocks' );

function wpmf_block_profile_edit( $attrs = array(), $content = '' ) {
	return do_shortcode( '[wpmf_profile_edit]' ); }
function wpmf_block_search_form( $attrs = array(), $content = '' ) {
	return do_shortcode( '[wpmf_search_form]' ); }
function wpmf_block_search_results( $attrs = array(), $content = '' ) {
	return do_shortcode( '[wpmf_search_results]' ); }

function wpmf_blocks_assets() {
	wp_register_script( 'wpmf-blocks', plugins_url( 'assets/blocks.js', __DIR__ . '/../wpmatch-free.php' ), array( 'wp-blocks', 'wp-element', 'wp-editor' ), WPMATCH_FREE_VERSION, true );
	wp_register_style( 'wpmf-blocks', plugins_url( 'assets/blocks.css', __DIR__ . '/../wpmatch-free.php' ), array(), WPMATCH_FREE_VERSION );
	
	// Register interactions assets
	wp_register_script( 
		'wpmf-interactions', 
		plugins_url( 'assets/interactions.js', __DIR__ . '/../wpmatch-free.php' ), 
		array( 'jquery' ), 
		WPMATCH_FREE_VERSION, 
		true 
	);
	
	wp_register_style( 
		'wpmf-interactions', 
		plugins_url( 'assets/interactions.css', __DIR__ . '/../wpmatch-free.php' ), 
		array(), 
		WPMATCH_FREE_VERSION 
	);
	
	// Localize interactions script
	wp_localize_script(
		'wpmf-interactions',
		'wpmfInteractions',
		array(
			'apiUrl'  => rest_url( 'wpmatch-free/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'userId'  => get_current_user_id(),
			'strings' => array(
				'sending'       => __( 'Sending...', 'wpmatch-free' ),
				'sent'          => __( 'Sent!', 'wpmatch-free' ),
				'error'         => __( 'Error occurred', 'wpmatch-free' ),
				'loginRequired' => __( 'Please log in to send interactions', 'wpmatch-free' ),
				'match'         => __( 'It\'s a match! 🎉', 'wpmatch-free' ),
			),
		)
	);
	
	// Register profile views assets
	wp_register_script( 
		'wpmf-profile-views', 
		plugins_url( 'assets/profile-views.js', __DIR__ . '/../wpmatch-free.php' ), 
		array(), 
		WPMATCH_FREE_VERSION, 
		true 
	);
	
	wp_register_style( 
		'wpmf-profile-views', 
		plugins_url( 'assets/profile-views.css', __DIR__ . '/../wpmatch-free.php' ), 
		array(), 
		WPMATCH_FREE_VERSION 
	);
	
	// Register WebRTC calling assets
	wp_register_script( 
		'wpmf-webrtc-calls', 
		plugins_url( 'assets/webrtc-calls.js', __DIR__ . '/../wpmatch-free.php' ), 
		array(), 
		WPMATCH_FREE_VERSION, 
		true 
	);
	
	wp_register_style( 
		'wpmf-webrtc-calls', 
		plugins_url( 'assets/webrtc-calls.css', __DIR__ . '/../wpmatch-free.php' ), 
		array(), 
		WPMATCH_FREE_VERSION 
	);
	
	// Localize WebRTC script
	wp_localize_script(
		'wpmf-webrtc-calls',
		'wpApiSettings',
		array(
			'root'  => rest_url( 'wpmatch-free/v1/' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'init', 'wpmf_blocks_assets' );
