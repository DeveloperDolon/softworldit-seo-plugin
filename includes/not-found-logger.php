<?php

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Not_Found_Logger
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_seo_404_log';

        add_action('template_redirect', [$this, 'log_404_error']);
        add_action('admin_init', [$this, 'handle_clear_log']);
    }

    public static function create_log_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_seo_404_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            user_agent varchar(255) DEFAULT '' NOT NULL,
            referrer varchar(255) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id),
            KEY url (url)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function log_404_error()
    {
        if (!is_404()) {
            return;
        }

        global $wpdb;

        $url = esc_url_raw($_SERVER['REQUEST_URI']);
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255) : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';


        $wpdb->insert(
            $this->table_name,
            [
                'url' => $url,
                'timestamp' => current_time('mysql'),
                'user_agent' => $user_agent,
                'referrer' => $referrer
            ]
        );
    }

    public function get_log_entries($per_page = 20, $current_page = 1)
    {
        global $wpdb;
        $offset = ($current_page - 1) * $per_page;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
    }

    public function get_log_count()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(id) FROM {$this->table_name}");
    }

    public function handle_clear_log()
    {
        if (
            isset($_POST['action']) &&
            $_POST['action'] === 'clear_404_log' &&
            isset($_POST['clear_404_log_nonce']) &&
            wp_verify_nonce($_POST['clear_404_log_nonce'], 'clear_404_log_action')
        ) {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$this->table_name}");
            wp_redirect(admin_url('admin.php?page=softworld-ai-seo-404-log&message=cleared'));
            exit;
        }
    }


    public function render_admin_page()
    {
        require_once AI_SEO_PLUGIN_PATH . 'templates/admin-not-found-log.php';
    }
}