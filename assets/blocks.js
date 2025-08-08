/**
 * WP Match Free - JavaScript Assets for Blocks and Interactive Features
 * Handles all plugin interactivity, including form submissions, search, and dynamic content loading
 * 
 * @package WPMatchFree
 * @version 0.1.0
 */

// Block Editor Components
(function(blocks, element) {
    const el = element.createElement;
    const { registerBlockType } = blocks;

    /**
     * Profile Edit Block
     */
    registerBlockType('wpmf/profile-edit', {
        title: 'Profile Edit Form',
        icon: 'id',
        category: 'widgets',
        
        edit: () => {
            return el('div', 
                { className: 'wp-block-wpmatch-free-profile-edit' },
                el('div', { className: 'wpmf-block-placeholder' }, 'Profile Edit Form')
            );
        },
        
        save: () => null
    });

    /**
     * Search Form Block
     */
    registerBlockType('wpmf/search-form', {
        title: 'Dating Search Form',
        icon: 'search',
        category: 'widgets',
        
        edit: () => {
            return el('div', 
                { className: 'wp-block-wpmatch-free-search-form' },
                el('div', { className: 'wpmf-block-placeholder' }, 'Dating Search Form')
            );
        },
        
        save: () => null
    });
})(window.wp.blocks, window.wp.element);

// Frontend JavaScript
(function($) {
    'use strict';

    /**
     * Handle profile form submission
     */
    $(document).on('submit', '.wpmf-profile-edit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                createNotice('success', response.message || 'Profile saved successfully!');
            } else {
                createNotice('error', response.message || 'Error saving profile');
            }
        }).fail(function(xhr) {
            createNotice('error', 'An error occurred. Please try again.');
            console.error('Profile save error:', xhr.responseText);
        }).always(function() {
            $submitBtn.prop('disabled', false).text(originalText);
        });
    });

    /**
     * Handle search form submission
     */
    $(document).on('submit', '.wpmf-search-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $resultsContainer = $('.wpmf-search-results');
        const $paginationContainer = $('.wpmf-pagination');
        const $loader = $('#wpmf-loader');
        
        $loader.show();
        $resultsContainer.addClass('loading');
        
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function(response) {
            if (response.success && response.html) {
                $resultsContainer.html(response.html);
                
                if (response.pagination) {
                    $paginationContainer.html(response.pagination);
                }
                
                // Initialize lazy loading for new images
                initLazyLoading();
            } else {
                $resultsContainer.html('<div class="wpmf-no-results">No matches found. Try different criteria.</div>');
            }
        }).fail(function(xhr) {
            $resultsContainer.html('<div class="wpmf-no-results">Error loading results. Please try again.</div>');
            console.error('Search error:', xhr.responseText);
        }).always(function() {
            $loader.hide();
            $resultsContainer.removeClass('loading');
        });
    });

    /**
     * Lazy loading for profile images
     */
    function initLazyLoading() {
        $('img[data-src]').each(function() {
            const $img = $(this);
            $img.attr('src', $img.data('src')).removeAttr('data-src');
        });
    }

    /**
     * Create notice/alert messages
     */
    function createNotice(type, message) {
        const $notice = $('<div class="wpmf-notice notice-' + type + '"><p>' + message + '</p></div>');
        $('body').append($notice);
        
        setTimeout(function() {
            $notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        initLazyLoading();
        
        // Handle AJAX pagination
        $(document).on('click', '.wpmf-pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            
            if (url && url !== '#') {
                history.pushState(null, null, url);
                $('.wpmf-search-form').trigger('submit');
            }
        });
        
        // Notification dismissals
        $(document).on('click', '.wpmf-notice .notice-dismiss', function() {
            const noticeId = $(this).closest('.wpmf-notice').data('id');
            if (noticeId) {
                $.post(ajaxurl, {
                    action: 'wpmf_dismiss_notice',
                    notice_id: noticeId
                });
            }
        });
    });

})(jQuery);