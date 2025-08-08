<?php
/**
 * Admin interface for WP Match Free
 *
 * @package WPMatchFree
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin interface for WP Match Free
 *
 * Manages the WordPress admin interface for the dating plugin,
 * including profile field management, dashboard statistics, and settings.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */
class WPMatch_Admin {
	/**
	 * AJAX: Render field list HTML for partial reload
	 */
	public function ajax_render_field_list() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}
		global $wpdb;
		$fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpmatch_profile_fields ORDER BY display_order ASC" );
		ob_start();
		?>
		<div class="field-content">
		<?php foreach ( $this->get_field_groups() as $group_id => $group_label ) : ?>
			<div class="tab-content" id="tab-<?php echo esc_attr( $group_id ); ?>" <?php echo 'basic' !== $group_id ? 'style="display:none"' : ''; ?>>
				<div class="fields-header">
					<h2><?php echo esc_html( $group_label ); ?></h2>
					<button class="button button-primary add-field-btn" data-group="<?php echo esc_attr( $group_id ); ?>">
						<?php esc_html_e( 'Add Field', 'wpmatch-free' ); ?>
					</button>
				</div>
				<div class="sortable-fields" data-group="<?php echo esc_attr( $group_id ); ?>">
					<?php
					$group_fields = array_filter(
						$fields,
						function ( $field ) use ( $group_id ) {
							return $field->field_group === $group_id;
						}
					);
					foreach ( $group_fields as $field ) :
						?>
						<div class="field-row" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
							<div class="field-handle">
								<span class="dashicons dashicons-move"></span>
							</div>
							<div class="field-info">
								<strong><?php echo esc_html( $field->field_label ); ?></strong>
								<span class="field-type">(<?php echo esc_html( $field->field_type ); ?>)</span>
								<div class="field-meta">
									<?php if ( $field->is_required ) : ?>
										<span class="required-badge"><?php esc_html_e( 'Required', 'wpmatch-free' ); ?></span>
									<?php endif; ?>
									<?php if ( $field->searchable ) : ?>
										<span class="searchable-badge"><?php esc_html_e( 'Searchable', 'wpmatch-free' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<div class="field-actions">
								<button class="button edit-field" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
									<?php esc_html_e( 'Edit', 'wpmatch-free' ); ?>
								</button>
								<button class="button delete-field" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
									<?php esc_html_e( 'Delete', 'wpmatch-free' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
		$html = ob_get_clean();
		wp_send_json_success( $html );
	}

	/**
	 * Initialize admin functionality
	 *
	 * Sets up WordPress hooks for admin menu, scripts, and AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_wpmatch_save_field', array( $this, 'ajax_save_field' ) );
		add_action( 'wp_ajax_wpmatch_delete_field', array( $this, 'ajax_delete_field' ) );
		add_action( 'wp_ajax_wpmatch_reorder_fields', array( $this, 'ajax_reorder_fields' ) );
		add_action( 'wp_ajax_wpmatch_render_field_list', array( $this, 'ajax_render_field_list' ) );
	}

	/**
	 * Add admin menu items
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Main menu page.
		add_menu_page(
			'WP Match Free',
			'WP Match Free',
			'manage_options',
			'wpmatch-free',
			array( $this, 'render_dashboard_page' ),
			'dashicons-heart',
			30
		);

		// Dashboard submenu (first item matches parent).
		add_submenu_page(
			'wpmatch-free',
			__( 'Dashboard', 'wpmatch-free' ),
			__( 'Dashboard', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-free',
			array( $this, 'render_dashboard_page' )
		);

		// Profile Fields submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Profile Fields', 'wpmatch-free' ),
			__( 'Profile Fields', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-fields',
			array( $this, 'render_fields_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Settings', 'wpmatch-free' ),
			__( 'Settings', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-settings',
			array( $this, 'render_settings_page' )
		);

		// Photo Management submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Photo Management', 'wpmatch-free' ),
			__( 'Photos', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-photos',
			array( $this, 'render_photos_admin_page' )
		);

		// Verifications submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Verifications', 'wpmatch-free' ),
			__( 'Verifications', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-verifications',
			array( $this, 'render_verifications_admin_page' )
		);

		// Reports submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Reports', 'wpmatch-free' ),
			__( 'Reports', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-reports',
			array( $this, 'render_reports_admin_page' )
		);

		// Members submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Members', 'wpmatch-free' ),
			__( 'Members', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-members',
			array( $this, 'render_members_admin_page' )
		);

		// Demo Content submenu.
		add_submenu_page(
			'wpmatch-free',
			__( 'Demo Content', 'wpmatch-free' ),
			__( 'Demo Content', 'wpmatch-free' ),
			'manage_options',
			'wpmatch-demo',
			array( $this, 'render_demo_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'wpmatch' ) === false ) {
			return;
		}

		// Admin CSS.
		wp_enqueue_style(
			'wpmatch-admin-css',
			WPMATCH_URL . 'assets/admin.css',
			array(),
			WPMATCH_FREE_VERSION
		);

		// Admin JavaScript.
		wp_enqueue_script(
			'wpmatch-admin-js',
			WPMATCH_URL . 'assets/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			WPMATCH_FREE_VERSION,
			true
		);

		// Localize script for AJAX URL and nonce.
		wp_localize_script(
			'wpmatch-admin-js',
			'wpmatchAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpmatch_admin_nonce' ),
				'strings' => array(
					'addField'            => __( 'Add Field', 'wpmatch-free' ),
					'editField'           => __( 'Edit Field', 'wpmatch-free' ),
					'fieldSaved'          => __( 'Field saved successfully!', 'wpmatch-free' ),
					'confirmDelete'       => __( 'Are you sure you want to delete this field?', 'wpmatch-free' ),
					'confirmCreateDemo'   => __( 'Are you sure you want to create demo users?', 'wpmatch-free' ),
					'confirmCleanupDemo'  => __( 'Are you sure you want to delete ALL demo users? This cannot be undone.', 'wpmatch-free' ),
					'error'               => __( 'An error occurred. Please try again.', 'wpmatch-free' ),
					'success'             => __( 'Success!', 'wpmatch-free' ),
				),
			)
		);
	}

	/**
	 * Render performance page
	 */


	/**
	 * Render reports page
	 */
	public function render_reports_admin_page() {
		global $wpdb;

		// Handle bulk actions.
		if ( isset( $_POST['action'] ) && isset( $_POST['report_ids'] ) && check_admin_referer( 'bulk_reports' ) ) {
			$this->handle_report_bulk_actions();
		}

		// Handle single report actions.
		if ( isset( $_GET['action'] ) && isset( $_GET['report_id'] ) && check_admin_referer( 'report_action_' . absint( wp_unslash( $_GET['report_id'] ) ) ) ) {
			$this->handle_report_action();
		}

		// Get filter parameters.
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		// Get report counts for status filter tabs.
		$counts = $this->get_report_counts();

		// Get reports based on filters.
		$reports = $this->get_reports_by_status( $status, $search );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'User Reports', 'wpmatch-free' ); ?></h1>

			<div class="tablenav top">
				<div class="alignleft actions">
					<form method="get">
						<input type="hidden" name="page" value="wpmatch-reports" />
						<?php if ( 'all' !== $status ) : ?>
							<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>" />
						<?php endif; ?>
						<input type="search" id="report-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search reports...', 'wpmatch-free' ); ?>" />
						<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Reports', 'wpmatch-free' ); ?>" />
					</form>
				</div>
			</div>

			<ul class="subsubsub">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-reports' ) ); ?>" class="<?php echo 'all' === $status ? 'current' : ''; ?>">
				<?php
				/* translators: %s: number of reports */
				printf( esc_html__( 'All (%s)', 'wpmatch-free' ), number_format( $counts['all'] ) );
				?>
				</a> |</li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-reports&status=open' ) ); ?>" class="<?php echo 'open' === $status ? 'current' : ''; ?>">
				<?php
				/* translators: %s: number of open reports */
				printf( esc_html__( 'Open (%s)', 'wpmatch-free' ), number_format( $counts['open'] ) );
				?>
				</a> |</li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-reports&status=resolved' ) ); ?>" class="<?php echo 'resolved' === $status ? 'current' : ''; ?>">
				<?php
				/* translators: %s: number of resolved reports */
				printf( esc_html__( 'Resolved (%s)', 'wpmatch-free' ), number_format( $counts['resolved'] ) );
				?>
				</a> |</li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-reports&status=dismissed' ) ); ?>" class="<?php echo 'dismissed' === $status ? 'current' : ''; ?>">
				<?php
				/* translators: %s: number of dismissed reports */
				printf( esc_html__( 'Dismissed (%s)', 'wpmatch-free' ), number_format( $counts['dismissed'] ) );
				?>
				</a></li>
			</ul>

			<?php if ( ! empty( $reports ) ) : ?>
				<form method="post">
					<?php wp_nonce_field( 'bulk_reports' ); ?>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select name="action" id="bulk-action-selector-top">
								<option value=""><?php esc_html_e( 'Bulk Actions', 'wpmatch-free' ); ?></option>
								<option value="resolve"><?php esc_html_e( 'Mark as Resolved', 'wpmatch-free' ); ?></option>
								<option value="dismiss"><?php esc_html_e( 'Dismiss', 'wpmatch-free' ); ?></option>
								<option value="reopen"><?php esc_html_e( 'Reopen', 'wpmatch-free' ); ?></option>
								<option value="delete"><?php esc_html_e( 'Delete', 'wpmatch-free' ); ?></option>
							</select>
							<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'wpmatch-free' ); ?>" />
						</div>
					</div>

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<td id="cb" class="manage-column column-cb check-column">
									<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'wpmatch-free' ); ?></label>
									<input id="cb-select-all-1" type="checkbox" />
								</td>
								<th scope="col"><?php esc_html_e( 'Reporter', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Target', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Type', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Reason', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Notes', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Date', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Actions', 'wpmatch-free' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $reports as $report ) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="report_ids[]" value="<?php echo esc_attr( $report->id ); ?>" />
									</th>
									<td>
										<?php
										if ( $report->reporter_name ) {
											echo esc_html( $report->reporter_name );
										} else {
											esc_html_e( 'Unknown User', 'wpmatch-free' );
										}
										?>
									</td>
									<td>
										<?php
										if ( 'user' === $report->target_type && $report->target_name ) {
											echo esc_html( $report->target_name );
										} elseif ( 'photo' === $report->target_type ) {
											echo esc_html__( 'Photo ID:', 'wpmatch-free' ) . ' ' . esc_html( $report->target_id );
										} elseif ( 'message' === $report->target_type ) {
											echo esc_html__( 'Message ID:', 'wpmatch-free' ) . ' ' . esc_html( $report->target_id );
										} else {
											echo esc_html( ucfirst( $report->target_type ) ) . ' #' . esc_html( $report->target_id );
										}
										?>
									</td>
									<td><?php echo esc_html( ucfirst( $report->target_type ) ); ?></td>
									<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $report->reason ) ) ); ?></td>
									<td>
										<?php if ( $report->notes ) : ?>
											<span title="<?php echo esc_attr( $report->notes ); ?>">
												<?php echo esc_html( wp_trim_words( $report->notes, 8 ) ); ?>
											</span>
										<?php else : ?>
											<em><?php esc_html_e( 'No notes', 'wpmatch-free' ); ?></em>
										<?php endif; ?>
									</td>
									<td>
										<span class="status-badge status-<?php echo esc_attr( $report->status ); ?>">
											<?php echo esc_html( ucfirst( $report->status ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $report->created_at ) ) ); ?></td>
									<td>
										<?php if ( 'open' === $report->status ) : ?>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-reports&action=resolve&report_id=' . $report->id ), 'report_action_' . $report->id ) ); ?>" class="button button-primary button-small">
												<?php esc_html_e( 'Resolve', 'wpmatch-free' ); ?>
											</a>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-reports&action=dismiss&report_id=' . $report->id ), 'report_action_' . $report->id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Dismiss', 'wpmatch-free' ); ?>
											</a>
										<?php else : ?>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-reports&action=reopen&report_id=' . $report->id ), 'report_action_' . $report->id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Reopen', 'wpmatch-free' ); ?>
											</a>
										<?php endif; ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-reports&action=delete&report_id=' . $report->id ), 'report_action_' . $report->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this report?', 'wpmatch-free' ); ?>')">
											<?php esc_html_e( 'Delete', 'wpmatch-free' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>

				<style>
				.status-badge {
					display: inline-block;
					padding: 2px 8px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
				.status-open { background-color: #dc3232; color: white; }
				.status-resolved { background-color: #00a32a; color: white; }
				.status-dismissed { background-color: #646970; color: white; }
				</style>

			<?php else : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No reports found.', 'wpmatch-free' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Render dashboard page
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard_page() {
		global $wpdb;

		// Get current tab.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'overview';

		// Get statistics.
		$stats = array(
			'total_profiles' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" ),
			'active_fields'  => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpmatch_profile_fields" ),
			'total_messages' => 0, // Will implement later.
			'matches_made'   => 0, // Will implement later.
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Match Free Dashboard', 'wpmatch-free' ); ?></h1>
			
			<!-- Dashboard Tabs -->
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-free&tab=overview' ) ); ?>" 
					class="nav-tab <?php echo 'overview' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Overview', 'wpmatch-free' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-free&tab=photos' ) ); ?>" 
					class="nav-tab <?php echo 'photos' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Photo Moderation', 'wpmatch-free' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-free&tab=reports' ) ); ?>" 
					class="nav-tab <?php echo 'reports' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'User Reports', 'wpmatch-free' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-free&tab=verifications' ) ); ?>" 
					class="nav-tab <?php echo 'verifications' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Verifications', 'wpmatch-free' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-free&tab=performance' ) ); ?>" 
					class="nav-tab <?php echo 'performance' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Performance', 'wpmatch-free' ); ?>
				</a>
			</nav>

			<!-- Tab Content -->
			<div class="tab-content">
				<?php
				switch ( $current_tab ) {
					case 'photos':
						$this->render_photos_tab();
						break;
					case 'reports':
						$this->render_reports_tab();
						break;
					case 'verifications':
						$this->render_verifications_tab();
						break;
					case 'performance':
						$this->render_performance_tab();
						break;
					default:
						$this->render_overview_tab( $stats );
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render overview tab content
	 *
	 * @since 1.0.0
	 * @param array $stats Dashboard statistics.
	 */
	private function render_overview_tab( $stats ) {
		?>
		<div class="wpmatch-dashboard-stats">
			<div class="stat-box">
				<h3><?php echo number_format( $stats['total_profiles'] ); ?></h3>
				<p><?php esc_html_e( 'Total Profiles', 'wpmatch-free' ); ?></p>
			</div>
			<div class="stat-box">
				<h3><?php echo number_format( $stats['active_fields'] ); ?></h3>
				<p><?php esc_html_e( 'Profile Fields', 'wpmatch-free' ); ?></p>
			</div>
			<div class="stat-box">
				<h3><?php echo number_format( $stats['total_messages'] ); ?></h3>
				<p><?php esc_html_e( 'Messages Sent', 'wpmatch-free' ); ?></p>
			</div>
			<div class="stat-box">
				<h3><?php echo number_format( $stats['matches_made'] ); ?></h3>
				<p><?php esc_html_e( 'Matches Made', 'wpmatch-free' ); ?></p>
			</div>
		</div>

		<div class="wpmatch-quick-actions">
			<h2><?php esc_html_e( 'Quick Actions', 'wpmatch-free' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-fields' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Manage Profile Fields', 'wpmatch-free' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-settings' ) ); ?>" class="button">
				<?php esc_html_e( 'Plugin Settings', 'wpmatch-free' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-demo' ) ); ?>" class="button">
				<?php esc_html_e( 'Demo Content', 'wpmatch-free' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render photos moderation tab
	 *
	 * @since 1.0.0
	 */
	private function render_photos_tab() {
		global $wpdb;

		// Get pending photos.
		$pending_photos = $wpdb->get_results(
			"SELECT p.*, u.display_name 
			 FROM {$wpdb->prefix}wpmf_photos p 
			 LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
			 WHERE p.status = 'pending' 
			 ORDER BY p.created_at DESC 
			 LIMIT 20"
		);
		?>
		<div class="wpmatch-moderation-section">
			<h2><?php esc_html_e( 'Photo Moderation', 'wpmatch-free' ); ?></h2>
			
			<?php if ( empty( $pending_photos ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No photos pending approval.', 'wpmatch-free' ); ?></p>
				</div>
			<?php else : ?>
				<div class="photo-moderation-grid">
					<?php foreach ( $pending_photos as $photo ) : ?>
						<div class="photo-item" data-photo-id="<?php echo esc_attr( $photo->id ); ?>">
							<div class="photo-preview">
								<?php if ( $photo->attachment_id ) : ?>
									<?php echo wp_get_attachment_image( $photo->attachment_id, 'thumbnail' ); ?>
								<?php else : ?>
									<div class="no-image"><?php esc_html_e( 'No Image', 'wpmatch-free' ); ?></div>
								<?php endif; ?>
							</div>
							<div class="photo-details">
								<strong><?php echo esc_html( $photo->display_name ); ?></strong>
								<small><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $photo->created_at ) ) ); ?></small>
							</div>
							<div class="photo-actions">
								<button class="button button-primary approve-photo" data-photo-id="<?php echo esc_attr( $photo->id ); ?>">
									<?php esc_html_e( 'Approve', 'wpmatch-free' ); ?>
								</button>
								<button class="button button-secondary reject-photo" data-photo-id="<?php echo esc_attr( $photo->id ); ?>">
									<?php esc_html_e( 'Reject', 'wpmatch-free' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render reports tab
	 *
	 * @since 1.0.0
	 */
	private function render_reports_tab() {
		global $wpdb;

		// Get recent reports.
		$reports = $wpdb->get_results(
			"SELECT r.*, u.display_name as reporter_name 
			 FROM {$wpdb->prefix}wpmf_reports r 
			 LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID 
			 WHERE r.status = 'open' 
			 ORDER BY r.created_at DESC 
			 LIMIT 20"
		);
		?>
		<div class="wpmatch-moderation-section">
			<h2><?php esc_html_e( 'User Reports', 'wpmatch-free' ); ?></h2>
			
			<?php if ( empty( $reports ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No open reports.', 'wpmatch-free' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Reporter', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Type', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Date', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wpmatch-free' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $reports as $report ) : ?>
							<tr>
								<td><?php echo esc_html( $report->reporter_name ); ?></td>
								<td><?php echo esc_html( ucfirst( $report->target_type ) ); ?></td>
								<td><?php echo esc_html( ucfirst( $report->reason ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $report->created_at ) ) ); ?></td>
								<td>
									<button class="button button-small resolve-report" data-report-id="<?php echo esc_attr( $report->id ); ?>">
										<?php esc_html_e( 'Resolve', 'wpmatch-free' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render verifications tab
	 *
	 * @since 1.0.0
	 */
	private function render_verifications_tab() {
		global $wpdb;

		// Get pending verifications
		$verifications = $wpdb->get_results(
			"SELECT v.*, u.display_name 
			 FROM {$wpdb->prefix}wpmf_verifications v 
			 LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID 
			 WHERE v.status = 'pending' 
			 ORDER BY v.created_at DESC 
			 LIMIT 20"
		);
		?>
		<div class="wpmatch-moderation-section">
			<h2><?php esc_html_e( 'Verifications', 'wpmatch-free' ); ?></h2>
			
			<?php if ( empty( $verifications ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No pending verifications.', 'wpmatch-free' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Submitted', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Status', 'wpmatch-free' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'wpmatch-free' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $verifications as $verification ) : ?>
							<tr>
								<td><?php echo esc_html( $verification->display_name ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $verification->created_at ) ) ); ?></td>
								<td><?php echo esc_html( ucfirst( $verification->status ) ); ?></td>
								<td>
									<button class="button button-primary approve-verification" data-verification-id="<?php echo esc_attr( $verification->id ); ?>">
										<?php esc_html_e( 'Approve', 'wpmatch-free' ); ?>
									</button>
									<button class="button button-secondary reject-verification" data-verification-id="<?php echo esc_attr( $verification->id ); ?>">
										<?php esc_html_e( 'Reject', 'wpmatch-free' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render performance tab
	 *
	 * @since 1.0.0
	 */
	private function render_performance_tab() {
		// Include the existing performance page content
		if ( function_exists( 'wpmf_performance_admin_page' ) ) {
			wpmf_performance_admin_page();
		} else {
			// Fallback performance info
			?>
			<div class="wpmatch-moderation-section">
				<h2><?php esc_html_e( 'Database Performance', 'wpmatch-free' ); ?></h2>
				<div class="performance-stats">
					<div class="stat-card">
						<h3><?php esc_html_e( 'Database Optimization', 'wpmatch-free' ); ?></h3>
						<p><?php esc_html_e( 'Monitor and optimize database performance for better site speed.', 'wpmatch-free' ); ?></p>
						<button class="button button-primary" onclick="location.reload()">
							<?php esc_html_e( 'Refresh Stats', 'wpmatch-free' ); ?>
						</button>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render comprehensive photo management admin page
	 *
	 * @since 1.0.0
	 */
	public function render_photos_admin_page() {
		global $wpdb;

		// Handle bulk actions
		if ( isset( $_POST['action'] ) && isset( $_POST['photo_ids'] ) && check_admin_referer( 'bulk_photos' ) ) {
			$this->handle_photo_bulk_actions();
		}

		// Handle single photo actions
		if ( isset( $_GET['action'] ) && isset( $_GET['photo_id'] ) && check_admin_referer( 'photo_action_' . $_GET['photo_id'] ) ) {
			$this->handle_photo_action();
		}

		// Get current view and search
		$current_view = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'pending';
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		// Get photo counts
		$counts = $this->get_photo_counts();

		// Get photos for current view
		$photos = $this->get_photos_by_status( $current_view, $search );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Photo Management', 'wpmatch-free' ); ?></h1>

			<!-- Status Filter Views -->
			<ul class="subsubsub">
				<li class="all">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-photos' ) ); ?>" 
						class="<?php echo $current_view === 'all' ? 'current' : ''; ?>">
						<?php esc_html_e( 'All', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['all'] ); ?>)</span>
					</a> |
				</li>
				<li class="pending">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-photos&status=pending' ) ); ?>" 
						class="<?php echo $current_view === 'pending' ? 'current' : ''; ?>">
						<?php esc_html_e( 'Pending', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['pending'] ); ?>)</span>
					</a> |
				</li>
				<li class="approved">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-photos&status=approved' ) ); ?>" 
						class="<?php echo $current_view === 'approved' ? 'current' : ''; ?>">
						<?php esc_html_e( 'Approved', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['approved'] ); ?>)</span>
					</a> |
				</li>
				<li class="rejected">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-photos&status=rejected' ) ); ?>" 
						class="<?php echo $current_view === 'rejected' ? 'current' : ''; ?>">
						<?php esc_html_e( 'Rejected', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['rejected'] ); ?>)</span>
					</a>
				</li>
			</ul>

			<!-- Search Form -->
			<form method="get" class="search-form">
				<input type="hidden" name="page" value="wpmatch-photos">
				<?php if ( $current_view !== 'all' ) : ?>
					<input type="hidden" name="status" value="<?php echo esc_attr( $current_view ); ?>">
				<?php endif; ?>
				<p class="search-box">
					<label class="screen-reader-text" for="photo-search-input"><?php esc_html_e( 'Search Photos:', 'wpmatch-free' ); ?></label>
					<input type="search" id="photo-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search users...', 'wpmatch-free' ); ?>">
					<?php submit_button( __( 'Search Photos', 'wpmatch-free' ), '', '', false, array( 'id' => 'search-submit' ) ); ?>
				</p>
			</form>

			<!-- Photos Management -->
			<form method="post" id="photos-form">
				<?php wp_nonce_field( 'bulk_photos' ); ?>
				
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'wpmatch-free' ); ?></label>
						<select name="action" id="bulk-action-selector-top">
							<option value="-1"><?php esc_html_e( 'Bulk Actions', 'wpmatch-free' ); ?></option>
							<?php if ( $current_view === 'pending' ) : ?>
								<option value="approve"><?php esc_html_e( 'Approve', 'wpmatch-free' ); ?></option>
								<option value="reject"><?php esc_html_e( 'Reject', 'wpmatch-free' ); ?></option>
							<?php endif; ?>
							<option value="delete"><?php esc_html_e( 'Delete', 'wpmatch-free' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'wpmatch-free' ), 'action', '', false, array( 'id' => 'doaction' ) ); ?>
					</div>
				</div>

				<?php if ( empty( $photos ) ) : ?>
					<div class="notice notice-info">
						<p><?php esc_html_e( 'No photos found.', 'wpmatch-free' ); ?></p>
					</div>
				<?php else : ?>
					<div class="photo-moderation-grid">
						<?php foreach ( $photos as $photo ) : ?>
							<div class="photo-item" data-photo-id="<?php echo esc_attr( $photo->id ); ?>">
								<div class="photo-checkbox">
									<input type="checkbox" name="photo_ids[]" value="<?php echo esc_attr( $photo->id ); ?>">
								</div>
								
								<div class="photo-preview">
									<?php if ( $photo->attachment_id ) : ?>
										<?php echo wp_get_attachment_image( $photo->attachment_id, 'medium', false, array( 'class' => 'photo-image' ) ); ?>
									<?php else : ?>
										<div class="no-image">
											<span class="dashicons dashicons-format-image"></span>
											<p><?php esc_html_e( 'No Image', 'wpmatch-free' ); ?></p>
										</div>
									<?php endif; ?>
									
									<div class="photo-overlay">
										<div class="photo-status status-<?php echo esc_attr( $photo->status ); ?>">
											<?php echo esc_html( ucfirst( $photo->status ) ); ?>
										</div>
										<?php if ( $photo->is_primary ) : ?>
											<div class="primary-badge">
												<span class="dashicons dashicons-star-filled"></span>
												<?php esc_html_e( 'Primary', 'wpmatch-free' ); ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
								
								<div class="photo-details">
									<div class="photo-user">
										<strong><?php echo esc_html( $photo->display_name ); ?></strong>
										<a href="<?php echo esc_url( get_edit_user_link( $photo->user_id ) ); ?>" class="user-link">
											<?php esc_html_e( 'View Profile', 'wpmatch-free' ); ?>
										</a>
									</div>
									
									<div class="photo-meta">
										<div class="upload-date">
											<strong><?php esc_html_e( 'Uploaded:', 'wpmatch-free' ); ?></strong>
											<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $photo->created_at ) ) ); ?>
										</div>
										
										<?php if ( $photo->moderation_notes ) : ?>
											<div class="moderation-notes">
												<strong><?php esc_html_e( 'Notes:', 'wpmatch-free' ); ?></strong>
												<span title="<?php echo esc_attr( $photo->moderation_notes ); ?>">
													<?php echo esc_html( wp_trim_words( $photo->moderation_notes, 8 ) ); ?>
												</span>
											</div>
										<?php endif; ?>
									</div>
								</div>
								
								<div class="photo-actions">
									<?php if ( $photo->status === 'pending' ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-photos&action=approve&photo_id=' . $photo->id ), 'photo_action_' . $photo->id ) ); ?>" 
											class="button button-small button-primary">
											<?php esc_html_e( 'Approve', 'wpmatch-free' ); ?>
										</a>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-photos&action=reject&photo_id=' . $photo->id ), 'photo_action_' . $photo->id ) ); ?>" 
											class="button button-small">
											<?php esc_html_e( 'Reject', 'wpmatch-free' ); ?>
										</a>
									<?php elseif ( $photo->status === 'approved' ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-photos&action=reject&photo_id=' . $photo->id ), 'photo_action_' . $photo->id ) ); ?>" 
											class="button button-small">
											<?php esc_html_e( 'Reject', 'wpmatch-free' ); ?>
										</a>
									<?php elseif ( $photo->status === 'rejected' ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-photos&action=approve&photo_id=' . $photo->id ), 'photo_action_' . $photo->id ) ); ?>" 
											class="button button-small button-primary">
											<?php esc_html_e( 'Approve', 'wpmatch-free' ); ?>
										</a>
									<?php endif; ?>
									
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-photos&action=delete&photo_id=' . $photo->id ), 'photo_action_' . $photo->id ) ); ?>" 
										class="button button-small button-link-delete" 
										onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this photo?', 'wpmatch-free' ); ?>')">
										<?php esc_html_e( 'Delete', 'wpmatch-free' ); ?>
									</a>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</form>

			<!-- Photo Statistics -->
			<div class="photo-stats" style="margin-top: 30px;">
				<h3><?php esc_html_e( 'Photo Statistics', 'wpmatch-free' ); ?></h3>
				<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
					<div class="stat-card">
						<strong><?php echo esc_html( $counts['pending'] ); ?></strong>
						<p><?php esc_html_e( 'Pending Review', 'wpmatch-free' ); ?></p>
					</div>
					<div class="stat-card">
						<strong><?php echo esc_html( $counts['approved'] ); ?></strong>
						<p><?php esc_html_e( 'Approved', 'wpmatch-free' ); ?></p>
					</div>
					<div class="stat-card">
						<strong><?php echo esc_html( $counts['rejected'] ); ?></strong>
						<p><?php esc_html_e( 'Rejected', 'wpmatch-free' ); ?></p>
					</div>
					<div class="stat-card">
						<strong><?php echo esc_html( number_format( $counts['approved'] / max( $counts['all'], 1 ) * 100, 1 ) ); ?>%</strong>
						<p><?php esc_html_e( 'Approval Rate', 'wpmatch-free' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<style>
		.photo-moderation-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			gap: 20px;
			margin-top: 20px;
		}
		
		.photo-item {
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 15px;
			background: #fff;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		
		.photo-checkbox {
			float: right;
			margin-bottom: 10px;
		}
		
		.photo-preview {
			position: relative;
			text-align: center;
			margin-bottom: 15px;
		}
		
		.photo-image {
			max-width: 100%;
			height: auto;
			border-radius: 4px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		}
		
		.no-image {
			background: #f8f9fa;
			border: 2px dashed #dee2e6;
			border-radius: 4px;
			padding: 40px 20px;
			color: #6c757d;
		}
		
		.no-image .dashicons {
			font-size: 48px;
			display: block;
			margin-bottom: 10px;
		}
		
		.photo-overlay {
			position: absolute;
			top: 8px;
			right: 8px;
		}
		
		.photo-status {
			padding: 4px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			margin-bottom: 5px;
		}
		
		.status-pending { background: #fff3cd; color: #856404; }
		.status-approved { background: #d1edff; color: #0c5460; }
		.status-rejected { background: #f8d7da; color: #721c24; }
		
		.primary-badge {
			background: #ffd700;
			color: #333;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 10px;
			font-weight: 600;
		}
		
		.primary-badge .dashicons {
			font-size: 12px;
			vertical-align: text-top;
		}
		
		.photo-details {
			margin-bottom: 15px;
		}
		
		.photo-user {
			margin-bottom: 10px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		
		.user-link {
			font-size: 12px;
			text-decoration: none;
		}
		
		.photo-meta div {
			margin-bottom: 8px;
			font-size: 13px;
		}
		
		.photo-meta strong {
			color: #555;
		}
		
		.photo-actions {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
		}
		
		.photo-actions .button {
			flex: 1;
			text-align: center;
			min-width: 70px;
		}
		
		.stat-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			padding: 15px;
			border-radius: 3px;
			text-align: center;
		}
		
		.stat-card strong {
			display: block;
			font-size: 24px;
			line-height: 1;
			margin-bottom: 5px;
			color: #23282d;
		}
		
		.stat-card p {
			margin: 0;
			color: #646970;
			font-size: 13px;
		}
		</style>
		<?php
	}

	/**
	 * Get photo counts by status
	 *
	 * @since 1.0.0
	 * @return array Counts by status
	 */
	private function get_photo_counts() {
		global $wpdb;

		$counts = array(
			'all'      => 0,
			'pending'  => 0,
			'approved' => 0,
			'rejected' => 0,
		);

		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) as count 
			 FROM {$wpdb->prefix}wpmf_photos 
			 GROUP BY status"
		);

		foreach ( $results as $result ) {
			$counts[ $result->status ] = (int) $result->count;
			$counts['all']            += (int) $result->count;
		}

		return $counts;
	}

	/**
	 * Get photos by status with optional search
	 *
	 * @since 1.0.0
	 * @param string $status Status filter
	 * @param string $search Search term
	 * @return array Photo records
	 */
	private function get_photos_by_status( $status, $search = '' ) {
		global $wpdb;

		$where  = array();
		$params = array();

		// Status filter.
		if ( 'all' !== $status ) {
			$where[]  = 'p.status = %s';
			$params[] = $status;
		}

		// Search filter.
		if ( ! empty( $search ) ) {
			$where[]     = 'u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$params[]    = $search_term;
			$params[]    = $search_term;
			$params[]    = $search_term;
		}

		$where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$query = "SELECT p.*, u.display_name, u.user_login, u.user_email 
				  FROM {$wpdb->prefix}wpmf_photos p 
				  LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
				  $where_clause 
				  ORDER BY p.created_at DESC 
				  LIMIT 50";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Handle single photo action
	 *
	 * @since 1.0.0
	 */
	private function handle_photo_action() {
		global $wpdb;

		$photo_id = (int) $_GET['photo_id'];
		$action   = sanitize_text_field( $_GET['action'] );

		$result  = false;
		$message = '';

		switch ( $action ) {
			case 'approve':
				$result  = $wpdb->update(
					$wpdb->prefix . 'wpmf_photos',
					array(
						'status'           => 'approved',
						'moderation_notes' => __( 'Approved by admin', 'wpmatch-free' ),
					),
					array( 'id' => $photo_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
				$message = __( 'Photo approved successfully.', 'wpmatch-free' );
				break;

			case 'reject':
				$result  = $wpdb->update(
					$wpdb->prefix . 'wpmf_photos',
					array(
						'status'           => 'rejected',
						'moderation_notes' => __( 'Rejected by admin', 'wpmatch-free' ),
					),
					array( 'id' => $photo_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
				$message = __( 'Photo rejected successfully.', 'wpmatch-free' );
				break;

			case 'delete':
				// Get photo info first
				$photo = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT attachment_id FROM {$wpdb->prefix}wpmf_photos WHERE id = %d",
						$photo_id
					)
				);

				// Delete from database
				$result = $wpdb->delete(
					$wpdb->prefix . 'wpmf_photos',
					array( 'id' => $photo_id ),
					array( '%d' )
				);

				// Delete attachment if exists
				if ( $result && $photo && $photo->attachment_id ) {
					wp_delete_attachment( $photo->attachment_id, true );
				}

				$message = __( 'Photo deleted successfully.', 'wpmatch-free' );
				break;
		}

		if ( $result ) {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);
		} else {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Action failed. Please try again.', 'wpmatch-free' ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Handle bulk photo actions
	 *
	 * @since 1.0.0
	 */
	private function handle_photo_bulk_actions() {
		global $wpdb;

		if ( empty( $_POST['photo_ids'] ) || ! is_array( $_POST['photo_ids'] ) ) {
			return;
		}

		$photo_ids = array_map( 'intval', $_POST['photo_ids'] );
		$action    = sanitize_text_field( $_POST['action'] );

		$count   = 0;
		$message = '';

		switch ( $action ) {
			case 'approve':
				foreach ( $photo_ids as $id ) {
					$result = $wpdb->update(
						$wpdb->prefix . 'wpmf_photos',
						array(
							'status'           => 'approved',
							'moderation_notes' => __( 'Bulk approved by admin', 'wpmatch-free' ),
						),
						array( 'id' => $id ),
						array( '%s', '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf(
					_n( '%d photo approved.', '%d photos approved.', $count, 'wpmatch-free' ),
					$count
				);
				break;

			case 'reject':
				foreach ( $photo_ids as $id ) {
					$result = $wpdb->update(
						$wpdb->prefix . 'wpmf_photos',
						array(
							'status'           => 'rejected',
							'moderation_notes' => __( 'Bulk rejected by admin', 'wpmatch-free' ),
						),
						array( 'id' => $id ),
						array( '%s', '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf(
					_n( '%d photo rejected.', '%d photos rejected.', $count, 'wpmatch-free' ),
					$count
				);
				break;

			case 'delete':
				foreach ( $photo_ids as $id ) {
					// Get photo info first
					$photo = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT attachment_id FROM {$wpdb->prefix}wpmf_photos WHERE id = %d",
							$id
						)
					);

					// Delete from database
					$result = $wpdb->delete(
						$wpdb->prefix . 'wpmf_photos',
						array( 'id' => $id ),
						array( '%d' )
					);

					// Delete attachment if exists
					if ( $result && $photo && $photo->attachment_id ) {
						wp_delete_attachment( $photo->attachment_id, true );
					}

					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf(
					_n( '%d photo deleted.', '%d photos deleted.', $count, 'wpmatch-free' ),
					$count
				);
				break;
		}

		if ( $count > 0 ) {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Render comprehensive verifications admin page
	 *
	 * @since 1.0.0
	 */
	public function render_verifications_admin_page() {
		global $wpdb;

		// Handle bulk actions
		if ( isset( $_POST['action'] ) && isset( $_POST['verification_ids'] ) && check_admin_referer( 'bulk_verifications' ) ) {
			$this->handle_verification_bulk_actions();
		}

		// Handle single verification actions
		if ( isset( $_GET['action'] ) && isset( $_GET['verification_id'] ) && check_admin_referer( 'verification_action_' . $_GET['verification_id'] ) ) {
			$this->handle_verification_action();
		}

		// Get current view and search
		$current_view = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'pending';
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		// Get verification counts
		$counts = $this->get_verification_counts();

		// Get verifications for current view
		$verifications = $this->get_verifications_by_status( $current_view, $search );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'User Verifications', 'wpmatch-free' ); ?></h1>

			<!-- Status Filter Views -->
			<ul class="subsubsub">
				<li class="all">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-verifications' ) ); ?>" 
						class="<?php echo $current_view === 'all' ? 'current' : ''; ?>">
						<?php esc_html_e( 'All', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['all'] ); ?>)</span>
					</a> |
				</li>
				<li class="pending">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-verifications&status=pending' ) ); ?>" 
						class="<?php echo $current_view === 'pending' ? 'current' : ''; ?>">
						<?php esc_html_e( 'Pending', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['pending'] ); ?>)</span>
					</a> |
				</li>
				<li class="approved">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-verifications&status=approved' ) ); ?>" 
						class="<?php echo $current_view === 'approved' ? 'current' : ''; ?>">
						<?php esc_html_e( 'Approved', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['approved'] ); ?>)</span>
					</a> |
				</li>
				<li class="rejected">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-verifications&status=rejected' ) ); ?>" 
						class="<?php echo $current_view === 'rejected' ? 'current' : ''; ?>">
						<?php esc_html_e( 'Rejected', 'wpmatch-free' ); ?> 
						<span class="count">(<?php echo esc_html( $counts['rejected'] ); ?>)</span>
					</a>
				</li>
			</ul>

			<!-- Search Form -->
			<form method="get" class="search-form">
				<input type="hidden" name="page" value="wpmatch-verifications">
				<?php if ( $current_view !== 'all' ) : ?>
					<input type="hidden" name="status" value="<?php echo esc_attr( $current_view ); ?>">
				<?php endif; ?>
				<p class="search-box">
					<label class="screen-reader-text" for="verification-search-input"><?php esc_html_e( 'Search Verifications:', 'wpmatch-free' ); ?></label>
					<input type="search" id="verification-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search users...', 'wpmatch-free' ); ?>">
					<?php submit_button( __( 'Search Verifications', 'wpmatch-free' ), '', '', false, array( 'id' => 'search-submit' ) ); ?>
				</p>
			</form>

			<!-- Verifications Table -->
			<form method="post" id="verifications-form">
				<?php wp_nonce_field( 'bulk_verifications' ); ?>
				
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'wpmatch-free' ); ?></label>
						<select name="action" id="bulk-action-selector-top">
							<option value="-1"><?php esc_html_e( 'Bulk Actions', 'wpmatch-free' ); ?></option>
							<?php if ( $current_view === 'pending' ) : ?>
								<option value="approve"><?php esc_html_e( 'Approve', 'wpmatch-free' ); ?></option>
								<option value="reject"><?php esc_html_e( 'Reject', 'wpmatch-free' ); ?></option>
							<?php endif; ?>
							<option value="delete"><?php esc_html_e( 'Delete', 'wpmatch-free' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'wpmatch-free' ), 'action', '', false, array( 'id' => 'doaction' ) ); ?>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td id="cb" class="manage-column column-cb check-column">
								<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'wpmatch-free' ); ?></label>
								<input id="cb-select-all-1" type="checkbox">
							</td>
							<th scope="col" class="manage-column column-user"><?php esc_html_e( 'User', 'wpmatch-free' ); ?></th>
							<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'wpmatch-free' ); ?></th>
							<th scope="col" class="manage-column column-submitted"><?php esc_html_e( 'Submitted', 'wpmatch-free' ); ?></th>
							<th scope="col" class="manage-column column-reviewed"><?php esc_html_e( 'Reviewed', 'wpmatch-free' ); ?></th>
							<th scope="col" class="manage-column column-reviewer"><?php esc_html_e( 'Reviewer', 'wpmatch-free' ); ?></th>
							<th scope="col" class="manage-column column-notes"><?php esc_html_e( 'Notes', 'wpmatch-free' ); ?></th>
							<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'wpmatch-free' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $verifications ) ) : ?>
							<tr class="no-items">
								<td class="colspanchange" colspan="8">
									<?php esc_html_e( 'No verifications found.', 'wpmatch-free' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $verifications as $verification ) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="verification_ids[]" value="<?php echo esc_attr( $verification->id ); ?>">
									</th>
									<td class="column-user">
										<strong><?php echo esc_html( $verification->display_name ); ?></strong>
										<div class="row-actions">
											<span class="view">
												<a href="<?php echo esc_url( get_edit_user_link( $verification->user_id ) ); ?>">
													<?php esc_html_e( 'View Profile', 'wpmatch-free' ); ?>
												</a>
											</span>
										</div>
									</td>
									<td class="column-status">
										<span class="verification-status status-<?php echo esc_attr( $verification->status ); ?>">
											<?php echo esc_html( ucfirst( $verification->status ) ); ?>
										</span>
									</td>
									<td class="column-submitted">
										<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $verification->created_at ) ) ); ?>
									</td>
									<td class="column-reviewed">
										<?php if ( $verification->updated_at !== $verification->created_at ) : ?>
											<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $verification->updated_at ) ) ); ?>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
									<td class="column-reviewer">
										<?php if ( $verification->reviewer_id ) : ?>
											<?php
											$reviewer = get_user_by( 'ID', $verification->reviewer_id );
											echo esc_html( $reviewer ? $reviewer->display_name : __( 'Unknown', 'wpmatch-free' ) );
											?>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
									<td class="column-notes">
										<?php if ( $verification->notes ) : ?>
											<span title="<?php echo esc_attr( $verification->notes ); ?>">
												<?php echo esc_html( wp_trim_words( $verification->notes, 5 ) ); ?>
											</span>
										<?php else : ?>
											—
										<?php endif; ?>
									</td>
									<td class="column-actions">
										<?php if ( $verification->status === 'pending' ) : ?>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-verifications&action=approve&verification_id=' . $verification->id ), 'verification_action_' . $verification->id ) ); ?>" 
												class="button button-small button-primary">
												<?php esc_html_e( 'Approve', 'wpmatch-free' ); ?>
											</a>
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-verifications&action=reject&verification_id=' . $verification->id ), 'verification_action_' . $verification->id ) ); ?>" 
												class="button button-small">
												<?php esc_html_e( 'Reject', 'wpmatch-free' ); ?>
											</a>
										<?php endif; ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-verifications&action=delete&verification_id=' . $verification->id ), 'verification_action_' . $verification->id ) ); ?>" 
											class="button button-small button-link-delete" 
											onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this verification?', 'wpmatch-free' ); ?>')">
											<?php esc_html_e( 'Delete', 'wpmatch-free' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</form>

			<!-- Verification Statistics -->
			<div class="verification-stats" style="margin-top: 20px;">
				<h3><?php esc_html_e( 'Verification Statistics', 'wpmatch-free' ); ?></h3>
				<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
					<div class="stat-card">
						<strong><?php echo esc_html( $counts['pending'] ); ?></strong>
						<p><?php esc_html_e( 'Pending Review', 'wpmatch-free' ); ?></p>
					</div>
					<div class="stat-card">
						<strong><?php echo esc_html( $counts['approved'] ); ?></strong>
						<p><?php esc_html_e( 'Approved', 'wpmatch-free' ); ?></p>
					</div>
					<div class="stat-card">
						<strong><?php echo esc_html( $counts['rejected'] ); ?></strong>
						<p><?php esc_html_e( 'Rejected', 'wpmatch-free' ); ?></p>
					</div>
					<div class="stat-card">
						<strong><?php echo esc_html( number_format( $counts['approved'] / max( $counts['all'], 1 ) * 100, 1 ) ); ?>%</strong>
						<p><?php esc_html_e( 'Approval Rate', 'wpmatch-free' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<style>
		.verification-status {
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 12px;
			font-weight: 500;
		}
		.status-pending { background: #fff3cd; color: #856404; }
		.status-approved { background: #d1edff; color: #0c5460; }
		.status-rejected { background: #f8d7da; color: #721c24; }
		.stat-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			padding: 15px;
			border-radius: 3px;
		}
		.stat-card strong {
			display: block;
			font-size: 24px;
			line-height: 1;
			margin-bottom: 5px;
		}
		.stat-card p {
			margin: 0;
			color: #646970;
		}
		</style>
		<?php
	}

	/**
	 * Get verification counts by status
	 *
	 * @since 1.0.0
	 * @return array Counts by status
	 */
	private function get_verification_counts() {
		global $wpdb;

		$counts = array(
			'all'      => 0,
			'pending'  => 0,
			'approved' => 0,
			'rejected' => 0,
		);

		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) as count 
			 FROM {$wpdb->prefix}wpmf_verifications 
			 GROUP BY status"
		);

		foreach ( $results as $result ) {
			$counts[ $result->status ] = (int) $result->count;
			$counts['all']            += (int) $result->count;
		}

		return $counts;
	}

	/**
	 * Get verifications by status with optional search
	 *
	 * @since 1.0.0
	 * @param string $status Status filter
	 * @param string $search Search term
	 * @return array Verification records
	 */
	private function get_verifications_by_status( $status, $search = '' ) {
		global $wpdb;

		$where  = array();
		$params = array();

		// Status filter.
		if ( 'all' !== $status ) {
			$where[]  = 'v.status = %s';
			$params[] = $status;
		}

		// Search filter.
		if ( ! empty( $search ) ) {
			$where[]     = 'u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$params[]    = $search_term;
			$params[]    = $search_term;
			$params[]    = $search_term;
		}

		$where_clause = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$query = "SELECT v.*, u.display_name, u.user_login, u.user_email 
				  FROM {$wpdb->prefix}wpmf_verifications v 
				  LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID 
				  $where_clause 
				  ORDER BY v.created_at DESC 
				  LIMIT 50";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Handle single verification action
	 *
	 * @since 1.0.0
	 */
	private function handle_verification_action() {
		global $wpdb;

		$verification_id = (int) $_GET['verification_id'];
		$action          = sanitize_text_field( $_GET['action'] );
		$current_user_id = get_current_user_id();

		$result  = false;
		$message = '';

		switch ( $action ) {
			case 'approve':
				$result  = $wpdb->update(
					$wpdb->prefix . 'wpmf_verifications',
					array(
						'status'      => 'approved',
						'reviewer_id' => $current_user_id,
						'updated_at'  => current_time( 'mysql' ),
					),
					array( 'id' => $verification_id ),
					array( '%s', '%d', '%s' ),
					array( '%d' )
				);
				$message = __( 'Verification approved successfully.', 'wpmatch-free' );
				break;

			case 'reject':
				$result  = $wpdb->update(
					$wpdb->prefix . 'wpmf_verifications',
					array(
						'status'      => 'rejected',
						'reviewer_id' => $current_user_id,
						'updated_at'  => current_time( 'mysql' ),
					),
					array( 'id' => $verification_id ),
					array( '%s', '%d', '%s' ),
					array( '%d' )
				);
				$message = __( 'Verification rejected successfully.', 'wpmatch-free' );
				break;

			case 'delete':
				$result  = $wpdb->delete(
					$wpdb->prefix . 'wpmf_verifications',
					array( 'id' => $verification_id ),
					array( '%d' )
				);
				$message = __( 'Verification deleted successfully.', 'wpmatch-free' );
				break;
		}

		if ( $result ) {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);
		} else {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Action failed. Please try again.', 'wpmatch-free' ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Handle bulk verification actions
	 *
	 * @since 1.0.0
	 */
	private function handle_verification_bulk_actions() {
		global $wpdb;

		if ( empty( $_POST['verification_ids'] ) || ! is_array( $_POST['verification_ids'] ) ) {
			return;
		}

		$verification_ids = array_map( 'intval', $_POST['verification_ids'] );
		$action           = sanitize_text_field( $_POST['action'] );
		$current_user_id  = get_current_user_id();

		$count   = 0;
		$message = '';

		switch ( $action ) {
			case 'approve':
				foreach ( $verification_ids as $id ) {
					$result = $wpdb->update(
						$wpdb->prefix . 'wpmf_verifications',
						array(
							'status'      => 'approved',
							'reviewer_id' => $current_user_id,
							'updated_at'  => current_time( 'mysql' ),
						),
						array( 'id' => $id ),
						array( '%s', '%d', '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf(
					_n( '%d verification approved.', '%d verifications approved.', $count, 'wpmatch-free' ),
					$count
				);
				break;

			case 'reject':
				foreach ( $verification_ids as $id ) {
					$result = $wpdb->update(
						$wpdb->prefix . 'wpmf_verifications',
						array(
							'status'      => 'rejected',
							'reviewer_id' => $current_user_id,
							'updated_at'  => current_time( 'mysql' ),
						),
						array( 'id' => $id ),
						array( '%s', '%d', '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf(
					_n( '%d verification rejected.', '%d verifications rejected.', $count, 'wpmatch-free' ),
					$count
				);
				break;

			case 'delete':
				foreach ( $verification_ids as $id ) {
					$result = $wpdb->delete(
						$wpdb->prefix . 'wpmf_verifications',
						array( 'id' => $id ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf(
					_n( '%d verification deleted.', '%d verifications deleted.', $count, 'wpmatch-free' ),
					$count
				);
				break;
		}

		if ( $count > 0 ) {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Render profile fields management page
	 *
	 * @since 1.0.0
	 */
	public function render_fields_page() {
		global $wpdb;

		// Get existing fields.
		$fields = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpmatch_profile_fields ORDER BY display_order ASC" );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Profile Fields Management', 'wpmatch-free' ); ?></h1>
			
			<div class="wpmatch-field-manager">
				<div class="field-tabs">
					<button class="tab-button active" data-tab="basic"><?php esc_html_e( 'Basic Identity', 'wpmatch-free' ); ?></button>
					<button class="tab-button" data-tab="location"><?php esc_html_e( 'Location', 'wpmatch-free' ); ?></button>
					<button class="tab-button" data-tab="appearance"><?php esc_html_e( 'Appearance', 'wpmatch-free' ); ?></button>
					<button class="tab-button" data-tab="lifestyle"><?php esc_html_e( 'Lifestyle', 'wpmatch-free' ); ?></button>
					<button class="tab-button" data-tab="interests"><?php esc_html_e( 'Interests', 'wpmatch-free' ); ?></button>
				</div>

				<div class="field-content">
					<?php foreach ( $this->get_field_groups() as $group_id => $group_label ) : ?>
						<div class="tab-content" id="tab-<?php echo esc_attr( $group_id ); ?>" <?php echo 'basic' !== $group_id ? 'style="display:none"' : ''; ?>>
							<div class="fields-header">
								<h2><?php echo esc_html( $group_label ); ?></h2>
								<button class="button button-primary add-field-btn" data-group="<?php echo esc_attr( $group_id ); ?>">
									<?php esc_html_e( 'Add Field', 'wpmatch-free' ); ?>
								</button>
							</div>
							
							<div class="sortable-fields" data-group="<?php echo esc_attr( $group_id ); ?>">
								<?php
								$group_fields = array_filter(
									$fields,
									function ( $field ) use ( $group_id ) {
										return $field->field_group === $group_id;
									}
								);

								foreach ( $group_fields as $field ) :
									?>
									<div class="field-row" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
										<div class="field-handle">
											<span class="dashicons dashicons-move"></span>
										</div>
										<div class="field-info">
											<strong><?php echo esc_html( $field->field_label ); ?></strong>
											<span class="field-type">(<?php echo esc_html( $field->field_type ); ?>)</span>
											<div class="field-meta">
												<?php if ( $field->is_required ) : ?>
														<span class="required-badge"><?php esc_html_e( 'Required', 'wpmatch-free' ); ?></span>
												<?php endif; ?>
												<?php if ( $field->searchable ) : ?>
													<span class="searchable-badge"><?php esc_html_e( 'Searchable', 'wpmatch-free' ); ?></span>
												<?php endif; ?>
											</div>
										</div>
										<div class="field-actions">
											<button class="button edit-field" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
												<?php esc_html_e( 'Edit', 'wpmatch-free' ); ?>
											</button>
											<button class="button delete-field" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
												<?php esc_html_e( 'Delete', 'wpmatch-free' ); ?>
											</button>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<!-- Field Editor Modal -->
		<div id="field-editor-modal" class="wpmatch-modal" style="display:none;">
			<div class="modal-content">
				<div class="modal-header">
					<h2 id="modal-title"><?php esc_html_e( 'Add Field', 'wpmatch-free' ); ?></h2>
					<button class="modal-close">&times;</button>
				</div>
				<div class="modal-body">
					<form id="field-form">
						<input type="hidden" id="field-id" name="field_id">
						<input type="hidden" id="field-group" name="field_group">
						
						<table class="form-table">
							<tr>
								<th><label for="field_key"><?php esc_html_e( 'Field Key', 'wpmatch-free' ); ?></label></th>
								<td>
									<input type="text" id="field_key" name="field_key" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'Machine-readable name (letters, numbers, underscores only)', 'wpmatch-free' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="field_label"><?php esc_html_e( 'Field Label', 'wpmatch-free' ); ?></label></th>
								<td>
									<input type="text" id="field_label" name="field_label" class="regular-text" required>
								</td>
							</tr>
							<tr>
								<th><label for="field_type"><?php esc_html_e( 'Field Type', 'wpmatch-free' ); ?></label></th>
								<td>
									<select id="field_type" name="field_type" required>
										<option value="text"><?php esc_html_e( 'Text', 'wpmatch-free' ); ?></option>
										<option value="textarea"><?php esc_html_e( 'Text Area', 'wpmatch-free' ); ?></option>
										<option value="select"><?php esc_html_e( 'Dropdown', 'wpmatch-free' ); ?></option>
										<option value="multiselect"><?php esc_html_e( 'Multi-Select', 'wpmatch-free' ); ?></option>
										<option value="date"><?php esc_html_e( 'Date', 'wpmatch-free' ); ?></option>
										<option value="location"><?php esc_html_e( 'Location', 'wpmatch-free' ); ?></option>
										<option value="image"><?php esc_html_e( 'Image Upload', 'wpmatch-free' ); ?></option>
									</select>
								</td>
							</tr>
							<tr id="options-row" style="display:none;">
								<th><label for="field_options"><?php esc_html_e( 'Options', 'wpmatch-free' ); ?></label></th>
								<td>
									<div id="options-container">
										<!-- Options will be added dynamically -->
									</div>
									<button type="button" id="add-option" class="button"><?php esc_html_e( 'Add Option', 'wpmatch-free' ); ?></button>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Field Settings', 'wpmatch-free' ); ?></th>
								<td>
									<label>
										<input type="checkbox" id="is_required" name="is_required" value="1">
										<?php esc_html_e( 'Required field', 'wpmatch-free' ); ?>
									</label><br>
									<label>
										<input type="checkbox" id="searchable" name="searchable" value="1">
										<?php esc_html_e( 'Searchable field', 'wpmatch-free' ); ?>
									</label>
								</td>
							</tr>
						</table>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="button" id="cancel-field"><?php esc_html_e( 'Cancel', 'wpmatch-free' ); ?></button>
					<button type="button" class="button button-primary" id="save-field"><?php esc_html_e( 'Save Field', 'wpmatch-free' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'wpmatch_settings',
			'wpmatch_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'array',
						'items'   => array(
							'type'       => 'object',
							'properties' => array(
								'profile_visibility' => array(
									'type' => 'string',
								),
								'new_user_approval'  => array(
									'type' => 'string',
								),
							),
						),
						'default' => array(
							array(
								'profile_visibility' => 'public',
								'new_user_approval'  => 'no',
							),
						),
					),
				),
				'default'           => array(
					array(
						'profile_visibility' => 'public',
						'new_user_approval'  => 'no',
					),
				),
			)
		);

		add_settings_section(
			'wpmatch_main',
			__( 'Main Settings', 'wpmatch-free' ),
			array( $this, 'render_settings_section' ),
			'wpmatch-settings'
		);

		add_settings_field(
			'profile_visibility',
			__( 'Profile Visibility', 'wpmatch-free' ),
			array( $this, 'render_profile_visibility_field' ),
			'wpmatch-settings',
			'wpmatch_main'
		);

		add_settings_field(
			'new_user_approval',
			__( 'New User Approval', 'wpmatch-free' ),
			array( $this, 'render_approval_field' ),
			'wpmatch-settings',
			'wpmatch_main'
		);
	}

	/**
	 * Sanitize plugin settings before saving.
	 *
	 * @param array $input Raw input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized                       = array();
		$sanitized['profile_visibility'] = in_array( $input['profile_visibility'], array( 'public', 'members' ), true )
			? $input['profile_visibility']
			: 'public';
		$sanitized['new_user_approval']  = isset( $input['new_user_approval'] ) ? 'yes' : 'no';
		return $sanitized;
	}

	/**
	 * Render the profile visibility select field.
	 */
	public function render_profile_visibility_field() {
		$settings = get_option( 'wpmatch_settings' );
		?>
		<select name="wpmatch_settings[profile_visibility]">
			<option value="public" <?php selected( $settings['profile_visibility'] ?? 'public', 'public' ); ?>>
				<?php esc_html_e( 'Public', 'wpmatch-free' ); ?>
			</option>
			<option value="members" <?php selected( $settings['profile_visibility'] ?? 'public', 'members' ); ?>>
				<?php esc_html_e( 'Members Only', 'wpmatch-free' ); ?>
			</option>
		</select>
		<?php
	}

	/**
	 * Render the new user approval checkbox field.
	 */
	public function render_approval_field() {
		$settings = get_option( 'wpmatch_settings' );
		?>
		<label>
			<input type="checkbox" name="wpmatch_settings[new_user_approval]" value="1" 
				<?php checked( $settings['new_user_approval'] ?? 'no', 'yes' ); ?>>
			<?php esc_html_e( 'Require admin approval for new members', 'wpmatch-free' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the settings section description.
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__( 'Configure core plugin settings', 'wpmatch-free' ) . '</p>';
	}

	/**
	 * Render the main plugin settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Show settings saved message.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'wpmatch_messages',
				'wpmatch_message',
				__( 'Settings Saved', 'wpmatch-free' ),
				'updated'
			);
		}

		settings_errors( 'wpmatch_messages' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Match Settings', 'wpmatch-free' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpmatch_settings' );
				do_settings_sections( 'wpmatch-settings' );
				submit_button( __( 'Save Settings', 'wpmatch-free' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=wpmatch-settings' ),
			__( 'Settings', 'wpmatch-free' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render demo content management page
	 *
	 * @since 1.0.0
	 */
	public function render_demo_page() {
		// Get demo statistics.
		$demo  = new WPMatch_Demo();
		$stats = $demo->get_demo_stats();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Demo Content Management', 'wpmatch-free' ); ?></h1>
			
			<div class="wpmatch-demo-manager">
				<!-- Demo Statistics -->
				<div class="demo-stats-section">
					<h2><?php esc_html_e( 'Demo Content Statistics', 'wpmatch-free' ); ?></h2>
					<div class="demo-stats-grid">
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( $stats['total_demo_users'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Demo Users', 'wpmatch-free' ); ?></div>
						</div>
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( $stats['users_with_profiles'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'With Profiles', 'wpmatch-free' ); ?></div>
						</div>
						<div class="stat-card">
							<div class="stat-number"><?php echo esc_html( $stats['free_limit'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Free Limit', 'wpmatch-free' ); ?></div>
						</div>
						<div class="stat-card <?php echo $stats['can_create_more'] ? 'available' : 'limit-reached'; ?>">
							<div class="stat-number"><?php echo esc_html( $stats['free_limit'] - $stats['total_demo_users'] ); ?></div>
							<div class="stat-label"><?php esc_html_e( 'Remaining', 'wpmatch-free' ); ?></div>
						</div>
					</div>
				</div>

				<!-- Demo Actions -->
				<div class="demo-actions-section">
					<h2><?php esc_html_e( 'Demo Content Actions', 'wpmatch-free' ); ?></h2>
					
					<div class="demo-action-cards">
						<div class="action-card create-users">
							<h3><?php esc_html_e( 'Create Demo Users', 'wpmatch-free' ); ?></h3>
							<p><?php esc_html_e( 'Generate realistic demo users with complete profiles to test your dating site features and design.', 'wpmatch-free' ); ?></p>
							
							<div class="create-users-form">
								<label for="demo-user-count"><?php esc_html_e( 'Number of users to create:', 'wpmatch-free' ); ?></label>
								<select id="demo-user-count" name="demo_user_count">
									<option value="5">5 <?php esc_html_e( 'users', 'wpmatch-free' ); ?></option>
									<option value="10" selected>10 <?php esc_html_e( 'users', 'wpmatch-free' ); ?></option>
									<option value="15">15 <?php esc_html_e( 'users', 'wpmatch-free' ); ?></option>
									<option value="20">20 <?php esc_html_e( 'users', 'wpmatch-free' ); ?></option>
								</select>
								
								<button type="button" class="button button-primary" id="create-demo-users" <?php echo ! $stats['can_create_more'] ? 'disabled' : ''; ?>>
									<?php esc_html_e( 'Create Demo Users', 'wpmatch-free' ); ?>
								</button>
							</div>
							
							<?php if ( ! $stats['can_create_more'] ) : ?>
								<p class="demo-limit-notice">
									<?php esc_html_e( 'Free limit reached. Clean up existing demo users or upgrade for more.', 'wpmatch-free' ); ?>
								</p>
							<?php endif; ?>
						</div>

						<div class="action-card cleanup-users">
							<h3><?php esc_html_e( 'Clean Up Demo Content', 'wpmatch-free' ); ?></h3>
							<p><?php esc_html_e( 'Remove all demo users and their profile data. This action cannot be undone.', 'wpmatch-free' ); ?></p>
							
							<button type="button" class="button button-secondary" id="cleanup-demo-users" <?php echo 0 === $stats['total_demo_users'] ? 'disabled' : ''; ?>>
								<?php esc_html_e( 'Clean Up All Demo Users', 'wpmatch-free' ); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Premium Demo Packs -->
				<div class="demo-addons-section">
					<h2><?php esc_html_e( 'Premium Demo Content Packs', 'wpmatch-free' ); ?></h2>
					<div class="addon-packs">
						<div class="pack-card">
							<h4><?php esc_html_e( 'Extended Demo Pack', 'wpmatch-free' ); ?></h4>
							<p><?php esc_html_e( '100 additional diverse demo users with enhanced profiles', 'wpmatch-free' ); ?></p>
							<span class="pack-price"><?php esc_html_e( '$19.99', 'wpmatch-free' ); ?></span>
							<button class="button button-primary" disabled><?php esc_html_e( 'Coming Soon', 'wpmatch-free' ); ?></button>
						</div>
						<div class="pack-card">
							<h4><?php esc_html_e( 'Professional Demo Pack', 'wpmatch-free' ); ?></h4>
							<p><?php esc_html_e( '500 demo users + demo messages and interactions', 'wpmatch-free' ); ?></p>
							<span class="pack-price"><?php esc_html_e( '$49.99', 'wpmatch-free' ); ?></span>
							<button class="button button-primary" disabled><?php esc_html_e( 'Coming Soon', 'wpmatch-free' ); ?></button>
						</div>
						<div class="pack-card">
							<h4><?php esc_html_e( 'Enterprise Demo Pack', 'wpmatch-free' ); ?></h4>
							<p><?php esc_html_e( 'Unlimited demo users + custom profile generation', 'wpmatch-free' ); ?></p>
							<span class="pack-price"><?php esc_html_e( '$99.99', 'wpmatch-free' ); ?></span>
							<button class="button button-primary" disabled><?php esc_html_e( 'Coming Soon', 'wpmatch-free' ); ?></button>
						</div>
					</div>
				</div>

				<!-- Tips and Information -->
				<div class="demo-info-section">
					<h3><?php esc_html_e( 'Demo Content Tips', 'wpmatch-free' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Demo users are created with realistic profiles and photos to showcase your site', 'wpmatch-free' ); ?></li>
						<li><?php esc_html_e( 'All demo users are marked as "demo" and can be easily identified and removed', 'wpmatch-free' ); ?></li>
						<li><?php esc_html_e( 'Demo content helps you test features, see design layouts, and show the site to clients', 'wpmatch-free' ); ?></li>
						<li><?php esc_html_e( 'Free version includes 20 demo users - upgrade for larger demo populations', 'wpmatch-free' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get field groups
	 *
	 * @since 1.0.0
	 * @return array Field groups with labels.
	 */
	private function get_field_groups() {
		return array(
			'basic'        => __( 'Basic Identity', 'wpmatch-free' ),
			'location'     => __( 'Location & Lifestyle', 'wpmatch-free' ),
			'appearance'   => __( 'Appearance', 'wpmatch-free' ),
			'lifestyle'    => __( 'Lifestyle', 'wpmatch-free' ),
			'interests'    => __( 'Interests', 'wpmatch-free' ),
			'verification' => __( 'Verification', 'wpmatch-free' ),
		);
	}

	/**
	 * AJAX: Save field
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_field() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Security check for required POST data.
		if ( empty( $_POST['field_key'] ) || empty( $_POST['field_label'] ) || empty( $_POST['field_type'] ) ) {
			wp_send_json_error( 'Required field data missing' );
		}

		$field_data = array(
			'field_key'   => sanitize_key( wp_unslash( $_POST['field_key'] ) ),
			'field_label' => sanitize_text_field( wp_unslash( $_POST['field_label'] ) ),
			'field_type'  => sanitize_text_field( wp_unslash( $_POST['field_type'] ) ),
			'field_group' => sanitize_text_field( wp_unslash( $_POST['field_group'] ?? 'basic' ) ),
			'is_required' => isset( $_POST['is_required'] ) ? 1 : 0,
			'searchable'  => isset( $_POST['searchable'] ) ? 1 : 0,
			'options'     => isset( $_POST['options'] ) ? wp_json_encode( array_map( 'sanitize_text_field', wp_unslash( $_POST['options'] ) ) ) : null,
		);

		global $wpdb;

		// Check if field_id is set before using it.
		if ( isset( $_POST['field_id'] ) && ! empty( $_POST['field_id'] ) ) {
			// Update existing field.
			$result = $wpdb->update(
				$wpdb->prefix . 'wpmatch_profile_fields',
				$field_data,
				array( 'field_id' => intval( $_POST['field_id'] ) )
			);
		} else {
			// Create new field.
			$max_order                   = $wpdb->get_var( "SELECT MAX(display_order) + 1 FROM {$wpdb->prefix}wpmatch_profile_fields" );
			$field_data['display_order'] = $max_order ? $max_order : 0;
			$result                      = $wpdb->insert( $wpdb->prefix . 'wpmatch_profile_fields', $field_data );
		}

		if ( false !== $result ) {
			wp_send_json_success( 'Field saved successfully' );
		} else {
			wp_send_json_error( 'Failed to save field' );
		}
	}

	/**
	 * AJAX: Delete field
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_field() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate field_id before usage and ensure nonce verification for form data.
		if ( ! isset( $_POST['field_id'] ) || empty( $_POST['field_id'] ) ) {
			wp_send_json_error( 'Missing field_id.' );
		}
		$field_id = intval( $_POST['field_id'] );

		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . 'wpmatch_profile_fields',
			array( 'field_id' => $field_id ),
			array( '%d' )
		);

		if ( $result ) {
			wp_send_json_success( 'Field deleted successfully' );
		} else {
			wp_send_json_error( 'Failed to delete field' );
		}
	}

	/**
	 * AJAX: Reorder fields
	 *
	 * @since 1.0.0
	 */
	public function ajax_reorder_fields() {
		check_ajax_referer( 'wpmatch_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['field_order'] ) || ! is_array( $_POST['field_order'] ) ) {
			wp_send_json_error( 'Invalid field order data' );
		}

		$field_order = array_map( 'intval', $_POST['field_order'] );

		global $wpdb;
		foreach ( $field_order as $order => $field_id ) {
			$wpdb->update(
				$wpdb->prefix . 'wpmatch_profile_fields',
				array( 'display_order' => $order ),
				array( 'field_id' => $field_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success( 'Field order updated' );
	}

	/**
	 * Get report counts by status
	 *
	 * @since 1.0.0
	 * @return array Report counts by status.
	 */
	private function get_report_counts() {
		global $wpdb;

		$counts = array(
			'all'       => 0,
			'open'      => 0,
			'resolved'  => 0,
			'dismissed' => 0,
		);

		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$wpdb->prefix}wpmf_reports GROUP BY status"
		);

		foreach ( $results as $result ) {
			$counts[ $result->status ] = (int) $result->count;
			$counts['all']            += (int) $result->count;
		}

		return $counts;
	}

	/**
	 * Get reports by status with search functionality
	 *
	 * @since 1.0.0
	 * @param string $status Report status to filter by.
	 * @param string $search Search term.
	 * @return array Reports.
	 */
	private function get_reports_by_status( $status = 'all', $search = '' ) {
		global $wpdb;

		$where_conditions = array();
		$params           = array();

		// Status filter.
		if ( 'all' !== $status ) {
			$where_conditions[] = 'r.status = %s';
			$params[]           = $status;
		}

		// Search filter.
		if ( ! empty( $search ) ) {
			$where_conditions[] = '(ru.display_name LIKE %s OR tu.display_name LIKE %s OR r.reason LIKE %s OR r.notes LIKE %s)';
			$search_term        = '%' . $wpdb->esc_like( $search ) . '%';
			$params[]           = $search_term;
			$params[]           = $search_term;
			$params[]           = $search_term;
			$params[]           = $search_term;
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$sql = "
			SELECT r.*, 
				   ru.display_name as reporter_name,
				   tu.display_name as target_name
			FROM {$wpdb->prefix}wpmf_reports r
			LEFT JOIN {$wpdb->users} ru ON r.reporter_id = ru.ID
			LEFT JOIN {$wpdb->users} tu ON r.target_id = tu.ID AND r.target_type = 'user'
			{$where_clause}
			ORDER BY r.created_at DESC
			LIMIT 50
		";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Handle single report action
	 *
	 * @since 1.0.0
	 */
	private function handle_report_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpmatch-free' ) );
		}

		$report_id = absint( $_GET['report_id'] );
		$action    = sanitize_text_field( $_GET['action'] );

		if ( ! $report_id || ! $action ) {
			return;
		}

		global $wpdb;
		$reports_table = $wpdb->prefix . 'wpmf_reports';

		$success = false;
		$message = '';

		switch ( $action ) {
			case 'resolve':
				$success = $wpdb->update(
					$reports_table,
					array( 'status' => 'resolved' ),
					array( 'id' => $report_id ),
					array( '%s' ),
					array( '%d' )
				);
				$message = $success ? __( 'Report marked as resolved.', 'wpmatch-free' ) : __( 'Failed to resolve report.', 'wpmatch-free' );
				break;

			case 'dismiss':
				$success = $wpdb->update(
					$reports_table,
					array( 'status' => 'dismissed' ),
					array( 'id' => $report_id ),
					array( '%s' ),
					array( '%d' )
				);
				$message = $success ? __( 'Report dismissed.', 'wpmatch-free' ) : __( 'Failed to dismiss report.', 'wpmatch-free' );
				break;

			case 'reopen':
				$success = $wpdb->update(
					$reports_table,
					array( 'status' => 'open' ),
					array( 'id' => $report_id ),
					array( '%s' ),
					array( '%d' )
				);
				$message = $success ? __( 'Report reopened.', 'wpmatch-free' ) : __( 'Failed to reopen report.', 'wpmatch-free' );
				break;

			case 'delete':
				$success = $wpdb->delete(
					$reports_table,
					array( 'id' => $report_id ),
					array( '%d' )
				);
				$message = $success ? __( 'Report deleted.', 'wpmatch-free' ) : __( 'Failed to delete report.', 'wpmatch-free' );
				break;
		}

		// Add admin notice
		if ( $success ) {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);
		} else {
			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);
		}

		// Redirect to remove action from URL
		wp_redirect( admin_url( 'admin.php?page=wpmatch-reports' ) );
		exit;
	}

	/**
	 * Handle bulk report actions
	 *
	 * @since 1.0.0
	 */
	private function handle_report_bulk_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpmatch-free' ) );
		}

		$action     = sanitize_text_field( $_POST['action'] );
		$report_ids = array_map( 'absint', $_POST['report_ids'] );

		if ( empty( $action ) || empty( $report_ids ) ) {
			return;
		}

		global $wpdb;
		$reports_table = $wpdb->prefix . 'wpmf_reports';

		$count   = 0;
		$message = '';

		switch ( $action ) {
			case 'resolve':
				foreach ( $report_ids as $report_id ) {
					$result = $wpdb->update(
						$reports_table,
						array( 'status' => 'resolved' ),
						array( 'id' => $report_id ),
						array( '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf( _n( '%d report marked as resolved.', '%d reports marked as resolved.', $count, 'wpmatch-free' ), $count );
				break;

			case 'dismiss':
				foreach ( $report_ids as $report_id ) {
					$result = $wpdb->update(
						$reports_table,
						array( 'status' => 'dismissed' ),
						array( 'id' => $report_id ),
						array( '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf( _n( '%d report dismissed.', '%d reports dismissed.', $count, 'wpmatch-free' ), $count );
				break;

			case 'reopen':
				foreach ( $report_ids as $report_id ) {
					$result = $wpdb->update(
						$reports_table,
						array( 'status' => 'open' ),
						array( 'id' => $report_id ),
						array( '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf( _n( '%d report reopened.', '%d reports reopened.', $count, 'wpmatch-free' ), $count );
				break;

			case 'delete':
				foreach ( $report_ids as $report_id ) {
					$result = $wpdb->delete(
						$reports_table,
						array( 'id' => $report_id ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				$message = sprintf( _n( '%d report deleted.', '%d reports deleted.', $count, 'wpmatch-free' ), $count );
				break;
		}

		// Add admin notice
		add_action(
			'admin_notices',
			function () use ( $message ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			}
		);

		// Redirect to remove POST data
		wp_redirect( admin_url( 'admin.php?page=wpmatch-reports' ) );
		exit;
	}

	/**
	 * Render members management page
	 */
	public function render_members_admin_page() {
		global $wpdb;

		// Handle bulk actions.
		if ( isset( $_POST['action'] ) && isset( $_POST['user_ids'] ) && check_admin_referer( 'bulk_members' ) ) {
			$this->handle_member_bulk_actions();
		}

		// Handle single member actions.
		if ( isset( $_GET['action'] ) && isset( $_GET['user_id'] ) && check_admin_referer( 'member_action_' . absint( wp_unslash( $_GET['user_id'] ) ) ) ) {
			$this->handle_member_action();
		}

		// Get filter parameters.
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		// Get member counts for status filter tabs.
		$counts = $this->get_member_counts();

		// Get members based on filters.
		$members = $this->get_members_by_status( $status, $search );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Member Management', 'wpmatch-free' ); ?></h1>

			<div class="tablenav top">
				<div class="alignleft actions">
					<form method="get">
						<input type="hidden" name="page" value="wpmatch-members" />
						<?php if ( 'all' !== $status ) : ?>
							<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>" />
						<?php endif; ?>
						<input type="search" id="member-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search members...', 'wpmatch-free' ); ?>" />
						<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Members', 'wpmatch-free' ); ?>" />
					</form>
				</div>
			</div>

			<ul class="subsubsub">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-members' ) ); ?>" class="<?php echo 'all' === $status ? 'current' : ''; ?>"><?php
				/* translators: %s: number of members */
				printf( esc_html__( 'All (%s)', 'wpmatch-free' ), number_format( $counts['all'] ) ); ?></a> |</li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-members&status=active' ) ); ?>" class="<?php echo 'active' === $status ? 'current' : ''; ?>"><?php
				/* translators: %s: number of active members */
				printf( esc_html__( 'Active Profiles (%s)', 'wpmatch-free' ), number_format( $counts['active'] ) ); ?></a> |</li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-members&status=verified' ) ); ?>" class="<?php echo 'verified' === $status ? 'current' : ''; ?>"><?php
				/* translators: %s: number of verified members */
				printf( esc_html__( 'Verified (%s)', 'wpmatch-free' ), number_format( $counts['verified'] ) ); ?></a> |</li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpmatch-members&status=inactive' ) ); ?>" class="<?php echo 'inactive' === $status ? 'current' : ''; ?>"><?php
				/* translators: %s: number of inactive members */
				printf( esc_html__( 'Inactive (%s)', 'wpmatch-free' ), number_format( $counts['inactive'] ) ); ?></a></li>
			</ul>

			<?php if ( ! empty( $members ) ) : ?>
				<form method="post">
					<?php wp_nonce_field( 'bulk_members' ); ?>
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<select name="action" id="bulk-action-selector-top">
								<option value=""><?php esc_html_e( 'Bulk Actions', 'wpmatch-free' ); ?></option>
								<option value="activate"><?php esc_html_e( 'Activate Profiles', 'wpmatch-free' ); ?></option>
								<option value="deactivate"><?php esc_html_e( 'Deactivate Profiles', 'wpmatch-free' ); ?></option>
								<option value="verify"><?php esc_html_e( 'Mark as Verified', 'wpmatch-free' ); ?></option>
								<option value="unverify"><?php esc_html_e( 'Remove Verification', 'wpmatch-free' ); ?></option>
								<option value="delete"><?php esc_html_e( 'Delete Users', 'wpmatch-free' ); ?></option>
							</select>
							<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'wpmatch-free' ); ?>" />
						</div>
					</div>

					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<td id="cb" class="manage-column column-cb check-column">
									<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'wpmatch-free' ); ?></label>
									<input id="cb-select-all-1" type="checkbox" />
								</td>
								<th scope="col"><?php esc_html_e( 'Avatar', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'User', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Profile Info', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Joined', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Last Active', 'wpmatch-free' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Actions', 'wpmatch-free' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $members as $member ) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="user_ids[]" value="<?php echo esc_attr( $member->ID ); ?>" />
									</th>
									<td>
										<?php echo get_avatar( $member->ID, 32 ); ?>
									</td>
									<td>
										<strong><?php echo esc_html( $member->display_name ); ?></strong><br>
										<small><?php echo esc_html( $member->user_login ); ?></small><br>
										<small><a href="mailto:<?php echo esc_attr( $member->user_email ); ?>"><?php echo esc_html( $member->user_email ); ?></a></small>
									</td>
									<td>
										<?php if ( $member->profile_status ) : ?>
											<strong><?php echo esc_html( ucfirst( $member->profile_status ) ); ?> Profile</strong><br>
											<?php if ( $member->age ) : ?>
												<small><?php echo esc_html( $member->age ); ?> years old</small><br>
											<?php endif; ?>
											<?php if ( $member->region ) : ?>
												<small><?php echo esc_html( $member->region ); ?></small><br>
											<?php endif; ?>
											<?php if ( $member->gender ) : ?>
												<small><?php echo esc_html( ucfirst( $member->gender ) ); ?></small>
											<?php endif; ?>
										<?php else : ?>
											<em><?php esc_html_e( 'No profile created', 'wpmatch-free' ); ?></em>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $member->verified ) : ?>
											<span class="status-badge status-verified">
												<?php esc_html_e( 'Verified', 'wpmatch-free' ); ?>
											</span>
										<?php else : ?>
											<span class="status-badge status-unverified">
												<?php esc_html_e( 'Unverified', 'wpmatch-free' ); ?>
											</span>
										<?php endif; ?>
										<?php if ( $member->profile_status ) : ?>
											<br><span class="status-badge status-<?php echo esc_attr( $member->profile_status ); ?>">
												<?php echo esc_html( ucfirst( $member->profile_status ) ); ?>
											</span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $member->user_registered ) ) ); ?></td>
									<td>
										<?php if ( $member->last_active ) : ?>
											<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $member->last_active ) ) ); ?>
										<?php else : ?>
											<em><?php esc_html_e( 'Never', 'wpmatch-free' ); ?></em>
										<?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $member->ID ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit User', 'wpmatch-free' ); ?>
										</a>
										<?php if ( $member->profile_status ) : ?>
											<br>
											<?php if ( 'active' === $member->profile_status ) : ?>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-members&action=deactivate&user_id=' . $member->ID ), 'member_action_' . $member->ID ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Deactivate', 'wpmatch-free' ); ?>
												</a>
											<?php else : ?>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-members&action=activate&user_id=' . $member->ID ), 'member_action_' . $member->ID ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Activate', 'wpmatch-free' ); ?>
												</a>
											<?php endif; ?>
											<?php if ( ! $member->verified ) : ?>
												<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wpmatch-members&action=verify&user_id=' . $member->ID ), 'member_action_' . $member->ID ) ); ?>" class="button button-small">
													<?php esc_html_e( 'Verify', 'wpmatch-free' ); ?>
												</a>
											<?php endif; ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>

				<style>
				.status-badge {
					display: inline-block;
					padding: 2px 6px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
				}
				.status-verified { background-color: #00a32a; color: white; }
				.status-unverified { background-color: #dba617; color: white; }
				.status-active { background-color: #00a32a; color: white; }
				.status-inactive { background-color: #d63638; color: white; }
				</style>

			<?php else : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No members found.', 'wpmatch-free' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get member counts by status
	 *
	 * @since 1.0.0
	 * @return array Member counts by status.
	 */
	private function get_member_counts() {
		global $wpdb;

		$counts = array(
			'all'      => 0,
			'active'   => 0,
			'verified' => 0,
			'inactive' => 0,
		);

		// Get all users count.
		$counts['all'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

		// Get profile counts.
		$profile_results = $wpdb->get_results(
			"SELECT p.status, COUNT(*) as count 
			 FROM {$wpdb->prefix}wpmf_profiles p 
			 GROUP BY p.status"
		);

		foreach ( $profile_results as $result ) {
			if ( 'active' === $result->status ) {
				$counts['active'] = (int) $result->count;
			} elseif ( 'inactive' === $result->status ) {
				$counts['inactive'] = (int) $result->count;
			}
		}

		// Get verified users count.
		$counts['verified'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wpmf_profiles WHERE verified = 1"
		);

		return $counts;
	}

	/**
	 * Get members by status with search functionality
	 *
	 * @since 1.0.0
	 * @param string $status Member status to filter by.
	 * @param string $search Search term.
	 * @return array Members.
	 */
	private function get_members_by_status( $status = 'all', $search = '' ) {
		global $wpdb;

		$where_conditions = array();
		$params = array();

		// Search filter.
		if ( ! empty( $search ) ) {
			$where_conditions[] = '(u.user_login LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $search_term;
			$params[] = $search_term;
			$params[] = $search_term;
		}

		// Status filter.
		if ( 'active' === $status ) {
			$where_conditions[] = "p.status = 'active'";
		} elseif ( 'verified' === $status ) {
			$where_conditions[] = 'p.verified = 1';
		} elseif ( 'inactive' === $status ) {
			$where_conditions[] = "(p.status = 'inactive' OR p.status IS NULL)";
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		$sql = "
			SELECT u.*, 
				   p.status as profile_status,
				   p.verified,
				   p.age,
				   p.region,
				   p.gender,
				   p.last_active
			FROM {$wpdb->users} u
			LEFT JOIN {$wpdb->prefix}wpmf_profiles p ON u.ID = p.user_id
			{$where_clause}
			ORDER BY u.user_registered DESC
			LIMIT 100
		";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Handle single member action
	 *
	 * @since 1.0.0
	 */
	private function handle_member_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpmatch-free' ) );
		}

		$user_id = absint( wp_unslash( $_GET['user_id'] ) );
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( ! $user_id || ! $action ) {
			return;
		}

		global $wpdb;
		$profiles_table = $wpdb->prefix . 'wpmf_profiles';

		$success = false;
		$message = '';

		switch ( $action ) {
			case 'activate':
				$success = $wpdb->update(
					$profiles_table,
					array( 'status' => 'active' ),
					array( 'user_id' => $user_id ),
					array( '%s' ),
					array( '%d' )
				);
				$message = $success ? __( 'Profile activated.', 'wpmatch-free' ) : __( 'Failed to activate profile.', 'wpmatch-free' );
				break;

			case 'deactivate':
				$success = $wpdb->update(
					$profiles_table,
					array( 'status' => 'inactive' ),
					array( 'user_id' => $user_id ),
					array( '%s' ),
					array( '%d' )
				);
				$message = $success ? __( 'Profile deactivated.', 'wpmatch-free' ) : __( 'Failed to deactivate profile.', 'wpmatch-free' );
				break;

			case 'verify':
				$success = $wpdb->update(
					$profiles_table,
					array( 'verified' => 1 ),
					array( 'user_id' => $user_id ),
					array( '%d' ),
					array( '%d' )
				);
				$message = $success ? __( 'User verified.', 'wpmatch-free' ) : __( 'Failed to verify user.', 'wpmatch-free' );
				break;

			case 'unverify':
				$success = $wpdb->update(
					$profiles_table,
					array( 'verified' => 0 ),
					array( 'user_id' => $user_id ),
					array( '%d' ),
					array( '%d' )
				);
				$message = $success ? __( 'Verification removed.', 'wpmatch-free' ) : __( 'Failed to remove verification.', 'wpmatch-free' );
				break;
		}

		// Add admin notice.
		if ( $success ) {
			add_action( 'admin_notices', function() use ( $message ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function() use ( $message ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			} );
		}

		// Redirect to remove action from URL.
		wp_safe_redirect( admin_url( 'admin.php?page=wpmatch-members' ) );
		exit;
	}

	/**
	 * Handle bulk member actions
	 *
	 * @since 1.0.0
	 */
	private function handle_member_bulk_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpmatch-free' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
		$user_ids = array_map( 'absint', $_POST['user_ids'] );

		if ( empty( $action ) || empty( $user_ids ) ) {
			return;
		}

		global $wpdb;
		$profiles_table = $wpdb->prefix . 'wpmf_profiles';

		$count = 0;
		$message = '';

		switch ( $action ) {
			case 'activate':
				foreach ( $user_ids as $user_id ) {
					$result = $wpdb->update(
						$profiles_table,
						array( 'status' => 'active' ),
						array( 'user_id' => $user_id ),
						array( '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				/* translators: %d: number of profiles activated */
				$message = sprintf( _n( '%d profile activated.', '%d profiles activated.', $count, 'wpmatch-free' ), $count );
				break;

			case 'deactivate':
				foreach ( $user_ids as $user_id ) {
					$result = $wpdb->update(
						$profiles_table,
						array( 'status' => 'inactive' ),
						array( 'user_id' => $user_id ),
						array( '%s' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				/* translators: %d: number of profiles deactivated */
				$message = sprintf( _n( '%d profile deactivated.', '%d profiles deactivated.', $count, 'wpmatch-free' ), $count );
				break;

			case 'verify':
				foreach ( $user_ids as $user_id ) {
					$result = $wpdb->update(
						$profiles_table,
						array( 'verified' => 1 ),
						array( 'user_id' => $user_id ),
						array( '%d' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				/* translators: %d: number of users verified */
				$message = sprintf( _n( '%d user verified.', '%d users verified.', $count, 'wpmatch-free' ), $count );
				break;

			case 'unverify':
				foreach ( $user_ids as $user_id ) {
					$result = $wpdb->update(
						$profiles_table,
						array( 'verified' => 0 ),
						array( 'user_id' => $user_id ),
						array( '%d' ),
						array( '%d' )
					);
					if ( $result ) {
						++$count;
					}
				}
				/* translators: %d: number of users unverified */
				$message = sprintf( _n( '%d verification removed.', '%d verifications removed.', $count, 'wpmatch-free' ), $count );
				break;

			case 'delete':
				foreach ( $user_ids as $user_id ) {
					// Don't delete admin users.
					if ( user_can( $user_id, 'manage_options' ) ) {
						continue;
					}
					$result = wp_delete_user( $user_id );
					if ( $result ) {
						++$count;
					}
				}
				/* translators: %d: number of users deleted */
				$message = sprintf( _n( '%d user deleted.', '%d users deleted.', $count, 'wpmatch-free' ), $count );
				break;
		}

		// Add admin notice.
		add_action( 'admin_notices', function() use ( $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		} );

		// Redirect to remove POST data.
		wp_safe_redirect( admin_url( 'admin.php?page=wpmatch-members' ) );
		exit;
	}
}

// Initialize admin.
add_action(
	'plugins_loaded',
	function () {
		new WPMatch_Admin();
	}
);