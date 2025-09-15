/**
 * Softworld Local SEO Admin JavaScript
 * 
 * Handles admin interface for local SEO management
 */

(function($) {
    'use strict';

    var LocalSEOAdmin = {
        modal: null,
        currentLocationId: null,
        isEditMode: false,
        geocoder: null,
        map: null,
        marker: null,
        unsavedChanges: false
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        LocalSEOAdmin.init();
    });

    /**
     * Initialize the admin interface
     */
    LocalSEOAdmin.init = function() {
        this.bindEvents();
        this.initModal();
        this.initGoogleMapsAPI();
        this.initFormTracking();
        this.initTooltips();
    };

    /**
     * Bind all event handlers
     */
    LocalSEOAdmin.bindEvents = function() {
        // Location management buttons
        $('#add-location-btn').on('click', this.openAddLocationModal.bind(this));
        $(document).on('click', '.edit-location-btn', this.openEditLocationModal.bind(this));
        $(document).on('click', '.delete-location-btn', this.deleteLocation.bind(this));
        
        // Geocoding and map functions
        $('#geocode-btn').on('click', this.geocodeAddress.bind(this));
        $('#location-address, #location-city, #location-state, #location-country').on('blur', this.autoGeocodeIfEmpty.bind(this));
        
        // Form handling
        $('#location-form').on('submit', this.handleFormSubmit.bind(this));
        $('#location-form input, #location-form textarea, #location-form select').on('change', this.markUnsavedChanges.bind(this));
        
        // Opening hours management
        $('.closed-checkbox').on('change', this.toggleOpeningHours.bind(this));
        $('.time-inputs input').on('change', this.validateTimeInputs.bind(this));
        
        // Tab navigation
        $('.softworld-tab-nav a').on('click', this.switchTab.bind(this));
        
        // Primary location handling
        $('#is-primary').on('change', this.handlePrimaryLocationChange.bind(this));
        
        // Image handling
        $('#location-image').on('change', this.validateImageURL.bind(this));
        $('.upload-image-btn').on('click', this.openMediaUploader.bind(this));
        
        // Copy hours functionality
        $('.copy-hours-btn').on('click', this.copyHoursToAllDays.bind(this));
        
        // Bulk hours operations
        $('.set-all-hours').on('click', this.setAllHours.bind(this));
        $('.clear-all-hours').on('click', this.clearAllHours.bind(this));
        
        // Window unload warning
        $(window).on('beforeunload', this.handleWindowUnload.bind(this));
        
        // Keyboard shortcuts
        $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        
        // Real-time validation
        $('#location-email').on('blur', this.validateEmail.bind(this));
        $('#location-website').on('blur', this.validateURL.bind(this));
        $('#location-phone').on('blur', this.formatPhoneNumber.bind(this));
        
        // Search functionality
        $('#locations-search').on('keyup', this.searchLocations.bind(this));
        
        // Export/Import
        $('#export-locations').on('click', this.exportLocations.bind(this));
        $('#import-locations').on('click', this.showImportModal.bind(this));
    };

    /**
     * Initialize the modal dialog
     */
    LocalSEOAdmin.initModal = function() {
        if (!$('#location-modal').length) {
            console.warn('Location modal not found');
            return;
        }

        this.modal = $('#location-modal').dialog({
            autoOpen: false,
            modal: true,
            width: 900,
            height: 700,
            resizable: true,
            draggable: true,
            buttons: {
                [softworldLocalSEO.i18n.save || 'Save']: {
                    text: softworldLocalSEO.i18n.save || 'Save',
                    class: 'button-primary',
                    click: this.saveLocation.bind(this)
                },
                'Cancel': {
                    text: 'Cancel',
                    click: function() {
                        if (LocalSEOAdmin.unsavedChanges) {
                            if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                                $(this).dialog('close');
                            }
                        } else {
                            $(this).dialog('close');
                        }
                    }
                }
            },
            close: this.resetForm.bind(this),
            open: function() {
                // Focus first input
                setTimeout(function() {
                    $('#location-name').focus();
                }, 100);
            }
        });
    };

    /**
     * Initialize Google Maps API
     */
    LocalSEOAdmin.initGoogleMapsAPI = function() {
        if (softworldLocalSEO.google_maps_api_key && typeof google === 'undefined') {
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + 
                        softworldLocalSEO.google_maps_api_key + 
                        '&libraries=places&callback=initLocalSEOGoogleMaps';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        } else if (typeof google !== 'undefined') {
            this.initGeocoder();
        }
    };

    /**
     * Initialize geocoder when Google Maps is loaded
     */
    window.initLocalSEOGoogleMaps = function() {
        LocalSEOAdmin.initGeocoder();
    };

    LocalSEOAdmin.initGeocoder = function() {
        if (typeof google !== 'undefined' && google.maps) {
            this.geocoder = new google.maps.Geocoder();
        }
    };

    /**
     * Initialize form change tracking
     */
    LocalSEOAdmin.initFormTracking = function() {
        this.unsavedChanges = false;
    };

    /**
     * Initialize tooltips for help text
     */
    LocalSEOAdmin.initTooltips = function() {
        $('.help-tooltip').hover(
            function() {
                var tooltip = $(this).data('tooltip');
                if (tooltip) {
                    $(this).append('<div class="tooltip-content">' + tooltip + '</div>');
                }
            },
            function() {
                $(this).find('.tooltip-content').remove();
            }
        );
    };

    /**
     * Open add location modal
     */
    LocalSEOAdmin.openAddLocationModal = function(e) {
        e.preventDefault();
        this.isEditMode = false;
        this.currentLocationId = null;
        this.unsavedChanges = false;
        
        this.modal.dialog('option', 'title', 'Add New Location');
        this.modal.dialog('open');
        
        // Set default values
        $('#business-type').val('LocalBusiness');
        $('#location-country').val('United States');
    };

    /**
     * Open edit location modal
     */
    LocalSEOAdmin.openEditLocationModal = function(e) {
        e.preventDefault();
        var locationId = $(e.currentTarget).data('location-id');
        
        if (!locationId) {
            this.showError('Location ID not found');
            return;
        }
        
        this.isEditMode = true;
        this.currentLocationId = locationId;
        this.unsavedChanges = false;
        
        this.loadLocation(locationId);
        this.modal.dialog('option', 'title', 'Edit Location');
        this.modal.dialog('open');
    };

    /**
     * Load location data via AJAX
     */
    LocalSEOAdmin.loadLocation = function(locationId) {
        this.showLoading();
        
        $.post(softworldLocalSEO.ajax_url, {
            action: 'softworld_get_location',
            location_id: locationId,
            nonce: softworldLocalSEO.nonce
        })
        .done(function(response) {
            this.hideLoading();
            
            if (response.success) {
                this.populateForm(response.data);
            } else {
                this.showError(response.data || 'Failed to load location');
            }
        }.bind(this))
        .fail(function() {
            this.hideLoading();
            this.showError('Network error occurred while loading location');
        }.bind(this));
    };

    /**
     * Populate form with location data
     */
    LocalSEOAdmin.populateForm = function(location) {
        // Basic information
        $('#location-id').val(location.id);
        $('#location-name').val(location.name || '');
        $('#location-address').val(location.address || '');
        $('#location-city').val(location.city || '');
        $('#location-state').val(location.state || '');
        $('#location-postal').val(location.postal_code || '');
        $('#location-country').val(location.country || '');
        $('#location-phone').val(location.phone || '');
        $('#location-email').val(location.email || '');
        $('#location-website').val(location.website || '');
        $('#location-latitude').val(location.latitude || '');
        $('#location-longitude').val(location.longitude || '');
        $('#business-type').val(location.business_type || 'LocalBusiness');
        $('#location-description').val(location.description || '');
        $('#location-image').val(location.image_url || '');
        $('#price-range').val(location.price_range || '');
        $('#accepts-reservations').prop('checked', location.accepts_reservations == 1);
        $('#serves-cuisine').val(location.serves_cuisine || '');
        $('#payment-accepted').val(location.payment_accepted || '');
        $('#is-primary').prop('checked', location.is_primary == 1);

        // Opening hours
        if (location.opening_hours) {
            var hours = typeof location.opening_hours === 'string' ? 
                        JSON.parse(location.opening_hours) : location.opening_hours;
            
            $.each(hours, function(day, dayHours) {
                var closedCheckbox = $('input[name="opening_hours[' + day + '][closed]"]');
                var openTime = $('input[name="opening_hours[' + day + '][open]"]');
                var closeTime = $('input[name="opening_hours[' + day + '][close]"]');

                if (dayHours && dayHours.closed) {
                    closedCheckbox.prop('checked', true);
                    openTime.prop('disabled', true).val('');
                    closeTime.prop('disabled', true).val('');
                } else if (dayHours) {
                    closedCheckbox.prop('checked', false);
                    openTime.prop('disabled', false).val(dayHours.open || '');
                    closeTime.prop('disabled', false).val(dayHours.close || '');
                }
            });
        }

        // Update map if coordinates exist
        if (location.latitude && location.longitude) {
            this.updateMapLocation(parseFloat(location.latitude), parseFloat(location.longitude));
        }
        
        this.unsavedChanges = false;
    };

    /**
     * Save location via AJAX
     */
    LocalSEOAdmin.saveLocation = function() {
        if (!this.validateForm()) {
            return;
        }

        var formData = new FormData(document.getElementById('location-form'));
        formData.append('action', this.isEditMode ? 'softworld_update_location' : 'softworld_add_location');
        formData.append('nonce', softworldLocalSEO.nonce);
        
        if (this.isEditMode && this.currentLocationId) {
            formData.append('location_id', this.currentLocationId);
        }

        this.showSaving();

        $.ajax({
            url: softworldLocalSEO.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000
        })
        .done(function(response) {
            this.hideSaving();
            
            if (response.success) {
                this.showSuccess(response.data.message || response.data);
                this.unsavedChanges = false;
                this.modal.dialog('close');
                
                // Reload page to show changes
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                this.showError(response.data || 'Failed to save location');
            }
        }.bind(this))
        .fail(function(xhr) {
            this.hideSaving();
            var errorMsg = 'Network error occurred while saving';
            
            if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMsg = xhr.responseJSON.data;
            } else if (xhr.status === 413) {
                errorMsg = 'Request too large. Please reduce image size.';
            } else if (xhr.status === 0) {
                errorMsg = 'Connection lost. Please check your internet connection.';
            }
            
            this.showError(errorMsg);
        }.bind(this));
    };

    /**
     * Delete location
     */
    LocalSEOAdmin.deleteLocation = function(e) {
        e.preventDefault();
        var locationId = $(e.currentTarget).data('location-id');
        var locationName = $(e.currentTarget).closest('tr').find('td:first strong').text();
        
        if (!locationId) {
            this.showError('Location ID not found');
            return;
        }
        
        var confirmMessage = softworldLocalSEO.i18n.confirm_delete;
        if (locationName) {
            confirmMessage = 'Are you sure you want to delete "' + locationName + '"?';
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }

        // Show loading on the row
        var $row = $(e.currentTarget).closest('tr');
        $row.addClass('deleting');

        $.post(softworldLocalSEO.ajax_url, {
            action: 'softworld_delete_location',
            location_id: locationId,
            nonce: softworldLocalSEO.nonce
        })
        .done(function(response) {
            if (response.success) {
                $row.fadeOut(function() {
                    $(this).remove();
                    LocalSEOAdmin.checkEmptyTable();
                });
                this.showSuccess(response.data);
            } else {
                $row.removeClass('deleting');
                this.showError(response.data || 'Failed to delete location');
            }
        }.bind(this))
        .fail(function() {
            $row.removeClass('deleting');
            this.showError('Network error occurred while deleting location');
        }.bind(this));
    };

    /**
     * Geocode address using Google Maps API
     */
    LocalSEOAdmin.geocodeAddress = function(e) {
        e.preventDefault();
        
        if (!this.geocoder) {
            this.showError('Google Maps API not loaded. Please check your API key.');
            return;
        }
        
        var address = this.buildFullAddress();
        
        if (!address) {
            this.showError('Please enter address information first.');
            return;
        }

        var $btn = $('#geocode-btn');
        $btn.prop('disabled', true).text('Geocoding...');

        this.geocoder.geocode({address: address}, function(results, status) {
            $btn.prop('disabled', false).text('Get Coordinates');
            
            if (status === 'OK' && results[0]) {
                var location = results[0].geometry.location;
                var lat = location.lat();
                var lng = location.lng();
                
                $('#location-latitude').val(lat);
                $('#location-longitude').val(lng);
                
                this.updateMapLocation(lat, lng);
                this.markUnsavedChanges();
                this.showSuccess('Coordinates found and updated!');
            } else {
                var errorMsg = 'Geocoding failed';
                
                switch(status) {
                    case 'ZERO_RESULTS':
                        errorMsg = 'No results found for this address';
                        break;
                    case 'OVER_QUERY_LIMIT':
                        errorMsg = 'Geocoding quota exceeded. Please try again later.';
                        break;
                    case 'REQUEST_DENIED':
                        errorMsg = 'Geocoding request denied. Please check your API key.';
                        break;
                    case 'INVALID_REQUEST':
                        errorMsg = 'Invalid address format';
                        break;
                }
                
                this.showError(errorMsg);
            }
        }.bind(this));
    };

    /**
     * Auto-geocode when address fields lose focus
     */
    LocalSEOAdmin.autoGeocodeIfEmpty = function(e) {
        if (!this.geocoder) return;
        
        var lat = $('#location-latitude').val();
        var lng = $('#location-longitude').val();
        
        // Only auto-geocode if coordinates are empty
        if (!lat && !lng) {
            var address = this.buildFullAddress();
            if (address) {
                setTimeout(function() {
                    $('#geocode-btn').trigger('click');
                }, 500);
            }
        }
    };

    /**
     * Build full address from form fields
     */
    LocalSEOAdmin.buildFullAddress = function() {
        var parts = [
            $('#location-address').val(),
            $('#location-city').val(),
            $('#location-state').val(),
            $('#location-postal').val(),
            $('#location-country').val()
        ];
        
        return parts.filter(function(part) {
            return part && part.trim();
        }).join(', ');
    };

    /**
     * Update map location with coordinates
     */
    LocalSEOAdmin.updateMapLocation = function(lat, lng) {
        if (!this.geocoder) return;
        
        // Create or update preview map in modal if container exists
        var mapContainer = $('#location-map-preview');
        if (mapContainer.length) {
            if (!this.map) {
                this.map = new google.maps.Map(mapContainer[0], {
                    zoom: 15,
                    center: {lat: lat, lng: lng}
                });
            } else {
                this.map.setCenter({lat: lat, lng: lng});
            }
            
            if (this.marker) {
                this.marker.setMap(null);
            }
            
            this.marker = new google.maps.Marker({
                position: {lat: lat, lng: lng},
                map: this.map,
                title: $('#location-name').val() || 'Location'
            });
            
            mapContainer.show();
        }
    };

    /**
     * Validate form before submission
     */
    LocalSEOAdmin.validateForm = function() {
        var isValid = true;
        var requiredFields = [
            {field: '#location-name', message: 'Business name is required'},
            {field: '#location-address', message: 'Address is required'},
            {field: '#location-city', message: 'City is required'},
            {field: '#location-state', message: 'State is required'},
            {field: '#location-country', message: 'Country is required'}
        ];
        
        // Clear previous errors
        $('.softworld-field-error').removeClass('softworld-field-error');
        $('.softworld-error-message').remove();
        
        // Validate required fields
        $.each(requiredFields, function(index, fieldInfo) {
            var $field = $(fieldInfo.field);
            if (!$field.val() || !$field.val().trim()) {
                this.markFieldError($field, fieldInfo.message);
                isValid = false;
            }
        }.bind(this));
        
        // Validate email format
        var email = $('#location-email').val();
        if (email && !this.isValidEmail(email)) {
            this.markFieldError($('#location-email'), 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate website URL
        var website = $('#location-website').val();
        if (website && !this.isValidURL(website)) {
            this.markFieldError($('#location-website'), 'Please enter a valid URL (including http:// or https://)');
            isValid = false;
        }
        
        // Validate coordinates if provided
        var lat = $('#location-latitude').val();
        var lng = $('#location-longitude').val();
        
        if (lat && (isNaN(lat) || lat < -90 || lat > 90)) {
            this.markFieldError($('#location-latitude'), 'Latitude must be between -90 and 90');
            isValid = false;
        }
        
        if (lng && (isNaN(lng) || lng < -180 || lng > 180)) {
            this.markFieldError($('#location-longitude'), 'Longitude must be between -180 and 180');
            isValid = false;
        }
        
        // Validate opening hours
        if (!this.validateOpeningHours()) {
            isValid = false;
        }
        
        if (!isValid) {
            this.showError('Please correct the errors above and try again.');
            // Focus first error field
            $('.softworld-field-error').first().focus();
        }
        
        return isValid;
    };

    /**
     * Validate opening hours
     */
    LocalSEOAdmin.validateOpeningHours = function() {
        var isValid = true;
        var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        $.each(days, function(index, day) {
            var closedCheckbox = $('input[name="opening_hours[' + day + '][closed]"]');
            var openTime = $('input[name="opening_hours[' + day + '][open]"]');
            var closeTime = $('input[name="opening_hours[' + day + '][close]"]');
            
            if (!closedCheckbox.is(':checked')) {
                var openVal = openTime.val();
                var closeVal = closeTime.val();
                
                if (openVal && closeVal) {
                    if (openVal >= closeVal) {
                        this.markFieldError(closeTime, 'Close time must be after open time');
                        isValid = false;
                    }
                } else if (openVal || closeVal) {
                    // If one is filled but not the other
                    if (!openVal) {
                        this.markFieldError(openTime, 'Open time is required when close time is set');
                        isValid = false;
                    }
                    if (!closeVal) {
                        this.markFieldError(closeTime, 'Close time is required when open time is set');
                        isValid = false;
                    }
                }
            }
        }.bind(this));
        
        return isValid;
    };

    /**
     * Mark field as having an error
     */
    LocalSEOAdmin.markFieldError = function($field, message) {
        $field.addClass('softworld-field-error');
        
        var $error = $('<span class="softworld-error-message">' + message + '</span>');
        $field.after($error);
        
        // Remove error on focus
        $field.one('focus', function() {
            $(this).removeClass('softworld-field-error');
            $(this).next('.softworld-error-message').remove();
        });
    };

    /**
     * Validate email format
     */
    LocalSEOAdmin.isValidEmail = function(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    /**
     * Validate URL format
     */
    LocalSEOAdmin.isValidURL = function(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return url.match(/^https?:\/\/.+/) !== null;
        }
    };

    /**
     * Handle form submission
     */
    LocalSEOAdmin.handleFormSubmit = function(e) {
        e.preventDefault();
        this.saveLocation();
    };

    /**
     * Mark form as having unsaved changes
     */
    LocalSEOAdmin.markUnsavedChanges = function() {
        this.unsavedChanges = true;
    };

    /**
     * Toggle opening hours for a day
     */
    LocalSEOAdmin.toggleOpeningHours = function(e) {
        var day = $(e.target).data('day');
        var timeInputs = $('.time-inputs input[data-day="' + day + '"]');
        
        if ($(e.target).is(':checked')) {
            timeInputs.prop('disabled', true).val('');
        } else {
            timeInputs.prop('disabled', false);
        }
        
        this.markUnsavedChanges();
    };

    /**
     * Validate time inputs
     */
    LocalSEOAdmin.validateTimeInputs = function(e) {
        var $input = $(e.target);
        var day = $input.data('day');
        var isOpen = $input.hasClass('open-time');
        
        if (isOpen) {
            var openTime = $input.val();
            var closeTime = $('input[name="opening_hours[' + day + '][close]"]').val();
            
            if (openTime && closeTime && openTime >= closeTime) {
                this.showError('Open time must be before close time');
                $input.focus();
            }
        }
        
        this.markUnsavedChanges();
    };

    /**
     * Switch between tabs
     */
    LocalSEOAdmin.switchTab = function(e) {
        e.preventDefault();
        var target = $(e.currentTarget).attr('href');
        
        $('.softworld-tab-nav li').removeClass('active');
        $(e.currentTarget).parent().addClass('active');
        
        $('.softworld-tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Focus first input in new tab
        setTimeout(function() {
            $(target).find('input, textarea, select').first().focus();
        }, 100);
    };

    /**
     * Handle primary location checkbox
     */
    LocalSEOAdmin.handlePrimaryLocationChange = function(e) {
        if ($(e.target).is(':checked')) {
            var message = 'This will make this location the primary location and remove primary status from other locations. Continue?';
            if (!confirm(message)) {
                $(e.target).prop('checked', false);
                return;
            }
        }
        this.markUnsavedChanges();
    };

    /**
     * Validate image URL
     */
    LocalSEOAdmin.validateImageURL = function(e) {
        var url = $(e.target).val();
        if (url && !this.isValidImageURL(url)) {
            this.markFieldError($(e.target), 'Please enter a valid image URL');
        }
    };

    /**
     * Check if URL is a valid image URL
     */
    LocalSEOAdmin.isValidImageURL = function(url) {
        if (!this.isValidURL(url)) return false;
        return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(url);
    };

    /**
     * Open WordPress media uploader
     */
    LocalSEOAdmin.openMediaUploader = function(e) {
        e.preventDefault();
        
        if (typeof wp !== 'undefined' && wp.media) {
            var mediaUploader = wp.media({
                title: 'Choose Business Image',
                button: {
                    text: 'Choose Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#location-image').val(attachment.url);
                LocalSEOAdmin.markUnsavedChanges();
            });

            mediaUploader.open();
        } else {
            this.showError('Media uploader not available');
        }
    };

    /**
     * Copy hours to all days
     */
    LocalSEOAdmin.copyHoursToAllDays = function(e) {
        e.preventDefault();
        
        var sourceDay = $(e.target).data('day');
        var sourceClosed = $('input[name="opening_hours[' + sourceDay + '][closed]"]').is(':checked');
        var sourceOpen = $('input[name="opening_hours[' + sourceDay + '][open]"]').val();
        var sourceClose = $('input[name="opening_hours[' + sourceDay + '][close]"]').val();
        
        var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        if (confirm('Copy these hours to all other days?')) {
            $.each(days, function(index, day) {
                if (day !== sourceDay) {
                    $('input[name="opening_hours[' + day + '][closed]"]').prop('checked', sourceClosed);
                    $('input[name="opening_hours[' + day + '][open]"]').val(sourceClosed ? '' : sourceOpen).prop('disabled', sourceClosed);
                    $('input[name="opening_hours[' + day + '][close]"]').val(sourceClosed ? '' : sourceClose).prop('disabled', sourceClosed);
                }
            });
            this.markUnsavedChanges();
        }
    };

    /**
     * Set standard business hours for all days
     */
    LocalSEOAdmin.setAllHours = function(e) {
        e.preventDefault();
        
        var hours = {
            weekday: {open: '09:00', close: '17:00'},
            weekend: {open: '10:00', close: '16:00'}
        };
        
        var weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        var weekends = ['saturday', 'sunday'];
        
        if (confirm('Set standard business hours (9 AM - 5 PM weekdays, 10 AM - 4 PM weekends)?')) {
            $.each(weekdays, function(index, day) {
                $('input[name="opening_hours[' + day + '][closed]"]').prop('checked', false);
                $('input[name="opening_hours[' + day + '][open]"]').val(hours.weekday.open).prop('disabled', false);
                $('input[name="opening_hours[' + day + '][close]"]').val(hours.weekday.close).prop('disabled', false);
            });
            
            $.each(weekends, function(index, day) {
                $('input[name="opening_hours[' + day + '][closed]"]').prop('checked', false);
                $('input[name="opening_hours[' + day + '][open]"]').val(hours.weekend.open).prop('disabled', false);
                $('input[name="opening_hours[' + day + '][close]"]').val(hours.weekend.close).prop('disabled', false);
            });
            
            this.markUnsavedChanges();
        }
    };

    /**
     * Clear all opening hours
     */
    LocalSEOAdmin.clearAllHours = function(e) {
        e.preventDefault();
        
        var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        if (confirm('Clear all opening hours?')) {
            $.each(days, function(index, day) {
                $('input[name="opening_hours[' + day + '][closed]"]').prop('checked', true);
                $('input[name="opening_hours[' + day + '][open]"]').val('').prop('disabled', true);
                $('input[name="opening_hours[' + day + '][close]"]').val('').prop('disabled', true);
            });
            this.markUnsavedChanges();
        }
    };

    /**
     * Handle window unload warning
     */
    LocalSEOAdmin.handleWindowUnload = function(e) {
        if (this.unsavedChanges && this.modal && this.modal.dialog('isOpen')) {
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    };

    /**
     * Handle keyboard shortcuts
     */
    LocalSEOAdmin.handleKeyboardShortcuts = function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            if (this.modal && this.modal.dialog('isOpen')) {
                e.preventDefault();
                this.saveLocation();
            }
        }
        
        // Escape to close modal
        if (e.which === 27 && this.modal && this.modal.dialog('isOpen')) {
            if (this.unsavedChanges) {
                if (confirm('You have unsaved changes. Are you sure you want to close?')) {
                    this.modal.dialog('close');
                }
            } else {
                this.modal.dialog('close');
            }
        }
    };

    /**
     * Real-time email validation
     */
    LocalSEOAdmin.validateEmail = function(e) {
        var email = $(e.target).val();
        if (email && !this.isValidEmail(email)) {
            this.markFieldError($(e.target), 'Please enter a valid email address');
        }
    };

    /**
     * Real-time URL validation
     */
    LocalSEOAdmin.validateURL = function(e) {
        var url = $(e.target).val();
        if (url && !this.isValidURL(url)) {
            this.markFieldError($(e.target), 'Please enter a valid URL (including http:// or https://)');
        }
    };

    /**
     * Format phone number as user types
     */
    LocalSEOAdmin.formatPhoneNumber = function(e) {
        var phone = $(e.target).val().replace(/\D/g, '');
        
        if (phone.length === 10) {
            $(e.target).val('(' + phone.substr(0, 3) + ') ' + phone.substr(3, 3) + '-' + phone.substr(6, 4));
        } else if (phone.length === 11 && phone.charAt(0) === '1') {
            $(e.target).val('1-(' + phone.substr(1, 3) + ') ' + phone.substr(4, 3) + '-' + phone.substr(7, 4));
        }
    };

    /**
     * Search locations in the table
     */
    LocalSEOAdmin.searchLocations = function(e) {
        var searchTerm = $(e.target).val().toLowerCase();
        
        $('#locations-tbody tr').each(function() {
            var $row = $(this);
            var text = $row.text().toLowerCase();
            
            if (text.indexOf(searchTerm) > -1 || searchTerm === '') {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        this.checkEmptyTable();
    };

    /**
     * Check if locations table is empty and show message
     */
    LocalSEOAdmin.checkEmptyTable = function() {
        var visibleRows = $('#locations-tbody tr:visible').length;
        var $noResults = $('#no-locations-message');
        
        if (visibleRows === 0) {
            if ($noResults.length === 0) {
                $('#locations-tbody').append(
                    '<tr id="no-locations-message"><td colspan="6" style="text-align: center; padding: 40px;">' +
                    'No locations found matching your search.' +
                    '</td></tr>'
                );
            }
        } else {
            $noResults.remove();
        }
    };

    /**
     * Export locations to CSV
     */
    LocalSEOAdmin.exportLocations = function(e) {
        e.preventDefault();
        
        if (!confirm('Export all locations to CSV file?')) {
            return;
        }
        
        // Create form and submit to trigger download
        var form = $('<form>', {
            method: 'POST',
            action: softworldLocalSEO.ajax_url
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'softworld_export_locations'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: softworldLocalSEO.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    };

    /**
     * Show import modal
     */
    LocalSEOAdmin.showImportModal = function(e) {
        e.preventDefault();
        // Implementation for import modal would go here
        alert('Import functionality coming soon!');
    };

    /**
     * Reset form to initial state
     */
    LocalSEOAdmin.resetForm = function() {
        if (document.getElementById('location-form')) {
            document.getElementById('location-form').reset();
        }
        
        // Reset checkboxes and disabled states
        $('.closed-checkbox').prop('checked', false);
        $('.time-inputs input').prop('disabled', false);
        
        // Clear validation errors
        $('.softworld-field-error').removeClass('softworld-field-error');
        $('.softworld-error-message').remove();
        
        // Reset state
        this.isEditMode = false;
        this.currentLocationId = null;
        this.unsavedChanges = false;
        
        // Hide map preview
        $('#location-map-preview').hide();
        
        // Reset to first tab
        $('.softworld-tab-nav li').removeClass('active');
        $('.softworld-tab-nav li:first').addClass('active');
        $('.softworld-tab-content').removeClass('active');
        $('.softworld-tab-content:first').addClass('active');
    };

    /**
     * Show loading state
     */
    LocalSEOAdmin.showLoading = function() {
        $('.ui-dialog-buttonset button').prop('disabled', true);
        $('#location-form').addClass('softworld-loading');
        
        if (!$('.loading-overlay').length) {
            $('<div class="loading-overlay"><div class="spinner"></div></div>')
                .appendTo('#location-modal');
        }
    };

    /**
     * Hide loading state
     */
    LocalSEOAdmin.hideLoading = function() {
        $('.ui-dialog-buttonset button').prop('disabled', false);
        $('#location-form').removeClass('softworld-loading');
        $('.loading-overlay').remove();
    };

    /**
     * Show saving state
     */
    LocalSEOAdmin.showSaving = function() {
        var $saveBtn = $('.ui-dialog-buttonset button:first');
        $saveBtn.prop('disabled', true).text('Saving...');
        this.showLoading();
    };

    /**
     * Hide saving state
     */
    LocalSEOAdmin.hideSaving = function() {
        var $saveBtn = $('.ui-dialog-buttonset button:first');
        $saveBtn.prop('disabled', false).text(softworldLocalSEO.i18n.save || 'Save');
        this.hideLoading();
    };

    /**
     * Show success message
     */
    LocalSEOAdmin.showSuccess = function(message) {
        this.showMessage(message, 'success');
    };

    /**
     * Show error message
     */
    LocalSEOAdmin.showError = function(message) {
        this.showMessage(message, 'error');
    };

    /**
     * Show warning message
     */
    LocalSEOAdmin.showWarning = function(message) {
        this.showMessage(message, 'warning');
    };

    /**
     * Show informational message
     */
    LocalSEOAdmin.showInfo = function(message) {
        this.showMessage(message, 'info');
    };

    /**
     * Show message with specified type
     */
    LocalSEOAdmin.showMessage = function(message, type) {
        type = type || 'info';
        
        // Remove existing messages
        $('.softworld-message').remove();
        
        // Create new message
        var $message = $('<div class="softworld-message ' + type + '">' + 
                        '<span class="message-text">' + message + '</span>' +
                        '<button type="button" class="message-dismiss">&times;</button>' +
                        '</div>');
        
        // Add to appropriate container
        var $container = this.modal && this.modal.dialog('isOpen') ? 
                        $('#location-modal') : $('.wrap');
        
        $container.prepend($message);
        
        // Add dismiss handler
        $message.find('.message-dismiss').on('click', function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Auto-dismiss after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to message if in modal
        if (this.modal && this.modal.dialog('isOpen')) {
            $('#location-modal').scrollTop(0);
        }
    };

    /**
     * Utility function to debounce function calls
     */
    LocalSEOAdmin.debounce = function(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    /**
     * Initialize the admin interface
     */
    LocalSEOAdmin.init();

    // Expose to global scope for debugging
    window.LocalSEOAdmin = LocalSEOAdmin;

})(jQuery);

// Additional CSS for loading states and messages
jQuery(document).ready(function($) {
    $('<style type="text/css">')
        .html(`
            .softworld-loading {
                opacity: 0.6;
                pointer-events: none;
            }
            
            .loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            
            .loading-overlay .spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #2271b1;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .softworld-message {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                margin: 12px 0;
                border-radius: 6px;
                border-left: 4px solid;
                font-weight: 500;
            }
            
            .softworld-message.success {
                background: #d1e7dd;
                color: #0f5132;
                border-left-color: #198754;
            }
            
            .softworld-message.error {
                background: #f8d7da;
                color: #721c24;
                border-left-color: #dc3545;
            }
            
            .softworld-message.warning {
                background: #fff3cd;
                color: #664d03;
                border-left-color: #ffc107;
            }
            
            .softworld-message.info {
                background: #d1ecf1;
                color: #055160;
                border-left-color: #0dcaf0;
            }
            
            .message-dismiss {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                padding: 0;
                margin-left: 12px;
                opacity: 0.6;
                transition: opacity 0.2s;
            }
            
            .message-dismiss:hover {
                opacity: 1;
            }
            
            .softworld-field-error {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            }
            
            .softworld-error-message {
                color: #dc3545;
                font-size: 12px;
                margin-top: 4px;
                display: block;
            }
            
            .deleting {
                opacity: 0.5;
                pointer-events: none;
            }
            
            .tooltip-content {
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                max-width: 200px;
                z-index: 10000;
                top: 100%;
                left: 0;
                margin-top: 5px;
            }
            
            .help-tooltip {
                position: relative;
                cursor: help;
            }
            
            #location-map-preview {
                width: 100%;
                height: 200px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-top: 10px;
                display: none;
            }
        `)
        .appendTo('head');
});