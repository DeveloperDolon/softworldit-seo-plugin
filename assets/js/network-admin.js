/**
 * Network Admin JavaScript for Softworld AI SEO
 */
jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    const networkAdmin = {
        ajax_url: softworldNetwork.ajax_url,
        nonce: softworldNetwork.nonce,
        i18n: softworldNetwork.i18n
    };

    /**
     * License Management
     */
    function initLicenseManagement() {
        // Activate License
        $('#activate-license').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const licenseKey = $('#license_key').val().trim();
            
            if (!licenseKey) {
                showNotification('error', 'Please enter a license key.');
                return;
            }

            $button.prop('disabled', true).text('Activating...');
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'softworld_network_activate_license',
                    nonce: networkAdmin.nonce,
                    license_key: licenseKey
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification('error', response.data || 'License activation failed.');
                        $button.prop('disabled', false).text('Activate License');
                    }
                },
                error: function() {
                    showNotification('error', 'Network error occurred.');
                    $button.prop('disabled', false).text('Activate License');
                }
            });
        });

        // Deactivate License
        $('#deactivate-license').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to deactivate your license?')) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true).text('Deactivating...');
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'softworld_network_deactivate_license',
                    nonce: networkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification('error', response.data || 'License deactivation failed.');
                        $button.prop('disabled', false).text('Deactivate License');
                    }
                },
                error: function() {
                    showNotification('error', 'Network error occurred.');
                    $button.prop('disabled', false).text('Deactivate License');
                }
            });
        });
    }

    /**
     * Network Settings Management
     */
    function initNetworkSettings() {
        // Save Network Settings
        $('#softworld-network-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            $submitBtn.prop('disabled', true).text(networkAdmin.i18n.saving);
            
            const formData = $form.serialize();
            formData.action = 'softworld_network_save_settings';
            formData.nonce = networkAdmin.nonce;
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data);
                        $submitBtn.text(networkAdmin.i18n.saved);
                        setTimeout(() => {
                            $submitBtn.text(originalText);
                        }, 2000);
                    } else {
                        showNotification('error', response.data || 'Save failed.');
                    }
                },
                error: function() {
                    showNotification('error', networkAdmin.i18n.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    if ($submitBtn.text() === networkAdmin.i18n.saving) {
                        $submitBtn.text(originalText);
                    }
                }
            });
        });

        // Apply to All Sites
        $('#apply-to-all-sites').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(networkAdmin.i18n.confirm_apply_all)) {
                return;
            }

            const $button = $(this);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Applying...');
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'softworld_network_apply_to_sites',
                    nonce: networkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data.message);
                    } else {
                        showNotification('error', response.data || 'Apply failed.');
                    }
                },
                error: function() {
                    showNotification('error', networkAdmin.i18n.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Settings change tracking
        let hasUnsavedChanges = false;
        
        $('#softworld-network-settings-form input, #softworld-network-settings-form select, #softworld-network-settings-form textarea').on('change', function() {
            hasUnsavedChanges = true;
        });

        // Warn about unsaved changes
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return networkAdmin.i18n.unsaved_changes;
            }
        });

        // Mark saved when form is submitted successfully
        $('#softworld-network-settings-form').on('softworld-settings-saved', function() {
            hasUnsavedChanges = false;
        });
    }

    /**
     * Import/Export Management
     */
    function initImportExport() {
        // Export Settings
        $('#softworld-export-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Check if at least one option is selected
            if (!$form.find('input[type="checkbox"]:checked').length) {
                showNotification('error', 'Please select at least one export option.');
                return;
            }

            $submitBtn.prop('disabled', true).text('Exporting...');
            
            // Create a temporary form for file download
            const tempForm = $('<form>', {
                method: 'POST',
                action: networkAdmin.ajax_url
            });
            
            // Add form data
            $form.find('input').each(function() {
                if ($(this).is(':checkbox') && !$(this).is(':checked')) return;
                tempForm.append($('<input>', {
                    type: 'hidden',
                    name: $(this).attr('name'),
                    value: $(this).val()
                }));
            });
            
            tempForm.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'softworld_network_export_settings'
            }));
            
            tempForm.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: networkAdmin.nonce
            }));
            
            $('body').append(tempForm);
            tempForm.submit();
            tempForm.remove();
            
            setTimeout(() => {
                $submitBtn.prop('disabled', false).text('Export Settings');
                showNotification('success', 'Export completed successfully!');
                loadImportExportLog();
            }, 1000);
        });

        // Import Settings
        $('#softworld-import-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const fileInput = $form.find('input[type="file"]')[0];
            
            if (!fileInput.files.length) {
                showNotification('error', 'Please select a file to import.');
                return;
            }

            $submitBtn.prop('disabled', true).text('Importing...');
            
            const formData = new FormData();
            formData.append('action', 'softworld_network_import_settings');
            formData.append('nonce', networkAdmin.nonce);
            formData.append('import_file', fileInput.files[0]);
            
            // Add checkbox values
            $form.find('input[type="checkbox"]:checked').each(function() {
                formData.append($(this).attr('name'), $(this).val());
            });
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data.message);
                        if (response.data.details && response.data.details.length) {
                            showNotification('info', 'Details: ' + response.data.details.join(', '));
                        }
                        $form[0].reset();
                        loadImportExportLog();
                    } else {
                        showNotification('error', response.data || 'Import failed.');
                    }
                },
                error: function() {
                    showNotification('error', networkAdmin.i18n.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Import Settings');
                }
            });
        });
    }

    /**
     * Site Override Management
     */
    function initSiteOverrides() {
        // Request Override Permission
        $('#override-request-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!confirm(networkAdmin.i18n.confirm_override_request)) {
                return;
            }

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            if (!$form.find('input[name="request_override"]').is(':checked')) {
                showNotification('error', 'Please check the override request checkbox.');
                return;
            }

            $submitBtn.prop('disabled', true).text('Sending Request...');
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'softworld_site_request_override',
                    nonce: networkAdmin.nonce,
                    override_reason: $form.find('textarea[name="override_reason"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('error', response.data || 'Request failed.');
                        $submitBtn.prop('disabled', false).text('Request Override Permission');
                    }
                },
                error: function() {
                    showNotification('error', networkAdmin.i18n.error);
                    $submitBtn.prop('disabled', false).text('Request Override Permission');
                }
            });
        });

        // Revoke Override
        $('#revoke-override').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to revert to network settings? This will remove all custom settings for this site.')) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true).text('Revoking...');
            
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'softworld_site_revoke_override',
                    nonce: networkAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('success', response.data);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('error', response.data || 'Revoke failed.');
                        $button.prop('disabled', false).text('Revert to Network Settings');
                    }
                },
                error: function() {
                    showNotification('error', networkAdmin.i18n.error);
                    $button.prop('disabled', false).text('Revert to Network Settings');
                }
            });
        });
    }

    /**
     * Load Import/Export Log
     */
    function loadImportExportLog() {
        $.ajax({
            url: networkAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'softworld_network_get_import_export_log',
                nonce: networkAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#import-export-log').html(response.data);
                }
            }
        });
    }

    /**
     * Real-time Statistics Updates
     */
    function initRealTimeStats() {
        function updateStats() {
            $.ajax({
                url: networkAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'softworld_network_get_stats',
                    nonce: networkAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        updateStatsDisplay(response.data);
                    }
                }
            });
        }

        function updateStatsDisplay(stats) {
            if (stats.license_status) {
                $('.license-status')
                    .removeClass('valid invalid')
                    .addClass(stats.license_status.valid ? 'valid' : 'invalid')
                    .find('span').text(stats.license_status.valid ? 'Active' : 'Inactive');
            }

            if (stats.network_stats) {
                $('.softworld-network-card').each(function() {
                    const $card = $(this);
                    const title = $card.find('h3').text();
                    
                    if (title.includes('Network Statistics')) {
                        const $list = $card.find('ul');
                        $list.find('li').each(function(index) {
                            const $li = $(this);
                            switch(index) {
                                case 0:
                                    $li.text(`Total Sites: ${stats.network_stats.total_sites}`);
                                    break;
                                case 1:
                                    $li.text(`Active Sites: ${stats.network_stats.active_sites}`);
                                    break;
                                case 2:
                                    $li.text(`Sites with Override: ${stats.network_stats.override_sites}`);
                                    break;
                            }
                        });
                    }
                });
            }
        }

        // Update stats every 30 seconds
        setInterval(updateStats, 30000);
    }

    /**
     * Enhanced Form Validation
     */
    function initFormValidation() {
        // License key validation
        $('#license_key').on('input', function() {
            const value = $(this).val().trim();
            const $button = $('#activate-license');
            
            if (value.length < 10) {
                $button.prop('disabled', true);
                $(this).addClass('invalid');
            } else {
                $button.prop('disabled', false);
                $(this).removeClass('invalid');
            }
        });

        // File upload validation
        $('input[type="file"]').on('change', function() {
            const file = this.files[0];
            const $form = $(this).closest('form');
            const $submitBtn = $form.find('button[type="submit"]');
            
            if (file) {
                if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
                    showNotification('error', 'Please select a valid JSON file.');
                    $submitBtn.prop('disabled', true);
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    showNotification('error', 'File size must be less than 5MB.');
                    $submitBtn.prop('disabled', true);
                    return;
                }
                
                $submitBtn.prop('disabled', false);
            }
        });
    }

    /**
     * Accessibility Enhancements
     */
    function initAccessibility() {
        // Keyboard navigation for cards
        $('.softworld-network-card').attr('tabindex', '0');
        
        // Focus management for modals/notifications
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.softworld-notification').fadeOut();
            }
        });

        // ARIA labels for status indicators
        $('.license-status').attr('role', 'status').attr('aria-live', 'polite');
        $('.site-status').attr('role', 'status');
    }

    /**
     * Show Notification
     */
    function showNotification(type, message, duration = 5000) {
        // Remove existing notifications
        $('.softworld-notification').remove();
        
        const $notification = $(`
            <div class="softworld-notification ${type}" role="alert" aria-live="assertive">
                ${message}
                <button type="button" class="notice-dismiss" aria-label="Dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        // Insert after the h1 or at the top of wrap
        const $target = $('.wrap h1').first();
        if ($target.length) {
            $target.after($notification);
        } else {
            $('.wrap').prepend($notification);
        }
        
        $notification.fadeIn();
        
        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            }, duration);
        }
        
        // Manual dismiss
        $notification.find('.notice-dismiss').on('click', function() {
            $notification.fadeOut(() => {
                $notification.remove();
            });
        });
    }

    /**
     * Utility Functions
     */
    function addLoadingState($element) {
        $element.addClass('softworld-loading');
    }

    function removeLoadingState($element) {
        $element.removeClass('softworld-loading');
    }

    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    /**
     * Initialize Everything
     */
    function init() {
        initLicenseManagement();
        initNetworkSettings();
        initImportExport();
        initSiteOverrides();
        initRealTimeStats();
        initFormValidation();
        initAccessibility();
        
        // Load initial data
        if ($('#import-export-log').length) {
            loadImportExportLog();
        }
        
        console.log('Softworld AI SEO Network Admin initialized successfully');
    }

    // Start initialization
    init();
});