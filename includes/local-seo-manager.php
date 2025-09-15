<?php
/**
 * Local SEO Manager
 * 
 * Handles local business schema, locations, maps, and business information
 * 
 * @package Softworld_AI_SEO
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Local_Manager
{
    private $option_name;
    private $locations_table;

    public function __construct($option_name = 'softworld-ai-seo')
    {
        $this->option_name = $option_name;
        global $wpdb;
        $this->locations_table = $wpdb->prefix . 'softworld_seo_locations';
        
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'output_local_schema'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_softworld_add_location', array($this, 'ajax_add_location'));
        add_action('wp_ajax_softworld_update_location', array($this, 'ajax_update_location'));
        add_action('wp_ajax_softworld_delete_location', array($this, 'ajax_delete_location'));
        add_action('wp_ajax_softworld_get_location', array($this, 'ajax_get_location'));
        
        // Shortcodes
        add_shortcode('softworld_business_info', array($this, 'business_info_shortcode'));
        add_shortcode('softworld_opening_hours', array($this, 'opening_hours_shortcode'));
        add_shortcode('softworld_google_map', array($this, 'google_map_shortcode'));
        add_shortcode('softworld_locations_list', array($this, 'locations_list_shortcode'));
    }

    public function init()
    {
        $this->create_locations_table();
    }

    /**
     * Create locations table
     */
    public function create_locations_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->locations_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text NOT NULL,
            city varchar(100) NOT NULL,
            state varchar(100) NOT NULL,
            postal_code varchar(20) NOT NULL,
            country varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '',
            email varchar(100) DEFAULT '',
            website varchar(255) DEFAULT '',
            latitude decimal(10, 8) DEFAULT NULL,
            longitude decimal(11, 8) DEFAULT NULL,
            business_type varchar(100) DEFAULT 'LocalBusiness',
            opening_hours longtext DEFAULT NULL,
            description text DEFAULT '',
            image_url varchar(255) DEFAULT '',
            price_range varchar(10) DEFAULT '',
            accepts_reservations tinyint(1) DEFAULT 0,
            serves_cuisine varchar(255) DEFAULT '',
            payment_accepted varchar(255) DEFAULT '',
            is_primary tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY is_primary (is_primary)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get all locations
     */
    public function get_locations($status = 'active')
    {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->locations_table} WHERE status = %s ORDER BY is_primary DESC, name ASC",
            $status
        ));
    }

    /**
     * Get primary location
     */
    public function get_primary_location()
    {
        global $wpdb;
        
        $location = $wpdb->get_row(
            "SELECT * FROM {$this->locations_table} WHERE is_primary = 1 AND status = 'active' LIMIT 1"
        );

        // If no primary location, get the first active location
        if (!$location) {
            $location = $wpdb->get_row(
                "SELECT * FROM {$this->locations_table} WHERE status = 'active' ORDER BY id ASC LIMIT 1"
            );
        }

        return $location;
    }

    /**
     * Get location by ID
     */
    public function get_location($id)
    {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->locations_table} WHERE id = %d",
            $id
        ));
    }

    /**
     * Add new location
     */
    public function add_location($data)
    {
        global $wpdb;

        // If this is set as primary, unset other primary locations
        if (!empty($data['is_primary'])) {
            $wpdb->update(
                $this->locations_table,
                array('is_primary' => 0),
                array(),
                array('%d'),
                array()
            );
        }

        // Parse opening hours if provided
        if (isset($data['opening_hours']) && is_array($data['opening_hours'])) {
            $data['opening_hours'] = json_encode($data['opening_hours']);
        }

        $result = $wpdb->insert($this->locations_table, $data);

        if ($result !== false) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update location
     */
    public function update_location($id, $data)
    {
        global $wpdb;

        // If this is set as primary, unset other primary locations
        if (!empty($data['is_primary'])) {
            $wpdb->update(
                $this->locations_table,
                array('is_primary' => 0),
                array('id !=' => $id),
                array('%d'),
                array('%d')
            );
        }

        // Parse opening hours if provided
        if (isset($data['opening_hours']) && is_array($data['opening_hours'])) {
            $data['opening_hours'] = json_encode($data['opening_hours']);
        }

        return $wpdb->update(
            $this->locations_table,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }

    /**
     * Delete location
     */
    public function delete_location($id)
    {
        global $wpdb;

        return $wpdb->delete(
            $this->locations_table,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Get Google Maps API key
     */
    public function get_google_maps_api_key()
    {
        return get_option('softworld_ai_seo_google_maps_api_key', '');
    }

    /**
     * Output local business schema
     */
    public function output_local_schema()
    {
        if (is_admin()) {
            return;
        }

        $primary_location = $this->get_primary_location();
        if (!$primary_location) {
            return;
        }

        $schema = $this->generate_local_business_schema($primary_location);
        
        if ($schema) {
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    /**
     * Generate local business schema
     */
    public function generate_local_business_schema($location)
    {
        if (!$location) {
            return false;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $location->business_type ?: 'LocalBusiness',
            'name' => $location->name,
            'description' => $location->description,
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => $location->address,
                'addressLocality' => $location->city,
                'addressRegion' => $location->state,
                'postalCode' => $location->postal_code,
                'addressCountry' => $location->country
            )
        );

        // Add coordinates if available
        if ($location->latitude && $location->longitude) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => floatval($location->latitude),
                'longitude' => floatval($location->longitude)
            );
        }

        // Add contact information
        if ($location->phone) {
            $schema['telephone'] = $location->phone;
        }

        if ($location->email) {
            $schema['email'] = $location->email;
        }

        if ($location->website) {
            $schema['url'] = $location->website;
        }

        // Add image
        if ($location->image_url) {
            $schema['image'] = $location->image_url;
        }

        // Add price range
        if ($location->price_range) {
            $schema['priceRange'] = $location->price_range;
        }

        // Add opening hours
        if ($location->opening_hours) {
            $opening_hours = json_decode($location->opening_hours, true);
            if ($opening_hours && is_array($opening_hours)) {
                $schema['openingHoursSpecification'] = $this->format_opening_hours_for_schema($opening_hours);
            }
        }

        // Add restaurant-specific fields
        if ($location->business_type === 'Restaurant') {
            if ($location->accepts_reservations) {
                $schema['acceptsReservations'] = true;
            }
            
            if ($location->serves_cuisine) {
                $schema['servesCuisine'] = explode(',', $location->serves_cuisine);
            }
        }

        // Add payment methods
        if ($location->payment_accepted) {
            $schema['paymentAccepted'] = explode(',', $location->payment_accepted);
        }

        return $schema;
    }

    /**
     * Format opening hours for schema
     */
    private function format_opening_hours_for_schema($opening_hours)
    {
        $schema_hours = array();
        
        $day_mapping = array(
            'monday' => 'Mo',
            'tuesday' => 'Tu',
            'wednesday' => 'We',
            'thursday' => 'Th',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'Su'
        );

        foreach ($opening_hours as $day => $hours) {
            if (empty($hours['closed']) && !empty($hours['open']) && !empty($hours['close'])) {
                $schema_hours[] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day_mapping[$day] ?? ucfirst($day),
                    'opens' => $hours['open'],
                    'closes' => $hours['close']
                );
            }
        }

        return $schema_hours;
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        $api_key = $this->get_google_maps_api_key();
        
        if ($api_key) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places',
                array(),
                null,
                true
            );
        }

        wp_enqueue_script(
            'softworld-local-seo-frontend',
            plugin_dir_url(__FILE__) . '../assets/js/local-seo-frontend.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }

    /**
     * AJAX: Add location
     */
    public function ajax_add_location()
    {
        check_ajax_referer('softworld_local_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $data = $this->sanitize_location_data($_POST);
        
        $location_id = $this->add_location($data);

        if ($location_id) {
            wp_send_json_success(array(
                'message' => __('Location added successfully', 'softworld-ai-seo'),
                'location_id' => $location_id
            ));
        } else {
            wp_send_json_error(__('Failed to add location', 'softworld-ai-seo'));
        }
    }

    /**
     * AJAX: Update location
     */
    public function ajax_update_location()
    {
        check_ajax_referer('softworld_local_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $location_id = intval($_POST['location_id']);
        $data = $this->sanitize_location_data($_POST);

        $result = $this->update_location($location_id, $data);

        if ($result !== false) {
            wp_send_json_success(__('Location updated successfully', 'softworld-ai-seo'));
        } else {
            wp_send_json_error(__('Failed to update location', 'softworld-ai-seo'));
        }
    }

    /**
     * AJAX: Delete location
     */
    public function ajax_delete_location()
    {
        check_ajax_referer('softworld_local_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $location_id = intval($_POST['location_id']);

        $result = $this->delete_location($location_id);

        if ($result !== false) {
            wp_send_json_success(__('Location deleted successfully', 'softworld-ai-seo'));
        } else {
            wp_send_json_error(__('Failed to delete location', 'softworld-ai-seo'));
        }
    }

    /**
     * AJAX: Get location
     */
    public function ajax_get_location()
    {
        check_ajax_referer('softworld_local_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $location_id = intval($_POST['location_id']);
        $location = $this->get_location($location_id);

        if ($location) {
            // Parse opening hours
            if ($location->opening_hours) {
                $location->opening_hours = json_decode($location->opening_hours, true);
            }

            wp_send_json_success($location);
        } else {
            wp_send_json_error(__('Location not found', 'softworld-ai-seo'));
        }
    }

    /**
     * Sanitize location data
     */
    private function sanitize_location_data($data)
    {
        return array(
            'name' => sanitize_text_field($data['name'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'state' => sanitize_text_field($data['state'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'country' => sanitize_text_field($data['country'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'website' => esc_url_raw($data['website'] ?? ''),
            'latitude' => !empty($data['latitude']) ? floatval($data['latitude']) : null,
            'longitude' => !empty($data['longitude']) ? floatval($data['longitude']) : null,
            'business_type' => sanitize_text_field($data['business_type'] ?? 'LocalBusiness'),
            'opening_hours' => isset($data['opening_hours']) ? $data['opening_hours'] : null,
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'price_range' => sanitize_text_field($data['price_range'] ?? ''),
            'accepts_reservations' => !empty($data['accepts_reservations']) ? 1 : 0,
            'serves_cuisine' => sanitize_text_field($data['serves_cuisine'] ?? ''),
            'payment_accepted' => sanitize_text_field($data['payment_accepted'] ?? ''),
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'status' => sanitize_text_field($data['status'] ?? 'active')
        );
    }

    /**
     * Business info shortcode
     */
    public function business_info_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'location_id' => null,
            'show' => 'all', // all, name, address, phone, email, website
            'template' => 'default'
        ), $atts, 'softworld_business_info');

        if ($atts['location_id']) {
            $location = $this->get_location($atts['location_id']);
        } else {
            $location = $this->get_primary_location();
        }

        if (!$location) {
            return '';
        }

        ob_start();
        
        $template_file = locate_template('softworld-seo/business-info-' . $atts['template'] . '.php');
        if (!$template_file) {
            $template_file = plugin_dir_path(__FILE__) . '../templates/business-info-' . $atts['template'] . '.php';
        }
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_default_business_info($location, $atts['show']);
        }
        
        return ob_get_clean();
    }

    /**
     * Opening hours shortcode
     */
    public function opening_hours_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'location_id' => null,
            'format' => 'table', // table, list
            'show_closed' => 'yes'
        ), $atts, 'softworld_opening_hours');

        if ($atts['location_id']) {
            $location = $this->get_location($atts['location_id']);
        } else {
            $location = $this->get_primary_location();
        }

        if (!$location || !$location->opening_hours) {
            return '';
        }

        $opening_hours = json_decode($location->opening_hours, true);
        if (!$opening_hours) {
            return '';
        }

        ob_start();
        $this->render_opening_hours($opening_hours, $atts['format'], $atts['show_closed'] === 'yes');
        return ob_get_clean();
    }

    /**
     * Google Map shortcode
     */
    public function google_map_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'location_id' => null,
            'width' => '100%',
            'height' => '300px',
            'zoom' => '15',
            'show_info_window' => 'yes'
        ), $atts, 'softworld_google_map');

        if (!$this->get_google_maps_api_key()) {
            return '<p>' . __('Google Maps API key not configured', 'softworld-ai-seo') . '</p>';
        }

        if ($atts['location_id']) {
            $location = $this->get_location($atts['location_id']);
        } else {
            $location = $this->get_primary_location();
        }

        if (!$location || !$location->latitude || !$location->longitude) {
            return '<p>' . __('Location coordinates not available', 'softworld-ai-seo') . '</p>';
        }

        $map_id = 'softworld-map-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($map_id); ?>" class="softworld-google-map" 
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"></div>
        <script>
        (function() {
            function initMap() {
                var location = {
                    lat: <?php echo floatval($location->latitude); ?>,
                    lng: <?php echo floatval($location->longitude); ?>
                };
                
                var map = new google.maps.Map(document.getElementById('<?php echo esc_js($map_id); ?>'), {
                    zoom: <?php echo intval($atts['zoom']); ?>,
                    center: location
                });
                
                var marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: '<?php echo esc_js($location->name); ?>'
                });
                
                <?php if ($atts['show_info_window'] === 'yes'): ?>
                var infoWindow = new google.maps.InfoWindow({
                    content: '<div><h4><?php echo esc_js($location->name); ?></h4><p><?php echo esc_js($location->address . ', ' . $location->city . ', ' . $location->state); ?></p></div>'
                });
                
                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });
                <?php endif; ?>
            }
            
            if (typeof google !== 'undefined' && google.maps) {
                initMap();
            } else {
                // Wait for Google Maps API to load
                var checkGoogleMaps = setInterval(function() {
                    if (typeof google !== 'undefined' && google.maps) {
                        clearInterval(checkGoogleMaps);
                        initMap();
                    }
                }, 100);
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Locations list shortcode
     */
    public function locations_list_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'show_map' => 'no',
            'template' => 'default'
        ), $atts, 'softworld_locations_list');

        $locations = $this->get_locations();
        
        if (intval($atts['limit']) > 0) {
            $locations = array_slice($locations, 0, intval($atts['limit']));
        }

        if (empty($locations)) {
            return '<p>' . __('No locations found', 'softworld-ai-seo') . '</p>';
        }

        ob_start();
        
        $template_file = locate_template('softworld-seo/locations-list-' . $atts['template'] . '.php');
        if (!$template_file) {
            $template_file = plugin_dir_path(__FILE__) . '../templates/locations-list-' . $atts['template'] . '.php';
        }
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_default_locations_list($locations, $atts);
        }
        
        return ob_get_clean();
    }

    /**
     * Render default business info
     */
    private function render_default_business_info($location, $show)
    {
        echo '<div class="softworld-business-info">';
        
        if ($show === 'all' || $show === 'name') {
            echo '<h3 class="business-name">' . esc_html($location->name) . '</h3>';
        }
        
        if ($show === 'all' || $show === 'address') {
            echo '<div class="business-address">';
            echo '<p>' . esc_html($location->address) . '</p>';
            echo '<p>' . esc_html($location->city . ', ' . $location->state . ' ' . $location->postal_code) . '</p>';
            if ($location->country) {
                echo '<p>' . esc_html($location->country) . '</p>';
            }
            echo '</div>';
        }
        
        if (($show === 'all' || $show === 'phone') && $location->phone) {
            echo '<div class="business-phone">';
            echo '<strong>' . __('Phone:', 'softworld-ai-seo') . '</strong> ';
            echo '<a href="tel:' . esc_attr($location->phone) . '">' . esc_html($location->phone) . '</a>';
            echo '</div>';
        }
        
        if (($show === 'all' || $show === 'email') && $location->email) {
            echo '<div class="business-email">';
            echo '<strong>' . __('Email:', 'softworld-ai-seo') . '</strong> ';
            echo '<a href="mailto:' . esc_attr($location->email) . '">' . esc_html($location->email) . '</a>';
            echo '</div>';
        }
        
        if (($show === 'all' || $show === 'website') && $location->website) {
            echo '<div class="business-website">';
            echo '<strong>' . __('Website:', 'softworld-ai-seo') . '</strong> ';
            echo '<a href="' . esc_url($location->website) . '" target="_blank">' . esc_html($location->website) . '</a>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Render opening hours
     */
    private function render_opening_hours($opening_hours, $format, $show_closed)
    {
        $days = array(
            'monday' => __('Monday', 'softworld-ai-seo'),
            'tuesday' => __('Tuesday', 'softworld-ai-seo'),
            'wednesday' => __('Wednesday', 'softworld-ai-seo'),
            'thursday' => __('Thursday', 'softworld-ai-seo'),
            'friday' => __('Friday', 'softworld-ai-seo'),
            'saturday' => __('Saturday', 'softworld-ai-seo'),
            'sunday' => __('Sunday', 'softworld-ai-seo')
        );

        if ($format === 'table') {
            echo '<table class="softworld-opening-hours-table">';
            foreach ($days as $day_key => $day_name) {
                $hours = $opening_hours[$day_key] ?? array();
                $is_closed = !empty($hours['closed']);
                
                if (!$show_closed && $is_closed) {
                    continue;
                }
                
                echo '<tr>';
                echo '<td class="day-name">' . esc_html($day_name) . '</td>';
                echo '<td class="hours">';
                
                if ($is_closed) {
                    echo '<span class="closed">' . __('Closed', 'softworld-ai-seo') . '</span>';
                } else {
                    $open = $hours['open'] ?? '';
                    $close = $hours['close'] ?? '';
                    if ($open && $close) {
                        echo esc_html($open . ' - ' . $close);
                    } else {
                        echo '<span class="closed">' . __('Closed', 'softworld-ai-seo') . '</span>';
                    }
                }
                
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<ul class="softworld-opening-hours-list">';
            foreach ($days as $day_key => $day_name) {
                $hours = $opening_hours[$day_key] ?? array();
                $is_closed = !empty($hours['closed']);
                
                if (!$show_closed && $is_closed) {
                    continue;
                }
                
                echo '<li>';
                echo '<strong>' . esc_html($day_name) . ':</strong> ';
                
                if ($is_closed) {
                    echo '<span class="closed">' . __('Closed', 'softworld-ai-seo') . '</span>';
                } else {
                    $open = $hours['open'] ?? '';
                    $close = $hours['close'] ?? '';
                    if ($open && $close) {
                        echo esc_html($open . ' - ' . $close);
                    } else {
                        echo '<span class="closed">' . __('Closed', 'softworld-ai-seo') . '</span>';
                    }
                }
                
                echo '</li>';
            }
            echo '</ul>';
        }
    }

    /**
     * Render default locations list
     */
    private function render_default_locations_list($locations, $atts)
    {
        echo '<div class="softworld-locations-list">';
        
        foreach ($locations as $location) {
            echo '<div class="location-item">';
            echo '<h4>' . esc_html($location->name) . '</h4>';
            echo '<div class="location-address">';
            echo '<p>' . esc_html($location->address) . '</p>';
            echo '<p>' . esc_html($location->city . ', ' . $location->state . ' ' . $location->postal_code) . '</p>';
            echo '</div>';
            
            if ($location->phone) {
                echo '<div class="location-phone">';
                echo '<a href="tel:' . esc_attr($location->phone) . '">' . esc_html($location->phone) . '</a>';
                echo '</div>';
            }
            
            if ($atts['show_map'] === 'yes' && $location->latitude && $location->longitude) {
                echo do_shortcode('[softworld_google_map location_id="' . $location->id . '" height="200px"]');
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        // Save settings
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
            check_admin_referer('softworld_local_seo_settings');
            
            update_option('softworld_ai_seo_google_maps_api_key', sanitize_text_field($_POST['google_maps_api_key'] ?? ''));
            
            echo '<div class="updated notice"><p>' . __('Settings saved successfully!', 'softworld-ai-seo') . '</p></div>';
        }

        $locations = $this->get_locations();
        $api_key = $this->get_google_maps_api_key();
        
        // Enqueue admin scripts
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Local SEO Manager', 'softworld-ai-seo'); ?></h1>
            
            <!-- API Settings -->
            <div class="softworld-local-seo-section">
                <h2><?php _e('Google Maps API Settings', 'softworld-ai-seo'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('softworld_local_seo_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Google Maps API Key', 'softworld-ai-seo'); ?></th>
                            <td>
                                <input type="text" name="google_maps_api_key" value="<?php echo esc_attr($api_key); ?>" 
                                       class="regular-text" />
                                <p class="description">
                                    <?php _e('Enter your Google Maps API key to enable map functionality.', 'softworld-ai-seo'); ?>
                                    <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                                        <?php _e('Get API key', 'softworld-ai-seo'); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Save Settings', 'softworld-ai-seo'), 'primary', 'save_settings'); ?>
                </form>
            </div>

            <!-- Locations Management -->
            <div class="softworld-local-seo-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2><?php _e('Business Locations', 'softworld-ai-seo'); ?></h2>
                    <button type="button" id="add-location-btn" class="button button-primary">
                        <?php _e('Add New Location', 'softworld-ai-seo'); ?>
                    </button>
                </div>

                <div class="softworld-locations-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Address', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Phone', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Type', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Primary', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Actions', 'softworld-ai-seo'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="locations-tbody">
                            <?php if (empty($locations)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">
                                        <?php _e('No locations found. Add your first location to get started.', 'softworld-ai-seo'); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($locations as $location): ?>
                                    <tr data-location-id="<?php echo $location->id; ?>">
                                        <td>
                                            <strong><?php echo esc_html($location->name); ?></strong>
                                            <?php if ($location->description): ?>
                                                <br><small><?php echo esc_html(wp_trim_words($location->description, 10)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html($location->address); ?><br>
                                            <?php echo esc_html($location->city . ', ' . $location->state . ' ' . $location->postal_code); ?>
                                        </td>
                                        <td><?php echo esc_html($location->phone); ?></td>
                                        <td><?php echo esc_html($location->business_type); ?></td>
                                        <td>
                                            <?php if ($location->is_primary): ?>
                                                <span class="dashicons dashicons-star-filled" style="color: #f4d03f;"></span>
                                            <?php else: ?>
                                                <span class="dashicons dashicons-star-empty"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small edit-location-btn" 
                                                    data-location-id="<?php echo $location->id; ?>">
                                                <?php _e('Edit', 'softworld-ai-seo'); ?>
                                            </button>
                                            <button type="button" class="button button-small button-link-delete delete-location-btn" 
                                                    data-location-id="<?php echo $location->id; ?>">
                                                <?php _e('Delete', 'softworld-ai-seo'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Schema Preview -->
            <?php if (!empty($locations)): ?>
                <div class="softworld-local-seo-section">
                    <h2><?php _e('Schema Preview', 'softworld-ai-seo'); ?></h2>
                    <p class="description">
                        <?php _e('This is how your local business schema will appear to search engines:', 'softworld-ai-seo'); ?>
                    </p>
                    
                    <?php 
                    $primary_location = $this->get_primary_location();
                    if ($primary_location):
                        $schema = $this->generate_local_business_schema($primary_location);
                    ?>
                        <pre class="softworld-schema-preview"><code><?php echo esc_html(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></code></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Shortcode Help -->
            <div class="softworld-local-seo-section">
                <h2><?php _e('Shortcodes', 'softworld-ai-seo'); ?></h2>
                <p class="description">
                    <?php _e('Use these shortcodes to display location information on your website:', 'softworld-ai-seo'); ?>
                </p>
                
                <div class="softworld-shortcode-help">
                    <h4><?php _e('Business Information', 'softworld-ai-seo'); ?></h4>
                    <code>[softworld_business_info]</code> - <?php _e('Display primary location business info', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_business_info location_id="1"]</code> - <?php _e('Display specific location business info', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_business_info show="name,phone"]</code> - <?php _e('Display only name and phone', 'softworld-ai-seo'); ?><br><br>
                    
                    <h4><?php _e('Opening Hours', 'softworld-ai-seo'); ?></h4>
                    <code>[softworld_opening_hours]</code> - <?php _e('Display opening hours as table', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_opening_hours format="list"]</code> - <?php _e('Display opening hours as list', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_opening_hours show_closed="no"]</code> - <?php _e('Hide closed days', 'softworld-ai-seo'); ?><br><br>
                    
                    <h4><?php _e('Google Maps', 'softworld-ai-seo'); ?></h4>
                    <code>[softworld_google_map]</code> - <?php _e('Display map for primary location', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_google_map location_id="1" height="400px"]</code> - <?php _e('Custom location and height', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_google_map zoom="12" show_info_window="no"]</code> - <?php _e('Custom zoom and no info window', 'softworld-ai-seo'); ?><br><br>
                    
                    <h4><?php _e('Locations List', 'softworld-ai-seo'); ?></h4>
                    <code>[softworld_locations_list]</code> - <?php _e('Display all locations', 'softworld-ai-seo'); ?><br>
                    <code>[softworld_locations_list limit="3" show_map="yes"]</code> - <?php _e('Limit to 3 locations with maps', 'softworld-ai-seo'); ?>
                </div>
            </div>
        </div>

        <!-- Location Modal -->
        <div id="location-modal" title="<?php _e('Location Details', 'softworld-ai-seo'); ?>" style="display: none;">
            <form id="location-form">
                <input type="hidden" id="location-id" name="location_id" value="">
                
                <div class="softworld-modal-tabs">
                    <ul class="softworld-tab-nav">
                        <li class="active"><a href="#basic-info-tab"><?php _e('Basic Info', 'softworld-ai-seo'); ?></a></li>
                        <li><a href="#contact-tab"><?php _e('Contact', 'softworld-ai-seo'); ?></a></li>
                        <li><a href="#location-tab"><?php _e('Location', 'softworld-ai-seo'); ?></a></li>
                        <li><a href="#hours-tab"><?php _e('Opening Hours', 'softworld-ai-seo'); ?></a></li>
                        <li><a href="#advanced-tab"><?php _e('Advanced', 'softworld-ai-seo'); ?></a></li>
                    </ul>
                    
                    <!-- Basic Info Tab -->
                    <div id="basic-info-tab" class="softworld-tab-content active">
                        <table class="form-table">
                            <tr>
                                <th><label for="location-name"><?php _e('Business Name', 'softworld-ai-seo'); ?> *</label></th>
                                <td><input type="text" id="location-name" name="name" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="business-type"><?php _e('Business Type', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <select id="business-type" name="business_type" class="regular-text">
                                        <option value="LocalBusiness">Local Business</option>
                                        <option value="Restaurant">Restaurant</option>
                                        <option value="Store">Store</option>
                                        <option value="Hotel">Hotel</option>
                                        <option value="Hospital">Hospital</option>
                                        <option value="Dentist">Dentist</option>
                                        <option value="LegalService">Legal Service</option>
                                        <option value="RealEstateAgent">Real Estate Agent</option>
                                        <option value="BeautySalon">Beauty Salon</option>
                                        <option value="GasStation">Gas Station</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="location-description"><?php _e('Description', 'softworld-ai-seo'); ?></label></th>
                                <td><textarea id="location-description" name="description" rows="3" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="location-image"><?php _e('Image URL', 'softworld-ai-seo'); ?></label></th>
                                <td><input type="url" id="location-image" name="image_url" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="is-primary"><?php _e('Primary Location', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="is-primary" name="is_primary" value="1">
                                        <?php _e('Set as primary location', 'softworld-ai-seo'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Contact Tab -->
                    <div id="contact-tab" class="softworld-tab-content">
                        <table class="form-table">
                            <tr>
                                <th><label for="location-phone"><?php _e('Phone', 'softworld-ai-seo'); ?></label></th>
                                <td><input type="tel" id="location-phone" name="phone" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="location-email"><?php _e('Email', 'softworld-ai-seo'); ?></label></th>
                                <td><input type="email" id="location-email" name="email" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="location-website"><?php _e('Website', 'softworld-ai-seo'); ?></label></th>
                                <td><input type="url" id="location-website" name="website" class="regular-text"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Location Tab -->
                    <div id="location-tab" class="softworld-tab-content">
                        <table class="form-table">
                            <tr>
                                <th><label for="location-address"><?php _e('Address', 'softworld-ai-seo'); ?> *</label></th>
                                <td><textarea id="location-address" name="address" required rows="2" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="location-city"><?php _e('City', 'softworld-ai-seo'); ?> *</label></th>
                                <td><input type="text" id="location-city" name="city" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="location-state"><?php _e('State/Province', 'softworld-ai-seo'); ?> *</label></th>
                                <td><input type="text" id="location-state" name="state" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="location-postal"><?php _e('Postal Code', 'softworld-ai-seo'); ?></label></th>
                                <td><input type="text" id="location-postal" name="postal_code" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="location-country"><?php _e('Country', 'softworld-ai-seo'); ?> *</label></th>
                                <td><input type="text" id="location-country" name="country" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="location-latitude"><?php _e('Latitude', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <input type="number" id="location-latitude" name="latitude" step="any" class="regular-text">
                                    <button type="button" id="geocode-btn" class="button button-secondary">
                                        <?php _e('Get Coordinates', 'softworld-ai-seo'); ?>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="location-longitude"><?php _e('Longitude', 'softworld-ai-seo'); ?></label></th>
                                <td><input type="number" id="location-longitude" name="longitude" step="any" class="regular-text"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Opening Hours Tab -->
                    <div id="hours-tab" class="softworld-tab-content">
                        <p><?php _e('Set your business opening hours for each day of the week:', 'softworld-ai-seo'); ?></p>
                        <div class="opening-hours-container">
                            <?php
                            $days = array(
                                'monday' => __('Monday', 'softworld-ai-seo'),
                                'tuesday' => __('Tuesday', 'softworld-ai-seo'),
                                'wednesday' => __('Wednesday', 'softworld-ai-seo'),
                                'thursday' => __('Thursday', 'softworld-ai-seo'),
                                'friday' => __('Friday', 'softworld-ai-seo'),
                                'saturday' => __('Saturday', 'softworld-ai-seo'),
                                'sunday' => __('Sunday', 'softworld-ai-seo')
                            );
                            
                            foreach ($days as $day_key => $day_name):
                            ?>
                                <div class="opening-hours-row">
                                    <div class="day-name">
                                        <label><?php echo esc_html($day_name); ?></label>
                                    </div>
                                    <div class="hours-controls">
                                        <label>
                                            <input type="checkbox" name="opening_hours[<?php echo $day_key; ?>][closed]" 
                                                   class="closed-checkbox" data-day="<?php echo $day_key; ?>">
                                            <?php _e('Closed', 'softworld-ai-seo'); ?>
                                        </label>
                                        <div class="time-inputs">
                                            <input type="time" name="opening_hours[<?php echo $day_key; ?>][open]" 
                                                   class="open-time" data-day="<?php echo $day_key; ?>">
                                            <span> - </span>
                                            <input type="time" name="opening_hours[<?php echo $day_key; ?>][close]" 
                                                   class="close-time" data-day="<?php echo $day_key; ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div id="advanced-tab" class="softworld-tab-content">
                        <table class="form-table">
                            <tr>
                                <th><label for="price-range"><?php _e('Price Range', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <select id="price-range" name="price_range" class="regular-text">
                                        <option value="">Not specified</option>
                                        <option value="$">$ - Inexpensive</option>
                                        <option value="$">$ - Moderate</option>
                                        <option value="$$">$$ - Expensive</option>
                                        <option value="$$">$$ - Very Expensive</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="accepts-reservations"><?php _e('Accepts Reservations', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="accepts-reservations" name="accepts_reservations" value="1">
                                        <?php _e('This business accepts reservations', 'softworld-ai-seo'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="serves-cuisine"><?php _e('Cuisine Types', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <input type="text" id="serves-cuisine" name="serves_cuisine" class="regular-text"
                                           placeholder="<?php _e('e.g., Italian, Mexican, American (comma-separated)', 'softworld-ai-seo'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="payment-accepted"><?php _e('Payment Methods', 'softworld-ai-seo'); ?></label></th>
                                <td>
                                    <input type="text" id="payment-accepted" name="payment_accepted" class="regular-text"
                                           placeholder="<?php _e('e.g., Cash, Credit Card, PayPal (comma-separated)', 'softworld-ai-seo'); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var locationModal = $('#location-modal');
            var isEditMode = false;
            
            // Initialize modal
            locationModal.dialog({
                autoOpen: false,
                modal: true,
                width: 800,
                height: 600,
                resizable: true,
                draggable: true,
                buttons: {
                    "<?php _e('Save', 'softworld-ai-seo'); ?>": function() {
                        saveLocation();
                    },
                    "<?php _e('Cancel', 'softworld-ai-seo'); ?>": function() {
                        $(this).dialog('close');
                    }
                },
                close: function() {
                    resetForm();
                }
            });
            
            // Tab functionality
            $('.softworld-tab-nav a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.softworld-tab-nav li').removeClass('active');
                $(this).parent().addClass('active');
                
                $('.softworld-tab-content').removeClass('active');
                $(target).addClass('active');
            });
            
            // Opening hours toggle
            $('.closed-checkbox').on('change', function() {
                var day = $(this).data('day');
                var timeInputs = $('.time-inputs input[data-day="' + day + '"]');
                
                if ($(this).is(':checked')) {
                    timeInputs.prop('disabled', true);
                } else {
                    timeInputs.prop('disabled', false);
                }
            });
            
            // Add location button
            $('#add-location-btn').on('click', function() {
                isEditMode = false;
                locationModal.dialog('option', 'title', '<?php _e('Add New Location', 'softworld-ai-seo'); ?>');
                locationModal.dialog('open');
            });
            
            // Edit location button
            $(document).on('click', '.edit-location-btn', function() {
                var locationId = $(this).data('location-id');
                isEditMode = true;
                loadLocation(locationId);
                locationModal.dialog('option', 'title', '<?php _e('Edit Location', 'softworld-ai-seo'); ?>');
                locationModal.dialog('open');
            });
            
            // Delete location button
            $(document).on('click', '.delete-location-btn', function() {
                var locationId = $(this).data('location-id');
                
                if (confirm('<?php _e('Are you sure you want to delete this location?', 'softworld-ai-seo'); ?>')) {
                    deleteLocation(locationId);
                }
            });
            
            // Geocoding
            $('#geocode-btn').on('click', function() {
                var address = $('#location-address').val();
                var city = $('#location-city').val();
                var state = $('#location-state').val();
                var country = $('#location-country').val();
                
                var fullAddress = [address, city, state, country].filter(Boolean).join(', ');
                
                if (!fullAddress) {
                    alert('<?php _e('Please enter address information first.', 'softworld-ai-seo'); ?>');
                    return;
                }
                
                geocodeAddress(fullAddress);
            });
            
            function loadLocation(locationId) {
                $.post(ajaxurl, {
                    action: 'softworld_get_location',
                    location_id: locationId,
                    nonce: '<?php echo wp_create_nonce('softworld_local_seo_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        var location = response.data;
                        
                        $('#location-id').val(location.id);
                        $('#location-name').val(location.name);
                        $('#location-address').val(location.address);
                        $('#location-city').val(location.city);
                        $('#location-state').val(location.state);
                        $('#location-postal').val(location.postal_code);
                        $('#location-country').val(location.country);
                        $('#location-phone').val(location.phone);
                        $('#location-email').val(location.email);
                        $('#location-website').val(location.website);
                        $('#location-latitude').val(location.latitude);
                        $('#location-longitude').val(location.longitude);
                        $('#business-type').val(location.business_type);
                        $('#location-description').val(location.description);
                        $('#location-image').val(location.image_url);
                        $('#price-range').val(location.price_range);
                        $('#accepts-reservations').prop('checked', location.accepts_reservations == 1);
                        $('#serves-cuisine').val(location.serves_cuisine);
                        $('#payment-accepted').val(location.payment_accepted);
                        $('#is-primary').prop('checked', location.is_primary == 1);
                        
                        // Load opening hours
                        if (location.opening_hours) {
                            $.each(location.opening_hours, function(day, hours) {
                                var closedCheckbox = $('input[name="opening_hours[' + day + '][closed]"]');
                                var openTime = $('input[name="opening_hours[' + day + '][open]"]');
                                var closeTime = $('input[name="opening_hours[' + day + '][close]"]');
                                
                                if (hours.closed) {
                                    closedCheckbox.prop('checked', true);
                                    openTime.prop('disabled', true);
                                    closeTime.prop('disabled', true);
                                } else {
                                    openTime.val(hours.open || '');
                                    closeTime.val(hours.close || '');
                                }
                            });
                        }
                    } else {
                        alert(response.data);
                    }
                });
            }
            
            function saveLocation() {
                var formData = new FormData(document.getElementById('location-form'));
                formData.append('action', isEditMode ? 'softworld_update_location' : 'softworld_add_location');
                formData.append('nonce', '<?php echo wp_create_nonce('softworld_local_seo_nonce'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || response.data);
                            locationModal.dialog('close');
                            location.reload(); // Reload page to show changes
                        } else {
                            alert(response.data);
                        }
                    }
                });
            }
            
            function deleteLocation(locationId) {
                $.post(ajaxurl, {
                    action: 'softworld_delete_location',
                    location_id: locationId,
                    nonce: '<?php echo wp_create_nonce('softworld_local_seo_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            }
            
            function geocodeAddress(address) {
                if (typeof google === 'undefined') {
                    alert('<?php _e('Google Maps API not loaded. Please check your API key.', 'softworld-ai-seo'); ?>');
                    return;
                }
                
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({address: address}, function(results, status) {
                    if (status === 'OK') {
                        var location = results[0].geometry.location;
                        $('#location-latitude').val(location.lat());
                        $('#location-longitude').val(location.lng());
                        alert('<?php _e('Coordinates found and updated!', 'softworld-ai-seo'); ?>');
                    } else {
                        alert('<?php _e('Geocoding failed: ', 'softworld-ai-seo'); ?>' + status);
                    }
                });
            }
            
            function resetForm() {
                document.getElementById('location-form').reset();
                $('.closed-checkbox').prop('checked', false);
                $('.time-inputs input').prop('disabled', false);
                isEditMode = false;
            }
        });
        </script>
        <?php
    }
}