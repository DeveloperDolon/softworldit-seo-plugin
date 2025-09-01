<?php wp_nonce_field('softworld_ai_seo_meta_box', 'softworld_ai_seo_nonce'); ?>

<div class="softworld-ai-seo-fields">
    <p>
        <label for="softworld_ai_seo_title">
            <strong><?php _e('SEO Title', 'softworld-ai-seo'); ?></strong>
        </label>
        <input type="text" id="softworld_ai_seo_title" name="softworld_ai_seo_title" 
               value="<?php echo esc_attr($seo_title); ?>" class="widefat">
        <span class="description">
            <?php _e('This will be used as the meta title for search engines.', 'softworld-ai-seo'); ?>
        </span>
    </p>
    
    <p>
        <label for="softworld_ai_seo_description">
            <strong><?php _e('SEO Description', 'softworld-ai-seo'); ?></strong>
        </label>
        <textarea id="softworld_ai_seo_description" name="softworld_ai_seo_description" 
                  rows="3" class="widefat"><?php echo esc_textarea($seo_description); ?></textarea>
        <span class="description">
            <?php _e('This will be used as the meta description for search engines.', 'softworld-ai-seo'); ?>
        </span>
    </p>
</div>