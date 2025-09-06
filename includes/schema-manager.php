<?php
/**
 * Enhanced Schema Manager Class
 * 
 * Handles all schema markup functionality including templates and testing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Schema_Manager
{
    private $plugin_name;

    public function __construct($plugin_name = 'softworld-ai-seo')
    {
        $this->plugin_name = $plugin_name;

        // Hook into WordPress
        add_action('wp_head', array($this, 'output_schema_markup'), 5);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta_box'));

        // Initialize settings
        add_action('admin_init', array($this, 'register_schema_settings'));

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // AJAX handlers for custom schema
        add_action('wp_ajax_save_custom_schema', array($this, 'ajax_save_custom_schema'));
        add_action('wp_ajax_delete_custom_schema', array($this, 'ajax_delete_custom_schema'));
        add_action('wp_ajax_get_custom_schema', array($this, 'ajax_get_custom_schema'));

        // AJAX handlers for templates
        add_action('wp_ajax_save_schema_template', array($this, 'ajax_save_schema_template'));
        add_action('wp_ajax_delete_schema_template', array($this, 'ajax_delete_schema_template'));
        add_action('wp_ajax_get_schema_template', array($this, 'ajax_get_schema_template'));
        add_action('wp_ajax_apply_schema_template', array($this, 'ajax_apply_schema_template'));

        // AJAX handlers for testing and additional functionality
        add_action('wp_ajax_test_schema', array($this, 'ajax_test_schema'));
        add_action('wp_ajax_get_all_templates', array($this, 'ajax_get_all_templates'));
        add_action('wp_ajax_import_templates', array($this, 'ajax_import_templates'));
        add_action('wp_ajax_test_url_schema', array($this, 'ajax_test_url_schema'));
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        // Check if we're on the schema settings page
        $is_schema_page = strpos($hook, 'softworld-ai-seo') !== false ||
            strpos($hook, 'softworld_schema') !== false ||
            (isset($_GET['page']) && strpos($_GET['page'], 'softworld') !== false);

        if ($is_schema_page || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_media();
            wp_enqueue_script('softworld-schema-admin', plugin_dir_url(__FILE__) . '../assets/js/schema-admin.js', array('jquery'), '1.0.0', true);

            // Localize the script with data
            wp_localize_script('softworld-schema-admin', 'softworldSchema', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('softworld_schema_nonce')
            ));
        }
    }

    /**
     * Register schema settings
     */
    public function register_schema_settings()
    {
        // Global Schema Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_global_settings', array(
            'sanitize_callback' => array($this, 'sanitize_global_settings')
        ));

        // Knowledge Graph Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_knowledge_graph', array(
            'sanitize_callback' => array($this, 'sanitize_knowledge_graph')
        ));

        // Schema Types Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_types_settings', array(
            'sanitize_callback' => array($this, 'sanitize_schema_types_settings')
        ));

        // Custom Schema Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_custom_types', array(
            'sanitize_callback' => array($this, 'sanitize_custom_schema')
        ));

        // Schema Templates
        register_setting('softworld_schema_settings_group', 'softworld_schema_templates', array(
            'sanitize_callback' => array($this, 'sanitize_schema_templates')
        ));
    }

    /**
     * Sanitize schema templates
     */
    public function sanitize_schema_templates($input)
    {
        $sanitized = array();

        if (!is_array($input)) {
            return $sanitized;
        }

        foreach ($input as $key => $template) {
            $sanitized_template = array();

            $sanitized_template['name'] = sanitize_text_field($template['name']);
            $sanitized_template['description'] = sanitize_textarea_field($template['description']);
            $sanitized_template['schema_type'] = sanitize_text_field($template['schema_type']);
            $sanitized_template['category'] = sanitize_text_field($template['category']);

            // Sanitize template data
            if (isset($template['template_data']) && is_array($template['template_data'])) {
                $sanitized_template['template_data'] = $this->sanitize_template_data($template['template_data']);
            }

            $sanitized[sanitize_key($key)] = $sanitized_template;
        }

        return $sanitized;
    }

    public function ajax_get_schema_template()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $template_id = isset($_POST['id']) ? sanitize_key($_POST['id']) : '';

        if (empty($template_id)) {
            wp_send_json_error('Template ID is required');
        }

        $templates = get_option('softworld_schema_templates', array());

        if (!isset($templates[$template_id])) {
            wp_send_json_error('Template not found');
        }

        wp_send_json_success($templates[$template_id]);
    }

    /**
     * AJAX handler to delete schema template
     */
    public function ajax_delete_schema_template()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $template_id = isset($_POST['id']) ? sanitize_key($_POST['id']) : '';

        if (empty($template_id)) {
            wp_send_json_error('Template ID is required');
        }

        $templates = get_option('softworld_schema_templates', array());

        if (!isset($templates[$template_id])) {
            wp_send_json_error('Template not found');
        }

        unset($templates[$template_id]);

        $result = update_option('softworld_schema_templates', $templates);

        if ($result || empty($templates)) {
            wp_send_json_success('Template deleted successfully');
        } else {
            wp_send_json_error('Failed to delete template');
        }
    }

    /**
     * AJAX handler to apply schema template
     */
    public function ajax_apply_schema_template()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $template_id = isset($_POST['template_id']) ? sanitize_key($_POST['template_id']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($template_id) || empty($post_id)) {
            wp_send_json_error('Template ID and Post ID are required');
        }

        $templates = get_option('softworld_schema_templates', array());

        if (!isset($templates[$template_id])) {
            wp_send_json_error('Template not found');
        }

        $template = $templates[$template_id];

        // Apply template to post
        update_post_meta($post_id, '_softworld_schema_type', $template['schema_type']);

        if (!empty($template['template_data'])) {
            update_post_meta($post_id, '_softworld_schema_data', $template['template_data']);
        }

        wp_send_json_success('Template applied successfully');
    }

    /**
     * AJAX handler to get all templates for export
     */
    public function ajax_get_all_templates()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $templates = get_option('softworld_schema_templates', array());
        wp_send_json_success($templates);
    }

    /**
     * AJAX handler to import templates
     */
    public function ajax_import_templates()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $template_data = isset($_POST['template_data']) ? $_POST['template_data'] : '';

        if (empty($template_data)) {
            wp_send_json_error('Template data is required');
        }

        $imported_templates = json_decode(stripslashes($template_data), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON data');
        }

        if (!is_array($imported_templates)) {
            wp_send_json_error('Template data must be an array');
        }

        $existing_templates = get_option('softworld_schema_templates', array());

        // Merge templates
        $merged_templates = array_merge($existing_templates, $imported_templates);

        $result = update_option('softworld_schema_templates', $merged_templates);

        if ($result) {
            wp_send_json_success('Templates imported successfully');
        } else {
            wp_send_json_error('Failed to import templates');
        }
    }

    /**
     * AJAX handler to test URL schema
     */
    public function ajax_test_url_schema()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

        if (empty($url)) {
            wp_send_json_error('URL is required');
        }

        // Simple validation to ensure it's a URL from the same domain
        $site_url = site_url();
        if (strpos($url, $site_url) !== 0) {
            wp_send_json_error('Only URLs from your own website can be tested');
        }

        // Get the page content
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'WordPress Schema Tester'
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to fetch URL: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        // Extract JSON-LD schema markup
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $body, $matches);

        if (empty($matches[1])) {
            wp_send_json_success('<div style="color: orange;">No JSON-LD schema markup found on this page.</div>');
        }

        $output = '<h4>Found Schema Markup:</h4>';
        foreach ($matches[1] as $index => $json) {
            $decoded = json_decode(trim($json), true);
            if ($decoded) {
                $output .= '<div style="margin-bottom: 15px;">';
                $output .= '<h5>Schema Block ' . ($index + 1) . ':</h5>';
                $output .= '<pre style="background: #f5f5f5; padding: 10px; font-size: 12px; max-height: 200px; overflow-y: auto;">';
                $output .= esc_html(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $output .= '</pre>';
                $output .= '</div>';
            }
        }

        wp_send_json_success($output);
    }

    /**
     * AJAX handler to test schema
     */
    public function ajax_test_schema()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $schema_type = isset($_POST['schema_type']) ? sanitize_text_field($_POST['schema_type']) : '';
        $schema_data = isset($_POST['schema_data']) ? $_POST['schema_data'] : array();
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($schema_type)) {
            wp_send_json_error('Schema type is required');
        }

        // Generate schema for testing
        $schema = $this->generate_test_schema($schema_type, $schema_data, $post_id);

        if (empty($schema)) {
            wp_send_json_error('Failed to generate schema');
        }

        // Basic validation
        if (!isset($schema['@type']) || !isset($schema['@context'])) {
            wp_send_json_error('Invalid schema structure');
        }

        wp_send_json_success($schema);
    }

    /**
     * AJAX handler to get custom schema
     */
    public function ajax_get_custom_schema()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $schema_id = isset($_POST['id']) ? sanitize_key($_POST['id']) : '';

        if (empty($schema_id)) {
            wp_send_json_error('Schema ID is required');
        }

        $custom_schemas = get_option('softworld_schema_custom_types', array());

        if (!isset($custom_schemas[$schema_id])) {
            wp_send_json_error('Custom schema not found');
        }

        wp_send_json_success($custom_schemas[$schema_id]);
    }

    /**
     * AJAX handler to delete custom schema
     */
    public function ajax_delete_custom_schema()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $schema_id = isset($_POST['id']) ? sanitize_key($_POST['id']) : '';

        if (empty($schema_id)) {
            wp_send_json_error('Schema ID is required');
        }

        $custom_schemas = get_option('softworld_schema_custom_types', array());

        if (!isset($custom_schemas[$schema_id])) {
            wp_send_json_error('Custom schema not found');
        }

        unset($custom_schemas[$schema_id]);

        $result = update_option('softworld_schema_custom_types', $custom_schemas);

        if ($result || empty($custom_schemas)) {
            wp_send_json_success('Custom schema deleted successfully');
        } else {
            wp_send_json_error('Failed to delete custom schema');
        }
    }

    /**
     * Generate test schema
     */
    private function generate_test_schema($schema_type, $schema_data, $post_id)
    {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type
        );

        // Get post data if available
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                $schema['name'] = get_the_title($post);
                $schema['description'] = get_the_excerpt($post);
                $schema['url'] = get_permalink($post);
            }
        }

        // Add specific schema properties based on type
        switch ($schema_type) {
            case 'Product':
                if (!empty($schema_data['price'])) {
                    $schema['offers'] = array(
                        '@type' => 'Offer',
                        'price' => $schema_data['price'],
                        'priceCurrency' => !empty($schema_data['currency']) ? $schema_data['currency'] : 'USD',
                        'availability' => 'https://schema.org/' . (!empty($schema_data['availability']) ? $schema_data['availability'] : 'InStock')
                    );
                }
                break;

            case 'Review':
                if (!empty($schema_data['rating'])) {
                    $schema['reviewRating'] = array(
                        '@type' => 'Rating',
                        'ratingValue' => $schema_data['rating'],
                        'bestRating' => '5'
                    );
                }
                if (!empty($schema_data['item_name'])) {
                    $schema['itemReviewed'] = array(
                        '@type' => 'Thing',
                        'name' => $schema_data['item_name']
                    );
                }
                break;

            case 'Recipe':
                if (!empty($schema_data['prep_time'])) {
                    $schema['prepTime'] = 'PT' . intval($schema_data['prep_time']) . 'M';
                }
                if (!empty($schema_data['cook_time'])) {
                    $schema['cookTime'] = 'PT' . intval($schema_data['cook_time']) . 'M';
                }
                if (!empty($schema_data['servings'])) {
                    $schema['recipeYield'] = intval($schema_data['servings']) . ' servings';
                }
                break;
        }

        return $schema;
    }

    /**
     * Sanitize template data recursively
     */
    private function sanitize_template_data($data)
    {
        $sanitized = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[sanitize_key($key)] = $this->sanitize_template_data($value);
            } else {
                $sanitized[sanitize_key($key)] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * AJAX handler to save custom schema - FIXED VERSION
     */
    public function ajax_save_custom_schema()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Check if schema data is provided
        if (!isset($_POST['schema'])) {
            wp_send_json_error('No schema data provided');
        }

        $schema_data = $_POST['schema'];
        $schema_id = isset($_POST['id']) ? sanitize_key($_POST['id']) : 'schema_' . time();

        // Get existing custom schemas
        $custom_schemas = get_option('softworld_schema_custom_types', array());

        // Validate required fields
        if (empty($schema_data['name']) || empty($schema_data['type'])) {
            wp_send_json_error('Schema name and type are required');
        }

        // Sanitize the schema data
        $sanitized_schema = array(
            'name' => sanitize_text_field($schema_data['name']),
            'type' => sanitize_text_field($schema_data['type']),
            'description' => isset($schema_data['description']) ? sanitize_textarea_field($schema_data['description']) : '',
            'properties' => array()
        );

        // Add properties if provided
        if (!empty($schema_data['properties']) && is_array($schema_data['properties'])) {
            foreach ($schema_data['properties'] as $property) {
                if (!empty($property['name'])) {
                    $prop_id = sanitize_key($property['name']);
                    $sanitized_schema['properties'][$prop_id] = array(
                        'name' => sanitize_text_field($property['name']),
                        'type' => isset($property['type']) ? sanitize_text_field($property['type']) : 'text',
                        'required' => !empty($property['required']),
                        'description' => isset($property['description']) ? sanitize_textarea_field($property['description']) : ''
                    );
                }
            }
        }

        // Add or update the schema
        $custom_schemas[$schema_id] = $sanitized_schema;

        // Save the updated schemas
        $result = update_option('softworld_schema_custom_types', $custom_schemas);

        if ($result) {
            wp_send_json_success('Custom schema saved successfully');
        } else {
            // Check if the value was actually the same (no change)
            if ($custom_schemas === get_option('softworld_schema_custom_types')) {
                wp_send_json_success('Custom schema saved successfully (no changes detected)');
            } else {
                wp_send_json_error('Failed to save custom schema');
            }
        }
    }

    /**
     * AJAX handler to save schema template - UPDATED VERSION
     */
    public function ajax_save_schema_template()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_schema_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Get template data from POST
        $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
        $template_description = isset($_POST['template_description']) ? sanitize_textarea_field($_POST['template_description']) : '';
        $template_category = isset($_POST['template_category']) ? sanitize_text_field($_POST['template_category']) : 'general';
        $schema_type = isset($_POST['schema_type']) ? sanitize_text_field($_POST['schema_type']) : '';
        $schema_data = isset($_POST['schema_data']) ? $_POST['schema_data'] : array();
        
        // Check if this is an edit operation (template_id is provided)
        $template_id = isset($_POST['id']) ? sanitize_key($_POST['id']) : '';

        // Validation
        if (empty($template_name)) {
            wp_send_json_error('Template name is required');
        }

        if (empty($schema_type)) {
            wp_send_json_error('Schema type is required');
        }

        // Sanitize schema data
        $sanitized_schema_data = $this->sanitize_template_data($schema_data);

        // Create template array
        $template = array(
            'name' => $template_name,
            'description' => $template_description,
            'schema_type' => $schema_type,
            'category' => $template_category,
            'template_data' => $sanitized_schema_data
        );

        // Get existing templates
        $templates = get_option('softworld_schema_templates', array());

        // If template_id is provided and exists, update it; otherwise create new one
        if (!empty($template_id) && isset($templates[$template_id])) {
            // Update existing template
            $templates[$template_id] = $template;
            $action_message = 'Template updated successfully';
        } else {
            // Create new template with unique ID
            $template_id = 'template_' . sanitize_key($template_name) . '_' . time();
            $templates[$template_id] = $template;
            $action_message = 'Template created successfully';
        }

        // Save templates
        $result = update_option('softworld_schema_templates', $templates);

        if ($result) {
            wp_send_json_success($action_message);
        } else {
            // Check if the value was actually the same (no change)
            if ($templates === get_option('softworld_schema_templates')) {
                wp_send_json_success($action_message . ' (no changes detected)');
            } else {
                wp_send_json_error('Failed to save template');
            }
        }
    }

    /**
     * Sanitize global settings
     */
    public function sanitize_global_settings($input)
    {
        $sanitized = array();

        if (isset($input['enable_schema'])) {
            $sanitized['enable_schema'] = (bool) $input['enable_schema'];
        }

        if (isset($input['default_article_type'])) {
            $sanitized['default_article_type'] = sanitize_text_field($input['default_article_type']);
        }

        if (isset($input['breadcrumb_schema'])) {
            $sanitized['breadcrumb_schema'] = (bool) $input['breadcrumb_schema'];
        }

        if (isset($input['sitelinks_searchbox'])) {
            $sanitized['sitelinks_searchbox'] = (bool) $input['sitelinks_searchbox'];
        }

        // Sanitize schema post types
        if (isset($input['schema_post_types'])) {
            $sanitized['schema_post_types'] = array();
            $post_types = get_post_types(array('public' => true), 'names');

            foreach ($input['schema_post_types'] as $post_type) {
                if (array_key_exists($post_type, $post_types)) {
                    $sanitized['schema_post_types'][] = sanitize_text_field($post_type);
                }
            }
        }

        // Sanitize default page schema
        if (isset($input['default_page_schema'])) {
            $allowed_page_schemas = array('WebPage', 'AboutPage', 'ContactPage', 'FAQPage');
            if (in_array($input['default_page_schema'], $allowed_page_schemas)) {
                $sanitized['default_page_schema'] = sanitize_text_field($input['default_page_schema']);
            }
        }

        // Sanitize custom schema enable
        if (isset($input['enable_custom_schema'])) {
            $sanitized['enable_custom_schema'] = (bool) $input['enable_custom_schema'];
        }

        return $sanitized;
    }

    /**
     * Sanitize knowledge graph settings
     */
    public function sanitize_knowledge_graph($input)
    {
        $sanitized = array();

        if (isset($input['entity_type'])) {
            $sanitized['entity_type'] = sanitize_text_field($input['entity_type']);
        }

        if (isset($input['organization_name'])) {
            $sanitized['organization_name'] = sanitize_text_field($input['organization_name']);
        }

        if (isset($input['organization_logo'])) {
            $sanitized['organization_logo'] = esc_url_raw($input['organization_logo']);
        }

        if (isset($input['person_name'])) {
            $sanitized['person_name'] = sanitize_text_field($input['person_name']);
        }

        if (isset($input['person_image'])) {
            $sanitized['person_image'] = esc_url_raw($input['person_image']);
        }

        if (isset($input['social_profiles'])) {
            $sanitized['social_profiles'] = array();
            foreach ($input['social_profiles'] as $profile) {
                if (!empty($profile)) {
                    $sanitized['social_profiles'][] = esc_url_raw($profile);
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize schema types settings
     */
    public function sanitize_schema_types_settings($input)
    {
        $sanitized = array();

        if (isset($input['enable_product_schema'])) {
            $sanitized['enable_product_schema'] = (bool) $input['enable_product_schema'];
        }

        if (isset($input['enable_faq_schema'])) {
            $sanitized['enable_faq_schema'] = (bool) $input['enable_faq_schema'];
        }

        if (isset($input['enable_howto_schema'])) {
            $sanitized['enable_howto_schema'] = (bool) $input['enable_howto_schema'];
        }

        return $sanitized;
    }

    /**
     * Sanitize custom schema settings
     */
    public function sanitize_custom_schema($input)
    {
        $sanitized = array();

        if (!is_array($input)) {
            return $sanitized;
        }

        foreach ($input as $key => $schema) {
            $sanitized_schema = array();

            // Sanitize basic fields
            $sanitized_schema['name'] = sanitize_text_field($schema['name']);
            $sanitized_schema['type'] = sanitize_text_field($schema['type']);
            $sanitized_schema['description'] = sanitize_textarea_field($schema['description']);

            // Sanitize properties
            if (isset($schema['properties']) && is_array($schema['properties'])) {
                $sanitized_properties = array();

                foreach ($schema['properties'] as $prop_key => $property) {
                    $sanitized_property = array(
                        'name' => sanitize_text_field($property['name']),
                        'type' => sanitize_text_field($property['type']),
                        'required' => !empty($property['required']),
                        'description' => sanitize_textarea_field($property['description'])
                    );

                    $sanitized_properties[sanitize_key($prop_key)] = $sanitized_property;
                }

                $sanitized_schema['properties'] = $sanitized_properties;
            }

            $sanitized[sanitize_key($key)] = $sanitized_schema;
        }

        return $sanitized;
    }

    /**
     * Add schema meta box to post/page editor
     */
    public function add_schema_meta_box()
    {
        $global_settings = get_option('softworld_schema_global_settings', array());
        $selected_post_types = isset($global_settings['schema_post_types']) ? $global_settings['schema_post_types'] : array('post', 'page');

        foreach ($selected_post_types as $post_type) {
            add_meta_box(
                'softworld_schema_meta_box',
                'Schema Settings',
                array($this, 'render_schema_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render schema meta box with template support
     */
    public function render_schema_meta_box($post)
    {
        wp_nonce_field('softworld_schema_meta_box', 'softworld_schema_nonce');

        $schema_type = get_post_meta($post->ID, '_softworld_schema_type', true);
        $schema_data = get_post_meta($post->ID, '_softworld_schema_data', true);

        if (!$schema_data) {
            $schema_data = array();
        }

        // Get schema types settings
        $schema_types_settings = get_option('softworld_schema_types_settings', array());

        // Get custom schema types
        $custom_schema_types = get_option('softworld_schema_custom_types', array());

        // Get schema templates
        $schema_templates = get_option('softworld_schema_templates', array());

        ?>
        <div class="softworld-schema-meta-box">
            <!-- Template Selection -->
            <div class="schema-template-section"
                style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                <p><strong>Quick Templates:</strong></p>
                <select id="schema_template_select" style="width: 100%; margin-bottom: 10px;">
                    <option value="">Select a template...</option>
                    <?php foreach ($schema_templates as $template_id => $template): ?>
                        <option value="<?php echo esc_attr($template_id); ?>"
                            data-schema-type="<?php echo esc_attr($template['schema_type']); ?>">
                            <?php echo esc_html($template['name']); ?> (<?php echo esc_html($template['schema_type']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="display: flex; gap: 5px;">
                    <button type="button" class="button button-small" id="apply_template_btn" disabled>Apply Template</button>
                    <button type="button" class="button button-small" id="save_as_template_btn">Save as Template</button>
                </div>
            </div>

            <p>
                <label for="softworld_schema_type"><strong>Schema Type:</strong></label>
                <select id="softworld_schema_type" name="softworld_schema_type" style="width: 100%;">
                    <option value="">None</option>
                    <option value="Article" <?php selected($schema_type, 'Article'); ?>>Article</option>

                    <?php if (!empty($schema_types_settings['enable_product_schema'])): ?>
                        <option value="Product" <?php selected($schema_type, 'Product'); ?>>Product</option>
                    <?php endif; ?>

                    <?php if (!empty($schema_types_settings['enable_faq_schema'])): ?>
                        <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQ</option>
                    <?php endif; ?>

                    <?php if (!empty($schema_types_settings['enable_howto_schema'])): ?>
                        <option value="HowTo" <?php selected($schema_type, 'HowTo'); ?>>How-To</option>
                    <?php endif; ?>

                    <option value="Review" <?php selected($schema_type, 'Review'); ?>>Review</option>
                    <option value="Recipe" <?php selected($schema_type, 'Recipe'); ?>>Recipe</option>

                    <!-- Custom Schema Types -->
                    <?php foreach ($custom_schema_types as $key => $custom_schema): ?>
                        <option value="<?php echo esc_attr($custom_schema['type']); ?>" <?php selected($schema_type, $custom_schema['type']); ?>>
                            <?php echo esc_html($custom_schema['name']); ?> (Custom)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Schema Testing -->
            <div class="schema-testing-section"
                style="margin: 15px 0; padding: 10px; border: 1px solid #ddd; background: #f0f8ff;">
                <p><strong>Schema Testing:</strong></p>
                <button type="button" class="button button-secondary" id="test_schema_btn" style="margin-bottom: 10px;">Test
                    Current Schema</button>
                <div id="schema_test_results" style="display: none;"></div>
            </div>

            <div id="schema_additional_fields" style="<?php echo empty($schema_type) ? 'display:none;' : ''; ?>">
                <!-- Product Schema Fields -->
                <div class="schema-fields product-fields"
                    style="<?php echo $schema_type !== 'Product' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="product_price"><strong>Price:</strong></label>
                        <input type="text" id="product_price" name="schema_data[price]"
                            value="<?php echo esc_attr(isset($schema_data['price']) ? $schema_data['price'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                    <p>
                        <label for="product_currency"><strong>Currency:</strong></label>
                        <input type="text" id="product_currency" name="schema_data[currency]"
                            value="<?php echo esc_attr(isset($schema_data['currency']) ? $schema_data['currency'] : 'USD'); ?>"
                            style="width: 100%;">
                    </p>
                    <p>
                        <label for="product_availability"><strong>Availability:</strong></label>
                        <select id="product_availability" name="schema_data[availability]" style="width: 100%;">
                            <option value="InStock" <?php selected(isset($schema_data['availability']) ? $schema_data['availability'] : '', 'InStock'); ?>>In Stock</option>
                            <option value="OutOfStock" <?php selected(isset($schema_data['availability']) ? $schema_data['availability'] : '', 'OutOfStock'); ?>>Out of Stock</option>
                            <option value="PreOrder" <?php selected(isset($schema_data['availability']) ? $schema_data['availability'] : '', 'PreOrder'); ?>>Pre Order</option>
                        </select>
                    </p>
                </div>

                <!-- FAQ Schema Fields -->
                <div class="schema-fields faq-fields" style="<?php echo $schema_type !== 'FAQPage' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="faq_questions"><strong>FAQ Questions (one per line):</strong></label>
                        <textarea id="faq_questions" name="schema_data[faq_questions]"
                            style="width: 100%; height: 100px;"><?php echo esc_textarea(isset($schema_data['faq_questions']) ? $schema_data['faq_questions'] : ''); ?></textarea>
                    </p>
                </div>

                <!-- How-To Schema Fields -->
                <div class="schema-fields howto-fields" style="<?php echo $schema_type !== 'HowTo' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="howto_steps"><strong>Steps (one per line):</strong></label>
                        <textarea id="howto_steps" name="schema_data[howto_steps]"
                            style="width: 100%; height: 100px;"><?php echo esc_textarea(isset($schema_data['howto_steps']) ? $schema_data['howto_steps'] : ''); ?></textarea>
                    </p>
                    <p>
                        <label for="howto_supplies"><strong>Supplies (comma separated):</strong></label>
                        <input type="text" id="howto_supplies" name="schema_data[howto_supplies]"
                            value="<?php echo esc_attr(isset($schema_data['howto_supplies']) ? $schema_data['howto_supplies'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                </div>

                <!-- Review Schema Fields -->
                <div class="schema-fields review-fields"
                    style="<?php echo $schema_type !== 'Review' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="review_rating"><strong>Rating (1-5):</strong></label>
                        <input type="number" id="review_rating" name="schema_data[rating]" min="1" max="5" step="0.1"
                            value="<?php echo esc_attr(isset($schema_data['rating']) ? $schema_data['rating'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                    <p>
                        <label for="review_item_name"><strong>Item Being Reviewed:</strong></label>
                        <input type="text" id="review_item_name" name="schema_data[item_name]"
                            value="<?php echo esc_attr(isset($schema_data['item_name']) ? $schema_data['item_name'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                </div>

                <!-- Recipe Schema Fields -->
                <div class="schema-fields recipe-fields"
                    style="<?php echo $schema_type !== 'Recipe' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="recipe_prep_time"><strong>Prep Time (minutes):</strong></label>
                        <input type="number" id="recipe_prep_time" name="schema_data[prep_time]"
                            value="<?php echo esc_attr(isset($schema_data['prep_time']) ? $schema_data['prep_time'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                    <p>
                        <label for="recipe_cook_time"><strong>Cook Time (minutes):</strong></label>
                        <input type="number" id="recipe_cook_time" name="schema_data[cook_time]"
                            value="<?php echo esc_attr(isset($schema_data['cook_time']) ? $schema_data['cook_time'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                    <p>
                        <label for="recipe_servings"><strong>Servings:</strong></label>
                        <input type="number" id="recipe_servings" name="schema_data[servings]"
                            value="<?php echo esc_attr(isset($schema_data['servings']) ? $schema_data['servings'] : ''); ?>"
                            style="width: 100%;">
                    </p>
                </div>

                <!-- Custom Schema Fields -->
                <?php foreach ($custom_schema_types as $key => $custom_schema): ?>
                    <div class="schema-fields custom-schema-fields custom-schema-<?php echo esc_attr($key); ?>"
                        style="<?php echo $schema_type !== $custom_schema['type'] ? 'display:none;' : ''; ?>">
                        <h4><?php echo esc_html($custom_schema['name']); ?> Properties</h4>
                        <p class="description"><?php echo esc_html($custom_schema['description']); ?></p>

                        <?php if (!empty($custom_schema['properties'])): ?>
                            <?php foreach ($custom_schema['properties'] as $prop_key => $property): ?>
                                <p>
                                    <label for="custom_<?php echo esc_attr($key); ?>_<?php echo esc_attr($prop_key); ?>">
                                        <strong><?php echo esc_html($property['name']); ?>:</strong>
                                        <?php if (!empty($property['required'])): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if ($property['type'] === 'textarea'): ?>
                                        <textarea id="custom_<?php echo esc_attr($key); ?>_<?php echo esc_attr($prop_key); ?>"
                                            name="schema_data[custom][<?php echo esc_attr($key); ?>][<?php echo esc_attr($prop_key); ?>]"
                                            style="width: 100%; height: 60px;" <?php echo !empty($property['required']) ? 'required' : ''; ?>><?php echo esc_textarea(isset($schema_data['custom'][$key][$prop_key]) ? $schema_data['custom'][$key][$prop_key] : ''); ?></textarea>
                                    <?php else: ?>
                                        <input type="<?php echo esc_attr($property['type']); ?>"
                                            id="custom_<?php echo esc_attr($key); ?>_<?php echo esc_attr($prop_key); ?>"
                                            name="schema_data[custom][<?php echo esc_attr($key); ?>][<?php echo esc_attr($prop_key); ?>]"
                                            value="<?php echo esc_attr(isset($schema_data['custom'][$key][$prop_key]) ? $schema_data['custom'][$key][$prop_key] : ''); ?>"
                                            style="width: 100%;" <?php echo !empty($property['required']) ? 'required' : ''; ?>>
                                    <?php endif; ?>

                                    <?php if (!empty($property['description'])): ?>
                                        <span class="description"><?php echo esc_html($property['description']); ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Template Save Modal -->
        <div id="template-save-modal" style="display: none;">
            <div
                style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;">
                <div
                    style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 4px; width: 400px;">
                    <h3>Save Schema Template</h3>
                    <p>
                        <label for="template_name"><strong>Template Name:</strong></label>
                        <input type="text" id="template_name" style="width: 100%;" placeholder="e.g., Blog Post Template">
                    </p>
                    <p>
                        <label for="template_description"><strong>Description:</strong></label>
                        <textarea id="template_description" style="width: 100%; height: 60px;"
                            placeholder="Describe what this template is for..."></textarea>
                    </p>
                    <p>
                        <label for="template_category"><strong>Category:</strong></label>
                        <select id="template_category" style="width: 100%;">
                            <option value="general">General</option>
                            <option value="blog">Blog</option>
                            <option value="ecommerce">E-commerce</option>
                            <option value="business">Business</option>
                            <option value="custom">Custom</option>
                        </select>
                    </p>
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="button" class="button" id="cancel_template_save">Cancel</button>
                        <button type="button" class="button button-primary" id="confirm_template_save">Save Template</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var schemaSelect = document.getElementById('softworld_schema_type');
                var additionalFields = document.getElementById('schema_additional_fields');
                var allFields = additionalFields.querySelectorAll('.schema-fields');
                var templateSelect = document.getElementById('schema_template_select');
                var applyTemplateBtn = document.getElementById('apply_template_btn');
                var saveTemplateBtn = document.getElementById('save_as_template_btn');
                var testSchemaBtn = document.getElementById('test_schema_btn');
                var templateSaveModal = document.getElementById('template-save-modal');
                var cancelTemplateSave = document.getElementById('cancel_template_save');
                var confirmTemplateSave = document.getElementById('confirm_template_save');

                // Post ID for AJAX requests
                var postId = <?php echo $post->ID; ?>;

                schemaSelect.addEventListener('change', function () {
                    var selectedType = this.value;

                    if (selectedType) {
                        additionalFields.style.display = 'block';
                    } else {
                        additionalFields.style.display = 'none';
                    }

                    // Hide all fields first
                    allFields.forEach(function (field) {
                        field.style.display = 'none';
                    });

                    // Show relevant fields
                    if (selectedType === 'Product') {
                        additionalFields.querySelector('.product-fields').style.display = 'block';
                    } else if (selectedType === 'FAQPage') {
                        additionalFields.querySelector('.faq-fields').style.display = 'block';
                    } else if (selectedType === 'HowTo') {
                        additionalFields.querySelector('.howto-fields').style.display = 'block';
                    } else if (selectedType === 'Review') {
                        additionalFields.querySelector('.review-fields').style.display = 'block';
                    } else if (selectedType === 'Recipe') {
                        additionalFields.querySelector('.recipe-fields').style.display = 'block';
                    }

                    // Show custom schema fields if selected
                    var customFields = additionalFields.querySelectorAll('.custom-schema-fields');
                    customFields.forEach(function (field) {
                        if (field.classList.contains('custom-schema-' + selectedType.toLowerCase().replace(/\s+/g, '-'))) {
                            field.style.display = 'block';
                        }
                    });
                });

                // Template functionality
                templateSelect.addEventListener('change', function () {
                    applyTemplateBtn.disabled = !this.value;
                });

                applyTemplateBtn.addEventListener('click', function () {
                    var templateId = templateSelect.value;
                    if (!templateId) return;

                    // Apply template via AJAX
                    jQuery.post(softworldSchema.ajaxurl, {
                        action: 'apply_schema_template',
                        nonce: softworldSchema.nonce,
                        template_id: templateId,
                        post_id: postId
                    }, function (response) {
                        if (response.success) {
                            alert('Template applied successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                });

                // Save as template
                saveTemplateBtn.addEventListener('click', function () {
                    if (!schemaSelect.value) {
                        alert('Please select a schema type first.');
                        return;
                    }

                    // Show the template save modal
                    templateSaveModal.style.display = 'block';
                });

                // Cancel template save
                cancelTemplateSave.addEventListener('click', function () {
                    templateSaveModal.style.display = 'none';
                });

                // Confirm template save
                // Confirm template save - FIXED VERSION
                confirmTemplateSave.addEventListener('click', function () {
                    var templateName = document.getElementById('template_name').value;
                    var templateDescription = document.getElementById('template_description').value;
                    var templateCategory = document.getElementById('template_category').value;

                    if (!templateName) {
                        alert('Please enter a template name.');
                        return;
                    }

                    // Gather current schema data
                    var schemaType = schemaSelect.value;
                    var schemaData = {};

                    // Collect data based on schema type
                    if (schemaType === 'Product') {
                        schemaData = {
                            price: document.getElementById('product_price').value,
                            currency: document.getElementById('product_currency').value,
                            availability: document.getElementById('product_availability').value
                        };
                    } else if (schemaType === 'FAQPage') {
                        schemaData = {
                            faq_questions: document.getElementById('faq_questions').value
                        };
                    } else if (schemaType === 'HowTo') {
                        schemaData = {
                            howto_steps: document.getElementById('howto_steps').value,
                            howto_supplies: document.getElementById('howto_supplies').value
                        };
                    } else if (schemaType === 'Review') {
                        schemaData = {
                            rating: document.getElementById('review_rating').value,
                            item_name: document.getElementById('review_item_name').value
                        };
                    } else if (schemaType === 'Recipe') {
                        schemaData = {
                            prep_time: document.getElementById('recipe_prep_time').value,
                            cook_time: document.getElementById('recipe_cook_time').value,
                            servings: document.getElementById('recipe_servings').value
                        };
                    } else {
                        // For custom schema types
                        schemaData = {};
                        var customFields = document.querySelectorAll('.custom-schema-fields input, .custom-schema-fields textarea, .custom-schema-fields select');
                        customFields.forEach(function (field) {
                            if (field.offsetParent !== null) { // Only visible fields
                                var nameParts = field.name.match(/schema_data\[custom]\[(.*?)]\[(.*?)]/);
                                if (nameParts && nameParts.length === 3) {
                                    if (!schemaData[nameParts[1]]) {
                                        schemaData[nameParts[1]] = {};
                                    }
                                    schemaData[nameParts[1]][nameParts[2]] = field.value;
                                }
                            }
                        });
                    }

                    // Show loading state
                    confirmTemplateSave.textContent = 'Saving...';
                    confirmTemplateSave.disabled = true;

                    // Save template via AJAX
                    jQuery.post(softworldSchema.ajaxurl, {
                        action: 'save_schema_template',
                        nonce: softworldSchema.nonce,
                        template_name: templateName,
                        template_description: templateDescription,
                        template_category: templateCategory,
                        schema_type: schemaType,
                        schema_data: schemaData
                    }, function (response) {
                        // Restore button state
                        confirmTemplateSave.textContent = 'Save Template';
                        confirmTemplateSave.disabled = false;

                        if (response.success) {
                            alert('Template saved successfully!');
                            templateSaveModal.style.display = 'none';
                            // Clear modal fields
                            document.getElementById('template_name').value = '';
                            document.getElementById('template_description').value = '';
                            document.getElementById('template_category').value = 'general';

                            // Reload the page to show the new template
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }).fail(function () {
                        // Restore button state on failure
                        confirmTemplateSave.textContent = 'Save Template';
                        confirmTemplateSave.disabled = false;
                        alert('An error occurred while saving the template.');
                    });
                });

                // Test schema functionality
                testSchemaBtn.addEventListener('click', function () {
                    var schemaType = schemaSelect.value;
                    if (!schemaType) {
                        alert('Please select a schema type first.');
                        return;
                    }

                    // Gather schema data
                    var schemaData = {};

                    // Collect data based on schema type
                    if (schemaType === 'Product') {
                        schemaData = {
                            price: document.getElementById('product_price').value,
                            currency: document.getElementById('product_currency').value,
                            availability: document.getElementById('product_availability').value
                        };
                    } else if (schemaType === 'FAQPage') {
                        schemaData = {
                            faq_questions: document.getElementById('faq_questions').value
                        };
                    } else if (schemaType === 'HowTo') {
                        schemaData = {
                            howto_steps: document.getElementById('howto_steps').value,
                            howto_supplies: document.getElementById('howto_supplies').value
                        };
                    } else if (schemaType === 'Review') {
                        schemaData = {
                            rating: document.getElementById('review_rating').value,
                            item_name: document.getElementById('review_item_name').value
                        };
                    } else if (schemaType === 'Recipe') {
                        schemaData = {
                            prep_time: document.getElementById('recipe_prep_time').value,
                            cook_time: document.getElementById('recipe_cook_time').value,
                            servings: document.getElementById('recipe_servings').value
                        };
                    } else {
                        // For custom schema types
                        schemaData = {};
                        var customFields = document.querySelectorAll('.custom-schema-fields input, .custom-schema-fields textarea, .custom-schema-fields select');
                        customFields.forEach(function (field) {
                            if (field.offsetParent !== null) { // Only visible fields
                                var fieldName = field.name.replace('schema_data[custom]', '').replace(/\[|\]/g, '');
                                schemaData[fieldName] = field.value;
                            }
                        });
                    }

                    // Show testing in progress
                    var testResults = document.getElementById('schema_test_results');
                    testResults.style.display = 'block';
                    testResults.innerHTML = '<p>Testing schema markup...</p>';

                    // Test schema via AJAX
                    jQuery.post(softworldSchema.ajaxurl, {
                        action: 'test_schema',
                        nonce: softworldSchema.nonce,
                        schema_type: schemaType,
                        schema_data: schemaData,
                        post_id: postId
                    }, function (response) {
                        if (response.success) {
                            testResults.innerHTML = '<p style="color: green;">✓ Schema is valid!</p>' +
                                '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
                        } else {
                            testResults.innerHTML = '<p style="color: red;">✗ Schema validation failed:</p>' +
                                '<pre>' + response.data + '</pre>';
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function save_schema_meta_box($post_id)
    {
        // Check nonce
        if (!isset($_POST['softworld_schema_nonce']) || !wp_verify_nonce($_POST['softworld_schema_nonce'], 'softworld_schema_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save schema type
        if (isset($_POST['softworld_schema_type'])) {
            update_post_meta($post_id, '_softworld_schema_type', sanitize_text_field($_POST['softworld_schema_type']));
        }

        // Save schema data
        if (isset($_POST['schema_data'])) {
            $schema_data = array();
            foreach ($_POST['schema_data'] as $key => $value) {
                if ($key === 'custom') {
                    // Handle custom schema data
                    $custom_data = array();
                    foreach ($value as $custom_key => $custom_value) {
                        $sanitized_custom = array();
                        foreach ($custom_value as $prop_key => $prop_value) {
                            $sanitized_custom[sanitize_key($prop_key)] = sanitize_text_field($prop_value);
                        }
                        $custom_data[sanitize_key($custom_key)] = $sanitized_custom;
                    }
                    $schema_data['custom'] = $custom_data;
                } else {
                    $schema_data[sanitize_key($key)] = sanitize_text_field($value);
                }
            }
            update_post_meta($post_id, '_softworld_schema_data', $schema_data);
        }
    }

    // ... rest of the class methods (output_schema_markup, get_knowledge_graph_schema, etc.)

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            // WordPress handles the option saving automatically
            echo '<div class="updated notice"><p>Schema settings saved successfully.</p></div>';
        }

        // Get current settings
        $global_settings = get_option('softworld_schema_global_settings', array(
            'enable_schema' => true,
            'default_article_type' => 'Article',
            'breadcrumb_schema' => true,
            'sitelinks_searchbox' => true,
            'schema_post_types' => array('post', 'page'),
            'default_page_schema' => 'WebPage',
            'enable_custom_schema' => true
        ));

        $kg_settings = get_option('softworld_schema_knowledge_graph', array(
            'entity_type' => 'Organization',
            'organization_name' => get_bloginfo('name'),
            'organization_logo' => '',
            'person_name' => '',
            'person_image' => '',
            'social_profiles' => array()
        ));

        $schema_types_settings = get_option('softworld_schema_types_settings', array(
            'enable_product_schema' => false,
            'enable_faq_schema' => false,
            'enable_howto_schema' => false
        ));

        $custom_schema_types = get_option('softworld_schema_custom_types', array());
        $schema_templates = get_option('softworld_schema_templates', array());

        // Output the script directly if it wasn't enqueued properly
        echo '<script>
        if (typeof softworldSchema === "undefined") {
            var softworldSchema = {
                ajaxurl: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('softworld_schema_nonce') . '"
            };
        }
        </script>';

        include plugin_dir_path(__FILE__) . '../templates/schema-settings-template.php';
    }
}