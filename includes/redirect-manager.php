<?php

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Redirect_Manager
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_seo_redirects';

        add_action('admin_init', [$this, 'handle_form_actions']);
        add_action('template_redirect', [$this, 'execute_redirects']);
    }

    public static function create_redirects_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_seo_redirects';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source_url varchar(255) NOT NULL,
            target_url varchar(255) NOT NULL,
            redirect_type smallint(3) NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY source_url (source_url)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function handle_form_actions()
    {
        // Add a new redirect
        if (isset($_POST['add_redirect_nonce']) && wp_verify_nonce($_POST['add_redirect_nonce'], 'add_redirect')) {
            $this->add_redirect();
        }

        // Delete a redirect
        if (isset($_GET['action'], $_GET['redirect_id']) && $_GET['action'] == 'delete_redirect' && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_redirect_' . $_GET['redirect_id'])) {
            $this->delete_redirect(absint($_GET['redirect_id']));
        }
    }

    private function add_redirect()
    {
        global $wpdb;

        $source_url = esc_url_raw(trim($_POST['source_url']));
        $target_url = esc_url_raw(trim($_POST['target_url']));
        $redirect_type = in_array($_POST['redirect_type'], ['301', '302']) ? intval($_POST['redirect_type']) : 301;

        if (empty($source_url) || empty($target_url)) {
            return;
        }

        // Basic validation to prevent redirect loops
        if ($source_url === $target_url) {
            return;
        }

        $wpdb->insert(
            $this->table_name,
            [
                'source_url' => $source_url,
                'target_url' => $target_url,
                'redirect_type' => $redirect_type,
                'created_at' => current_time('mysql'),
            ]
        );
    }

    private function delete_redirect($redirect_id)
    {
        global $wpdb;
        $wpdb->delete($this->table_name, ['id' => $redirect_id], ['%d']);
    }

    public function get_all_redirects()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
    }

    public function execute_redirects()
    {
        global $wpdb;

        $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
        $request_url = home_url($request_uri);


        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT target_url, redirect_type FROM {$this->table_name} WHERE source_url = %s",
            $request_url
        ));

        if ($redirect) {
            wp_redirect($redirect->target_url, $redirect->redirect_type);
            exit;
        }
    }

    public function render_admin_page()
    {
        require_once AI_SEO_PLUGIN_PATH . 'templates/admin-redirects.php';
    }
}