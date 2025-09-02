<?php
/**
 * Variable replacement engine for custom fields and WC attributes
 */

class Softworld_AI_SEO_Variable_Replacement {

    public function replace_variables($content, $post_id) {
        $content = $this->replace_custom_fields($content, $post_id);
        $content = $this->replace_wc_attributes($content, $post_id);
        return $content;
    }

    private function replace_custom_fields($content, $post_id) {
        preg_match_all('/%cf_([^%]+)%/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $field_name) {
                $field_value = get_post_meta($post_id, $field_name, true);
                $content = str_replace("%cf_{$field_name}%", $field_value, $content);
            }
        }
        
        return $content;
    }

    private function replace_wc_attributes($content, $post_id) {
        if (!class_exists('WooCommerce') || 'product' !== get_post_type($post_id)) {
            return $content;
        }

        preg_match_all('/%attr_([^%]+)%/', $content, $matches);
        
        if (!empty($matches[1])) {
            $product = wc_get_product($post_id);
            
            foreach ($matches[1] as $attribute_name) {
                $attribute_value = $product->get_attribute($attribute_name);
                $content = str_replace("%attr_{$attribute_name}%", $attribute_value, $content);
            }
        }
        
        return $content;
    }
}