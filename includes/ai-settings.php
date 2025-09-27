<?php
/**
 * AI Settings Admin Page
 * 
 * Handles the admin interface for AI SEO settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Settings
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register AI SEO settings
     */
    public function register_settings()
    {
        // Register settings group
        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_openai_api_key', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        
        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_openai_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gpt-3.5-turbo'
        ]);
        
        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_max_tokens', [
            'sanitize_callback' => 'intval',
            'default' => 150
        ]);
        
        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_temperature', [
            'sanitize_callback' => [$this, 'sanitize_temperature'],
            'default' => 0.7
        ]);
        
        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_enable_auto_generation', [
            'sanitize_callback' => 'intval',
            'default' => 0
        ]);
        
        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_default_tone', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'professional'
        ]);

        // Add settings sections
        add_settings_section(
            'softworld_ai_api_settings',
            __('OpenAI API Configuration', 'softworld-ai-seo'),
            [$this, 'api_settings_callback'],
            'softworld-ai-seo-settings'
        );

        add_settings_section(
            'softworld_ai_generation_settings',
            __('Generation Settings', 'softworld-ai-seo'),
            [$this, 'generation_settings_callback'],
            'softworld-ai-seo-settings'
        );

        add_settings_section(
            'softworld_ai_bulk_settings',
            __('Bulk Generation', 'softworld-ai-seo'),
            [$this, 'bulk_settings_callback'],
            'softworld-ai-seo-settings'
        );

        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_default_faq_count', [
            'sanitize_callback' => 'intval',
            'default' => 5
        ]);

        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_default_point_count', [
            'sanitize_callback' => 'intval',
            'default' => 5
        ]);

        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_enable_faqs', [
            'sanitize_callback' => 'intval',
            'default' => 1
        ]);

        register_setting('softworld_ai_seo_settings', 'softworld_ai_seo_enable_key_points', [
            'sanitize_callback' => 'intval',
            'default' => 1
        ]);

        // Add settings fields
        $this->add_settings_fields();
    }

    /**
     * Add all settings fields
     */
    private function add_settings_fields()
    {
        // API Settings Fields
        add_settings_field(
            'softworld_ai_seo_openai_api_key',
            __('OpenAI API Key', 'softworld-ai-seo'),
            [$this, 'api_key_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_api_settings'
        );

        add_settings_field(
            'softworld_ai_seo_openai_model',
            __('OpenAI Model', 'softworld-ai-seo'),
            [$this, 'model_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_api_settings'
        );

        // Generation Settings Fields
        add_settings_field(
            'softworld_ai_seo_max_tokens',
            __('Max Tokens', 'softworld-ai-seo'),
            [$this, 'max_tokens_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_temperature',
            __('Temperature', 'softworld-ai-seo'),
            [$this, 'temperature_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_default_tone',
            __('Default Tone', 'softworld-ai-seo'),
            [$this, 'default_tone_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_enable_auto_generation',
            __('Auto Generation', 'softworld-ai-seo'),
            [$this, 'auto_generation_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_enable_faqs',
            __('Enable FAQ Generation', 'softworld-ai-seo'),
            [$this, 'enable_faqs_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_enable_key_points',
            __('Enable Key Points', 'softworld-ai-seo'),
            [$this, 'enable_key_points_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_default_faq_count',
            __('Default FAQ Count', 'softworld-ai-seo'),
            [$this, 'default_faq_count_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

        add_settings_field(
            'softworld_ai_seo_default_point_count',
            __('Default Key Points Count', 'softworld-ai-seo'),
            [$this, 'default_point_count_field'],
            'softworld-ai-seo-settings',
            'softworld_ai_generation_settings'
        );

    }

    // Add these field methods:
    public function enable_faqs_field()
    {
        $value = get_option('softworld_ai_seo_enable_faqs', 1);
        echo '<label>';
        echo '<input type="checkbox" id="softworld_ai_seo_enable_faqs" name="softworld_ai_seo_enable_faqs" value="1"' . checked($value, 1, false) . ' />';
        echo ' ' . __('Enable FAQ generation in post editor', 'softworld-ai-seo');
        echo '</label>';
        echo '<p class="description">' . __('Allow users to generate FAQs for their content.', 'softworld-ai-seo') . '</p>';
    }

    public function enable_key_points_field()
    {
        $value = get_option('softworld_ai_seo_enable_key_points', 1);
        echo '<label>';
        echo '<input type="checkbox" id="softworld_ai_seo_enable_key_points" name="softworld_ai_seo_enable_key_points" value="1"' . checked($value, 1, false) . ' />';
        echo ' ' . __('Enable key points extraction in post editor', 'softworld-ai-seo');
        echo '</label>';
        echo '<p class="description">' . __('Allow users to extract key points from their content.', 'softworld-ai-seo') . '</p>';
    }

    public function default_faq_count_field()
    {
        $value = get_option('softworld_ai_seo_default_faq_count', 5);
        echo '<input type="number" id="softworld_ai_seo_default_faq_count" name="softworld_ai_seo_default_faq_count" value="' . esc_attr($value) . '" min="3" max="10" step="1" />';
        echo '<p class="description">' . __('Default number of FAQs to generate (3-10).', 'softworld-ai-seo') . '</p>';
    }

    public function default_point_count_field()
    {
        $value = get_option('softworld_ai_seo_default_point_count', 5);
        echo '<input type="number" id="softworld_ai_seo_default_point_count" name="softworld_ai_seo_default_point_count" value="' . esc_attr($value) . '" min="3" max="10" step="1" />';
        echo '<p class="description">' . __('Default number of key points to extract (3-10).', 'softworld-ai-seo') . '</p>';
    }

    /**
     * Section callbacks
     */
    public function api_settings_callback()
    {
        echo '<p>' . __('Configure your OpenAI API settings to enable AI-powered SEO generation.', 'softworld-ai-seo') . '</p>';
    }

    public function generation_settings_callback()
    {
        echo '<p>' . __('Customize how AI generates SEO titles and descriptions.', 'softworld-ai-seo') . '</p>';
    }

    public function bulk_settings_callback()
    {
        echo '<p>' . __('Generate SEO data for multiple posts at once.', 'softworld-ai-seo') . '</p>';
    }

    /**
     * Field callbacks
     */
    public function api_key_field()
    {
        $value = get_option('softworld_ai_seo_openai_api_key', '');
        
        echo '<input type="password" id="softworld_ai_seo_openai_api_key" name="softworld_ai_seo_openai_api_key" value="' . esc_attr($value) . '" class="regular-text" autocomplete="new-password" />';
        echo '<button type="button" class="button" onclick="toggleApiKey()">' . __('Show/Hide', 'softworld-ai-seo') . '</button>';
        echo '<button type="button" id="test-connection-btn" class="button button-secondary">' . __('Test Connection', 'softworld-ai-seo') . '</button>';
        echo '<p class="description">' . sprintf(__('Get your API key from %s', 'softworld-ai-seo'), '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a>') . '</p>';
        echo '<div id="connection-status" style="margin-top: 10px;"></div>';
        
        // Add JavaScript for API key toggle and testing
        ?>
        <script>
        function toggleApiKey() {
            const field = document.getElementById('softworld_ai_seo_openai_api_key');
            field.type = field.type === 'password' ? 'text' : 'password';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const testBtn = document.getElementById('test-connection-btn');
            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    const btn = this;
                    const status = document.getElementById('connection-status');
                    const apiKey = document.getElementById('softworld_ai_seo_openai_api_key').value;
                    
                    if (!apiKey) {
                        status.innerHTML = '<div class="notice notice-error inline"><p><?php _e("Please enter an API key first.", "softworld-ai-seo"); ?></p></div>';
                        return;
                    }
                    
                    btn.disabled = true;
                    btn.textContent = '<?php _e("Testing...", "softworld-ai-seo"); ?>';
                    
                    jQuery.post(ajaxurl, {
                        action: 'softworld_test_openai_connection',
                        nonce: '<?php echo wp_create_nonce("softworld_ai_seo_nonce"); ?>'
                    }, function(response) {
                        if (response.success) {
                            status.innerHTML = '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>';
                        } else {
                            status.innerHTML = '<div class="notice notice-error inline"><p>' + response.data + '</p></div>';
                        }
                    }).always(function() {
                        btn.disabled = false;
                        btn.textContent = '<?php _e("Test Connection", "softworld-ai-seo"); ?>';
                    });
                });
            }
        });
        </script>
        <?php
    }

    public function model_field()
    {
        $value = get_option('softworld_ai_seo_openai_model', 'gpt-3.5-turbo');
        $models = [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Recommended)',
            'gpt-4' => 'GPT-4 (Premium)',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo (Latest)'
        ];
        
        echo '<select id="softworld_ai_seo_openai_model" name="softworld_ai_seo_openai_model">';
        foreach ($models as $model_key => $model_name) {
            echo '<option value="' . esc_attr($model_key) . '"' . selected($value, $model_key, false) . '>' . esc_html($model_name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('GPT-3.5 Turbo is cost-effective for most use cases. GPT-4 provides better quality but costs more.', 'softworld-ai-seo') . '</p>';
    }

    public function max_tokens_field()
    {
        $value = get_option('softworld_ai_seo_max_tokens', 150);
        echo '<input type="number" id="softworld_ai_seo_max_tokens" name="softworld_ai_seo_max_tokens" value="' . esc_attr($value) . '" min="50" max="500" step="10" />';
        echo '<p class="description">' . __('Maximum tokens for AI responses. Higher values allow longer responses but cost more.', 'softworld-ai-seo') . '</p>';
    }

    public function temperature_field()
    {
        $value = get_option('softworld_ai_seo_temperature', 0.7);
        echo '<input type="number" id="softworld_ai_seo_temperature" name="softworld_ai_seo_temperature" value="' . esc_attr($value) . '" min="0" max="1" step="0.1" />';
        echo '<p class="description">' . __('Creativity level: 0 = More focused, 1 = More creative. Recommended: 0.7', 'softworld-ai-seo') . '</p>';
    }

    public function default_tone_field()
    {
        $value = get_option('softworld_ai_seo_default_tone', 'professional');
        $tones = [
            'professional' => __('Professional', 'softworld-ai-seo'),
            'casual' => __('Casual', 'softworld-ai-seo'),
            'friendly' => __('Friendly', 'softworld-ai-seo'),
            'authoritative' => __('Authoritative', 'softworld-ai-seo'),
            'conversational' => __('Conversational', 'softworld-ai-seo'),
            'engaging' => __('Engaging', 'softworld-ai-seo')
        ];
        
        echo '<select id="softworld_ai_seo_default_tone" name="softworld_ai_seo_default_tone">';
        foreach ($tones as $tone_key => $tone_name) {
            echo '<option value="' . esc_attr($tone_key) . '"' . selected($value, $tone_key, false) . '>' . esc_html($tone_name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Default tone for AI-generated content. Can be overridden per post.', 'softworld-ai-seo') . '</p>';
    }

    public function auto_generation_field()
    {
        $value = get_option('softworld_ai_seo_enable_auto_generation', 0);
        echo '<label>';
        echo '<input type="checkbox" id="softworld_ai_seo_enable_auto_generation" name="softworld_ai_seo_enable_auto_generation" value="1"' . checked($value, 1, false) . ' />';
        echo ' ' . __('Automatically suggest SEO title and description when editing posts', 'softworld-ai-seo');
        echo '</label>';
        echo '<p class="description">' . __('When enabled, AI suggestions will appear automatically in the post editor.', 'softworld-ai-seo') . '</p>';
    }

    /**
     * Sanitize temperature value
     */
    public function sanitize_temperature($value)
    {
        $value = floatval($value);
        return max(0, min(1, $value));
    }

    /**
     * Render the AI settings admin page
     */
    public function render_admin_page()
    {
        // Handle form submission
        if (isset($_POST['submit']) && $_POST['submit'] === 'Save Changes') {
            // WordPress handles the saving through settings API
        }

        // Get AI generator for statistics
        $ai_generator = new Softworld_AI_SEO_Generator();
        $usage_stats = $ai_generator->get_usage_stats(30);
        $is_ai_available = $ai_generator->is_ai_available();

        ?>
        <div class="wrap">
            <h1><?php _e('AI SEO Settings', 'softworld-ai-seo'); ?></h1>

            <?php if (!$is_ai_available): ?>
                <div class="notice notice-warning">
                    <p><strong><?php _e('AI features are not available', 'softworld-ai-seo'); ?></strong></p>
                    <p><?php _e('Please configure your OpenAI API key below to enable AI-powered SEO generation.', 'softworld-ai-seo'); ?></p>
                </div>
            <?php endif; ?>

            <div class="softworld-ai-settings-container">
                <div class="softworld-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('softworld_ai_seo_settings');
                        do_settings_sections('softworld-ai-seo-settings');
                        submit_button();
                        ?>
                    </form>
                </div>

                <div class="softworld-settings-sidebar">
                    <!-- Usage Statistics -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Usage Statistics (30 days)', 'softworld-ai-seo'); ?></h2>
                        <div class="inside">
                            <div class="softworld-stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($usage_stats['total_generations']); ?></span>
                                    <span class="stat-label"><?php _e('Total Generations', 'softworld-ai-seo'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($usage_stats['titles_generated']); ?></span>
                                    <span class="stat-label"><?php _e('Titles Generated', 'softworld-ai-seo'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($usage_stats['descriptions_generated']); ?></span>
                                    <span class="stat-label"><?php _e('Descriptions Generated', 'softworld-ai-seo'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($usage_stats['bulk_generations']); ?></span>
                                    <span class="stat-label"><?php _e('Bulk Operations', 'softworld-ai-seo'); ?></span>
                                </div>
                                
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($usage_stats['faqs_generated']); ?></span>
                                    <span class="stat-label"><?php _e('FAQs Generated', 'softworld-ai-seo'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($usage_stats['key_points_generated']); ?></span>
                                    <span class="stat-label"><?php _e('Key Points Generated', 'softworld-ai-seo'); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($usage_stats['daily_usage'])): ?>
                                <canvas id="usage-chart" width="300" height="150"></canvas>
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    if (typeof Chart !== 'undefined') {
                                        const ctx = document.getElementById('usage-chart').getContext('2d');
                                        const chartData = <?php echo json_encode($usage_stats['daily_usage']); ?>;
                                        
                                        new Chart(ctx, {
                                            type: 'line',
                                            data: {
                                                labels: chartData.map(item => item.date),
                                                datasets: [{
                                                    label: '<?php _e("Daily Usage", "softworld-ai-seo"); ?>',
                                                    data: chartData.map(item => item.count),
                                                    borderColor: '#0073aa',
                                                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                                    borderWidth: 2,
                                                    fill: true
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                scales: {
                                                    y: {
                                                        beginAtZero: true
                                                    }
                                                }
                                            }
                                        });
                                    }
                                });
                                </script>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Help & Tips -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Tips & Best Practices', 'softworld-ai-seo'); ?></h2>
                        <div class="inside">
                            <ul class="softworld-tips-list">
                                <li><?php _e('Use target keywords in your content for better AI suggestions', 'softworld-ai-seo'); ?></li>
                                <li><?php _e('Write detailed content - AI generates better SEO from comprehensive text', 'softworld-ai-seo'); ?></li>
                                <li><?php _e('Review and edit AI suggestions before publishing', 'softworld-ai-seo'); ?></li>
                                <li><?php _e('Lower temperature values (0.3-0.5) for more focused results', 'softworld-ai-seo'); ?></li>
                                <li><?php _e('Higher temperature values (0.7-0.9) for more creative results', 'softworld-ai-seo'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pricing Information -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('OpenAI Pricing', 'softworld-ai-seo'); ?></h2>
                        <div class="inside">
                            <p><?php _e('Approximate costs per generation:', 'softworld-ai-seo'); ?></p>
                            <ul class="softworld-pricing-list">
                                <li><strong>GPT-3.5 Turbo:</strong> ~$0.001-0.002</li>
                                <li><strong>GPT-4:</strong> ~$0.01-0.03</li>
                                <li><strong>GPT-4 Turbo:</strong> ~$0.005-0.015</li>
                            </ul>
                            <p class="description"><?php _e('Costs vary based on content length and settings.', 'softworld-ai-seo'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Generation Section -->
            <div class="softworld-bulk-section">
                <h2><?php _e('Bulk SEO Generation', 'softworld-ai-seo'); ?></h2>
                
                <div class="softworld-bulk-controls">
                    <div class="bulk-filters">
                        <label for="bulk-post-type"><?php _e('Post Type:', 'softworld-ai-seo'); ?></label>
                        <select id="bulk-post-type">
                            <option value="post"><?php _e('Posts', 'softworld-ai-seo'); ?></option>
                            <option value="page"><?php _e('Pages', 'softworld-ai-seo'); ?></option>
                            <?php
                            $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
                            foreach ($custom_post_types as $cpt) {
                                echo '<option value="' . esc_attr($cpt->name) . '">' . esc_html($cpt->labels->name) . '</option>';
                            }
                            ?>
                        </select>

                        <label for="bulk-status"><?php _e('Status:', 'softworld-ai-seo'); ?></label>
                        <select id="bulk-status">
                            <option value="missing"><?php _e('Missing SEO Data', 'softworld-ai-seo'); ?></option>
                            <option value="all"><?php _e('All Posts', 'softworld-ai-seo'); ?></option>
                        </select>

                        <button type="button" id="load-bulk-posts" class="button"><?php _e('Load Posts', 'softworld-ai-seo'); ?></button>
                    </div>

                    <div class="bulk-options" style="display: none;">
                        <h3><?php _e('Generation Options', 'softworld-ai-seo'); ?></h3>
                        
                        <label>
                            <input type="checkbox" id="bulk-generate-titles" checked>
                            <?php _e('Generate Titles', 'softworld-ai-seo'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" id="bulk-generate-descriptions" checked>
                            <?php _e('Generate Descriptions', 'softworld-ai-seo'); ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" id="bulk-auto-save">
                            <?php _e('Auto-save Results', 'softworld-ai-seo'); ?>
                        </label>

                        <label for="bulk-tone"><?php _e('Tone:', 'softworld-ai-seo'); ?></label>
                        <select id="bulk-tone">
                            <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                            <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                            <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                            <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                        </select>

                        <button type="button" id="start-bulk-generation" class="button button-primary" disabled>
                            <?php _e('Start Generation', 'softworld-ai-seo'); ?>
                        </button>
                    </div>
                </div>

                <div id="bulk-posts-container" style="display: none;">
                    <h3><?php _e('Select Posts', 'softworld-ai-seo'); ?></h3>
                    <div class="bulk-actions">
                        <button type="button" id="select-all-posts" class="button"><?php _e('Select All', 'softworld-ai-seo'); ?></button>
                        <button type="button" id="deselect-all-posts" class="button"><?php _e('Deselect All', 'softworld-ai-seo'); ?></button>
                    </div>
                    <div id="bulk-posts-list"></div>
                </div>

                <div id="bulk-progress" style="display: none;">
                    <h3><?php _e('Generation Progress', 'softworld-ai-seo'); ?></h3>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">0 / 0</div>
                    <div id="bulk-results"></div>
                </div>
            </div>
        </div>

        <style>
        .softworld-ai-settings-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .softworld-settings-main {
            flex: 2;
        }
        
        .softworld-settings-sidebar {
            flex: 1;
        }
        
        .softworld-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .stat-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .softworld-tips-list, .softworld-pricing-list {
            margin: 0;
            padding-left: 20px;
        }
        
        .softworld-tips-list li, .softworld-pricing-list li {
            margin-bottom: 8px;
        }
        
        .softworld-bulk-section {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .bulk-filters {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .bulk-options {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .bulk-options label {
            display: block;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        #bulk-posts-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background: white;
        }
        
        .bulk-post-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .bulk-post-item:last-child {
            border-bottom: none;
        }
        
        .bulk-post-item input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .bulk-post-title {
            font-weight: bold;
        }
        
        .bulk-post-meta {
            color: #666;
            font-size: 12px;
        }
        
        #bulk-results {
            max-height: 300px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        
        .bulk-result-item {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 3px;
            border-left: 4px solid #ddd;
        }
        
        .bulk-result-item.success {
            background: #f0f8ff;
            border-left-color: #00a32a;
            color: #00a32a;
        }
        
        .bulk-result-item.error {
            background: #ffebee;
            border-left-color: #d63638;
            color: #d63638;
        }
        
        @media (max-width: 768px) {
            .softworld-ai-settings-container {
                flex-direction: column;
            }
            
            .bulk-filters {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .bulk-filters label {
                margin-bottom: 5px;
            }
            
            .softworld-stats-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
}