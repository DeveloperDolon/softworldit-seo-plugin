<?php
/**
 * Plugin Name: SOFTWORLD AI SEO Plugin
 * Plugin URI:  https://wordpress.org/plugins/softworld-ai-seo-plugin/
 * Description: Advance SEO Plugin with AI-powered content optimization using OpenAI
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

// main plugin class
class Softworld_AI_SEO_Plugin
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        echo "hello world form init functiod";
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
        // Activation logic here
    }

    public function deactivate()
    {
        // Deactivation logic here
    }
}

new Softworld_AI_SEO_Plugin();
?>