<?php
/**
 * Schema Manager Class
 * 
 * Handles all schema markup functionality for the SEO plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Schema_Manager
{
    private $plugin_name;

    public function __construct($plugin_name = 'softworld-ai-seo')
    {
        $this->plugin_name = $plugin_name;
        
        // Hook into WordPress
        add_action('wp_head', array($this, 'output_schema_markup'), 5);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta_box'));
        
        // Initialize settings
        add_action('admin_init', array($this, 'register_schema_settings'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook === 'toplevel_page_softworld-ai-seo') {
            wp_enqueue_media();
            wp_enqueue_script('softworld-schema-admin', plugin_dir_url(__FILE__) . '../assets/js/schema-admin.js', array('jquery'), '1.0.0', true);
        }
    }

    /**
     * Register schema settings
     */
    public function register_schema_settings()
    {
        // Global Schema Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_global_settings', array(
            'sanitize_callback' => array($this, 'sanitize_global_settings')
        ));

        // Knowledge Graph Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_knowledge_graph', array(
            'sanitize_callback' => array($this, 'sanitize_knowledge_graph')
        ));
        
        // Schema Types Settings
        register_setting('softworld_schema_settings_group', 'softworld_schema_types_settings', array(
            'sanitize_callback' => array($this, 'sanitize_schema_types_settings')
        ));
    }

    /**
     * Sanitize global settings
     */
    public function sanitize_global_settings($input)
    {
        $sanitized = array();
        
        if (isset($input['enable_schema'])) {
            $sanitized['enable_schema'] = (bool) $input['enable_schema'];
        }
        
        if (isset($input['default_article_type'])) {
            $sanitized['default_article_type'] = sanitize_text_field($input['default_article_type']);
        }
        
        if (isset($input['breadcrumb_schema'])) {
            $sanitized['breadcrumb_schema'] = (bool) $input['breadcrumb_schema'];
        }
        
        if (isset($input['sitelinks_searchbox'])) {
            $sanitized['sitelinks_searchbox'] = (bool) $input['sitelinks_searchbox'];
        }
        
        // Sanitize schema post types
        if (isset($input['schema_post_types'])) {
            $sanitized['schema_post_types'] = array();
            $post_types = get_post_types(array('public' => true), 'names');
            
            foreach ($input['schema_post_types'] as $post_type) {
                if (array_key_exists($post_type, $post_types)) {
                    $sanitized['schema_post_types'][] = sanitize_text_field($post_type);
                }
            }
        }
        
        // Sanitize default page schema
        if (isset($input['default_page_schema'])) {
            $allowed_page_schemas = array('WebPage', 'AboutPage', 'ContactPage', 'FAQPage');
            if (in_array($input['default_page_schema'], $allowed_page_schemas)) {
                $sanitized['default_page_schema'] = sanitize_text_field($input['default_page_schema']);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize knowledge graph settings
     */
    public function sanitize_knowledge_graph($input)
    {
        $sanitized = array();
        
        if (isset($input['entity_type'])) {
            $sanitized['entity_type'] = sanitize_text_field($input['entity_type']);
        }
        
        if (isset($input['organization_name'])) {
            $sanitized['organization_name'] = sanitize_text_field($input['organization_name']);
        }
        
        if (isset($input['organization_logo'])) {
            $sanitized['organization_logo'] = esc_url_raw($input['organization_logo']);
        }
        
        if (isset($input['person_name'])) {
            $sanitized['person_name'] = sanitize_text_field($input['person_name']);
        }
        
        if (isset($input['person_image'])) {
            $sanitized['person_image'] = esc_url_raw($input['person_image']);
        }
        
        if (isset($input['social_profiles'])) {
            $sanitized['social_profiles'] = array();
            foreach ($input['social_profiles'] as $profile) {
                if (!empty($profile)) {
                    $sanitized['social_profiles'][] = esc_url_raw($profile);
                }
            }
        }

        return $sanitized;
    }
    
    /**
     * Sanitize schema types settings
     */
    public function sanitize_schema_types_settings($input)
    {
        $sanitized = array();
        
        if (isset($input['enable_product_schema'])) {
            $sanitized['enable_product_schema'] = (bool) $input['enable_product_schema'];
        }
        
        if (isset($input['enable_faq_schema'])) {
            $sanitized['enable_faq_schema'] = (bool) $input['enable_faq_schema'];
        }
        
        if (isset($input['enable_howto_schema'])) {
            $sanitized['enable_howto_schema'] = (bool) $input['enable_howto_schema'];
        }

        return $sanitized;
    }

    /**
     * Add schema meta box to post/page editor
     */
    public function add_schema_meta_box()
    {
        $global_settings = get_option('softworld_schema_global_settings', array());
        $selected_post_types = isset($global_settings['schema_post_types']) ? $global_settings['schema_post_types'] : array('post', 'page');
        
        foreach ($selected_post_types as $post_type) {
            add_meta_box(
                'softworld_schema_meta_box',
                'Schema Settings',
                array($this, 'render_schema_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render schema meta box
     */
    public function render_schema_meta_box($post)
    {
        wp_nonce_field('softworld_schema_meta_box', 'softworld_schema_nonce');
        
        $schema_type = get_post_meta($post->ID, '_softworld_schema_type', true);
        $schema_data = get_post_meta($post->ID, '_softworld_schema_data', true);
        
        if (!$schema_data) {
            $schema_data = array();
        }
        
        // Get schema types settings
        $schema_types_settings = get_option('softworld_schema_types_settings', array());
        
        ?>
        <div class="softworld-schema-meta-box">
            <p>
                <label for="softworld_schema_type"><strong>Schema Type:</strong></label>
                <select id="softworld_schema_type" name="softworld_schema_type" style="width: 100%;">
                    <option value="">None</option>
                    <option value="Article" <?php selected($schema_type, 'Article'); ?>>Article</option>
                    
                    <?php if (!empty($schema_types_settings['enable_product_schema'])): ?>
                    <option value="Product" <?php selected($schema_type, 'Product'); ?>>Product</option>
                    <?php endif; ?>
                    
                    <?php if (!empty($schema_types_settings['enable_faq_schema'])): ?>
                    <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQ</option>
                    <?php endif; ?>
                    
                    <?php if (!empty($schema_types_settings['enable_howto_schema'])): ?>
                    <option value="HowTo" <?php selected($schema_type, 'HowTo'); ?>>How-To</option>
                    <?php endif; ?>
                    
                    <option value="Review" <?php selected($schema_type, 'Review'); ?>>Review</option>
                    <option value="Recipe" <?php selected($schema_type, 'Recipe'); ?>>Recipe</option>
                </select>
            </p>
            
            <div id="schema_additional_fields" style="<?php echo empty($schema_type) ? 'display:none;' : ''; ?>">
                <!-- Product Schema Fields -->
                <div class="schema-fields product-fields" style="<?php echo $schema_type !== 'Product' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="product_price"><strong>Price:</strong></label>
                        <input type="text" id="product_price" name="schema_data[price]" value="<?php echo esc_attr(isset($schema_data['price']) ? $schema_data['price'] : ''); ?>" style="width: 100%;">
                    </p>
                    <p>
                        <label for="product_currency"><strong>Currency:</strong></label>
                        <input type="text" id="product_currency" name="schema_data[currency]" value="<?php echo esc_attr(isset($schema_data['currency']) ? $schema_data['currency'] : 'USD'); ?>" style="width: 100%;">
                    </p>
                    <p>
                        <label for="product_availability"><strong>Availability:</strong></label>
                        <select id="product_availability" name="schema_data[availability]" style="width: 100%;">
                            <option value="InStock" <?php selected(isset($schema_data['availability']) ? $schema_data['availability'] : '', 'InStock'); ?>>In Stock</option>
                            <option value="OutOfStock" <?php selected(isset($schema_data['availability']) ? $schema_data['availability'] : '', 'OutOfStock'); ?>>Out of Stock</option>
                            <option value="PreOrder" <?php selected(isset($schema_data['availability']) ? $schema_data['availability'] : '', 'PreOrder'); ?>>Pre Order</option>
                        </select>
                    </p>
                </div>
                
                <!-- FAQ Schema Fields -->
                <div class="schema-fields faq-fields" style="<?php echo $schema_type !== 'FAQPage' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="faq_questions"><strong>FAQ Questions (one per line):</strong></label>
                        <textarea id="faq_questions" name="schema_data[faq_questions]" style="width: 100%; height: 100px;"><?php echo esc_textarea(isset($schema_data['faq_questions']) ? $schema_data['faq_questions'] : ''); ?></textarea>
                    </p>
                </div>
                
                <!-- How-To Schema Fields -->
                <div class="schema-fields howto-fields" style="<?php echo $schema_type !== 'HowTo' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="howto_steps"><strong>Steps (one per line):</strong></label>
                        <textarea id="howto_steps" name="schema_data[howto_steps]" style="width: 100%; height: 100px;"><?php echo esc_textarea(isset($schema_data['howto_steps']) ? $schema_data['howto_steps'] : ''); ?></textarea>
                    </p>
                    <p>
                        <label for="howto_supplies"><strong>Supplies (comma separated):</strong></label>
                        <input type="text" id="howto_supplies" name="schema_data[howto_supplies]" value="<?php echo esc_attr(isset($schema_data['howto_supplies']) ? $schema_data['howto_supplies'] : ''); ?>" style="width: 100%;">
                    </p>
                </div>
                
                <!-- Review Schema Fields -->
                <div class="schema-fields review-fields" style="<?php echo $schema_type !== 'Review' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="review_rating"><strong>Rating (1-5):</strong></label>
                        <input type="number" id="review_rating" name="schema_data[rating]" min="1" max="5" step="0.1" value="<?php echo esc_attr(isset($schema_data['rating']) ? $schema_data['rating'] : ''); ?>" style="width: 100%;">
                    </p>
                    <p>
                        <label for="review_item_name"><strong>Item Being Reviewed:</strong></label>
                        <input type="text" id="review_item_name" name="schema_data[item_name]" value="<?php echo esc_attr(isset($schema_data['item_name']) ? $schema_data['item_name'] : ''); ?>" style="width: 100%;">
                    </p>
                </div>
                
                <!-- Recipe Schema Fields -->
                <div class="schema-fields recipe-fields" style="<?php echo $schema_type !== 'Recipe' ? 'display:none;' : ''; ?>">
                    <p>
                        <label for="recipe_prep_time"><strong>Prep Time (minutes):</strong></label>
                        <input type="number" id="recipe_prep_time" name="schema_data[prep_time]" value="<?php echo esc_attr(isset($schema_data['prep_time']) ? $schema_data['prep_time'] : ''); ?>" style="width: 100%;">
                    </p>
                    <p>
                        <label for="recipe_cook_time"><strong>Cook Time (minutes):</strong></label>
                        <input type="number" id="recipe_cook_time" name="schema_data[cook_time]" value="<?php echo esc_attr(isset($schema_data['cook_time']) ? $schema_data['cook_time'] : ''); ?>" style="width: 100%;">
                    </p>
                    <p>
                        <label for="recipe_servings"><strong>Servings:</strong></label>
                        <input type="number" id="recipe_servings" name="schema_data[servings]" value="<?php echo esc_attr(isset($schema_data['servings']) ? $schema_data['servings'] : ''); ?>" style="width: 100%;">
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var schemaSelect = document.getElementById('softworld_schema_type');
            var additionalFields = document.getElementById('schema_additional_fields');
            var allFields = additionalFields.querySelectorAll('.schema-fields');
            
            schemaSelect.addEventListener('change', function() {
                var selectedType = this.value;
                
                if (selectedType) {
                    additionalFields.style.display = 'block';
                } else {
                    additionalFields.style.display = 'none';
                }
                
                // Hide all fields first
                allFields.forEach(function(field) {
                    field.style.display = 'none';
                });
                
                // Show relevant fields
                if (selectedType === 'Product') {
                    additionalFields.querySelector('.product-fields').style.display = 'block';
                } else if (selectedType === 'FAQPage') {
                    additionalFields.querySelector('.faq-fields').style.display = 'block';
                } else if (selectedType === 'HowTo') {
                    additionalFields.querySelector('.howto-fields').style.display = 'block';
                } else if (selectedType === 'Review') {
                    additionalFields.querySelector('.review-fields').style.display = 'block';
                } else if (selectedType === 'Recipe') {
                    additionalFields.querySelector('.recipe-fields').style.display = 'block';
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save schema meta box data
     */
    public function save_schema_meta_box($post_id)
    {
        // Check nonce
        if (!isset($_POST['softworld_schema_nonce']) || !wp_verify_nonce($_POST['softworld_schema_nonce'], 'softworld_schema_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save schema type
        if (isset($_POST['softworld_schema_type'])) {
            update_post_meta($post_id, '_softworld_schema_type', sanitize_text_field($_POST['softworld_schema_type']));
        }
        
        // Save schema data
        if (isset($_POST['schema_data'])) {
            $schema_data = array();
            foreach ($_POST['schema_data'] as $key => $value) {
                $schema_data[sanitize_key($key)] = sanitize_text_field($value);
            }
            update_post_meta($post_id, '_softworld_schema_data', $schema_data);
        }
    }

    /**
     * Output schema markup in head
     */
    public function output_schema_markup()
    {
        $global_settings = get_option('softworld_schema_global_settings', array());
        
        // Check if schema is enabled
        if (empty($global_settings['enable_schema'])) {
            return;
        }
        
        // Check if current post type supports schema
        if (is_singular()) {
            global $post;
            $selected_post_types = isset($global_settings['schema_post_types']) ? $global_settings['schema_post_types'] : array('post', 'page');
            
            if (!in_array($post->post_type, $selected_post_types)) {
                return;
            }
        }
        
        $schema_data = array();
        
        // Add Knowledge Graph
        $knowledge_graph = $this->get_knowledge_graph_schema();
        if ($knowledge_graph) {
            $schema_data[] = $knowledge_graph;
        }
        
        // Add Website Schema
        $website_schema = $this->get_website_schema();
        if ($website_schema) {
            $schema_data[] = $website_schema;
        }
        
        // Add page-specific schema
        if (is_singular()) {
            $post_schema = $this->get_post_schema();
            if ($post_schema) {
                $schema_data[] = $post_schema;
            }
        }
        
        // Add breadcrumb schema
        if (!empty($global_settings['breadcrumb_schema']) && !is_front_page()) {
            $breadcrumb_schema = $this->get_breadcrumb_schema();
            if ($breadcrumb_schema) {
                $schema_data[] = $breadcrumb_schema;
            }
        }
        
        if (!empty($schema_data)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n" . '</script>' . "\n";
        }
    }

    /**
     * Get Knowledge Graph schema
     */
    private function get_knowledge_graph_schema()
    {
        $kg_settings = get_option('softworld_schema_knowledge_graph', array());
        
        if (empty($kg_settings['entity_type'])) {
            return null;
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $kg_settings['entity_type'],
            'url' => home_url(),
        );
        
        if ($kg_settings['entity_type'] === 'Organization') {
            if (!empty($kg_settings['organization_name'])) {
                $schema['name'] = $kg_settings['organization_name'];
            }
            if (!empty($kg_settings['organization_logo'])) {
                $schema['logo'] = $kg_settings['organization_logo'];
            }
        } elseif ($kg_settings['entity_type'] === 'Person') {
            if (!empty($kg_settings['person_name'])) {
                $schema['name'] = $kg_settings['person_name'];
            }
            if (!empty($kg_settings['person_image'])) {
                $schema['image'] = $kg_settings['person_image'];
            }
        }
        
        if (!empty($kg_settings['social_profiles'])) {
            $schema['sameAs'] = $kg_settings['social_profiles'];
        }
        
        return $schema;
    }

    /**
     * Get Website schema
     */
    private function get_website_schema()
    {
        $global_settings = get_option('softworld_schema_global_settings', array());
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
        );
        
        if (!empty($global_settings['sitelinks_searchbox'])) {
            $schema['potentialAction'] = array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            );
        }
        
        return $schema;
    }

    /**
     * Get post-specific schema
     */
    private function get_post_schema()
    {
        global $post;
        
        if (!$post) {
            return null;
        }
        
        $schema_type = get_post_meta($post->ID, '_softworld_schema_type', true);
        $schema_data = get_post_meta($post->ID, '_softworld_schema_data', true);
        
        // Get global settings for defaults
        $global_settings = get_option('softworld_schema_global_settings', array());
        
        if (empty($schema_type)) {
            // Set default based on post type
            if ($post->post_type === 'post') {
                $schema_type = !empty($global_settings['default_article_type']) ? 
                    $global_settings['default_article_type'] : 'Article';
            } elseif ($post->post_type === 'page') {
                $schema_type = !empty($global_settings['default_page_schema']) ? 
                    $global_settings['default_page_schema'] : 'WebPage';
            } else {
                return null;
            }
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'headline' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
        );
        
        // Add dates for content that typically has them
        if (in_array($schema_type, array('Article', 'BlogPosting', 'NewsArticle', 'TechArticle'))) {
            $schema['datePublished'] = get_the_date('c', $post->ID);
            $schema['dateModified'] = get_the_modified_date('c', $post->ID);
        }
        
        // Add description
        $description = get_post_meta($post->ID, '_softworld_ai_seo_description', true);
        if (empty($description)) {
            $description = wp_trim_words(strip_tags($post->post_content), 30);
        }
        if ($description) {
            $schema['description'] = $description;
        }
        
        // Add author
        $author_id = $post->post_author;
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $author_id),
            'url' => get_author_posts_url($author_id)
        );
        
        // Add featured image
        if (has_post_thumbnail($post->ID)) {
            $image_data = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
            if ($image_data) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2]
                );
            }
        }
        
        // Add publisher
        $kg_settings = get_option('softworld_schema_knowledge_graph', array());
        if (!empty($kg_settings['entity_type']) && !empty($kg_settings['organization_name'])) {
            $schema['publisher'] = array(
                '@type' => $kg_settings['entity_type'],
                'name' => $kg_settings['organization_name']
            );
            if (!empty($kg_settings['organization_logo'])) {
                $schema['publisher']['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $kg_settings['organization_logo']
                );
            }
        }
        
        // Add schema-specific data
        switch ($schema_type) {
            case 'Product':
                if (!empty($schema_data['price'])) {
                    $schema['offers'] = array(
                        '@type' => 'Offer',
                        'price' => $schema_data['price'],
                        'priceCurrency' => !empty($schema_data['currency']) ? $schema_data['currency'] : 'USD',
                        'availability' => 'https://schema.org/' . (!empty($schema_data['availability']) ? $schema_data['availability'] : 'InStock')
                    );
                }
                break;
                
            case 'FAQPage':
                if (!empty($schema_data['faq_questions'])) {
                    $questions = explode("\n", $schema_data['faq_questions']);
                    $mainEntity = array();
                    
                    foreach ($questions as $question) {
                        if (!empty(trim($question))) {
                            $mainEntity[] = array(
                                '@type' => 'Question',
                                'name' => trim($question),
                                'acceptedAnswer' => array(
                                    '@type' => 'Answer',
                                    'text' => '' // This would need to be populated with answers
                                )
                            );
                        }
                    }
                    
                    if (!empty($mainEntity)) {
                        $schema['mainEntity'] = $mainEntity;
                    }
                }
                break;
                
            case 'HowTo':
                if (!empty($schema_data['howto_steps'])) {
                    $steps = explode("\n", $schema_data['howto_steps']);
                    $stepObjects = array();
                    $stepCount = 1;
                    
                    foreach ($steps as $step) {
                        if (!empty(trim($step))) {
                            $stepObjects[] = array(
                                '@type' => 'HowToStep',
                                'position' => $stepCount++,
                                'text' => trim($step)
                            );
                        }
                    }
                    
                    if (!empty($stepObjects)) {
                        $schema['step'] = $stepObjects;
                    }
                }
                
                if (!empty($schema_data['howto_supplies'])) {
                    $supplies = explode(",", $schema_data['howto_supplies']);
                    $supplyObjects = array();
                    
                    foreach ($supplies as $supply) {
                        if (!empty(trim($supply))) {
                            $supplyObjects[] = array(
                                '@type' => 'HowToSupply',
                                'name' => trim($supply)
                            );
                        }
                    }
                    
                    if (!empty($supplyObjects)) {
                        $schema['supply'] = $supplyObjects;
                    }
                }
                break;
                
            case 'Review':
                if (!empty($schema_data['rating'])) {
                    $schema['reviewRating'] = array(
                        '@type' => 'Rating',
                        'ratingValue' => $schema_data['rating'],
                        'bestRating' => '5',
                        'worstRating' => '1'
                    );
                }
                if (!empty($schema_data['item_name'])) {
                    $schema['itemReviewed'] = array(
                        '@type' => 'Thing',
                        'name' => $schema_data['item_name']
                    );
                }
                break;
                
            case 'Recipe':
                if (!empty($schema_data['prep_time'])) {
                    $schema['prepTime'] = 'PT' . $schema_data['prep_time'] . 'M';
                }
                if (!empty($schema_data['cook_time'])) {
                    $schema['cookTime'] = 'PT' . $schema_data['cook_time'] . 'M';
                }
                if (!empty($schema_data['servings'])) {
                    $schema['recipeYield'] = $schema_data['servings'];
                }
                break;
        }
        
        return $schema;
    }

    /**
     * Get breadcrumb schema
     */
    private function get_breadcrumb_schema()
    {
        if (is_front_page()) {
            return null;
        }
        
        $breadcrumbs = array();
        $position = 1;
        
        // Home
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => get_bloginfo('name'),
            'item' => home_url()
        );
        
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term) {
                $breadcrumbs[] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $term->name,
                    'item' => get_term_link($term)
                );
            }
        } elseif (is_single()) {
            global $post;
            
            // Add category for posts
            if ($post->post_type === 'post') {
                $categories = get_the_category($post->ID);
                if (!empty($categories)) {
                    $category = $categories[0];
                    $breadcrumbs[] = array(
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $category->name,
                        'item' => get_category_link($category->term_id)
                    );
                }
            }
            
            // Add current post
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($post->ID),
                'item' => get_permalink($post->ID)
            );
        } elseif (is_page()) {
            global $post;
            
            // Add parent pages
            if ($post->post_parent) {
                $parents = array();
                $parent_id = $post->post_parent;
                while ($parent_id) {
                    $page = get_page($parent_id);
                    $parents[] = $page;
                    $parent_id = $page->post_parent;
                }
                $parents = array_reverse($parents);
                
                foreach ($parents as $parent) {
                    $breadcrumbs[] = array(
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => get_the_title($parent->ID),
                        'item' => get_permalink($parent->ID)
                    );
                }
            }
            
            // Add current page
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($post->ID),
                'item' => get_permalink($post->ID)
            );
        }
        
        if (count($breadcrumbs) > 1) {
            return array(
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs
            );
        }
        
        return null;
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            // WordPress handles the option saving automatically
            echo '<div class="updated notice"><p>Schema settings saved successfully.</p></div>';
        }
        
        // Get current settings
        $global_settings = get_option('softworld_schema_global_settings', array(
            'enable_schema' => true,
            'default_article_type' => 'Article',
            'breadcrumb_schema' => true,
            'sitelinks_searchbox' => true,
            'schema_post_types' => array('post', 'page'),
            'default_page_schema' => 'WebPage'
        ));
        
        $kg_settings = get_option('softworld_schema_knowledge_graph', array(
            'entity_type' => 'Organization',
            'organization_name' => get_bloginfo('name'),
            'organization_logo' => '',
            'person_name' => '',
            'person_image' => '',
            'social_profiles' => array()
        ));
        
        $schema_types_settings = get_option('softworld_schema_types_settings', array(
            'enable_product_schema' => false,
            'enable_faq_schema' => false,
            'enable_howto_schema' => false
        ));
        
        include plugin_dir_path(__FILE__) . '../templates/schema-settings-template.php';
    }
}
