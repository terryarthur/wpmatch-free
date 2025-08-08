<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class WPMF_Reports_List_Table extends WP_List_Table {
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox"/>',
			'id'          => 'ID',
			'reporter_id' => 'Reporter',
			'target_type' => 'Target Type',
			'target_id'   => 'Target ID',
			'reason'      => 'Reason',
			'status'      => 'Status',
			'created_at'  => 'Created',
		);
	}
	public function get_bulk_actions() {
		return array(
			'close' => __( 'Close', 'wpmatch-free' ),
			'open'  => __( 'Reopen', 'wpmatch-free' ),
		); }
	public function column_cb( $item ) {
		echo '<input type="checkbox" name="ids[]" value="' . esc_attr( $item['id'] ) . '" />'; }
	public function column_default( $item, $column_name ) {
		return esc_html( (string) ( $item[ $column_name ] ?? '' ) ); }
	public function prepare_items() {
		global $wpdb;
		$t                     = $wpdb->prefix . 'wpmf_reports';
		$per                   = 20;
		$page                  = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$offset                = ( $page - 1 ) * $per;
		$total                 = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" );
		$this->items           = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} ORDER BY id DESC LIMIT %d OFFSET %d", $per, $offset ), ARRAY_A );
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per,
			)
		);
	}
	public function process_bulk_action() {
		if ( empty( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) ) {
			return; }
		check_admin_referer( 'bulk-' . $this->_args['plural'] );
		$ids    = array_map( 'absint', $_POST['ids'] );
		$action = $this->current_action();
		if ( ! $action ) {
			return; }
		global $wpdb;
		$t      = $wpdb->prefix . 'wpmf_reports';
		$status = $action === 'close' ? 'closed' : 'open';
		$in     = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$t} SET status=%s WHERE id IN ($in)", array_merge( array( $status ), $ids ) ) );
	}
}
