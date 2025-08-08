<?php
/**
 * Pagination system for WP Match Free.
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pagination helper class.
 *
 * @since 0.1.0
 */
class WPMF_Pagination {

	/**
	 * Default items per page.
	 *
	 * @since 0.1.0
	 */
	const DEFAULT_PER_PAGE = 20;

	/**
	 * Maximum items per page.
	 *
	 * @since 0.1.0
	 */
	const MAX_PER_PAGE = 50;

	/**
	 * Get pagination data for search results.
	 *
	 * @param array $filters Search filters.
	 * @param int   $current_page Current page number.
	 * @param int   $per_page Items per page.
	 * @param int   $user_id Current user ID.
	 * @return array Pagination data with results.
	 * @since 0.1.0
	 */
	public static function get_paginated_search( $filters = array(), $current_page = 1, $per_page = self::DEFAULT_PER_PAGE, $user_id = 0 ) {
		// Validate and sanitize input
		$current_page = max( 1, absint( $current_page ) );
		$per_page     = min( self::MAX_PER_PAGE, max( 1, absint( $per_page ) ) );
		$offset       = ( $current_page - 1 ) * $per_page;
		
		// Generate cache keys for both results and count
		$cache_key_results = WPMF_Cache::generate_search_key( 
			array_merge( $filters, array( 'page' => $current_page, 'per_page' => $per_page ) ), 
			$user_id 
		);
		$cache_key_count = WPMF_Cache::generate_search_key( 
			array_merge( $filters, array( 'count_only' => true ) ), 
			$user_id 
		);
		
		// Try to get cached results
		$cached_data = WPMF_Cache::get_search_results( 
			array_merge( $filters, array( 'page' => $current_page, 'per_page' => $per_page ) ), 
			$user_id 
		);
		$cached_count = WPMF_Cache::get_search_results( 
			array_merge( $filters, array( 'count_only' => true ) ), 
			$user_id 
		);
		
		if ( false !== $cached_data && false !== $cached_count ) {
			$results     = $cached_data;
			$total_items = $cached_count;
		} else {
			// Cache miss - fetch from database
			$results = WPMF_DB_Optimization::optimized_search( $filters, $user_id, $per_page, $offset );
			$total_items = WPMF_DB_Optimization::get_search_count( $filters, $user_id );
			
			// Cache both results and count
			WPMF_Cache::set_search_results( 
				array_merge( $filters, array( 'page' => $current_page, 'per_page' => $per_page ) ), 
				$results, 
				$user_id 
			);
			WPMF_Cache::set_search_results( 
				array_merge( $filters, array( 'count_only' => true ) ), 
				$total_items, 
				$user_id 
			);
		}
		
		$total_pages = ceil( $total_items / $per_page );
		
		return array(
			'results'      => $results,
			'total_items'  => $total_items,
			'total_pages'  => $total_pages,
			'current_page' => $current_page,
			'per_page'     => $per_page,
			'offset'       => $offset,
			'has_prev'     => $current_page > 1,
			'has_next'     => $current_page < $total_pages,
			'prev_page'    => max( 1, $current_page - 1 ),
			'next_page'    => min( $total_pages, $current_page + 1 ),
		);
	}

