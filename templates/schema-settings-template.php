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