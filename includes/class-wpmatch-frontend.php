<?php
/**
 * Frontend functionality for WP Match Free
 * Handles profile display, forms, and user interactions
 *
 * @package WPMatchFree
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend functionality for WP Match Free
 *
 * Handles profile display, forms, and user interactions on the frontend
 * of the WordPress site including shortcodes, AJAX handlers, and form rendering.
 *
 * @package WPMatchFree
 * @since   1.0.0
 */
class WPMatch_Frontend {

	/**
	 * Initialize frontend functionality
	 *
	 * Sets up WordPress hooks for scripts, shortcodes, and AJAX handlers.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_ajax_wpmatch_save_profile', array( $this, 'ajax_save_profile' ) );
		add_action( 'wp_ajax_nopriv_wpmatch_save_profile', array( $this, 'ajax_save_profile' ) );
		add_action( 'wp_ajax_wpmatch_search_profiles', array( $this, 'ajax_search_profiles' ) );
		add_action( 'wp_ajax_nopriv_wpmatch_search_profiles', array( $this, 'ajax_search_profiles' ) );
	}

	/**
	 * Enqueue frontend scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_scripts() {
		// Frontend CSS.
		wp_enqueue_style(
			'wpmatch-frontend-css',
			WPMATCH_URL . 'assets/blocks.css',
			array(),
			WPMATCH_FREE_VERSION
		);

		// Frontend JavaScript.
		wp_enqueue_script(
			'wpmatch-frontend-js',
			WPMATCH_URL . 'assets/blocks.js',
			array( 'jquery' ),
			WPMATCH_FREE_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'wpmatch-frontend-js',
			'wpmatchFrontend',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'wpmatch_frontend_nonce' ),
				'currentUserId' => get_current_user_id(),
				'strings'       => array(
					'profileSaved' => __( 'Profile saved successfully!', 'wpmatch-free' ),
					'error'        => __( 'An error occurred. Please try again.', 'wpmatch-free' ),
					'required'     => __( 'This field is required.', 'wpmatch-free' ),
					'searching'    => __( 'Searching...', 'wpmatch-free' ),
					'noResults'    => __( 'No matches found.', 'wpmatch-free' ),
				),
			)
		);
	}

	/**
	 * Register shortcodes
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'wpmatch_profile_form', array( $this, 'profile_form_shortcode' ) );
		add_shortcode( 'wpmatch_search_form', array( $this, 'search_form_shortcode' ) );
		add_shortcode( 'wpmatch_search_results', array( $this, 'search_results_shortcode' ) );
		add_shortcode( 'wpmatch_profile_display', array( $this, 'profile_display_shortcode' ) );
	}

	/**
	 * Profile form shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function profile_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'user_id' => get_current_user_id(),
				'fields'  => 'all', // Or comma-separated field keys.
			),
			$atts
		);

		if ( ! $atts['user_id'] ) {
			return '<p>' . esc_html__( 'Please log in to edit your profile.', 'wpmatch-free' ) . '</p>';
		}

		ob_start();
		$this->render_profile_form( $atts['user_id'], $atts['fields'] );
		return ob_get_clean();
	}

	/**
	 * Search form shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function search_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'fields' => 'basic', // Basic, advanced, or comma-separated field keys.
			),
			$atts
		);

		ob_start();
		$this->render_search_form( $atts['fields'] );
		return ob_get_clean();
	}

	/**
	 * Search results shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function search_results_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 12,
				'columns'  => 3,
			),
			$atts
		);

		ob_start();
		$this->render_search_results( $atts );
		return ob_get_clean();
	}

	/**
	 * Profile display shortcode
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function profile_display_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'user_id' => get_current_user_id(),
				'fields'  => 'all',
			),
			$atts
		);

		if ( ! $atts['user_id'] ) {
			return '<p>' . esc_html__( 'User not found.', 'wpmatch-free' ) . '</p>';
		}

		ob_start();
		$this->render_profile_display( $atts['user_id'], $atts['fields'] );
		return ob_get_clean();
	}

	/**
	 * Render profile form
	 *
	 * @since 1.0.0
	 * @param int    $user_id      User ID to render form for.
	 * @param string $field_filter Field filter criteria.
	 */
	public function render_profile_form( $user_id, $field_filter = 'all' ) {
		$fields      = $this->get_profile_fields( $field_filter );
		$user_values = $this->get_user_field_values( $user_id );
		?>
		<form class="wpmf-profile-edit" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<?php wp_nonce_field( 'wpmatch_frontend_nonce', 'wpmatch_nonce' ); ?>
			<input type="hidden" name="action" value="wpmatch_save_profile">
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
			
			<?php foreach ( $this->group_fields( $fields ) as $group_id => $group_fields ) : ?>
				<div class="profile-section" data-group="<?php echo esc_attr( $group_id ); ?>">
					<h3><?php echo esc_html( $this->get_group_label( $group_id ) ); ?></h3>
					
					<?php foreach ( $group_fields as $field ) : ?>
						<div class="profile-field" data-field-id="<?php echo esc_attr( $field->field_id ); ?>">
							<?php $this->render_field_input( $field, $user_values[ $field->field_key ] ?? '' ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			
			<div class="profile-form-actions">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Profile', 'wpmatch-free' ); ?>
				</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render search form
	 *
	 * @since 1.0.0
	 * @param string $field_filter Field filter criteria.
	 */
	public function render_search_form( $field_filter = 'basic' ) {
		$fields = $this->get_searchable_fields( $field_filter );
		?>
		<form class="wpmf-search-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<?php wp_nonce_field( 'wpmatch_frontend_nonce', 'wpmatch_nonce' ); ?>
			<input type="hidden" name="action" value="wpmatch_search_profiles">
			
			<div class="search-fields-grid">
				<?php foreach ( $fields as $field ) : ?>
					<div class="search-field" data-field-key="<?php echo esc_attr( $field->field_key ); ?>">
						<?php $this->render_search_field( $field ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			
			<div class="search-form-actions">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Search Profiles', 'wpmatch-free' ); ?>
				</button>
				<button type="reset" class="button">
					<?php esc_html_e( 'Clear', 'wpmatch-free' ); ?>
				</button>
			</div>
		</form>
		
		<div id="wpmf-search-results" class="wpmf-search-results">
			<!-- Results loaded via AJAX -->
		</div>
		<div id="wpmf-search-pagination" class="wpmf-pagination">
			<!-- Pagination loaded via AJAX -->
		</div>
		<?php
	}

	/**
	 * Render search results
	 *
	 * @since 1.0.0
	 * @param array $atts Display attributes.
	 */
	public function render_search_results( $atts ) {
		// This will be populated via AJAX search.
		?>
		<div class="wpmf-search-container">
			<div class="wpmf-search-summary">
				<?php esc_html_e( 'Use the search form above to find matches.', 'wpmatch-free' ); ?>
			</div>
			<div id="wpmf-search-results" class="wpmf-search-results">
				<!-- Results will be loaded here -->
			</div>
			<div id="wpmf-search-pagination" class="wpmf-pagination">
				<!-- Pagination will be loaded here -->
			</div>
		</div>
		<?php
	}

	/**
	 * Render profile display
	 *
	 * @since 1.0.0
	 * @param int    $user_id      User ID to display.
	 * @param string $field_filter Field filter criteria.
	 */
	public function render_profile_display( $user_id, $field_filter = 'all' ) {
		$fields      = $this->get_profile_fields( $field_filter );
		$user_values = $this->get_user_field_values( $user_id );
		$user        = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			echo '<p>' . esc_html__( 'User not found.', 'wpmatch-free' ) . '</p>';
			return;
		}
		?>
		<div class="wpmf-profile-display" data-user-id="<?php echo esc_attr( $user_id ); ?>">
			<div class="profile-header">
				<div class="profile-avatar">
					<?php echo get_avatar( $user_id, 150 ); ?>
				</div>
				<div class="profile-basic">
					<h2><?php echo esc_html( $user->display_name ); ?></h2>
					<?php if ( ! empty( $user_values['age'] ) ) : ?>
						<p class="profile-age"><?php echo esc_html( $user_values['age'] ); ?> years old</p>
					<?php endif; ?>
				</div>
			</div>

			<?php foreach ( $this->group_fields( $fields ) as $group_id => $group_fields ) : ?>
				<?php
				$group_has_values = false;
				foreach ( $group_fields as $field ) {
					if ( ! empty( $user_values[ $field->field_key ] ) ) {
						$group_has_values = true;
						break;
					}
				}

				if ( ! $group_has_values ) {
					continue;
				}
				?>
				
				<div class="profile-section" data-group="<?php echo esc_attr( $group_id ); ?>">
					<h3><?php echo esc_html( $this->get_group_label( $group_id ) ); ?></h3>
					
					<?php foreach ( $group_fields as $field ) : ?>
						<?php
						$value = $user_values[ $field->field_key ] ?? '';
						if ( empty( $value ) ) {
							continue;
						}
						?>
						
						<div class="profile-field" data-field-key="<?php echo esc_attr( $field->field_key ); ?>">
							<label><?php echo esc_html( $field->field_label ); ?></label>
							<div class="field-value">
								<?php $this->render_field_display( $field, $value ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render field input
	 *
	 * @since 1.0.0
	 * @param object $field Field object.
	 * @param string $value Field value.
	 */
	private function render_field_input( $field, $value = '' ) {
		$field_name = 'profile_fields[' . $field->field_key . ']';
		$field_id   = 'field_' . $field->field_key;
		$required   = $field->is_required ? 'required' : '';

		echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field->field_label );
		if ( $field->is_required ) {
			echo ' <span class="required">*</span>';
		}
		echo '</label>';

		switch ( $field->field_type ) {
			case 'text':
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" ' . esc_attr( $required ) . '>';
				break;

			case 'textarea':
				echo '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" rows="4" ' . esc_attr( $required ) . '>' . esc_textarea( $value ) . '</textarea>';
				break;

			case 'select':
				$options = json_decode( $field->options, true );
				$options = $options ? $options : array();
				echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" ' . esc_attr( $required ) . '>';
				echo '<option value="">' . esc_html__( 'Select...', 'wpmatch-free' ) . '</option>';
				foreach ( $options as $option ) {
					$selected = ( $value === $option ) ? 'selected' : '';
					echo '<option value="' . esc_attr( $option ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $option ) . '</option>';
				}
				echo '</select>';
				break;

			case 'multiselect':
				$options         = json_decode( $field->options, true );
				$options         = $options ? $options : array();
				$selected_values = is_array( $value ) ? $value : explode( ',', $value );
				foreach ( $options as $option ) {
					$checked = in_array( $option, $selected_values, true ) ? 'checked' : '';
					echo '<label><input type="checkbox" name="' . esc_attr( $field_name ) . '[]" value="' . esc_attr( $option ) . '" ' . esc_attr( $checked ) . '> ' . esc_html( $option ) . '</label><br>';
				}
				break;

			case 'date':
				echo '<input type="date" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" ' . esc_attr( $required ) . '>';
				break;

			case 'location':
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr__( 'Enter location', 'wpmatch-free' ) . '" ' . esc_attr( $required ) . '>';
				break;

			default:
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" ' . esc_attr( $required ) . '>';
				break;
		}
	}

	/**
	 * Render search field
	 *
	 * @since 1.0.0
	 * @param object $field Field object.
	 */
	private function render_search_field( $field ) {
		$field_name = 'search_fields[' . $field->field_key . ']';
		$field_id   = 'search_' . $field->field_key;

		echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field->field_label ) . '</label>';

		switch ( $field->field_type ) {
			case 'select':
			case 'multiselect':
				$options = json_decode( $field->options, true ) ?: array();
				echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '">';
				echo '<option value="">' . esc_html__( 'Any', 'wpmatch-free' ) . '</option>';
				foreach ( $options as $option ) {
					echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
				}
				echo '</select>';
				break;

			case 'date':
				echo '<div class="date-range">';
				echo '<input type="date" name="' . esc_attr( $field_name ) . '[from]" placeholder="From">';
				echo '<input type="date" name="' . esc_attr( $field_name ) . '[to]" placeholder="To">';
				echo '</div>';
				break;

			default:
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $field->field_label ) . '">';
				break;
		}
	}

	/**
	 * Render field display value
	 *
	 * @since 1.0.0
	 * @param object $field Field object.
	 * @param string $value Field value.
	 */
	private function render_field_display( $field, $value ) {
		switch ( $field->field_type ) {
			case 'multiselect':
				$values = is_array( $value ) ? $value : explode( ',', $value );
				echo '<ul class="field-list">';
				foreach ( $values as $v ) {
					echo '<li>' . esc_html( trim( $v ) ) . '</li>';
				}
				echo '</ul>';
				break;

			case 'date':
				$date = date_create( $value );
				if ( $date ) {
					echo esc_html( date_format( $date, 'F j, Y' ) );
				} else {
					echo esc_html( $value );
				}
				break;

			default:
				echo esc_html( $value );
				break;
		}
	}

	/**
	 * Get profile fields
	 *
	 * @since 1.0.0
	 * @param string $field_filter Field filter criteria.
	 * @return array Profile fields.
	 */
	private function get_profile_fields( $field_filter = 'all' ) {
		global $wpdb;

		$where = '';
		if ( $field_filter !== 'all' ) {
			if ( is_string( $field_filter ) && false !== strpos( $field_filter, ',' ) ) {
				$field_keys = array_map( 'trim', explode( ',', $field_filter ) );
				if ( ! empty( $field_keys ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $field_keys ), '%s' ) );
					$prepared_query = "AND field_key IN ($placeholders)";
					$where          = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $prepared_query ), $field_keys ) );
				}
			} else {
				$where = $wpdb->prepare( 'AND field_group = %s', $field_filter );
			}
		}

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpmatch_profile_fields WHERE 1=1 $where ORDER BY display_order ASC" );
	}

	/**
	 * Get searchable fields
	 *
	 * @since 1.0.0
	 * @param string $field_filter Field filter criteria.
	 * @return array Searchable fields.
	 */
	private function get_searchable_fields( $field_filter = 'basic' ) {
		global $wpdb;

		$where = 'AND searchable = 1';
		if ( $field_filter !== 'all' ) {
			if ( $field_filter === 'basic' ) {
				$where .= " AND field_group IN ('basic', 'location')";
			} else {
				$where .= $wpdb->prepare( ' AND field_group = %s', $field_filter );
			}
		}

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpmatch_profile_fields WHERE 1=1 $where ORDER BY display_order ASC" );
	}

	/**
	 * Get user field values
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return array Field values.
	 */
	private function get_user_field_values( $user_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT f.field_key, v.field_value 
            FROM {$wpdb->prefix}wpmatch_profile_fields f
            LEFT JOIN {$wpdb->prefix}wpmatch_profile_values v ON f.field_id = v.field_id AND v.user_id = %d
        ",
				$user_id
			)
		);

		$values = array();
		foreach ( $results as $result ) {
			$values[ $result->field_key ] = $result->field_value;
		}

		return $values;
	}

	/**
	 * Group fields by group
	 *
	 * @since 1.0.0
	 * @param array $fields Field objects.
	 * @return array Grouped fields.
	 */
	private function group_fields( $fields ) {
		$grouped = array();
		foreach ( $fields as $field ) {
			if ( ! isset( $grouped[ $field->field_group ] ) ) {
				$grouped[ $field->field_group ] = array();
			}
			$grouped[ $field->field_group ][] = $field;
		}
		return $grouped;
	}

	/**
	 * Get group label
	 *
	 * @since 1.0.0
	 * @param string $group_id Group identifier.
	 * @return string Group label.
	 */
	private function get_group_label( $group_id ) {
		$labels = array(
			'basic'        => __( 'Basic Information', 'wpmatch-free' ),
			'location'     => __( 'Location & Lifestyle', 'wpmatch-free' ),
			'appearance'   => __( 'Appearance', 'wpmatch-free' ),
			'lifestyle'    => __( 'Lifestyle', 'wpmatch-free' ),
			'interests'    => __( 'Interests', 'wpmatch-free' ),
			'verification' => __( 'Verification', 'wpmatch-free' ),
		);
		return $labels[ $group_id ] ?? ucfirst( $group_id );
	}

	/**
	 * AJAX: Save profile
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_profile() {
		check_ajax_referer( 'wpmatch_frontend_nonce', 'wpmatch_nonce' );

		$user_id      = intval( $_POST['user_id'] );
		$current_user = get_current_user_id();

		// Security check.
		if ( $user_id !== $current_user && ! current_user_can( 'edit_users' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wpmatch-free' ) );
		}

		if ( empty( $_POST['profile_fields'] ) ) {
			wp_send_json_error( __( 'No profile data received.', 'wpmatch-free' ) );
		}

		global $wpdb;

		foreach ( $_POST['profile_fields'] as $field_key => $field_value ) {
			// Get field configuration.
			$field = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wpmatch_profile_fields WHERE field_key = %s",
					$field_key
				)
			);

			if ( ! $field ) {
				continue;
			}

			// Sanitize value based on field type.
			$sanitized_value = $this->sanitize_field_value( $field_value, $field->field_type );

			// Save/update field value.
			$wpdb->replace(
				$wpdb->prefix . 'wpmatch_profile_values',
				array(
					'user_id'     => $user_id,
					'field_id'    => $field->field_id,
					'field_value' => $sanitized_value,
				),
				array( '%d', '%d', '%s' )
			);
		}

		wp_send_json_success( __( 'Profile saved successfully!', 'wpmatch-free' ) );
	}

	/**
	 * AJAX: Search profiles
	 *
	 * @since 1.0.0
	 */
	public function ajax_search_profiles() {
		check_ajax_referer( 'wpmatch_frontend_nonce', 'wpmatch_nonce' );

		$search_fields = $_POST['search_fields'] ?? array();
		$page          = intval( $_POST['page'] ?? 1 );
		$per_page      = intval( $_POST['per_page'] ?? 12 );

		// Build search query
		$results = $this->perform_profile_search( $search_fields, $page, $per_page );

		ob_start();
		$this->render_profile_cards( $results['profiles'] );
		$html = ob_get_clean();

		ob_start();
		$this->render_pagination( $results['total'], $page, $per_page );
		$pagination = ob_get_clean();

		wp_send_json_success(
			array(
				'html'       => $html,
				'pagination' => $pagination,
				'total'      => $results['total'],
				'profiles'   => $results['profiles'], // For schema.org
			)
		);
	}

	/**
	 * Perform profile search
	 *
	 * @since 1.0.0
	 * @param array $search_fields Search criteria.
	 * @param int   $page          Current page.
	 * @param int   $per_page      Results per page.
	 * @return array Search results.
	 */
	private function perform_profile_search( $search_fields, $page = 1, $per_page = 12 ) {
		global $wpdb;

		$offset = ( $page - 1 ) * $per_page;

		// Base query
		$query            = "SELECT DISTINCT u.ID, u.display_name FROM {$wpdb->users} u";
		$where_conditions = array();
		$join_conditions  = array();
		$query_params     = array();

		// Add search conditions
		foreach ( $search_fields as $field_key => $field_value ) {
			if ( empty( $field_value ) ) {
				continue;
			}

			$field = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wpmatch_profile_fields WHERE field_key = %s",
					$field_key
				)
			);

			if ( ! $field ) {
				continue;
			}

			$alias             = 'pv_' . $field_key;
			$join_conditions[] = "LEFT JOIN {$wpdb->prefix}wpmatch_profile_values {$alias} ON u.ID = {$alias}.user_id AND {$alias}.field_id = {$field->field_id}";

			if ( is_array( $field_value ) ) {
				// Date range or multi-value search
				if ( isset( $field_value['from'] ) && ! empty( $field_value['from'] ) ) {
					$where_conditions[] = "{$alias}.field_value >= %s";
					$query_params[]     = $field_value['from'];
				}
				if ( isset( $field_value['to'] ) && ! empty( $field_value['to'] ) ) {
					$where_conditions[] = "{$alias}.field_value <= %s";
					$query_params[]     = $field_value['to'];
				}
			} else {
				$where_conditions[] = "{$alias}.field_value LIKE %s";
				$query_params[]     = '%' . $field_value . '%';
			}
		}

		// Build final query
		if ( ! empty( $join_conditions ) ) {
			$query .= ' ' . implode( ' ', $join_conditions );
		}

		if ( ! empty( $where_conditions ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where_conditions );
		}

		// Get total count
		$count_query = str_replace( 'SELECT DISTINCT u.ID, u.display_name', 'SELECT COUNT(DISTINCT u.ID)', $query );
		$total       = $wpdb->get_var( $wpdb->prepare( $count_query, $query_params ) );

		// Get paginated results
		$query         .= ' ORDER BY u.display_name LIMIT %d OFFSET %d';
		$query_params[] = $per_page;
		$query_params[] = $offset;

		$profiles = $wpdb->get_results( $wpdb->prepare( $query, $query_params ) );

		return array(
			'profiles' => $profiles,
			'total'    => intval( $total ),
		);
	}

	/**
	 * Render profile cards
	 *
	 * @since 1.0.0
	 * @param array $profiles Profile objects.
	 */
	private function render_profile_cards( $profiles ) {
		if ( empty( $profiles ) ) {
			echo '<div class="wpmf-no-results">' . esc_html__( 'No profiles found matching your criteria.', 'wpmatch-free' ) . '</div>';
			return;
		}

		foreach ( $profiles as $profile ) {
			$user_values = $this->get_user_field_values( $profile->ID );
			?>
			<div class="wpmf-card" data-user-id="<?php echo esc_attr( $profile->ID ); ?>">
				<div class="wpmf-card-avatar">
					<?php echo get_avatar( $profile->ID, 80 ); ?>
				</div>
				<div class="wpmf-card-info">
					<h3 class="wpmf-card-headline"><?php echo esc_html( $profile->display_name ); ?></h3>
					
					<?php if ( ! empty( $user_values['age'] ) ) : ?>
						<div class="wpmf-card-age"><?php echo esc_html( $user_values['age'] ); ?> years old</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $user_values['location'] ) ) : ?>
						<div class="wpmf-card-region"><?php echo esc_html( $user_values['location'] ); ?></div>
					<?php endif; ?>
					
					<?php if ( ! empty( $user_values['about_me'] ) ) : ?>
						<div class="wpmf-card-bio"><?php echo wp_trim_words( esc_html( $user_values['about_me'] ), 20 ); ?></div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Render pagination
	 *
	 * @since 1.0.0
	 * @param int $total        Total results.
	 * @param int $current_page Current page.
	 * @param int $per_page     Results per page.
	 */
	private function render_pagination( $total, $current_page, $per_page ) {
		$total_pages = ceil( $total / $per_page );

		if ( $total_pages <= 1 ) {
			return;
		}

		echo '<div class="wpmf-pagination-info">';
		printf( __( 'Showing page %1$d of %2$d (%3$d total profiles)', 'wpmatch-free' ), $current_page, $total_pages, $total );
		echo '</div>';

		echo '<ul class="wpmf-pagination-links">';

		// Previous
		if ( $current_page > 1 ) {
			echo '<li class="wpmf-pagination-prev"><button data-page="' . ( $current_page - 1 ) . '">' . esc_html__( 'Previous', 'wpmatch-free' ) . '</button></li>';
		}

		// Page numbers
		for ( $i = max( 1, $current_page - 2 ); $i <= min( $total_pages, $current_page + 2 ); $i++ ) {
			$class = ( $i === $current_page ) ? 'wpmf-pagination-current' : '';
			echo '<li class="' . $class . '"><button data-page="' . $i . '">' . $i . '</button></li>';
		}

		// Next
		if ( $current_page < $total_pages ) {
			echo '<li class="wpmf-pagination-next"><button data-page="' . ( $current_page + 1 ) . '">' . esc_html__( 'Next', 'wpmatch-free' ) . '</button></li>';
		}

		echo '</ul>';
	}

	/**
	 * Sanitize field value
	 */
	private function sanitize_field_value( $value, $field_type ) {
		switch ( $field_type ) {
			case 'multiselect':
				return is_array( $value ) ? implode( ',', array_map( 'sanitize_text_field', $value ) ) : sanitize_text_field( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'date':
				return sanitize_text_field( $value ); // Could add date validation
			default:
				return sanitize_text_field( $value );
		}
	}
}

// Initialize frontend
add_action('plugins_loaded', function() { new WPMatch_Frontend(); });