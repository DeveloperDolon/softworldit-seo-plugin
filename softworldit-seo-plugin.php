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

// main plugin class
class Softworld_AI_SEO_Plugin
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Add Sitemap Admin Page
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        // Initialize SEO plugin features if needed
    }

    public function enqueue_scripts()
    {
        // Enqueue frontend scripts if needed
    }

    public function admin_enqueue_scripts()
    {
        // Enqueue admin scripts if needed
    }

    public function activate()
    {
        // Register sitemap rewrite rules on activation
        SoftworldIT_SEO_Sitemap::add_rewrite();
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
            'softworld-seo',                    // Menu slug
            array($this, 'render_dashboard'),   // Callback
            'dashicons-chart-area',             // Icon
            80                                  // Position
        );

        // Add Sitemap submenu
        add_submenu_page(
            'softworld-seo',
            'XML Sitemap',
            'XML Sitemap',
            'manage_options',
            'softworldit-seo-sitemap',
            array($this, 'render_sitemap_admin')
        );
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
        ?>
        <div class="wrap">
            <h1>XML Sitemap</h1>
            <p>Your sitemap is automatically generated and updated whenever you publish or update content.</p>
            <p><strong>Sitemap Index:</strong> 
                <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank">
                    <?php echo esc_url(home_url('/sitemap.xml')); ?>
                </a>
            </p>

            <h2>Details</h2>
            <ul>
                <li>✔ Posts are included</li>
                <li>✔ Pages are included</li>
                <li>✔ Sitemap updates automatically</li>
                <li>✔ robots.txt includes sitemap link</li>
            </ul>

            <form method="post">
                <?php submit_button('Regenerate Sitemap Rules'); ?>
            </form>
        </div>
        <?php

        // Handle regenerate button
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            SoftworldIT_SEO_Sitemap::add_rewrite();
            flush_rewrite_rules();
            echo '<div class="updated notice"><p>Sitemap rules regenerated successfully.</p></div>';
        }
    }
}

// Initialize plugin
new Softworld_AI_SEO_Plugin();
?>