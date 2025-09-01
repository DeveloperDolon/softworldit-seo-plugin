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
    
    private function create_custom_roles() {
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
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['regenerate_rules'])) {
                SoftworldIT_SEO_Sitemap::add_rewrite();
                flush_rewrite_rules();
                echo '<div class="updated notice"><p>Sitemap rules regenerated successfully.</p></div>';
            } elseif (isset($_POST['save_settings'])) {
                // Save sitemap settings
                $settings = [
                    'include_posts' => isset($_POST['include_posts']) ? 1 : 0,
                    'include_pages' => isset($_POST['include_pages']) ? 1 : 0,
                    'excluded_posts' => isset($_POST['excluded_posts']) ? array_map('intval', $_POST['excluded_posts']) : [],
                    'excluded_pages' => isset($_POST['excluded_pages']) ? array_map('intval', $_POST['excluded_pages']) : [],
                ];
                update_option('softworld_sitemap_settings', $settings);
                echo '<div class="updated notice"><p>Sitemap settings saved successfully.</p></div>';
            }
        }

        // Get current settings
        $settings = get_option('softworld_sitemap_settings', [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
        ]);

        // Get all posts and pages for exclusion lists
        $all_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $all_pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
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

            <h2>Content Settings</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Include in Sitemap</th>
                        <td>
                            <label>
                                <input type="checkbox" name="include_posts" value="1" <?php checked($settings['include_posts'], 1); ?>>
                                Include Posts
                            </label><br>
                            <label>
                                <input type="checkbox" name="include_pages" value="1" <?php checked($settings['include_pages'], 1); ?>>
                                Include Pages
                            </label>
                        </td>
                    </tr>

                    <?php if (!empty($all_posts)): ?>
                        <div class="sitemap-exclusion-section">
                            <h3>Exclude Specific Posts</h3>
                            
                            <div class="exclusion-search-box">
                                <input type="text" id="posts-search" placeholder="Search posts..." class="regular-text">
                                <button type="button" class="button button-small" onclick="clearPostsSearch()">Clear</button>
                            </div>
                            
                            <div class="exclusion-list-container">
                                <?php if (!empty($all_posts)) : ?>
                                    <div class="exclusion-checkbox-list">
                                        <?php foreach ($all_posts as $post) : 
                                            $is_excluded = in_array($post->ID, $settings['excluded_posts']);
                                        ?>
                                            <label class="exclusion-checkbox-item">
                                                <input type="checkbox" 
                                                    name="excluded_posts[]" 
                                                    value="<?php echo $post->ID; ?>" 
                                                    <?php checked($is_excluded); ?>
                                                    class="post-checkbox"
                                                    data-title="<?php echo esc_attr($post->post_title); ?>">
                                                <span class="post-title"><?php echo esc_html($post->post_title); ?></span>
                                                <span class="post-id">(ID: <?php echo $post->ID; ?>)</span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="description">No posts found.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="exclusion-actions">
                                <button type="button" class="button button-small" onclick="selectAllPosts()">Select All</button>
                                <button type="button" class="button button-small" onclick="deselectAllPosts()">Deselect All</button>
                                <span class="selected-count">Selected: <span id="posts-selected-count">0</span></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($all_pages)): ?>
                        <div class="sitemap-exclusion-section">
                            <h3>Exclude Specific Pages</h3>
                            
                            <div class="exclusion-search-box">
                                <input type="text" id="pages-search" placeholder="Search pages..." class="regular-text">
                                <button type="button" class="button button-small" onclick="clearPagesSearch()">Clear</button>
                            </div>
                            
                            <div class="exclusion-list-container">
                                <?php if (!empty($all_pages)) : ?>
                                    <div class="exclusion-checkbox-list">
                                        <?php foreach ($all_pages as $page) : 
                                            $is_excluded = in_array($page->ID, $settings['excluded_pages']);
                                        ?>
                                            <label class="exclusion-checkbox-item">
                                                <input type="checkbox" 
                                                    name="excluded_pages[]" 
                                                    value="<?php echo $page->ID; ?>" 
                                                    <?php checked($is_excluded); ?>
                                                    class="page-checkbox"
                                                    data-title="<?php echo esc_attr($page->post_title); ?>">
                                                <span class="page-title"><?php echo esc_html($page->post_title); ?></span>
                                                <span class="page-id">(ID: <?php echo $page->ID; ?>)</span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="description">No pages found.</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="exclusion-actions">
                                <button type="button" class="button button-small" onclick="selectAllPages()">Select All</button>
                                <button type="button" class="button button-small" onclick="deselectAllPages()">Deselect All</button>
                                <span class="selected-count">Selected: <span id="pages-selected-count">0</span></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </table>

                <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
            </form>

            <h2>Current Status</h2>
            <ul>
                <li><?php echo $settings['include_posts'] ? '✔' : '✖'; ?> Posts are included</li>
                <li><?php echo $settings['include_pages'] ? '✔' : '✖'; ?> Pages are included</li>
                <li>✔ Sitemap updates automatically</li>
                <li>✔ robots.txt includes sitemap link</li>
                <li>Excluded posts: <?php echo count($settings['excluded_posts']); ?></li>
                <li>Excluded pages: <?php echo count($settings['excluded_pages']); ?></li>
            </ul>

            <form method="post">
                <?php submit_button('Regenerate Sitemap Rules', 'secondary', 'regenerate_rules'); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize plugin
new Softworld_AI_SEO_Plugin();
?>