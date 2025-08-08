/**
 * Admin JavaScript for WP Match Free
 */

jQuery(document).ready(function($) {
    'use strict';

    // Field Management
    var FieldManager = {
        init: function() {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function() {
            // Tab switching
            $('.tab-button').on('click', this.switchTab);
            
            // Add field button (use delegated binding for dynamic content)
            $(document).on('click', '.add-field-btn', this.openAddModal);
            
            // Edit field button
            $(document).on('click', '.edit-field', this.openEditModal);
            
            // Delete field button
            $(document).on('click', '.delete-field', this.deleteField);
            
            // Modal events
            $('.modal-close, #cancel-field').on('click', this.closeModal);
            $('#save-field').on('click', this.saveField);
            
            // Field type change
            $('#field_type').on('change', this.toggleOptions);
            
            // Add option button
            $('#add-option').on('click', this.addOption);
            
            // Remove option
            $(document).on('click', '.remove-option', this.removeOption);
        },

        initSortable: function() {
            $('.sortable-fields').sortable({
                handle: '.field-handle',
                placeholder: 'field-placeholder',
                helper: 'clone',
                opacity: 0.7,
                update: function(event, ui) {
                    FieldManager.updateFieldOrder($(this));
                }
            });
        },

        switchTab: function(e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            
            $('.tab-button').removeClass('active');
            $(this).addClass('active');
            
            $('.tab-content').hide();
            $('#tab-' + tabId).show();
        },

        openAddModal: function(e) {
            e.preventDefault();
            var group = $(this).data('group');
            
            $('#modal-title').text(wpmatchAdmin.strings.addField || 'Add Field');
            $('#field-form')[0].reset();
            $('#field-id').val('');
            $('#field-group').val(group);
            $('#field_key').prop('disabled', false);
            $('#options-container').empty();
            
            $('#field-editor-modal').show();
        },

        openEditModal: function(e) {
            e.preventDefault();
            var fieldId = $(this).data('field-id');
            var $row = $(this).closest('.field-row');
            
            // Get field data from the row
            var fieldData = {
                id: fieldId,
                key: $row.find('.field-info strong').text().toLowerCase().replace(/\s+/g, '_'),
                label: $row.find('.field-info strong').text(),
                type: $row.find('.field-type').text().replace(/[()]/g, ''),
                required: $row.find('.required-badge').length > 0,
                searchable: $row.find('.searchable-badge').length > 0
            };
            
            $('#modal-title').text(wpmatchAdmin.strings.editField || 'Edit Field');
            $('#field-id').val(fieldData.id);
            $('#field_key').val(fieldData.key).prop('disabled', true);
            $('#field_label').val(fieldData.label);
            $('#field_type').val(fieldData.type).trigger('change');
            $('#is_required').prop('checked', fieldData.required);
            $('#searchable').prop('checked', fieldData.searchable);
            
            $('#field-editor-modal').show();
        },

        deleteField: function(e) {
            e.preventDefault();
            
            if (!confirm(wpmatchAdmin.strings.confirmDelete)) {
                return;
            }
            
            var fieldId = $(this).data('field-id');
            var $row = $(this).closest('.field-row');
            
            $.ajax({
                url: wpmatchAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpmatch_delete_field',
                    field_id: fieldId,
                    nonce: wpmatchAdmin.nonce
                },
                beforeSend: function() {
                    $row.addClass('wpmatch-loading');
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $(this).remove();
                        });
                        FieldManager.showNotice('Field deleted successfully', 'success');
                    } else {
                        FieldManager.showNotice('Failed to delete field', 'error');
                    }
                },
                error: function() {
                    FieldManager.showNotice('Error deleting field', 'error');
                },
                complete: function() {
                    $row.removeClass('wpmatch-loading');
                }
            });
        },

        closeModal: function(e) {
            e.preventDefault();
            $('#field-editor-modal').hide();
        },

        saveField: function(e) {
            e.preventDefault();
            
            var formData = $('#field-form').serializeArray();
            var data = {
                action: 'wpmatch_save_field',
                nonce: wpmatchAdmin.nonce
            };
            
            // Convert form data to object
            $.each(formData, function(i, field) {
                if (field.name === 'options[]') {
                    if (!data.options) data.options = [];
                    data.options.push(field.value);
                } else {
                    data[field.name] = field.value;
                }
            });
            
            // Add checkbox values if not checked
            if (!$('#is_required').is(':checked')) {
                data.is_required = 0;
            }
            if (!$('#searchable').is(':checked')) {
                data.searchable = 0;
            }
            
            $.ajax({
                url: wpmatchAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    $('#save-field').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        FieldManager.showNotice(response.data || 'Field saved successfully', 'success');
                        FieldManager.closeModal();
                        FieldManager.refreshFieldList();
                    } else {
                        FieldManager.showNotice(response.data || 'Failed to save field', 'error');
                    }
                },
                error: function() {
                    FieldManager.showNotice('Error saving field', 'error');
                },
                complete: function() {
                    $('#save-field').prop('disabled', false).text('Save Field');
                }
            });
        },

        toggleOptions: function() {
            var fieldType = $(this).val();
            if (fieldType === 'select' || fieldType === 'multiselect') {
                $('#options-row').show();
            } else {
                $('#options-row').hide();
            }
        },

        addOption: function(e) {
            e.preventDefault();
            var optionHtml = '<div class="option-item">' +
                '<input type="text" name="options[]" placeholder="Option value" required>' +
                '<button type="button" class="remove-option">Remove</button>' +
                '</div>';
            $('#options-container').append(optionHtml);
        },

        removeOption: function(e) {
            e.preventDefault();
            $(this).closest('.option-item').remove();
        },

        updateFieldOrder: function($container) {
            var fieldOrder = [];
            $container.find('.field-row').each(function() {
                fieldOrder.push($(this).data('field-id'));
            });
            
            $.ajax({
                url: wpmatchAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpmatch_reorder_fields',
                    field_order: fieldOrder,
                    nonce: wpmatchAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FieldManager.showNotice('Field order updated', 'success');
                    }
                }
            });
        },

        refreshFieldList: function() {
            $.ajax({
                url: wpmatchAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpmatch_render_field_list',
                    nonce: wpmatchAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.field-content').replaceWith(response.data);
                        // Re-initialize sortable for the new content
                        FieldManager.initSortable();
                        // Note: Most events use delegated binding, so no need to re-bind
                    }
                },
                error: function() {
                    FieldManager.showNotice('Error refreshing field list', 'error');
                }
            });
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="wpmatch-notice ' + (type === 'error' ? 'error' : '') + '">' + message + '</div>');
            $('body').append($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Settings Management
    var SettingsManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Settings form handling can be added here
        }
    };

    // Demo Content Management
    var DemoManager = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Create demo users
            $('#create-demo-users').on('click', this.createDemoUsers);
            
            // Cleanup demo users
            $('#cleanup-demo-users').on('click', this.cleanupDemoUsers);
        },

        createDemoUsers: function(e) {
            e.preventDefault();
            
            if (!confirm(wpmatchAdmin.strings.confirmCreateDemo || 'Are you sure you want to create demo users?')) {
                return;
            }
            
            var $button = $(this);
            var count = $('#demo-user-count').val();
            
            $.ajax({
                url: wpmatchAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpmatch_create_demo_users',
                    count: count,
                    nonce: wpmatchAdmin.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Creating...');
                },
                success: function(response) {
                    if (response.success) {
                        DemoManager.showNotice(response.data.message, 'success');
                        DemoManager.updateStats(response.data.stats);
                    } else {
                        DemoManager.showNotice(response.data || 'Failed to create demo users', 'error');
                    }
                },
                error: function() {
                    DemoManager.showNotice('Error creating demo users', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Create Demo Users');
                }
            });
        },

        cleanupDemoUsers: function(e) {
            e.preventDefault();
            
            if (!confirm(wpmatchAdmin.strings.confirmCleanupDemo || 'Are you sure you want to delete ALL demo users? This cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            
            $.ajax({
                url: wpmatchAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpmatch_cleanup_demo_users',
                    nonce: wpmatchAdmin.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Cleaning up...');
                },
                success: function(response) {
                    if (response.success) {
                        DemoManager.showNotice(response.data.message, 'success');
                        DemoManager.updateStats(response.data.stats);
                    } else {
                        DemoManager.showNotice(response.data || 'Failed to cleanup demo users', 'error');
                    }
                },
                error: function() {
                    DemoManager.showNotice('Error cleaning up demo users', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Clean Up All Demo Users');
                }
            });
        },

        updateStats: function(stats) {
            // Update statistics display
            $('.demo-stats-grid .stat-card:nth-child(1) .stat-number').text(stats.total_demo_users);
            $('.demo-stats-grid .stat-card:nth-child(2) .stat-number').text(stats.users_with_profiles);
            $('.demo-stats-grid .stat-card:nth-child(4) .stat-number').text(stats.free_limit - stats.total_demo_users);
            
            // Update button states
            $('#create-demo-users').prop('disabled', !stats.can_create_more);
            $('#cleanup-demo-users').prop('disabled', stats.total_demo_users === 0);
            
            // Update limit card styling
            var $limitCard = $('.demo-stats-grid .stat-card:nth-child(4)');
            $limitCard.removeClass('available limit-reached');
            $limitCard.addClass(stats.can_create_more ? 'available' : 'limit-reached');
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="wpmatch-notice ' + (type === 'error' ? 'error' : '') + '">' + message + '</div>');
            $('body').append($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 4000);
        }
    };

    // Dashboard
    var Dashboard = {
        init: function() {
            this.loadStats();
        },

        loadStats: function() {
            // Load dashboard statistics
            // Can be enhanced with real-time data
        }
    };

    // Initialize components based on current page
    var currentPage = new URLSearchParams(window.location.search).get('page');
    
    switch (currentPage) {
        case 'wpmatch-fields':
            FieldManager.init();
            break;
        case 'wpmatch-settings':
            SettingsManager.init();
            break;
        case 'wpmatch-demo':
            DemoManager.init();
            break;
        case 'wpmatch-free':
        default:
            Dashboard.init();
            break;
    }

    // Global features
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (xhr.status === 401) {
            alert('Your session has expired. Please refresh the page and try again.');
        }
    });
});