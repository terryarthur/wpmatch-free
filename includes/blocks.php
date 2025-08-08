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
}
add_action( 'init', 'wpmf_blocks_assets' );
