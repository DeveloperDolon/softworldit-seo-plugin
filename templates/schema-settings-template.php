<?php
/**
 * Enhanced Schema Settings Template with Templates and Testing Features
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize settings
$global_settings = get_option('softworld_schema_global_settings', array());
$kg_settings = get_option('softworld_schema_knowledge_graph', array());
$schema_types_settings = get_option('softworld_schema_types_settings', array());
$custom_schema_types = get_option('softworld_schema_custom_types', array());
$schema_templates = get_option('softworld_schema_templates', array());
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
                                <option value="BlogPosting" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'BlogPosting'); ?>>Blog Posting</option>
                                <option value="NewsArticle" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'NewsArticle'); ?>>News Article</option>
                                <option value="TechArticle" <?php selected(isset($global_settings['default_article_type']) ? $global_settings['default_article_type'] : '', 'TechArticle'); ?>>Tech Article</option>
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
                            <p class="description">Automatically add breadcrumb schema to help with navigation breadcrumbs.</p>
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

            <!-- Schema Templates Section -->
            <div class="softworld-schema-section">
                <h2>Schema Templates</h2>
                <p class="description">Create and manage reusable schema templates to speed up your workflow.</p>

                <div class="schema-templates-controls" style="margin-bottom: 20px;">
                    <button type="button" class="button button-primary" id="create-new-template">Create New Template</button>
                    <button type="button" class="button" id="import-template">Import Templates</button>
                    <button type="button" class="button" id="export-templates">Export Templates</button>
                </div>

                <div class="templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php if (!empty($schema_templates)): ?>
                        <?php foreach ($schema_templates as $template_id => $template): ?>
                            <div class="template-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px; background: #f9f9f9;">
                                <h3 style="margin-top: 0;"><?php echo esc_html($template['name']); ?></h3>
                                <p><strong>Type:</strong> <?php echo esc_html($template['schema_type']); ?></p>
                                <p><strong>Category:</strong> <?php echo esc_html($template['category']); ?></p>
                                <p><?php echo esc_html($template['description']); ?></p>
                                <div class="template-actions" style="margin-top: 15px;">
                                    <button type="button" class="button edit-template" data-template-id="<?php echo esc_attr($template_id); ?>">Edit</button>
                                    <button type="button" class="button button-danger delete-template" data-template-id="<?php echo esc_attr($template_id); ?>">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #f5f5f5; border-radius: 4px;">
                            <p>No templates created yet. Create your first template to get started.</p>
                            <button type="button" class="button button-primary" id="create-first-template">Create Your First Template</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Template Form Modal -->
            <div id="template-form-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 4px; width: 600px; max-height: 80vh; overflow-y: auto;">
                    <h2 id="template-modal-title">Create Schema Template</h2>
                    
                    <input type="hidden" id="template-edit-id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="template-form-name">Template Name</label></th>
                            <td>
                                <input type="text" id="template-form-name" class="regular-text" placeholder="e.g., Blog Post Template">
                                <p class="description">A descriptive name for your template.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="template-form-description">Description</label></th>
                            <td>
                                <textarea id="template-form-description" class="regular-text" rows="3" placeholder="Describe what this template is for"></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="template-form-schema-type">Schema Type</label></th>
                            <td>
                                <select id="template-form-schema-type" class="regular-text">
                                    <option value="">Select Schema Type</option>
                                    <option value="Article">Article</option>
                                    <option value="Product">Product</option>
                                    <option value="Recipe">Recipe</option>
                                    <option value="Review">Review</option>
                                    <option value="FAQPage">FAQPage</option>
                                    <option value="HowTo">HowTo</option>
                                </select>
                                <p class="description">Select the schema type this template applies to.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><label for="template-form-category">Category</label></th>
                            <td>
                                <select id="template-form-category" class="regular-text">
                                    <option value="general">General</option>
                                    <option value="blog">Blog</option>
                                    <option value="ecommerce">E-commerce</option>
                                    <option value="business">Business</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <div id="template-properties-section" style="display: none; margin-top: 20px;">
                        <h3>Template Properties</h3>
                        <div id="template-properties-container">
                            <!-- Properties will be dynamically added here based on schema type -->
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="button" id="cancel-template-form">Cancel</button>
                        <button type="button" class="button button-primary" id="save-template-form">Save Template</button>
                    </div>
                </div>
            </div>

            <!-- Import/Export Modal -->
            <div id="template-import-export-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 4px; width: 600px;">
                    <h2>Import/Export Templates</h2>
                    
                    <div id="import-section">
                        <h3>Import Templates</h3>
                        <p>Paste your template data (JSON format) below:</p>
                        <textarea id="import-data" style="width: 100%; height: 150px; font-family: monospace;"></textarea>
                        <button type="button" class="button button-primary" id="import-templates-btn" style="margin-top: 10px;">Import Templates</button>
                    </div>
                    
                    <div id="export-section" style="margin-top: 30px;">
                        <h3>Export Templates</h3>
                        <p>Copy the JSON below to export your templates:</p>
                        <textarea id="export-data" style="width: 100%; height: 150px; font-family: monospace;" readonly></textarea>
                        <button type="button" class="button" id="copy-export-data" style="margin-top: 10px;">Copy to Clipboard</button>
                    </div>
                    
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="button" id="close-import-export">Close</button>
                    </div>
                </div>
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
                            <p class="description">Choose whether this website represents an organization or a person.</p>
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
                                <p class="description">URL to your organization's logo image (recommended: square format, at least 112x112px).</p>
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
                            <button type="button" class="button" onclick="addSocialProfile()">Add Social Profile</button>
                            <p class="description">Add URLs to your social media profiles (Facebook, Twitter, LinkedIn, Instagram, etc.).</p>
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

            <!-- Schema Testing Tools -->
            <div class="softworld-schema-section">
                <h2>Schema Testing & Validation Tools</h2>
                <div class="testing-tools-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <div class="tool-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px; background: #f0f8ff;">
                        <h3 style="margin-top: 0; color: #0073aa;">Live Schema Tester</h3>
                        <p>Test any URL on your website to see what schema markup is being generated.</p>
                        
                        <div class="test-form" style="margin: 15px 0;">
                            <input type="url" id="test-url" placeholder="https://yoursite.com/page-to-test" style="width: 100%; margin-bottom: 10px;">
                            <button type="button" class="button button-primary" id="test-url-btn">Test This URL</button>
                        </div>
                        
                        <div id="url-test-results" style="display: none; margin-top: 15px; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                            <!-- Test results will be displayed here -->
                        </div>
                    </div>

                    <div class="tool-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px; background: #f0f8ff;">
                        <h3 style="margin-top: 0; color: #0073aa;">Google Rich Results Test</h3>
                        <p>Test how your structured data appears in Google search results.</p>
                        <a href="https://search.google.com/test/rich-results" target="_blank"
                            class="button button-secondary" style="display: inline-block; margin-top: 10px;">
                            Open Testing Tool
                        </a>
                    </div>

                    <div class="tool-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px; background: #f0f8ff;">
                        <h3 style="margin-top: 0; color: #0073aa;">Schema Markup Validator</h3>
                        <p>Validate your schema markup for errors and warnings.</p>
                        <a href="https://validator.schema.org/" target="_blank" class="button button-secondary" style="display: inline-block; margin-top: 10px;">
                            Open Validator
                        </a>
                    </div>

                    <div class="tool-card" style="border: 1px solid #ddd; padding: 20px; border-radius: 4px; background: #f0f8ff;">
                        <h3 style="margin-top: 0; color: #0073aa;">Schema Markup Generator</h3>
                        <p>Generate schema markup for specific content types quickly.</p>
                        <select id="schema-generator-type" style="width: 100%; margin: 10px 0;">
                            <option value="">Select Schema Type</option>
                            <option value="Article">Article</option>
                            <option value="Product">Product</option>
                            <option value="Recipe">Recipe</option>
                            <option value="Review">Review</option>
                            <option value="Event">Event</option>
                        </select>
                        <button type="button" class="button" id="generate-schema-btn">Generate Schema</button>
                    </div>
                </div>
            </div>

            <!-- Available Schema Types Information -->
            <div class="softworld-schema-section">
                <h2>Available Schema Types</h2>
                <div class="softworld-schema-types-grid">
                    <div class="schema-type-card">
                        <h3>Article</h3>
                        <p>For blog posts, news articles, and editorial content. Includes author, publish date, and article metadata.</p>
                        <strong>Best for:</strong> Blog posts, news articles, editorial content
                    </div>

                    <div class="schema-type-card">
                        <h3>Product</h3>
                        <p>For product pages with pricing, availability, and review information. Enhances product visibility in search.</p>
                        <strong>Best for:</strong> E-commerce products, services with pricing
                    </div>

                    <div class="schema-type-card">
                        <h3>FAQ</h3>
                        <p>For frequently asked questions pages. Helps display rich snippets with Q&A format in search results.</p>
                        <strong>Best for:</strong> FAQ pages, Q&A content
                    </div>

                    <div class="schema-type-card">
                        <h3>How-To</h3>
                        <p>For step-by-step guides and tutorials. Displays structured steps in search results.</p>
                        <strong>Best for:</strong> Tutorials, guides, instruction manuals
                    </div>

                    <div class="schema-type-card">
                        <h3>Review</h3>
                        <p>For product reviews and ratings. Shows star ratings and review information in search results.</p>
                        <strong>Best for:</strong> Product reviews, service reviews
                    </div>

                    <div class="schema-type-card">
                        <h3>Recipe</h3>
                        <p>For cooking recipes with ingredients, cooking time, and nutritional information.</p>
                        <strong>Best for:</strong> Food blogs, recipe websites
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

    .testing-tools-grid .tool-card {
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        background: #f0f8ff;
    }

    .testing-tools-grid .tool-card h3 {
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

    .template-card:hover {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s;
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
        
        // Template Management
        var templateFormModal = document.getElementById('template-form-modal');
        var importExportModal = document.getElementById('template-import-export-modal');
        
        // Create new template
        document.getElementById('create-new-template').addEventListener('click', function() {
            showTemplateForm();
        });
        
        if(document.getElementById('create-first-template')) {
            document.getElementById('create-first-template').addEventListener('click', function() {
                showTemplateForm();
            });
        }
        
        // Import/Export templates
        document.getElementById('import-template').addEventListener('click', function() {
            importExportModal.style.display = 'block';
        });
        
        document.getElementById('export-templates').addEventListener('click', function() {
            // Get current templates for export
            jQuery.post(softworldSchema.ajaxurl, {
                action: 'get_all_templates',
                nonce: softworldSchema.nonce
            }, function(response) {
                if (response.success) {
                    document.getElementById('export-data').value = JSON.stringify(response.data, null, 2);
                    importExportModal.style.display = 'block';
                }
            });
        });
        
        // Close import/export modal
        document.getElementById('close-import-export').addEventListener('click', function() {
            importExportModal.style.display = 'none';
        });
        
        // Copy export data
        document.getElementById('copy-export-data').addEventListener('click', function() {
            var exportData = document.getElementById('export-data');
            exportData.select();
            document.execCommand('copy');
            alert('Templates copied to clipboard!');
        });
        
        // Import templates
        document.getElementById('import-templates-btn').addEventListener('click', function() {
            var importData = document.getElementById('import-data').value;
            if (!importData) {
                alert('Please paste template data to import.');
                return;
            }
            
            try {
                JSON.parse(importData); // Validate JSON
                
                jQuery.post(softworldSchema.ajaxurl, {
                    action: 'import_templates',
                    nonce: softworldSchema.nonce,
                    template_data: importData
                }, function(response) {
                    if (response.success) {
                        alert('Templates imported successfully!');
                        location.reload();
                    } else {
                        alert('Import failed: ' + response.data);
                    }
                });
            } catch(e) {
                alert('Invalid JSON data. Please check your import data.');
            }
        });
        
        // Edit template
        document.querySelectorAll('.edit-template').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var templateId = this.getAttribute('data-template-id');
                editTemplate(templateId);
            });
        });
        
        // Delete template
        document.querySelectorAll('.delete-template').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var templateId = this.getAttribute('data-template-id');
                if (confirm('Are you sure you want to delete this template?')) {
                    deleteTemplate(templateId);
                }
            });
        });
        
        // Template form handlers
        document.getElementById('cancel-template-form').addEventListener('click', function() {
            templateFormModal.style.display = 'none';
        });
        
        document.getElementById('save-template-form').addEventListener('click', function() {
            saveTemplateForm();
        });
        
        // Schema type change handler for template form
        document.getElementById('template-form-schema-type').addEventListener('change', function() {
            updateTemplateProperties(this.value);
        });
        
        // URL Testing functionality
        document.getElementById('test-url-btn').addEventListener('click', function() {
            var url = document.getElementById('test-url').value;
            var button = this;
            var resultsDiv = document.getElementById('url-test-results');
            
            if (!url) {
                alert('Please enter a URL to test.');
                return;
            }
            
            button.textContent = 'Testing...';
            button.disabled = true;
            
            jQuery.post(softworldSchema.ajaxurl, {
                action: 'test_url_schema',
                nonce: softworldSchema.nonce,
                url: url
            }, function(response) {
                button.textContent = 'Test This URL';
                button.disabled = false;
                
                if (response.success) {
                    resultsDiv.innerHTML = response.data;
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="color: red;">Error: ' + response.data + '</div>';
                    resultsDiv.style.display = 'block';
                }
            });
        });
        
        // Schema generator
        document.getElementById('generate-schema-btn').addEventListener('click', function() {
            var schemaType = document.getElementById('schema-generator-type').value;
            if (!schemaType) {
                alert('Please select a schema type first.');
                return;
            }
            
            // Generate basic schema template
            var schemaTemplate = generateBasicSchema(schemaType);
            
            // Show in a modal or new window
            var newWindow = window.open('', '_blank', 'width=800,height=600');
            newWindow.document.write('<html><head><title>Generated Schema - ' + schemaType + '</title></head><body>');
            newWindow.document.write('<h2>Generated ' + schemaType + ' Schema</h2>');
            newWindow.document.write('<textarea style="width: 100%; height: 500px; font-family: monospace;">' + JSON.stringify(schemaTemplate, null, 2) + '</textarea>');
            newWindow.document.write('</body></html>');
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
        
       // Save custom schema - UPDATED VERSION
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
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }).fail(function(xhr, status, error) {
                // Restore button state on failure
                saveCustomSchemaBtn.textContent = 'Save Schema';
                saveCustomSchemaBtn.disabled = false;
                alert('An error occurred while saving the schema: ' + error);
            });
        });

        // Edit custom schema
        document.querySelectorAll('.edit-custom-schema').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var schemaId = this.getAttribute('data-id');
                
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
                        
                        customSchemaForm.style.display = 'block';
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
                
                jQuery.post(softworldSchema.ajaxurl, {
                    action: 'delete_custom_schema',
                    nonce: softworldSchema.nonce,
                    id: schemaId
                }, function(response) {
                    if (response.success) {
                        alert('Custom schema deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });
        
        // Helper functions
        function showTemplateForm(templateData = null) {
            if (templateData) {
                document.getElementById('template-modal-title').textContent = 'Edit Schema Template';
                document.getElementById('template-edit-id').value = templateData.id;
                document.getElementById('template-form-name').value = templateData.name;
                document.getElementById('template-form-description').value = templateData.description;
                document.getElementById('template-form-schema-type').value = templateData.schema_type;
                document.getElementById('template-form-category').value = templateData.category;
            } else {
                document.getElementById('template-modal-title').textContent = 'Create Schema Template';
                document.getElementById('template-edit-id').value = '';
                document.getElementById('template-form-name').value = '';
                document.getElementById('template-form-description').value = '';
                document.getElementById('template-form-schema-type').value = '';
                document.getElementById('template-form-category').value = 'general';
            }
            
            templateFormModal.style.display = 'block';
        }
        
        function editTemplate(templateId) {
            jQuery.post(softworldSchema.ajaxurl, {
                action: 'get_schema_template',
                nonce: softworldSchema.nonce,
                id: templateId
            }, function(response) {
                if (response.success) {
                    showTemplateForm(Object.assign({id: templateId}, response.data));
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
        
        function deleteTemplate(templateId) {
            jQuery.post(softworldSchema.ajaxurl, {
                action: 'delete_schema_template',
                nonce: softworldSchema.nonce,
                id: templateId
            }, function(response) {
                if (response.success) {
                    alert('Template deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
        
        function saveTemplateForm() {
            var templateId = document.getElementById('template-edit-id').value || 'template_' + Date.now(); // Get ID first for consistency

            var template_name = document.getElementById('template-form-name').value;
            var template_description = document.getElementById('template-form-description').value;
            var schema_type = document.getElementById('template-form-schema-type').value;
            var template_category = document.getElementById('template-form-category').value;
            var template_properties_data = {}; // This will hold your schema_data

            // Collect property values from the form if the section is visible
            var propertyInputs = document.querySelectorAll('#template-properties-container input, #template-properties-container textarea, #template-properties-container select');
            propertyInputs.forEach(function(input) {
                // Ensure name starts with 'schema_data[' to match PHP's expectation later
                if (input.name && input.value) {
                    // Remove 'schema_data[' and ']' from the name to make it a direct key
                    var cleanedName = input.name.replace('schema_data[', '').replace(']', '');
                    template_properties_data[cleanedName] = input.value;
                }
            });

            if (!template_name || !schema_type) {
                alert('Please fill in all required fields.');
                return;
            }

            jQuery.post(softworldSchema.ajaxurl, {
                action: 'save_schema_template',
                nonce: softworldSchema.nonce,
                // Send individual parameters directly
                id: templateId, // PHP should use this for edit scenarios, though your current PHP generates a new ID
                template_name: template_name,
                template_description: template_description,
                template_category: template_category,
                schema_type: schema_type,
                // Send the collected properties as a sub-array (jQuery.post will handle this correctly for PHP)
                schema_data: template_properties_data
            }, function(response) {
                if (response.success) {
                    alert('Template saved successfully!');
                    templateFormModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                alert('AJAX request failed: ' + textStatus + ', ' + errorThrown);
                console.error(jqXHR);
            });
        }
        
        function updateTemplateProperties(schemaType) {
            var container = document.getElementById('template-properties-container');
            var section = document.getElementById('template-properties-section');
            
            if (!schemaType) {
                section.style.display = 'none';
                return;
            }
            
            section.style.display = 'block';
            container.innerHTML = '';
            
            // Add properties based on schema type
            var properties = getSchemaTypeProperties(schemaType);
            properties.forEach(function(property) {
                var propertyHtml = '<div class="template-property" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">';
                propertyHtml += '<label style="font-weight: bold;">' + property.label + ':</label>';
                
                if (property.type === 'textarea') {
                    propertyHtml += '<textarea name="' + property.name + '" style="width: 100%; margin-top: 5px;" rows="3" placeholder="' + property.placeholder + '"></textarea>';
                } else if (property.type === 'select') {
                    propertyHtml += '<select name="' + property.name + '" style="width: 100%; margin-top: 5px;">';
                    property.options.forEach(function(option) {
                        propertyHtml += '<option value="' + option.value + '">' + option.label + '</option>';
                    });
                    propertyHtml += '</select>';
                } else {
                    propertyHtml += '<input type="' + property.type + '" name="' + property.name + '" style="width: 100%; margin-top: 5px;" placeholder="' + property.placeholder + '">';
                }
                
                if (property.description) {
                    propertyHtml += '<p class="description" style="margin: 5px 0 0 0; font-style: italic; color: #666;">' + property.description + '</p>';
                }
                
                propertyHtml += '</div>';
                container.innerHTML += propertyHtml;
            });
        }
        
        function getSchemaTypeProperties(schemaType) {
            var properties = [];
            
            switch(schemaType) {
                case 'Product':
                    properties = [
                        {name: 'schema_data[price]', label: 'Default Price', type: 'text', placeholder: '19.99', description: 'Default price for products'},
                        {name: 'schema_data[currency]', label: 'Currency', type: 'text', placeholder: 'USD', description: 'Currency code'},
                        {name: 'schema_data[availability]', label: 'Availability', type: 'select', options: [
                            {value: 'InStock', label: 'In Stock'},
                            {value: 'OutOfStock', label: 'Out of Stock'},
                            {value: 'PreOrder', label: 'Pre Order'}
                        ]}
                    ];
                    break;
                case 'Recipe':
                    properties = [
                        {name: 'schema_data[prep_time]', label: 'Prep Time (minutes)', type: 'number', placeholder: '15'},
                        {name: 'schema_data[cook_time]', label: 'Cook Time (minutes)', type: 'number', placeholder: '30'},
                        {name: 'schema_data[servings]', label: 'Servings', type: 'number', placeholder: '4'}
                    ];
                    break;
                case 'Review':
                    properties = [
                        {name: 'schema_data[rating]', label: 'Default Rating', type: 'number', placeholder: '4.5', description: 'Rating from 1-5'},
                        {name: 'schema_data[item_name]', label: 'Item Being Reviewed', type: 'text', placeholder: 'Product or Service Name'}
                    ];
                    break;
                case 'FAQPage':
                    properties = [
                        {name: 'schema_data[faq_questions]', label: 'Sample FAQ Questions', type: 'textarea', placeholder: 'What is...?\nHow do I...?\nWhen should I...?', description: 'One question per line'}
                    ];
                    break;
                case 'HowTo':
                    properties = [
                        {name: 'schema_data[howto_steps]', label: 'Sample Steps', type: 'textarea', placeholder: 'Step 1: Do this\nStep 2: Do that\nStep 3: Finish', description: 'One step per line'},
                        {name: 'schema_data[howto_supplies]', label: 'Required Supplies', type: 'text', placeholder: 'screwdriver, hammer, nails', description: 'Comma separated list'}
                    ];
                    break;
            }
            
            return properties;
        }
        
        function generateBasicSchema(schemaType) {
            var schema = {
                "@context": "https://schema.org",
                "@type": schemaType
            };
            
            switch(schemaType) {
                case 'Article':
                    schema.headline = "Article Title Here";
                    schema.author = {
                        "@type": "Person",
                        "name": "Author Name"
                    };
                    schema.datePublished = new Date().toISOString();
                    schema.description = "Article description here";
                    break;
                case 'Product':
                    schema.name = "Product Name";
                    schema.description = "Product description";
                    schema.offers = {
                        "@type": "Offer",
                        "price": "19.99",
                        "priceCurrency": "USD",
                        "availability": "https://schema.org/InStock"
                    };
                    break;
                case 'Recipe':
                    schema.name = "Recipe Name";
                    schema.description = "Recipe description";
                    schema.prepTime = "PT15M";
                    schema.cookTime = "PT30M";
                    schema.recipeYield = "4 servings";
                    break;
                case 'Review':
                    schema.reviewBody = "Review content here";
                    schema.reviewRating = {
                        "@type": "Rating",
                        "ratingValue": "5",
                        "bestRating": "5"
                    };
                    schema.itemReviewed = {
                        "@type": "Thing",
                        "name": "Item being reviewed"
                    };
                    break;
            }
            
            return schema;
        }
    });

    // Functions for media library and social profiles
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