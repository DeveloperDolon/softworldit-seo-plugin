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
        add_action('admin_menu', array($this, 'add_bulk_editor_page'));

        // Handle WooCommerce integration if active
        add_action('plugins_loaded', array($this, 'check_woocommerce_integration'));

        // Add AJAX handlers for bulk operations
        add_action('wp_ajax_softworld_bulk_update_seo', array($this, 'handle_bulk_update'));
    }

    public function add_bulk_editor_page()
    {
        error_log('Registering Bulk Editor submenu: ' . $this->plugin_slug . '-bulk-editor');
        $hook = add_submenu_page(
            $this->plugin_slug,
            __('Bulk SEO Editor', 'softworld-ai-seo'),
            __('Bulk Editor', 'softworld-ai-seo'),
            $this->capability,
            $this->plugin_slug . '-bulk-editor',
            array($this, 'render_bulk_editor_page')
        );

        // Load scripts only on this page
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
        wp_enqueue_script('softworld-bulk-editor', plugin_dir_url(__FILE__) . '../assets/js/bulk-editor.js', array('jquery'), '1.0', true);
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

        // Get post types for filtering
        $post_types = get_post_types(array('public' => true), 'objects');

        // Get posts for bulk editing (with pagination)
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $posts_per_page = 20;
        $post_type_filter = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';

        $query_args = array(
            'post_type' => $post_type_filter,
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_status' => array('publish', 'pending', 'draft', 'future')
        );

        $posts_query = new WP_Query($query_args);

        // Extract variables for the template
        $plugin_slug = $this->plugin_slug;
        $page_slug = $plugin_slug . '-bulk-editor';

        // Get the current URL with parameters
        $current_url = admin_url('admin.php?page=' . $page_slug);
        if (!empty($post_type_filter) && $post_type_filter !== 'post') {
            $current_url .= '&post_type=' . $post_type_filter;
        }

        // Calculate the correct template path
        $template_path = dirname(plugin_dir_path(__FILE__)) . '/templates/bulk-editor.php';

        // Render the bulk editor template
        if (file_exists($template_path)) {
            // Pass all variables to the template
            include $template_path;
        } else {
            echo '<div class="error"><p>' .
                __('Template file not found! Please create it at:', 'softworld-ai-seo') .
                ' ' . esc_html($template_path) . '</p></div>';
        }
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