	/**
	 * Generate pagination HTML.
	 *
	 * @param array  $pagination_data Pagination data from get_paginated_search().
	 * @param string $base_url Base URL for pagination links.
	 * @param array  $url_args Additional URL arguments to preserve.
	 * @return string Pagination HTML.
	 * @since 0.1.0
	 */
	public static function render_pagination( $pagination_data, $base_url = '', $url_args = array() ) {
		if ( $pagination_data['total_pages'] <= 1 ) {
			return '';
		}
		
		$current_page = $pagination_data['current_page'];
		$total_pages  = $pagination_data['total_pages'];
		
		if ( empty( $base_url ) ) {
			$base_url = remove_query_arg( 'paged' );
		}
		
		$output = '<nav class="wpmf-pagination" aria-label="' . esc_attr__( 'Search results pagination', 'wpmatch-free' ) . '">';
		$output .= '<div class="wpmf-pagination-info">';
		$output .= sprintf(
			/* translators: 1: current page, 2: total pages, 3: total items */
			esc_html__( 'Page %1$d of %2$d (%3$d total results)', 'wpmatch-free' ),
			$current_page,
			$total_pages,
			$pagination_data['total_items']
		);
		$output .= '</div>';
		
		$output .= '<ul class="wpmf-pagination-links">';
		
		// Previous page link
		if ( $pagination_data['has_prev'] ) {
			$prev_url = add_query_arg( 
				array_merge( $url_args, array( 'paged' => $pagination_data['prev_page'] ) ), 
				$base_url 
			);
			$output .= '<li class="wpmf-pagination-prev">';
			$output .= '<a href="' . esc_url( $prev_url ) . '" rel="prev">' . esc_html__( '« Previous', 'wpmatch-free' ) . '</a>';
			$output .= '</li>';
		}
		
		// Page number links
		$start_page = max( 1, $current_page - 2 );
		$end_page   = min( $total_pages, $current_page + 2 );
		
		// First page link
		if ( $start_page > 1 ) {
			$first_url = add_query_arg( 
				array_merge( $url_args, array( 'paged' => 1 ) ), 
				$base_url 
			);
			$output .= '<li><a href="' . esc_url( $first_url ) . '">1</a></li>';
			
			if ( $start_page > 2 ) {
				$output .= '<li class="wpmf-pagination-dots"><span>…</span></li>';
			}
		}
		
		// Middle page links
		for ( $i = $start_page; $i <= $end_page; $i++ ) {
			if ( $i === $current_page ) {
				$output .= '<li class="wpmf-pagination-current"><span aria-current="page">' . $i . '</span></li>';
			} else {
				$page_url = add_query_arg( 
					array_merge( $url_args, array( 'paged' => $i ) ), 
					$base_url 
				);
				$output .= '<li><a href="' . esc_url( $page_url ) . '">' . $i . '</a></li>';
			}
		}
		
		// Last page link
		if ( $end_page < $total_pages ) {
			if ( $end_page < $total_pages - 1 ) {
				$output .= '<li class="wpmf-pagination-dots"><span>…</span></li>';
			}
			
			$last_url = add_query_arg( 
				array_merge( $url_args, array( 'paged' => $total_pages ) ), 
				$base_url 
			);
			$output .= '<li><a href="' . esc_url( $last_url ) . '">' . $total_pages . '</a></li>';
		}
		
		// Next page link
		if ( $pagination_data['has_next'] ) {
			$next_url = add_query_arg( 
				array_merge( $url_args, array( 'paged' => $pagination_data['next_page'] ) ), 
				$base_url 
			);
			$output .= '<li class="wpmf-pagination-next">';
			$output .= '<a href="' . esc_url( $next_url ) . '" rel="next">' . esc_html__( 'Next »', 'wpmatch-free' ) . '</a>';
			$output .= '</li>';
		}
		
		$output .= '</ul>';
		$output .= '</nav>';
		
		return $output;
	}

	/**
	 * Get pagination for admin list tables.
	 *
	 * @param string $table_name Database table name (without prefix).
	 * @param array  $where_conditions WHERE conditions array.
	 * @param int    $current_page Current page number.
	 * @param int    $per_page Items per page.
	 * @param string $order_by ORDER BY clause.
	 * @return array Pagination data with results.
	 * @since 0.1.0
	 */
	public static function get_admin_pagination( $table_name, $where_conditions = array(), $current_page = 1, $per_page = 20, $order_by = 'id DESC' ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'wpmf_' . $table_name;
		$current_page = max( 1, absint( $current_page ) );
		$per_page = max( 1, absint( $per_page ) );
		$offset = ( $current_page - 1 ) * $per_page;
		
		// Build WHERE clause
		$where_sql = '';
		$where_params = array();
		if ( ! empty( $where_conditions ) ) {
			$where_parts = array();
			foreach ( $where_conditions as $condition ) {
				if ( isset( $condition['column'], $condition['value'], $condition['operator'] ) ) {
					$where_parts[] = $wpdb->prepare(
						"{$condition['column']} {$condition['operator']} %s",
						$condition['value']
					);
				}
			}
			if ( ! empty( $where_parts ) ) {
				$where_sql = ' WHERE ' . implode( ' AND ', $where_parts );
			}
		}
		
		// Get total count
		$count_sql = "SELECT COUNT(*) FROM {$table}{$where_sql}";
		$total_items = (int) $wpdb->get_var( $count_sql );
		
		// Get results
		$results_sql = $wpdb->prepare(
			"SELECT * FROM {$table}{$where_sql} ORDER BY {$order_by} LIMIT %d OFFSET %d",
			$per_page,
			$offset
		);
		$results = $wpdb->get_results( $results_sql, ARRAY_A );
		
		$total_pages = ceil( $total_items / $per_page );
		
		return array(
			'results'      => $results,
			'total_items'  => $total_items,
			'total_pages'  => $total_pages,
			'current_page' => $current_page,
			'per_page'     => $per_page,
			'offset'       => $offset,
			'has_prev'     => $current_page > 1,
			'has_next'     => $current_page < $total_pages,
		);
	}

