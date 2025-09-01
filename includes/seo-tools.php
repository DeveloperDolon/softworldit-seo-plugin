<?php
if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Tools
{
    private $plugin_slug;
    private $capability;

    public function __construct($plugin_slug)
    {
        $this->plugin_slug = $plugin_slug;
        $this->capability = 'manage_options';

        $this->init();
    }

    public function init()
    {
        // Add bulk editor to admin menu
        add_action('admin_menu', array($this, 'add_bulk_editor_page'), 20);

        // Handle WooCommerce integration if active
        add_action('plugins_loaded', array($this, 'check_woocommerce_integration'));
        add_action('wp_ajax_softworld_bulk_update_seo', array($this, 'handle_bulk_update'));
    }

    public function add_bulk_editor_page()
    {
        $hook = add_submenu_page(
            $this->plugin_slug, // Parent slug
            __('Bulk SEO Editor', 'softworld-ai-seo'), // Page title
            __('Bulk Editor', 'softworld-ai-seo'), // Menu title
            $this->capability, // Capability
            $this->plugin_slug . '-bulk-editor', // Menu slug
            array($this, 'render_bulk_editor_page') // Callback
        );

        if ($hook) {
            add_action('load-' . $hook, array($this, 'load_bulk_editor_assets'));
        }
    }

    public function load_bulk_editor_assets()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_bulk_editor_scripts'));
    }

    public function enqueue_bulk_editor_scripts()
    {
        wp_enqueue_script(
            'softworld-bulk-editor',
            AI_SEO_PLUGIN_URL . 'assets/js/bulk-editor.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_localize_script('softworld-bulk-editor', 'softworld_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('softworld_bulk_seo_nonce')
        ));
    }

    public function render_bulk_editor_page()
    {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        // Get all public post types
        $all_post_types = get_post_types(array('public' => true), 'names');
        $post_types = get_post_types(array('public' => true), 'objects');

        // Get current page and filter values
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $posts_per_page = 20;
        $post_type_filter = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';

        // Validate post type filter
        if (!in_array($post_type_filter, $all_post_types)) {
            $post_type_filter = 'post';
        }

        // Calculate offset for pagination
        $offset = ($paged - 1) * $posts_per_page;

        // Get posts using get_posts (more reliable than WP_Query for admin)
        $posts_args = array(
            'post_type' => $post_type_filter,
            'post_status' => array('publish', 'pending', 'draft', 'future'),
            'numberposts' => $posts_per_page,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false
        );

        $posts = get_posts($posts_args);

        // Get total count for pagination
        $count_args = array(
            'post_type' => $post_type_filter,
            'post_status' => array('publish', 'pending', 'draft', 'future'),
            'numberposts' => -1,
            'fields' => 'ids'
        );

        $total_posts = count(get_posts($count_args));
        $max_num_pages = ceil($total_posts / $posts_per_page);

        // Create a mock query object for template compatibility
        $posts_query = new stdClass();
        $posts_query->posts = $posts;
        $posts_query->post_count = count($posts);
        $posts_query->max_num_pages = $max_num_pages;
        $posts_query->found_posts = $total_posts;

        // Set up global post data for the template
        global $post;
        $original_post = $post;

        // Slugs
        $plugin_slug = $this->plugin_slug;
        $page_slug = $plugin_slug . '-bulk-editor';

        // Template path
        $template_path = plugin_dir_path(__FILE__) . '../templates/bulk-editor.php';

        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error"><p>' .
                __('Template file not found! Please create it at:', 'softworld-ai-seo') .
                ' ' . esc_html($template_path) . '</p></div>';
        }

        // Restore original post data
        $post = $original_post;
    }

    public function handle_bulk_update()
    {
        // Check nonce and permissions
        if (
            !isset($_POST['softworld_bulk_nonce']) ||
            !wp_verify_nonce($_POST['softworld_bulk_nonce'], 'softworld_bulk_seo_nonce') ||
            !current_user_can($this->capability)
        ) {
            wp_send_json_error(__('Unauthorized', 'softworld-ai-seo'));
        }

        $updated = 0;
        $errors = 0;

        if (!empty($_POST['seo_data']) && is_array($_POST['seo_data'])) {
            foreach ($_POST['seo_data'] as $post_id => $seo_fields) {
                $post_id = intval($post_id);

                if (current_user_can('edit_post', $post_id)) {
                    // Update SEO title
                    if (isset($seo_fields['title'])) {
                        update_post_meta(
                            $post_id,
                            '_softworld_ai_seo_title',
                            sanitize_text_field($seo_fields['title'])
                        );
                    }

                    // Update SEO description
                    if (isset($seo_fields['description'])) {
                        update_post_meta(
                            $post_id,
                            '_softworld_ai_seo_description',
                            sanitize_textarea_field($seo_fields['description'])
                        );
                    }

                    $updated++;
                } else {
                    $errors++;
                }
            }
        }

        wp_send_json_success(array(
            'updated' => $updated,
            'errors' => $errors,
            'message' => sprintf(__('Updated %d items, %d errors', 'softworld-ai-seo'), $updated, $errors)
        ));
    }

    public function check_woocommerce_integration()
    {
        if (class_exists('WooCommerce')) {
            $this->integrate_woocommerce();
        }
    }

    private function integrate_woocommerce()
    {
        // Add SEO meta boxes to WooCommerce products
        add_action('add_meta_boxes_product', array($this, 'add_woocommerce_meta_boxes'));

        // Save WooCommerce product SEO data
        add_action('woocommerce_process_product_meta', array($this, 'save_woocommerce_seo_data'));
    }

    public function add_woocommerce_meta_boxes()
    {
        add_meta_box(
            'softworld-ai-seo-woocommerce',
            __('SEO Settings', 'softworld-ai-seo'),
            array($this, 'render_woocommerce_meta_box'),
            'product',
            'normal',
            'high'
        );
    }

    public function render_woocommerce_meta_box($post)
    {
        // Get existing values
        $seo_title = get_post_meta($post->ID, '_softworld_ai_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);

        // Include WooCommerce meta box template
        $template_path = plugin_dir_path(__FILE__) . '../templates/woocommerce-meta-box.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    public function save_woocommerce_seo_data($post_id)
    {
        // Verify nonce
        if (
            !isset($_POST['softworld_ai_seo_nonce']) ||
            !wp_verify_nonce($_POST['softworld_ai_seo_nonce'], 'softworld_ai_seo_meta_box')
        ) {
            return;
        }

        // Save SEO data
        if (isset($_POST['softworld_ai_seo_title'])) {
            update_post_meta(
                $post_id,
                '_softworld_ai_seo_title',
                sanitize_text_field($_POST['softworld_ai_seo_title'])
            );
        }

        if (isset($_POST['softworld_ai_seo_description'])) {
            update_post_meta(
                $post_id,
                '_softworld_ai_seo_description',
                sanitize_textarea_field($_POST['softworld_ai_seo_description'])
            );
        }
    }
}
