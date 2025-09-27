<?php
/**
 * AI SEO Generator Class
 * 
 * Handles OpenAI integration for generating SEO titles and meta descriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Generator
{
    private $api_key;
    private $model;
    private $max_tokens;
    private $temperature;

    public function __construct()
    {
        // Get settings from options
        $this->api_key = get_option('softworld_ai_seo_openai_api_key', false);
        $this->model = get_option('softworld_ai_seo_openai_model', 'gpt-3.5-turbo');
        $this->max_tokens = get_option('softworld_ai_seo_max_tokens', 150);
        $this->temperature = get_option('softworld_ai_seo_temperature', 0.7);

        // Add hooks
        add_action('wp_ajax_softworld_generate_seo_title', [$this, 'ajax_generate_seo_title']);
        add_action('wp_ajax_softworld_generate_seo_description', [$this, 'ajax_generate_seo_description']);
        add_action('wp_ajax_softworld_generate_bulk_seo', [$this, 'ajax_generate_bulk_seo']);
        add_action('wp_ajax_softworld_test_openai_connection', [$this, 'ajax_test_openai_connection']);
        add_action('wp_ajax_softworld_generate_faqs', [$this, 'ajax_generate_faqs']);
        add_action('wp_ajax_softworld_generate_key_points', [$this, 'ajax_generate_key_points']);
    }

    /**
     * Check if AI features are available
     */
    public function is_ai_available()
    {
        return !empty($this->api_key) && !empty($this->model);
    }

    /**
     * Generate SEO title using OpenAI
     */
    public function generate_seo_title($content, $post_type = 'post', $target_keywords = '', $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        // Prepare the prompt
        $prompt = $this->build_title_prompt($content, $post_type, $target_keywords, $tone);

        // Make API request
        return $this->make_openai_request($prompt, 60); // 60 tokens for title
    }

    /**
     * Generate SEO meta description using OpenAI
     */
    public function generate_seo_description($content, $post_type = 'post', $target_keywords = '', $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        // Prepare the prompt
        $prompt = $this->build_description_prompt($content, $post_type, $target_keywords, $tone);

        // Make API request
        return $this->make_openai_request($prompt, 160); // 160 tokens for description
    }

    public function create_usage_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_ai_usage';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            usage_type varchar(50) NOT NULL,
            content_length int(11) NOT NULL,
            post_type varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            usage_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usage_type (usage_type),
            KEY usage_date (usage_date),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Build prompt for SEO title generation
     */
    private function build_title_prompt($content, $post_type, $target_keywords, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 500);
        
        $prompt = "You are an expert SEO copywriter. Generate a compelling SEO title based on the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content Preview: " . $content_excerpt . "\n";
        
        if (!empty($target_keywords)) {
            $prompt .= "Target Keywords: " . $target_keywords . "\n";
        }
        
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Keep it under 60 characters for optimal SEO\n";
        $prompt .= "- Make it compelling and click-worthy\n";
        $prompt .= "- Include target keywords naturally if provided\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Focus on the main value proposition\n\n";
        $prompt .= "Generate only the title, no explanations or quotes:";

        return $prompt;
    }

    /**
     * Build prompt for meta description generation
     */
    private function build_description_prompt($content, $post_type, $target_keywords, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 800);
        
        $prompt = "You are an expert SEO copywriter. Generate a compelling meta description based on the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content Preview: " . $content_excerpt . "\n";
        
        if (!empty($target_keywords)) {
            $prompt .= "Target Keywords: " . $target_keywords . "\n";
        }
        
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Keep it between 150-160 characters for optimal SEO\n";
        $prompt .= "- Make it compelling and informative\n";
        $prompt .= "- Include target keywords naturally if provided\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Include a clear call-to-action or value proposition\n";
        $prompt .= "- Summarize the main benefit or key takeaway\n\n";
        $prompt .= "Generate only the meta description, no explanations or quotes:";

        return $prompt;
    }

    /**
     * Extract content excerpt for AI processing
     */
    private function extract_content_excerpt($content, $max_length = 500)
    {
        // Remove HTML tags
        $content = wp_strip_all_tags($content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Trim to max length
        if (strlen($content) > $max_length) {
            $content = substr($content, 0, $max_length) . '...';
        }
        
        return trim($content);
    }

    /**
     * Make request to OpenAI API
     */
    private function make_openai_request($prompt, $max_tokens = null)
    {
        $max_tokens = $max_tokens ?: $this->max_tokens;
        
        $body = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => $this->temperature,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        ];

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 30,
            'method' => 'POST'
        ];

        $response = wp_remote_request('https://api.openai.com/v1/chat/completions', $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            $error_message = isset($data['error']['message']) ? 
                $data['error']['message'] : 
                __('Unknown API error', 'softworld-ai-seo');
            
            return new WP_Error('api_error', $error_message);
        }

        return trim($data['choices'][0]['message']['content']);
    }

    /**
     * AJAX handler for generating SEO title
     */
    public function ajax_generate_seo_title()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $target_keywords = sanitize_text_field($_POST['target_keywords'] ?? '');
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_seo_title($content, $post_type, $target_keywords, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Log the generation
        $this->log_ai_usage('title', strlen($content), $post_type);

        wp_send_json_success([
            'title' => $result,
            'character_count' => strlen($result)
        ]);
    }

    /**
     * AJAX handler for generating SEO meta description
     */
    public function ajax_generate_seo_description()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $target_keywords = sanitize_text_field($_POST['target_keywords'] ?? '');
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_seo_description($content, $post_type, $target_keywords, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Log the generation
        $this->log_ai_usage('description', strlen($content), $post_type);

        wp_send_json_success([
            'description' => $result,
            'character_count' => strlen($result)
        ]);
    }

    /**
     * AJAX handler for bulk SEO generation
     */
    public function ajax_generate_bulk_seo()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $post_ids = array_map('intval', $_POST['post_ids'] ?? []);
        $generate_titles = !empty($_POST['generate_titles']);
        $generate_descriptions = !empty($_POST['generate_descriptions']);
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($post_ids)) {
            wp_send_json_error(__('No posts selected', 'softworld-ai-seo'));
        }

        $results = [];
        $errors = [];

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            $content = $post->post_title . "\n\n" . $post->post_content;
            $post_result = ['id' => $post_id];

            // Generate title if requested
            if ($generate_titles) {
                $title_result = $this->generate_seo_title($content, $post->post_type, '', $tone);
                if (is_wp_error($title_result)) {
                    $errors[] = sprintf(__('Post %d title: %s', 'softworld-ai-seo'), $post_id, $title_result->get_error_message());
                } else {
                    $post_result['title'] = $title_result;
                    // Optionally auto-save
                    if (!empty($_POST['auto_save'])) {
                        update_post_meta($post_id, '_softworld_ai_seo_title', $title_result);
                    }
                }
            }

            // Generate description if requested
            if ($generate_descriptions) {
                $desc_result = $this->generate_seo_description($content, $post->post_type, '', $tone);
                if (is_wp_error($desc_result)) {
                    $errors[] = sprintf(__('Post %d description: %s', 'softworld-ai-seo'), $post_id, $desc_result->get_error_message());
                } else {
                    $post_result['description'] = $desc_result;
                    // Optionally auto-save
                    if (!empty($_POST['auto_save'])) {
                        update_post_meta($post_id, '_softworld_ai_seo_description', $desc_result);
                    }
                }
            }

            $results[] = $post_result;

            // Log bulk usage
            $this->log_ai_usage('bulk', strlen($content), $post->post_type);
        }

        wp_send_json_success([
            'results' => $results,
            'errors' => $errors,
            'processed' => count($results)
        ]);
    }

    /**
     * AJAX handler for testing OpenAI connection
     */
    public function ajax_test_openai_connection()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $test_prompt = "Generate a simple SEO title for a blog post about WordPress plugins. Keep it under 60 characters.";
        
        $result = $this->make_openai_request($test_prompt, 30);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'connected' => false
            ]);
        }

        wp_send_json_success([
            'message' => __('OpenAI connection successful!', 'softworld-ai-seo'),
            'connected' => true,
            'test_result' => $result
        ]);
    }

    /**
     * Log AI usage for analytics
     */
    private function log_ai_usage($type, $content_length, $post_type)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_ai_usage';
        
        // Create table if it doesn't exist
        $this->create_usage_table();

        $wpdb->insert(
            $table_name,
            [
                'usage_type' => $type,
                'content_length' => $content_length,
                'post_type' => $post_type,
                'user_id' => get_current_user_id(),
                'usage_date' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%d', '%s']
        );
    }

    /**
     * Get AI usage statistics
     */
    public function get_usage_stats($days = 30)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_ai_usage';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return [
                'total_generations' => 0,
                'titles_generated' => 0,
                'descriptions_generated' => 0,
                'bulk_generations' => 0,
                'daily_usage' => []
            ];
        }

        $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));

        $stats = [
            'total_generations' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_date >= %s", $date_from)
            ),
            'titles_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'title' AND usage_date >= %s", $date_from)
            ),
            'descriptions_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'description' AND usage_date >= %s", $date_from)
            ),
            'bulk_generations' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'bulk' AND usage_date >= %s", $date_from)
            ),
            // Add these lines to the get_usage_stats method:
            'faqs_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'faqs' AND usage_date >= %s", $date_from)
            ),
            'key_points_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'key_points' AND usage_date >= %s", $date_from)
            ),
        ];

        // Get daily usage for chart
        $daily_usage = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(usage_date) as date, COUNT(*) as count 
                 FROM $table_name 
                 WHERE usage_date >= %s 
                 GROUP BY DATE(usage_date) 
                 ORDER BY date ASC",
                $date_from
            ),
            ARRAY_A
        );

        $stats['daily_usage'] = $daily_usage;

        return $stats;
    }

    /**
     * Generate FAQs using OpenAI
     */
    public function generate_faqs($content, $post_type = 'post', $faq_count = 5, $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        $prompt = $this->build_faqs_prompt($content, $post_type, $faq_count, $tone);
        $result = $this->make_openai_request($prompt, 500);

        if (is_wp_error($result)) {
            return $result;
        }

        // Log the generation
        $this->log_ai_usage('faqs', strlen($content), $post_type);

        return $this->parse_faqs_response($result);
    }

    /**
     * Generate key points using OpenAI
     */
    public function generate_key_points($content, $post_type = 'post', $point_count = 5, $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        $prompt = $this->build_key_points_prompt($content, $post_type, $point_count, $tone);
        $result = $this->make_openai_request($prompt, 300);

        if (is_wp_error($result)) {
            return $result;
        }

        // Log the generation
        $this->log_ai_usage('key_points', strlen($content), $post_type);

        return $this->parse_key_points_response($result);
    }

    /**
     * Build prompt for FAQ generation
     */
    private function build_faqs_prompt($content, $post_type, $faq_count, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 1000);
        
        $prompt = "You are an expert content strategist. Generate frequently asked questions (FAQs) based on the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content: " . $content_excerpt . "\n";
        $prompt .= "Number of FAQs: " . $faq_count . "\n";
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Generate exactly " . $faq_count . " relevant questions and answers\n";
        $prompt .= "- Questions should be what readers would commonly ask about this topic\n";
        $prompt .= "- Answers should be informative but concise (2-3 sentences each)\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Focus on the most important aspects of the content\n\n";
        $prompt .= "Format your response as:\n";
        $prompt .= "Q1: [Question]\nA1: [Answer]\n\nQ2: [Question]\nA2: [Answer]\n\n";
        $prompt .= "Continue this pattern for all questions.\n\n";
        $prompt .= "Generate the FAQs now:";

        return $prompt;
    }

    /**
     * Build prompt for key points generation
     */
    private function build_key_points_prompt($content, $post_type, $point_count, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 1000);
        
        $prompt = "You are an expert content strategist. Extract the most important key points from the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content: " . $content_excerpt . "\n";
        $prompt .= "Number of Points: " . $point_count . "\n";
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Extract exactly " . $point_count . " key points\n";
        $prompt .= "- Each point should be a clear, actionable takeaway\n";
        $prompt .= "- Keep each point concise (1-2 sentences)\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Focus on the most valuable insights\n";
        $prompt .= "- Prioritize practical and useful information\n\n";
        $prompt .= "Format your response as a numbered list:\n";
        $prompt .= "1. [Key Point]\n2. [Key Point]\n3. [Key Point]\n\n";
        $prompt .= "Generate the key points now:";

        return $prompt;
    }

    /**
     * Parse FAQs response from AI
     */
    private function parse_faqs_response($response)
    {
        $faqs = [];
        $lines = explode("\n", trim($response));
        $current_question = '';
        $current_answer = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line starts with Q followed by number and colon
            if (preg_match('/^Q\d+:\s*(.+)/', $line, $matches)) {
                // If we have a previous FAQ, save it
                if (!empty($current_question) && !empty($current_answer)) {
                    $faqs[] = [
                        'question' => $current_question,
                        'answer' => $current_answer
                    ];
                }
                $current_question = trim($matches[1]);
                $current_answer = '';
            }
            // Check if line starts with A followed by number and colon
            elseif (preg_match('/^A\d+:\s*(.+)/', $line, $matches)) {
                $current_answer = trim($matches[1]);
            }
            // If we're building an answer, continue adding to it
            elseif (!empty($current_question) && !empty($current_answer)) {
                $current_answer .= ' ' . $line;
            }
        }
        
        // Add the last FAQ if exists
        if (!empty($current_question) && !empty($current_answer)) {
            $faqs[] = [
                'question' => $current_question,
                'answer' => $current_answer
            ];
        }
        
        return $faqs;
    }

    /**
     * Parse key points response from AI
     */
    private function parse_key_points_response($response)
    {
        $points = [];
        $lines = explode("\n", trim($response));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line starts with number and period/dot
            if (preg_match('/^\d+\.\s*(.+)/', $line, $matches)) {
                $points[] = trim($matches[1]);
            }
            // Also handle bullet points
            elseif (preg_match('/^[-•]\s*(.+)/', $line, $matches)) {
                $points[] = trim($matches[1]);
            }
        }
        
        return $points;
    }

    /**
     * AJAX handler for generating FAQs
     */
    public function ajax_generate_faqs()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $faq_count = intval($_POST['faq_count'] ?? 5);
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_faqs($content, $post_type, $faq_count, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'faqs' => $result,
            'count' => count($result)
        ]);
    }

    /**
     * AJAX handler for generating key points
     */
    public function ajax_generate_key_points()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $point_count = intval($_POST['point_count'] ?? 5);
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_key_points($content, $post_type, $point_count, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'key_points' => $result,
            'count' => count($result)
        ]);
    }
}