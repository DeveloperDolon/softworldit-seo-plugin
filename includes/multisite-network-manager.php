<?php
/**
 * Multisite Network Manager
 * Handles multisite network support for license, settings, and import/export
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
                'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'softworld-ai-seo')
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
     * Render Network Admin Dashboard
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
                    </ul>
                </div>

                <div class="softworld-network-card">
                    <h3><?php _e('Quick Actions', 'softworld-ai-seo'); ?></h3>
                    <div class="quick-actions">
                        <a href="<?php echo network_admin_url('admin.php?page=softworld-ai-seo-network-settings'); ?>" class="button button-primary">
                            <?php _e('Manage Settings', 'softworld-ai-seo'); ?>
                        </a>
                        <a href="<?php echo network_admin_url('admin.php?page=softworld-ai-seo-network-import-export'); ?>" class="button button-secondary">
                            <?php _e('Import/Export', 'softworld-ai-seo'); ?>
                        </a>
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
                                <th><?php _e('Last Activity', 'softworld-ai-seo'); ?></th>
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
                                        <td><?php echo $site['last_activity'] ? date('Y-m-d H:i', strtotime($site['last_activity'])) : __('Never', 'softworld-ai-seo'); ?></td>
                                        <td>
                                            <a href="<?php echo $site['admin_url']; ?>" class="button button-small" target="_blank">
                                                <?php _e('Visit Admin', 'softworld-ai-seo'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5"><?php _e('No sites found.', 'softworld-ai-seo'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
     * Get Network Sites
     */
    public function get_network_sites()
    {
        $sites = [];
        $network_sites = get_sites(['number' => 0]);
        
        foreach ($network_sites as $site) {
            switch_to_blog($site->blog_id);
            
            $sites[] = [
                'id' => $site->blog_id,
                'name' => get_bloginfo('name'),
                'url' => get_home_url(),
                'admin_url' => get_admin_url(),
                'active' => !is_main_site($site->blog_id) || get_option('softworld_ai_seo_active', false),
                'has_override' => (bool) get_option('softworld_ai_seo_override_approved', false),
                'last_activity' => get_option('softworld_ai_seo_last_activity', null)
            ];
            
            restore_current_blog();
        }
        
        return $sites;
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
     * Get Network Statistics
     */
    public function get_network_statistics()
    {
        $sites = $this->get_network_sites();
        
        return [
            'total_sites' => count($sites),
            'active_sites' => count(array_filter($sites, function($site) { return $site['active']; })),
            'override_sites' => count(array_filter($sites, function($site) { return $site['has_override']; }))
        ];
    }

    /**
     * Get Network Settings
     */
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
     * Get Available Settings Configuration
     */
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
     * Render Setting Field
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
    public function render_main_plugin_page() {
        // Check if site has override permission
        $override_approved = get_option('softworld_ai_seo_override_approved', false);
        
        if ($override_approved) {
            // Render the main plugin settings page
            echo '<div class="wrap">';
            echo '<h1>'.__('Softworld AI SEO Settings', 'softworld-ai-seo').'</h1>';
            echo '<p>'.__('You have override permission. Configure your site-specific settings here.', 'softworld-ai-seo').'</p>';
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
            if (!$site) continue;
            
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
            if (!$site) continue;
            
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
}