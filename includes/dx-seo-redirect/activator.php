<?php
if (!defined('ABSPATH')) exit;

class DXSEO_Activator {
    public static function activate(){
        global $wpdb; 
        $table = $wpdb->prefix.'dxseo_redirects';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            from_path TEXT NOT NULL,
            to_path TEXT NULL,
            regex TINYINT(1) DEFAULT 0,
            status_code INT DEFAULT 301,
            active TINYINT(1) DEFAULT 1,
            start_at DATETIME NULL,
            end_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            hits BIGINT UNSIGNED DEFAULT 0,
            last_hit DATETIME NULL,
            note TEXT NULL,
            PRIMARY KEY (id),
            INDEX(active),
            INDEX(status_code)
        ) $charset;";

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Default options
        add_option('dxseo_settings', [
            'deleted_behavior' => '410', // 410 | homepage | parent
            'autocreate_slug_redirect' => 1,
            'apply_to_404_only' => 0,
            'disallow_params' => ['utm_*','gclid','fbclid','replytocom'],
            'noindex_patterns' => [],
            'gone_patterns' => [],
            'disable_media_pages' => 1,
            'strip_replytocom' => 1
        ]);
    }
}
