<?php
/**
 * Frontend output for SEO meta tags
 */

class Softworld_AI_SEO_Frontend_Output {
    private $variable_replacer;

    public function __construct($variable_replacer) {
        $this->variable_replacer = $variable_replacer;
        add_action('wp_head', array($this, 'output_seo_meta_tags'));
    }

    public function output_seo_meta_tags() {
        if (is_singular()) {
            $post_id = get_queried_object_id();
            // Output SEO title
            $seo_title = $this->variable_replacer->get_seo_title($post_id);
            if (!empty($seo_title)) {
                echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
            }
            
            // Output SEO description
            $seo_description = $this->variable_replacer->get_seo_description($post_id);
            if (!empty($seo_description)) {
                echo '<meta name="description" content="' . esc_attr($seo_description) . '">' . "\n";
            }
        }
    }
}