<?php
/**
 * Plugin Name: SOFTWORLD AI SEO Plugin
 * Plugin URI:  https://wordpress.org/plugins/softworld-ai-seo-plugin/
 * Description: Advance SEO Plugin with AI-powered content optimization using OpenAI + Dynamic XML Sitemap
 * Version: 1.0.0
 * Author: SOFTWORLDIT LLC
 * Author URI: https://softworldit.com/
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: softworld-ai-seo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_SEO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AI_SEO_VERSION', '1.0.0');

// Include sitemap feature
require_once plugin_dir_path(__FILE__) . 'includes/sitemap.php';
require_once plugin_dir_path(__FILE__) . 'includes/role-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/seo-tools.php';
require_once plugin_dir_path(__FILE__) . 'includes/robots-txt-editor.php'; // Add this line

// main plugin class
class Softworld_AI_SEO_Plugin
{
    private $role_manager;
    private $seo_tools;
    private $robots_txt_editor; // Add this property


    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Add Admin Pages
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize Role Manager
        $this->role_manager = new Softworld_AI_SEO_Role_Manager('softworld-ai-seo');
        // Initialize SEO Tools
        $this->seo_tools = new Softworld_AI_SEO_Tools('softworld-ai-seo');
        // Initialize Robots.txt Editor
        $this->robots_txt_editor = new Softworld_AI_SEO_Robots_Txt_Editor();

        add_action('wp_ajax_softworld_search_posts', [$this, 'ajax_search_posts']);
        add_action('wp_ajax_softworld_search_pages', [$this, 'ajax_search_pages']);
    }

    public function init()
    {
        // Create custom roles if they don't exist
        $this->create_custom_roles();

        // Initialize SEO plugin features if needed
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'), 10);

        // Save meta box data
        add_action('save_post', array($this, 'save_meta_box_data'));
    }

    public function ajax_search_posts()
    {
        check_ajax_referer('search_posts_nonce', 'nonce');

        $search = sanitize_text_field($_GET['term']);

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $search,
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $results = [];
        foreach ($posts as $post) {
            $results[] = [
                'id' => $post->ID,
                'label' => $post->post_title . ' (ID: ' . $post->ID . ')',
                'value' => $post->post_title
            ];
        }

        wp_send_json($results);
    }

    // AJAX handler for searching pages
    public function ajax_search_pages()
    {
        check_ajax_referer('search_pages_nonce', 'nonce');

        $search = sanitize_text_field($_GET['term']);

        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => $search,
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $results = [];
        foreach ($pages as $page) {
            $results[] = [
                'id' => $page->ID,
                'label' => $page->post_title . ' (ID: ' . $page->ID . ')',
                'value' => $page->post_title
            ];
        }

        wp_send_json($results);
    }

    public function save_meta_box_data($post_id)
    {
        // Check if our nonce is set
        if (!isset($_POST['softworld_ai_seo_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['softworld_ai_seo_nonce'], 'softworld_ai_seo_meta_box')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save SEO Title
        if (isset($_POST['softworld_ai_seo_title'])) {
            update_post_meta($post_id, '_softworld_ai_seo_title', sanitize_text_field($_POST['softworld_ai_seo_title']));
        }

        // Save SEO Description
        if (isset($_POST['softworld_ai_seo_description'])) {
            update_post_meta($post_id, '_softworld_ai_seo_description', sanitize_textarea_field($_POST['softworld_ai_seo_description']));
        }
    }

    private function create_custom_roles()
    {
        // Create SEO Manager role if it doesn't exist
        if (!get_role('seo_manager')) {
            add_role('seo_manager', __('SEO Manager', 'softworld-ai-seo'), array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                'manage_categories' => true,
            ));
        }

        // Create SEO Editor role if it doesn't exist
        if (!get_role('seo_editor')) {
            add_role('seo_editor', __('SEO Editor', 'softworld-ai-seo'), array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            ));
        }
    }

    public function register_meta_boxes()
    {
        // Check if current user can view the meta box
        if ($this->role_manager->should_hide_meta_box()) {
            return;
        }

        // Register your SEO meta box
        add_meta_box(
            'softworld_ai_seo_meta_box',
            'SOFTWORLD AI SEO',
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    public function render_meta_box($post)
    {
        // Your meta box content here
        echo '<div class="softworld-ai-seo-meta-box">';
        echo '<p>AI-powered SEO optimization will go here.</p>';

        // Example: Add nonce field for security
        wp_nonce_field('softworld_ai_seo_meta_box', 'softworld_ai_seo_nonce');

        // Example: Get existing meta values
        $meta_title = get_post_meta($post->ID, '_softworld_ai_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);

        // Example form fields
        echo '<div style="margin: 10px 0;">';
        echo '<label for="softworld_ai_seo_title" style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Title:</label>';
        echo '<input type="text" id="softworld_ai_seo_title" name="softworld_ai_seo_title" value="' . esc_attr($meta_title) . '" style="width: 100%; padding: 5px;">';
        echo '</div>';

        echo '<div style="margin: 10px 0;">';
        echo '<label for="softworld_ai_seo_description" style="display: block; margin-bottom: 5px; font-weight: bold;">SEO Description:</label>';
        echo '<textarea id="softworld_ai_seo_description" name="softworld_ai_seo_description" style="width: 100%; height: 80px; padding: 5px;">' . esc_textarea($meta_description) . '</textarea>';
        echo '</div>';

        echo '</div>';
    }

    public function enqueue_scripts()
    {
        // Enqueue frontend scripts if needed
        wp_enqueue_style(
            'softworldit-seo-admin-style',
            plugin_dir_url(__FILE__) . 'assets/css/admin-style.css',
            [],
            '1.0.0'
        );
    }

    public function admin_enqueue_scripts()
    {
        // Enqueue admin scripts if needed
        wp_enqueue_script(
            'softworldit-seo-admin-script',
            plugin_dir_url(__FILE__) . 'assets/js/admin-script.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public function activate()
    {
        // Register sitemap rewrite rules on activation
        SoftworldIT_SEO_Sitemap::add_rewrite();

        // Create custom roles
        $this->create_custom_roles();

        flush_rewrite_rules();
    }

    public function deactivate()
    {
        // Flush rules on deactivation
        flush_rewrite_rules();
    }

    /**
     * Register Sitemap Admin Page
     */
    public function register_admin_menu()
    {
        add_menu_page(
            'Softworld SEO',                    // Page title
            'Softworld SEO',                    // Menu title
            'manage_options',                   // Capability
            'softworld-ai-seo',                    // Menu slug
            array($this, 'render_dashboard'),   // Callback
            'dashicons-chart-area',             // Icon
            80                                  // Position
        );

        // Add Sitemap submenu
        add_submenu_page(
            'softworld-ai-seo',
            'XML Sitemap',
            'XML Sitemap',
            'manage_options',
            'softworldit-seo-sitemap',
            array($this, 'render_sitemap_admin')
        );

        // Add Robots.txt submenu
        add_submenu_page(
            'softworld-ai-seo',
            'Robots.txt Editor',
            'Robots.txt Editor',
            'manage_options',
            'softworldit-seo-robots',
            array($this, 'render_robots_admin')
        );

        add_submenu_page(
            'softworld-ai-seo',
            'AI Settings',
            'AI Settings',
            'manage_options',
            'softworldit-seo-settings',
            array($this, 'render_ai_settings_admin')
        );
    }

    public function render_robots_admin()
    {
        $this->robots_txt_editor->render_admin_page();
    }

    public function render_ai_settings_admin()
    {
        ?>
        <div class="wrap">
            <h1>AI Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('softworld_ai_settings_group');
                do_settings_sections('softworldit-seo-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_dashboard()
    {
        echo '<div class="wrap"><h1>Softworld SEO Dashboard</h1>';
        echo '<p>Welcome to the SOFTWORLD AI SEO Plugin.</p></div>';
    }

    /**
     * Render Sitemap Admin Page
     */
    public function render_sitemap_admin()
    {
        // Define default settings
        $default_settings = [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => ['category', 'post_tag'],
            'include_cpt' => [],
        ];

        // Get current settings with proper defaults
        $settings = get_option('softworld_sitemap_settings', $default_settings);
        $settings = wp_parse_args($settings, $default_settings);

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['regenerate_rules'])) {
                SoftworldIT_SEO_Sitemap::add_rewrite();
                flush_rewrite_rules();
                echo '<div class="updated notice"><p>Sitemap rules regenerated successfully.</p></div>';
            } elseif (isset($_POST['save_settings'])) {
                // Save sitemap settings
                $new_settings = [
                    'include_posts' => isset($_POST['include_posts']) ? 1 : 0,
                    'include_pages' => isset($_POST['include_pages']) ? 1 : 0,
                    'excluded_posts' => isset($_POST['excluded_posts']) ? array_map('intval', $_POST['excluded_posts']) : [],
                    'excluded_pages' => isset($_POST['excluded_pages']) ? array_map('intval', $_POST['excluded_pages']) : [],
                    'include_taxonomies' => isset($_POST['include_taxonomies']) ? $_POST['include_taxonomies'] : [],
                    'include_cpt' => isset($_POST['include_cpt']) ? $_POST['include_cpt'] : [],
                ];

                // Ensure arrays are properly formatted
                $new_settings['include_taxonomies'] = is_array($new_settings['include_taxonomies']) ?
                    $new_settings['include_taxonomies'] : [];
                $new_settings['include_cpt'] = is_array($new_settings['include_cpt']) ?
                    $new_settings['include_cpt'] : [];

                update_option('softworld_sitemap_settings', $new_settings);
                $settings = $new_settings; // Update current settings for display
                echo '<div class="updated notice"><p>Sitemap settings saved successfully.</p></div>';
            }
        }

        // Get all public taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');

        // Get all custom post types
        $cpts = get_post_types([
            'public' => true,
            '_builtin' => false
        ], 'objects');

        // Get excluded posts and pages for display
        $excluded_posts = !empty($settings['excluded_posts']) ? get_posts([
            'post_type' => 'post',
            'post__in' => $settings['excluded_posts'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]) : [];

        $excluded_pages = !empty($settings['excluded_pages']) ? get_posts([
            'post_type' => 'page',
            'post__in' => $settings['excluded_pages'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]) : [];

        // Enqueue scripts and styles for the improved UI
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('softworldit-seo-sitemap-admin', plugin_dir_url(__FILE__) . 'assets/js/sitemap-admin.js', ['jquery', 'jquery-ui-autocomplete'], '1.0.0', true);
        wp_enqueue_style('softworldit-seo-sitemap-admin', plugin_dir_url(__FILE__) . 'assets/css/sitemap-admin.css', [], '1.0.0');

        // Localize script for AJAX
        wp_localize_script('softworldit-seo-sitemap-admin', 'softworld_sitemap', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('softworld_sitemap_nonce'),
            'search_posts_nonce' => wp_create_nonce('search_posts_nonce'),
            'search_pages_nonce' => wp_create_nonce('search_pages_nonce'),
        ]);

        ?>
        <div class="wrap">
            <h1>XML Sitemap Settings</h1>
            <p>Your sitemap is automatically generated and updated whenever you publish or update content.</p>
            <p><strong>Sitemap Index:</strong>
                <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank">
                    <?php echo esc_url(home_url('/sitemap.xml')); ?>
                </a>
            </p>

            <div class="softworld-sitemap-container">
                <form method="post" id="softworld-sitemap-form">
                    <div class="softworld-sitemap-section">
                        <h2>Content Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Include in Sitemap</th>
                                <td>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="include_posts" value="1" <?php checked($settings['include_posts'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Include Posts
                                    </label><br>
                                    <label class="softworld-checkbox-label">
                                        <input type="checkbox" name="include_pages" value="1" <?php checked($settings['include_pages'], 1); ?>>
                                        <span class="checkmark"></span>
                                        Include Pages
                                    </label>
                                </td>
                            </tr>

                            <?php if (!empty($taxonomies)): ?>
                                <tr>
                                    <th scope="row">Include Taxonomies</th>
                                    <td class="softworld-checkbox-group">
                                        <?php foreach ($taxonomies as $taxonomy):
                                            $is_checked = in_array($taxonomy->name, (array) $settings['include_taxonomies']);
                                            ?>
                                            <label class="softworld-checkbox-label">
                                                <input type="checkbox" name="include_taxonomies[]"
                                                    value="<?php echo esc_attr($taxonomy->name); ?>" <?php checked($is_checked); ?>>
                                                <span class="checkmark"></span>
                                                <?php echo esc_html($taxonomy->labels->name); ?>
                                                (<?php echo esc_html($taxonomy->name); ?>)
                                            </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($cpts)): ?>
                                <tr>
                                    <th scope="row">Include Custom Post Types</th>
                                    <td class="softworld-checkbox-group">
                                        <?php foreach ($cpts as $cpt):
                                            $is_checked = in_array($cpt->name, (array) $settings['include_cpt']);
                                            ?>
                                            <label class="softworld-checkbox-label">
                                                <input type="checkbox" name="include_cpt[]" value="<?php echo esc_attr($cpt->name); ?>"
                                                    <?php checked($is_checked); ?>>
                                                <span class="checkmark"></span>
                                                <?php echo esc_html($cpt->labels->name); ?> (<?php echo esc_html($cpt->name); ?>)
                                            </label>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <div class="softworld-sitemap-section">
                        <h2>Exclusion Settings</h2>

                        <div class="softworld-exclusion-panel">
                            <h3>Exclude Posts</h3>
                            <div class="softworld-search-box">
                                <input type="text" id="softworld-post-search" placeholder="Search posts to exclude..."
                                    class="softworld-search-input">
                                <button type="button" id="softworld-search-post-btn"
                                    class="button button-secondary">Search</button>
                            </div>
                            <div id="softworld-post-results" class="softworld-search-results" style="display: none;"></div>

                            <div class="softworld-excluded-items">
                                <h4>Currently Excluded Posts</h4>
                                <div id="softworld-excluded-posts" class="softworld-excluded-list">
                                    <?php if (!empty($excluded_posts)): ?>
                                        <?php foreach ($excluded_posts as $post): ?>
                                            <div class="softworld-excluded-item" data-id="<?php echo $post->ID; ?>">
                                                <span class="softworld-item-title"><?php echo esc_html($post->post_title); ?></span>
                                                <span class="softworld-item-id">(ID: <?php echo $post->ID; ?>)</span>
                                                <button type="button" class="softworld-remove-item">Remove</button>
                                                <input type="hidden" name="excluded_posts[]" value="<?php echo $post->ID; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="softworld-no-items">No posts excluded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="softworld-exclusion-panel">
                            <h3>Exclude Pages</h3>
                            <div class="softworld-search-box">
                                <input type="text" id="softworld-page-search" placeholder="Search pages to exclude..."
                                    class="softworld-search-input">
                                <button type="button" id="softworld-search-page-btn"
                                    class="button button-secondary">Search</button>
                            </div>
                            <div id="softworld-page-results" class="softworld-search-results" style="display: none;"></div>

                            <div class="softworld-excluded-items">
                                <h4>Currently Excluded Pages</h4>
                                <div id="softworld-excluded-pages" class="softworld-excluded-list">
                                    <?php if (!empty($excluded_pages)): ?>
                                        <?php foreach ($excluded_pages as $page): ?>
                                            <div class="softworld-excluded-item" data-id="<?php echo $page->ID; ?>">
                                                <span class="softworld-item-title"><?php echo esc_html($page->post_title); ?></span>
                                                <span class="softworld-item-id">(ID: <?php echo $page->ID; ?>)</span>
                                                <button type="button" class="softworld-remove-item">Remove</button>
                                                <input type="hidden" name="excluded_pages[]" value="<?php echo $page->ID; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="softworld-no-items">No pages excluded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
                </form>
            </div>

            <div class="softworld-sitemap-status">
                <h2>Current Status</h2>
                <div class="softworld-status-cards">
                    <div class="softworld-status-card">
                        <h3>Content Types</h3>
                        <ul>
                            <li><?php echo $settings['include_posts'] ? '✅' : '❌'; ?> Posts included</li>
                            <li><?php echo $settings['include_pages'] ? '✅' : '❌'; ?> Pages included</li>
                            <li>Taxonomies: <?php echo count((array) $settings['include_taxonomies']); ?> included</li>
                            <li>Custom Post Types: <?php echo count((array) $settings['include_cpt']); ?> included</li>
                        </ul>
                    </div>
                    <div class="softworld-status-card">
                        <h3>Exclusions</h3>
                        <ul>
                            <li>Posts excluded: <?php echo count((array) $settings['excluded_posts']); ?></li>
                            <li>Pages excluded: <?php echo count((array) $settings['excluded_pages']); ?></li>
                        </ul>
                    </div>
                    <div class="softworld-status-card">
                        <h3>System</h3>
                        <ul>
                            <li>✅ Sitemap updates automatically</li>
                            <li>✅ robots.txt includes sitemap link</li>
                            <li>✅ Rewrite rules active</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="softworld-sitemap-actions">
                <form method="post" >
                    <?php submit_button('Regenerate Sitemap Rules', 'secondary', 'regenerate_rules'); ?>
                </form>
                <a style="margin-top: 7px;" href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank" class="button button-primary">View
                    Sitemap</a>
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
new Softworld_AI_SEO_Plugin();
?>