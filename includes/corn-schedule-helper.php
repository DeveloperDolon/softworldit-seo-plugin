<?php
/**
 * WordPress Cron Schedules for Advanced Settings
 * Add this code to your main plugin file or create a separate file
 */

// Add custom cron intervals for email reports
add_filter('cron_schedules', 'softworld_add_cron_intervals');

function softworld_add_cron_intervals($schedules)
{
    $schedules['weekly'] = [
        'interval' => WEEK_IN_SECONDS,
        'display' => __('Once Weekly', 'softworld-ai-seo')
    ];
    
    $schedules['monthly'] = [
        'interval' => MONTH_IN_SECONDS,
        'display' => __('Once Monthly', 'softworld-ai-seo')
    ];

    return $schedules;
}

// Clean up old query args data automatically
add_action('wp', 'softworld_schedule_cleanup');

function softworld_schedule_cleanup()
{
    if (!wp_next_scheduled('softworld_cleanup_old_data')) {
        wp_schedule_event(time(), 'daily', 'softworld_cleanup_old_data');
    }
}

add_action('softworld_cleanup_old_data', 'softworld_perform_cleanup');

function softworld_perform_cleanup()
{
    $settings = get_option('softworld_query_monitoring_settings', []);
    $cleanup_threshold = isset($settings['cleanup_threshold']) ? intval($settings['cleanup_threshold']) : 90;
    
    global $wpdb;
    
    // Clean old query args data
    $query_table = $wpdb->prefix . 'softworld_query_args';
    if ($wpdb->get_var("SHOW TABLES LIKE '$query_table'") == $query_table) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $query_table WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $cleanup_threshold
        ));
    }
    
    // Clean old report logs (keep last 100 reports)
    $reports_table = $wpdb->prefix . 'softworld_seo_reports';
    if ($wpdb->get_var("SHOW TABLES LIKE '$reports_table'") == $reports_table) {
        $wpdb->query(
            "DELETE FROM $reports_table WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id FROM $reports_table ORDER BY sent_date DESC LIMIT 100
                ) AS temp
            )"
        );
    }
}

// Clean up scheduled events on plugin deactivation
register_deactivation_hook(__FILE__, 'softworld_cleanup_cron_events');

function softworld_cleanup_cron_events()
{
    wp_clear_scheduled_hook('softworld_send_seo_report');
    wp_clear_scheduled_hook('softworld_cleanup_old_data');
}

?>