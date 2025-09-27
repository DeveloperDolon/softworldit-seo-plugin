<?php
/**
 * Enhanced Meta Boxes with AI Integration
 * 
 * This file replaces the existing another_services/meta-boxes.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_Enhanced_Meta_Boxes
{
    private $ai_generator;

    public function __construct()
    {
        // Initialize AI generator
        $this->ai_generator = new Softworld_AI_SEO_Generator();
        
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX handlers for loading posts in bulk generation
        add_action('wp_ajax_softworld_load_bulk_posts', [$this, 'ajax_load_bulk_posts']);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes()
    {
        $post_types = ['post', 'page'];
        
        // Add custom post types
        $custom_post_types = get_post_types(['public' => true, '_builtin' => false]);
        $post_types = array_merge($post_types, $custom_post_types);

        foreach ($post_types as $post_type) {
            add_meta_box(
                'softworld_ai_seo_meta_box',
                __('Softworld AI SEO', 'softworld-ai-seo'),
                [$this, 'render_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the enhanced meta box with AI features
     */
    public function render_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('softworld_ai_seo_meta_box', 'softworld_ai_seo_nonce');

        // Get existing values
        $seo_title = get_post_meta($post->ID, '_softworld_ai_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);
        $focus_keyword = get_post_meta($post->ID, '_softworld_ai_seo_focus_keyword', true);
        $meta_keywords = get_post_meta($post->ID, '_softworld_ai_seo_meta_keywords', true);

        // Check if AI is available
        $ai_available = $this->ai_generator->is_ai_available();
        $default_tone = get_option('softworld_ai_seo_default_tone', 'professional');

        // faqs 
        $faqs_enabled = get_option('softworld_ai_seo_enable_faqs', 1);
        $points_enabled = get_option('softworld_ai_seo_enable_key_points', 1);
        $meta_faqs = get_post_meta($post->ID, '_softworld_ai_seo_faqs', true);
        $meta_key_points = get_post_meta($post->ID, '_softworld_ai_seo_key_points', true);

        ?>
        <div class="softworld-meta-box-container">
            <table class="form-table" style="margin: 0;">
                <tbody>
                    <?php if (!$ai_available): ?>
                    <tr>
                        <th scope="row"><?php _e('Warning', 'softworld-ai-seo'); ?></th>
                        <td>
                            <div style="background-color:#fff3cd; border:1px solid #ffeeba; color:#856404; padding:12px; border-radius:6px; margin:10px 0;">
                                <p style="margin:0;">
                                    <?php _e('AI features are not available. Please configure your OpenAI API key in', 'softworld-ai-seo'); ?> 
                                    <a href="<?php echo admin_url('admin.php?page=softworldit-seo-settings'); ?>" style="color:#856404; font-weight:bold; text-decoration:underline;">
                                        <?php _e('AI Settings', 'softworld-ai-seo'); ?>
                                    </a>.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <!-- SEO Title Row -->
                    <tr>
                        <th scope="row">
                            <label for="softworld_ai_seo_title"><?php _e('SEO Title', 'softworld-ai-seo'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                id="softworld_ai_seo_title" 
                                name="softworld_ai_seo_title" 
                                value="<?php echo esc_attr($seo_title); ?>" 
                                class="regular-text"
                                style="width: 100%; margin-bottom: 5px;"
                                maxlength="60"
                                placeholder="<?php _e('Enter SEO title...', 'softworld-ai-seo'); ?>">
                            
                            <div class="char-counter" id="title-counter" style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                <span class="current"><?php echo strlen($seo_title); ?></span> / 60 characters
                            </div>
                        
                            <?php if ($ai_available): ?>
                            <div class="ai-controls" style="margin-bottom: 10px;">
                                <button type="button" 
                                        class="button button-secondary ai-generate-btn" 
                                        data-type="title"
                                        style="margin-right: 10px;">
                                    <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>
                                    <?php _e('Generate SEO Title', 'softworld-ai-seo'); ?>
                                </button>
                                
                                <div class="ai-options" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                                    <div style="margin-bottom: 8px;">
                                        <label style="display: inline-block; width: 80px; font-size: 12px;"><?php _e('Keywords:', 'softworld-ai-seo'); ?></label>
                                        <input type="text" class="ai-keywords" placeholder="<?php _e('WordPress, SEO, etc.', 'softworld-ai-seo'); ?>" style="width: 200px; margin-right: 10px;">
                                        
                                        <label style="display: inline-block; width: 40px; font-size: 12px;"><?php _e('Tone:', 'softworld-ai-seo'); ?></label>
                                        <select class="ai-tone" style="width: 120px;">
                                            <option value="professional" <?php selected($default_tone, 'professional'); ?>><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                            <option value="friendly" <?php selected($default_tone, 'friendly'); ?>><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                            <option value="authoritative" <?php selected($default_tone, 'authoritative'); ?>><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                            <option value="casual" <?php selected($default_tone, 'casual'); ?>><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                            <option value="conversational" <?php selected($default_tone, 'conversational'); ?>><?php _e('Conversational', 'softworld-ai-seo'); ?></option>
                                            <option value="engaging" <?php selected($default_tone, 'engaging'); ?>><?php _e('Engaging', 'softworld-ai-seo'); ?></option>
                                        </select>
                                    </div>
                                    <button type="button" class="button button-primary ai-generate-now" data-type="title">
                                        <?php _e('Generate Now', 'softworld-ai-seo'); ?>
                                    </button>
                                    <button type="button" class="button ai-cancel" style="margin-left: 5px;">
                                        <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                    </button>
                                </div>
                                
                                <div class="softworld-ai-status"></div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- SEO Description Row -->
                    <tr>
                        <th scope="row">
                            <label for="softworld_ai_seo_description"><?php _e('SEO Meta Description', 'softworld-ai-seo'); ?></label>
                        </th>
                        <td>
                            <textarea id="softworld_ai_seo_description" 
                                    name="softworld_ai_seo_description" 
                                    rows="3" 
                                    style="width: 100%; margin-bottom: 5px;"
                                    maxlength="160"
                                    placeholder="<?php _e('Enter meta description...', 'softworld-ai-seo'); ?>"><?php echo esc_textarea($seo_description); ?></textarea>
                            
                            <div class="char-counter" id="description-counter" style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                <span class="current"><?php echo strlen($seo_description); ?></span> / 160 characters
                            </div>

                            <?php if ($ai_available): ?>
                            <div class="ai-controls" style="margin-bottom: 10px;">
                                <button type="button" 
                                        class="button button-secondary ai-generate-btn" 
                                        data-type="description"
                                        style="margin-right: 10px;">
                                    <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>
                                    <?php _e('Generate Meta Description', 'softworld-ai-seo'); ?>
                                </button>
                                
                                <div class="ai-options" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                                    <div style="margin-bottom: 8px;">
                                        <label style="display: inline-block; width: 80px; font-size: 12px;"><?php _e('Keywords:', 'softworld-ai-seo'); ?></label>
                                        <input type="text" class="ai-keywords" placeholder="<?php _e('WordPress, SEO, etc.', 'softworld-ai-seo'); ?>" style="width: 200px; margin-right: 10px;">
                                        
                                        <label style="display: inline-block; width: 40px; font-size: 12px;"><?php _e('Tone:', 'softworld-ai-seo'); ?></label>
                                        <select class="ai-tone" style="width: 120px;">
                                            <option value="professional" <?php selected($default_tone, 'professional'); ?>><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                            <option value="friendly" <?php selected($default_tone, 'friendly'); ?>><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                            <option value="authoritative" <?php selected($default_tone, 'authoritative'); ?>><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                            <option value="casual" <?php selected($default_tone, 'casual'); ?>><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                            <option value="conversational" <?php selected($default_tone, 'conversational'); ?>><?php _e('Conversational', 'softworld-ai-seo'); ?></option>
                                            <option value="engaging" <?php selected($default_tone, 'engaging'); ?>><?php _e('Engaging', 'softworld-ai-seo'); ?></option>
                                        </select>
                                    </div>
                                    <button type="button" class="button button-primary ai-generate-now" data-type="description">
                                        <?php _e('Generate Now', 'softworld-ai-seo'); ?>
                                    </button>
                                    <button type="button" class="button ai-cancel" style="margin-left: 5px;">
                                        <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                    </button>
                                </div>
                                
                                <div class="softworld-ai-status"></div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Additional SEO Fields -->
                    <tr>
                        <th scope="row">
                            <label for="softworld_ai_seo_focus_keyword"><?php _e('Focus Keyword', 'softworld-ai-seo'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                id="softworld_ai_seo_focus_keyword" 
                                name="softworld_ai_seo_focus_keyword" 
                                value="<?php echo esc_attr($focus_keyword); ?>" 
                                class="regular-text" 
                                style="width: 100%;"
                                placeholder="<?php _e('Enter focus keyword...', 'softworld-ai-seo'); ?>" />
                            <p class="description"><?php _e('Primary keyword for this content', 'softworld-ai-seo'); ?></p>
                        </td>
                    </tr>

                    <?php if ($faqs_enabled || $points_enabled): ?>
                        <!-- FAQ Section -->
                        <?php if ($faqs_enabled): ?>
                        <tr>
                            <th scope="row">
                                <label for="softworld_ai_seo_faqs"><?php _e('FAQs', 'softworld-ai-seo'); ?></label>
                            </th>
                            <td>
                                <div class="softworld-faqs-container">
                                    <textarea id="softworld_ai_seo_faqs" 
                                            name="softworld_ai_seo_faqs" 
                                            rows="8" 
                                            style="width: 100%; margin-bottom: 8px;"
                                            placeholder="<?php _e('Generated FAQs will appear here...', 'softworld-ai-seo'); ?>"><?php echo esc_textarea($meta_faqs); ?></textarea>
                                    
                                    <?php if ($ai_available): ?>
                                    <div class="ai-controls" style="margin-bottom: 10px;">
                                        <button type="button" 
                                                class="button button-secondary ai-generate-btn" 
                                                data-type="faqs"
                                                style="margin-right: 10px;">
                                            <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>
                                            <?php _e('Generate FAQs', 'softworld-ai-seo'); ?>
                                        </button>
                                        
                                        <div class="ai-options" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                                            <div style="margin-bottom: 8px;">
                                                <label style="display: inline-block; width: 80px; font-size: 12px;"><?php _e('Count:', 'softworld-ai-seo'); ?></label>
                                                <input type="number" class="ai-faq-count" value="<?php echo get_option('softworld_ai_seo_default_faq_count', 5); ?>" min="3" max="10" style="width: 60px; margin-right: 15px;">
                                                
                                                <label style="display: inline-block; width: 40px; font-size: 12px;"><?php _e('Tone:', 'softworld-ai-seo'); ?></label>
                                                <select class="ai-tone" style="width: 120px;">
                                                    <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                                    <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                                    <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                                    <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                                </select>
                                            </div>
                                            <button type="button" class="button button-primary ai-generate-now" data-type="faqs">
                                                <?php _e('Generate Now', 'softworld-ai-seo'); ?>
                                            </button>
                                            <button type="button" class="button ai-cancel" style="margin-left: 5px;">
                                                <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div id="faqs-preview" class="softworld-content-preview" style="display: none; margin-top: 10px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">
                                        <h4><?php _e('FAQ Preview:', 'softworld-ai-seo'); ?></h4>
                                        <div class="faqs-content"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <!-- Key Points Section -->
                        <?php if ($points_enabled): ?>
                        <tr>
                            <th scope="row">
                                <label for="softworld_ai_seo_key_points"><?php _e('Key Points', 'softworld-ai-seo'); ?></label>
                            </th>
                            <td>
                                <div class="softworld-key-points-container">
                                    <textarea id="softworld_ai_seo_key_points" 
                                            name="softworld_ai_seo_key_points" 
                                            rows="6" 
                                            style="width: 100%; margin-bottom: 8px;"
                                            placeholder="<?php _e('Generated key points will appear here...', 'softworld-ai-seo'); ?>"><?php echo esc_textarea($meta_key_points); ?></textarea>
                                    
                                    <?php if ($ai_available): ?>
                                    <div class="ai-controls" style="margin-bottom: 10px;">
                                        <button type="button" 
                                                class="button button-secondary ai-generate-btn" 
                                                data-type="key_points"
                                                style="margin-right: 10px;">
                                            <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>
                                            <?php _e('Generate Key Points', 'softworld-ai-seo'); ?>
                                        </button>
                                        
                                        <div class="ai-options" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                                            <div style="margin-bottom: 8px;">
                                                <label style="display: inline-block; width: 80px; font-size: 12px;"><?php _e('Count:', 'softworld-ai-seo'); ?></label>
                                                <input type="number" class="ai-point-count" value="<?php echo get_option('softworld_ai_seo_default_point_count', 5); ?>" min="3" max="10" style="width: 60px; margin-right: 15px;">
                                                
                                                <label style="display: inline-block; width: 40px; font-size: 12px;"><?php _e('Tone:', 'softworld-ai-seo'); ?></label>
                                                <select class="ai-tone" style="width: 120px;">
                                                    <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                                    <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                                    <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                                    <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                                </select>
                                            </div>
                                            <button type="button" class="button button-primary ai-generate-now" data-type="key_points">
                                                <?php _e('Generate Now', 'softworld-ai-seo'); ?>
                                            </button>
                                            <button type="button" class="button ai-cancel" style="margin-left: 5px;">
                                                <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div id="points-preview" class="softworld-content-preview" style="display: none; margin-top: 10px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">
                                        <h4><?php _e('Key Points Preview:', 'softworld-ai-seo'); ?></h4>
                                        <div class="points-content"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <!-- Generate Both Section -->
                        <?php if ($ai_available): ?>
                        <tr>
                            <th scope="row"><?php _e('Quick Actions', 'softworld-ai-seo'); ?></th>
                            <td>
                                <button type="button" class="button button-primary" id="generate-both-faqs-points" style="margin-right: 10px;">
                                    <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>
                                    <?php _e('Generate Both FAQs & Key Points', 'softworld-ai-seo'); ?>
                                </button>
                                <p class="description"><?php _e('Generate both FAQs and key points at once using default settings.', 'softworld-ai-seo'); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endif; ?>

                    // Add this JavaScript section to handle the new functionality
                    // Add this JavaScript section to handle the new functionality
                    <?php if ($faqs_enabled || $points_enabled): ?>
                        <script>
                        // Additional FAQ and Key Points functionality
                        $('.ai-generate-now[data-type="faqs"]').click(function() {
                            const button = $(this);
                            const container = button.closest('.ai-controls');
                            const faqCount = container.find('.ai-faq-count').val();
                            const tone = container.find('.ai-tone').val();
                            
                            let content = '<?php echo esc_js($post->post_title); ?>';
                            
                            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                                content += '\\n\\n' + tinymce.get('content').getContent({format: 'text'});
                            } else if ($('#content').length) {
                                content += '\\n\\n' + $('#content').val();
                            } else {
                                content += '\\n\\n<?php echo esc_js(wp_strip_all_tags($post->post_content)); ?>';
                            }

                            if (!content.trim()) {
                                showMessage('<?php _e("Please add some content to your post first.", "softworld-ai-seo"); ?>', 'error');
                                return;
                            }

                            button.prop('disabled', true).text('<?php _e("Generating...", "softworld-ai-seo"); ?>');

                            $.post(ajaxurl, {
                                action: 'softworld_generate_faqs',
                                nonce: '<?php echo wp_create_nonce("softworld_ai_seo_nonce"); ?>',
                                content: content,
                                post_type: '<?php echo esc_js($post->post_type); ?>',
                                faq_count: faqCount,
                                tone: tone
                            })
                            .done(function(response) {
                                if (response.success) {
                                    const faqs = response.data.faqs;
                                    let faqText = '';
                                    faqs.forEach(function(faq, index) {
                                        faqText += 'Q' + (index + 1) + ': ' + faq.question + '\\n';
                                        faqText += 'A' + (index + 1) + ': ' + faq.answer + '\\n\\n';
                                    });
                                    $('#softworld_ai_seo_faqs').val(faqText);
                                    container.find('.ai-options').slideUp();
                                    showFAQPreview(faqs);
                                    showMessage('<?php _e("FAQs generated successfully!", "softworld-ai-seo"); ?>', 'success');
                                } else {
                                    showMessage(response.data || '<?php _e("FAQ generation failed", "softworld-ai-seo"); ?>', 'error');
                                }
                            })
                            .fail(function() {
                                showMessage('<?php _e("Network error occurred", "softworld-ai-seo"); ?>', 'error');
                            })
                            .always(function() {
                                button.prop('disabled', false).text('<?php _e("Generate Now", "softworld-ai-seo"); ?>');
                            });
                        });

                        $('.ai-generate-now[data-type="key_points"]').click(function() {
                            const button = $(this);
                            const container = button.closest('.ai-controls');
                            const pointCount = container.find('.ai-point-count').val();
                            const tone = container.find('.ai-tone').val();
                            
                            let content = '<?php echo esc_js($post->post_title); ?>';
                            
                            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                                content += '\\n\\n' + tinymce.get('content').getContent({format: 'text'});
                            } else if ($('#content').length) {
                                content += '\\n\\n' + $('#content').val();
                            } else {
                                content += '\\n\\n<?php echo esc_js(wp_strip_all_tags($post->post_content)); ?>';
                            }

                            if (!content.trim()) {
                                showMessage('<?php _e("Please add some content to your post first.", "softworld-ai-seo"); ?>', 'error');
                                return;
                            }

                            button.prop('disabled', true).text('<?php _e("Generating...", "softworld-ai-seo"); ?>');

                            $.post(ajaxurl, {
                                action: 'softworld_generate_key_points',
                                nonce: '<?php echo wp_create_nonce("softworld_ai_seo_nonce"); ?>',
                                content: content,
                                post_type: '<?php echo esc_js($post->post_type); ?>',
                                point_count: pointCount,
                                tone: tone
                            })
                            .done(function(response) {
                                if (response.success) {
                                    const points = response.data.key_points;
                                    let pointText = '';
                                    points.forEach(function(point, index) {
                                        pointText += (index + 1) + '. ' + point + '\\n';
                                    });
                                    $('#softworld_ai_seo_key_points').val(pointText);
                                    container.find('.ai-options').slideUp();
                                    showPointsPreview(points);
                                    showMessage('<?php _e("Key points generated successfully!", "softworld-ai-seo"); ?>', 'success');
                                } else {
                                    showMessage(response.data || '<?php _e("Key points generation failed", "softworld-ai-seo"); ?>', 'error');
                                }
                            })
                            .fail(function() {
                                showMessage('<?php _e("Network error occurred", "softworld-ai-seo"); ?>', 'error');
                            })
                            .always(function() {
                                button.prop('disabled', false).text('<?php _e("Generate Now", "softworld-ai-seo"); ?>');
                            });
                        });

                        // Generate both FAQs and Key Points
                        $('#generate-both-faqs-points').click(function() {
                            const button = $(this);
                            
                            let content = '<?php echo esc_js($post->post_title); ?>';
                            
                            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                                content += '\\n\\n' + tinymce.get('content').getContent({format: 'text'});
                            } else if ($('#content').length) {
                                content += '\\n\\n' + $('#content').val();
                            } else {
                                content += '\\n\\n<?php echo esc_js(wp_strip_all_tags($post->post_content)); ?>';
                            }

                            if (!content.trim()) {
                                showMessage('<?php _e("Please add some content to your post first.", "softworld-ai-seo"); ?>', 'error');
                                return;
                            }

                            button.prop('disabled', true).text('<?php _e("Generating both...", "softworld-ai-seo"); ?>');

                            $.post(ajaxurl, {
                                action: 'softworld_generate_both_faqs_points',
                                nonce: '<?php echo wp_create_nonce("softworld_ai_seo_nonce"); ?>',
                                content: content,
                                post_type: '<?php echo esc_js($post->post_type); ?>',
                                faq_count: '<?php echo get_option("softworld_ai_seo_default_faq_count", 5); ?>',
                                point_count: '<?php echo get_option("softworld_ai_seo_default_point_count", 5); ?>',
                                tone: '<?php echo get_option("softworld_ai_seo_default_tone", "professional"); ?>'
                            })
                            .done(function(response) {
                                if (response.success) {
                                    // Handle FAQs
                                    if (response.data.faqs) {
                                        const faqs = response.data.faqs;
                                        let faqText = '';
                                        faqs.forEach(function(faq, index) {
                                            faqText += 'Q' + (index + 1) + ': ' + faq.question + '\\n';
                                            faqText += 'A' + (index + 1) + ': ' + faq.answer + '\\n\\n';
                                        });
                                        $('#softworld_ai_seo_faqs').val(faqText);
                                        showFAQPreview(faqs);
                                    }
                                    
                                    // Handle Key Points
                                    if (response.data.key_points) {
                                        const points = response.data.key_points;
                                        let pointText = '';
                                        points.forEach(function(point, index) {
                                            pointText += (index + 1) + '. ' + point + '\\n';
                                        });
                                        $('#softworld_ai_seo_key_points').val(pointText);
                                        showPointsPreview(points);
                                    }
                                    
                                    showMessage('<?php _e("Both FAQs and key points generated successfully!", "softworld-ai-seo"); ?>', 'success');
                                } else {
                                    showMessage(response.data || '<?php _e("Generation failed", "softworld-ai-seo"); ?>', 'error');
                                }
                            })
                            .fail(function() {
                                showMessage('<?php _e("Network error occurred", "softworld-ai-seo"); ?>', 'error');
                            })
                            .always(function() {
                                button.prop('disabled', false).text('<?php _e("Generate Both FAQs & Key Points", "softworld-ai-seo"); ?>');
                            });
                        });

                        // Preview functions
                        function showFAQPreview(faqs) {
                            const preview = $('#faqs-preview');
                            const content = preview.find('.faqs-content');
                            
                            let html = '<div class="faq-list">';
                            faqs.forEach(function(faq, index) {
                                html += '<div class="faq-item" style="margin-bottom: 15px; padding: 10px; border-left: 3px solid #0073aa;">';
                                html += '<strong>Q' + (index + 1) + ':</strong> ' + faq.question + '<br>';
                                html += '<strong>A' + (index + 1) + ':</strong> ' + faq.answer;
                                html += '</div>';
                            });
                            html += '</div>';
                            
                            content.html(html);
                            preview.show();
                        }

                        function showPointsPreview(points) {
                            const preview = $('#points-preview');
                            const content = preview.find('.points-content');
                            
                            let html = '<ol class="key-points-list">';
                            points.forEach(function(point) {
                                html += '<li style="margin-bottom: 8px; padding: 5px;">' + point + '</li>';
                            });
                            html += '</ol>';
                            
                            content.html(html);
                            preview.show();
                        }
                        </script>
                    <?php endif; ?>

                    <tr>
                        <th scope="row">
                            <label for="softworld_ai_seo_meta_keywords"><?php _e('Meta Keywords', 'softworld-ai-seo'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                id="softworld_ai_seo_meta_keywords" 
                                name="softworld_ai_seo_meta_keywords" 
                                value="<?php echo esc_attr($meta_keywords); ?>" 
                                class="regular-text" 
                                style="width: 100%;"
                                placeholder="<?php _e('keyword1, keyword2, keyword3...', 'softworld-ai-seo'); ?>" />
                            <p class="description"><?php _e('Comma-separated keywords (optional)', 'softworld-ai-seo'); ?></p>
                        </td>
                    </tr>

                    <!-- Preview Row -->
                    <tr>
                        <th scope="row"><?php _e('Search Preview', 'softworld-ai-seo'); ?></th>
                        <td>
                            <div id="seo-preview" style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 3px; font-family: Arial, sans-serif;">
                                <div class="preview-url" style="color: #006621; font-size: 14px; margin-bottom: 2px;">
                                    <?php echo esc_url(get_permalink($post->ID) ?: home_url('/')); ?>
                                </div>
                                <div class="preview-title" style="color: #1a73e8; font-size: 18px; margin-bottom: 2px; text-decoration: underline; cursor: pointer;">
                                    <?php echo esc_html($seo_title ?: $post->post_title ?: 'Your SEO Title Here'); ?>
                                </div>
                                <div class="preview-description" style="color: #545454; font-size: 13px; line-height: 1.4;">
                                    <?php echo esc_html($seo_description ?: 'Your meta description will appear here...'); ?>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <?php if ($ai_available): ?>
                    <!-- Quick AI Actions -->
                    <tr>
                        <th scope="row"><?php _e('Quick Actions', 'softworld-ai-seo'); ?></th>
                        <td>
                            <div class="softworld-quick-actions">
                                <button type="button" class="button button-secondary softworld-generate-both">
                                    <span class="dashicons dashicons-admin-generic" style="font-size: 14px; vertical-align: middle;"></span>
                                    <?php _e('Generate Both Title & Description', 'softworld-ai-seo'); ?>
                                </button>
                                
                                <div class="softworld-ai-bulk-settings" style="display: none; margin-top: 10px; padding: 10px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
                                    <div style="margin-bottom: 10px;">
                                        <label style="display: inline-block; width: 80px; font-size: 12px;"><?php _e('Keywords:', 'softworld-ai-seo'); ?></label>
                                        <input type="text" class="softworld-bulk-keywords" placeholder="<?php _e('Optional keywords...', 'softworld-ai-seo'); ?>" style="width: 200px; margin-right: 10px;">
                                        
                                        <label style="display: inline-block; width: 40px; font-size: 12px;"><?php _e('Tone:', 'softworld-ai-seo'); ?></label>
                                        <select class="softworld-bulk-tone" style="width: 120px;">
                                            <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                            <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                            <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                            <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                            <option value="conversational"><?php _e('Conversational', 'softworld-ai-seo'); ?></option>
                                            <option value="engaging"><?php _e('Engaging', 'softworld-ai-seo'); ?></option>
                                        </select>
                                    </div>
                                    <button type="button" class="button-primary softworld-generate-both-now">
                                        <?php _e('Generate Both', 'softworld-ai-seo'); ?>
                                    </button>
                                    <button type="button" class="button softworld-cancel-bulk" style="margin-left: 5px;">
                                        <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="ai-message" style="margin: 10px 0; display: none;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Prevent multiple initializations
            if (window.softworldSEOInitialized) {
                return;
            }
            window.softworldSEOInitialized = true;

            // Character counters
            function updateCounter(input, counter) {
                const length = input.val().length;
                const limit = input.attr('maxlength') || (input.attr('id').includes('title') ? 60 : 160);
                const currentSpan = counter.find('.current');
                
                currentSpan.text(length);
                
                // Color coding
                if (length > limit) {
                    counter.css('color', '#dc3232');
                } else if ((input.attr('id').includes('title') && length >= 50 && length <= 60) || 
                        (input.attr('id').includes('description') && length >= 150 && length <= 160)) {
                    counter.css('color', '#46b450');
                } else {
                    counter.css('color', '#666');
                }
            }

            // Update preview
            function updatePreview() {
                const title = $('#softworld_ai_seo_title').val() || '<?php echo esc_js($post->post_title ?: "Your SEO Title Here"); ?>';
                const description = $('#softworld_ai_seo_description').val() || 'Your meta description will appear here...';
                
                $('#seo-preview .preview-title').text(title);
                $('#seo-preview .preview-description').text(description);
            }

            // Bind events
            $('#softworld_ai_seo_title').on('input', function() {
                updateCounter($(this), $('#title-counter'));
                updatePreview();
            });

            $('#softworld_ai_seo_description').on('input', function() {
                updateCounter($(this), $('#description-counter'));
                updatePreview();
            });

            // Initialize counters
            updateCounter($('#softworld_ai_seo_title'), $('#title-counter'));
            updateCounter($('#softworld_ai_seo_description'), $('#description-counter'));

            <?php if ($ai_available): ?>
            // AI Generation
            $('.ai-generate-btn').click(function() {
                const button = $(this);
                const options = button.siblings('.ai-options');
                
                // Hide all other options first
                $('.ai-options, .softworld-ai-bulk-settings').not(options).slideUp();
                
                // Toggle current options
                options.slideToggle();
            });

            $('.softworld-generate-both').click(function() {
                const settings = $('.softworld-ai-bulk-settings');
                $('.ai-options').slideUp();
                settings.slideToggle();
            });

            $('.ai-cancel, .softworld-cancel-bulk').click(function() {
                $(this).closest('.ai-options, .softworld-ai-bulk-settings').slideUp();
            });

            $('.ai-generate-now').click(function() {
                const button = $(this);
                const type = button.data('type');
                const container = button.closest('.ai-controls');
                const keywords = container.find('.ai-keywords').val();
                const tone = container.find('.ai-tone').val();
                
                generateAI(type, keywords, tone, button, container);
            });

            $('.softworld-generate-both-now').click(function() {
                const button = $(this);
                const container = button.closest('.softworld-quick-actions');
                const keywords = container.find('.softworld-bulk-keywords').val();
                const tone = container.find('.softworld-bulk-tone').val();
                
                generateAI('both', keywords, tone, button, container);
            });

            function generateAI(type, keywords, tone, button, container) {
                // Get content
                let content = '<?php echo esc_js($post->post_title); ?>';
                
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    content += '\\n\\n' + tinymce.get('content').getContent({format: 'text'});
                } else if ($('#content').length) {
                    content += '\\n\\n' + $('#content').val();
                } else {
                    content += '\\n\\n<?php echo esc_js(wp_strip_all_tags($post->post_content)); ?>';
                }

                if (!content.trim()) {
                    showMessage('<?php _e("Please add some content to your post first.", "softworld-ai-seo"); ?>', 'error');
                    return;
                }

                // Show loading
                const originalText = button.text();
                button.prop('disabled', true).text('<?php _e("Generating...", "softworld-ai-seo"); ?>');

                const ajaxAction = type === 'both' ? 'softworld_generate_seo_both' : 'softworld_generate_seo_' + type;

                $.post(ajaxurl, {
                    action: ajaxAction,
                    nonce: '<?php echo wp_create_nonce("softworld_ai_seo_nonce"); ?>',
                    content: content,
                    post_type: '<?php echo esc_js($post->post_type); ?>',
                    target_keywords: keywords,
                    tone: tone
                })
                .done(function(response) {
                    if (response.success) {
                        if (type === 'both') {
                            if (response.data.title) {
                                $('#softworld_ai_seo_title').val(response.data.title).trigger('input');
                            }
                            if (response.data.description) {
                                $('#softworld_ai_seo_description').val(response.data.description).trigger('input');
                            }
                            showMessage('<?php _e("Generated successfully!", "softworld-ai-seo"); ?>', 'success');
                        } else {
                            const result = response.data[type] || response.data.title || response.data.description;
                            $('#softworld_ai_seo_' + type).val(result).trigger('input');
                            showMessage('<?php _e("Generated successfully!", "softworld-ai-seo"); ?>', 'success');
                        }
                        container.find('.ai-options, .softworld-ai-bulk-settings').slideUp();
                    } else {
                        showMessage(response.data || '<?php _e("Generation failed", "softworld-ai-seo"); ?>', 'error');
                    }
                })
                .fail(function() {
                    showMessage('<?php _e("Network error occurred", "softworld-ai-seo"); ?>', 'error');
                })
                .always(function() {
                    button.prop('disabled', false).text(originalText);
                });
            }

            function showMessage(message, type) {
                const messageDiv = $('#ai-message');
                const className = type === 'error' ? 'notice-error' : 'notice-success';
                messageDiv.html('<div class="notice ' + className + '" style="padding: 8px 12px;"><p>' + message + '</p></div>')
                        .show()
                        .delay(5000)
                        .fadeOut();
            }
            <?php endif; ?>
        });
        </script>
        <?php
    }
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id)
    {
        // Check if our nonce is set
        if (!isset($_POST['softworld_ai_seo_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['softworld_ai_seo_nonce'], 'softworld_ai_seo_meta_box')) {
            return;
        }

        // If this is an autosave, don't do anything
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

        // Save Focus Keyword
        if (isset($_POST['softworld_ai_seo_focus_keyword'])) {
            update_post_meta($post_id, '_softworld_ai_seo_focus_keyword', sanitize_text_field($_POST['softworld_ai_seo_focus_keyword']));
        }

        // Save Meta Keywords
        if (isset($_POST['softworld_ai_seo_meta_keywords'])) {
            update_post_meta($post_id, '_softworld_ai_seo_meta_keywords', sanitize_text_field($_POST['softworld_ai_seo_meta_keywords']));
        }
    }

    /**
     * Enqueue scripts for meta box
     */
    public function enqueue_scripts($hook)
    {
        global $post;

        if (!in_array($hook, ['post.php', 'post-new.php']) || !$post) {
            return;
        }

        // // Enqueue the AI admin script
        // wp_enqueue_script(
        //     'softworld-ai-seo-admin',
        //     plugin_dir_url(__FILE__) . '../assets/js/ai-admin.js',
        //     ['jquery'],
        //     '1.0.0',
        //     true
        // );

        // Localize script with data
        wp_localize_script('softworld-ai-seo-admin', 'softworldAISettings', [
            'nonce' => wp_create_nonce('softworld_ai_seo_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'post_id' => $post->ID,
            'post_type' => $post->post_type,
            'autoGenerate' => get_option('softworld_ai_seo_enable_auto_generation', 0),
            'i18n' => [
                'generating' => __('Generating...', 'softworld-ai-seo'),
                'generated' => __('Generated successfully!', 'softworld-ai-seo'),
                'error' => __('Error occurred', 'softworld-ai-seo'),
                'content_required' => __('Please add some content first.', 'softworld-ai-seo'),
                'ai_unavailable' => __('AI features are not available.', 'softworld-ai-seo')
            ]
        ]);

        // Add enhanced CSS for table layout
        wp_add_inline_style('wp-admin', '
            .softworld-meta-box-container .form-table th {
                width: 150px;
                vertical-align: top;
                padding-top: 15px;
            }
            
            .softworld-meta-box-container .form-table td {
                padding-top: 10px;
            }
            
            .softworld-ai-status {
                margin-top: 10px;
                padding: 8px 12px;
                border-radius: 3px;
                display: none;
            }
            
            .softworld-ai-status.loading {
                background: #e7f3ff;
                border-left: 4px solid #0073aa;
                color: #0073aa;
                display: block;
            }
            
            .softworld-ai-status.success {
                background: #edfaef;
                border-left: 4px solid #00a32a;
                color: #00a32a;
                display: block;
            }
            
            .softworld-ai-status.error {
                background: #fcf0f1;
                border-left: 4px solid #d63638;
                color: #d63638;
                display: block;
            }
        ');
    }

    /**
     * AJAX handler for loading bulk posts
     */
    public function ajax_load_bulk_posts()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $status = sanitize_text_field($_POST['status'] ?? 'missing');

        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        // Filter by missing SEO data if requested
        if ($status === 'missing') {
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_softworld_ai_seo_title',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_softworld_ai_seo_title',
                    'value' => '',
                    'compare' => '='
                ],
                [
                    'key' => '_softworld_ai_seo_description',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_softworld_ai_seo_description',
                    'value' => '',
                    'compare' => '='
                ]
            ];
        }

        $posts = get_posts($args);
        $results = [];

        foreach ($posts as $post) {
            $seo_title = get_post_meta($post->ID, '_softworld_ai_seo_title', true);
            $seo_description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);

            $results[] = [
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'seo_title' => !empty($seo_title),
                'seo_description' => !empty($seo_description),
                'edit_url' => get_edit_post_link($post->ID)
            ];
        }

        wp_send_json_success([
            'posts' => $results,
            'total' => count($results)
        ]);
    }
}