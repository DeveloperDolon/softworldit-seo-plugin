<?php
if (!defined('ABSPATH')) exit;

class DXSEO_Admin_Menu {
    private static $initialized = false;
    
    public static function init(){
        if (self::$initialized) return;
        self::$initialized = true;
        
        // Use admin_menu hook with high priority to ensure proper registration
        add_action('admin_menu', [__CLASS__, 'register_menus'], 20);
        add_action('wp_ajax_dxseo_save_redirect', [__CLASS__, 'ajax_save_redirect']);
        add_action('wp_ajax_dxseo_delete_redirect', [__CLASS__, 'ajax_delete_redirect']);
        add_action('admin_post_dxseo_export', [__CLASS__, 'handle_export']);
        add_action('admin_post_dxseo_import', [__CLASS__, 'handle_import']);
        add_action('admin_post_dxseo_save_settings', [__CLASS__, 'handle_settings']);
    }

    public static function register_menus(){
        // Use unique slugs that won't conflict with any rewrite rules
        add_submenu_page(
            'softworld-ai-seo', 
            'Regex Redirects', 
            'Regex Redirects', 
            'manage_options', 
            'sw-seo-regex-redirects', 
            [__CLASS__, 'render_regex_page']
        );
        
        add_submenu_page(
            'softworld-ai-seo', 
            'Slug & Deleted', 
            'Slug & Deleted', 
            'manage_options', 
            'sw-seo-slug-deleted', 
            [__CLASS__, 'render_slug_page']
        );
        
        add_submenu_page(
            'softworld-ai-seo', 
            'Schedule Redirect', 
            'Schedule Redirect', 
            'manage_options', 
            'sw-seo-schedule-redirect', 
            [__CLASS__, 'render_schedule_page']
        );
        
        add_submenu_page(
            'softworld-ai-seo', 
            'Export / Import', 
            'Export / Import', 
            'manage_options', 
            'sw-seo-export-import', 
            [__CLASS__, 'render_export_import_page']
        );
        
        add_submenu_page(
            'softworld-ai-seo', 
            'Crawl Cleanup', 
            'Crawl Cleanup', 
            'manage_options', 
            'sw-seo-crawl-cleanup', 
            [__CLASS__, 'render_crawl_cleanup_page']
        );
        
        add_submenu_page(
            'softworld-ai-seo', 
            'DX Settings', 
            'DX Settings', 
            'manage_options', 
            'sw-seo-dx-settings', 
            [__CLASS__, 'render_dx_settings_page']
        );
    }

