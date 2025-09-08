<?php
/**
 * Advanced Settings Manager
 * Handles SEO email reports, query arg monitoring, admin UI customization, and shortcode processing
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
        
        // Admin UI customization hooks
        add_action('wp_loaded', [$this, 'init_admin_customizations']);
        
        // Shortcode processing hooks
        add_action('wp_head', [$this, 'process_shortcodes_in_metadata']);
        add_filter('wp_title', [$this, 'process_shortcodes_in_title'], 10, 2);
        add_filter('get_the_excerpt', [$this, 'process_shortcodes_in_excerpt'], 10, 2);
        
        // Create database tables on activation
        register_activation_hook(AI_SEO_PLUGIN_PATH . 'softworld-ai-seo-plugin.php', [$this, 'create_tables']);
    }

    public function init()
    {
        // Initialize advanced settings features
    }

    /**
     * Initialize admin customizations based on settings
     */
    public function init_admin_customizations()
    {
        $settings = $this->get_settings();
        
        if ($settings['hide_admin_bar']) {
            add_filter('show_admin_bar', '__return_false');
        }
        
        if ($settings['hide_admin_columns']) {
            add_action('admin_init', [$this, 'hide_admin_columns']);
        }
    }

    /**
     * Hide specific admin columns
     */
    public function hide_admin_columns()
    {
        $settings = $this->get_settings();
        $columns_to_hide = array_map('trim', explode(',', $settings['hidden_columns_list']));
        
        foreach ($columns_to_hide as $column) {
            if (empty($column)) continue;
            
            // Handle different post types and admin screens
            add_filter('manage_posts_columns', function($columns) use ($column) {
                unset($columns[$column]);
                return $columns;
            });
            
            add_filter('manage_pages_columns', function($columns) use ($column) {
                unset($columns[$column]);
                return $columns;
            });
            
            add_filter('manage_users_columns', function($columns) use ($column) {
                unset($columns[$column]);
                return $columns;
            });
        }
        
        // Add CSS to hide columns by class name if specified
        if (!empty($settings['hidden_columns_css'])) {
            add_action('admin_head', function() use ($settings) {
                echo '<style>' . esc_html($settings['hidden_columns_css']) . '</style>';
            });
        }
    }

    /**
     * Process shortcodes in metadata for head section
     */
    public function process_shortcodes_in_metadata()
    {
        $settings = $this->get_settings();
        
        if (!$settings['enable_shortcodes_in_meta']) {
            return;
        }
        
        global $post;
        
        if (!is_object($post)) {
            return;
        }
        
        // Process shortcodes in SEO title
        $seo_title = get_post_meta($post->ID, '_softworld_ai_seo_title', true);
        if (!empty($seo_title)) {
            $processed_title = do_shortcode($seo_title);
            // Temporarily store processed title for use in wp_head
            $this->temp_processed_title = $processed_title;
        }
        
        // Process shortcodes in meta description
        $meta_description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);
        if (!empty($meta_description)) {
            $processed_description = do_shortcode($meta_description);
            echo '<meta name="description" content="' . esc_attr($processed_description) . '">' . "\n";
        }
        
        // Process shortcodes in meta keywords
        $meta_keywords = get_post_meta($post->ID, '_softworld_ai_seo_keywords', true);
        if (!empty($meta_keywords)) {
            $processed_keywords = do_shortcode($meta_keywords);
            echo '<meta name="keywords" content="' . esc_attr($processed_keywords) . '">' . "\n";
        }
        
        // Process custom meta fields if enabled
        if ($settings['process_custom_meta_fields']) {
            $custom_fields = array_map('trim', explode(',', $settings['custom_meta_fields_list']));
            foreach ($custom_fields as $field) {
                if (empty($field)) continue;
                
                $field_value = get_post_meta($post->ID, $field, true);
                if (!empty($field_value)) {
                    $processed_value = do_shortcode($field_value);
                    // Store processed custom meta for potential use by other functions
                    $this->processed_custom_meta[$field] = $processed_value;
                }
            }
        }
    }

    /**
     * Process shortcodes in title
     */
    public function process_shortcodes_in_title($title, $sep = null)
    {
        $settings = $this->get_settings();
        
        if (!$settings['enable_shortcodes_in_meta']) {
            return $title;
        }
        
        // Use processed title if available
        if (isset($this->temp_processed_title)) {
            return $this->temp_processed_title;
        }
        
        return do_shortcode($title);
    }

    /**
     * Process shortcodes in excerpt/meta description
     */
    public function process_shortcodes_in_excerpt($excerpt, $post = null)
    {
        $settings = $this->get_settings();
        
        if (!$settings['enable_shortcodes_in_meta']) {
            return $excerpt;
        }
        
        return do_shortcode($excerpt);
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

                <!-- Admin UI Customization Section -->
                <div class="softworld-settings-section">
                    <h2><?php _e('Admin UI Customization', $this->text_domain); ?></h2>
                    <form method="post" id="softworld-admin-ui-form">
                        <?php wp_nonce_field('softworld_advanced_settings', 'softworld_nonce'); ?>
                        <input type="hidden" name="action" value="save_admin_ui_settings">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Hide Admin Bar', $this->text_domain); ?></th>
                                <td>
                                    <label class="softworld-toggle">
                                        <input type="checkbox" name="hide_admin_bar" value="1" 
                                               <?php checked($settings['hide_admin_bar'], 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <p class="description"><?php _e('Hide the WordPress admin bar from the frontend for all users', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Hide Admin Columns', $this->text_domain); ?></th>
                                <td>
                                    <label class="softworld-toggle">
                                        <input type="checkbox" name="hide_admin_columns" value="1" 
                                               <?php checked($settings['hide_admin_columns'], 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <p class="description"><?php _e('Hide specific columns in admin list tables', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Hidden Columns List', $this->text_domain); ?></th>
                                <td>
                                    <textarea name="hidden_columns_list" rows="3" class="large-text" 
                                              placeholder="author, date, comments, tags"><?php echo esc_textarea($settings['hidden_columns_list']); ?></textarea>
                                    <p class="description"><?php _e('Comma-separated list of column names to hide (e.g., author, date, comments)', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Custom CSS for Columns', $this->text_domain); ?></th>
                                <td>
                                    <textarea name="hidden_columns_css" rows="5" class="large-text" 
                                              placeholder=".column-author { display: none !important; }"><?php echo esc_textarea($settings['hidden_columns_css']); ?></textarea>
                                    <p class="description"><?php _e('Additional CSS rules to hide columns by class name or ID', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Hide From User Roles', $this->text_domain); ?></th>
                                <td>
                                    <fieldset>
                                        <?php
                                        $roles = wp_roles()->get_names();
                                        $hidden_roles = (array)$settings['hide_for_user_roles'];
                                        foreach ($roles as $role_key => $role_name):
                                        ?>
                                            <label>
                                                <input type="checkbox" name="hide_for_user_roles[]" value="<?php echo esc_attr($role_key); ?>" 
                                                       <?php echo in_array($role_key, $hidden_roles) ? 'checked' : ''; ?>>
                                                <?php echo esc_html($role_name); ?>
                                            </label><br>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <p class="description"><?php _e('Apply UI customizations only to selected user roles', $this->text_domain); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Admin UI Settings', $this->text_domain), 'primary', 'save_admin_ui_settings'); ?>
                    </form>
                </div>

                <!-- Shortcode Processing Section -->
                <div class="softworld-settings-section">
                    <h2><?php _e('Shortcode Processing in Metadata', $this->text_domain); ?></h2>
                    <form method="post" id="softworld-shortcode-form">
                        <?php wp_nonce_field('softworld_advanced_settings', 'softworld_nonce'); ?>
                        <input type="hidden" name="action" value="save_shortcode_settings">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Shortcodes in Meta', $this->text_domain); ?></th>
                                <td>
                                    <label class="softworld-toggle">
                                        <input type="checkbox" name="enable_shortcodes_in_meta" value="1" 
                                               <?php checked($settings['enable_shortcodes_in_meta'], 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <p class="description"><?php _e('Process shortcodes in SEO titles, descriptions, and other meta fields', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Process in Titles', $this->text_domain); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="process_in_titles" value="1" 
                                               <?php checked($settings['process_in_titles'], 1); ?>>
                                        <?php _e('Enable shortcode processing in SEO titles', $this->text_domain); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Process in Descriptions', $this->text_domain); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="process_in_descriptions" value="1" 
                                               <?php checked($settings['process_in_descriptions'], 1); ?>>
                                        <?php _e('Enable shortcode processing in meta descriptions', $this->text_domain); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Process Custom Meta Fields', $this->text_domain); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="process_custom_meta_fields" value="1" 
                                               <?php checked($settings['process_custom_meta_fields'], 1); ?>>
                                        <?php _e('Enable shortcode processing in custom meta fields', $this->text_domain); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Custom Meta Fields List', $this->text_domain); ?></th>
                                <td>
                                    <textarea name="custom_meta_fields_list" rows="3" class="large-text" 
                                              placeholder="_custom_field_1, _my_meta_field, custom_seo_data"><?php echo esc_textarea($settings['custom_meta_fields_list']); ?></textarea>
                                    <p class="description"><?php _e('Comma-separated list of custom meta field names to process shortcodes in', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Allowed Shortcodes', $this->text_domain); ?></th>
                                <td>
                                    <textarea name="allowed_shortcodes" rows="3" class="large-text" 
                                              placeholder="date, site_name, current_user, custom_shortcode"><?php echo esc_textarea($settings['allowed_shortcodes']); ?></textarea>
                                    <p class="description"><?php _e('Comma-separated list of shortcodes allowed in metadata (leave blank to allow all)', $this->text_domain); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Cache Processed Results', $this->text_domain); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="cache_processed_shortcodes" value="1" 
                                               <?php checked($settings['cache_processed_shortcodes'], 1); ?>>
                                        <?php _e('Cache processed shortcode results for better performance', $this->text_domain); ?>
                                    </label>
                                    <p class="description"><?php _e('Recommended for sites with many dynamic shortcodes', $this->text_domain); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save Shortcode Settings', $this->text_domain), 'primary', 'save_shortcode_settings'); ?>
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
            
            <!-- JavaScript for AJAX functionality -->
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Send test email
                $('#send-test-email').click(function() {
                    var button = $(this);
                    var status = $('#test-email-status');
                    
                    button.prop('disabled', true).text('<?php _e('Sending...', $this->text_domain); ?>');
                    status.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'softworld_send_test_email',
                            nonce: '<?php echo wp_create_nonce('softworld_advanced_settings'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                status.html('<span style="color: green;">✓ ' + response.data + '</span>');
                            } else {
                                status.html('<span style="color: red;">✗ ' + response.data + '</span>');
                            }
                        },
                        error: function() {
                            status.html('<span style="color: red;">✗ <?php _e('Request failed', $this->text_domain); ?></span>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Send Test Report', $this->text_domain); ?>');
                        }
                    });
                });
                
                // Clear query logs
                $('#clear-query-logs').click(function() {
                    if (!confirm('<?php _e('Are you sure you want to clear old query logs?', $this->text_domain); ?>')) {
                        return;
                    }
                    
                    var button = $(this);
                    button.prop('disabled', true).text('<?php _e('Clearing...', $this->text_domain); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'softworld_clear_query_logs',
                            nonce: '<?php echo wp_create_nonce('softworld_advanced_settings'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✓ ' + response.data);
                                location.reload();
                            } else {
                                alert('✗ ' + response.data);
                            }
                        },
                        error: function() {
                            alert('✗ <?php _e('Request failed', $this->text_domain); ?>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Clear Old Logs', $this->text_domain); ?>');
                        }
                    });
                });
                
                // Toggle sections based on checkbox states
                $('input[name="hide_admin_columns"]').change(function() {
                    var columnSettings = $(this).closest('table').find('tr').slice(2, 5);
                    if ($(this).is(':checked')) {
                        columnSettings.show();
                    } else {
                        columnSettings.hide();
                    }
                }).trigger('change');
                
                $('input[name="enable_shortcodes_in_meta"]').change(function() {
                    var shortcodeSettings = $(this).closest('table').find('tr').slice(1);
                    if ($(this).is(':checked')) {
                        shortcodeSettings.show();
                    } else {
                        shortcodeSettings.hide();
                    }
                }).trigger('change');
                
                $('input[name="process_custom_meta_fields"]').change(function() {
                    var customFieldsRow = $(this).closest('table').find('tr').has('textarea[name="custom_meta_fields_list"]');
                    if ($(this).is(':checked')) {
                        customFieldsRow.show();
                    } else {
                        customFieldsRow.hide();
                    }
                }).trigger('change');
            });
            
            function updateQueryStatus(id, status) {
                // Placeholder for query status update functionality
                console.log('Update query status:', id, status);
            }
            
            function viewReportData(id) {
                // Placeholder for viewing report data
                console.log('View report data:', id);
            }
            </script>
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
        } elseif ($action === 'save_admin_ui_settings') {
            $this->save_admin_ui_settings();
        } elseif ($action === 'save_shortcode_settings') {
            $this->save_shortcode_settings();
        }
    }

    /**
     * Save admin UI settings
     */
    private function save_admin_ui_settings()
    {
        $settings = [
            'hide_admin_bar' => isset($_POST['hide_admin_bar']) ? 1 : 0,
            'hide_admin_columns' => isset($_POST['hide_admin_columns']) ? 1 : 0,
            'hidden_columns_list' => sanitize_textarea_field($_POST['hidden_columns_list'] ?? ''),
            'hidden_columns_css' => sanitize_textarea_field($_POST['hidden_columns_css'] ?? ''),
            'hide_for_user_roles' => isset($_POST['hide_for_user_roles']) ? array_map('sanitize_text_field', $_POST['hide_for_user_roles']) : []
        ];

        update_option('softworld_admin_ui_settings', $settings);
        add_settings_error('softworld_advanced_settings', 'settings_updated', 
            __('Admin UI settings saved successfully.', $this->text_domain), 'updated');
    }

    /**
     * Save email settings
     */
    private function save_email_settings()
    {
        $settings = [
            'enable_email_reports' => isset($_POST['enable_email_reports']) ? 1 : 0,
            'report_recipients' => sanitize_textarea_field($_POST['report_recipients'] ?? ''),
            'report_frequency' => sanitize_text_field($_POST['report_frequency'] ?? 'weekly'),
            'report_content' => isset($_POST['report_content']) ? array_map('sanitize_text_field', $_POST['report_content']) : []
        ];

        update_option('softworld_email_settings', $settings);
        add_settings_error('softworld_advanced_settings', 'settings_updated', 
            __('Email settings saved successfully.', $this->text_domain), 'updated');
    }

    /**
     * Save query monitoring settings
     */
    private function save_query_settings()
    {
        $settings = [
            'enable_query_monitoring' => isset($_POST['enable_query_monitoring']) ? 1 : 0,
            'ignored_query_args' => sanitize_textarea_field($_POST['ignored_query_args'] ?? ''),
            'cleanup_threshold' => intval($_POST['cleanup_threshold'] ?? 30),
            'enable_impact_scoring' => isset($_POST['enable_impact_scoring']) ? 1 : 0
        ];

        update_option('softworld_query_settings', $settings);
        add_settings_error('softworld_advanced_settings', 'settings_updated', 
            __('Query monitoring settings saved successfully.', $this->text_domain), 'updated');
    }

    /**
     * Save shortcode settings
     */
    private function save_shortcode_settings()
    {
        $settings = [
            'enable_shortcodes_in_meta' => isset($_POST['enable_shortcodes_in_meta']) ? 1 : 0,
            'process_in_titles' => isset($_POST['process_in_titles']) ? 1 : 0,
            'process_in_descriptions' => isset($_POST['process_in_descriptions']) ? 1 : 0,
            'process_custom_meta_fields' => isset($_POST['process_custom_meta_fields']) ? 1 : 0,
            'custom_meta_fields_list' => sanitize_textarea_field($_POST['custom_meta_fields_list'] ?? ''),
            'allowed_shortcodes' => sanitize_textarea_field($_POST['allowed_shortcodes'] ?? ''),
            'cache_processed_shortcodes' => isset($_POST['cache_processed_shortcodes']) ? 1 : 0
        ];

        update_option('softworld_shortcode_settings', $settings);
        add_settings_error('softworld_advanced_settings', 'settings_updated', 
            __('Shortcode settings saved successfully.', $this->text_domain), 'updated');
    }

    /**
     * Get all settings with defaults
     */
    private function get_settings()
    {
        $email_settings = get_option('softworld_email_settings', []);
        $admin_ui_settings = get_option('softworld_admin_ui_settings', []);
        $query_settings = get_option('softworld_query_settings', []);
        $shortcode_settings = get_option('softworld_shortcode_settings', []);

        return array_merge([
            // Email settings defaults
            'enable_email_reports' => 0,
            'report_recipients' => get_option('admin_email'),
            'report_frequency' => 'weekly',
            'report_content' => ['traffic_overview', 'technical_issues'],
            
            // Admin UI settings defaults
            'hide_admin_bar' => 0,
            'hide_admin_columns' => 0,
            'hidden_columns_list' => '',
            'hidden_columns_css' => '',
            'hide_for_user_roles' => [],
            
            // Query monitoring defaults
            'enable_query_monitoring' => 1,
            'ignored_query_args' => 'utm_source,utm_medium,utm_campaign,fbclid,gclid',
            'cleanup_threshold' => 30,
            'enable_impact_scoring' => 1,
            
            // Shortcode processing defaults
            'enable_shortcodes_in_meta' => 0,
            'process_in_titles' => 1,
            'process_in_descriptions' => 1,
            'process_custom_meta_fields' => 0,
            'custom_meta_fields_list' => '',
            'allowed_shortcodes' => '',
            'cache_processed_shortcodes' => 1
        ], $email_settings, $admin_ui_settings, $query_settings, $shortcode_settings);
    }

    /**
     * Get query arguments statistics
     */
    private function get_query_args_stats()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'softworld_query_args';

        $stats = [];
        
        // Total unique URLs
        $stats['total_unique_urls'] = $wpdb->get_var("SELECT COUNT(DISTINCT url) FROM $table");
        
        // Total query arguments
        $stats['total_query_args'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        // High impact arguments (score > 7)
        $stats['high_impact_args'] = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE impact_score > 7");
        
        // Recent activity (last 7 days)
        $stats['recent_activity'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        return array_map('intval', $stats);
    }

    /**
     * Get recent reports
     */
    private function get_recent_reports()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'softworld_seo_reports';

        return $wpdb->get_results(
            "SELECT * FROM $table ORDER BY sent_date DESC LIMIT 10",
            ARRAY_A
        );
    }

    /**
     * Render query arguments table
     */
    private function render_query_args_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'softworld_query_args';
        
        $query_args = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY last_seen DESC LIMIT 50",
            ARRAY_A
        );

        if (empty($query_args)) {
            echo '<p>' . __('No query arguments data available.', $this->text_domain) . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('URL', $this->text_domain); ?></th>
                    <th><?php _e('Query Args', $this->text_domain); ?></th>
                    <th><?php _e('Hit Count', $this->text_domain); ?></th>
                    <th><?php _e('Impact Score', $this->text_domain); ?></th>
                    <th><?php _e('Status', $this->text_domain); ?></th>
                    <th><?php _e('Last Seen', $this->text_domain); ?></th>
                    <th><?php _e('Actions', $this->text_domain); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($query_args as $arg): ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url($arg['url']); ?>" target="_blank" title="<?php echo esc_attr($arg['url']); ?>">
                            <?php echo esc_html(substr($arg['url'], 0, 50)) . (strlen($arg['url']) > 50 ? '...' : ''); ?>
                        </a>
                    </td>
                    <td><code><?php echo esc_html($arg['query_args']); ?></code></td>
                    <td><?php echo number_format($arg['hit_count']); ?></td>
                    <td>
                        <span class="impact-score score-<?php echo $arg['impact_score'] > 7 ? 'high' : ($arg['impact_score'] > 4 ? 'medium' : 'low'); ?>">
                            <?php echo esc_html($arg['impact_score']); ?>/10
                        </span>
                    </td>
                    <td>
                        <span class="status-<?php echo esc_attr($arg['status']); ?>">
                            <?php echo esc_html(ucfirst($arg['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($arg['last_seen']))); ?></td>
                    <td>
                        <select onchange="updateQueryStatus(<?php echo $arg['id']; ?>, this.value)" class="query-status-select">
                            <option value="new" <?php selected($arg['status'], 'new'); ?>><?php _e('New', $this->text_domain); ?></option>
                            <option value="reviewed" <?php selected($arg['status'], 'reviewed'); ?>><?php _e('Reviewed', $this->text_domain); ?></option>
                            <option value="whitelisted" <?php selected($arg['status'], 'whitelisted'); ?>><?php _e('Whitelisted', $this->text_domain); ?></option>
                            <option value="blacklisted" <?php selected($arg['status'], 'blacklisted'); ?>><?php _e('Blacklisted', $this->text_domain); ?></option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render recent reports table
     */
    private function render_recent_reports_table($reports)
    {
        if (empty($reports)) {
            echo '<p>' . __('No reports have been sent yet.', $this->text_domain) . '</p>';
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
                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $report['report_type']))); ?></td>
                    <td><?php echo esc_html($report['recipient_email']); ?></td>
                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($report['sent_date']))); ?></td>
                    <td>
                        <span class="status-<?php echo esc_attr($report['status']); ?>">
                            <?php echo esc_html(ucfirst($report['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" onclick="viewReportData(<?php echo $report['id']; ?>)" class="button button-small">
                            <?php _e('View Data', $this->text_domain); ?>
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
            $frequency = $settings['report_frequency'];
            $schedule_map = [
                'daily' => 'daily',
                'weekly' => 'weekly',
                'monthly' => 'monthly'
            ];
            
            wp_schedule_event(time(), $schedule_map[$frequency] ?? 'weekly', 'softworld_send_seo_report');
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

        $recipients = array_filter(array_map('trim', explode(',', $settings['report_recipients'])));
        if (empty($recipients)) {
            return;
        }

        $report_data = $this->generate_seo_report_data($settings['report_content']);
        $email_content = $this->format_email_report($report_data);

        foreach ($recipients as $recipient) {
            if (is_email($recipient)) {
                $this->send_email_report($recipient, $email_content, 'scheduled');
            }
        }
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

        $current_url = home_url($_SERVER['REQUEST_URI']);
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        
        if (empty($query_string)) {
            return;
        }

        // Parse and filter query arguments
        parse_str($query_string, $query_args);
        $ignored_args = array_filter(array_map('trim', explode(',', $settings['ignored_query_args'])));
        
        foreach ($ignored_args as $ignored) {
            unset($query_args[$ignored]);
        }

        if (empty($query_args)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'softworld_query_args';
        $query_string_clean = http_build_query($query_args);

        // Check if this URL/query combination exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE url = %s AND query_args = %s",
            $current_url, $query_string_clean
        ));

        if ($existing) {
            // Update hit count and last seen
            $wpdb->update(
                $table,
                [
                    'hit_count' => $existing->hit_count + 1,
                    'last_seen' => current_time('mysql')
                ],
                ['id' => $existing->id]
            );
        } else {
            // Insert new record
            $impact_score = $settings['enable_impact_scoring'] ? $this->calculate_impact_score($query_args) : 0;
            
            $wpdb->insert(
                $table,
                [
                    'url' => $current_url,
                    'query_args' => $query_string_clean,
                    'first_seen' => current_time('mysql'),
                    'last_seen' => current_time('mysql'),
                    'hit_count' => 1,
                    'impact_score' => $impact_score,
                    'status' => 'new'
                ]
            );
        }
    }

    /**
     * Calculate SEO impact score for query arguments
     */
    private function calculate_impact_score($query_args)
    {
        $score = 0;
        $high_impact_params = ['p', 'page_id', 's', 'cat', 'tag', 'author', 'year', 'month'];
        $medium_impact_params = ['orderby', 'order', 'posts_per_page', 'paged'];
        
        foreach ($query_args as $key => $value) {
            if (in_array($key, $high_impact_params)) {
                $score += 3;
            } elseif (in_array($key, $medium_impact_params)) {
                $score += 2;
            } else {
                $score += 1;
            }
        }
        
        return min($score, 10); // Cap at 10
    }

    /**
     * Generate SEO report data
     */
    private function generate_seo_report_data($content_types)
    {
        $data = [];
        
        foreach ($content_types as $type) {
            switch ($type) {
                case 'traffic_overview':
                    $data['traffic_overview'] = $this->get_traffic_overview();
                    break;
                case 'top_keywords':
                    $data['top_keywords'] = $this->get_top_keywords();
                    break;
                case 'technical_issues':
                    $data['technical_issues'] = $this->get_technical_issues();
                    break;
                case 'content_analysis':
                    $data['content_analysis'] = $this->get_content_analysis();
                    break;
                case 'query_monitoring':
                    $data['query_monitoring'] = $this->get_query_monitoring_summary();
                    break;
            }
        }
        
        return $data;
    }

    /**
     * Format email report
     */
    private function format_email_report($data)
    {
        $html = '<h2>' . __('SEO Performance Report', $this->text_domain) . '</h2>';
        $html .= '<p>' . sprintf(__('Generated on %s', $this->text_domain), date('Y-m-d H:i:s')) . '</p>';
        
        foreach ($data as $section => $content) {
            $html .= '<h3>' . ucfirst(str_replace('_', ' ', $section)) . '</h3>';
            if (is_array($content)) {
                $html .= '<ul>';
                foreach ($content as $item) {
                    $html .= '<li>' . esc_html($item) . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p>' . esc_html($content) . '</p>';
            }
        }
        
        return $html;
    }

    /**
     * Send email report
     */
    private function send_email_report($recipient, $content, $type = 'manual')
    {
        $subject = sprintf(__('[%s] SEO Performance Report', $this->text_domain), get_bloginfo('name'));
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent = wp_mail($recipient, $subject, $content, $headers);
        
        // Log the report
        global $wpdb;
        $reports_table = $wpdb->prefix . 'softworld_seo_reports';
        
        $wpdb->insert(
            $reports_table,
            [
                'report_type' => $type,
                'sent_date' => current_time('mysql'),
                'recipient_email' => $recipient,
                'status' => $sent ? 'sent' : 'failed',
                'report_data' => wp_json_encode(['content' => $content])
            ]
        );
        
        return $sent;
    }

    /**
     * AJAX handler for sending test email
     */
    public function ajax_send_test_email()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'softworld_advanced_settings')) {
            wp_die(__('Security check failed', $this->text_domain));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', $this->text_domain));
        }

        $settings = $this->get_settings();
        $recipients = array_filter(array_map('trim', explode(',', $settings['report_recipients'])));
        
        if (empty($recipients)) {
            wp_send_json_error(__('No recipients configured', $this->text_domain));
        }

        $test_data = $this->generate_seo_report_data(['traffic_overview', 'technical_issues']);
        $content = $this->format_email_report($test_data);
        
        $success_count = 0;
        foreach ($recipients as $recipient) {
            if (is_email($recipient) && $this->send_email_report($recipient, $content, 'test')) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            wp_send_json_success(sprintf(__('Test email sent to %d recipients', $this->text_domain), $success_count));
        } else {
            wp_send_json_error(__('Failed to send test email', $this->text_domain));
        }
    }

    /**
     * AJAX handler for clearing query logs
     */
    public function ajax_clear_query_logs()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'softworld_advanced_settings')) {
            wp_die(__('Security check failed', $this->text_domain));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', $this->text_domain));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'softworld_query_args';
        $settings = $this->get_settings();
        
        $threshold_date = date('Y-m-d H:i:s', strtotime('-' . $settings['cleanup_threshold'] . ' days'));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE last_seen < %s",
            $threshold_date
        ));
        
        wp_send_json_success(sprintf(__('%d old records cleared', $this->text_domain), $deleted));
    }

    // Helper methods for report generation
    private function get_traffic_overview()
    {
        return ['Sample traffic data - integrate with your analytics'];
    }

    private function get_top_keywords()
    {
        return ['Sample keyword data - integrate with search console'];
    }

    private function get_technical_issues()
    {
        return ['Sample technical issues - run site audits'];
    }

    private function get_content_analysis()
    {
        return ['Sample content analysis - analyze recent posts'];
    }

    private function get_query_monitoring_summary()
    {
        return $this->get_query_args_stats();
    }
}
?>