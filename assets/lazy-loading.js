/**
 * WP Match Free - Lazy Loading and AJAX functionality
 *
 * @package WPMatchFree
 * @since 0.1.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initLazyLoading();
        initInfiniteScroll();
    });

    /**
     * Initialize lazy loading for profile images
     */
    function initLazyLoading() {
        if (!window.wpmf_ajax) {
            return;
        }

        // Lazy load profile images
        $('.wpmf-profile-image[data-src]').each(function() {
            var $img = $(this);
            var src = $img.data('src');
            
            if (src && isInViewport($img[0])) {
                loadImage($img, src);
            }
        });

        // Load images as they come into view
        $(window).on('scroll resize', debounce(function() {
            $('.wpmf-profile-image[data-src]').each(function() {
                var $img = $(this);
                var src = $img.data('src');
                
                if (src && isInViewport($img[0])) {
                    loadImage($img, src);
                }
            });
        }, 100));
    }

    /**
     * Initialize infinite scroll for search results
     */
    function initInfiniteScroll() {
        var $container = $('.wpmf-search-results');
        var $loadMore = $('.wpmf-load-more');
        
        if (!$container.length || !window.wpmf_ajax) {
            return;
        }

        $loadMore.on('click', function(e) {
            e.preventDefault();
            loadMoreResults();
        });

        // Auto-load when scrolling near bottom
        $(window).on('scroll', debounce(function() {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 500) {
                if ($loadMore.is(':visible') && !$loadMore.hasClass('loading')) {
                    loadMoreResults();
                }
            }
        }, 250));
    }

    /**
     * Load more search results via AJAX
     */
    function loadMoreResults() {
        var $loadMore = $('.wpmf-load-more');
        var $container = $('.wpmf-search-results');
        var page = parseInt($loadMore.data('page') || 1) + 1;

        if ($loadMore.hasClass('loading')) {
            return;
        }

        $loadMore.addClass('loading').text('Loading...');

        $.ajax({
            url: wpmf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpmf_load_more_results',
                nonce: wpmf_ajax.nonce,
                page: page,
                // Add current search parameters
                search_params: getSearchParams()
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $container.append(response.data.html);
                    $loadMore.data('page', page);
                    
                    if (response.data.has_more) {
                        $loadMore.removeClass('loading').text('Load More');
                    } else {
                        $loadMore.hide();
                    }
                    
                    // Initialize lazy loading for new images
                    initLazyLoading();
                } else {
                    $loadMore.hide();
                }
            },
            error: function() {
                $loadMore.removeClass('loading').text('Load More');
            }
        });
    }

    /**
     * Load profile image
     */
    function loadImage($img, src) {
        var img = new Image();
        img.onload = function() {
            $img.attr('src', src).removeAttr('data-src').addClass('loaded');
        };
        img.onerror = function() {
            $img.addClass('error').removeAttr('data-src');
        };
        img.src = src;
    }

    /**
     * Check if element is in viewport
     */
    function isInViewport(element) {
        var rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Get current search parameters
     */
    function getSearchParams() {
        var params = {};
        $('.wpmf-search-form input, .wpmf-search-form select').each(function() {
            var $input = $(this);
            if ($input.val()) {
                params[$input.attr('name')] = $input.val();
            }
        });
        return params;
    }

    /**
     * Debounce function to limit function calls
     */
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

})(jQuery);