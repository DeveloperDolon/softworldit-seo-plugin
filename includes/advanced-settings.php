<?php
/**
 * Advanced Settings Manager
 * Handles SEO email reports and query arg monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Advanced_Settings
{
    private $text_domain;

    public function __construct($text_domain = 'softworld-ai-seo')
    {
        $this->text_domain = $text_domain;
        
        // Initialize hooks
        add_action('init', [$this, 'init']);
        add_action('wp_ajax_softworld_send_test_email', [$this, 'ajax_send_test_email']);
        add_action('wp_ajax_softworld_clear_query_logs', [$this, 'ajax_clear_query_logs']);
        
        // Schedule email reports
        add_action('wp', [$this, 'schedule_email_reports']);
        add_action('softworld_send_seo_report', [$this, 'send_scheduled_seo_report']);
        
        // Monitor query arguments
        add_action('init', [$this, 'monitor_query_args'], 1);
        
        // Create database tables on activation
        register_activation_hook(AI_SEO_PLUGIN_PATH . 'softworld-ai-seo-plugin.php', [$this, 'create_tables']);
    }

    public function init()
    {
        // Initialize advanced settings features
    }

    /**
     * Create database tables for query monitoring
     */
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Query args monitoring table
        $query_args_table = $wpdb->prefix . 'softworld_query_args';
        
        $sql = "CREATE TABLE $query_args_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(500) NOT NULL,
            query_args text NOT NULL,
            first_seen datetime DEFAULT CURRENT_TIMESTAMP,
            last_seen datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            hit_count int(11) DEFAULT 1,
            status enum('new','reviewed','whitelisted','blacklisted') DEFAULT 'new',
            impact_score int(3) DEFAULT 0,
            PRIMARY KEY (id),
            KEY url_index (url(191)),
            KEY status_index (status),
            KEY last_seen_index (last_seen)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // SEO reports log table
        $reports_table = $wpdb->prefix . 'softworld_seo_reports';
        
        $sql2 = "CREATE TABLE $reports_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            report_type varchar(50) NOT NULL,
            sent_date datetime DEFAULT CURRENT_TIMESTAMP,
            recipient_email varchar(255) NOT NULL,
            status enum('sent','failed') DEFAULT 'sent',
            report_data longtext,
            PRIMARY KEY (id),
            KEY sent_date_index (sent_date),
            KEY report_type_index (report_type)
        ) $charset_collate;";

        dbDelta($sql2);
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handle_form_submission();
        }

        $settings = $this->get_settings();
        $query_stats = $this->get_query_args_stats();
        $recent_reports = $this->get_recent_reports();
        
        ?>
        <div class="wrap softworld-advanced-settings">
            <h1><?php _e('Advanced SEO Settings', $this->text_domain); ?></h1>
            
            <div class="softworld-settings-container">
                <!-- Email Reports Section -->
                <div class="softworld-settings-section">
                    <h2><?php _e('SEO Email Reports', $this->text_domain); ?></h2>
                    <form method="post" id="softworld-email-reports-form">
                        <?php wp_nonce_field('softworld_advanced_settings', 'softworld_nonce'); ?>
                        <input type="hidden" name="action" value="save_email_settings">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Email Reports', $this->text_domain); ?></th>
                                <td>
                                    <label class="softworld-toggle">
                                        <input type="checkbox" name="enable_email_reports" value="1" 
                                               <?php checked($settings['enable_email_reports'], 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <p class="description"><?php _e('Automatically send SEO performance reports via email', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Report Recipients', $this->text_domain); ?></th>
                                <td>
                                    <textarea name="report_recipients" rows="3" class="large-text" 
                                              placeholder="admin@example.com, seo@example.com"><?php echo esc_textarea($settings['report_recipients']); ?></textarea>
                                    <p class="description"><?php _e('Enter email addresses separated by commas', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Report Frequency', $this->text_domain); ?></th>
                                <td>
                                    <select name="report_frequency" class="regular-text">
                                        <option value="daily" <?php selected($settings['report_frequency'], 'daily'); ?>><?php _e('Daily', $this->text_domain); ?></option>
                                        <option value="weekly" <?php selected($settings['report_frequency'], 'weekly'); ?>><?php _e('Weekly', $this->text_domain); ?></option>
                                        <option value="monthly" <?php selected($settings['report_frequency'], 'monthly'); ?>><?php _e('Monthly', $this->text_domain); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Report Content', $this->text_domain); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="report_content[]" value="traffic_overview" 
                                                   <?php echo in_array('traffic_overview', (array)$settings['report_content']) ? 'checked' : ''; ?>>
                                            <?php _e('Traffic Overview', $this->text_domain); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="report_content[]" value="top_keywords" 
                                                   <?php echo in_array('top_keywords', (array)$settings['report_content']) ? 'checked' : ''; ?>>
                                            <?php _e('Top Keywords Performance', $this->text_domain); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="report_content[]" value="technical_issues" 
                                                   <?php echo in_array('technical_issues', (array)$settings['report_content']) ? 'checked' : ''; ?>>
                                            <?php _e('Technical SEO Issues', $this->text_domain); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="report_content[]" value="content_analysis" 
                                                   <?php echo in_array('content_analysis', (array)$settings['report_content']) ? 'checked' : ''; ?>>
                                            <?php _e('Content Analysis', $this->text_domain); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="report_content[]" value="query_monitoring" 
                                                   <?php echo in_array('query_monitoring', (array)$settings['report_content']) ? 'checked' : ''; ?>>
                                            <?php _e('Query Args Monitoring', $this->text_domain); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Test Email', $this->text_domain); ?></th>
                                <td>
                                    <button type="button" id="send-test-email" class="button button-secondary">
                                        <?php _e('Send Test Report', $this->text_domain); ?>
                                    </button>
                                    <span id="test-email-status"></span>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Email Settings', $this->text_domain), 'primary', 'save_email_settings'); ?>
                    </form>
                </div>

                <!-- Query Args Monitoring Section -->
                <div class="softworld-settings-section">
                    <h2><?php _e('Query Arguments Monitoring', $this->text_domain); ?></h2>
                    <form method="post" id="softworld-query-monitoring-form">
                        <?php wp_nonce_field('softworld_advanced_settings', 'softworld_nonce'); ?>
                        <input type="hidden" name="action" value="save_query_settings">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Query Monitoring', $this->text_domain); ?></th>
                                <td>
                                    <label class="softworld-toggle">
                                        <input type="checkbox" name="enable_query_monitoring" value="1" 
                                               <?php checked($settings['enable_query_monitoring'], 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <p class="description"><?php _e('Monitor and analyze URL query parameters for SEO impact', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Ignored Query Args', $this->text_domain); ?></th>
                                <td>
                                    <textarea name="ignored_query_args" rows="3" class="large-text" 
                                              placeholder="utm_source, utm_medium, fbclid"><?php echo esc_textarea($settings['ignored_query_args']); ?></textarea>
                                    <p class="description"><?php _e('Query parameters to ignore (comma-separated)', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Auto-cleanup Threshold', $this->text_domain); ?></th>
                                <td>
                                    <input type="number" name="cleanup_threshold" value="<?php echo esc_attr($settings['cleanup_threshold']); ?>" 
                                           min="1" max="10000" class="small-text"> <?php _e('days', $this->text_domain); ?>
                                    <p class="description"><?php _e('Automatically remove old query arg logs after this many days', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Impact Scoring', $this->text_domain); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_impact_scoring" value="1" 
                                               <?php checked($settings['enable_impact_scoring'], 1); ?>>
                                        <?php _e('Calculate SEO impact scores for query parameters', $this->text_domain); ?>
                                    </label>
                                    <p class="description"><?php _e('Analyze how query parameters affect search engine indexing', $this->text_domain); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Query Settings', $this->text_domain), 'primary', 'save_query_settings'); ?>
                    </form>
                </div>

                <!-- Query Args Statistics -->
                <div class="softworld-settings-section">
                    <h2><?php _e('Query Arguments Statistics', $this->text_domain); ?></h2>
                    <div class="softworld-stats-grid">
                        <div class="stat-card">
                            <h3><?php echo number_format($query_stats['total_unique_urls']); ?></h3>
                            <p><?php _e('Unique URLs Monitored', $this->text_domain); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo number_format($query_stats['total_query_args']); ?></h3>
                            <p><?php _e('Total Query Parameters', $this->text_domain); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo number_format($query_stats['high_impact_args']); ?></h3>
                            <p><?php _e('High Impact Parameters', $this->text_domain); ?></p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo number_format($query_stats['recent_activity']); ?></h3>
                            <p><?php _e('Last 7 Days Activity', $this->text_domain); ?></p>
                        </div>
                    </div>
                    
                    <div class="query-args-table-container">
                        <div class="table-actions">
                            <button type="button" id="clear-query-logs" class="button button-secondary">
                                <?php _e('Clear Old Logs', $this->text_domain); ?>
                            </button>
                            <button type="button" id="export-query-data" class="button button-secondary">
                                <?php _e('Export Data', $this->text_domain); ?>
                            </button>
                        </div>
                        
                        <?php $this->render_query_args_table(); ?>
                    </div>
                </div>

                <!-- Recent Reports -->
                <div class="softworld-settings-section">
                    <h2><?php _e('Recent Email Reports', $this->text_domain); ?></h2>
                    <div class="reports-table-container">
                        <?php $this->render_recent_reports_table($recent_reports); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle form submissions
     */
    private function handle_form_submission()
    {
        if (!wp_verify_nonce($_POST['softworld_nonce'], 'softworld_advanced_settings')) {
            wp_die(__('Security check failed', $this->text_domain));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action', $this->text_domain));
        }

        $action = $_POST['action'];

        if ($action === 'save_email_settings') {
            $this->save_email_settings();
        } elseif ($action === 'save_query_settings') {
            $this->save_query_settings();
        }
    }

    /**
     * Save email settings
     */
    private function save_email_settings()
    {
        $settings = [
            'enable_email_reports' => isset($_POST['enable_email_reports']) ? 1 : 0,
            'report_recipients' => sanitize_textarea_field($_POST['report_recipients']),
            'report_frequency' => sanitize_text_field($_POST['report_frequency']),
            'report_content' => isset($_POST['report_content']) ? array_map('sanitize_text_field', $_POST['report_content']) : []
        ];

        update_option('softworld_email_report_settings', $settings);
        
        // Reschedule email reports
        $this->reschedule_email_reports($settings['report_frequency']);
        
        add_action('admin_notices', function() {
            echo '<div class="updated notice"><p>' . __('Email settings saved successfully!', $this->text_domain) . '</p></div>';
        });
    }

    /**
     * Save query monitoring settings
     */
    private function save_query_settings()
    {
        $settings = [
            'enable_query_monitoring' => isset($_POST['enable_query_monitoring']) ? 1 : 0,
            'ignored_query_args' => sanitize_textarea_field($_POST['ignored_query_args']),
            'cleanup_threshold' => intval($_POST['cleanup_threshold']),
            'enable_impact_scoring' => isset($_POST['enable_impact_scoring']) ? 1 : 0
        ];

        update_option('softworld_query_monitoring_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="updated notice"><p>' . __('Query monitoring settings saved successfully!', $this->text_domain) . '</p></div>';
        });
    }

    /**
     * Get current settings with defaults
     */
    private function get_settings()
    {
        $email_defaults = [
            'enable_email_reports' => 0,
            'report_recipients' => get_option('admin_email'),
            'report_frequency' => 'weekly',
            'report_content' => ['traffic_overview', 'technical_issues']
        ];

        $query_defaults = [
            'enable_query_monitoring' => 1,
            'ignored_query_args' => 'utm_source,utm_medium,utm_campaign,utm_content,utm_term,fbclid,gclid,ref,_ga',
            'cleanup_threshold' => 90,
            'enable_impact_scoring' => 1
        ];

        $email_settings = wp_parse_args(get_option('softworld_email_report_settings', []), $email_defaults);
        $query_settings = wp_parse_args(get_option('softworld_query_monitoring_settings', []), $query_defaults);

        return array_merge($email_settings, $query_settings);
    }

    /**
     * Monitor query arguments
     */
    public function monitor_query_args()
    {
        $settings = $this->get_settings();
        
        if (!$settings['enable_query_monitoring']) {
            return;
        }

        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        $current_url = $this->get_current_url();
        $query_args = $_GET;

        if (empty($query_args)) {
            return;
        }

        // Remove ignored query args
        $ignored_args = array_map('trim', explode(',', $settings['ignored_query_args']));
        foreach ($ignored_args as $arg) {
            unset($query_args[$arg]);
        }

        if (!empty($query_args)) {
            $this->log_query_args($current_url, $query_args, $settings);
        }
    }

    /**
     * Log query arguments to database
     */
    private function log_query_args($url, $query_args, $settings)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_query_args';
        $query_string = http_build_query($query_args);
        
        // Check if this URL + query combo already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE url = %s AND query_args = %s",
            $url, $query_string
        ));

        if ($existing) {
            // Update hit count and last seen
            $wpdb->update(
                $table_name,
                ['hit_count' => $existing->hit_count + 1],
                ['id' => $existing->id]
            );
        } else {
            // Insert new record
            $impact_score = $settings['enable_impact_scoring'] ? $this->calculate_impact_score($query_args) : 0;
            
            $wpdb->insert(
                $table_name,
                [
                    'url' => $url,
                    'query_args' => $query_string,
                    'impact_score' => $impact_score,
                    'hit_count' => 1
                ]
            );
        }
    }

    /**
     * Calculate SEO impact score for query parameters
     */
    private function calculate_impact_score($query_args)
    {
        $score = 0;
        $high_impact_params = ['p', 'page', 'category', 'tag', 'search', 'filter'];
        $medium_impact_params = ['sort', 'order', 'view', 'display'];
        
        foreach ($query_args as $param => $value) {
            if (in_array($param, $high_impact_params)) {
                $score += 10;
            } elseif (in_array($param, $medium_impact_params)) {
                $score += 5;
            } else {
                $score += 2;
            }
            
            // Longer parameter values typically have higher impact
            $score += min(strlen($value) / 10, 5);
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Get current URL
     */
    private function get_current_url()
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        // Remove query string from URL for storage
        return strtok($url, '?');
    }

    /**
     * Get query args statistics
     */
    private function get_query_args_stats()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'softworld_query_args';
        
        $stats = [
            'total_unique_urls' => 0,
            'total_query_args' => 0,
            'high_impact_args' => 0,
            'recent_activity' => 0
        ];

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return $stats;
        }

        $stats['total_unique_urls'] = $wpdb->get_var("SELECT COUNT(DISTINCT url) FROM $table_name");
        $stats['total_query_args'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $stats['high_impact_args'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE impact_score > 50");
        $stats['recent_activity'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        return $stats;
    }

    /**
     * Render query args table
     */
    private function render_query_args_table()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'softworld_query_args';
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY last_seen DESC LIMIT 50"
        );

        if (empty($results)) {
            echo '<p>' . __('No query arguments monitored yet.', $this->text_domain) . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('URL', $this->text_domain); ?></th>
                    <th><?php _e('Query Parameters', $this->text_domain); ?></th>
                    <th><?php _e('Hit Count', $this->text_domain); ?></th>
                    <th><?php _e('Impact Score', $this->text_domain); ?></th>
                    <th><?php _e('Last Seen', $this->text_domain); ?></th>
                    <th><?php _e('Status', $this->text_domain); ?></th>
                    <th><?php _e('Actions', $this->text_domain); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><code><?php echo esc_html($row->url); ?></code></td>
                        <td><code><?php echo esc_html($row->query_args); ?></code></td>
                        <td><?php echo number_format($row->hit_count); ?></td>
                        <td>
                            <span class="impact-score impact-<?php echo $row->impact_score > 50 ? 'high' : ($row->impact_score > 25 ? 'medium' : 'low'); ?>">
                                <?php echo $row->impact_score; ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y H:i', strtotime($row->last_seen)); ?></td>
                        <td><span class="status-<?php echo $row->status; ?>"><?php echo ucfirst($row->status); ?></span></td>
                        <td>
                            <button type="button" class="button button-small" onclick="updateQueryStatus(<?php echo $row->id; ?>, 'reviewed')">
                                <?php _e('Mark Reviewed', $this->text_domain); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Get recent reports
     */
    private function get_recent_reports()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'softworld_seo_reports';
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY sent_date DESC LIMIT 20"
        );
    }

    /**
     * Render recent reports table
     */
    private function render_recent_reports_table($reports)
    {
        if (empty($reports)) {
            echo '<p>' . __('No reports sent yet.', $this->text_domain) . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Report Type', $this->text_domain); ?></th>
                    <th><?php _e('Recipient', $this->text_domain); ?></th>
                    <th><?php _e('Sent Date', $this->text_domain); ?></th>
                    <th><?php _e('Status', $this->text_domain); ?></th>
                    <th><?php _e('Actions', $this->text_domain); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $report->report_type))); ?></td>
                        <td><?php echo esc_html($report->recipient_email); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($report->sent_date)); ?></td>
                        <td><span class="status-<?php echo $report->status; ?>"><?php echo ucfirst($report->status); ?></span></td>
                        <td>
                            <button type="button" class="button button-small" onclick="viewReportData(<?php echo $report->id; ?>)">
                                <?php _e('View Details', $this->text_domain); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Schedule email reports
     */
    public function schedule_email_reports()
    {
        $settings = $this->get_settings();
        
        if (!$settings['enable_email_reports']) {
            wp_clear_scheduled_hook('softworld_send_seo_report');
            return;
        }

        if (!wp_next_scheduled('softworld_send_seo_report')) {
            $this->reschedule_email_reports($settings['report_frequency']);
        }
    }

    /**
     * Reschedule email reports
     */
    private function reschedule_email_reports($frequency)
    {
        wp_clear_scheduled_hook('softworld_send_seo_report');
        
        $intervals = [
            'daily' => DAY_IN_SECONDS,
            'weekly' => WEEK_IN_SECONDS,
            'monthly' => MONTH_IN_SECONDS
        ];

        if (isset($intervals[$frequency])) {
            wp_schedule_event(time(), $frequency, 'softworld_send_seo_report');
        }
    }

    /**
     * Send scheduled SEO report
     */
    public function send_scheduled_seo_report()
    {
        $settings = $this->get_settings();
        
        if (!$settings['enable_email_reports']) {
            return;
        }

        $recipients = array_map('trim', explode(',', $settings['report_recipients']));
        $report_data = $this->generate_report_data($settings['report_content']);

        foreach ($recipients as $recipient) {
            if (is_email($recipient)) {
                $this->send_seo_report($recipient, $report_data, $settings['report_frequency']);
            }
        }
    }

    /**
     * Generate report data
     */
    private function generate_report_data($content_types)
    {
        $data = [];

        foreach ($content_types as $type) {
            switch ($type) {
                case 'traffic_overview':
                    $data['traffic'] = $this->get_traffic_overview();
                    break;
                case 'top_keywords':
                    $data['keywords'] = $this->get_top_keywords();
                    break;
                case 'technical_issues':
                    $data['issues'] = $this->get_technical_issues();
                    break;
                case 'content_analysis':
                    $data['content'] = $this->get_content_analysis();
                    break;
                case 'query_monitoring':
                    $data['query_args'] = $this->get_query_monitoring_summary();
                    break;
            }
        }

        return $data;
    }

    /**
     * Get traffic overview data
     */
    private function get_traffic_overview()
    {
        // This would integrate with analytics APIs or internal tracking
        // For now, return sample data
        return [
            'total_visits' => wp_count_posts('post')->publish * 10, // Simulated data
            'organic_traffic' => wp_count_posts('post')->publish * 7,
            'bounce_rate' => '45%',
            'avg_session_duration' => '2:30',
            'top_pages' => $this->get_top_performing_pages()
        ];
    }

    /**
     * Get top keywords data
     */
    private function get_top_keywords()
    {
        // This would integrate with Search Console API or keyword tracking
        return [
            'total_keywords' => 150,
            'avg_position' => 25.4,
            'clicks' => 1250,
            'impressions' => 45000,
            'top_keywords' => [
                ['keyword' => 'sample keyword 1', 'position' => 5, 'clicks' => 150],
                ['keyword' => 'sample keyword 2', 'position' => 8, 'clicks' => 120],
                ['keyword' => 'sample keyword 3', 'position' => 12, 'clicks' => 95]
            ]
        ];
    }

    /**
     * Get technical issues
     */
    private function get_technical_issues()
    {
        $issues = [];
        
        // Check for missing meta descriptions
        $missing_meta = get_posts([
            'post_type' => ['post', 'page'],
            'meta_query' => [
                [
                    'key' => '_softworld_ai_seo_description',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        if (!empty($missing_meta)) {
            $issues[] = [
                'type' => 'missing_meta_descriptions',
                'count' => count($missing_meta),
                'severity' => 'medium',
                'description' => sprintf(__('%d pages missing meta descriptions', $this->text_domain), count($missing_meta))
            ];
        }

        // Check for duplicate titles
        global $wpdb;
        $duplicate_titles = $wpdb->get_results(
            "SELECT meta_value, COUNT(*) as count 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_softworld_ai_seo_title' 
             GROUP BY meta_value 
             HAVING COUNT(*) > 1"
        );

        if (!empty($duplicate_titles)) {
            $total_duplicates = array_sum(array_column($duplicate_titles, 'count'));
            $issues[] = [
                'type' => 'duplicate_titles',
                'count' => $total_duplicates,
                'severity' => 'high',
                'description' => sprintf(__('%d pages with duplicate titles', $this->text_domain), $total_duplicates)
            ];
        }

        return $issues;
    }

    /**
     * Get content analysis data
     */
    private function get_content_analysis()
    {
        $recent_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $analysis = [
            'total_posts' => wp_count_posts('post')->publish,
            'avg_word_count' => 0,
            'posts_without_images' => 0,
            'posts_without_internal_links' => 0
        ];

        $total_words = 0;
        foreach ($recent_posts as $post) {
            $word_count = str_word_count(strip_tags($post->post_content));
            $total_words += $word_count;

            // Check for images
            if (!has_post_thumbnail($post->ID) && strpos($post->post_content, '<img') === false) {
                $analysis['posts_without_images']++;
            }

            // Check for internal links (simplified)
            if (strpos($post->post_content, home_url()) === false) {
                $analysis['posts_without_internal_links']++;
            }
        }

        $analysis['avg_word_count'] = !empty($recent_posts) ? round($total_words / count($recent_posts)) : 0;

        return $analysis;
    }

    /**
     * Get query monitoring summary
     */
    private function get_query_monitoring_summary()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'softworld_query_args';
        
        $summary = [
            'new_parameters' => 0,
            'high_impact_parameters' => 0,
            'total_monitored' => 0,
            'top_parameters' => []
        ];

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $summary['new_parameters'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE first_seen >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            
            $summary['high_impact_parameters'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE impact_score > 50"
            );
            
            $summary['total_monitored'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            $summary['top_parameters'] = $wpdb->get_results(
                "SELECT query_args, hit_count, impact_score 
                 FROM $table_name 
                 ORDER BY hit_count DESC 
                 LIMIT 5"
            );
        }

        return $summary;
    }

    /**
     * Get top performing pages
     */
    private function get_top_performing_pages()
    {
        $pages = get_posts([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'comment_count',
            'order' => 'DESC'
        ]);

        $top_pages = [];
        foreach ($pages as $page) {
            $top_pages[] = [
                'title' => $page->post_title,
                'url' => get_permalink($page->ID),
                'visits' => rand(100, 1000), // Simulated data
                'engagement' => rand(60, 95) . '%'
            ];
        }

        return $top_pages;
    }

    /**
     * Send SEO report email
     */
    private function send_seo_report($recipient, $report_data, $frequency)
    {
        $subject = sprintf(__('Your %s SEO Report - %s', $this->text_domain), 
            ucfirst($frequency), 
            get_bloginfo('name')
        );

        $template_path = AI_SEO_PLUGIN_PATH . 'templates/email-report.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $message = ob_get_clean();
        } else {
            $message = $this->get_default_email_template($report_data, $frequency);
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];

        $sent = wp_mail($recipient, $subject, $message, $headers);

        // Log the report
        $this->log_email_report($frequency, $recipient, $sent ? 'sent' : 'failed', $report_data);

        return $sent;
    }

    /**
     * Get default email template
     */
    private function get_default_email_template($report_data, $frequency)
    {
        $html = '<html><head><style>';
        $html .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }';
        $html .= '.header { background: #0073aa; color: white; padding: 20px; text-align: center; }';
        $html .= '.content { padding: 20px; }';
        $html .= '.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }';
        $html .= '.stat { display: inline-block; margin: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px; }';
        $html .= '.issue-high { color: #d63638; }';
        $html .= '.issue-medium { color: #dba617; }';
        $html .= '.issue-low { color: #00a32a; }';
        $html .= '</style></head><body>';

        $html .= '<div class="header">';
        $html .= '<h1>' . sprintf(__('Your %s SEO Report', $this->text_domain), ucfirst($frequency)) . '</h1>';
        $html .= '<p>' . get_bloginfo('name') . ' - ' . date('F j, Y') . '</p>';
        $html .= '</div>';

        $html .= '<div class="content">';

        // Traffic Overview
        if (isset($report_data['traffic'])) {
            $traffic = $report_data['traffic'];
            $html .= '<div class="section">';
            $html .= '<h2>' . __('Traffic Overview', $this->text_domain) . '</h2>';
            $html .= '<div class="stat"><strong>' . number_format($traffic['total_visits']) . '</strong><br>Total Visits</div>';
            $html .= '<div class="stat"><strong>' . number_format($traffic['organic_traffic']) . '</strong><br>Organic Traffic</div>';
            $html .= '<div class="stat"><strong>' . $traffic['bounce_rate'] . '</strong><br>Bounce Rate</div>';
            $html .= '<div class="stat"><strong>' . $traffic['avg_session_duration'] . '</strong><br>Avg. Session Duration</div>';
            $html .= '</div>';
        }

        // Technical Issues
        if (isset($report_data['issues'])) {
            $html .= '<div class="section">';
            $html .= '<h2>' . __('Technical SEO Issues', $this->text_domain) . '</h2>';
            if (empty($report_data['issues'])) {
                $html .= '<p style="color: #00a32a;">✓ No critical issues detected!</p>';
            } else {
                foreach ($report_data['issues'] as $issue) {
                    $class = 'issue-' . $issue['severity'];
                    $html .= '<p class="' . $class . '">• ' . $issue['description'] . '</p>';
                }
            }
            $html .= '</div>';
        }

        // Query Args Monitoring
        if (isset($report_data['query_args'])) {
            $query_data = $report_data['query_args'];
            $html .= '<div class="section">';
            $html .= '<h2>' . __('Query Parameters Monitoring', $this->text_domain) . '</h2>';
            $html .= '<p><strong>' . $query_data['new_parameters'] . '</strong> new parameters detected this week</p>';
            $html .= '<p><strong>' . $query_data['high_impact_parameters'] . '</strong> high-impact parameters found</p>';
            $html .= '<p><strong>' . $query_data['total_monitored'] . '</strong> total parameters monitored</p>';
            $html .= '</div>';
        }

        $html .= '<div class="section">';
        $html .= '<p>' . __('This report was generated automatically by the Softworld AI SEO Plugin.', $this->text_domain) . '</p>';
        $html .= '<p><a href="' . admin_url('admin.php?page=softworld-ai-seo-advanced-settings') . '">' . __('Manage your SEO settings', $this->text_domain) . '</a></p>';
        $html .= '</div>';

        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Log email report
     */
    private function log_email_report($report_type, $recipient, $status, $report_data)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_seo_reports';

        $wpdb->insert(
            $table_name,
            [
                'report_type' => $report_type,
                'recipient_email' => $recipient,
                'status' => $status,
                'report_data' => json_encode($report_data)
            ]
        );
    }

    /**
     * AJAX handler for sending test email
     */
 // Update the ajax_send_test_email method in your Softworld_AI_SEO_Advanced_Settings class
    public function ajax_send_test_email()
    {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Check nonce first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'softworld_advanced_settings')) {
            wp_send_json_error(__('Security check failed', $this->text_domain));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', $this->text_domain));
            return;
        }

        // Get settings
        $settings = $this->get_settings();
        
        // Validate recipients
        $recipients_raw = trim($settings['report_recipients']);
        if (empty($recipients_raw)) {
            wp_send_json_error(__('No email recipients configured. Please add at least one email address.', $this->text_domain));
            return;
        }

        $recipients = array_map('trim', explode(',', $recipients_raw));
        $valid_recipients = [];
        
        foreach ($recipients as $recipient) {
            if (is_email($recipient)) {
                $valid_recipients[] = $recipient;
            }
        }
        
        if (empty($valid_recipients)) {
            wp_send_json_error(__('No valid email addresses found. Please check your recipient list.', $this->text_domain));
            return;
        }

        // Generate test report data
        try {
            $test_data = $this->generate_test_report_data();
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to generate report data: ', $this->text_domain) . $e->getMessage());
            return;
        }

        // Enable email logging for debugging
        add_action('wp_mail_failed', array($this, 'log_email_error'));
        
        $sent_count = 0;
        $errors = [];
        
        foreach ($valid_recipients as $recipient) {
            try {
                $result = $this->send_test_seo_report($recipient, $test_data);
                if ($result) {
                    $sent_count++;
                } else {
                    $errors[] = sprintf(__('Failed to send to %s', $this->text_domain), $recipient);
                }
            } catch (Exception $e) {
                $errors[] = sprintf(__('Error sending to %s: %s', $this->text_domain), $recipient, $e->getMessage());
            }
        }

        // Return results
        if ($sent_count > 0) {
            $message = sprintf(__('Test email sent successfully to %d of %d recipients', $this->text_domain), 
                            $sent_count, count($valid_recipients));
            
            if (!empty($errors)) {
                $message .= '. ' . __('Errors:', $this->text_domain) . ' ' . implode(', ', $errors);
            }
            
            wp_send_json_success($message);
        } else {
            $error_message = __('Failed to send test email to any recipients.', $this->text_domain);
            if (!empty($errors)) {
                $error_message .= ' ' . __('Errors:', $this->text_domain) . ' ' . implode(', ', $errors);
            }
            wp_send_json_error($error_message);
        }
    }

    /**
     * Send test SEO report email with better error handling
     */
    private function send_test_seo_report($recipient, $report_data)
    {
        $subject = sprintf(__('[TEST] SEO Report - %s', $this->text_domain), get_bloginfo('name'));

        // Generate email content
        $message = $this->get_test_email_template($report_data);

        // Set headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . get_option('admin_email')
        ];

        // Additional debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Attempting to send email to: ' . $recipient);
            error_log('Subject: ' . $subject);
            error_log('Headers: ' . print_r($headers, true));
        }

        // Send email
        $sent = wp_mail($recipient, $subject, $message, $headers);

        // Log the attempt
        $this->log_email_report('test', $recipient, $sent ? 'sent' : 'failed', $report_data);

        return $sent;
    }

    private function generate_test_report_data()
    {
        return [
            'traffic' => [
                'total_visits' => 1234,
                'organic_traffic' => 987,
                'bounce_rate' => '42%',
                'avg_session_duration' => '2:15',
                'top_pages' => [
                    ['title' => 'Sample Page 1', 'visits' => 150, 'engagement' => '75%'],
                    ['title' => 'Sample Page 2', 'visits' => 120, 'engagement' => '68%']
                ]
            ],
            'keywords' => [
                'total_keywords' => 85,
                'avg_position' => 23.4,
                'clicks' => 456,
                'impressions' => 12500,
                'top_keywords' => [
                    ['keyword' => 'test keyword 1', 'position' => 5, 'clicks' => 50],
                    ['keyword' => 'test keyword 2', 'position' => 8, 'clicks' => 35]
                ]
            ],
            'issues' => [
                [
                    'type' => 'test_issue',
                    'count' => 3,
                    'severity' => 'medium',
                    'description' => 'This is a test technical issue for the email report'
                ]
            ],
            'content' => [
                'total_posts' => wp_count_posts('post')->publish,
                'avg_word_count' => 650,
                'posts_without_images' => 2,
                'posts_without_internal_links' => 1
            ],
            'query_args' => [
                'new_parameters' => 5,
                'high_impact_parameters' => 2,
                'total_monitored' => 25,
                'top_parameters' => [
                    (object)['query_args' => 'test=1&debug=true', 'hit_count' => 15, 'impact_score' => 75]
                ]
            ]
        ];
    }

    /**
     * Get simplified test email template
     */
    private function get_test_email_template($report_data)
    {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Test SEO Report</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border: 1px solid #ddd; border-radius: 8px; }
            .header { background: #0073aa; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { padding: 20px; }
            .stat { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 4px; }
            .footer { background: #f1f1f1; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🧪 Test SEO Report</h1>
                <p>' . esc_html($site_name) . ' - ' . date('F j, Y') . '</p>
            </div>
            
            <div class="content">
                <p><strong>This is a test email from your Softworld AI SEO Plugin!</strong></p>
                
                <div class="stat">
                    <h3>📊 Sample Traffic Data</h3>
                    <p>Total Visits: <strong>' . number_format($report_data['traffic']['total_visits']) . '</strong></p>
                    <p>Organic Traffic: <strong>' . number_format($report_data['traffic']['organic_traffic']) . '</strong></p>
                    <p>Bounce Rate: <strong>' . esc_html($report_data['traffic']['bounce_rate']) . '</strong></p>
                </div>
                
                <div class="stat">
                    <h3>🔍 Query Monitoring</h3>
                    <p>New Parameters: <strong>' . $report_data['query_args']['new_parameters'] . '</strong></p>
                    <p>High Impact: <strong>' . $report_data['query_args']['high_impact_parameters'] . '</strong></p>
                    <p>Total Monitored: <strong>' . $report_data['query_args']['total_monitored'] . '</strong></p>
                </div>
                
                <div class="stat">
                    <h3>✅ Email Configuration Test</h3>
                    <p>If you received this email, your email settings are working correctly!</p>
                </div>
            </div>
            
            <div class="footer">
                <p>Generated by <strong>Softworld AI SEO Plugin</strong></p>
                <p><a href="' . admin_url('admin.php?page=softworld-ai-seo-advanced-settings') . '">Manage Settings</a></p>
            </div>
        </div>
    </body>
    </html>';

        return $html;
    }


    /**
     * AJAX handler for clearing query logs
     */
    public function ajax_clear_query_logs()
    {
        check_ajax_referer('softworld_advanced_settings', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', $this->text_domain));
        }

        global $wpdb;
        
        $settings = $this->get_settings();
        $threshold_days = intval($settings['cleanup_threshold']);
        
        $table_name = $wpdb->prefix . 'softworld_query_args';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $threshold_days
        ));

        wp_send_json_success(sprintf(__('Cleared %d old query log entries', $this->text_domain), $deleted));
    }

    /**
     * Log email errors for debugging
     */
    public function log_email_error($wp_error)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Email Error: ' . $wp_error->get_error_message());
        }
        
        // Store error for display to user
        set_transient('softworld_email_error', $wp_error->get_error_message(), 300);
    }

    /**
     * Check email configuration
     */
    public function check_email_configuration()
    {
        $issues = [];
        
        // Check if wp_mail function exists
        if (!function_exists('wp_mail')) {
            $issues[] = 'wp_mail function is not available';
        }
        
        // Check admin email
        $admin_email = get_option('admin_email');
        if (empty($admin_email) || !is_email($admin_email)) {
            $issues[] = 'Invalid admin email address in WordPress settings';
        }
        
        // Check if SMTP is configured (basic check)
        $smtp_host = ini_get('SMTP');
        if (empty($smtp_host) && !defined('WPMS_SMTP_HOST')) {
            $issues[] = 'No SMTP server configured (this might be normal if using system mail)';
        }
        
        return $issues;
    }
}