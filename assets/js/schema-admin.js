/**
 * Schema Settings Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize schema type selector in meta box
        initializeSchemaMetaBox();
        
        // Initialize entity type toggles in admin page
        initializeEntityTypeToggles();
        
        // Initialize social profile management
        initializeSocialProfiles();
        
        // Initialize media library integration
        initializeMediaLibrary();
    });
    
    /**
     * Initialize schema meta box functionality
     */
    function initializeSchemaMetaBox() {
        var $schemaSelect = $('#softworld_schema_type');
        var $additionalFields = $('#schema_additional_fields');
        var $allFields = $additionalFields.find('.schema-fields');
        
        if ($schemaSelect.length) {
            $schemaSelect.on('change', function() {
                var selectedType = $(this).val();
                
                if (selectedType) {
                    $additionalFields.show();
                } else {
                    $additionalFields.hide();
                }
                
                // Hide all schema-specific fields first
                $allFields.hide();
                
                // Show relevant fields based on selected type
                switch (selectedType) {
                    case 'Product':
                        $additionalFields.find('.product-fields').show();
                        break;
                    case 'Review':
                        $additionalFields.find('.review-fields').show();
                        break;
                    case 'Recipe':
                        $additionalFields.find('.recipe-fields').show();
                        break;
                    case 'FAQPage':
                        // Add FAQ-specific fields if needed
                        break;
                    case 'HowTo':
                        // Add How-To specific fields if needed
                        break;
                }
                
                // Add schema type information
                showSchemaTypeInfo(selectedType);
            });
            
            // Trigger change on page load to show/hide fields correctly
            $schemaSelect.trigger('change');
        }
    }
    
    /**
     * Show information about the selected schema type
     */
    function showSchemaTypeInfo(schemaType) {
        var $infoContainer = $('.schema-type-info');
        
        // Remove existing info
        $infoContainer.remove();
        
        if (!schemaType) return;
        
        var infoText = '';
        switch (schemaType) {
            case 'Article':
                infoText = 'Article schema helps search engines understand your blog posts and news articles.';
                break;
            case 'Product':
                infoText = 'Product schema displays rich snippets with pricing and availability in search results.';
                break;
            case 'FAQPage':
                infoText = 'FAQ schema can show your questions and answers directly in search results.';
                break;
            case 'HowTo':
                infoText = 'How-To schema displays step-by-step instructions in search results.';
                break;
            case 'Review':
                infoText = 'Review schema shows star ratings and review information in search snippets.';
                break;
            case 'Recipe':
                infoText = 'Recipe schema displays cooking time, ingredients, and ratings in search results.';
                break;
        }
        
        if (infoText) {
            var $info = $('<div class="schema-type-info"><p>' + infoText + '</p></div>');
            $('#schema_additional_fields').after($info);
        }
    }
    
    /**
     * Initialize entity type toggles
     */
    function initializeEntityTypeToggles() {
        var $entityRadios = $('input[name="softworld_schema_knowledge_graph[entity_type]"]');
        var $orgFields = $('#organization_fields');
        var $personFields = $('#person_fields');
        
        if ($entityRadios.length) {
            $entityRadios.on('change', function() {
                var selectedType = $(this).val();
                
                if (selectedType === 'Organization') {
                    $orgFields.show();
                    $personFields.hide();
                } else if (selectedType === 'Person') {
                    $orgFields.hide();
                    $personFields.show();
                }
            });
            
            // Trigger change on page load
            $entityRadios.filter(':checked').trigger('change');
        }
    }
    
    /**
     * Initialize social profile management
     */
    function initializeSocialProfiles() {
        // Add social profile button
        $(document).on('click', '.add-social-profile', function(e) {
            e.preventDefault();
            addSocialProfile();
        });
        
        // Remove social profile button
        $(document).on('click', '.remove-social-profile', function(e) {
            e.preventDefault();
            $(this).closest('.social-profile-row').remove();
            
            // Ensure at least one profile field exists
            if ($('.social-profile-row').length === 0) {
                addSocialProfile();
            }
        });
    }
    
    /**
     * Add a new social profile field
     */
    function addSocialProfile() {
        var $container = $('#social_profiles_container');
        var profileCount = $('.social-profile-row').length;
        
        var $newRow = $('<div class="social-profile-row">' +
            '<input type="url" name="softworld_schema_knowledge_graph[social_profiles][]" ' +
            'placeholder="https://www.facebook.com/yourpage" class="regular-text">' +
            '<button type="button" class="button remove-social-profile">Remove</button>' +
            '</div>');
        
        // Hide remove button if this is the first field
        if (profileCount === 0) {
            $newRow.find('.remove-social-profile').hide();
        }
        
        // Show remove buttons on other fields if this isn't the first
        if (profileCount > 0) {
            $('.social-profile-row .remove-social-profile').show();
        }
        
        $container.append($newRow);
    }
    
    /**
     * Initialize media library integration
     */
    function initializeMediaLibrary() {
        var mediaUploader;
        
        $(document).on('click', '.select-media-button', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var targetInput = $button.data('target');
            
            // If the media frame already exists, reopen it
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Create the media frame
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Select Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // Handle selection
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                var $input = $('input[name="' + targetInput + '"]');
                
                if ($input.length) {
                    $input.val(attachment.url);
                    
                    // Show preview if container exists
                    showImagePreview($input, attachment);
                }
            });
            
            // Open the uploader
            mediaUploader.open();
        });
        
        // Remove image button
        $(document).on('click', '.remove-media-button', function(e) {
            e.preventDefault();
            
            var targetInput = $(this).data('target');
            var $input = $('input[name="' + targetInput + '"]');
            
            $input.val('');
            $(this).siblings('.image-preview').remove();
            $(this).hide();
        });
    }
    
    /**
     * Show image preview
     */
    function showImagePreview($input, attachment) {
        var $container = $input.parent();
        
        // Remove existing preview
        $container.find('.image-preview').remove();
        
        // Add new preview
        var $preview = $('<div class="image-preview" style="margin-top: 10px;">' +
            '<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">' +
            '<p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">' + attachment.filename + '</p>' +
            '</div>');
        
        $container.append($preview);
        
        // Show remove button
        $container.find('.remove-media-button').show();
    }
    
    /**
     * Validate schema settings form
     */
    function validateSchemaForm() {
        var isValid = true;
        var errors = [];
        
        // Check required fields based on entity type
        var entityType = $('input[name="softworld_schema_knowledge_graph[entity_type]"]:checked').val();
        
        if (entityType === 'Organization') {
            var orgName = $('input[name="softworld_schema_knowledge_graph[organization_name]"]').val();
            if (!orgName.trim()) {
                errors.push('Organization name is required when Organization is selected.');
                isValid = false;
            }
        } else if (entityType === 'Person') {
            var personName = $('input[name="softworld_schema_knowledge_graph[person_name]"]').val();
            if (!personName.trim()) {
                errors.push('Person name is required when Person is selected.');
                isValid = false;
            }
        }
        
        // Validate social profile URLs
        $('.social-profile-row input[type="url"]').each(function() {
            var url = $(this).val();
            if (url && !isValidUrl(url)) {
                errors.push('Please enter valid URLs for social profiles.');
                isValid = false;
                return false; // Break loop
            }
        });
        
        // Show errors if any
        if (!isValid) {
            alert('Please fix the following errors:\n\n' + errors.join('\n'));
        }
        
        return isValid;
    }
    
    /**
     * Check if URL is valid
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    /**
     * Initialize form validation
     */
    $(document).on('submit', 'form', function(e) {
        if ($(this).find('input[name*="softworld_schema"]').length > 0) {
            if (!validateSchemaForm()) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    /**
     * Auto-save functionality for meta box
     */
    function initializeAutoSave() {
        var $metaBox = $('.softworld-schema-meta-box');
        var saveTimeout;
        
        if ($metaBox.length) {
            $metaBox.find('input, select, textarea').on('change input', function() {
                clearTimeout(saveTimeout);
                
                // Add visual feedback
                $metaBox.addClass('schema-loading');
                
                saveTimeout = setTimeout(function() {
                    $metaBox.removeClass('schema-loading');
                    // Here you could add AJAX save functionality if needed
                }, 1000);
            });
        }
    }
    
    // Initialize auto-save
    initializeAutoSave();

})(jQuery);

// Global functions for backward compatibility and template usage
function addSocialProfile() {
    jQuery('.add-social-profile').trigger('click');
}

function removeSocialProfile(button) {
    jQuery(button).trigger('click');
}

function openMediaLibrary(fieldName) {
    var $button = jQuery('.select-media-button[data-target="softworld_schema_knowledge_graph[' + fieldName + ']"]');
    if ($button.length) {
        $button.trigger('click');
    } else {
        // Fallback for direct field access
        var mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Image',
            button: { text: 'Select Image' },
            multiple: false,
            library: { type: 'image' }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var $input = jQuery('input[name="softworld_schema_knowledge_graph[' + fieldName + ']"]');
            $input.val(attachment.url);
        });
        
        mediaUploader.open();
    }
}