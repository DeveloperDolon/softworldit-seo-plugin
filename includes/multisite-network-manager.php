<?php
/**
 * Enhanced Multisite Network Manager
 * Handles multisite network support for license, settings, import/export, NGINX sitemap rules, and robots.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Multisite_Network_Manager
{
    private $plugin_slug;
    private $network_settings_key = 'softworld_ai_seo_network_settings';
    private $license_key = 'softworld_ai_seo_network_license';

    public function __construct($plugin_slug)
    {
        $this->plugin_slug = $plugin_slug;

        // Only run on multisite installations
        if (is_multisite()) {
            $this->init();
        }
    }
    
    public function init()
    {
        // Network admin hooks - Only add these if we're in network context
        if (is_network_admin()) {
            add_action('wp_ajax_softworld_network_save_settings', [$this, 'ajax_save_network_settings']);
            add_action('wp_ajax_softworld_network_activate_license', [$this, 'ajax_activate_license']);
            add_action('wp_ajax_softworld_network_deactivate_license', [$this, 'ajax_deactivate_license']);
            add_action('wp_ajax_softworld_network_export_settings', [$this, 'ajax_export_settings']);
            add_action('wp_ajax_softworld_network_import_settings', [$this, 'ajax_import_settings']);
            add_action('wp_ajax_softworld_network_apply_to_sites', [$this, 'ajax_apply_settings_to_sites']);
            // NEW: NGINX and robots.txt AJAX handlers
            add_action('wp_ajax_softworld_network_generate_nginx_rules', [$this, 'ajax_generate_nginx_rules']);
            add_action('wp_ajax_softworld_network_save_robots_txt', [$this, 'ajax_save_network_robots_txt']);
            add_action('wp_ajax_softworld_network_apply_robots_to_sites', [$this, 'ajax_apply_robots_to_sites']);
        }

        // Site admin hooks - Only for individual sites
        if (!is_network_admin()) {
            add_action('admin_menu', [$this, 'maybe_add_site_menu']);
            add_action('admin_init', [$this, 'maybe_restrict_site_settings']);
            add_action('wp_ajax_softworld_site_request_override', [$this, 'ajax_request_override']);
        }

        // Global hooks
        add_action('init', [$this, 'validate_network_license']);
        add_filter('softworld_ai_seo_license_valid', [$this, 'check_network_license'], 10, 2);

        // Settings inheritance hooks
        add_filter('softworld_ai_seo_get_option', [$this, 'maybe_inherit_network_setting'], 10, 3);
        add_action('softworld_ai_seo_settings_updated', [$this, 'log_settings_change'], 10, 2);

        // Admin asset enqueuing
        add_action('admin_enqueue_scripts', [$this, 'enqueue_network_admin_assets']);

        // NEW: Network robots.txt handling
        add_action('do_robots', [$this, 'handle_network_robots_txt'], 1);
        add_filter('robots_txt', [$this, 'filter_network_robots_txt'], 10, 2);
    }
    
    public function enqueue_network_admin_assets()
    {
        // Only enqueue on network admin pages
        if (!is_network_admin()) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'softworld-ai-seo') === false) {
            return;
        }

        wp_enqueue_style('softworld-network-admin', AI_SEO_PLUGIN_URL . 'assets/css/network-admin.css', [], AI_SEO_VERSION);
        wp_enqueue_script('softworld-network-admin', AI_SEO_PLUGIN_URL . 'assets/js/network-admin.js', ['jquery'], AI_SEO_VERSION, true);

        wp_localize_script('softworld-network-admin', 'softworldNetwork', [
            'nonce' => wp_create_nonce('softworld_network_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'i18n' => [
                'saving' => __('Saving...', 'softworld-ai-seo'),
                'saved' => __('Saved!', 'softworld-ai-seo'),
                'error' => __('Error occurred', 'softworld-ai-seo'),
                'confirm_apply_all' => __('Are you sure you want to apply these settings to all sites?', 'softworld-ai-seo'),
                'confirm_override_request' => __('Request permission to override network settings?', 'softworld-ai-seo'),
                'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'softworld-ai-seo'),
                'generating_rules' => __('Generating NGINX rules...', 'softworld-ai-seo'),
                'rules_generated' => __('NGINX rules generated successfully!', 'softworld-ai-seo'),
                'applying_robots' => __('Applying robots.txt to all sites...', 'softworld-ai-seo'),
                'robots_applied' => __('Robots.txt applied to all sites!', 'softworld-ai-seo')
            ]
        ]);
    }

    /**
     * Validate Network License
     */
    public function validate_network_license()
    {
        // Only run in network admin context
        if (!is_network_admin()) {
            return;
        }

        $license_info = $this->get_network_license_info();

        if (!$license_info['valid'] || empty($license_info['key'])) {
            return;
        }

        // Check if license needs validation (every 24 hours)
        $last_check = get_site_option('softworld_ai_seo_license_last_check', 0);

        if ((time() - $last_check) < DAY_IN_SECONDS) {
            return;
        }

        // Validate with remote server
        $validation_result = $this->validate_license_with_server($license_info['key']);

        if (!$validation_result['success']) {
            // Mark license as invalid
            $license_info['valid'] = false;
            update_site_option($this->license_key, $license_info);
        }

        update_site_option('softworld_ai_seo_license_last_check', time());
    }

    /**
     * Enhanced Network Admin Dashboard with new features
     */
    public function render_network_admin_page()
    {
        if (!current_user_can('manage_network')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        $sites = $this->get_network_sites();
        $license_info = $this->get_network_license_info();
        $network_stats = $this->get_network_statistics();

        ?>
                                <div class="wrap softworld-network-wrap">
                                    <h1><?php _e('Softworld SEO Network Dashboard', 'softworld-ai-seo'); ?></h1>
            
                                    <!-- License Status Card -->
                                    <div class="softworld-network-cards">
                                        <div class="softworld-network-card">
                                            <h3><?php _e('License Status', 'softworld-ai-seo'); ?></h3>
                                            <div class="license-status <?php echo $license_info['valid'] ? 'valid' : 'invalid'; ?>">
                                                <span class="status-indicator"></span>
                                                <?php if ($license_info['valid']): ?>
                                                                <span><?php _e('Active', 'softworld-ai-seo'); ?></span>
                                                                <small><?php printf(__('Expires: %s', 'softworld-ai-seo'), $license_info['expires']); ?></small>
                                                <?php else: ?>
                                                                <span><?php _e('Inactive', 'softworld-ai-seo'); ?></span>
                                                                <small><a href="<?php echo network_admin_url('admin.php?page=softworld-ai-seo-network-license'); ?>">Activate License</a></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="softworld-network-card">
                                            <h3><?php _e('Network Statistics', 'softworld-ai-seo'); ?></h3>
                                            <ul>
                                                <li><?php printf(__('Total Sites: %d', 'softworld-ai-seo'), $network_stats['total_sites']); ?></li>
                                                <li><?php printf(__('Active Sites: %d', 'softworld-ai-seo'), $network_stats['active_sites']); ?></li>
                                                <li><?php printf(__('Sites with Override: %d', 'softworld-ai-seo'), $network_stats['override_sites']); ?></li>
                                                <li><?php printf(__('Sitemaps Generated: %d', 'softworld-ai-seo'), $network_stats['sitemap_count']); ?></li>
                                            </ul>
                                        </div>

                                        <div class="softworld-network-card">
                                            <h3><?php _e('Quick Actions', 'softworld-ai-seo'); ?></h3>
                                            <div class="quick-actions">
                                                <a href="<?php echo network_admin_url('admin.php?page=softworld-ai-seo-network-settings'); ?>" class="button button-primary">
                                                    <?php _e('Manage Settings', 'softworld-ai-seo'); ?>
                                                </a>
                                                <a href="<?php echo network_admin_url('admin.php?page=softworld-ai-seo-network-nginx'); ?>" class="button button-secondary">
                                                    <?php _e('NGINX Rules', 'softworld-ai-seo'); ?>
                                                </a>
                                                <a href="<?php echo network_admin_url('admin.php?page=softworld-ai-seo-network-robots'); ?>" class="button button-secondary">
                                                    <?php _e('Robots.txt Manager', 'softworld-ai-seo'); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- NEW: NGINX & Robots.txt Quick Status -->
                                    <div class="softworld-network-tools-status">
                                        <h2><?php _e('Network Tools Status', 'softworld-ai-seo'); ?></h2>
                                        <div class="tools-status-grid">
                                            <div class="tool-status-item">
                                                <h4><?php _e('NGINX Sitemap Rules', 'softworld-ai-seo'); ?></h4>
                                                <p class="status-text"><?php echo $this->get_nginx_rules_status(); ?></p>
                                                <button type="button" id="quick-generate-nginx" class="button button-small">
                                                    <?php _e('Generate Rules', 'softworld-ai-seo'); ?>
                                                </button>
                                            </div>
                                            <div class="tool-status-item">
                                                <h4><?php _e('Network Robots.txt', 'softworld-ai-seo'); ?></h4>
                                                <p class="status-text"><?php echo $this->get_robots_txt_status(); ?></p>
                                                <button type="button" id="quick-apply-robots" class="button button-small">
                                                    <?php _e('Apply to All Sites', 'softworld-ai-seo'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sites Overview -->
                                    <div class="softworld-sites-overview">
                                        <h2><?php _e('Sites Overview', 'softworld-ai-seo'); ?></h2>
                                        <div class="sites-table-container">
                                            <table class="wp-list-table widefat fixed striped">
                                                <thead>
                                                    <tr>
                                                        <th><?php _e('Site', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Status', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Settings', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Sitemap', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Robots.txt', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Actions', 'softworld-ai-seo'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($sites)): ?>
                                                                    <?php foreach ($sites as $site): ?>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <strong><?php echo esc_html($site['name']); ?></strong>
                                                                                            <br><small><?php echo esc_url($site['url']); ?></small>
                                                                                        </td>
                                                                                        <td>
                                                                                            <span class="site-status <?php echo $site['active'] ? 'active' : 'inactive'; ?>">
                                                                                                <?php echo $site['active'] ? __('Active', 'softworld-ai-seo') : __('Inactive', 'softworld-ai-seo'); ?>
                                                                                            </span>
                                                                                        </td>
                                                                                        <td>
                                                                                            <?php if ($site['has_override']): ?>
                                                                                                            <span class="settings-override"><?php _e('Override Active', 'softworld-ai-seo'); ?></span>
                                                                                            <?php else: ?>
                                                                                                            <span class="settings-inherited"><?php _e('Inherited', 'softworld-ai-seo'); ?></span>
                                                                                            <?php endif; ?>
                                                                                        </td>
                                                                                        <td>
                                                                                            <?php if ($site['has_sitemap']): ?>
                                                                                                            <a href="<?php echo esc_url($site['sitemap_url']); ?>" target="_blank" class="sitemap-link">
                                                                                                                <?php _e('View Sitemap', 'softworld-ai-seo'); ?>
                                                                                                            </a>
                                                                                            <?php else: ?>
                                                                                                            <span class="no-sitemap"><?php _e('No Sitemap', 'softworld-ai-seo'); ?></span>
                                                                                            <?php endif; ?>
                                                                                        </td>
                                                                                        <td>
                                                                                            <?php if ($site['custom_robots']): ?>
                                                                                                            <span class="custom-robots"><?php _e('Custom', 'softworld-ai-seo'); ?></span>
                                                                                            <?php else: ?>
                                                                                                            <span class="inherited-robots"><?php _e('Network', 'softworld-ai-seo'); ?></span>
                                                                                            <?php endif; ?>
                                                                                        </td>
                                                                                        <td>
                                                                                            <a href="<?php echo $site['admin_url']; ?>" class="button button-small" target="_blank">
                                                                                                <?php _e('Visit Admin', 'softworld-ai-seo'); ?>
                                                                                            </a>
                                                                                        </td>
                                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                    <?php else: ?>
                                                                    <tr>
                                                                        <td colspan="6"><?php _e('No sites found.', 'softworld-ai-seo'); ?></td>
                                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
        
                                <!-- Enhanced Styles -->
                                <style>
                                    .softworld-network-cards { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
                                    .softworld-network-card { background: white; padding: 20px; border: 1px solid #ccd0d4; flex: 1; min-width: 300px; }
                                    .license-status.valid { color: #00a32a; }
                                    .license-status.invalid { color: #d63638; }
                                    .site-status.active { color: #00a32a; }
                                    .site-status.inactive { color: #d63638; }
                                    .settings-override { color: #dba617; font-weight: bold; }
                                    .settings-inherited { color: #2271b1; }
                                    .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; }
                                    .status-indicator { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
                                    .license-status.valid .status-indicator { background: #00a32a; }
                                    .license-status.invalid .status-indicator { background: #d63638; }
            
                                    /* NEW: Tools status styles */
                                    .softworld-network-tools-status { margin: 20px 0; padding: 20px; background: white; border: 1px solid #ccd0d4; }
                                    .tools-status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
                                    .tool-status-item { padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
                                    .tool-status-item h4 { margin: 0 0 10px 0; color: #23282d; }
                                    .tool-status-item .status-text { margin: 5px 0 10px 0; font-size: 13px; color: #666; }
                                    .sitemap-link { color: #00a32a; text-decoration: none; }
                                    .no-sitemap { color: #d63638; }
                                    .custom-robots { color: #dba617; }
                                    .inherited-robots { color: #2271b1; }
            
                                    @media (max-width: 768px) {
                                        .tools-status-grid { grid-template-columns: 1fr; }
                                        .softworld-network-cards { flex-direction: column; }
                                    }
                                </style>
                                <?php
    }

    /**
     * NEW: Render NGINX Rules Management Page
     */
    public function render_nginx_rules_page()
    {
        if (!current_user_can('manage_network')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        $sites = $this->get_network_sites();
        $generated_rules = get_site_option('softworld_ai_seo_nginx_rules', '');

        ?>
                                <div class="wrap softworld-network-wrap">
                                    <h1><?php _e('NGINX Sitemap Rewrite Rules', 'softworld-ai-seo'); ?></h1>
            
                                    <div class="nginx-rules-container">
                                        <div class="nginx-info-section">
                                            <h2><?php _e('About NGINX Sitemap Rules', 'softworld-ai-seo'); ?></h2>
                                            <p><?php _e('These rules help NGINX properly handle sitemap requests across your multisite network. Add these rules to your NGINX server configuration.', 'softworld-ai-seo'); ?></p>
                    
                                            <div class="nginx-benefits">
                                                <h3><?php _e('Benefits:', 'softworld-ai-seo'); ?></h3>
                                                <ul>
                                                    <li><?php _e('Improved sitemap loading performance', 'softworld-ai-seo'); ?></li>
                                                    <li><?php _e('Better SEO crawler experience', 'softworld-ai-seo'); ?></li>
                                                    <li><?php _e('Proper handling of multisite sitemap requests', 'softworld-ai-seo'); ?></li>
                                                    <li><?php _e('Reduced server load', 'softworld-ai-seo'); ?></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="nginx-generator-section">
                                            <h2><?php _e('Generate NGINX Rules', 'softworld-ai-seo'); ?></h2>
                                            <form id="nginx-rules-form">
                                                <?php wp_nonce_field('softworld_network_nginx', 'nginx_nonce'); ?>
                        
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row"><?php _e('Include Sites', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <fieldset>
                                                                <label>
                                                                    <input type="checkbox" name="include_all_sites" value="1" checked>
                                                                    <?php _e('Generate rules for all active sites', 'softworld-ai-seo'); ?>
                                                                </label>
                                                                <br>
                                                                <label>
                                                                    <input type="checkbox" name="include_subdirs" value="1">
                                                                    <?php _e('Include subdirectory installations', 'softworld-ai-seo'); ?>
                                                                </label>
                                                                <br>
                                                                <label>
                                                                    <input type="checkbox" name="include_main_site" value="1" checked>
                                                                    <?php _e('Include main site', 'softworld-ai-seo'); ?>
                                                                </label>
                                                            </fieldset>
                                                        </td>
                                                    </tr>
                            
                                                    <tr>
                                                        <th scope="row"><?php _e('Rule Options', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <fieldset>
                                                                <label>
                                                                    <input type="checkbox" name="add_caching" value="1" checked>
                                                                    <?php _e('Add caching headers for sitemaps', 'softworld-ai-seo'); ?>
                                                                </label>
                                                                <br>
                                                                <label>
                                                                    <input type="checkbox" name="add_compression" value="1" checked>
                                                                    <?php _e('Enable gzip compression for XML files', 'softworld-ai-seo'); ?>
                                                                </label>
                                                                <br>
                                                                <label>
                                                                    <input type="checkbox" name="add_security" value="1">
                                                                    <?php _e('Add security headers', 'softworld-ai-seo'); ?>
                                                                </label>
                                                            </fieldset>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th scope="row"><?php _e('Cache Duration', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <select name="cache_duration">
                                                                <option value="3600"><?php _e('1 Hour', 'softworld-ai-seo'); ?></option>
                                                                <option value="21600" selected><?php _e('6 Hours', 'softworld-ai-seo'); ?></option>
                                                                <option value="86400"><?php _e('24 Hours', 'softworld-ai-seo'); ?></option>
                                                                <option value="604800"><?php _e('1 Week', 'softworld-ai-seo'); ?></option>
                                                            </select>
                                                            <p class="description">
                                                                <?php _e('How long browsers and CDNs should cache sitemap files.', 'softworld-ai-seo'); ?>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </table>
                        
                                                <p class="submit">
                                                    <button type="submit" class="button button-primary">
                                                        <?php _e('Generate NGINX Rules', 'softworld-ai-seo'); ?>
                                                    </button>
                                                </p>
                                            </form>
                                        </div>

                                        <?php if ($generated_rules): ?>
                                                    <div class="nginx-rules-display">
                                                        <h2><?php _e('Generated NGINX Rules', 'softworld-ai-seo'); ?></h2>
                                                        <p><?php _e('Copy the following rules to your NGINX server configuration:', 'softworld-ai-seo'); ?></p>
                    
                                                        <div class="nginx-rules-output">
                                                            <div class="rules-header">
                                                                <span><?php _e('NGINX Configuration', 'softworld-ai-seo'); ?></span>
                                                                <button type="button" class="copy-rules-btn" data-clipboard-target="#nginx-rules-text">
                                                                    <?php _e('Copy to Clipboard', 'softworld-ai-seo'); ?>
                                                                </button>
                                                            </div>
                                                            <pre id="nginx-rules-text"><code><?php echo esc_html($generated_rules); ?></code></pre>
                                                        </div>
                    
                                                        <div class="nginx-installation-guide">
                                                            <h3><?php _e('Installation Instructions', 'softworld-ai-seo'); ?></h3>
                                                            <ol>
                                                                <li><?php _e('Copy the generated rules above', 'softworld-ai-seo'); ?></li>
                                                                <li><?php _e('Open your NGINX server configuration file (usually in /etc/nginx/sites-available/)', 'softworld-ai-seo'); ?></li>
                                                                <li><?php _e('Paste the rules inside your server block', 'softworld-ai-seo'); ?></li>
                                                                <li><?php _e('Test the configuration: nginx -t', 'softworld-ai-seo'); ?></li>
                                                                <li><?php _e('Reload NGINX: systemctl reload nginx', 'softworld-ai-seo'); ?></li>
                                                            </ol>
                                                        </div>
                                                    </div>
                                        <?php endif; ?>

                                        <!-- Site-specific Sitemap URLs -->
                                        <div class="sitemap-urls-section">
                                            <h2><?php _e('Network Sitemap URLs', 'softworld-ai-seo'); ?></h2>
                                            <div class="sitemap-urls-list">
                                                <?php foreach ($sites as $site): ?>
                                                                <?php if ($site['has_sitemap']): ?>
                                                                                <div class="sitemap-url-item">
                                                                                    <strong><?php echo esc_html($site['name']); ?></strong>
                                                                                    <br>
                                                                                    <a href="<?php echo esc_url($site['sitemap_url']); ?>" target="_blank">
                                                                                        <?php echo esc_url($site['sitemap_url']); ?>
                                                                                    </a>
                                                                                    <button type="button" class="copy-url-btn" data-url="<?php echo esc_url($site['sitemap_url']); ?>">
                                                                                        <?php _e('Copy', 'softworld-ai-seo'); ?>
                                                                                    </button>
                                                                                </div>
                                                                <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        
                                <style>
                                    .nginx-rules-container { max-width: 1200px; }
                                    .nginx-info-section, .nginx-generator-section, .nginx-rules-display, .sitemap-urls-section { 
                                        background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; 
                                    }
                                    .nginx-benefits ul { padding-left: 20px; }
                                    .nginx-rules-output { position: relative; }
                                    .rules-header { 
                                        display: flex; justify-content: space-between; align-items: center; 
                                        padding: 10px; background: #f1f3f4; border: 1px solid #ddd; 
                                    }
                                    .rules-header span { font-weight: bold; }
                                    .copy-rules-btn, .copy-url-btn { 
                                        background: #0073aa; color: white; border: none; padding: 5px 10px; 
                                        cursor: pointer; border-radius: 3px; 
                                    }
                                    #nginx-rules-text { 
                                        margin: 0; padding: 15px; background: #2d3748; color: #e2e8f0; 
                                        overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; 
                                        border: 1px solid #ddd; border-top: none; 
                                    }
                                    .nginx-installation-guide ol { padding-left: 20px; }
                                    .sitemap-url-item { 
                                        padding: 10px; margin: 10px 0; border: 1px solid #ddd; 
                                        border-radius: 4px; background: #f9f9f9; 
                                    }
                                    .sitemap-url-item a { margin-right: 10px; }
                                </style>
                                <?php
    }

    /**
     * Render License Management Page
     */
    public function render_license_page()
    {
        if (!current_user_can('manage_network')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        $license_info = $this->get_network_license_info();

        ?>
                                <div class="wrap softworld-network-wrap">
                                    <h1><?php _e('Network License Management', 'softworld-ai-seo'); ?></h1>
            
                                    <form id="softworld-network-license-form" class="softworld-license-form">
                                        <?php wp_nonce_field('softworld_network_license', 'license_nonce'); ?>
                
                                        <table class="form-table">
                                            <tr>
                                                <th scope="row"><?php _e('License Key', 'softworld-ai-seo'); ?></th>
                                                <td>
                                                    <input type="text" 
                                                           id="license_key" 
                                                           name="license_key" 
                                                           value="<?php echo esc_attr($license_info['key']); ?>" 
                                                           class="regular-text" 
                                                           <?php echo $license_info['valid'] ? 'readonly' : ''; ?>>
                                                    <p class="description">
                                                        <?php _e('Enter your Softworld AI SEO network license key.', 'softworld-ai-seo'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                    
                                            <?php if ($license_info['valid']): ?>
                                                        <tr>
                                                            <th scope="row"><?php _e('License Status', 'softworld-ai-seo'); ?></th>
                                                            <td>
                                                                <div class="license-status-display">
                                                                    <span class="license-valid">✅ <?php _e('Active', 'softworld-ai-seo'); ?></span>
                                                                    <p><strong><?php _e('Licensed to:', 'softworld-ai-seo'); ?></strong> <?php echo esc_html($license_info['customer_name']); ?></p>
                                                                    <p><strong><?php _e('Expires:', 'softworld-ai-seo'); ?></strong> <?php echo esc_html($license_info['expires']); ?></p>
                                                                    <p><strong><?php _e('Sites Allowed:', 'softworld-ai-seo'); ?></strong> <?php echo esc_html($license_info['site_limit']); ?></p>
                                                                    <p><strong><?php _e('Sites Used:', 'softworld-ai-seo'); ?></strong> <?php echo esc_html($license_info['sites_used']); ?></p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                            <?php endif; ?>
                                        </table>
                
                                        <p class="submit">
                                            <?php if ($license_info['valid']): ?>
                                                            <button type="button" id="deactivate-license" class="button button-secondary">
                                                                <?php _e('Deactivate License', 'softworld-ai-seo'); ?>
                                                            </button>
                                            <?php else: ?>
                                                            <button type="button" id="activate-license" class="button button-primary">
                                                                <?php _e('Activate License', 'softworld-ai-seo'); ?>
                                                            </button>
                                            <?php endif; ?>
                                        </p>
                                    </form>
            
                                    <div id="license-status-message" class="notice" style="display: none;"></div>
                                </div>
                                <?php
    }

    /**
     * NEW: Render Network Robots.txt Management Page
     */
    public function render_network_robots_page()
    {
        if (!current_user_can('manage_network')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', $this->get_default_network_robots());
        $sites = $this->get_network_sites();

        ?>
                                <div class="wrap softworld-network-wrap">
                                    <h1><?php _e('Network Robots.txt Manager', 'softworld-ai-seo'); ?></h1>
            
                                    <div class="robots-txt-container">
                                        <div class="robots-info-section">
                                            <h2><?php _e('About Network Robots.txt', 'softworld-ai-seo'); ?></h2>
                                            <p><?php _e('Manage robots.txt files across your entire multisite network. You can set a default network robots.txt that applies to all sites, or allow individual sites to have custom robots.txt files.', 'softworld-ai-seo'); ?></p>
                    
                                            <div class="robots-benefits">
                                                <h3><?php _e('Features:', 'softworld-ai-seo'); ?></h3>
                                                <ul>
                                                    <li><?php _e('Centralized robots.txt management', 'softworld-ai-seo'); ?></li>
                                                    <li><?php _e('Automatic sitemap inclusion', 'softworld-ai-seo'); ?></li>
                                                    <li><?php _e('Per-site customization options', 'softworld-ai-seo'); ?></li>
                                                    <li><?php _e('SEO-friendly default rules', 'softworld-ai-seo'); ?></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="network-robots-editor">
                                            <h2><?php _e('Default Network Robots.txt', 'softworld-ai-seo'); ?></h2>
                                            <p><?php _e('This robots.txt content will be used for all sites that don\'t have custom overrides.', 'softworld-ai-seo'); ?></p>
                    
                                            <form id="network-robots-form">
                                                <?php wp_nonce_field('softworld_network_robots', 'robots_nonce'); ?>
                        
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row"><?php _e('Network Robots.txt Content', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <textarea name="network_robots_content" id="network-robots-content" rows="15" class="large-text code"><?php echo esc_textarea($network_robots); ?></textarea>
                                                            <p class="description">
                                                                <?php _e('Use %%sitemap_url%% to automatically include the sitemap URL for each site.', 'softworld-ai-seo'); ?>
                                                            </p>
                                                        </td>
                                                    </tr>
                            
                                                    <tr>
                                                        <th scope="row"><?php _e('Application Options', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <fieldset>
                                                                <label>
                                                                    <input type="checkbox" name="auto_include_sitemaps" value="1" checked>
                                                                    <?php _e('Automatically include sitemap URLs', 'softworld-ai-seo'); ?>
                                                                </label>
                                                                <br>
                                                                <label>
                                                                    <input type="checkbox" name="block_admin_areas" value="1" checked>
                                                                    <?php _e('Block admin and login areas', 'softworld-ai-seo'); ?>
                                                                </label>
                                                                <br>
                                                                <label>
                                                                    <input type="checkbox" name="allow_site_overrides" value="1">
                                                                    <?php _e('Allow individual sites to override robots.txt', 'softworld-ai-seo'); ?>
                                                                </label>
                                                            </fieldset>
                                                        </td>
                                                    </tr>
                                                </table>
                        
                                                <p class="submit">
                                                    <button type="submit" class="button button-primary">
                                                        <?php _e('Save Network Robots.txt', 'softworld-ai-seo'); ?>
                                                    </button>
                                                    <button type="button" id="apply-robots-to-all" class="button button-secondary">
                                                        <?php _e('Apply to All Sites', 'softworld-ai-seo'); ?>
                                                    </button>
                                                    <button type="button" id="preview-robots" class="button button-secondary">
                                                        <?php _e('Preview', 'softworld-ai-seo'); ?>
                                                    </button>
                                                </p>
                                            </form>
                                        </div>

                                        <!-- Robots.txt Templates -->
                                        <div class="robots-templates-section">
                                            <h2><?php _e('Robots.txt Templates', 'softworld-ai-seo'); ?></h2>
                                            <p><?php _e('Quick-start templates for common scenarios:', 'softworld-ai-seo'); ?></p>
                    
                                            <div class="template-buttons">
                                                <button type="button" class="load-template" data-template="standard">
                                                    <?php _e('Standard Template', 'softworld-ai-seo'); ?>
                                                </button>
                                                <button type="button" class="load-template" data-template="strict">
                                                    <?php _e('Strict Template', 'softworld-ai-seo'); ?>
                                                </button>
                                                <button type="button" class="load-template" data-template="permissive">
                                                    <?php _e('Permissive Template', 'softworld-ai-seo'); ?>
                                                </button>
                                                <button type="button" class="load-template" data-template="ecommerce">
                                                    <?php _e('E-commerce Template', 'softworld-ai-seo'); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Site-specific Robots.txt Status -->
                                        <div class="site-robots-status">
                                            <h2><?php _e('Site-specific Robots.txt Status', 'softworld-ai-seo'); ?></h2>
                                            <table class="wp-list-table widefat fixed striped">
                                                <thead>
                                                    <tr>
                                                        <th><?php _e('Site', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Robots.txt Source', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Last Modified', 'softworld-ai-seo'); ?></th>
                                                        <th><?php _e('Actions', 'softworld-ai-seo'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($sites as $site): ?>
                                                                    <tr>
                                                                        <td>
                                                                            <strong><?php echo esc_html($site['name']); ?></strong>
                                                                            <br><small><?php echo esc_url($site['url']); ?></small>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($site['custom_robots']): ?>
                                                                                            <span class="custom-robots"><?php _e('Custom Override', 'softworld-ai-seo'); ?></span>
                                                                            <?php else: ?>
                                                                                            <span class="network-robots"><?php _e('Network Default', 'softworld-ai-seo'); ?></span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?php echo $site['robots_modified'] ? date('Y-m-d H:i', strtotime($site['robots_modified'])) : __('Never', 'softworld-ai-seo'); ?></td>
                                                                        <td>
                                                                            <a href="<?php echo esc_url($site['robots_url']); ?>" target="_blank" class="button button-small">
                                                                                <?php _e('View', 'softworld-ai-seo'); ?>
                                                                            </a>
                                                                            <?php if ($site['custom_robots']): ?>
                                                                                            <button type="button" class="reset-to-network button button-small" data-site-id="<?php echo $site['id']; ?>">
                                                                                                <?php _e('Reset to Network', 'softworld-ai-seo'); ?>
                                                                                            </button>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Robots.txt Preview Modal -->
                                        <div id="robots-preview-modal" class="robots-modal" style="display: none;">
                                            <div class="robots-modal-content">
                                                <div class="robots-modal-header">
                                                    <h3><?php _e('Robots.txt Preview', 'softworld-ai-seo'); ?></h3>
                                                    <button type="button" class="robots-modal-close">&times;</button>
                                                </div>
                                                <div class="robots-modal-body">
                                                    <div class="preview-tabs">
                                                        <button type="button" class="preview-tab active" data-site="main">
                                                            <?php _e('Main Site', 'softworld-ai-seo'); ?>
                                                        </button>
                                                        <?php foreach (array_slice($sites, 1, 3) as $site): ?>
                                                                        <button type="button" class="preview-tab" data-site="<?php echo $site['id']; ?>">
                                                                            <?php echo esc_html($site['name']); ?>
                                                                        </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="preview-content">
                                                        <pre id="robots-preview-text"></pre>
                                                    </div>
                                                </div>
                                                <div class="robots-modal-footer">
                                                    <button type="button" class="button robots-modal-close">
                                                        <?php _e('Close', 'softworld-ai-seo'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        
                                <style>
                                    .robots-txt-container { max-width: 1200px; }
                                    .robots-info-section, .network-robots-editor, .robots-templates-section, .site-robots-status { 
                                        background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; 
                                    }
                                    .robots-benefits ul { padding-left: 20px; }
                                    #network-robots-content { 
                                        font-family: 'Courier New', monospace; font-size: 13px; 
                                        background: #f9f9f9; border: 1px solid #ddd; 
                                    }
                                    .template-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
                                    .load-template { 
                                        padding: 8px 15px; background: #f0f0f1; border: 1px solid #c3c4c7; 
                                        cursor: pointer; border-radius: 3px; 
                                    }
                                    .load-template:hover { background: #e9e9ea; }
                                    .custom-robots { color: #dba617; font-weight: bold; }
                                    .network-robots { color: #2271b1; }
                                    .reset-to-network { margin-left: 5px; }
            
                                    /* Modal styles */
                                    .robots-modal { 
                                        position: fixed; z-index: 100000; left: 0; top: 0; 
                                        width: 100%; height: 100%; background: rgba(0,0,0,0.5); 
                                    }
                                    .robots-modal-content { 
                                        background: white; margin: 5% auto; padding: 0; width: 80%; 
                                        max-width: 800px; border-radius: 4px; 
                                    }
                                    .robots-modal-header { 
                                        padding: 20px; border-bottom: 1px solid #ddd; 
                                        display: flex; justify-content: space-between; align-items: center; 
                                    }
                                    .robots-modal-close { 
                                        background: none; border: none; font-size: 24px; 
                                        cursor: pointer; color: #666; 
                                    }
                                    .robots-modal-body { padding: 20px; }
                                    .robots-modal-footer { 
                                        padding: 15px 20px; border-top: 1px solid #ddd; 
                                        text-align: right; 
                                    }
                                    .preview-tabs { 
                                        display: flex; gap: 5px; margin-bottom: 15px; 
                                        border-bottom: 1px solid #ddd; 
                                    }
                                    .preview-tab { 
                                        padding: 8px 15px; background: #f0f0f1; border: 1px solid #c3c4c7; 
                                        border-bottom: none; cursor: pointer; 
                                    }
                                    .preview-tab.active { background: white; border-bottom: 1px solid white; margin-bottom: -1px; }
                                    .preview-content { min-height: 300px; }
                                    #robots-preview-text { 
                                        background: #f9f9f9; padding: 15px; border: 1px solid #ddd; 
                                        font-family: 'Courier New', monospace; font-size: 13px; 
                                    }
                                </style>
                                <?php
    }

    /**
     * Render Network Settings Page
     */
    public function render_network_settings_page()
    {
        if (!current_user_can('manage_network')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        $network_settings = $this->get_network_settings();
        $available_settings = $this->get_available_settings();

        ?>
                                <div class="wrap softworld-network-wrap">
                                    <h1><?php _e('Network Settings', 'softworld-ai-seo'); ?></h1>
            
                                    <form id="softworld-network-settings-form">
                                        <?php wp_nonce_field('softworld_network_settings', 'settings_nonce'); ?>
                
                                        <!-- Settings Inheritance -->
                                        <div class="softworld-settings-section">
                                            <h2><?php _e('Settings Inheritance', 'softworld-ai-seo'); ?></h2>
                                            <table class="form-table">
                                                <tr>
                                                    <th scope="row"><?php _e('Allow Site Overrides', 'softworld-ai-seo'); ?></th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox" name="allow_site_overrides" value="1" 
                                                                   <?php checked($network_settings['allow_site_overrides'], 1); ?>>
                                                            <?php _e('Allow individual sites to override network settings', 'softworld-ai-seo'); ?>
                                                        </label>
                                                        <p class="description">
                                                            <?php _e('When enabled, site administrators can request permission to use custom settings.', 'softworld-ai-seo'); ?>
                                                        </p>
                                                    </td>
                                                </tr>
                        
                                                <tr>
                                                    <th scope="row"><?php _e('Auto-approve Override Requests', 'softworld-ai-seo'); ?></th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox" name="auto_approve_overrides" value="1" 
                                                                   <?php checked($network_settings['auto_approve_overrides'], 1); ?>>
                                                            <?php _e('Automatically approve override requests', 'softworld-ai-seo'); ?>
                                                        </label>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <!-- Default Network Settings -->
                                        <div class="softworld-settings-section">
                                            <h2><?php _e('Default Network Settings', 'softworld-ai-seo'); ?></h2>
                                            <p class="description">
                                                <?php _e('These settings will be inherited by all sites in the network (unless overridden).', 'softworld-ai-seo'); ?>
                                            </p>
                    
                                            <?php foreach ($available_settings as $section_key => $section): ?>
                                                            <div class="settings-group">
                                                                <h3><?php echo esc_html($section['title']); ?></h3>
                                                                <table class="form-table">
                                                                    <?php foreach ($section['settings'] as $setting_key => $setting): ?>
                                                                                    <tr>
                                                                                        <th scope="row"><?php echo esc_html($setting['label']); ?></th>
                                                                                        <td>
                                                                                            <?php $this->render_setting_field($setting_key, $setting, $network_settings); ?>
                                                                                            <?php if (!empty($setting['description'])): ?>
                                                                                                            <p class="description"><?php echo esc_html($setting['description']); ?></p>
                                                                                            <?php endif; ?>
                                                                                        </td>
                                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </table>
                                                            </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="submit">
                                            <button type="submit" class="button button-primary">
                                                <?php _e('Save Network Settings', 'softworld-ai-seo'); ?>
                                            </button>
                                            <button type="button" id="apply-to-all-sites" class="button button-secondary">
                                                <?php _e('Apply to All Sites', 'softworld-ai-seo'); ?>
                                            </button>
                                        </p>
                                    </form>
                                </div>
                                <?php
    }
    /** *
     *  * Render Setting Field
     */
    public function render_setting_field($setting_key, $setting, $network_settings)
    {
        $value = isset($network_settings[$setting_key]) ? $network_settings[$setting_key] : '';

        switch ($setting['type']) {
            case 'text':
                echo '<input type="text" name="' . esc_attr($setting_key) . '" value="' . esc_attr($value) . '" class="regular-text">';
                break;

            case 'textarea':
                echo '<textarea name="' . esc_attr($setting_key) . '" class="large-text" rows="3">' . esc_textarea($value) . '</textarea>';
                break;

            case 'checkbox':
                echo '<label><input type="checkbox" name="' . esc_attr($setting_key) . '" value="1" ' . checked($value, 1, false) . '> ' . esc_html($setting['label']) . '</label>';
                break;

            case 'select':
                echo '<select name="' . esc_attr($setting_key) . '">';
                foreach ($setting['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                }
                echo '</select>';
                break;
        }
    }


    public function get_available_settings()
    {
        return [
            'general' => [
                'title' => __('General Settings', 'softworld-ai-seo'),
                'settings' => [
                    'default_seo_title' => [
                        'type' => 'text',
                        'label' => __('Default SEO Title', 'softworld-ai-seo'),
                        'description' => __('Default title template for all sites.', 'softworld-ai-seo')
                    ],
                    'default_seo_description' => [
                        'type' => 'textarea',
                        'label' => __('Default SEO Description', 'softworld-ai-seo'),
                        'description' => __('Default description template for all sites.', 'softworld-ai-seo')
                    ]
                ]
            ],
            'sitemap' => [
                'title' => __('Sitemap Settings', 'softworld-ai-seo'),
                'settings' => [
                    'ping_search_engines' => [
                        'type' => 'checkbox',
                        'label' => __('Ping Search Engines', 'softworld-ai-seo'),
                        'description' => __('Automatically ping search engines when content is updated.', 'softworld-ai-seo')
                    ],
                    'include_posts' => [
                        'type' => 'checkbox',
                        'label' => __('Include Posts in Sitemap', 'softworld-ai-seo'),
                        'description' => __('Include blog posts in XML sitemap.', 'softworld-ai-seo')
                    ],
                    'include_pages' => [
                        'type' => 'checkbox',
                        'label' => __('Include Pages in Sitemap', 'softworld-ai-seo'),
                        'description' => __('Include pages in XML sitemap.', 'softworld-ai-seo')
                    ]
                ]
            ]
        ];
    }

    /**
     * Get Network Sites with enhanced sitemap and robots.txt information
     */
    public function get_network_sites()
    {
        $sites = [];
        $network_sites = get_sites(['number' => 0]);

        foreach ($network_sites as $site) {
            switch_to_blog($site->blog_id);

            $sitemap_url = home_url('/sitemap.xml');
            $robots_url = home_url('/robots.txt');

            $sites[] = [
                'id' => $site->blog_id,
                'name' => get_bloginfo('name'),
                'url' => get_home_url(),
                'admin_url' => get_admin_url(),
                'active' => !is_main_site($site->blog_id) || get_option('softworld_ai_seo_active', false),
                'has_override' => (bool) get_option('softworld_ai_seo_override_approved', false),
                'last_activity' => get_option('softworld_ai_seo_last_activity', null),
                // NEW: Sitemap information
                'has_sitemap' => $this->site_has_sitemap($site->blog_id),
                'sitemap_url' => $sitemap_url,
                // NEW: Robots.txt information
                'custom_robots' => (bool) get_option('softworld_ai_seo_custom_robots_txt', false),
                'robots_url' => $robots_url,
                'robots_modified' => get_option('softworld_ai_seo_robots_modified', null)
            ];

            restore_current_blog();
        }

        return $sites;
    }

    /**
     * Render Import/Export Page
     */
    public function render_import_export_page()
    {
        if (!current_user_can('manage_network')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'softworld-ai-seo'));
        }

        ?>
                                <div class="wrap softworld-network-wrap">
                                    <h1><?php _e('Import/Export Network Settings', 'softworld-ai-seo'); ?></h1>
            
                                    <div class="softworld-import-export-container">
                                        <!-- Export Section -->
                                        <div class="softworld-export-section">
                                            <h2><?php _e('Export Settings', 'softworld-ai-seo'); ?></h2>
                                            <p><?php _e('Export your network settings to a JSON file for backup or migration.', 'softworld-ai-seo'); ?></p>
                    
                                            <form id="softworld-export-form">
                                                <?php wp_nonce_field('softworld_network_export', 'export_nonce'); ?>
                        
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row"><?php _e('Export Options', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <label><input type="checkbox" name="export_network_settings" value="1" checked> <?php _e('Network Settings', 'softworld-ai-seo'); ?></label><br>
                                                            <label><input type="checkbox" name="export_site_settings" value="1"> <?php _e('All Site Settings', 'softworld-ai-seo'); ?></label><br>
                                                            <label><input type="checkbox" name="export_license_info" value="1"> <?php _e('License Information', 'softworld-ai-seo'); ?></label><br>
                                                            <label><input type="checkbox" name="export_override_permissions" value="1" checked> <?php _e('Override Permissions', 'softworld-ai-seo'); ?></label>
                                                        </td>
                                                    </tr>
                                                </table>
                        
                                                <p class="submit">
                                                    <button type="submit" class="button button-primary">
                                                        <?php _e('Export Settings', 'softworld-ai-seo'); ?>
                                                    </button>
                                                </p>
                                            </form>
                                        </div>

                                        <!-- Import Section -->
                                        <div class="softworld-import-section">
                                            <h2><?php _e('Import Settings', 'softworld-ai-seo'); ?></h2>
                                            <p><?php _e('Import settings from a previously exported JSON file.', 'softworld-ai-seo'); ?></p>
                    
                                            <form id="softworld-import-form" enctype="multipart/form-data">
                                                <?php wp_nonce_field('softworld_network_import', 'import_nonce'); ?>
                        
                                                <table class="form-table">
                                                    <tr>
                                                        <th scope="row"><?php _e('Settings File', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <input type="file" name="import_file" accept=".json" required>
                                                            <p class="description">
                                                                <?php _e('Select a JSON file exported from Softworld AI SEO.', 'softworld-ai-seo'); ?>
                                                            </p>
                                                        </td>
                                                    </tr>
                            
                                                    <tr>
                                                        <th scope="row"><?php _e('Import Options', 'softworld-ai-seo'); ?></th>
                                                        <td>
                                                            <label><input type="checkbox" name="overwrite_existing" value="1"> <?php _e('Overwrite Existing Settings', 'softworld-ai-seo'); ?></label><br>
                                                            <label><input type="checkbox" name="apply_to_sites" value="1"> <?php _e('Apply to All Sites', 'softworld-ai-seo'); ?></label><br>
                                                            <label><input type="checkbox" name="backup_before_import" value="1" checked> <?php _e('Create Backup Before Import', 'softworld-ai-seo'); ?></label>
                                                        </td>
                                                    </tr>
                                                </table>
                        
                                                <p class="submit">
                                                    <button type="submit" class="button button-primary">
                                                        <?php _e('Import Settings', 'softworld-ai-seo'); ?>
                                                    </button>
                                                </p>
                                            </form>
                                        </div>
                                    </div>
            
                                    <!-- Import/Export Log -->
                                    <div class="softworld-import-export-log">
                                        <h2><?php _e('Recent Activity', 'softworld-ai-seo'); ?></h2>
                                        <div id="import-export-log">
                                            <?php $this->render_import_export_log(); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
    }

    /**
     * Render Import/Export Log
     */
    public function render_import_export_log()
    {
        $log = get_site_option('softworld_ai_seo_import_export_log', []);

        if (empty($log)) {
            echo '<p>' . __('No recent activity.', 'softworld-ai-seo') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Date', 'softworld-ai-seo') . '</th>';
        echo '<th>' . __('Action', 'softworld-ai-seo') . '</th>';
        echo '<th>' . __('User', 'softworld-ai-seo') . '</th>';
        echo '<th>' . __('Details', 'softworld-ai-seo') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach (array_reverse(array_slice($log, -10)) as $entry) {
            echo '<tr>';
            echo '<td>' . date('Y-m-d H:i', strtotime($entry['date'])) . '</td>';
            echo '<td>' . esc_html($entry['action']) . '</td>';
            echo '<td>' . esc_html($entry['user']) . '</td>';
            echo '<td>' . esc_html($entry['details']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Enhanced Network Statistics with sitemap count
     */
    public function get_network_statistics()
    {
        $sites = $this->get_network_sites();

        return [
            'total_sites' => count($sites),
            'active_sites' => count(array_filter($sites, function ($site) {
                return $site['active'];
            })),
            'override_sites' => count(array_filter($sites, function ($site) {
                return $site['has_override'];
            })),
            'sitemap_count' => count(array_filter($sites, function ($site) {
                return $site['has_sitemap'];
            }))
        ];
    }

    /**
     * Get Network License Info
     */
    public function get_network_license_info()
    {
        $license_data = get_site_option($this->license_key, []);

        return wp_parse_args($license_data, [
            'key' => '',
            'valid' => false,
            'expires' => '',
            'customer_name' => '',
            'site_limit' => 0,
            'sites_used' => count($this->get_network_sites())
        ]);
    }

    /**
     * AJAX: Save Network Settings
     */
    public function ajax_save_network_settings()
    {
        check_ajax_referer('softworld_network_settings', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $settings = [];
        $available_settings = $this->get_available_settings();

        // Process all available settings
        foreach ($available_settings as $section) {
            foreach ($section['settings'] as $key => $setting) {
                if (isset($_POST[$key])) {
                    $settings[$key] = sanitize_text_field($_POST[$key]);
                } elseif ($setting['type'] === 'checkbox') {
                    $settings[$key] = 0;
                }
            }
        }

        // Add system settings
        $settings['allow_site_overrides'] = isset($_POST['allow_site_overrides']) ? 1 : 0;
        $settings['auto_approve_overrides'] = isset($_POST['auto_approve_overrides']) ? 1 : 0;

        update_site_option($this->network_settings_key, $settings);

        // Log the action
        $this->log_import_export_action('Settings Saved', get_current_user()->display_name, 'Network settings updated');

        wp_send_json_success(__('Network settings saved successfully.', 'softworld-ai-seo'));
    }

    /**
     * AJAX: Activate License
     */
    public function ajax_activate_license()
    {
        check_ajax_referer('softworld_network_license', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $license_key = sanitize_text_field($_POST['license_key']);

        if (empty($license_key)) {
            wp_send_json_error(__('Please enter a license key.', 'softworld-ai-seo'));
        }

        // Validate license with remote server
        $validation_result = $this->validate_license_with_server($license_key);

        if ($validation_result['success']) {
            $license_data = [
                'key' => $license_key,
                'valid' => true,
                'expires' => $validation_result['expires'],
                'customer_name' => $validation_result['customer_name'],
                'site_limit' => $validation_result['site_limit'],
                'activated_at' => current_time('mysql')
            ];

            update_site_option($this->license_key, $license_data);

            wp_send_json_success([
                'message' => __('License activated successfully!', 'softworld-ai-seo'),
                'license_info' => $license_data
            ]);
        } else {
            wp_send_json_error($validation_result['message']);
        }
    }

    /**
     * AJAX: Deactivate License
     */
    public function ajax_deactivate_license()
    {
        check_ajax_referer('softworld_network_license', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $license_info = $this->get_network_license_info();

        // Deactivate with remote server
        $this->deactivate_license_with_server($license_info['key']);

        // Clear local license data
        delete_site_option($this->license_key);

        wp_send_json_success(__('License deactivated successfully.', 'softworld-ai-seo'));
    }

    /**
     * AJAX: Export Settings
     */
    public function ajax_export_settings()
    {
        check_ajax_referer('softworld_network_export', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_die(__('Permission denied', 'softworld-ai-seo'));
        }

        $export_data = [];

        if (isset($_POST['export_network_settings'])) {
            $export_data['network_settings'] = $this->get_network_settings();
        }

        if (isset($_POST['export_site_settings'])) {
            $export_data['site_settings'] = $this->get_all_site_settings();
        }

        if (isset($_POST['export_license_info'])) {
            $license_info = $this->get_network_license_info();
            // Remove sensitive data
            unset($license_info['key']);
            $export_data['license_info'] = $license_info;
        }

        if (isset($_POST['export_override_permissions'])) {
            $export_data['override_permissions'] = $this->get_override_permissions();
        }

        $export_data['export_date'] = current_time('mysql');
        $export_data['export_version'] = AI_SEO_VERSION;
        $export_data['network_url'] = network_home_url();

        // Log export action
        $this->log_import_export_action('Export', get_current_user()->display_name, 'Settings exported');

        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="softworld-seo-network-settings-' . date('Y-m-d-H-i-s') . '.json"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * AJAX: Import Settings
     */
    public function ajax_import_settings()
    {
        check_ajax_referer('softworld_network_import', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Please select a valid file to import.', 'softworld-ai-seo'));
        }

        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);

        if (!$import_data) {
            wp_send_json_error(__('Invalid JSON file.', 'softworld-ai-seo'));
        }

        // Validate import data
        if (!isset($import_data['export_version'])) {
            wp_send_json_error(__('Invalid export file format.', 'softworld-ai-seo'));
        }

        $results = [];
        $overwrite = isset($_POST['overwrite_existing']);
        $apply_to_sites = isset($_POST['apply_to_sites']);

        // Create backup if requested
        if (isset($_POST['backup_before_import'])) {
            $this->create_backup_before_import();
        }

        // Import network settings
        if (isset($import_data['network_settings'])) {
            if ($overwrite || !get_site_option($this->network_settings_key)) {
                update_site_option($this->network_settings_key, $import_data['network_settings']);
                $results[] = __('Network settings imported', 'softworld-ai-seo');
            }
        }

        // Import site settings
        if (isset($import_data['site_settings']) && $apply_to_sites) {
            $imported_sites = $this->import_site_settings($import_data['site_settings'], $overwrite);
            $results[] = sprintf(__('%d site settings imported', 'softworld-ai-seo'), $imported_sites);
        }

        // Import override permissions
        if (isset($import_data['override_permissions'])) {
            $this->import_override_permissions($import_data['override_permissions']);
            $results[] = __('Override permissions imported', 'softworld-ai-seo');
        }

        // Log import action
        $this->log_import_export_action('Import', get_current_user()->display_name, 'Settings imported from ' . $_FILES['import_file']['name']);

        wp_send_json_success([
            'message' => __('Import completed successfully!', 'softworld-ai-seo'),
            'details' => $results
        ]);
    }

    /**
     * AJAX: Apply Settings to All Sites
     */
    public function ajax_apply_settings_to_sites()
    {
        check_ajax_referer('softworld_network_settings', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $network_settings = $this->get_network_settings();
        $sites = get_sites(['number' => 0]);
        $updated_count = 0;

        foreach ($sites as $site) {
            // Skip sites with approved overrides
            switch_to_blog($site->blog_id);

            if (!get_option('softworld_ai_seo_override_approved', false)) {
                // Apply network settings to this site
                foreach ($network_settings as $key => $value) {
                    if (strpos($key, 'default_') === 0) {
                        $site_key = str_replace('default_', 'softworld_ai_seo_', $key);
                        update_option($site_key, $value);
                    }
                }
                $updated_count++;
            }

            restore_current_blog();
        }

        wp_send_json_success([
            'message' => sprintf(__('Settings applied to %d sites.', 'softworld-ai-seo'), $updated_count)
        ]);
    }

    /**
     * Maybe Add Site Menu (for override requests)
     */
    public function maybe_add_site_menu()
    {
        if (!is_multisite() || is_network_admin()) {
            return;
        }

        $network_settings = $this->get_network_settings();

        if (!$network_settings['allow_site_overrides']) {
            return;
        }

        // Check if the main menu exists
        global $menu;
        $menu_slug = 'softworld-ai-seo';
        $menu_exists = false;
        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === $menu_slug) {
                    $menu_exists = true;
                    break;
                }
            }
        }

        // If the main menu doesn't exist, add it
        if (!$menu_exists) {
            add_menu_page(
                __('Softworld AI SEO', 'softworld-ai-seo'),
                __('Softworld SEO', 'softworld-ai-seo'),
                'manage_options',
                'softworld-ai-seo',
                [$this, 'render_main_plugin_page'],
                'dashicons-chart-area',
                80
            );
        }

        add_submenu_page(
            'softworld-ai-seo',
            __('Network Settings', 'softworld-ai-seo'),
            __('Network Settings', 'softworld-ai-seo'),
            'manage_options',
            'softworld-ai-seo-network-override',
            [$this, 'render_site_override_page']
        );
    }

    /**
     * Maybe Restrict Site Settings
     */
    public function maybe_restrict_site_settings()
    {
        if (!is_multisite() || is_network_admin()) {
            return;
        }

        $network_settings = $this->get_network_settings();
        $override_approved = get_option('softworld_ai_seo_override_approved', false);

        // If overrides are not allowed and site doesn't have approval, redirect certain pages
        if (!$network_settings['allow_site_overrides'] && !$override_approved) {
            // Add restrictions here if needed
        }
    }

    /**
     * AJAX: Request Override
     */
    public function ajax_request_override()
    {
        check_ajax_referer('softworld_site_override', 'nonce');

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

    /**
     * Render Site Override Request Page
     */
    public function render_site_override_page()
    {
        $override_approved = get_option('softworld_ai_seo_override_approved', false);
        $override_requested = get_option('softworld_ai_seo_override_requested', false);
        $network_settings = $this->get_network_settings();

        ?>
                        <div class="wrap">
                            <h1><?php _e('Network Settings Override', 'softworld-ai-seo'); ?></h1>
            
                            <?php if ($override_approved): ?>
                                        <div class="notice notice-success">
                                            <p><?php _e('Your override request has been approved. You can now configure custom settings for this site.', 'softworld-ai-seo'); ?></p>
                                        </div>
                
                                        <p>
                                            <a href="<?php echo admin_url('admin.php?page=softworld-ai-seo'); ?>" class="button button-primary">
                                                <?php _e('Configure Settings', 'softworld-ai-seo'); ?>
                                            </a>
                                            <button type="button" id="revoke-override" class="button button-secondary">
                                                <?php _e('Revert to Network Settings', 'softworld-ai-seo'); ?>
                                            </button>
                                        </p>
                
                            <?php elseif ($override_requested): ?>
                                        <div class="notice notice-info">
                                            <p><?php _e('Your override request is pending approval from the network administrator.', 'softworld-ai-seo'); ?></p>
                                        </div>
                
                            <?php else: ?>
                                        <div class="notice notice-info">
                                            <p><?php _e('This site currently inherits settings from the network administrator.', 'softworld-ai-seo'); ?></p>
                                        </div>
                
                                        <form id="override-request-form">
                                            <?php wp_nonce_field('softworld_site_override', 'override_nonce'); ?>
                    
                                            <table class="form-table">
                                                <tr>
                                                    <th scope="row"><?php _e('Request Override', 'softworld-ai-seo'); ?></th>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox" name="request_override" value="1">
                                                            <?php _e('I want to use custom SEO settings for this site', 'softworld-ai-seo'); ?>
                                                        </label>
                                                        <p class="description">
                                                            <?php _e('This will send a request to the network administrator for permission to override network settings.', 'softworld-ai-seo'); ?>
                                                        </p>
                                                    </td>
                                                </tr>
                        
                                                <tr>
                                                    <th scope="row"><?php _e('Justification', 'softworld-ai-seo'); ?></th>
                                                    <td>
                                                        <textarea name="override_reason" class="large-text" rows="4" placeholder="<?php _e('Please explain why you need custom settings...', 'softworld-ai-seo'); ?>"></textarea>
                                                    </td>
                                                </tr>
                                            </table>
                    
                                            <p class="submit">
                                                <button type="submit" class="button button-primary">
                                                    <?php _e('Request Override Permission', 'softworld-ai-seo'); ?>
                                                </button>
                                            </p>
                                        </form>
                            <?php endif; ?>
                        </div>
                        <?php
    }

    /**
     * Render Main Plugin Page for sites with override
     */
    public function render_main_plugin_page()
    {
        // Check if site has override permission
        $override_approved = get_option('softworld_ai_seo_override_approved', false);

        if ($override_approved) {
            // Render the main plugin settings page
            echo '<div class="wrap">';
            echo '<h1>' . __('Softworld AI SEO Settings', 'softworld-ai-seo') . '</h1>';
            echo '<p>' . __('You have override permission. Configure your site-specific settings here.', 'softworld-ai-seo') . '</p>';
            // Add your settings form here
            echo '</div>';
        } else {
            // Redirect to network override page
            wp_redirect(admin_url('admin.php?page=softworld-ai-seo-network-override'));
            exit;
        }
    }

    /**
     * Validate License with Server
     */
    public function validate_license_with_server($license_key)
    {
        // Mock validation for development - replace with actual API call
        return [
            'success' => true,
            'expires' => date('Y-m-d', strtotime('+1 year')),
            'customer_name' => 'Demo Customer',
            'site_limit' => 999,
            'message' => 'License is valid'
        ];

        /* Uncomment this for actual API integration:
        $response = wp_remote_post('https://softworldit.com/api/validate-license', [
            'body' => [
                'license_key' => $license_key,
                'domain' => network_home_url(),
                'product' => 'softworld-ai-seo'
            ],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => __('Unable to connect to license server.', 'softworld-ai-seo')
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data) {
            return [
                'success' => false,
                'message' => __('Invalid response from license server.', 'softworld-ai-seo')
            ];
        }

        return $data;
        */
    }

    /**
     * Deactivate License with Server
     */
    public function deactivate_license_with_server($license_key)
    {
        // For development, just return
        return;

        /* Uncomment for actual API integration:
        wp_remote_post('https://softworldit.com/api/deactivate-license', [
            'body' => [
                'license_key' => $license_key,
                'domain' => network_home_url()
            ],
            'timeout' => 30
        ]);
        */
    }

    /**
     * Get All Site Settings
     */
    public function get_all_site_settings()
    {
        $sites = get_sites(['number' => 0]);
        $all_settings = [];

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            $site_settings = [];
            $option_names = [
                'softworld_ai_seo_title',
                'softworld_ai_seo_description',
                'softworld_sitemap_settings',
                'softworld_ai_seo_robots_txt'
            ];

            foreach ($option_names as $option_name) {
                $value = get_option($option_name);
                if ($value !== false) {
                    $site_settings[$option_name] = $value;
                }
            }

            $all_settings[$site->blog_id] = [
                'site_url' => get_home_url(),
                'site_name' => get_bloginfo('name'),
                'settings' => $site_settings
            ];

            restore_current_blog();
        }

        return $all_settings;
    }

    /**
     * Get Override Permissions
     */
    public function get_override_permissions()
    {
        $sites = get_sites(['number' => 0]);
        $permissions = [];

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            if (get_option('softworld_ai_seo_override_approved', false)) {
                $permissions[$site->blog_id] = [
                    'site_url' => get_home_url(),
                    'approved' => true,
                    'approved_date' => get_option('softworld_ai_seo_override_approved_date', ''),
                    'reason' => get_option('softworld_ai_seo_override_reason', '')
                ];
            }

            restore_current_blog();
        }

        return $permissions;
    }

    /**
     * Import Site Settings
     */
    public function import_site_settings($site_settings, $overwrite = false)
    {
        $imported_count = 0;

        foreach ($site_settings as $blog_id => $site_data) {
            $site = get_site($blog_id);
            if (!$site)
                continue;

            switch_to_blog($blog_id);

            foreach ($site_data['settings'] as $option_name => $value) {
                if ($overwrite || get_option($option_name) === false) {
                    update_option($option_name, $value);
                }
            }

            $imported_count++;
            restore_current_blog();
        }

        return $imported_count;
    }

    /**
     * Import Override Permissions
     */
    public function import_override_permissions($permissions)
    {
        foreach ($permissions as $blog_id => $permission_data) {
            $site = get_site($blog_id);
            if (!$site)
                continue;

            switch_to_blog($blog_id);

            update_option('softworld_ai_seo_override_approved', $permission_data['approved']);
            update_option('softworld_ai_seo_override_approved_date', $permission_data['approved_date']);
            update_option('softworld_ai_seo_override_reason', $permission_data['reason']);

            restore_current_blog();
        }
    }

    /**
     * Create Backup Before Import
     */
    public function create_backup_before_import()
    {
        $backup_data = [
            'network_settings' => $this->get_network_settings(),
            'site_settings' => $this->get_all_site_settings(),
            'override_permissions' => $this->get_override_permissions(),
            'backup_date' => current_time('mysql')
        ];

        $backup_key = 'softworld_ai_seo_backup_' . time();
        update_site_option($backup_key, $backup_data);

        // Keep only last 5 backups
        $this->cleanup_old_backups();
    }

    /**
     * Cleanup Old Backups
     */
    public function cleanup_old_backups()
    {
        global $wpdb;

        $backups = $wpdb->get_results(
            "SELECT meta_key FROM {$wpdb->sitemeta} 
             WHERE meta_key LIKE 'softworld_ai_seo_backup_%' 
             ORDER BY meta_key DESC"
        );

        if (count($backups) > 5) {
            for ($i = 5; $i < count($backups); $i++) {
                delete_site_option($backups[$i]->meta_key);
            }
        }
    }

    /**
     * Log Import/Export Action
     */
    public function log_import_export_action($action, $user, $details)
    {
        $log = get_site_option('softworld_ai_seo_import_export_log', []);

        $log[] = [
            'date' => current_time('mysql'),
            'action' => $action,
            'user' => $user,
            'details' => $details
        ];

        // Keep only last 50 entries
        $log = array_slice($log, -50);

        update_site_option('softworld_ai_seo_import_export_log', $log);
    }

    /**
     * Check Network License
     */
    public function check_network_license($is_valid, $context)
    {
        if (!is_multisite()) {
            return $is_valid;
        }

        $license_info = $this->get_network_license_info();
        return $license_info['valid'];
    }

    /**
     * Maybe Inherit Network Setting
     */
    public function maybe_inherit_network_setting($value, $option, $default)
    {
        if (!is_multisite() || is_network_admin()) {
            return $value;
        }

        // Check if site has override permission
        if (get_option('softworld_ai_seo_override_approved', false)) {
            return $value;
        }

        // Try to inherit from network settings
        $network_settings = $this->get_network_settings();
        $network_key = str_replace('softworld_ai_seo_', 'default_', $option);

        if (isset($network_settings[$network_key])) {
            return $network_settings[$network_key];
        }

        return $value;
    }

    /**
     * Log Settings Change
     */
    public function log_settings_change($option_name, $new_value)
    {
        // Log settings changes for audit purposes
        $this->log_import_export_action(
            'Setting Changed',
            get_current_user()->display_name ?? 'System',
            "Option: {$option_name}"
        );
    }

    /**
     * Check if site has sitemap
     */
    private function site_has_sitemap($blog_id)
    {
        switch_to_blog($blog_id);
        $has_sitemap = get_option('softworld_ai_seo_sitemap_enabled', true);
        restore_current_blog();
        return $has_sitemap;
    }

    public function get_network_settings()
    {
        $default_settings = [
            'allow_site_overrides' => 0,
            'auto_approve_overrides' => 0,
            'default_seo_title' => '',
            'default_seo_description' => '',
            'default_robots_txt' => '',
            'ping_search_engines' => 1,
            'include_posts' => 1,
            'include_pages' => 1
        ];

        return wp_parse_args(get_site_option($this->network_settings_key, []), $default_settings);
    }

    /**
     * Get NGINX rules status
     */
    private function get_nginx_rules_status()
    {
        $rules = get_site_option('softworld_ai_seo_nginx_rules', '');
        $last_generated = get_site_option('softworld_ai_seo_nginx_rules_generated', '');

        if (empty($rules)) {
            return __('No rules generated yet', 'softworld-ai-seo');
        }

        return sprintf(__('Last generated: %s'), date('Y-m-d H:i', strtotime($last_generated)));
    }

    /**
     * Get robots.txt status
     */
    private function get_robots_txt_status()
    {
        $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', '');
        $last_updated = get_site_option('softworld_ai_seo_network_robots_updated', '');

        if (empty($network_robots)) {
            return __('Using WordPress default', 'softworld-ai-seo');
        }

        return sprintf(__('Last updated: %s'), date('Y-m-d H:i', strtotime($last_updated)));
    }

    /**
     * Get default network robots.txt content
     */
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

    /**
     * AJAX: Generate NGINX Rules
     */
    public function ajax_generate_nginx_rules()
    {
        check_ajax_referer('softworld_network_nginx', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $include_all_sites = isset($_POST['include_all_sites']);
        $include_subdirs = isset($_POST['include_subdirs']);
        $include_main_site = isset($_POST['include_main_site']);
        $add_caching = isset($_POST['add_caching']);
        $add_compression = isset($_POST['add_compression']);
        $add_security = isset($_POST['add_security']);
        $cache_duration = intval($_POST['cache_duration']);

        $sites = $this->get_network_sites();
        $rules = $this->generate_nginx_sitemap_rules($sites, [
            'include_all_sites' => $include_all_sites,
            'include_subdirs' => $include_subdirs,
            'include_main_site' => $include_main_site,
            'add_caching' => $add_caching,
            'add_compression' => $add_compression,
            'add_security' => $add_security,
            'cache_duration' => $cache_duration
        ]);

        // Save generated rules
        update_site_option('softworld_ai_seo_nginx_rules', $rules);
        update_site_option('softworld_ai_seo_nginx_rules_generated', current_time('mysql'));

        wp_send_json_success([
            'rules' => $rules,
            'message' => __('NGINX rules generated successfully!', 'softworld-ai-seo')
        ]);
    }

    /**
     * Generate NGINX Sitemap Rules
     */
    private function generate_nginx_sitemap_rules($sites, $options)
    {
        $rules = "# Softworld AI SEO - NGINX Sitemap Rules\n";
        $rules .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";

        // Add compression rules if requested
        if ($options['add_compression']) {
            $rules .= "# Enable gzip compression for XML files\n";
            $rules .= "location ~* \\.(xml|xsl)$ {\n";
            $rules .= "    gzip on;\n";
            $rules .= "    gzip_vary on;\n";
            $rules .= "    gzip_types application/xml text/xml;\n";
            $rules .= "}\n\n";
        }

        // Add security headers if requested
        if ($options['add_security']) {
            $rules .= "# Security headers for XML files\n";
            $rules .= "location ~* \\.(xml)$ {\n";
            $rules .= "    add_header X-Content-Type-Options nosniff;\n";
            $rules .= "    add_header X-Frame-Options DENY;\n";
            $rules .= "    add_header X-XSS-Protection \"1; mode=block\";\n";
            $rules .= "}\n\n";
        }

        // Sitemap location rules
        $rules .= "# Sitemap location rules\n";

        if (is_subdomain_install()) {
            // Subdomain installation
            $rules .= "location = /sitemap.xml {\n";
            $rules .= "    try_files \$uri \$uri/ /index.php?sitemap=1;\n";
        } else {
            // Subdirectory installation
            if ($options['include_main_site']) {
                $rules .= "location = /sitemap.xml {\n";
                $rules .= "    try_files \$uri \$uri/ /index.php?sitemap=1;\n";
                $rules .= "}\n\n";
            }

            if ($options['include_subdirs']) {
                foreach ($sites as $site) {
                    if ($site['id'] == 1)
                        continue; // Skip main site

                    $site_path = trim(parse_url($site['url'], PHP_URL_PATH), '/');
                    if ($site_path) {
                        $rules .= "location = /{$site_path}/sitemap.xml {\n";
                        $rules .= "    try_files \$uri \$uri/ /{$site_path}/index.php?sitemap=1;\n";
                        $rules .= "}\n\n";
                    }
                }
            }
        }

        // Add caching rules if requested
        if ($options['add_caching']) {
            $rules .= "# Caching rules for sitemaps\n";
            $rules .= "location ~* sitemap.*\\.xml$ {\n";
            $rules .= "    expires {$options['cache_duration']}s;\n";
            $rules .= "    add_header Cache-Control \"public, must-revalidate, proxy-revalidate\";\n";
            $rules .= "    add_header X-Cached-By \"NGINX\";\n";
        }

        $rules .= "}\n\n";

        // Additional sitemap types
        $rules .= "# Additional sitemap types\n";
        $rules .= "location ~* sitemap.*\\.(xml|xsl)$ {\n";
        $rules .= "    try_files \$uri \$uri/ /index.php?sitemap=\$1;\n";
        $rules .= "}\n\n";

        $rules .= "# End Softworld AI SEO NGINX Rules\n";

        return $rules;
    }

    /**
     * AJAX: Save Network Robots.txt
     */
    public function ajax_save_network_robots_txt()
    {
        check_ajax_referer('softworld_network_robots', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $robots_content = sanitize_textarea_field($_POST['network_robots_content']);
        $auto_include_sitemaps = isset($_POST['auto_include_sitemaps']);
        $block_admin_areas = isset($_POST['block_admin_areas']);
        $allow_site_overrides = isset($_POST['allow_site_overrides']);

        // Process robots.txt content
        if ($auto_include_sitemaps && strpos($robots_content, '%%sitemap_url%%') === false) {
            $robots_content .= "\n\nSitemap: %%sitemap_url%%";
        }

        // Save network robots.txt
        update_site_option('softworld_ai_seo_network_robots_txt', $robots_content);
        update_site_option('softworld_ai_seo_network_robots_updated', current_time('mysql'));
        update_site_option('softworld_ai_seo_robots_allow_overrides', $allow_site_overrides);

        wp_send_json_success(__('Network robots.txt saved successfully!', 'softworld-ai-seo'));
    }

    /**
     * AJAX: Apply Robots.txt to All Sites
     */
    public function ajax_apply_robots_to_sites()
    {
        check_ajax_referer('softworld_network_robots', 'nonce');

        if (!current_user_can('manage_network')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', '');
        if (empty($network_robots)) {
            wp_send_json_error(__('No network robots.txt content to apply.', 'softworld-ai-seo'));
        }

        $sites = get_sites(['number' => 0]);
        $applied_count = 0;

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            // Don't override sites that have custom robots.txt and overrides are allowed
            $allow_overrides = get_site_option('softworld_ai_seo_robots_allow_overrides', false);
            $has_custom = get_option('softworld_ai_seo_custom_robots_txt', false);

            if (!($allow_overrides && $has_custom)) {
                // Apply network robots.txt with site-specific sitemap URL
                $site_robots = str_replace('%%sitemap_url%%', home_url('/sitemap.xml'), $network_robots);
                update_option('softworld_ai_seo_robots_txt_content', $site_robots);
                update_option('softworld_ai_seo_robots_modified', current_time('mysql'));
                $applied_count++;
            }

            restore_current_blog();
        }

        wp_send_json_success([
            'message' => sprintf(__('Robots.txt applied to %d sites.', 'softworld-ai-seo'), $applied_count)
        ]);
    }

    /**
     * Handle Network Robots.txt Display
     */
    public function handle_network_robots_txt()
    {
        if (!is_multisite()) {
            return;
        }

        // Check if site has custom robots.txt
        $custom_robots = get_option('softworld_ai_seo_custom_robots_txt', false);
        $allow_overrides = get_site_option('softworld_ai_seo_robots_allow_overrides', false);

        if (!$allow_overrides || !$custom_robots) {
            // Use network robots.txt
            $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', '');
            if (!empty($network_robots)) {
                $robots_content = str_replace('%%sitemap_url%%', home_url('/sitemap.xml'), $network_robots);
                echo esc_html($robots_content);
                return;
            }
        }
    }

    /**
     * Filter Network Robots.txt
     */
    public function filter_network_robots_txt($output, $public)
    {
        if (!is_multisite() || !$public) {
            return $output;
        }

        // Check if site has custom robots.txt override
        $custom_robots = get_option('softworld_ai_seo_custom_robots_txt', false);
        $allow_overrides = get_site_option('softworld_ai_seo_robots_allow_overrides', false);

        if (!$allow_overrides || !$custom_robots) {
            // Use network robots.txt
            $network_robots = get_site_option('softworld_ai_seo_network_robots_txt', '');
            if (!empty($network_robots)) {
                $robots_content = str_replace('%%sitemap_url%%', home_url('/sitemap.xml'), $network_robots);
                return $robots_content;
            }
        }

        return $output;
    }

    // ... [Previous methods remain unchanged] ...

    /**
     * Add NGINX and Robots.txt pages to network menu
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

    // ... [All other existing methods remain the same] ...
}