	/**
	 * Get current page number from request.
	 *
	 * @param string $param_name Parameter name (default: 'paged').
	 * @return int Current page number.
	 * @since 0.1.0
	 */
	public static function get_current_page( $param_name = 'paged' ) {
		$page = 1;
		
		if ( isset( $_GET[ $param_name ] ) ) {
			$page = absint( $_GET[ $param_name ] );
		} elseif ( isset( $_POST[ $param_name ] ) ) {
			$page = absint( $_POST[ $param_name ] );
		}
		
		return max( 1, $page );
	}

	/**
	 * Get per page value from request or user meta.
	 *
	 * @param int    $default Default per page value.
	 * @param string $meta_key User meta key for stored preference.
	 * @return int Per page value.
	 * @since 0.1.0
	 */
	public static function get_per_page( $default = self::DEFAULT_PER_PAGE, $meta_key = 'wpmf_search_per_page' ) {
		$per_page = $default;
		
		// Check URL parameter
		if ( isset( $_GET['per_page'] ) ) {
			$per_page = absint( $_GET['per_page'] );
		}
		// Check user preference
		elseif ( is_user_logged_in() ) {
			$user_preference = get_user_meta( get_current_user_id(), $meta_key, true );
			if ( $user_preference ) {
				$per_page = absint( $user_preference );
			}
		}
		
		// Validate and constrain
		return min( self::MAX_PER_PAGE, max( 1, $per_page ) );
	}

	/**
	 * Save user's per page preference.
	 *
	 * @param int    $per_page Per page value to save.
	 * @param string $meta_key User meta key.
	 * @since 0.1.0
	 */
	public static function save_per_page_preference( $per_page, $meta_key = 'wpmf_search_per_page' ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		$per_page = min( self::MAX_PER_PAGE, max( 1, absint( $per_page ) ) );
		update_user_meta( get_current_user_id(), $meta_key, $per_page );
	}

