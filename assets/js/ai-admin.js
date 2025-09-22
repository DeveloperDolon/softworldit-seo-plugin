/**
 * Softworld AI SEO Admin JavaScript
 * Handles AI-powered SEO generation in the admin
 */

(function($) {
    'use strict';

    // Main AI SEO object
    window.SoftworldAISEO = {
        init: function() {
            this.initMetaBoxFeatures();
            this.initBulkGeneration();
            this.initSettingsPage();
        },

        // Initialize meta box AI features
        initMetaBoxFeatures: function() {
            if ($('#softworld_ai_seo_meta_box').length === 0) return;

            this.addAIButtons();
            this.bindMetaBoxEvents();
        },

        // Add AI generation buttons to meta box
        addAIButtons: function() {
            const titleField = $('#softworld_ai_seo_title');
            const descField = $('#softworld_ai_seo_description');

            if (titleField.length) {
                const titleContainer = $('<div class="softworld-ai-field-container"></div>');
                titleField.wrap(titleContainer);
                
                const titleControls = $(`
                    <div class="softworld-ai-controls">
                        <button type="button" class="button softworld-ai-generate" data-type="title">
                            <span class="dashicons dashicons-admin-generic"></span> Generate SEO Title
                        </button>
                        <div class="softworld-ai-options" style="display: none;">
                            <label>Keywords: <input type="text" class="softworld-target-keywords" placeholder="target keywords (optional)"></label>
                            <label>Tone: 
                                <select class="softworld-tone-select">
                                    <option value="professional">Professional</option>
                                    <option value="casual">Casual</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="authoritative">Authoritative</option>
                                    <option value="conversational">Conversational</option>
                                    <option value="engaging">Engaging</option>
                                </select>
                            </label>
                            <button type="button" class="button-primary softworld-generate-now">Generate</button>
                            <button type="button" class="button softworld-cancel-generate">Cancel</button>
                        </div>
                        <div class="softworld-ai-status"></div>
                    </div>
                `);
                titleField.after(titleControls);
            }

            if (descField.length) {
                const descContainer = $('<div class="softworld-ai-field-container"></div>');
                descField.wrap(descContainer);
                
                const descControls = $(`
                    <div class="softworld-ai-controls">
                        <button type="button" class="button softworld-ai-generate" data-type="description">
                            <span class="dashicons dashicons-admin-generic"></span> Generate Meta Description
                        </button>
                        <div class="softworld-ai-options" style="display: none;">
                            <label>Keywords: <input type="text" class="softworld-target-keywords" placeholder="target keywords (optional)"></label>
                            <label>Tone: 
                                <select class="softworld-tone-select">
                                    <option value="professional">Professional</option>
                                    <option value="casual">Casual</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="authoritative">Authoritative</option>
                                    <option value="conversational">Conversational</option>
                                    <option value="engaging">Engaging</option>
                                </select>
                            </label>
                            <button type="button" class="button-primary softworld-generate-now">Generate</button>
                            <button type="button" class="button softworld-cancel-generate">Cancel</button>
                        </div>
                        <div class="softworld-ai-status"></div>
                    </div>
                `);
                descField.after(descControls);
            }

            // Add character counter for description
            if (descField.length) {
                const charCounter = $('<div class="softworld-char-counter">0 characters</div>');
                descField.after(charCounter);
                
                descField.on('input', function() {
                    const length = $(this).val().length;
                    charCounter.text(length + ' characters');
                    charCounter.removeClass('optimal warning');
                    
                    if (length >= 150 && length <= 160) {
                        charCounter.addClass('optimal');
                    } else if (length > 160) {
                        charCounter.addClass('warning');
                    }
                });
                
                // Trigger initial count
                descField.trigger('input');
            }
        },

        // Bind meta box events
        bindMetaBoxEvents: function() {
            const self = this;

            // Show AI options
            $(document).on('click', '.softworld-ai-generate', function() {
                const $button = $(this);
                const $options = $button.siblings('.softworld-ai-options');
                
                $options.slideToggle();
                $button.text($options.is(':visible') ? 'Hide Options' : 'Generate SEO ' + $button.data('type'));
            });

            // Cancel generation
            $(document).on('click', '.softworld-cancel-generate', function() {
                const $options = $(this).closest('.softworld-ai-options');
                const $button = $options.siblings('.softworld-ai-generate');
                
                $options.slideUp();
                $button.text('Generate SEO ' + $button.data('type'));
            });

            // Generate content
            $(document).on('click', '.softworld-generate-now', function() {
                const $button = $(this);
                const $controls = $button.closest('.softworld-ai-controls');
                const $generateBtn = $controls.find('.softworld-ai-generate');
                const type = $generateBtn.data('type');
                
                self.generateContent(type, $controls);
            });

            // Auto-suggest on content change (if enabled)
            if (window.softworldAISettings && window.softworldAISettings.autoGenerate) {
                let contentTimeout;
                
                $('#content').on('input', function() {
                    clearTimeout(contentTimeout);
                    contentTimeout = setTimeout(() => {
                        self.showAutoSuggestions();
                    }, 2000);
                });
            }
        },

        // Generate AI content
        generateContent: function(type, $controls) {
            const $status = $controls.find('.softworld-ai-status');
            const $button = $controls.find('.softworld-generate-now');
            const keywords = $controls.find('.softworld-target-keywords').val();
            const tone = $controls.find('.softworld-tone-select').val();
            
            // Get content
            let content = '';
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                content = tinyMCE.activeEditor.getContent();
            } else {
                content = $('#content').val();
            }
            
            // Add title to content
            const title = $('#title').val();
            if (title) {
                content = title + '\n\n' + content;
            }

            if (!content.trim()) {
                $status.html('<span class="error">Please add some content first.</span>');
                return;
            }

            // Show loading state
            $button.prop('disabled', true).text('Generating...');
            $status.html('<span class="loading">Generating AI content...</span>');

            const data = {
                action: 'softworld_generate_seo_' + type,
                nonce: window.softworldAISettings ? window.softworldAISettings.nonce : '',
                content: content,
                post_type: $('#post_type').val() || 'post',
                target_keywords: keywords,
                tone: tone
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        const fieldId = type === 'title' ? '#softworld_ai_seo_title' : '#softworld_ai_seo_description';
                        const $field = $(fieldId);
                        
                        // Insert generated content
                        $field.val(response.data[type]);
                        
                        // Trigger input event for character counter
                        $field.trigger('input');
                        
                        // Show success message with character count
                        const charCount = response.data.character_count;
                        const isOptimal = type === 'description' ? 
                            (charCount >= 150 && charCount <= 160) : 
                            (charCount <= 60);
                        
                        const statusClass = isOptimal ? 'success' : 'warning';
                        const statusMsg = isOptimal ? 'Generated successfully!' : 
                            (type === 'description' ? 
                                'Consider keeping between 150-160 characters' : 
                                'Consider keeping under 60 characters');
                        
                        $status.html(`<span class="${statusClass}">${statusMsg} (${charCount} characters)</span>`);
                        
                        // Hide options
                        $controls.find('.softworld-ai-options').slideUp();
                        
                    } else {
                        $status.html('<span class="error">Error: ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span class="error">Network error occurred.</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate');
                }
            });
        },

        // Show auto suggestions
        showAutoSuggestions: function() {
            // Implementation for auto suggestions
            // This would analyze the content and show suggestions
        },

        // Initialize bulk generation features
        initBulkGeneration: function() {
            if ($('#load-bulk-posts').length === 0) return;

            this.bindBulkEvents();
        },

        // Bind bulk generation events
        bindBulkEvents: function() {
            const self = this;

            // Load posts for bulk generation
            $('#load-bulk-posts').on('click', function() {
                const postType = $('#bulk-post-type').val();
                const status = $('#bulk-status').val();
                
                self.loadBulkPosts(postType, status);
            });

            // Select/deselect all posts
            $('#select-all-posts').on('click', function() {
                $('#bulk-posts-list input[type="checkbox"]').prop('checked', true);
                self.updateBulkGenerationButton();
            });

            $('#deselect-all-posts').on('click', function() {
                $('#bulk-posts-list input[type="checkbox"]').prop('checked', false);
                self.updateBulkGenerationButton();
            });

            // Update generate button when selection changes
            $(document).on('change', '#bulk-posts-list input[type="checkbox"]', function() {
                self.updateBulkGenerationButton();
            });

            // Start bulk generation
            $('#start-bulk-generation').on('click', function() {
                self.startBulkGeneration();
            });
        },

        // Load posts for bulk generation
        loadBulkPosts: function(postType, status) {
            const $button = $('#load-bulk-posts');
            const $container = $('#bulk-posts-container');
            const $list = $('#bulk-posts-list');

            $button.prop('disabled', true).text('Loading...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'softworld_load_bulk_posts',
                    nonce: window.softworldAISettings ? window.softworldAISettings.nonce : '',
                    post_type: postType,
                    status: status
                },
                success: function(response) {
                    if (response.success && response.data.posts) {
                        let html = '';
                        
                        response.data.posts.forEach(function(post) {
                            const missingTitle = !post.seo_title ? 'missing-title' : '';
                            const missingDesc = !post.seo_description ? 'missing-desc' : '';
                            
                            html += `
                                <div class="bulk-post-item ${missingTitle} ${missingDesc}">
                                    <input type="checkbox" value="${post.ID}" data-post-id="${post.ID}">
                                    <div class="bulk-post-info">
                                        <div class="bulk-post-title">${post.post_title}</div>
                                        <div class="bulk-post-meta">
                                            ID: ${post.ID} | 
                                            SEO Title: ${post.seo_title ? '✓' : '✗'} | 
                                            SEO Description: ${post.seo_description ? '✓' : '✗'}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $list.html(html);
                        $container.show();
                        $('.bulk-options').show();
                        
                        // Auto-select posts with missing data
                        if (status === 'missing') {
                            $('#bulk-posts-list input[type="checkbox"]').prop('checked', true);
                        }
                        
                        this.updateBulkGenerationButton();
                        
                    } else {
                        $list.html('<p>No posts found.</p>');
                        $container.show();
                    }
                },
                error: function() {
                    alert('Error loading posts.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Load Posts');
                }
            });
        },

        // Update bulk generation button state
        updateBulkGenerationButton: function() {
            const selectedCount = $('#bulk-posts-list input[type="checkbox"]:checked').length;
            const $button = $('#start-bulk-generation');
            
            if (selectedCount > 0) {
                $button.prop('disabled', false).text(`Generate for ${selectedCount} posts`);
            } else {
                $button.prop('disabled', true).text('Start Generation');
            }
        },

        // Start bulk generation process
        startBulkGeneration: function() {
            const selectedPosts = [];
            $('#bulk-posts-list input[type="checkbox"]:checked').each(function() {
                selectedPosts.push($(this).val());
            });

            if (selectedPosts.length === 0) {
                alert('Please select at least one post.');
                return;
            }

            const generateTitles = $('#bulk-generate-titles').is(':checked');
            const generateDescriptions = $('#bulk-generate-descriptions').is(':checked');
            const autoSave = $('#bulk-auto-save').is(':checked');
            const tone = $('#bulk-tone').val();

            if (!generateTitles && !generateDescriptions) {
                alert('Please select at least one option (titles or descriptions).');
                return;
            }

            // Show progress section
            const $progress = $('#bulk-progress');
            const $progressFill = $('.progress-fill');
            const $progressText = $('.progress-text');
            const $results = $('#bulk-results');

            $progress.show();
            $progressFill.css('width', '0%');
            $progressText.text(`0 / ${selectedPosts.length}`);
            $results.html('');

            // Disable controls
            $('.bulk-options input, .bulk-options select, .bulk-options button').prop('disabled', true);

            // Process posts in batches
            this.processBulkPosts(selectedPosts, 0, {
                generateTitles,
                generateDescriptions,
                autoSave,
                tone
            });
        },

        // Process bulk posts
        processBulkPosts: function(postIds, currentIndex, options) {
            const self = this;
            const total = postIds.length;
            
            if (currentIndex >= total) {
                // All done
                $('.bulk-options input, .bulk-options select, .bulk-options button').prop('disabled', false);
                $('#bulk-results').append('<div class="notice notice-success"><p>Bulk generation completed!</p></div>');
                return;
            }

            const postId = postIds[currentIndex];
            const progress = ((currentIndex + 1) / total) * 100;
            
            $('.progress-fill').css('width', progress + '%');
            $('.progress-text').text(`${currentIndex + 1} / ${total}`);

            // Process current post
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'softworld_generate_bulk_seo',
                    nonce: window.softworldAISettings ? window.softworldAISettings.nonce : '',
                    post_ids: [postId],
                    generate_titles: options.generateTitles,
                    generate_descriptions: options.generateDescriptions,
                    auto_save: options.autoSave,
                    tone: options.tone
                },
                success: function(response) {
                    const $results = $('#bulk-results');
                    
                    if (response.success && response.data.results.length > 0) {
                        const result = response.data.results[0];
                        let resultHtml = `<div class="bulk-result-item success">
                            <strong>Post ${postId}:</strong> `;
                        
                        if (result.title) {
                            resultHtml += `Title generated (${result.title.length} chars) `;
                        }
                        if (result.description) {
                            resultHtml += `Description generated (${result.description.length} chars)`;
                        }
                        
                        resultHtml += '</div>';
                        $results.append(resultHtml);
                    } else {
                        $results.append(`<div class="bulk-result-item error">
                            <strong>Post ${postId}:</strong> Failed to generate content
                        </div>`);
                    }

                    // Process next post after a short delay
                    setTimeout(() => {
                        self.processBulkPosts(postIds, currentIndex + 1, options);
                    }, 1000);
                },
                error: function() {
                    $('#bulk-results').append(`<div class="bulk-result-item error">
                        <strong>Post ${postId}:</strong> Network error
                    </div>`);
                    
                    // Continue with next post
                    setTimeout(() => {
                        self.processBulkPosts(postIds, currentIndex + 1, options);
                    }, 1000);
                }
            });
        },

        // Initialize settings page features
        initSettingsPage: function() {
            if ($('#softworld_ai_seo_openai_api_key').length === 0) return;

            // Already handled in PHP template
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        SoftworldAISEO.init();
    });

    // Add CSS styles
    const styles = `
        <style>
        .softworld-ai-field-container {
            position: relative;
        }
        
        .softworld-ai-controls {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .softworld-ai-generate {
            background: #0073aa !important;
            color: white !important;
            border: none !important;
            border-radius: 3px;
        }
        
        .softworld-ai-generate:hover {
            background: #005a87 !important;
        }
        
        .softworld-ai-generate .dashicons {
            margin-right: 5px;
        }
        
        .softworld-ai-options {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .softworld-ai-options label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .softworld-ai-options input,
        .softworld-ai-options select {
            margin-left: 5px;
            min-width: 150px;
        }
        
        .softworld-ai-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 3px;
        }
        
        .softworld-ai-status .loading {
            color: #0073aa;
            animation: pulse 1.5s infinite;
        }
        
        .softworld-ai-status .success {
            color: #46b450;
        }
        
        .softworld-ai-status .error {
            color: #dc3232;
        }
        
        .softworld-ai-status .warning {
            color: #ffb900;
        }
        
        .softworld-char-counter {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .softworld-char-counter.optimal {
            color: #46b450;
            font-weight: bold;
        }
        
        .softworld-char-counter.warning {
            color: #dc3232;
            font-weight: bold;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .bulk-post-item {
            display: flex;
            align-items: flex-start;
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .bulk-post-item:hover {
            background-color: #f9f9f9;
        }
        
        .bulk-post-item.missing-title {
            border-left: 3px solid #ffb900;
        }
        
        .bulk-post-item.missing-desc {
            border-right: 3px solid #dc3232;
        }
        
        .bulk-post-item.missing-title.missing-desc {
            border-left: 3px solid #dc3232;
            border-right: 3px solid #dc3232;
        }
        
        .bulk-post-item input[type="checkbox"] {
            margin-right: 12px;
            margin-top: 3px;
        }
        
        .bulk-post-info {
            flex: 1;
        }
        
        .bulk-post-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #23282d;
        }
        
        .bulk-post-meta {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }
        
        .bulk-result-item {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 3px;
        }
        
        .bulk-result-item.success {
            background: #d4edda;
            border-left: 4px solid #46b450;
        }
        
        .bulk-result-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3232;
        }
        
        .progress-bar {
            width: 100%;
            height: 24px;
            background-color: #f1f1f1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #005a87);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 12px;
        }
        
        .progress-text {
            text-align: center;
            font-weight: 600;
            margin: 10px 0;
            color: #23282d;
        }
        
        #bulk-results {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        
        .bulk-actions {
            margin-bottom: 15px;
        }
        
        .bulk-actions button {
            margin-right: 10px;
        }
        </style>
    `;
    
    $('head').append(styles);

})(jQuery);