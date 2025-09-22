<?php
/**
 * Plugin Name: DXSEO Troubleshooting & Support Infrastructure
 * Description: Adds a Troubleshooting & Support Infrastructure admin panel (debug toggle, sitemap fixer, JS error collector, Action Scheduler checks, PHP compatibility, one-time secure notes, temporary admin users, auto scan).
 * Version: 1.1.0
 * Author: Softworld It
 * Text Domain: dxseo-troubleshooter
 */
// echo $undefined_variable;
if (!defined('ABSPATH')) exit;

class DXSEO_Troubleshooter {

    public static function init() {
        // add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_dxseo_enable_runtime_debug', [__CLASS__, 'ajax_enable_runtime_debug']);
        add_action('wp_ajax_dxseo_flush_rewrites', [__CLASS__, 'ajax_flush_rewrites']);
        add_action('wp_ajax_dxseo_log_js_error', [__CLASS__, 'ajax_log_js_error']);
        add_action('admin_init', [__CLASS__, 'remove_expired_temp_users']);
        add_action('admin_post_dxseo_create_temp_user', [__CLASS__, 'handle_create_temp_user']);
        add_action('admin_post_dxseo_delete_temp_user', [__CLASS__, 'handle_delete_temp_user']);
        add_action('admin_post_dxseo_clear_js_errors', [__CLASS__, 'handle_clear_js_errors']);
        add_action('init', [__CLASS__, 'serve_one_time_secret']);
        // persist runtime debug
        add_action('admin_init', [__CLASS__, 'persist_runtime_debug']);
        // add auto scan on admin init
        add_action('admin_init', [__CLASS__, 'auto_scan_plugins']);
    }

    public static function admin_menu() {
        add_menu_page('Troubleshooting & Support Infrastructure', 'Troubleshooting & Support Infrastructure', 'manage_options', 'dxseo_troubleshoot', [__CLASS__, 'render_admin_page'], 'dashicons-admin-tools', 59);
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'dxseo_troubleshoot') === false) return;
        wp_enqueue_style('dxseo-admin-css', plugin_dir_url(__FILE__) . 'assets/css/dxseo-troubleshooter.css');
        wp_enqueue_script('dxseo-admin-js', plugin_dir_url(__FILE__) . 'assets/js/dxseo-troubleshooter.js', ['jquery'], null, true);
        wp_localize_script('dxseo-admin-js', 'dxseoAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dxseo_nonce'),
        ]);
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        include plugin_dir_path(__FILE__) . 'dxseo-admin-page.php';
    }

    // --- Runtime Debug ---
    public static function ajax_enable_runtime_debug() {
        check_ajax_referer('dxseo_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        update_option('dxseo_runtime_debug', time());
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        wp_send_json_success(['message' => 'Runtime debug enabled for this request (display_errors on). Use wp-config.php for persistent debug.']);
    }

    public static function persist_runtime_debug() {
        if (get_option('dxseo_runtime_debug')) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }
    }

    // --- Rewrite flush ---
    public static function ajax_flush_rewrites() {
        check_ajax_referer('dxseo_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        flush_rewrite_rules(true);
        wp_send_json_success(['message' => 'Rewrite rules flushed. If sitemap 404 persists, ensure your sitemap plugin (eg. Yoast) is active and permalinks are saved.']);
    }

    // --- JS error log ---
    public static function ajax_log_js_error() {
        check_ajax_referer('dxseo_nonce');
        $payload = wp_unslash($_POST['payload'] ?? '{}');
        $logs = get_option('dxseo_js_errors', []);
        $logs[] = ['time' => current_time('mysql'), 'payload' => $payload, 'ip' => $_SERVER['REMOTE_ADDR']];
        update_option('dxseo_js_errors', $logs);
        wp_send_json_success();
    }

    public static function handle_clear_js_errors() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        delete_option('dxseo_js_errors');
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot'));
        exit;
    }

    // --- Temporary admin users ---
    public static function handle_create_temp_user() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_create_temp_user');
        $username = sanitize_user($_POST['dx_username']);
        $email = sanitize_email($_POST['dx_email']);
        $days = intval($_POST['dx_days'] ?? 1);
        if (!$username || !$email) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=missing'));
            exit;
        }
        $password = wp_generate_password(12, true);
        $user_id = username_exists($username) ? null : wp_create_user($username, $password, $email);
        if (!$user_id) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=exists'));
            exit;
        }
        $user = new WP_User($user_id);
        $user->set_role('administrator');
        $expiry = time() + ($days * DAY_IN_SECONDS);
        update_user_meta($user_id, 'dxseo_temp_user_expires', $expiry);
        $temp_users = get_option('dxseo_temp_users', []);
        $temp_users[$user_id] = ['username'=>$username,'expires'=>$expiry];
        update_option('dxseo_temp_users', $temp_users);
        $redirect = admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=created&u=' . $user_id . '&p=' . rawurlencode($password));
        wp_redirect($redirect);
        exit;
    }

    public static function handle_delete_temp_user() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_delete_temp_user');
        $user_id = intval($_POST['dx_user_id']);
        if ($user_id && get_userdata($user_id)) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($user_id);
        }
        $temp_users = get_option('dxseo_temp_users', []);
        if (isset($temp_users[$user_id])) {
            unset($temp_users[$user_id]);
            update_option('dxseo_temp_users', $temp_users);
        }
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot'));
        exit;
    }

    public static function remove_expired_temp_users() {
        if (!current_user_can('manage_options')) return;
        $temp_users = get_option('dxseo_temp_users', []);
        $now = time();
        $changed = false;
        foreach ($temp_users as $uid => $meta) {
            if ($meta['expires'] <= $now) {
                if (get_userdata($uid)) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                    wp_delete_user($uid);
                }
                unset($temp_users[$uid]);
                $changed = true;
            }
        }
        if ($changed) update_option('dxseo_temp_users', $temp_users);
    }

    // --- One-time secret notes ---
    public static function serve_one_time_secret() {
        if (isset($_GET['dxseo_one_time_token'])) {
            $token = sanitize_text_field($_GET['dxseo_one_time_token']);
            $secrets = get_option('dxseo_one_time_secrets', []);
            if (!isset($secrets[$token])) {
                wp_die('Secret not found or already viewed.');
            }
            $data = $secrets[$token];
            $expires = $data['expires'] ?? 0;
            if (time() > $expires) {
                unset($secrets[$token]);
                update_option('dxseo_one_time_secrets', $secrets);
                wp_die('Secret expired.');
            }
            $payload = $data['payload'];
            unset($secrets[$token]);
            update_option('dxseo_one_time_secrets', $secrets);
            wp_die('<h2>One-time secret</h2><pre style="white-space:pre-wrap">' . esc_html($payload) . '</pre>');
        }
    }

    public static function create_one_time_secret($payload, $ttl_seconds=3600) {
        $token = wp_generate_password(24, false, false);
        $secrets = get_option('dxseo_one_time_secrets', []);
        $secrets[$token] = ['payload' => $payload, 'expires' => time() + $ttl_seconds];
        update_option('dxseo_one_time_secrets', $secrets);
        return $token;
    }

    // --- Auto scan (Yoast style) ---
    public static function auto_scan_plugins() {
        // Example: detect common SEO plugins
        $active_plugins = get_option('active_plugins', []);
        $results = [];
        foreach ($active_plugins as $p) {
            if (str_contains($p, 'wordpress-seo') || str_contains($p, 'all-in-one-seo-pack')) {
                $results[] = $p;
            }
        }
        if (!empty($results)) {
            update_option('dxseo_auto_scan_results', $results);
        }
    }
}