	/**
	 * Generate AJAX pagination for dynamic loading.
	 *
	 * @param array $pagination_data Pagination data.
	 * @param array $ajax_args AJAX arguments.
	 * @return string AJAX pagination HTML.
	 * @since 0.1.0
	 */
	public static function render_ajax_pagination( $pagination_data, $ajax_args = array() ) {
		if ( $pagination_data['total_pages'] <= 1 ) {
			return '';
		}
		
		$current_page = $pagination_data['current_page'];
		$total_pages  = $pagination_data['total_pages'];
		
		$output = '<nav class="wpmf-pagination wpmf-ajax-pagination" data-ajax-args="' . esc_attr( wp_json_encode( $ajax_args ) ) . '">';
		$output .= '<div class="wpmf-pagination-info">';
		$output .= sprintf(
			/* translators: 1: current page, 2: total pages, 3: total items */
			esc_html__( 'Page %1$d of %2$d (%3$d total results)', 'wpmatch-free' ),
			$current_page,
			$total_pages,
			$pagination_data['total_items']
		);
		$output .= '</div>';
		
		$output .= '<ul class="wpmf-pagination-links">';
		
		// Previous page
		if ( $pagination_data['has_prev'] ) {
			$output .= '<li class="wpmf-pagination-prev">';
			$output .= '<button type="button" data-page="' . $pagination_data['prev_page'] . '" rel="prev">';
			$output .= esc_html__( '« Previous', 'wpmatch-free' );
			$output .= '</button></li>';
		}
		
		// Page numbers (simplified for AJAX)
		$start_page = max( 1, $current_page - 2 );
		$end_page   = min( $total_pages, $current_page + 2 );
		
		for ( $i = $start_page; $i <= $end_page; $i++ ) {
			if ( $i === $current_page ) {
				$output .= '<li class="wpmf-pagination-current"><span>' . $i . '</span></li>';
			} else {
				$output .= '<li><button type="button" data-page="' . $i . '">' . $i . '</button></li>';
			}
		}
		
		// Next page
		if ( $pagination_data['has_next'] ) {
			$output .= '<li class="wpmf-pagination-next">';
			$output .= '<button type="button" data-page="' . $pagination_data['next_page'] . '" rel="next">';
			$output .= esc_html__( 'Next »', 'wpmatch-free' );
			$output .= '</button></li>';
		}
		
		$output .= '</ul>';
		$output .= '</nav>';
		
		return $output;
	}

	/**
	 * Handle AJAX pagination requests.
	 *
	 * @since 0.1.0
	 */
	public static function handle_ajax_pagination() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wpmf_ajax_pagination' ) ) {
			wp_die( 'Security check failed' );
		}
		
		$page     = absint( $_POST['page'] ?? 1 );
		$per_page = absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE );
		$filters  = array();
		
		// Extract filters from request
		$allowed_filters = array( 'region', 'age_min', 'age_max', 'verified', 'has_photo' );
		foreach ( $allowed_filters as $filter ) {
			if ( isset( $_POST[ $filter ] ) ) {
				$filters[ $filter ] = sanitize_text_field( $_POST[ $filter ] );
			}
		}
		
		$user_id = get_current_user_id();
		$pagination_data = self::get_paginated_search( $filters, $page, $per_page, $user_id );
		
		$response = array(
			'success' => true,
			'data' => array(
				'results_html' => self::render_search_results_html( $pagination_data['results'] ),
				'pagination_html' => self::render_ajax_pagination( $pagination_data, $_POST ),
				'pagination_info' => array(
					'current_page' => $pagination_data['current_page'],
					'total_pages'  => $pagination_data['total_pages'],
					'total_items'  => $pagination_data['total_items'],
				),
			),
		);
		
		wp_send_json( $response );
	}

	/**
	 * Render search results HTML for AJAX responses.
	 *
	 * @param array $results Search results.
	 * @return string Results HTML.
	 * @since 0.1.0
	 */
	private static function render_search_results_html( $results ) {
		$output = '<div class="wpmf-search-results">';
		
		foreach ( $results as $profile ) {
			$output .= '<div class="wpmf-card" data-user-id="' . esc_attr( $profile['user_id'] ) . '">';
			$output .= '<div class="wpmf-card-headline">' . esc_html( $profile['headline'] ?? '' ) . '</div>';
			$output .= '<div class="wpmf-card-region">' . esc_html( $profile['region'] ?? '' ) . '</div>';
			if ( ! empty( $profile['age'] ) ) {
				$output .= '<div class="wpmf-card-age">' . esc_html( $profile['age'] ) . ' years old</div>';
			}
			$output .= '</div>';
		}
		
		if ( empty( $results ) ) {
			$output .= '<div class="wpmf-no-results">' . esc_html__( 'No profiles found matching your criteria.', 'wpmatch-free' ) . '</div>';
		}
		
		$output .= '</div>';
		
		return $output;
	}
}

// Register AJAX handler
add_action( 'wp_ajax_wpmf_pagination', array( 'WPMF_Pagination', 'handle_ajax_pagination' ) );
add_action( 'wp_ajax_nopriv_wpmf_pagination', array( 'WPMF_Pagination', 'handle_ajax_pagination' ) );