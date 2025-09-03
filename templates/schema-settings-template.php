<?php
/**
 * Schema Settings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize settings
$global_settings = get_option('softworld_schema_global_settings', array());
$kg_settings = get_option('softworld_schema_knowledge_graph', array());
$schema_types_settings = get_option('softworld_schema_types_settings', array());
$custom_schema_types = get_option('softworld_schema_custom_types', array());
?>

<div class="wrap">
    <h1>Schema Settings</h1>
    <p>Configure structured data markup to help search engines better understand your content.</p>

    <form method="post" action="options.php">
        <?php
        settings_fields('softworld_schema_settings_group');
        ?>

        <div class="softworld-schema-container">
            <!-- Global Schema Settings -->
            <div class="softworld-schema-section">
                <h2>Global Schema Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Schema Markup</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_global_settings[enable_schema]" value="1"
                                    <?php checked(!empty($global_settings['enable_schema'])); ?>>
                                <span class="checkmark"></span>
                                Enable structured data markup
                            </label>
                            <p class="description">Enable JSON-LD structured data markup across your website.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Default Article Type</th>
                        <td>
                            <select name="softworld_schema_global_settings[default_article_type]" class="regular-text">
                                <option value="Article" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'Article'); ?>>Article</option>
                                <option value="BlogPosting" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'BlogPosting'); ?>>Blog Posting
                                </option>
                                <option value="NewsArticle" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'NewsArticle'); ?>>News Article
                                </option>
                                <option value="TechArticle" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'TechArticle'); ?>>Tech Article
                                </option>
                            </select>
                            <p class="description">Default schema type for posts when no specific type is set.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Breadcrumb Schema</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_global_settings[breadcrumb_schema]"
                                    value="1" <?php checked(!empty($global_settings['breadcrumb_schema'])); ?>>
                                <span class="checkmark"></span>
                                Enable breadcrumb structured data
                            </label>
                            <p class="description">Automatically add breadcrumb schema to help with navigation
                                breadcrumbs.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Sitelinks Searchbox</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_global_settings[sitelinks_searchbox]"
                                    value="1" <?php checked(!empty($global_settings['sitelinks_searchbox'])); ?>>
                                <span class="checkmark"></span>
                                Enable sitelinks searchbox
                            </label>
                            <p class="description">Add search functionality to your Google search result snippet.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable Custom Schema</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_global_settings[enable_custom_schema]" value="1"
                                    <?php checked(!empty($global_settings['enable_custom_schema'])); ?>>
                                <span class="checkmark"></span>
                                Enable custom schema markup
                            </label>
                            <p class="description">Allow creation and use of custom schema types.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Knowledge Graph Settings -->
            <div class="softworld-schema-section">
                <h2>Knowledge Graph Settings</h2>
                <p class="description">Configure your organization or personal brand information for search engines.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">Entity Type</th>
                        <td>
                            <label>
                                <input type="radio" name="softworld_schema_knowledge_graph[entity_type]"
                                    value="Organization" <?php checked(isset($kg_settings['entity_type']) ? $kg_settings['entity_type'] : 'Organization', 'Organization'); ?>>
                                Organization
                            </label><br>
                            <label>
                                <input type="radio" name="softworld_schema_knowledge_graph[entity_type]" value="Person"
                                    <?php checked(isset($kg_settings['entity_type']) ? $kg_settings['entity_type'] : 'Organization', 'Person'); ?>>
                                Person
                            </label>
                            <p class="description">Choose whether this website represents an organization or a person.
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Organization Fields -->
                <div id="organization_fields"
                    style="<?php echo (isset($kg_settings['entity_type']) && $kg_settings['entity_type'] !== 'Organization') ? 'display:none;' : ''; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Organization Name</th>
                            <td>
                                <input type="text" name="softworld_schema_knowledge_graph[organization_name]"
                                    value="<?php echo esc_attr(isset($kg_settings['organization_name']) ? $kg_settings['organization_name'] : ''); ?>"
                                    class="regular-text">
                                <p class="description">Your company or organization name.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Organization Logo</th>
                            <td>
                                <input type="url" name="softworld_schema_knowledge_graph[organization_logo]"
                                    value="<?php echo esc_attr(isset($kg_settings['organization_logo']) ? $kg_settings['organization_logo'] : ''); ?>"
                                    class="regular-text" id="organization_logo_input">
                                <button type="button" class="button" onclick="openMediaLibrary('organization_logo')">
                                    Select Logo
                                </button>
                                <button type="button" class="button remove-media-button" onclick="removeMedia('organization_logo')"
                                    style="<?php echo empty($kg_settings['organization_logo']) ? 'display:none;' : ''; ?>">
                                    Remove
                                </button>
                                <p class="description">URL to your organization's logo image (recommended: square
                                    format, at least 112x112px).</p>
                                <?php if (!empty($kg_settings['organization_logo'])): ?>
                                    <div class="image-preview" style="margin-top: 10px;">
                                        <img src="<?php echo esc_url($kg_settings['organization_logo']); ?>"
                                            style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Person Fields -->
                <div id="person_fields"
                    style="<?php echo (isset($kg_settings['entity_type']) && $kg_settings['entity_type'] !== 'Person') ? 'display:none;' : ''; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Person Name</th>
                            <td>
                                <input type="text" name="softworld_schema_knowledge_graph[person_name]"
                                    value="<?php echo esc_attr(isset($kg_settings['person_name']) ? $kg_settings['person_name'] : ''); ?>"
                                    class="regular-text">
                                <p class="description">Full name of the person this website represents.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Person Image</th>
                            <td>
                                <input type="url" name="softworld_schema_knowledge_graph[person_image]"
                                    value="<?php echo esc_attr(isset($kg_settings['person_image']) ? $kg_settings['person_image'] : ''); ?>"
                                    class="regular-text" id="person_image_input">
                                <button type="button" class="button" onclick="openMediaLibrary('person_image')">
                                    Select Image
                                </button>
                                <button type="button" class="button remove-media-button" onclick="removeMedia('person_image')"
                                    style="<?php echo empty($kg_settings['person_image']) ? 'display:none;' : ''; ?>">
                                    Remove
                                </button>
                                <p class="description">URL to a profile photo or headshot.</p>
                                <?php if (!empty($kg_settings['person_image'])): ?>
                                    <div class="image-preview" style="margin-top: 10px;">
                                        <img src="<?php echo esc_url($kg_settings['person_image']); ?>"
                                            style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Social Profiles -->
                <table class="form-table">
                    <tr>
                        <th scope="row">Social Profiles</th>
                        <td>
                            <div id="social_profiles_container">
                                <?php
                                $social_profiles = !empty($kg_settings['social_profiles']) ? $kg_settings['social_profiles'] : array('');
                                foreach ($social_profiles as $index => $profile):
                                    ?>
                                    <div class="social-profile-row" style="margin-bottom: 10px;">
                                        <input type="url" name="softworld_schema_knowledge_graph[social_profiles][]"
                                            value="<?php echo esc_attr($profile); ?>"
                                            placeholder="https://www.facebook.com/yourpage" class="regular-text">
                                        <button type="button" class="button remove-social-profile"
                                            onclick="removeSocialProfile(this)"
                                            style="<?php echo $index === 0 ? 'display:none;' : ''; ?>">
                                            Remove
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button" onclick="addSocialProfile()">Add Social
                                Profile</button>
                            <p class="description">Add URLs to your social media profiles (Facebook, Twitter, LinkedIn,
                                Instagram, etc.).</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Schema Types Configuration -->
            <div class="softworld-schema-section">
                <h2>Schema Types Configuration</h2>
                <p class="description">Configure which schema types to enable and their default settings.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Product Schema</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_types_settings[enable_product_schema]" value="1"
                                    <?php checked(!empty($schema_types_settings['enable_product_schema'])); ?>>
                                <span class="checkmark"></span>
                                Enable Product schema markup
                            </label>
                            <p class="description">Enable Product schema for WooCommerce products or other product pages.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Enable FAQ Schema</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_types_settings[enable_faq_schema]" value="1"
                                    <?php checked(!empty($schema_types_settings['enable_faq_schema'])); ?>>
                                <span class="checkmark"></span>
                                Enable FAQ schema markup
                            </label>
                            <p class="description">Enable FAQ schema for pages with frequently asked questions.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Enable How-To Schema</th>
                        <td>
                            <label class="softworld-checkbox-label">
                                <input type="checkbox" name="softworld_schema_types_settings[enable_howto_schema]" value="1"
                                    <?php checked(!empty($schema_types_settings['enable_howto_schema'])); ?>>
                                <span class="checkmark"></span>
                                Enable How-To schema markup
                            </label>
                            <p class="description">Enable How-To schema for tutorial and instructional content.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Individual Content Settings -->
            <div class="softworld-schema-section">
                <h2>Individual Content Settings</h2>
                <p class="description">Configure how schema is applied to individual content types.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">Post Types with Schema Support</th>
                        <td>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            $selected_post_types = isset($global_settings['schema_post_types']) ? $global_settings['schema_post_types'] : array('post', 'page');
                            
                            foreach ($post_types as $post_type) :
                                if ($post_type->name === 'attachment') continue;
                            ?>
                                <label class="softworld-checkbox-label" style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="softworld_schema_global_settings[schema_post_types][]" 
                                        value="<?php echo esc_attr($post_type->name); ?>"
                                        <?php checked(in_array($post_type->name, $selected_post_types)); ?>>
                                    <span class="checkmark"></span>
                                    <?php echo esc_html($post_type->label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">Select which post types should have schema markup support.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Default Schema for Pages</th>
                        <td>
                            <select name="softworld_schema_global_settings[default_page_schema]" class="regular-text">
                                <option value="WebPage" <?php selected(isset($global_settings['default_page_schema']) ? $global_settings['default_page_schema'] : '', 'WebPage'); ?>>WebPage</option>
                                <option value="AboutPage" <?php selected(isset($global_settings['default_page_schema']) ? $global_settings['default_page_schema'] : '', 'AboutPage'); ?>>AboutPage</option>
                                <option value="ContactPage" <?php selected(isset($global_settings['default_page_schema']) ? $global_settings['default_page_schema'] : '', 'ContactPage'); ?>>ContactPage</option>
                                <option value="FAQPage" <?php selected(isset($global_settings['default_page_schema']) ? $global_settings['default_page_schema'] : '', 'FAQPage'); ?>>FAQPage</option>
                            </select>
                            <p class="description">Default schema type for pages when no specific type is set.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Custom Schema Settings -->
            <div class="softworld-schema-section">
                <h2>Custom Schema Types</h2>
                <p class="description">Create and manage custom schema types for specialized content.</p>

                <div id="custom-schema-container">
                    <?php if (!empty($custom_schema_types)): ?>
                        <?php foreach ($custom_schema_types as $key => $schema): ?>
                        <div class="custom-schema-item" data-id="<?php echo esc_attr($key); ?>">
                            <h3><?php echo esc_html($schema['name']); ?> <small>(<?php echo esc_html($schema['type']); ?>)</small></h3>
                            <p><?php echo esc_html($schema['description']); ?></p>
                            <div class="custom-schema-actions">
                                <button type="button" class="button edit-custom-schema" data-id="<?php echo esc_attr($key); ?>">Edit</button>
                                <button type="button" class="button button-danger delete-custom-schema" data-id="<?php echo esc_attr($key); ?>">Delete</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No custom schema types defined yet.</p>
                    <?php endif; ?>
                </div>

                <button type="button" class="button button-primary" id="add-custom-schema">Add Custom Schema Type</button>

                <!-- Custom Schema Form (hidden by default) -->
                <div id="custom-schema-form" style="display: none; margin-top: 20px; padding: 20px; border: 1px solid #ddd; background: #f9f9f9;">
                    <h3>Add/Edit Custom Schema</h3>
                    
                    <input type="hidden" id="custom-schema-id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="custom-schema-name">Schema Name</label></th>
                            <td>
                                <input type="text" id="custom-schema-name" class="regular-text" placeholder="e.g., Course, Event, Product">
                                <p class="description">A descriptive name for your custom schema type.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="custom-schema-type">Schema.org Type</label></th>
                            <td>
                                <input type="text" id="custom-schema-type" class="regular-text" placeholder="e.g., Course, Event, Product">
                                <p class="description">The official Schema.org type name.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="custom-schema-description">Description</label></th>
                            <td>
                                <textarea id="custom-schema-description" class="regular-text" rows="3" placeholder="Describe what this schema type is used for"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <h4>Properties</h4>
                    <div id="custom-schema-properties">
                        <div class="schema-property">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label>Property Name</label></th>
                                    <td><input type="text" class="property-name regular-text" placeholder="e.g., instructor, startDate"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label>Data Type</label></th>
                                    <td>
                                        <select class="property-type">
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="date">Date</option>
                                            <option value="url">URL</option>
                                            <option value="textarea">Long Text</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label>Required</label></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" class="property-required">
                                            This property is required
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label>Description</label></th>
                                    <td>
                                        <textarea class="property-description regular-text" rows="2" placeholder="Description of this property"></textarea>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" class="button remove-property">Remove Property</button>
                            <hr>
                        </div>
                    </div>
                    
                    <button type="button" class="button" id="add-property">Add Property</button>
                    
                    <div class="custom-schema-form-actions" style="margin-top: 20px;">
                        <button type="button" class="button button-primary" id="save-custom-schema">Save Schema</button>
                        <button type="button" class="button" id="cancel-custom-schema">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Schema Types Information -->
            <div class="softworld-schema-section">
                <h2>Available Schema Types</h2>
                <div class="softworld-schema-types-grid">
                    <div class="schema-type-card">
                        <h3>Article</h3>
                        <p>For blog posts, news articles, and editorial content. Includes author, publish date, and
                            article metadata.</p>
                        <strong>Best for:</strong> Blog posts, news articles, editorial content
                    </div>

                    <div class="schema-type-card">
                        <h3>Product</h3>
                        <p>For product pages with pricing, availability, and review information. Enhances product
                            visibility in search.</p>
                        <strong>Best for:</strong> E-commerce products, services with pricing
                    </div>

                    <div class="schema-type-card">
                        <h3>FAQ</h3>
                        <p>For frequently asked questions pages. Helps display rich snippets with Q&A format in search
                            results.</p>
                        <strong>Best for:</strong> FAQ pages, Q&A content
                    </div>

                    <div class="schema-type-card">
                        <h3>How-To</h3>
                        <p>For step-by-step guides and tutorials. Displays structured steps in search results.</p>
                        <strong>Best for:</strong> Tutorials, guides, instruction manuals
                    </div>

                    <div class="schema-type-card">
                        <h3>Review</h3>
                        <p>For product reviews and ratings. Shows star ratings and review information in search results.
                        </p>
                        <strong>Best for:</strong> Product reviews, service reviews
                    </div>

                    <div class="schema-type-card">
                        <h3>Recipe</h3>
                        <p>For cooking recipes with ingredients, cooking time, and nutritional information.</p>
                        <strong>Best for:</strong> Food blogs, recipe websites
                    </div>
                </div>
            </div>

            <!-- Testing Tools -->
            <div class="softworld-schema-section">
                <h2>Schema Testing Tools</h2>
                <div class="softworld-testing-tools">
                    <div class="tool-card">
                        <h3>Google Rich Results Test</h3>
                        <p>Test how your structured data appears in Google search results.</p>
                        <a href="https://search.google.com/test/rich-results" target="_blank"
                            class="button button-secondary">
                            Open Testing Tool
                        </a>
                    </div>

                    <div class="tool-card">
                        <h3>Schema Markup Validator</h3>
                        <p>Validate your schema markup for errors and warnings.</p>
                        <a href="https://validator.schema.org/" target="_blank" class="button button-secondary">
                            Open Validator
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php submit_button('Save Schema Settings'); ?>
    </form>
</div>

<style>
    .softworld-schema-container {
        max-width: 1200px;
    }

    .softworld-schema-section {
        background: #fff;
        border: 1px solid #ccd0d4;
        margin: 20px 0;
        padding: 20px;
        border-radius: 4px;
    }

    .softworld-schema-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .softworld-checkbox-label {
        position: relative;
        display: inline-block;
        padding-left: 25px;
        cursor: pointer;
        user-select: none;
    }

    .softworld-checkbox-label input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 18px;
        width: 18px;
        background-color: #eee;
        border-radius: 3px;
    }

    .softworld-checkbox-label:hover input~.checkmark {
        background-color: #ccc;
    }

    .softworld-checkbox-label input:checked~.checkmark {
        background-color: #0073aa;
    }

    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    .softworld-checkbox-label input:checked~.checkmark:after {
        display: block;
    }

    .softworld-checkbox-label .checkmark:after {
        left: 6px;
        top: 2px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
    }

    .softworld-schema-types-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .schema-type-card {
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        background: #f9f9f9;
    }

    .schema-type-card h3 {
        margin-top: 0;
        color: #0073aa;
    }

    .softworld-testing-tools {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .tool-card {
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        background: #f0f8ff;
    }

    .tool-card h3 {
        margin-top: 0;
        color: #0073aa;
    }

    .social-profile-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .remove-media-button {
        margin-left: 5px;
    }
    
    .custom-schema-item {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
        background: #f9f9f9;
    }
    
    .custom-schema-actions {
        margin-top: 10px;
    }
    
    .button-danger {
        background: #dc3232;
        border-color: #dc3232;
        color: white;
    }
    
    .button-danger:hover {
        background: #f00;
        border-color: #f00;
        color: white;
    }
    
    .schema-property {
        margin-bottom: 20px;
        padding: 15px;
        background: white;
        border: 1px solid #ddd;
    }
    
    .required {
        color: red;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle entity type fields
        var entityRadios = document.querySelectorAll('input[name="softworld_schema_knowledge_graph[entity_type]"]');
        var orgFields = document.getElementById('organization_fields');
        var personFields = document.getElementById('person_fields');

        entityRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.value === 'Organization') {
                    orgFields.style.display = 'block';
                    personFields.style.display = 'none';
                } else if (this.value === 'Person') {
                    orgFields.style.display = 'none';
                    personFields.style.display = 'block';
                }
            });
        });
        
        // Custom Schema functionality
        var customSchemaForm = document.getElementById('custom-schema-form');
        var addCustomSchemaBtn = document.getElementById('add-custom-schema');
        var saveCustomSchemaBtn = document.getElementById('save-custom-schema');
        var cancelCustomSchemaBtn = document.getElementById('cancel-custom-schema');
        var addPropertyBtn = document.getElementById('add-property');
        var propertiesContainer = document.getElementById('custom-schema-properties');
        
        // Show custom schema form
        addCustomSchemaBtn.addEventListener('click', function() {
            customSchemaForm.style.display = 'block';
            // Reset form
            document.getElementById('custom-schema-id').value = '';
            document.getElementById('custom-schema-name').value = '';
            document.getElementById('custom-schema-type').value = '';
            document.getElementById('custom-schema-description').value = '';
            // Clear properties except the first one
            var properties = propertiesContainer.querySelectorAll('.schema-property');
            for (var i = 1; i < properties.length; i++) {
                properties[i].remove();
            }
            // Reset first property
            var firstProperty = properties[0];
            firstProperty.querySelector('.property-name').value = '';
            firstProperty.querySelector('.property-type').value = 'text';
            firstProperty.querySelector('.property-required').checked = false;
            firstProperty.querySelector('.property-description').value = '';
        });
        
        // Cancel custom schema form
        cancelCustomSchemaBtn.addEventListener('click', function() {
            customSchemaForm.style.display = 'none';
        });
        
        // Add property
        addPropertyBtn.addEventListener('click', function() {
            var propertyTemplate = propertiesContainer.querySelector('.schema-property').cloneNode(true);
            // Clear values
            propertyTemplate.querySelector('.property-name').value = '';
            propertyTemplate.querySelector('.property-type').value = 'text';
            propertyTemplate.querySelector('.property-required').checked = false;
            propertyTemplate.querySelector('.property-description').value = '';
            // Add remove event
            propertyTemplate.querySelector('.remove-property').addEventListener('click', function() {
                propertyTemplate.remove();
            });
            propertiesContainer.appendChild(propertyTemplate);
        });
        
        // Remove property event delegation
        propertiesContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-property')) {
                // Don't remove the last property
                if (propertiesContainer.querySelectorAll('.schema-property').length > 1) {
                    e.target.closest('.schema-property').remove();
                }
            }
        });
        
        // Save custom schema
        // Update the saveCustomSchemaBtn click event handler
            saveCustomSchemaBtn.addEventListener('click', function() {
                var schemaId = document.getElementById('custom-schema-id').value || 'schema_' + Date.now();
                var schemaName = document.getElementById('custom-schema-name').value;
                var schemaType = document.getElementById('custom-schema-type').value;
                var schemaDescription = document.getElementById('custom-schema-description').value;
                
                if (!schemaName || !schemaType) {
                    alert('Please provide both a schema name and type.');
                    return;
                }
                
                // Collect properties
                var properties = [];
                var propertyElements = propertiesContainer.querySelectorAll('.schema-property');
                
                propertyElements.forEach(function(propertyEl) {
                    var name = propertyEl.querySelector('.property-name').value;
                    if (name) {
                        properties.push({
                            name: name,
                            type: propertyEl.querySelector('.property-type').value,
                            required: propertyEl.querySelector('.property-required').checked,
                            description: propertyEl.querySelector('.property-description').value
                        });
                    }
                });
                
                // Show loading state
                saveCustomSchemaBtn.textContent = 'Saving...';
                saveCustomSchemaBtn.disabled = true;
                
                // Send AJAX request
                jQuery.post(softworldSchema.ajaxurl, {
                    action: 'save_custom_schema',
                    nonce: softworldSchema.nonce,
                    id: schemaId,
                    schema: {
                        name: schemaName,
                        type: schemaType,
                        description: schemaDescription,
                        properties: properties
                    }
                }, function(response) {
                    // Restore button state
                    saveCustomSchemaBtn.textContent = 'Save Schema';
                    saveCustomSchemaBtn.disabled = false;
                    
                    if (response.success) {
                        alert('Custom schema saved successfully!');
                        customSchemaForm.style.display = 'none';
                        location.reload(); // Reload to show the new schema
                    } else {
                        alert('Error: ' + response.data);
                    }
                }).fail(function() {
                    // Restore button state on failure
                    saveCustomSchemaBtn.textContent = 'Save Schema';
                    saveCustomSchemaBtn.disabled = false;
                    alert('An error occurred while saving the schema.');
                });
            });

        // Edit custom schema
        document.querySelectorAll('.edit-custom-schema').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var schemaId = this.getAttribute('data-id');
                
                // Fetch schema data via AJAX
                jQuery.post(softworldSchema.ajaxurl, {
                    action: 'get_custom_schema',
                    nonce: softworldSchema.nonce,
                    id: schemaId
                }, function(response) {
                    if (response.success) {
                        var schema = response.data;
                        document.getElementById('custom-schema-id').value = schemaId;
                        document.getElementById('custom-schema-name').value = schema.name;
                        document.getElementById('custom-schema-type').value = schema.type;
                        document.getElementById('custom-schema-description').value = schema.description;
                        
                        // Clear properties
                        propertiesContainer.innerHTML = '';
                        
                        // Add properties
                        if (schema.properties && Object.keys(schema.properties).length > 0) {
                            Object.keys(schema.properties).forEach(function(propKey) {
                                var property = schema.properties[propKey];
                                var propertyEl = document.createElement('div');
                                propertyEl.className = 'schema-property';
                                propertyEl.innerHTML = `
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><label>Property Name</label></th>
                                            <td><input type="text" class="property-name regular-text" value="${property.name}" placeholder="e.g., instructor, startDate"></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label>Data Type</label></th>
                                            <td>
                                                <select class="property-type">
                                                    <option value="text" ${property.type === 'text' ? 'selected' : ''}>Text</option>
                                                    <option value="number" ${property.type === 'number' ? 'selected' : ''}>Number</option>
                                                    <option value="date" ${property.type === 'date' ? 'selected' : ''}>Date</option>
                                                    <option value="url" ${property.type === 'url' ? 'selected' : ''}>URL</option>
                                                    <option value="textarea" ${property.type === 'textarea' ? 'selected' : ''}>Long Text</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label>Required</label></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" class="property-required" ${property.required ? 'checked' : ''}>
                                                    This property is required
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label>Description</label></th>
                                            <td>
                                                <textarea class="property-description regular-text" rows="2" placeholder="Description of this property">${property.description || ''}</textarea>
                                            </td>
                                        </tr>
                                    </table>
                                    <button type="button" class="button remove-property">Remove Property</button>
                                    <hr>
                                `;
                                propertiesContainer.appendChild(propertyEl);
                            });
                        } else {
                            // Add one empty property
                            var propertyEl = document.createElement('div');
                            propertyEl.className = 'schema-property';
                            propertyEl.innerHTML = `
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><label>Property Name</label></th>
                                        <td><input type="text" class="property-name regular-text" placeholder="e.g., instructor, startDate"></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Data Type</label></th>
                                        <td>
                                            <select class="property-type">
                                                <option value="text">Text</option>
                                                <option value="number">Number</option>
                                                <option value="date">Date</option>
                                                <option value="url">URL</option>
                                                <option value="textarea">Long Text</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Required</label></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" class="property-required">
                                                This property is required
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label>Description</label></th>
                                        <td>
                                            <textarea class="property-description regular-text" rows="2" placeholder="Description of this property"></textarea>
                                        </td>
                                    </tr>
                                </table>
                                <button type="button" class="button remove-property">Remove Property</button>
                                <hr>
                            `;
                            propertiesContainer.appendChild(propertyEl);
                        }
                        
                        customSchemaForm.style.display = 'block';
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        
        // Delete custom schema
        document.querySelectorAll('.delete-custom-schema').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to delete this custom schema?')) {
                    return;
                }
                
                var schemaId = this.getAttribute('data-id');
                
                // Send AJAX request
                jQuery.post(softworldSchema.ajaxurl, {
                    action: 'delete_custom_schema',
                    nonce: softworldSchema.nonce,
                    id: schemaId
                }, function(response) {
                    if (response.success) {
                        alert('Custom schema deleted successfully!');
                        location.reload(); // Reload to update the list
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
    });

    function addSocialProfile() {
        var container = document.getElementById('social_profiles_container');
        var newRow = document.createElement('div');
        newRow.className = 'social-profile-row';
        newRow.style.marginBottom = '10px';

        newRow.innerHTML = `
            <input type="url" name="softworld_schema_knowledge_graph[social_profiles][]" 
                placeholder="https://www.facebook.com/yourpage" 
                class="regular-text">
            <button type="button" class="button remove-social-profile" onclick="removeSocialProfile(this)">
                Remove
            </button>
        `;

        container.appendChild(newRow);
    }

    function removeSocialProfile(button) {
        button.closest('.social-profile-row').remove();
    }
    
    function removeMedia(fieldName) {
        var input = document.querySelector('input[name="softworld_schema_knowledge_graph[' + fieldName + ']"]');
        input.value = '';
        
        var removeButton = input.nextElementSibling.nextElementSibling;
        removeButton.style.display = 'none';
        
        var preview = input.parentNode.querySelector('.image-preview');
        if (preview) {
            preview.style.display = 'none';
        }
    }

    function openMediaLibrary(fieldName) {
        var mediaUploader;

        // If the media uploader already exists, reopen it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create the media uploader
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Image',
            button: {
                text: 'Select Image'
            },
            multiple: false
        });

        // Handle selection
        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var input = document.querySelector('input[name="softworld_schema_knowledge_graph[' + fieldName + ']"]');
            input.value = attachment.url;
            
            // Show the remove button
            var removeButton = input.nextElementSibling.nextElementSibling;
            removeButton.style.display = 'inline-block';
            
            // Update or create the preview
            var preview = input.parentNode.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'image-preview';
                preview.style.marginTop = '10px';
                var img = document.createElement('img');
                img.style.maxWidth = '150px';
                img.style.maxHeight = '150px';
                img.style.border = '1px solid #ddd';
                img.style.padding = '5px';
                preview.appendChild(img);
                input.parentNode.appendChild(preview);
            }
            preview.style.display = 'block';
            preview.querySelector('img').src = attachment.url;
        });

        // Open the uploader
        mediaUploader.open();
    }
</script>