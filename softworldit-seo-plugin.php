<?php
/**
 * Plugin Name: SOFTWORLD AI SEO Plugin
 * Plugin URI:  https://wordpress.org/plugins/softworld-ai-seo-plugin/
 * Description: Advance SEO Plugin with AI-powered content optimization using OpenAI + Dynamic XML Sitemap
 * Version: 1.0.0
 * Author: SOFTWORLDIT LLC
 * Author URI: https://softworldit.com/
 * Network: true
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: softworld-ai-seo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_SEO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AI_SEO_VERSION', '1.0.0');

// Include sitemap feature
require_once plugin_dir_path(__FILE__) . 'includes/sitemap.php';
require_once plugin_dir_path(__FILE__) . 'includes/role-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/seo-tools.php';
require_once plugin_dir_path(__FILE__) . 'includes/robots-txt-editor.php';
require_once plugin_dir_path(__FILE__) . 'includes/redirect-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/not-found-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/crawl-optimizer.php';
require_once plugin_dir_path(__FILE__) . 'includes/advanced-meta-boxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/variable-replacement.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend-output.php';
require_once plugin_dir_path(__FILE__) . 'includes/schema-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/link-assistant.php';
require_once plugin_dir_path(__FILE__) . 'includes/advanced-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/corn-schedule-helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/multisite-network-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/local-seo-manager.php';

// Another services Includes
require_once plugin_dir_path(__FILE__) . 'includes/another_services/meta-boxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/another_services/snippet-preview.php';
require_once plugin_dir_path(__FILE__) . 'includes/another_services/content-analysis.php';
require_once plugin_dir_path(__FILE__) . 'includes/another_services/dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/another_services/save_meta_box.php';
require_once plugin_dir_path(__FILE__) . 'includes/another_services/setup-wizard.php';
require_once plugin_dir_path(__FILE__) . 'includes/another_services/search-appearance.php';

// ds seo redirects
require_once plugin_dir_path(__FILE__) . 'includes/dx-seo-redirect/activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/dx-seo-redirect/deactivator.php';
require_once plugin_dir_path(__FILE__) . 'includes/dx-seo-redirect/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/dx-seo-redirect/router.php';
require_once plugin_dir_path(__FILE__) . 'includes/dx-seo-redirect/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/dx-seo-redirect/admin-menu.php';

// main plugin class
class Softworld_AI_SEO_Plugin
{
    private $role_manager;
    private $seo_tools;
    private $robots_txt_editor;
    private $redirect_manager;
    private $not_found_logger;
    private $crawl_optimizer;
    private $advanced_meta_boxes;
    private $frontend_output;
    private $variable_replacer;
    private $schema_manager;
    private $link_assistant;
    private $advanced_settings;
    private $multisite_manager;
    private $local_seo_manager;

    public function __construct()
    {
        // Initialize multisite manager FIRST if multisite
        if (is_multisite()) {
            $this->multisite_manager = new Softworld_AI_SEO_Multisite_Network_Manager('softworld-ai-seo');
        }

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Add Admin Pages - Use priority to ensure multisite manager is ready
        add_action('admin_menu', array($this, 'register_admin_menu'), 10);

        // FIXED: Only register network admin menu if we're in multisite AND have the manager
        add_action('network_admin_menu', array($this, 'register_network_admin_menu'), 10);

        // plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize other components
        $this->init_components();

        // AJAX handlers
        $this->init_ajax_handlers();

        // Initialize DXSEO Admin Menu
        add_action('plugins_loaded', function () {
            if (class_exists('DXSEO_Admin_Menu')) {
                DXSEO_Admin_Menu::init();
            }
        });
    }

    /**
     * Initialize components
     */
    private function init_components()
    {
        // Initialize Role Manager
        $this->role_manager = new Softworld_AI_SEO_Role_Manager('softworld-ai-seo');
        // Initialize SEO Tools
        $this->seo_tools = new Softworld_AI_SEO_Tools('softworld-ai-seo');
        // Initialize Robots.txt Editor
        $this->robots_txt_editor = new Softworld_AI_SEO_Robots_Txt_Editor();
        // Initialize Redirect Manager
        $this->redirect_manager = new Softworld_AI_SEO_Redirect_Manager();
        // Initialize 404 Logger
        $this->not_found_logger = new Softworld_AI_SEO_Not_Found_Logger();
        // Initialize Crawl Optimizer
        $this->crawl_optimizer = new Softworld_AI_SEO_Crawl_Optimizer();
        // Add this to the constructor
        $this->advanced_meta_boxes = new Softworld_AI_SEO_Advanced_Meta_Boxes();
        // Initialize in constructor
        $this->variable_replacer = new Softworld_AI_SEO_Variable_Replacement();
        // Initialize Frontend Output
        $this->frontend_output = new Softworld_AI_SEO_Frontend_Output($this->variable_replacer);
        $this->schema_manager = new Softworld_AI_SEO_Schema_Manager('softworld-ai-seo');
        $this->link_assistant = new Softworld_AI_SEO_Link_Assistant('softworld-ai-seo');
        $this->advanced_settings = new Softworld_AI_SEO_Advanced_Settings('softworld-ai-seo');
        $this->local_seo_manager = new Softworld_AI_SEO_Local_Manager('softworld-ai-seo');
    }

    /**
     * Initialize AJAX handlers
     */
    private function init_ajax_handlers()
    {
        add_action('wp_ajax_softworld_search_posts', [$this, 'ajax_search_posts']);
        add_action('wp_ajax_softworld_search_pages', [$this, 'ajax_search_pages']);
        add_action('wp_ajax_softworld_manual_ping', [$this, 'ajax_manual_ping']);
        add_shortcode('softworld_seo_title', array($this, 'seo_title_shortcode'));
        add_shortcode('softworld_seo_description', array($this, 'seo_description_shortcode'));

        // NEW: Add NGINX and robots.txt AJAX handlers for network admin
        if (is_multisite() && is_network_admin()) {
            add_action('wp_ajax_softworld_network_generate_nginx_rules', [$this->multisite_manager, 'ajax_generate_nginx_rules']);
            add_action('wp_ajax_softworld_network_save_robots_txt', [$this->multisite_manager, 'ajax_save_network_robots_txt']);
            add_action('wp_ajax_softworld_network_apply_robots_to_sites', [$this->multisite_manager, 'ajax_apply_robots_to_sites']);
            add_action('wp_ajax_softworld_reset_site_robots', [$this, 'ajax_reset_site_robots']);
        }
    }

    public function init()
    {
        // Create custom roles if they don't exist
        $this->create_custom_roles();

        // Initialize SEO plugin features if needed
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'), 10);

        // Save meta box data
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('wp_ajax_softworld_get_advanced_stats', [$this, 'ajax_get_advanced_stats']);
        add_action('wp_ajax_softworld_update_query_status', [$this, 'ajax_update_query_status']);
        add_action('wp_ajax_softworld_get_report_data', [$this, 'ajax_get_report_data']);
        add_action('wp_ajax_softworld_export_query_data', [$this, 'ajax_export_query_data']);

        // NEW: Handle multisite robots.txt
        if (is_multisite()) {
            add_action('template_redirect', [$this, 'handle_multisite_robots_txt'], 1);
        }

        // FIXED: Only add multisite AJAX handlers if we have multi-site manager
        if (is_multisite() && $this->multisite_manager) {
            add_action('wp_ajax_softworld_site_request_override', [$this, 'ajax_site_request_override']);
            add_action('wp_ajax_softworld_site_revoke_override', [$this, 'ajax_site_revoke_override']);
            add_action('wp_ajax_softworld_network_get_stats', [$this, 'ajax_network_get_stats']);
            add_action('wp_ajax_softworld_network_get_import_export_log', [$this, 'ajax_network_get_import_export_log']);
        }
    }

    // Add this new AJAX handler method for resetting site robots:

    public function ajax_reset_site_robots()
    {
        check_ajax_referer('softworld_network_nonce', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $site_id = intval($_POST['site_id']);
        if (!$site_id) {
            wp_send_json_error(__('Invalid site ID', 'softworld-ai-seo'));
        }

        switch_to_blog($site_id);

        // Remove custom robots.txt settings
        delete_option('softworld_ai_seo_custom_robots_txt');
        delete_option('softworld_ai_seo_robots_txt_content');
        delete_option('softworld_ai_seo_robots_modified');

        // Apply network robots.txt
        $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', '');
        if (!empty($network_robots)) {
            $site_robots = str_replace('%%sitemap_url%%', home_url('/sitemap.xml'), $network_robots);
            update_option('softworld_ai_seo_robots_txt_content', $site_robots);
            update_option('softworld_ai_seo_robots_modified', current_time('mysql'));
        }

        restore_current_blog();

        wp_send_json_success(__('Site reset to network robots.txt successfully.', 'softworld-ai-seo'));
    }

    public function check_sitemap_status($blog_id = null)
    {
        if ($blog_id) {
            switch_to_blog($blog_id);
        }

        $sitemap_enabled = get_option('softworld_ai_seo_sitemap_enabled', true);
        $sitemap_url = home_url('/sitemap.xml');

        // Check if sitemap exists
        $response = wp_remote_head($sitemap_url);
        $exists = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;

        if ($blog_id) {
            restore_current_blog();
        }

        return [
            'enabled' => $sitemap_enabled,
            'exists' => $exists,
            'url' => $sitemap_url
        ];
    }

    public function ajax_get_advanced_stats()
    {
        check_ajax_referer('softworld_advanced_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_query_args';

        $stats = [
            'total_unique_urls' => 0,
            'total_query_args' => 0,
            'high_impact_args' => 0,
            'recent_activity' => 0
        ];

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $stats['total_unique_urls'] = $wpdb->get_var("SELECT COUNT(DISTINCT url) FROM $table_name");
            $stats['total_query_args'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $stats['high_impact_args'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE impact_score > 50");
            $stats['recent_activity'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
        }

        wp_send_json_success($stats);
    }

    public function ajax_update_query_status()
    {
        check_ajax_referer('softworld_advanced_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);

        $allowed_statuses = ['new', 'reviewed', 'whitelisted', 'blacklisted'];
        if (!in_array($status, $allowed_statuses)) {
            wp_send_json_error(__('Invalid status', 'softworld-ai-seo'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'softworld_query_args';

        $result = $wpdb->update(
            $table_name,
            ['status' => $status],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success(__('Status updated successfully', 'softworld-ai-seo'));
        } else {
            wp_send_json_error(__('Failed to update status', 'softworld-ai-seo'));
        }
    }

    public function ajax_site_request_override()
    {
        check_ajax_referer('softworld_network_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $reason = sanitize_textarea_field($_POST['override_reason'] ?? '');

        // Store override request
        update_option('softworld_ai_seo_override_requested', true);
        update_option('softworld_ai_seo_override_reason', $reason);
        update_option('softworld_ai_seo_override_request_date', current_time('mysql'));
        update_option('softworld_ai_seo_override_requester', get_current_user_id());

        // Get network settings to check auto-approval
        $network_settings = get_site_option('softworld_ai_seo_network_settings', []);

        if (!empty($network_settings['auto_approve_overrides'])) {
            // Auto-approve the request
            update_option('softworld_ai_seo_override_approved', true);
            update_option('softworld_ai_seo_override_approved_date', current_time('mysql'));

            wp_send_json_success(__('Override request approved automatically!', 'softworld-ai-seo'));
        } else {
            // Send notification to network admin
            $this->notify_network_admin_override_request($reason);

            wp_send_json_success(__('Override request submitted for review.', 'softworld-ai-seo'));
        }
    }

    /**
     * AJAX: Site Revoke Override
     */
    public function ajax_site_revoke_override()
    {
        check_ajax_referer('softworld_network_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        // Remove all override-related options
        delete_option('softworld_ai_seo_override_approved');
        delete_option('softworld_ai_seo_override_requested');
        delete_option('softworld_ai_seo_override_reason');
        delete_option('softworld_ai_seo_override_request_date');
        delete_option('softworld_ai_seo_override_approved_date');
        delete_option('softworld_ai_seo_override_requester');

        // Reset to network settings
        $network_settings = get_site_option('softworld_ai_seo_network_settings', []);
        foreach ($network_settings as $key => $value) {
            if (strpos($key, 'default_') === 0) {
                $site_key = str_replace('default_', 'softworld_ai_seo_', $key);
                update_option($site_key, $value);
            }
        }

        wp_send_json_success(__('Override revoked. Site now uses network settings.', 'softworld-ai-seo'));
    }

    /**
     * AJAX: Get Network Stats
     */
    public function ajax_network_get_stats()
    {
        check_ajax_referer('softworld_network_nonce', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $stats = [
            'license_status' => $this->multisite_manager ?
                $this->multisite_manager->get_network_license_info() : null,
            'network_stats' => $this->multisite_manager ?
                $this->multisite_manager->get_network_statistics() : null
        ];

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Get Import/Export Log
     */
    public function ajax_network_get_import_export_log()
    {
        check_ajax_referer('softworld_network_nonce', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        ob_start();
        if ($this->multisite_manager) {
            $this->multisite_manager->render_import_export_log();
        }
        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    /**
     * Notify Network Admin of Override Request
     */
    private function notify_network_admin_override_request($reason)
    {
        $site_name = get_bloginfo('name');
        $site_url = get_home_url();
        $user = wp_get_current_user();

        $subject = sprintf(
            __('[%s] SEO Settings Override Request', 'softworld-ai-seo'),
            get_network()->site_name
        );

        $message = sprintf(
            __('A site administrator has requested permission to override network SEO settings.

                Site: %s (%s)
                Requested by: %s (%s)
                Date: %s

                Reason:
                %s

                To review this request, visit: %s

                ---
                This is an automated message from Softworld AI SEO Plugin.', 'softworld-ai-seo'),
            $site_name,
            $site_url,
            $user->display_name,
            $user->user_email,
            current_time('F j, Y g:i A'),
            $reason ?: __('No reason provided', 'softworld-ai-seo'),
            network_admin_url('admin.php?page=softworld-ai-seo-network')
        );

        // Get network admin email
        $network_admin_email = get_site_option('admin_email');

        wp_mail($network_admin_email, $subject, $message);
    }

    public function ajax_get_report_data()
    {
        check_ajax_referer('softworld_advanced_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $report_id = intval($_POST['id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'softworld_seo_reports';

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $report_id
        ));

        if (!$report) {
            wp_send_json_error(__('Report not found', 'softworld-ai-seo'));
        }

        $report_data = json_decode($report->report_data, true);

        $html = '<div class="report-data-viewer">';
        $html .= '<h4>' . sprintf(__('Report Type: %s', 'softworld-ai-seo'), ucwords(str_replace('_', ' ', $report->report_type))) . '</h4>';
        $html .= '<p><strong>' . __('Sent to:', 'softworld-ai-seo') . '</strong> ' . esc_html($report->recipient_email) . '</p>';
        $html .= '<p><strong>' . __('Sent on:', 'softworld-ai-seo') . '</strong> ' . date('F j, Y \a\t g:i A', strtotime($report->sent_date)) . '</p>';
        $html .= '<p><strong>' . __('Status:', 'softworld-ai-seo') . '</strong> ' . ucfirst($report->status) . '</p>';

        if ($report_data) {
            $html .= '<h4>' . __('Report Contents:', 'softworld-ai-seo') . '</h4>';
            $html .= '<pre style="background: #f1f3f4; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;">';
            $html .= esc_html(json_encode($report_data, JSON_PRETTY_PRINT));
            $html .= '</pre>';
        }

        $html .= '</div>';

        wp_send_json_success($html);
    }

    public function ajax_export_query_data()
    {
        check_ajax_referer('softworld_advanced_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'softworld-ai-seo'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'softworld_query_args';

        $results = $wpdb->get_results(
            "SELECT url, query_args, hit_count, impact_score, status, first_seen, last_seen 
             FROM $table_name 
             ORDER BY last_seen DESC",
            ARRAY_A
        );

        if (empty($results)) {
            wp_die(__('No data to export', 'softworld-ai-seo'));
        }

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="query-args-data-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, [
            'URL',
            'Query Arguments',
            'Hit Count',
            'Impact Score',
            'Status',
            'First Seen',
            'Last Seen'
        ]);

        // Add data rows
        foreach ($results as $row) {
            fputcsv($output, [
                $row['url'],
                $row['query_args'],
                $row['hit_count'],
                $row['impact_score'],
                $row['status'],
                $row['first_seen'],
                $row['last_seen']
            ]);
        }

        fclose($output);
        exit;
    }

    // Keep these helper methods if you need them:
    public function get_the_seo_title($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        return $this->variable_replacer->get_seo_title($post_id);
    }

    public function get_the_seo_description($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        return $this->variable_replacer->get_seo_description($post_id);
    }

    // Shortcode methods
    public function seo_title_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
        ), $atts);

        return $this->get_seo_title($atts['id']);
    }

    public function seo_description_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
        ), $atts);

        return $this->get_seo_description($atts['id']);
    }

    public function ajax_search_posts()
    {
        check_ajax_referer('search_posts_nonce', 'nonce');

        $search = sanitize_text_field($_GET['term']);

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $search,
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $results = [];
        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'label' => $post->post_title . ' (ID: ' . $post->ID . ')',
                'value' => $post->post_title
            ];
        }

        wp_send_json($results);
    }

    // AJAX handler for searching pages
    public function ajax_search_pages()
    {
        check_ajax_referer('search_pages_nonce', 'nonce');

        $search = sanitize_text_field($_GET['term']);

        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => $search,
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $results = [];
        foreach ($pages as $page) {
            $results[] = [
                'id' => $page->ID,
                'label' => $page->post_title . ' (ID: ' . $page->ID . ')',
                'value' => $page->post_title
            ];
        }

        wp_send_json($results);
    }

    public function ajax_manual_ping()
    {
        check_ajax_referer('softworld_sitemap_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to perform this action.');
        }

        $result = SoftworldIT_SEO_Sitemap::manual_ping_search_engines();

        if ($result) {
            wp_send_json_success('Search engines pinged successfully.');
        } else {
            wp_send_json_error('Failed to ping search engines.');
        }
    }

    public function save_meta_box_data($post_id)
    {
        // Check if our nonce is set
        if (!isset($_POST['softworld_ai_seo_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['softworld_ai_seo_nonce'], 'softworld_ai_seo_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save SEO Title
        if (isset($_POST['softworld_ai_seo_title'])) {
            update_post_meta($post_id, '_softworld_ai_seo_title', sanitize_text_field($_POST['softworld_ai_seo_title']));
        }

        // Save SEO Description
        if (isset($_POST['softworld_ai_seo_description'])) {
            update_post_meta($post_id, '_softworld_ai_seo_description', sanitize_textarea_field($_POST['softworld_ai_seo_description']));
        }
    }

    private function create_custom_roles()
    {
        // Create SEO Manager role if it doesn't exist
        if (!get_role('seo_manager')) {
            add_role('seo_manager', __('SEO Manager', 'softworld-ai-seo'), array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                'manage_categories' => true,
            ));
        }

        // Create SEO Editor role if it doesn't exist
        if (!get_role('seo_editor')) {
            add_role('seo_editor', __('SEO Editor', 'softworld-ai-seo'), array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            ));
        }
    }

    public function register_meta_boxes()
    {
        // Check if current user can view the meta box
        if ($this->role_manager->should_hide_meta_box()) {
            return;
        }

        // Register your SEO meta box
        add_meta_box(
            'softworld_ai_seo_meta_box',
            'SOFTWORLD AI SEO',
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    public function render_meta_box($post)
    {
        // Your meta box content here
        echo '<div class="softworld-ai-seo-meta-box">';
        echo '<p>AI-powered SEO optimization will go here.</p>';

        // Example: Add nonce field for security
        wp_nonce_field('softworld_ai_seo_meta_box', 'softworld_ai_seo_nonce');

        // Example: Get existing meta values
        $meta_title = get_post_meta($post->ID, '_softworld_ai_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);

        // Example form fields
        echo '<div style="margin: 10px 0;">';
        echo '<label for="softworld_ai_seo_title" style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Title:</label>';
        echo '<input type="text" id="softworld_ai_seo_title" name="softworld_ai_seo_title" value="' . esc_attr($meta_title) . '" style="width: 100%; padding: 5px;">';
        echo '</div>';

        echo '<div style="margin: 10px 0;">';
        echo '<label for="softworld_ai_seo_description" style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Description:</label>';
        echo '<textarea id="softworld_ai_seo_description" name="softworld_ai_seo_description" style="width: 100%; height: 80px; padding: 5px;">' . esc_textarea($meta_description) . '</textarea>';
        echo '</div>';

        echo '</div>';
    }

    public function enqueue_scripts()
    {
        // Enqueue frontend scripts if needed
        wp_enqueue_style(
            'softworldit-seo-admin-style',
            plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
            [],
            '1.0.0'
        );

        wp_enqueue_style(
            'softworldit-ayc-admin-css',
            plugin_dir_url(__FILE__) . 'assets/css/another_services.css',
            [],
            '1.0.0'
        );

        wp_enqueue_style(
            'softworldit-seo-schema-admin',
            plugin_dir_url(__FILE__) . 'assets/css/schema-admin.css',
            [],
            '1.0.0'
        );

        // NEW: Link Assistant CSS
        wp_enqueue_style(
            'softworldit-link-assistant-admin',
            plugin_dir_url(__FILE__) . 'assets/css/link-assistant-admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_style(
            'softworldit-advanced-settings-css',
            plugin_dir_url(__FILE__) . 'assets/css/advanced-settings.css',
            [],
            '1.0.0'
        );

        wp_enqueue_style(
            'softworld-network-admin',
            plugin_dir_url(__FILE__) . 'assets/css/network-admin.css',
            [],
            '1.0.0'
        );

        wp_enqueue_style(
            'softworldit-local-seo-admin',
            plugin_dir_url(__FILE__) . 'assets/css/local-seo-admin.css',
            [],
            '1.0.0'
        );
    }

    public function admin_enqueue_scripts()
    {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.3.0', true);
        // Enqueue admin scripts if needed
        wp_enqueue_script(
            'softworldit-seo-admin-script',
            plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'softworldit-ayc-admin-js',
            plugin_dir_url(__FILE__) . 'assets/js/another_services.js',
            ['jquery', 'chart-js'],
            '1.0.0',
            true
        );

        // ✅ এখানে AJAX + Nonce + Host data পাঠানো হলো (v1.5 থেকে আনা হয়েছে)
        $host = parse_url(home_url(), PHP_URL_HOST);
        wp_localize_script('softworldit-ayc-admin-js', 'AYC_SEO', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ayc_nonce'),
            'i18n' => [
                'save' => 'Save',
                'cancel' => 'Cancel',
                'enterSuggestion' => 'Write a tip or note…',
                'selectStatus' => 'Select status',
                'good' => 'Good',
                'check' => 'Check',
                'poor' => 'Poor',
                'overridden' => '(overridden)',
            ],
            'host' => $host ?: ''
        ]);

        wp_enqueue_script(
            'softworldit-seo-schema-admin',
            plugin_dir_url(__FILE__) . 'assets/js/schema-admin.js',
            ['jquery', 'wp-media'],
            '1.0.0',
            true
        );

        // NEW: Link Assistant JavaScript
        wp_enqueue_script(
            'softworldit-link-assistant-admin',
            plugin_dir_url(__FILE__) . 'assets/js/link-assistant-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // NEW: Localize Link Assistant script
        wp_localize_script('softworldit-link-assistant-admin', 'softworldLinkAssistant', [
            'nonce' => wp_create_nonce('softworld_link_assistant_nonce'),
            'search_nonce' => wp_create_nonce('search_posts_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
        ]);

        // Add Advanced Settings JavaScript
        wp_enqueue_script(
            'softworldit-advanced-settings-js',
            plugin_dir_url(__FILE__) . 'assets/js/advanced-settings.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize Advanced Settings script
        wp_localize_script('softworldit-advanced-settings-js', 'softworldAdvancedSettings', [
            'nonce' => wp_create_nonce('softworld_advanced_settings'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'i18n' => [
                'sending' => __('Sending...', 'softworld-ai-seo'),
                'success' => __('Success!', 'softworld-ai-seo'),
                'error' => __('Error occurred', 'softworld-ai-seo'),
                'confirm_clear' => __('Are you sure you want to clear old logs?', 'softworld-ai-seo'),
                'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'softworld-ai-seo')
            ]
        ]);

        // Enqueue network admin scripts - FIXED: Only for multisite
        if (is_multisite()) {
            wp_enqueue_script(
                'softworld-network-admin',
                plugin_dir_url(__FILE__) . 'assets/js/network-admin.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('softworld-network-admin', 'softworldNetwork', [
                'nonce' => wp_create_nonce('softworld_network_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'i18n' => [
                    'saving' => __('Saving...', 'softworld-ai-seo'),
                    'saved' => __('Saved!', 'softworld-ai-seo'),
                    'error' => __('Error occurred', 'softworld-ai-seo'),
                    'confirm_apply_all' => __('Are you sure you want to apply these settings to all sites?', 'softworld-ai-seo'),
                    'confirm_override_request' => __('Request permission to override network settings?', 'softworld-ai-seo'),
                    'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'softworld-ai-seo')
                ]
            ]);

            // Enqueue site override assets for individual sites (non-network admin)
            if (!is_network_admin()) {
                wp_enqueue_script(
                    'softworld-site-override',
                    plugin_dir_url(__FILE__) . 'assets/js/site-override.js',
                    ['jquery'],
                    '1.0.0',
                    true
                );

                wp_localize_script('softworld-site-override', 'softworldSiteOverride', [
                    'nonce' => wp_create_nonce('softworld_network_nonce'),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'i18n' => [
                        'confirm_override_request' => __('Request permission to override network settings?', 'softworld-ai-seo'),
                        'confirm_revoke_override' => __('Revert to network settings? This will remove all custom settings.', 'softworld-ai-seo')
                    ]
                ]);
            }
        }

        // Add Local SEO JavaScript
        wp_enqueue_script(
            'softworldit-local-seo-admin',
            plugin_dir_url(__FILE__) . 'assets/js/local-seo-admin.js',
            ['jquery', 'jquery-ui-dialog'],
            '1.0.0',
            true
        );

        // Localize Local SEO script
        wp_localize_script('softworldit-local-seo-admin', 'softworldLocalSEO', [
            'nonce' => wp_create_nonce('softworld_local_seo_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'google_maps_api_key' => get_option('softworld_ai_seo_google_maps_api_key', ''),
            'i18n' => [
                'loading' => __('Loading...', 'softworld-ai-seo'),
                'saved' => __('Saved successfully!', 'softworld-ai-seo'),
                'error' => __('An error occurred', 'softworld-ai-seo'),
                'confirm_delete' => __('Are you sure you want to delete this location?', 'softworld-ai-seo'),
                'geocoding_error' => __('Could not find coordinates for this address', 'softworld-ai-seo'),
                'required_field' => __('This field is required', 'softworld-ai-seo'),
                'invalid_email' => __('Please enter a valid email address', 'softworld-ai-seo'),
                'invalid_url' => __('Please enter a valid URL', 'softworld-ai-seo')
            ]
        ]);
    }

    public function activate()
    {
        // Register sitemap rewrite rules on activation
        if (class_exists('SoftworldIT_SEO_Sitemap')) {
            SoftworldIT_SEO_Sitemap::add_rewrite();
        }

        // Add custom table for redirects
        if ($this->redirect_manager) {
            Softworld_AI_SEO_Redirect_Manager::create_redirects_table();
        }

        // Add custom table for 404 logs
        if ($this->not_found_logger) {
            Softworld_AI_SEO_Not_Found_Logger::create_log_table();
        }

        // Create advanced settings tables
        if ($this->advanced_settings) {
            $this->advanced_settings->create_tables();
        }

        // Create network tables if multisite
        if (is_multisite() && $this->multisite_manager) {
            $this->create_network_tables();

            // Initialize network settings if they don't exist
            if (!get_site_option('softworld_ai_seo_network_settings')) {
                $default_network_settings = [
                    'allow_site_overrides' => 0,
                    'auto_approve_overrides' => 0,
                    'default_seo_title' => get_bloginfo('name') . ' - %%title%%',
                    'default_seo_description' => get_bloginfo('description'),
                    'ping_search_engines' => 1,
                    'include_posts' => 1,
                    'include_pages' => 1
                ];
                update_site_option('softworld_ai_seo_network_settings', $default_network_settings);
            }

            // NEW: Initialize default network robots.txt
            if (!get_site_option('softworld_ai_seo_network_robots_txt')) {
                $default_robots = $this->get_default_network_robots();
                update_site_option('softworld_ai_seo_network_robots_txt', $default_robots);
                update_site_option('softworld_ai_seo_network_robots_updated', current_time('mysql'));
            }
        }

        global $wp_rewrite;
        delete_option('rewrite_rules');
        $wp_rewrite->flush_rules(true);

        // Clear any WordPress cache
        wp_cache_flush();
    }

    public function deactivate()
    {
        // Flush rules on deactivation
        flush_rewrite_rules();
    }

    private function get_default_network_robots()
    {
        return "# Softworld AI SEO Network Robots.txt
    User-agent: *
    Allow: /

    # Block admin areas
    Disallow: /wp-admin/
    Disallow: /wp-login.php
    Disallow: /wp-register.php

    # Block unnecessary files
    Disallow: /wp-config.php
    Disallow: /readme.html
    Disallow: /license.txt

    # Allow important files
    Allow: /wp-admin/admin-ajax.php
    Allow: /wp-content/uploads/

    # Sitemap
    Sitemap: %%sitemap_url%%

    # Crawl-delay for aggressive bots
    User-agent: AhrefsBot
    Crawl-delay: 10

    User-agent: SemrushBot
    Crawl-delay: 10

    User-agent: MJ12bot
    Crawl-delay: 10";
    }

    // Add this method to handle robots.txt output for multisite:

    public function handle_multisite_robots_txt()
    {
        if (!is_multisite()) {
            return;
        }

        // Check if this is a robots.txt request
        if (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] !== '/robots.txt') {
            return;
        }

        // Get network or site-specific robots.txt
        $custom_robots = get_option('softworld_ai_seo_custom_robots_txt', false);
        $allow_overrides = get_site_option('softworld_ai_seo_robots_allow_overrides', false);

        if ($allow_overrides && $custom_robots) {
            // Use site-specific robots.txt
            $robots_content = get_option('softworld_ai_seo_robots_txt_content', '');
        } else {
            // Use network robots.txt
            $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', '');
            if (!empty($network_robots)) {
                $robots_content = str_replace('%%sitemap_url%%', home_url('/sitemap.xml'), $network_robots);
            }
        }

        if (!empty($robots_content)) {
            header('Content-Type: text/plain; charset=utf-8');
            echo esc_html($robots_content);
            exit;
        }
    }

    /**
     * Create Network Tables
     */
    private function create_network_tables()
    {
        global $wpdb;

        // Create network override requests table
        $table_name = $wpdb->base_prefix . 'softworld_seo_network_overrides';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            blog_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            reason text,
            status varchar(20) DEFAULT 'pending',
            requested_date datetime DEFAULT CURRENT_TIMESTAMP,
            approved_date datetime DEFAULT NULL,
            approved_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY blog_id (blog_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Helper method to check if current site can override network settings
     */
    public function can_override_network_settings()
    {
        if (!is_multisite()) {
            return true;
        }

        return (bool) get_option('softworld_ai_seo_override_approved', false);
    }

    /**
     * Helper method to get effective setting value (network or site)
     */
    public function get_effective_setting($option_name, $default = false)
    {
        if (!is_multisite()) {
            return get_option($option_name, $default);
        }

        if ($this->can_override_network_settings()) {
            return get_option($option_name, $default);
        }

        // Try to get from network settings
        $network_settings = get_site_option('softworld_ai_seo_network_settings', []);
        $network_key = str_replace('softworld_ai_seo_', 'default_', $option_name);

        if (isset($network_settings[$network_key])) {
            return $network_settings[$network_key];
        }

        return $default;
    }

    /**
     * Register Admin Menu
     */
    public function register_admin_menu()
    {
        // FIXED: Check if site has access to settings in multisite environment
        if (is_multisite() && !is_network_admin()) {
            $network_settings = get_site_option('softworld_ai_seo_network_settings', []);
            $override_approved = get_option('softworld_ai_seo_override_approved', false);

            // If overrides are not allowed and site doesn't have approval, show limited menu
            if (empty($network_settings['allow_site_overrides']) && !$override_approved) {
                add_menu_page(
                    'Softworld SEO',
                    'Softworld SEO',
                    'manage_options',
                    'softworld-ai-seo',
                    [$this, 'render_restricted_dashboard'],
                    'dashicons-chart-area',
                    80
                );
                return;
            }

            // If overrides are allowed but not approved, show override request page
            if (!empty($network_settings['allow_site_overrides']) && !$override_approved) {
                add_menu_page(
                    'Softworld SEO',
                    'Softworld SEO',
                    'manage_options',
                    'softworld-ai-seo',
                    [$this->multisite_manager, 'render_site_override_page'],
                    'dashicons-chart-area',
                    80
                );
                return;
            }
        }

        // Regular menu for single site or approved multisite subsites
        add_menu_page(
            'Softworld SEO',
            'Softworld SEO',
            'manage_options',
            'softworld-ai-seo',
            'softworldit_render_dashboard',
            'dashicons-chart-area',
            80
        );

        add_submenu_page('softworld-ai-seo', 'Setup Wizard', 'Setup Wizard', 'manage_options', 'softworldit-setup', 'swit_render_setup_wizard');
        add_submenu_page('softworld-ai-seo', 'Search Appearance', 'Search Appearance', 'manage_options', 'softworldit-search', 'softworldit_render_search');

        // Add Sitemap submenu
        add_submenu_page(
            'softworld-ai-seo',
            'XML Sitemap',
            'XML Sitemap',
            'manage_options',
            'softworldit-seo-sitemap',
            array($this, 'render_sitemap_admin')
        );

        // NEW: Add Link Assistant submenu
        add_submenu_page(
            'softworld-ai-seo',
            __('Link Assistant', 'softworld-ai-seo'),
            __('Link Assistant', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-link-assistant',
            [$this->link_assistant, 'render_admin_page']
        );

        // Add Robots.txt submenu
        add_submenu_page(
            'softworld-ai-seo',
            'Robots.txt Editor',
            'Robots.txt Editor',
            'manage_options',
            'softworldit-seo-robots',
            array($this, 'render_robots_admin')
        );

        add_submenu_page(
            'softworld-ai-seo',
            'AI Settings',
            'AI Settings',
            'manage_options',
            'softworldit-seo-settings',
            array($this, 'render_ai_settings_admin')
        );

        add_submenu_page(
            'softworld-ai-seo',
            __('Schema Settings', 'softworld-ai-seo'),
            __('Schema Settings', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-schema',
            [$this->schema_manager, 'render_admin_page']
        );

        // Add submenus for the new features
        add_submenu_page(
            'softworld-ai-seo',
            __('Redirects', 'softworld-ai-seo'),
            __('Redirects', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-redirects',
            [$this->redirect_manager, 'render_admin_page']
        );

        add_submenu_page(
            'softworld-ai-seo',
            __('404 Log', 'softworld-ai-seo'),
            __('404 Log', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-404-log',
            [$this->not_found_logger, 'render_admin_page']
        );

        add_submenu_page(
            'softworld-ai-seo',
            __('Crawl Optimization', 'softworld-ai-seo'),
            __('Crawl Optimization', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-crawl-optimizer',
            [$this->crawl_optimizer, 'render_admin_page']
        );

        add_submenu_page(
            'softworld-ai-seo',
            __('Advanced Settings', 'softworld-ai-seo'),
            __('Advanced Settings', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-advanced-settings',
            [$this->advanced_settings, 'render_admin_page']
        );

        add_submenu_page(
            'softworld-ai-seo',
            __('Local SEO', 'softworld-ai-seo'),
            __('Local SEO', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-local-seo',
            [$this->local_seo_manager, 'render_admin_page']
        );
    }

    /**
     * Register Network Admin Menu - FIXED: Single method only
     */
    public function register_network_admin_menu()
    {
        if (!is_multisite() || !is_network_admin()) {
            return;
        }

        // Add main network menu
        add_menu_page(
            __('Softworld SEO Network', 'softworld-ai-seo'),
            __('Softworld SEO Network', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network',
            [$this, 'render_network_admin_page'],
            'dashicons-chart-area',
            80
        );

        // Add submenu items
        add_submenu_page(
            'softworld-ai-seo-network',
            __('Network Dashboard', 'softworld-ai-seo'),
            __('Dashboard', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network',
            [$this, 'render_network_admin_page']
        );

        add_submenu_page(
            'softworld-ai-seo-network',
            __('License Management', 'softworld-ai-seo'),
            __('License Management', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network-license',
            [$this, 'render_license_page']
        );

        add_submenu_page(
            'softworld-ai-seo-network',
            __('Network Settings', 'softworld-ai-seo'),
            __('Network Settings', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network-settings',
            [$this, 'render_network_settings_page']
        );

        // NEW: NGINX Rules page
        add_submenu_page(
            'softworld-ai-seo-network',
            __('NGINX Rules', 'softworld-ai-seo'),
            __('NGINX Rules', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network-nginx',
            [$this, 'render_nginx_rules_page']
        );

        // NEW: Network Robots.txt page
        add_submenu_page(
            'softworld-ai-seo-network',
            __('Robots.txt Manager', 'softworld-ai-seo'),
            __('Robots.txt Manager', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network-robots',
            [$this, 'render_network_robots_page']
        );

        add_submenu_page(
            'softworld-ai-seo-network',
            __('Import/Export', 'softworld-ai-seo'),
            __('Import/Export', 'softworld-ai-seo'),
            'manage_network',
            'softworld-ai-seo-network-import-export',
            [$this, 'render_import_export_page']
        );
    }

    /**
     * Render Restricted Dashboard for sites without override permission
     */
    public function render_restricted_dashboard()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Softworld SEO', 'softworld-ai-seo'); ?></h1>
            <div class="notice notice-info">
                <p><?php _e('This site uses network-managed SEO settings. Contact your network administrator for configuration changes.', 'softworld-ai-seo'); ?>
                </p>
            </div>

            <div class="softworld-restricted-info">
                <h2><?php _e('Current SEO Settings', 'softworld-ai-seo'); ?></h2>
                <p><?php _e('The following SEO settings are being applied from the network configuration:', 'softworld-ai-seo'); ?>
                </p>

                <?php
                $network_settings = get_site_option('softworld_ai_seo_network_settings', []);
                if (!empty($network_settings)):
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Setting', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Value', 'softworld-ai-seo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($network_settings['default_seo_title'])): ?>
                                <tr>
                                    <td><?php _e('Default SEO Title', 'softworld-ai-seo'); ?></td>
                                    <td><?php echo esc_html($network_settings['default_seo_title']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($network_settings['default_seo_description'])): ?>
                                <tr>
                                    <td><?php _e('Default SEO Description', 'softworld-ai-seo'); ?></td>
                                    <td><?php echo esc_html($network_settings['default_seo_description']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><?php _e('Ping Search Engines', 'softworld-ai-seo'); ?></td>
                                <td><?php echo !empty($network_settings['ping_search_engines']) ? __('Enabled', 'softworld-ai-seo') : __('Disabled', 'softworld-ai-seo'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Include Posts in Sitemap', 'softworld-ai-seo'); ?></td>
                                <td><?php echo !empty($network_settings['include_posts']) ? __('Enabled', 'softworld-ai-seo') : __('Disabled', 'softworld-ai-seo'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Include Pages in Sitemap', 'softworld-ai-seo'); ?></td>
                                <td><?php echo !empty($network_settings['include_pages']) ? __('Enabled', 'softworld-ai-seo') : __('Disabled', 'softworld-ai-seo'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No network settings configured yet.', 'softworld-ai-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_schema_admin()
    {
        $this->schema_manager->render_admin_page();
    }

    public function render_robots_admin()
    {
        $this->robots_txt_editor->render_admin_page();
    }

    public function render_ai_settings_admin()
    {
        ?>
        <div class="wrap">
            <h1>AI Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('softworld_ai_settings_group');
                do_settings_sections('softworldit-seo-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_dashboard()
    {
        echo '<div class="wrap"><h1>Softworld SEO Dashboard</h1>';
        echo '<p>Welcome to the SOFTWORLD AI SEO Plugin.</p></div>';
    }

    public function render_sitemap_admin()
    {
        // Define default settings
        $default_settings = [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => ['category', 'post_tag'],
            'include_cpt' => [],
            'priority_posts' => 0.7,
            'priority_pages' => 0.8,
            'priority_taxonomies' => 0.5,
            'priority_cpt' => 0.6,
            'frequency_posts' => 'weekly',
            'frequency_pages' => 'monthly',
            'frequency_taxonomies' => 'weekly',
            'frequency_cpt' => 'weekly',
            'ping_search_engines' => 1,
            'ping_google' => 1,
            'ping_bing' => 1,
            'ping_yandex' => 0,
            'exclude_images' => 0,
        ];

        // Get current settings with proper defaults
        $settings = get_option('softworld_sitemap_settings', $default_settings);
        $settings = wp_parse_args($settings, $default_settings);

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['regenerate_rules'])) {
                if (class_exists('SoftworldIT_SEO_Sitemap')) {
                    SoftworldIT_SEO_Sitemap::add_rewrite();
                    flush_rewrite_rules();
                    echo '<div class="updated notice"><p>Sitemap rules regenerated successfully.</p></div>';
                }
            } elseif (isset($_POST['save_settings'])) {
                // Save sitemap settings
                $new_settings = [
                    'include_posts' => isset($_POST['include_posts']) ? 1 : 0,
                    'include_pages' => isset($_POST['include_pages']) ? 1 : 0,
                    'excluded_posts' => isset($_POST['excluded_posts']) ? array_map('intval', $_POST['excluded_posts']) : [],
                    'excluded_pages' => isset($_POST['excluded_pages']) ? array_map('intval', $_POST['excluded_pages']) : [],
                    'include_taxonomies' => isset($_POST['include_taxonomies']) ? $_POST['include_taxonomies'] : [],
                    'include_cpt' => isset($_POST['include_cpt']) ? $_POST['include_cpt'] : [],
                    'priority_posts' => isset($_POST['priority_posts']) ? floatval($_POST['priority_posts']) : 0.7,
                    'priority_pages' => isset($_POST['priority_pages']) ? floatval($_POST['priority_pages']) : 0.8,
                    'priority_taxonomies' => isset($_POST['priority_taxonomies']) ? floatval($_POST['priority_taxonomies']) : 0.5,
                    'priority_cpt' => isset($_POST['priority_cpt']) ? floatval($_POST['priority_cpt']) : 0.6,
                    'frequency_posts' => isset($_POST['frequency_posts']) ? sanitize_text_field($_POST['frequency_posts']) : 'weekly',
                    'frequency_pages' => isset($_POST['frequency_pages']) ? sanitize_text_field($_POST['frequency_pages']) : 'monthly',
                    'frequency_taxonomies' => isset($_POST['frequency_taxonomies']) ? sanitize_text_field($_POST['frequency_taxonomies']) : 'weekly',
                    'frequency_cpt' => isset($_POST['frequency_cpt']) ? sanitize_text_field($_POST['frequency_cpt']) : 'weekly',
                    'ping_search_engines' => isset($_POST['ping_search_engines']) ? 1 : 0,
                    'ping_google' => isset($_POST['ping_google']) ? 1 : 0,
                    'ping_bing' => isset($_POST['ping_bing']) ? 1 : 0,
                    'ping_yandex' => isset($_POST['ping_yandex']) ? 1 : 0,
                    'exclude_images' => isset($_POST['exclude_images']) ? 1 : 0,
                ];

                // Ensure arrays are properly formatted
                $new_settings['include_taxonomies'] = is_array($new_settings['include_taxonomies']) ?
                    $new_settings['include_taxonomies'] : [];
                $new_settings['include_cpt'] = is_array($new_settings['include_cpt']) ?
                    $new_settings['include_cpt'] : [];

                update_option('softworld_sitemap_settings', $new_settings);
                $settings = $new_settings; // Update current settings for display
                echo '<div class="updated notice"><p>Sitemap settings saved successfully.</p></div>';
            } elseif (isset($_POST['ping_now'])) {
                // Manually ping search engines
                if (class_exists('SoftworldIT_SEO_Sitemap')) {
                    SoftworldIT_SEO_Sitemap::manual_ping_search_engines();
                    echo '<div class="updated notice"><p>Search engines pinged successfully.</p></div>';
                }
            }
        }

        // Get all public taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');

        // Get all custom post types
        $cpts = get_post_types([
            'public' => true,
            '_builtin' => false
        ], 'objects');

        // Get excluded posts and pages for display
        $excluded_posts = !empty($settings['excluded_posts']) ? get_posts([
            'post_type' => 'post',
            'post__in' => $settings['excluded_posts'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]) : [];

        $excluded_pages = !empty($settings['excluded_pages']) ? get_posts([
            'post_type' => 'page',
            'post__in' => $settings['excluded_pages'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]) : [];

        // Priority options
        $priority_options = [
            '1.0' => '1.0 - Highest',
            '0.9' => '0.9',
            '0.8' => '0.8 - High',
            '0.7' => '0.7',
            '0.6' => '0.6 - Medium',
            '0.5' => '0.5',
            '0.4' => '0.4 - Low',
            '0.3' => '0.3',
            '0.2' => '0.2',
            '0.1' => '0.1 - Lowest',
            '0.0' => '0.0 - Excluded',
        ];

        // Frequency options
        $frequency_options = [
            'always' => 'Always',
            'hourly' => 'Hourly',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'never' => 'Never',
        ];

        // Get ping log
        $ping_log = [];
        if (class_exists('SoftworldIT_SEO_Sitemap')) {
            $ping_log = SoftworldIT_SEO_Sitemap::get_ping_log();
        }

        // Enqueue scripts and styles for the improved UI
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('softworldit-seo-sitemap-admin', plugin_dir_url(__FILE__) . 'assets/js/sitemap-admin.js', ['jquery', 'jquery-ui-autocomplete'], '1.0.0', true);
        wp_enqueue_style('softworldit-seo-sitemap-admin', plugin_dir_url(__FILE__) . 'assets/css/sitemap-admin.css', [], '1.0.0');

        // Localize script for AJAX
        wp_localize_script('softworldit-seo-sitemap-admin', 'softworld_sitemap', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('softworld_sitemap_nonce'),
            'search_posts_nonce' => wp_create_nonce('search_posts_nonce'),
            'search_pages_nonce' => wp_create_nonce('search_pages_nonce'),
        ]);

        ?>
        <div class="wrap">
            <h1>XML Sitemap Settings</h1>
            <p>Your sitemap is automatically generated and updated whenever you publish or update content.</p>
            <p><strong>Sitemap Index:</strong>
                <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank">
                    <?php echo esc_url(home_url('/sitemap.xml')); ?>
                </a>
            </p>

            <!-- Sitemap form content would continue here... -->
            <p>Sitemap configuration interface would be displayed here with all the form elements for settings.</p>

            <div class="softworld-sitemap-container">
                <form method="post" id="softworld-sitemap-form">
                    <div class="softworld-sitemap-section">
                        <h2>Content Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Include in Sitemap</th>
                                <td>
                                    <label class="softworld-checkbox-label" style="width: fit-content;">
                                        <input type="checkbox" name="include_posts" value="1" <?php checked($settings['include_posts'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Include Posts
                                    </label><br>
                                    <label class="softworld-checkbox-label" style="width: fit-content;">
                                        <input type="checkbox" name="include_pages" value="1" <?php checked($settings['include_pages'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Include Pages
                                    </label>
                                </td>
                            </tr>

                            <?php if (!empty($taxonomies)): ?>
                                <tr>
                                    <th scope="row">Include Taxonomies</th>
                                    <td class="softworld-checkbox-group">
                                        <?php foreach ($taxonomies as $taxonomy):
                                            $is_checked = in_array($taxonomy->name, (array) $settings['include_taxonomies']);
                                            ?>
                                            <label class="softworld-checkbox-label">
                                                <input type="checkbox" name="include_taxonomies[]"
                                                    value="<?php echo esc_attr($taxonomy->name); ?>" <?php checked($is_checked); ?>>
                                                <span class="checkmark"></span>
                                                <?php echo esc_html($taxonomy->labels->name); ?>
                                                (<?php echo esc_html($taxonomy->name); ?>)
                                            </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($cpts)): ?>
                                <tr>
                                    <th scope="row">Include Custom Post Types</th>
                                    <td class="softworld-checkbox-group">
                                        <?php foreach ($cpts as $cpt):
                                            $is_checked = in_array($cpt->name, (array) $settings['include_cpt']);
                                            ?>
                                            <label class="softworld-checkbox-label">
                                                <input type="checkbox" name="include_cpt[]" value="<?php echo esc_attr($cpt->name); ?>"
                                                    <?php checked($is_checked); ?>>
                                                <span class="checkmark"></span>
                                                <?php echo esc_html($cpt->labels->name); ?> (<?php echo esc_html($cpt->name); ?>)
                                            </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="softworld-sitemap-section">
                        <h2>Priority & Frequency Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Posts Priority</th>
                                <td>
                                    <select name="priority_posts" class="regular-text">
                                        <?php foreach ($priority_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['priority_posts'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Posts Frequency</th>
                                <td>
                                    <select name="frequency_posts" class="regular-text">
                                        <?php foreach ($frequency_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['frequency_posts'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Pages Priority</th>
                                <td>
                                    <select name="priority_pages" class="regular-text">
                                        <?php foreach ($priority_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['priority_pages'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Pages Frequency</th>
                                <td>
                                    <select name="frequency_pages" class="regular-text">
                                        <?php foreach ($frequency_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['frequency_pages'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Taxonomies Priority</th>
                                <td>
                                    <select name="priority_taxonomies" class="regular-text">
                                        <?php foreach ($priority_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['priority_taxonomies'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Taxonomies Frequency</th>
                                <td>
                                    <select name="frequency_taxonomies" class="regular-text">
                                        <?php foreach ($frequency_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['frequency_taxonomies'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Custom Post Types Priority</th>
                                <td>
                                    <select name="priority_cpt" class="regular-text">
                                        <?php foreach ($priority_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['priority_cpt'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Custom Post Types Frequency</th>
                                <td>
                                    <select name="frequency_cpt" class="regular-text">
                                        <?php foreach ($frequency_options as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['frequency_cpt'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="softworld-sitemap-section">
                        <h2>Search Engine Ping Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Ping Search Engines</th>
                                <td>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="ping_search_engines" value="1" <?php checked($settings['ping_search_engines'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Automatically ping search engines when content is updated
                                    </label>
                                    <p class="description">When enabled, search engines will be notified automatically when you
                                        publish or update content.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Search Engines to Ping</th>
                                <td>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="ping_google" value="1" <?php checked($settings['ping_google'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Google
                                    </label><br>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="ping_bing" value="1" <?php checked($settings['ping_bing'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Bing
                                    </label><br>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="ping_yandex" value="1" <?php checked($settings['ping_yandex'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Yandex
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Manual Ping</th>
                                <td>
                                    <button type="submit" name="ping_now" class="button button-secondary">Ping Search Engines
                                        Now</button>
                                    <p class="description">Manually notify search engines about your sitemap updates.</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="softworld-sitemap-section">
                        <h2>Image Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Exclude Images</th>
                                <td>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="exclude_images" value="1" <?php checked($settings['exclude_images'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Exclude images from sitemap
                                    </label>
                                    <p class="description">When enabled, images will not be included in your sitemap.</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="softworld-sitemap-section">
                        <h2>Exclusion Settings</h2>

                        <div class="softworld-exclusion-panel">
                            <h3>Exclude Posts</h3>
                            <div class="softworld-search-box">
                                <input type="text" id="softworld-post-search" placeholder="Search posts to exclude..."
                                    class="softworld-search-input">
                                <button type="button" id="softworld-search-post-btn"
                                    class="button button-secondary">Search</button>
                            </div>
                            <div id="softworld-post-results" class="softworld-search-results" style="display: none;"></div>

                            <div class="softworld-excluded-items">
                                <h4>Currently Excluded Posts</h4>
                                <div id="softworld-excluded-posts" class="softworld-excluded-list">
                                    <?php if (!empty($excluded_posts)): ?>
                                        <?php foreach ($excluded_posts as $post): ?>
                                            <div class="softworld-excluded-item" data-id="<?php echo $post->ID; ?>">
                                                <span class="softworld-item-title"><?php echo esc_html($post->post_title); ?></span>
                                                <span class="softworld-item-id">(ID: <?php echo $post->ID; ?>)</span>
                                                <button type="button" class="softworld-remove-item">Remove</button>
                                                <input type="hidden" name="excluded_posts[]" value="<?php echo $post->ID; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="softworld-no-items">No posts excluded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="softworld-exclusion-panel">
                            <h3>Exclude Pages</h3>
                            <div class="softworld-search-box">
                                <input type="text" id="softworld-page-search" placeholder="Search pages to exclude..."
                                    class="softworld-search-input">
                                <button type="button" id="softworld-search-page-btn"
                                    class="button button-secondary">Search</button>
                            </div>
                            <div id="softworld-page-results" class="softworld-search-results" style="display: none;"></div>

                            <div class="softworld-excluded-items">
                                <h4>Currently Excluded Pages</h4>
                                <div id="softworld-excluded-pages" class="softworld-excluded-list">
                                    <?php if (!empty($excluded_pages)): ?>
                                        <?php foreach ($excluded_pages as $page): ?>
                                            <div class="softworld-excluded-item" data-id="<?php echo $page->ID; ?>">
                                                <span class="softworld-item-title"><?php echo esc_html($page->post_title); ?></span>
                                                <span class="softworld-item-id">(ID: <?php echo $page->ID; ?>)</span>
                                                <button type="button" class="softworld-remove-item">Remove</button>
                                                <input type="hidden" name="excluded_pages[]" value="<?php echo $page->ID; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="softworld-no-items">No pages excluded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
                </form>
            </div>

            <div class="softworld-sitemap-status">
                <h2>Current Status</h2>
                <div class="softworld-status-cards">
                    <div class="softworld-status-card">
                        <h3>Content Types</h3>
                        <ul>
                            <li><?php echo $settings['include_posts'] ? '✅' : '❌'; ?> Posts included</li>
                            <li><?php echo $settings['include_pages'] ? '✅' : '❌'; ?> Pages included</li>
                            <li>Taxonomies: <?php echo count((array) $settings['include_taxonomies']); ?> included</li>
                            <li>Custom Post Types: <?php echo count((array) $settings['include_cpt']); ?> included</li>
                        </ul>
                    </div>
                    <div class="softworld-status-card">
                        <h3>Priority Settings</h3>
                        <ul>
                            <li>Posts: <?php echo $settings['priority_posts']; ?></li>
                            <li>Pages: <?php echo $settings['priority_pages']; ?></li>
                            <li>Taxonomies: <?php echo $settings['priority_taxonomies']; ?></li>
                            <li>Custom Post Types: <?php echo $settings['priority_cpt']; ?></li>
                        </ul>
                    </div>
                    <div class="softworld-status-card">
                        <h3>Frequency Settings</h3>
                        <ul>
                            <li>Posts: <?php echo ucfirst($settings['frequency_posts']); ?></li>
                            <li>Pages: <?php echo ucfirst($settings['frequency_pages']); ?></li>
                            <li>Taxonomies: <?php echo ucfirst($settings['frequency_taxonomies']); ?></li>
                            <li>Custom Post Types: <?php echo ucfirst($settings['frequency_cpt']); ?></li>
                        </ul>
                    </div>
                    <div class="softworld-status-card">
                        <h3>Ping Settings</h3>
                        <ul>
                            <li>Auto Ping: <?php echo $settings['ping_search_engines'] ? '✅' : '❌'; ?></li>
                            <li>Google: <?php echo $settings['ping_google'] ? '✅' : '❌'; ?></li>
                            <li>Bing: <?php echo $settings['ping_bing'] ? '✅' : '❌'; ?></li>
                            <li>Yandex: <?php echo $settings['ping_yandex'] ? '✅' : '❌'; ?></li>
                            <li>Images: <?php echo $settings['exclude_images'] ? '❌ Excluded' : '✅ Included'; ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (!empty($ping_log)): ?>
                <div class="softworld-sitemap-section">
                    <h2>Ping Log</h2>
                    <div class="softworld-ping-log">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Search Engine</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($ping_log) as $log_entry): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log_entry['time'])); ?></td>
                                        <td><?php echo ucfirst($log_entry['engine']); ?></td>
                                        <td><?php echo ucfirst($log_entry['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="softworld-sitemap-actions">
                <form method="post">
                    <?php submit_button('Regenerate Sitemap Rules', 'secondary', 'regenerate_rules'); ?>
                </form>
                <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" style="margin-top: 6px;" target="_blank"
                    class="button button-primary">View Sitemap</a>
            </div>

        </div>
        <?php
    }
}

// Initialize plugin
$GLOBALS['softworld_ai_seo_plugin'] = new Softworld_AI_SEO_Plugin();

/**
 * Add this function to check multisite licensing
 */
function softworld_ai_seo_is_license_valid()
{
    if (!is_multisite()) {
        // For single site, check individual license
        return get_option('softworld_ai_seo_license_valid', false);
    }

    // For multisite, check network license
    $license_info = get_site_option('softworld_ai_seo_network_license', []);
    return !empty($license_info['valid']);
}

/**
 * Add this filter to modify settings retrieval in multisite
 */
add_filter('option_softworld_ai_seo_title', function ($value, $option) {
    global $softworld_ai_seo_plugin;
    if ($softworld_ai_seo_plugin && method_exists($softworld_ai_seo_plugin, 'get_effective_setting')) {
        return $softworld_ai_seo_plugin->get_effective_setting($option, $value);
    }
    return $value;
}, 10, 2);

add_filter('option_softworld_ai_seo_description', function ($value, $option) {
    global $softworld_ai_seo_plugin;
    if ($softworld_ai_seo_plugin && method_exists($softworld_ai_seo_plugin, 'get_effective_setting')) {
        return $softworld_ai_seo_plugin->get_effective_setting($option, $value);
    }
    return $value;
}, 10, 2);