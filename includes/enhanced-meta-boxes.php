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

        ?>
        <div class="softworld-seo-meta-box">
            
            <?php if (!$ai_available): ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php _e('AI features are not available. Configure your OpenAI API key in', 'softworld-ai-seo'); ?>
                        <a href="<?php echo admin_url('admin.php?page=softworldit-seo-settings'); ?>" target="_blank">
                            <?php _e('AI Settings', 'softworld-ai-seo'); ?>
                        </a>
                        <?php _e('to enable AI-powered SEO generation.', 'softworld-ai-seo'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- SEO Title Section -->
            <div class="softworld-field-group">
                <label for="softworld_ai_seo_title" class="softworld-field-label">
                    <strong><?php _e('SEO Title', 'softworld-ai-seo'); ?></strong>
                    <span class="softworld-field-description"><?php _e('Recommended: 50-60 characters', 'softworld-ai-seo'); ?></span>
                </label>
                
                <div class="softworld-ai-field-container">
                    <input type="text" 
                           id="softworld_ai_seo_title" 
                           name="softworld_ai_seo_title" 
                           value="<?php echo esc_attr($seo_title); ?>" 
                           class="widefat softworld-seo-input" 
                           placeholder="<?php _e('Enter SEO title...', 'softworld-ai-seo'); ?>" 
                           maxlength="60" />
                    
                    <div class="softworld-char-counter" data-max="60">
                        <span class="current"><?php echo strlen($seo_title); ?></span> / <span class="max">60</span> <?php _e('characters', 'softworld-ai-seo'); ?>
                    </div>

                    <?php if ($ai_available): ?>
                        <div class="softworld-ai-controls">
                            <button type="button" class="button softworld-ai-generate" data-type="title">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Generate SEO Title', 'softworld-ai-seo'); ?>
                            </button>
                            
                            <div class="softworld-ai-options" style="display: none;">
                                <div class="softworld-ai-options-grid">
                                    <div class="ai-option">
                                        <label for="title-keywords"><?php _e('Target Keywords', 'softworld-ai-seo'); ?></label>
                                        <input type="text" id="title-keywords" class="softworld-target-keywords" placeholder="<?php _e('Optional keywords...', 'softworld-ai-seo'); ?>">
                                    </div>
                                    <div class="ai-option">
                                        <label for="title-tone"><?php _e('Tone', 'softworld-ai-seo'); ?></label>
                                        <select id="title-tone" class="softworld-tone-select">
                                            <option value="professional" <?php selected($default_tone, 'professional'); ?>><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                            <option value="casual" <?php selected($default_tone, 'casual'); ?>><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                            <option value="friendly" <?php selected($default_tone, 'friendly'); ?>><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                            <option value="authoritative" <?php selected($default_tone, 'authoritative'); ?>><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                            <option value="conversational" <?php selected($default_tone, 'conversational'); ?>><?php _e('Conversational', 'softworld-ai-seo'); ?></option>
                                            <option value="engaging" <?php selected($default_tone, 'engaging'); ?>><?php _e('Engaging', 'softworld-ai-seo'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ai-action-buttons">
                                    <button type="button" class="button-primary softworld-generate-now">
                                        <?php _e('Generate', 'softworld-ai-seo'); ?>
                                    </button>
                                    <button type="button" class="button softworld-cancel-generate">
                                        <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="softworld-ai-status"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SEO Description Section -->
            <div class="softworld-field-group">
                <label for="softworld_ai_seo_description" class="softworld-field-label">
                    <strong><?php _e('Meta Description', 'softworld-ai-seo'); ?></strong>
                    <span class="softworld-field-description"><?php _e('Recommended: 150-160 characters', 'softworld-ai-seo'); ?></span>
                </label>
                
                <div class="softworld-ai-field-container">
                    <textarea id="softworld_ai_seo_description" 
                              name="softworld_ai_seo_description" 
                              class="widefat softworld-seo-textarea" 
                              rows="3" 
                              placeholder="<?php _e('Enter meta description...', 'softworld-ai-seo'); ?>"
                              maxlength="160"><?php echo esc_textarea($seo_description); ?></textarea>
                    
                    <div class="softworld-char-counter" data-max="160" data-optimal-min="150">
                        <span class="current"><?php echo strlen($seo_description); ?></span> / <span class="max">160</span> <?php _e('characters', 'softworld-ai-seo'); ?>
                    </div>

                    <?php if ($ai_available): ?>
                        <div class="softworld-ai-controls">
                            <button type="button" class="button softworld-ai-generate" data-type="description">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Generate Meta Description', 'softworld-ai-seo'); ?>
                            </button>
                            
                            <div class="softworld-ai-options" style="display: none;">
                                <div class="softworld-ai-options-grid">
                                    <div class="ai-option">
                                        <label for="desc-keywords"><?php _e('Target Keywords', 'softworld-ai-seo'); ?></label>
                                        <input type="text" id="desc-keywords" class="softworld-target-keywords" placeholder="<?php _e('Optional keywords...', 'softworld-ai-seo'); ?>">
                                    </div>
                                    <div class="ai-option">
                                        <label for="desc-tone"><?php _e('Tone', 'softworld-ai-seo'); ?></label>
                                        <select id="desc-tone" class="softworld-tone-select">
                                            <option value="professional" <?php selected($default_tone, 'professional'); ?>><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                            <option value="casual" <?php selected($default_tone, 'casual'); ?>><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                            <option value="friendly" <?php selected($default_tone, 'friendly'); ?>><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                            <option value="authoritative" <?php selected($default_tone, 'authoritative'); ?>><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                            <option value="conversational" <?php selected($default_tone, 'conversational'); ?>><?php _e('Conversational', 'softworld-ai-seo'); ?></option>
                                            <option value="engaging" <?php selected($default_tone, 'engaging'); ?>><?php _e('Engaging', 'softworld-ai-seo'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ai-action-buttons">
                                    <button type="button" class="button-primary softworld-generate-now">
                                        <?php _e('Generate', 'softworld-ai-seo'); ?>
                                    </button>
                                    <button type="button" class="button softworld-cancel-generate">
                                        <?php _e('Cancel', 'softworld-ai-seo'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="softworld-ai-status"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional SEO Fields -->
            <div class="softworld-field-group">
                <label for="softworld_ai_seo_focus_keyword" class="softworld-field-label">
                    <strong><?php _e('Focus Keyword', 'softworld-ai-seo'); ?></strong>
                    <span class="softworld-field-description"><?php _e('Primary keyword for this content', 'softworld-ai-seo'); ?></span>
                </label>
                <input type="text" 
                       id="softworld_ai_seo_focus_keyword" 
                       name="softworld_ai_seo_focus_keyword" 
                       value="<?php echo esc_attr($focus_keyword); ?>" 
                       class="widefat softworld-seo-input" 
                       placeholder="<?php _e('Enter focus keyword...', 'softworld-ai-seo'); ?>" />
            </div>

            <div class="softworld-field-group">
                <label for="softworld_ai_seo_meta_keywords" class="softworld-field-label">
                    <strong><?php _e('Meta Keywords', 'softworld-ai-seo'); ?></strong>
                    <span class="softworld-field-description"><?php _e('Comma-separated keywords (optional)', 'softworld-ai-seo'); ?></span>
                </label>
                <input type="text" 
                       id="softworld_ai_seo_meta_keywords" 
                       name="softworld_ai_seo_meta_keywords" 
                       value="<?php echo esc_attr($meta_keywords); ?>" 
                       class="widefat softworld-seo-input" 
                       placeholder="<?php _e('keyword1, keyword2, keyword3...', 'softworld-ai-seo'); ?>" />
            </div>

            <!-- SEO Preview -->
            <div class="softworld-field-group">
                <h4><?php _e('Search Preview', 'softworld-ai-seo'); ?></h4>
                <div class="softworld-seo-preview">
                    <div class="preview-url">
                        <?php echo esc_url(get_permalink($post->ID) ?: home_url('/')); ?>
                    </div>
                    <div class="preview-title" id="preview-title">
                        <?php echo esc_html($seo_title ?: $post->post_title ?: 'Your SEO Title'); ?>
                    </div>
                    <div class="preview-description" id="preview-description">
                        <?php echo esc_html($seo_description ?: 'Your meta description will appear here...'); ?>
                    </div>
                </div>
            </div>

            <?php if ($ai_available): ?>
                <!-- Quick AI Actions -->
                <div class="softworld-field-group">
                    <h4><?php _e('Quick AI Actions', 'softworld-ai-seo'); ?></h4>
                    <div class="softworld-quick-actions">
                        <button type="button" class="button button-secondary softworld-generate-both">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('Generate Both Title & Description', 'softworld-ai-seo'); ?>
                        </button>
                        
                        <div class="softworld-ai-bulk-settings" style="display: none;">
                            <label>
                                <?php _e('Tone:', 'softworld-ai-seo'); ?>
                                <select class="softworld-bulk-tone">
                                    <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                    <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                    <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                    <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                </select>
                            </label>
                            <label>
                                <?php _e('Keywords:', 'softworld-ai-seo'); ?>
                                <input type="text" class="softworld-bulk-keywords" placeholder="<?php _e('Optional keywords...', 'softworld-ai-seo'); ?>">
                            </label>
                            <button type="button" class="button-primary softworld-generate-both-now">
                                <?php _e('Generate Both', 'softworld-ai-seo'); ?>
                            </button>
                            <button type="button" class="button softworld-cancel-bulk">
                                <?php _e('Cancel', 'softworld-ai-seo'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <style>
        .softworld-seo-meta-box {
            padding: 0;
        }
        
        .softworld-field-group {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .softworld-field-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .softworld-field-label {
            display: block;
            margin-bottom: 8px;
            color: #1d2327;
        }
        
        .softworld-field-label strong {
            font-size: 14px;
        }
        
        .softworld-field-description {
            display: block;
            font-size: 12px;
            color: #646970;
            font-weight: normal;
            margin-top: 2px;
        }
        
        .softworld-seo-input,
        .softworld-seo-textarea {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .softworld-seo-input:focus,
        .softworld-seo-textarea:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        .softworld-char-counter {
            margin-top: 5px;
            font-size: 12px;
            color: #646970;
            text-align: right;
        }
        
        .softworld-char-counter .current {
            font-weight: bold;
        }
        
        .softworld-char-counter.optimal .current {
            color: #00a32a;
        }
        
        .softworld-char-counter.warning .current {
            color: #dba617;
        }
        
        .softworld-char-counter.error .current {
            color: #d63638;
        }
        
        .softworld-ai-controls {
            margin-top: 10px;
            padding: 15px;
            background: #f6f7f7;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .softworld-ai-generate {
            background: #0073aa !important;
            color: white !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 3px !important;
            cursor: pointer;
        }
        
        .softworld-ai-generate:hover {
            background: #005a87 !important;
        }
        
        .softworld-ai-generate .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            margin-right: 5px;
        }
        
        .softworld-ai-options {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .softworld-ai-options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .ai-option label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #1d2327;
        }
        
        .ai-option input,
        .ai-option select {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #c3c4c7;
            border-radius: 3px;
        }
        
        .ai-action-buttons {
            display: flex;
            gap: 10px;
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
        
        .softworld-ai-status.warning {
            background: #fcf9e8;
            border-left: 4px solid #dba617;
            color: #dba617;
            display: block;
        }
        
        .softworld-seo-preview {
            padding: 15px;
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            font-family: Arial, sans-serif;
        }
        
        .preview-url {
            color: #1a0dab;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .preview-title {
            color: #1a0dab;
            font-size: 18px;
            font-weight: normal;
            margin-bottom: 2px;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .preview-description {
            color: #545454;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .softworld-quick-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .softworld-generate-both {
            align-self: flex-start;
        }
        
        .softworld-ai-bulk-settings {
            padding: 15px;
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .softworld-ai-bulk-settings label {
            display: block;
            margin-bottom: 10px;
        }
        
        .softworld-ai-bulk-settings input,
        .softworld-ai-bulk-settings select {
            margin-left: 8px;
            min-width: 150px;
        }
        </style>
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

        // Enqueue the AI admin script
        wp_enqueue_script(
            'softworld-ai-seo-admin',
            plugin_dir_url(__FILE__) . '../assets/js/ai-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );

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

        // Add inline script for real-time preview updates
        wp_add_inline_script('softworld-ai-seo-admin', '
            jQuery(document).ready(function($) {
                // Update preview when fields change
                $("#softworld_ai_seo_title").on("input", function() {
                    const value = $(this).val() || $("#title").val() || "Your SEO Title";
                    $("#preview-title").text(value);
                    
                    // Update character counter
                    updateCharCounter($(this), 60);
                });
                
                $("#softworld_ai_seo_description").on("input", function() {
                    const value = $(this).val() || "Your meta description will appear here...";
                    $("#preview-description").text(value);
                    
                    // Update character counter
                    updateCharCounter($(this), 160, 150);
                });
                
                // Character counter function
                function updateCharCounter($field, max, optimalMin) {
                    const $counter = $field.siblings(".softworld-char-counter");
                    const length = $field.val().length;
                    
                    $counter.find(".current").text(length);
                    $counter.removeClass("optimal warning error");
                    
                    if (optimalMin && length >= optimalMin && length <= max) {
                        $counter.addClass("optimal");
                    } else if (length > max * 0.8) {
                        $counter.addClass("warning");
                    } else if (length > max) {
                        $counter.addClass("error");
                    }
                }
                
                // Initialize counters
                $("#softworld_ai_seo_title").trigger("input");
                $("#softworld_ai_seo_description").trigger("input");
            });
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