    // Render methods that include the actual page files
    public static function render_regex_page() {
        $page_file = AI_SEO_PLUGIN_PATH . 'includes/dx-seo-redirect/page-regex.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="wrap"><h1>Regex Redirects</h1><p>Page file not found: ' . esc_html($page_file) . '</p></div>';
        }
    }
    
    public static function render_slug_page() {
        $page_file = AI_SEO_PLUGIN_PATH . 'includes/dx-seo-redirect/page-slug-change.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="wrap"><h1>Slug & Deleted</h1><p>Page file not found: ' . esc_html($page_file) . '</p></div>';
        }
    }
    
    public static function render_schedule_page() {
        $page_file = AI_SEO_PLUGIN_PATH . 'includes/dx-seo-redirect/page-schedule.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="wrap"><h1>Schedule Redirect</h1><p>Page file not found: ' . esc_html($page_file) . '</p></div>';
        }
    }
    
    public static function render_export_import_page() {
        $page_file = AI_SEO_PLUGIN_PATH . 'includes/dx-seo-redirect/page-export-import.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="wrap"><h1>Export / Import</h1><p>Page file not found: ' . esc_html($page_file) . '</p></div>';
        }
    }
    
    public static function render_crawl_cleanup_page() {
        $page_file = AI_SEO_PLUGIN_PATH . 'includes/dx-seo-redirect/page-crawl-cleanup.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="wrap"><h1>Crawl Cleanup</h1><p>Page file not found: ' . esc_html($page_file) . '</p></div>';
        }
    }
    
    public static function render_dx_settings_page() {
        $page_file = AI_SEO_PLUGIN_PATH . 'includes/dx-seo-redirect/page-settings.php';
        if (file_exists($page_file)) {
            include $page_file;
        } else {
            echo '<div class="wrap"><h1>DX Settings</h1><p>Page file not found: ' . esc_html($page_file) . '</p></div>';
        }
    }

    public static function dashboard(){
        echo '<div class="wrap dxseo-wrap"><h1 class="dxseo-h1">DX SEO</h1><p>Choose a tool from the submenu: Regex Redirects, Slug & Deleted, Schedule Redirect, Export/Import, Crawl Cleanup, Settings.</p></div>';
    }

    public static function handle_export(){
        if (!current_user_can('manage_options')) wp_die('No');
        check_admin_referer('dxseo_export');
        global $wpdb; $table = DXSEO_Helpers::table();
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        DXSEO_Helpers::csv_download($rows);
    }

    public static function handle_import(){
        if (!current_user_can('manage_options')) wp_die('No');
        check_admin_referer('dxseo_import');
        if (!isset($_FILES['dxseo_csv']) || !is_uploaded_file($_FILES['dxseo_csv']['tmp_name'])){
            wp_redirect(add_query_arg('import','fail', wp_get_referer())); exit;
        }
        $fh = fopen($_FILES['dxseo_csv']['tmp_name'], 'r');
        $header = fgetcsv($fh); // skip header
        global $wpdb; $table = DXSEO_Helpers::table();
        while(($row = fgetcsv($fh)) !== false){
            list($id,$from,$to,$regex,$code,$active,$start,$end,$note) = $row;
            $wpdb->insert($table, [
                'from_path'=>DXSEO_Helpers::sanitize_path($from),
                'to_path'=>$to,
                'regex'=>(int)$regex,
                'status_code'=>(int)$code,
                'active'=>(int)$active,
                'start_at'=>($start?:null),
                'end_at'=>($end?:null),
                'note'=>$note,
                'created_at'=>DXSEO_Helpers::now(),
                'updated_at'=>DXSEO_Helpers::now()
            ]);
        }
        fclose($fh);
        wp_redirect(add_query_arg('import','ok', wp_get_referer())); exit;
    }

    public static function ajax_save_redirect(){
        check_ajax_referer('dxseo_nonce','nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('No');
        global $wpdb; $table = DXSEO_Helpers::table();
        $data = [
            'from_path' => DXSEO_Helpers::sanitize_path($_POST['from_path'] ?? '/'),
            'to_path' => sanitize_text_field($_POST['to_path'] ?? ''),
            'regex' => intval($_POST['regex'] ?? 0),
            'status_code' => intval($_POST['status_code'] ?? 301),
            'active' => intval($_POST['active'] ?? 1),
            'start_at' => sanitize_text_field($_POST['start_at'] ?? null),
            'end_at' => sanitize_text_field($_POST['end_at'] ?? null),
            'note' => sanitize_text_field($_POST['note'] ?? '')
        ];
        if (!empty($_POST['id'])){
            $wpdb->update($table, $data, ['id'=>intval($_POST['id'])]);
        } else {
            $wpdb->insert($table, array_merge($data,[
                'created_at'=>DXSEO_Helpers::now(),
                'updated_at'=>DXSEO_Helpers::now()
            ]));
        }
        wp_send_json_success(['message'=>'Saved']);
    }

    public static function ajax_delete_redirect(){
        check_ajax_referer('dxseo_nonce','nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('No');
        global $wpdb; $table = DXSEO_Helpers::table();
        $wpdb->delete($table, ['id'=>intval($_POST['id'])]);
        wp_send_json_success(['message'=>'Deleted']);
    }

    public static function handle_settings() {
        // Handle settings form submission
        if (!current_user_can('manage_options')) wp_die('No');
        check_admin_referer('dxseo_settings');
        
        // Process settings here
        update_option('dxseo_settings', $_POST);
        
        wp_redirect(add_query_arg('settings', 'saved', wp_get_referer()));
        exit;
    }
}