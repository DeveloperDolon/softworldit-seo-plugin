/**
 * Site Override JavaScript for Softworld AI SEO Plugin
 * Handles override requests and permission management for multisite subsites
 */

jQuery(document).ready(function($) {
    
    // Handle override request form submission
    $('#override-request-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('input[name="request_override"]').is(':checked')) {
            alert('Please check the override request checkbox before submitting.');
            return;
        }
        
        if (!confirm(softworldSiteOverride.i18n.confirm_override_request)) {
            return;
        }
        
        var formData = {
            action: 'softworld_site_request_override',
            nonce: softworldSiteOverride.nonce,
            override_reason: $('textarea[name="override_reason"]').val()
        };
        
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Submitting...');
        
        $.post(softworldSiteOverride.ajax_url, formData, function(response) {
            if (response.success) {
                $('<div class="notice notice-success"><p>' + response.data + '</p></div>')
                    .insertAfter('.wrap h1');
                $('#override-request-form').fadeOut();
                
                // Reload page after 2 seconds to show updated status
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                $('<div class="notice notice-error"><p>' + response.data + '</p></div>')
                    .insertAfter('.wrap h1');
            }
        }).fail(function() {
            $('<div class="notice notice-error"><p>An error occurred while processing your request.</p></div>')
                .insertAfter('.wrap h1');
        }).always(function() {
            $submitBtn.prop('disabled', false).text(originalText);
        });
    });
    
    // Handle revoke override button
    $('#revoke-override').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(softworldSiteOverride.i18n.confirm_revoke_override)) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Processing...');
        
        var requestData = {
            action: 'softworld_site_revoke_override',
            nonce: softworldSiteOverride.nonce
        };
        
        $.post(softworldSiteOverride.ajax_url, requestData, function(response) {
            if (response.success) {
                $('<div class="notice notice-success"><p>' + response.data + '</p></div>')
                    .insertAfter('.wrap h1');
                
                // Reload page after 2 seconds to show updated status
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                $('<div class="notice notice-error"><p>' + response.data + '</p></div>')
                    .insertAfter('.wrap h1');
            }
        }).fail(function() {
            $('<div class="notice notice-error"><p>An error occurred while processing your request.</p></div>')
                .insertAfter('.wrap h1');
        }).always(function() {
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // Handle reason textarea toggle
    $('input[name="request_override"]').on('change', function() {
        var $reasonRow = $(this).closest('table').find('textarea[name="override_reason"]').closest('tr');
        if ($(this).is(':checked')) {
            $reasonRow.show();
            $('textarea[name="override_reason"]').focus();
        } else {
            $reasonRow.hide();
        }
    });
    
    // Initialize textarea visibility based on checkbox state
    if (!$('input[name="request_override"]').is(':checked')) {
        $('textarea[name="override_reason"]').closest('tr').hide();
    }
    
    // Auto-hide notices after 5 seconds
    $('.notice').each(function() {
        var $notice = $(this);
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    });
});