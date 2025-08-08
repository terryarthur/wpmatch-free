<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once __DIR__ . '/class-wpmf-photos-list-table.php';
require_once __DIR__ . '/class-wpmf-reports-list-table.php';
require_once __DIR__ . '/class-wpmf-verifications-list-table.php';

function wpmf_admin_photos_page() {
	if ( ! current_user_can( 'dating_moderate' ) ) {
		wp_die( esc_html__( 'Access denied', 'wpmatch-free' ) ); }
	$table = new WPMF_Photos_List_Table();
	$table->process_bulk_action();
	$table->prepare_items();
	echo '<div class="wrap"><h1>' . esc_html__( 'Photo Moderation', 'wpmatch-free' ) . '</h1>';
	echo '<form method="post">';
	$table->views();
	$table->display();
	echo '</form></div>';
}

function wpmf_admin_reports_page() {
	if ( ! current_user_can( 'dating_view_reports' ) ) {
		wp_die( esc_html__( 'Access denied', 'wpmatch-free' ) ); }
	$table = new WPMF_Reports_List_Table();
	$table->process_bulk_action();
	$table->prepare_items();
	echo '<div class="wrap"><h1>' . esc_html__( 'User Reports', 'wpmatch-free' ) . '</h1>';
	echo '<form method="post">';
	$table->views();
	$table->display();
	echo '</form></div>';
}

function wpmf_admin_verifications_page() {
	if ( ! current_user_can( 'dating_verify' ) ) {
		wp_die( esc_html__( 'Access denied', 'wpmatch-free' ) ); }
	$table = new WPMF_Verifications_List_Table();
	$table->process_bulk_action();
	$table->prepare_items();
	echo '<div class="wrap"><h1>' . esc_html__( 'Verifications', 'wpmatch-free' ) . '</h1>';
	echo '<form method="post">';
	$table->views();
	$table->display();
	echo '</form></div>';
}
