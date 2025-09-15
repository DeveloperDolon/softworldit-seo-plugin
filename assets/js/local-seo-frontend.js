/**
 * Softworld Local SEO Frontend JavaScript
 * 
 * Handles frontend interactions for local SEO features
 */

(function($) {
    'use strict';

    // Global variables
    var SoftworldLocalSEO = {
        maps: [],
        initialized: false
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SoftworldLocalSEO.init();
    });

    // Initialize Google Maps when API is loaded
    window.initSoftworldMaps = function() {
        SoftworldLocalSEO.initGoogleMaps();
    };

    SoftworldLocalSEO.init = function() {
        if (this.initialized) return;
        
        this.bindEvents();
        this.initResponsiveTables();
        this.initAccessibility();
        
        this.initialized = true;
    };

    /**
     * Bind event handlers
     */
    SoftworldLocalSEO.bindEvents = function() {
        // Handle business info interactions
        this.handleBusinessInfoClicks();
        
        // Handle opening hours display
        this.handleOpeningHours();
        
        // Handle location list interactions
        this.handleLocationList();
        
        // Handle map resize on window resize
        $(window).on('resize', this.handleMapResize.bind(this));
    };

    /**
     * Initialize responsive tables
     */
    SoftworldLocalSEO.initResponsiveTables = function() {
        $('.softworld-opening-hours-table').each(function() {
            var $table = $(this);
            var $wrapper = $('<div class="table-responsive"></div>');
            $table.wrap($wrapper);
        });
    };

    /**
     * Initialize accessibility features
     */
    SoftworldLocalSEO.initAccessibility = function() {
        // Add ARIA labels to interactive elements
        $('.softworld-google-map').each(function() {
            if (!$(this).attr('role')) {
                $(this).attr('role', 'application').attr('aria-label', 'Interactive map');
            }
        });

        // Add keyboard navigation for opening hours
        $('.softworld-opening-hours-table tr').attr('tabindex', '0');
    };

    /**
     * Handle business info interactions
     */
    SoftworldLocalSEO.handleBusinessInfoClicks = function() {
        // Make phone numbers clickable on mobile
        $('.business-phone').each(function() {
            var $phone = $(this);
            var phoneText = $phone.text();
            var phoneNumber = phoneText.replace(/[^\d+]/g, '');
            
            if (phoneNumber && !$phone.find('a').length) {
                var phoneLink = '<a href="tel:' + phoneNumber + '">' + phoneText + '</a>';
                $phone.html(phoneLink);
            }
        });

        // Handle email clicks
        $('.business-email a').on('click', function(e) {
            // Track email clicks if analytics is available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'email_click', {
                    event_category: 'local_seo',
                    event_label: 'business_email'
                });
            }
        });

        // Handle phone clicks
        $('.business-phone a').on('click', function(e) {
            // Track phone clicks if analytics is available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'phone_click', {
                    event_category: 'local_seo',
                    event_label: 'business_phone'
                });
            }
        });
    };

    /**
     * Handle opening hours functionality
     */
    SoftworldLocalSEO.handleOpeningHours = function() {
        // Highlight current day
        this.highlightCurrentDay();
        
        // Show current status (open/closed)
        this.showCurrentStatus();
        
        // Add toggle functionality for mobile
        this.addMobileToggle();
    };

    /**
     * Highlight current day in opening hours
     */
    SoftworldLocalSEO.highlightCurrentDay = function() {
        var days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var today = days[new Date().getDay()];
        
        $('.softworld-opening-hours-table tr, .softworld-opening-hours-list li').each(function() {
            var $row = $(this);
            var dayText = $row.find('.day-name, strong').first().text().toLowerCase();
            
            if (dayText.includes(today.substring(0, 3))) {
                $row.addClass('current-day');
            }
        });
    };

    /**
     * Show current open/closed status
     */
    SoftworldLocalSEO.showCurrentStatus = function() {
        var now = new Date();
        var currentTime = now.getHours() * 100 + now.getMinutes();
        var days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var today = days[now.getDay()];

        $('.softworld-opening-hours-table, .softworld-opening-hours-list').each(function() {
            var $container = $(this);
            var $statusIndicator = $('<div class="current-status"></div>');
            
            // Find today's hours
            var $todayRow = $container.find('tr, li').filter(function() {
                var dayText = $(this).find('.day-name, strong').first().text().toLowerCase();
                return dayText.includes(today.substring(0, 3));
            });

            if ($todayRow.length) {
                var hoursText = $todayRow.find('.hours').text() || $todayRow.text();
                var isOpen = this.isCurrentlyOpen(hoursText, currentTime);
                
                if (isOpen !== null) {
                    var statusText = isOpen ? 'Open Now' : 'Closed Now';
                    var statusClass = isOpen ? 'status-open' : 'status-closed';
                    
                    $statusIndicator.addClass(statusClass).text(statusText);
                    $container.before($statusIndicator);
                }
            }
        }.bind(this));
    };

    /**
     * Check if business is currently open
     */
    SoftworldLocalSEO.isCurrentlyOpen = function(hoursText, currentTime) {
        if (!hoursText || hoursText.toLowerCase().includes('closed')) {
            return false;
        }

        var timeMatch = hoursText.match(/(\d{1,2}):?(\d{2})?\s*(am|pm)?\s*-\s*(\d{1,2}):?(\d{2})?\s*(am|pm)?/i);
        
        if (!timeMatch) return null;

        var openHour = parseInt(timeMatch[1]);
        var openMin = parseInt(timeMatch[2] || '0');
        var openAmPm = timeMatch[3];
        var closeHour = parseInt(timeMatch[4]);
        var closeMin = parseInt(timeMatch[5] || '0');
        var closeAmPm = timeMatch[6];

        // Convert to 24-hour format
        if (openAmPm && openAmPm.toLowerCase() === 'pm' && openHour !== 12) {
            openHour += 12;
        } else if (openAmPm && openAmPm.toLowerCase() === 'am' && openHour === 12) {
            openHour = 0;
        }

        if (closeAmPm && closeAmPm.toLowerCase() === 'pm' && closeHour !== 12) {
            closeHour += 12;
        } else if (closeAmPm && closeAmPm.toLowerCase() === 'am' && closeHour === 12) {
            closeHour = 0;
        }

        var openTime = openHour * 100 + openMin;
        var closeTime = closeHour * 100 + closeMin;

        // Handle overnight hours (e.g., 10 PM - 2 AM)
        if (closeTime < openTime) {
            return currentTime >= openTime || currentTime <= closeTime;
        }

        return currentTime >= openTime && currentTime <= closeTime;
    };

    /**
     * Add mobile toggle for opening hours
     */
    SoftworldLocalSEO.addMobileToggle = function() {
        if ($(window).width() <= 768) {
            $('.softworld-opening-hours-table, .softworld-opening-hours-list').each(function() {
                var $container = $(this);
                var $toggle = $('<button class="hours-toggle">Show Hours</button>');
                
                $toggle.on('click', function() {
                    $container.toggle();
                    $(this).text($container.is(':visible') ? 'Hide Hours' : 'Show Hours');
                });
                
                $container.hide().before($toggle);
            });
        }
    };

    /**
     * Handle location list interactions
     */
    SoftworldLocalSEO.handleLocationList = function() {
        // Add click handlers for location items
        $('.location-item').on('click', function(e) {
            if (!$(e.target).is('a')) {
                var $phone = $(this).find('.location-phone a');
                if ($phone.length) {
                    window.location.href = $phone.attr('href');
                }
            }
        });

        // Add keyboard navigation
        $('.location-item').attr('tabindex', '0').on('keydown', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                $(this).trigger('click');
                e.preventDefault();
            }
        });
    };

    /**
     * Initialize Google Maps
     */
    SoftworldLocalSEO.initGoogleMaps = function() {
        if (typeof google === 'undefined' || !google.maps) {
            console.log('Google Maps API not loaded');
            return;
        }

        $('.softworld-google-map').each(function() {
            var $mapDiv = $(this);
            var mapId = $mapDiv.attr('id');
            
            if (!mapId || SoftworldLocalSEO.maps[mapId]) {
                return; // Skip if no ID or already initialized
            }

            var lat = parseFloat($mapDiv.data('lat'));
            var lng = parseFloat($mapDiv.data('lng'));
            var zoom = parseInt($mapDiv.data('zoom')) || 15;
            var title = $mapDiv.data('title') || 'Location';

            if (isNaN(lat) || isNaN(lng)) {
                console.log('Invalid coordinates for map: ' + mapId);
                return;
            }

            var mapOptions = {
                center: { lat: lat, lng: lng },
                zoom: zoom,
                styles: this.getMapStyles(),
                gestureHandling: 'cooperative',
                mapTypeControl: false,
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true
            };

            var map = new google.maps.Map(document.getElementById(mapId), mapOptions);
            
            var marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: title,
                animation: google.maps.Animation.DROP
            });

            // Add info window if data is provided
            var infoContent = $mapDiv.data('info');
            if (infoContent) {
                var infoWindow = new google.maps.InfoWindow({
                    content: infoContent
                });

                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });
            }

            // Store map reference
            SoftworldLocalSEO.maps[mapId] = map;

            // Add resize listener
            google.maps.event.addListener(map, 'idle', function() {
                google.maps.event.trigger(map, 'resize');
            });

        }.bind(this));
    };

    /**
     * Get custom map styles
     */
    SoftworldLocalSEO.getMapStyles = function() {
        return [
            {
                featureType: 'all',
                elementType: 'labels.text.fill',
                stylers: [{ color: '#444444' }]
            },
            {
                featureType: 'landscape',
                elementType: 'all',
                stylers: [{ color: '#f2f2f2' }]
            },
            {
                featureType: 'poi',
                elementType: 'all',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'road',
                elementType: 'all',
                stylers: [{ saturation: -100 }, { lightness: 45 }]
            },
            {
                featureType: 'road.highway',
                elementType: 'all',
                stylers: [{ visibility: 'simplified' }]
            },
            {
                featureType: 'transit',
                elementType: 'all',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'water',
                elementType: 'all',
                stylers: [{ color: '#b4d4e1' }, { visibility: 'on' }]
            }
        ];
    };

    /**
     * Handle map resize
     */
    SoftworldLocalSEO.handleMapResize = function() {
        Object.keys(this.maps).forEach(function(mapId) {
            var map = SoftworldLocalSEO.maps[mapId];
            if (map) {
                google.maps.event.trigger(map, 'resize');
            }
        });
    };

    /**
     * Utility function to detect mobile devices
     */
    SoftworldLocalSEO.isMobile = function() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    };

    /**
     * Add loading animation
     */
    SoftworldLocalSEO.showLoading = function($element) {
        $element.addClass('softworld-loading');
        if (!$element.find('.softworld-spinner').length) {
            $element.append('<div class="softworld-spinner"></div>');
        }
    };

    /**
     * Remove loading animation
     */
    SoftworldLocalSEO.hideLoading = function($element) {
        $element.removeClass('softworld-loading');
        $element.find('.softworld-spinner').remove();
    };

    /**
     * Show message
     */
    SoftworldLocalSEO.showMessage = function(message, type, $container) {
        type = type || 'info';
        $container = $container || $('body');
        
        var $message = $('<div class="softworld-message ' + type + '">' + message + '</div>');
        $container.prepend($message);
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };

    /**
     * Format phone number for display
     */
    SoftworldLocalSEO.formatPhoneNumber = function(phone) {
        var cleaned = ('' + phone).replace(/\D/g, '');
        var match = cleaned.match(/^1?(\d{3})(\d{3})(\d{4})$/);
        
        if (match) {
            var intlCode = (match[0].length === 11) ? '1-' : '';
            return [intlCode, '(', match[1], ') ', match[2], '-', match[3]].join('');
        }
        
        return phone;
    };

    /**
     * Get directions URL
     */
    SoftworldLocalSEO.getDirectionsUrl = function(lat, lng, address) {
        var baseUrl = 'https://www.google.com/maps/dir/?api=1&destination=';
        
        if (lat && lng) {
            return baseUrl + lat + ',' + lng;
        } else if (address) {
            return baseUrl + encodeURIComponent(address);
        }
        
        return null;
    };

    /**
     * Initialize structured data
     */
    SoftworldLocalSEO.initStructuredData = function() {
        // This function can be used to dynamically add structured data
        // if needed for specific implementations
        var $businessInfo = $('.softworld-business-info');
        if ($businessInfo.length && typeof gtag !== 'undefined') {
            gtag('event', 'view_business_info', {
                event_category: 'local_seo',
                event_label: 'business_info_displayed'
            });
        }
    };

    // Expose to global scope for external use
    window.SoftworldLocalSEO = SoftworldLocalSEO;

})(jQuery);