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
     * Add schema meta box to post/page editor
     */
    public function add_schema_meta_box()
    {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
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
        
        ?>
        <div class="softworld-schema-meta-box">
            <p>
                <label for="softworld_schema_type"><strong>Schema Type:</strong></label>
                <select id="softworld_schema_type" name="softworld_schema_type" style="width: 100%;">
                    <option value="">None</option>
                    <option value="Article" <?php selected($schema_type, 'Article'); ?>>Article</option>
                    <option value="Product" <?php selected($schema_type, 'Product'); ?>>Product</option>
                    <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQ</option>
                    <option value="HowTo" <?php selected($schema_type, 'HowTo'); ?>>How-To</option>
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
        
        if (empty($schema_type)) {
            // Default to Article for posts
            if ($post->post_type === 'post') {
                $schema_type = 'Article';
            } else {
                return null;
            }
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'headline' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
        );
        
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
            'sitelinks_searchbox' => true
        ));
        
        $kg_settings = get_option('softworld_schema_knowledge_graph', array(
            'entity_type' => 'Organization',
            'organization_name' => get_bloginfo('name'),
            'organization_logo' => '',
            'person_name' => '',
            'person_image' => '',
            'social_profiles' => array()
        ));
        
        include plugin_dir_path(__FILE__) . '../templates/schema-settings-template.php';
    }
}