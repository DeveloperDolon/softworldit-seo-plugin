<?php
/**
 * Advanced Meta Boxes for Custom Fields and WooCommerce Attributes
 */

class Softworld_AI_SEO_Advanced_Meta_Boxes {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_advanced_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_advanced_meta_boxes() {
        $post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'softworld-ai-seo-advanced',
                __('Advanced SEO Settings', 'softworld-ai-seo'),
                array($this, 'render_advanced_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_advanced_meta_box($post) {
        wp_nonce_field('softworld_ai_seo_advanced_meta_box', 'softworld_ai_seo_advanced_nonce');
        
        // Get existing values
        $custom_fields_pattern = get_post_meta($post->ID, '_softworld_ai_seo_custom_fields_pattern', true);
        $wc_attributes_pattern = get_post_meta($post->ID, '_softworld_ai_seo_wc_attributes_pattern', true);
        
        echo '<div class="softworld-advanced-seo-settings">';
        
        // Custom Fields Section
        echo '<h3>' . __('Custom Fields Integration', 'softworld-ai-seo') . '</h3>';
        echo '<p>' . __('Use %cf_fieldname% to include custom field values in your SEO title/description', 'softworld-ai-seo') . '</p>';
        echo '<label for="softworld_custom_fields_pattern">' . __('Custom Fields Pattern:', 'softworld-ai-seo') . '</label>';
        echo '<input type="text" id="softworld_custom_fields_pattern" name="softworld_custom_fields_pattern" value="' . esc_attr($custom_fields_pattern) . '" style="width:100%" placeholder="e.g., %cf_color% - %cf_size%" />';
        
        // WooCommerce Attributes Section (only show if WooCommerce is active)
        if (class_exists('WooCommerce')) {
            echo '<h3 style="margin-top:20px">' . __('WooCommerce Attributes', 'softworld-ai-seo') . '</h3>';
            echo '<p>' . __('Use %attr_attributename% to include product attributes in your SEO title/description', 'softworld-ai-seo') . '</p>';
            echo '<label for="softworld_wc_attributes_pattern">' . __('WC Attributes Pattern:', 'softworld-ai-seo') . '</label>';
            echo '<input type="text" id="softworld_wc_attributes_pattern" name="softworld_wc_attributes_pattern" value="' . esc_attr($wc_attributes_pattern) . '" style="width:100%" placeholder="e.g., %attr_color% - %attr_size%" />';
        }
        
        echo '</div>';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script(
                'softworld-ai-seo-advanced',
                AI_SEO_PLUGIN_URL . 'assets/js/advanced-meta-box.js',
                array('jquery'),
                AI_SEO_VERSION,
                true
            );
        }
    }
}