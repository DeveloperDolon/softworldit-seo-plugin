<?php
/**
 * AI Writing Assistant Template
 */

if (!defined('ABSPATH')) exit;

$ai_available = softworld_is_ai_available();
?>

<div class="wrap softworld-writing-assistant">
    <h1><?php _e('AI Writing Assistant', 'softworld-ai-seo'); ?></h1>
    
    <div class="writing-assistant-container">
        <!-- Tabs Navigation -->
        <div class="assistant-tabs">
            <button class="assistant-tab active" data-tab="generate"><?php _e('Generate Content', 'softworld-ai-seo'); ?></button>
            <button class="assistant-tab" data-tab="expand"><?php _e('Expand Content', 'softworld-ai-seo'); ?></button>
            <button class="assistant-tab" data-tab="rewrite"><?php _e('Rewrite', 'softworld-ai-seo'); ?></button>
            <button class="assistant-tab" data-tab="summarize"><?php _e('Summarize', 'softworld-ai-seo'); ?></button>
            <button class="assistant-tab" data-tab="outline"><?php _e('Create Outline', 'softworld-ai-seo'); ?></button>
        </div>

        <!-- Tab Content -->
        <div class="assistant-content">
            
            <!-- Generate Content Tab -->
            <div class="tab-panel active" id="generate-panel">
                <h2><?php _e('Generate New Content', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Create original content from scratch using AI.', 'softworld-ai-seo'); ?></p>
                
                <div class="assistant-form">
                    <div class="form-row">
                        <label for="generate-topic"><?php _e('Topic/Title', 'softworld-ai-seo'); ?> <span class="required">*</span></label>
                        <textarea id="generate-topic" rows="3" placeholder="<?php _e('Enter your topic or title...', 'softworld-ai-seo'); ?>"></textarea>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="generate-type"><?php _e('Content Type', 'softworld-ai-seo'); ?></label>
                            <select id="generate-type">
                                <option value="paragraph"><?php _e('Paragraph', 'softworld-ai-seo'); ?></option>
                                <option value="blog_post"><?php _e('Blog Post', 'softworld-ai-seo'); ?></option>
                                <option value="article"><?php _e('Article', 'softworld-ai-seo'); ?></option>
                                <option value="product_description"><?php _e('Product Description', 'softworld-ai-seo'); ?></option>
                                <option value="introduction"><?php _e('Introduction', 'softworld-ai-seo'); ?></option>
                                <option value="conclusion"><?php _e('Conclusion', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="generate-words"><?php _e('Word Count', 'softworld-ai-seo'); ?></label>
                            <input type="number" id="generate-words" value="300" min="50" max="2000" step="50">
                        </div>
                        
                        <div class="form-row">
                            <label for="generate-tone"><?php _e('Tone', 'softworld-ai-seo'); ?></label>
                            <select id="generate-tone">
                                <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                                <option value="conversational"><?php _e('Conversational', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label for="generate-keywords"><?php _e('Keywords (Optional)', 'softworld-ai-seo'); ?></label>
                        <input type="text" id="generate-keywords" placeholder="<?php _e('Comma separated keywords...', 'softworld-ai-seo'); ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-primary" id="btn-generate-content">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Generate Content', 'softworld-ai-seo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="result-container" id="generate-result" style="display: none;">
                    <div class="result-header">
                        <h3><?php _e('Generated Content', 'softworld-ai-seo'); ?></h3>
                        <div class="result-stats">
                            <span class="word-count"></span>
                        </div>
                    </div>
                    <div class="result-content" contenteditable="true"></div>
                    <div class="result-actions">
                        <button type="button" class="button copy-btn"><?php _e('Copy', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button insert-btn"><?php _e('Insert to Editor', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button regenerate-btn"><?php _e('Regenerate', 'softworld-ai-seo'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Expand Content Tab -->
            <div class="tab-panel" id="expand-panel">
                <h2><?php _e('Expand Existing Content', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Add more details, examples, or context to your existing content.', 'softworld-ai-seo'); ?></p>
                
                <div class="assistant-form">
                    <div class="form-row">
                        <label for="expand-content"><?php _e('Content to Expand', 'softworld-ai-seo'); ?> <span class="required">*</span></label>
                        <textarea id="expand-content" rows="8" placeholder="<?php _e('Paste your content here...', 'softworld-ai-seo'); ?>"></textarea>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="expand-type"><?php _e('Expansion Type', 'softworld-ai-seo'); ?></label>
                            <select id="expand-type">
                                <option value="elaborate"><?php _e('Elaborate More', 'softworld-ai-seo'); ?></option>
                                <option value="add_examples"><?php _e('Add Examples', 'softworld-ai-seo'); ?></option>
                                <option value="add_context"><?php _e('Add Context', 'softworld-ai-seo'); ?></option>
                                <option value="add_details"><?php _e('Add Details', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="expand-words"><?php _e('Additional Words', 'softworld-ai-seo'); ?></label>
                            <input type="number" id="expand-words" value="200" min="50" max="1000" step="50">
                        </div>
                        
                        <div class="form-row">
                            <label for="expand-tone"><?php _e('Tone', 'softworld-ai-seo'); ?></label>
                            <select id="expand-tone">
                                <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-primary" id="btn-expand-content">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Expand Content', 'softworld-ai-seo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="result-container" id="expand-result" style="display: none;">
                    <div class="result-header">
                        <h3><?php _e('Expanded Content', 'softworld-ai-seo'); ?></h3>
                        <div class="result-stats">
                            <span class="word-count"></span>
                        </div>
                    </div>
                    <div class="result-content" contenteditable="true"></div>
                    <div class="result-actions">
                        <button type="button" class="button copy-btn"><?php _e('Copy', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button append-btn"><?php _e('Append to Original', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button regenerate-btn"><?php _e('Regenerate', 'softworld-ai-seo'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Rewrite Content Tab -->
            <div class="tab-panel" id="rewrite-panel">
                <h2><?php _e('Rewrite Content', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Improve, simplify, or change the style of your existing content.', 'softworld-ai-seo'); ?></p>
                
                <div class="assistant-form">
                    <div class="form-row">
                        <label for="rewrite-content"><?php _e('Content to Rewrite', 'softworld-ai-seo'); ?> <span class="required">*</span></label>
                        <textarea id="rewrite-content" rows="8" placeholder="<?php _e('Paste your content here...', 'softworld-ai-seo'); ?>"></textarea>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="rewrite-style"><?php _e('Rewrite Style', 'softworld-ai-seo'); ?></label>
                            <select id="rewrite-style">
                                <option value="improve"><?php _e('Improve Quality', 'softworld-ai-seo'); ?></option>
                                <option value="simplify"><?php _e('Simplify Language', 'softworld-ai-seo'); ?></option>
                                <option value="professional"><?php _e('Make Professional', 'softworld-ai-seo'); ?></option>
                                <option value="casual"><?php _e('Make Casual', 'softworld-ai-seo'); ?></option>
                                <option value="engaging"><?php _e('Make Engaging', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="rewrite-tone"><?php _e('Tone', 'softworld-ai-seo'); ?></label>
                            <select id="rewrite-tone">
                                <option value="professional"><?php _e('Professional', 'softworld-ai-seo'); ?></option>
                                <option value="casual"><?php _e('Casual', 'softworld-ai-seo'); ?></option>
                                <option value="friendly"><?php _e('Friendly', 'softworld-ai-seo'); ?></option>
                                <option value="authoritative"><?php _e('Authoritative', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-primary" id="btn-rewrite-content">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Rewrite Content', 'softworld-ai-seo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="comparison-container" id="rewrite-result" style="display: none;">
                    <div class="comparison-view">
                        <div class="comparison-column">
                            <h3><?php _e('Original', 'softworld-ai-seo'); ?></h3>
                            <div class="original-content"></div>
                        </div>
                        <div class="comparison-column">
                            <h3><?php _e('Rewritten', 'softworld-ai-seo'); ?></h3>
                            <div class="result-stats">
                                <span class="word-count"></span>
                            </div>
                            <div class="rewritten-content" contenteditable="true"></div>
                        </div>
                    </div>
                    <div class="result-actions">
                        <button type="button" class="button copy-btn"><?php _e('Copy', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button replace-btn"><?php _e('Replace Original', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button regenerate-btn"><?php _e('Regenerate', 'softworld-ai-seo'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Summarize Content Tab -->
            <div class="tab-panel" id="summarize-panel">
                <h2><?php _e('Summarize Content', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Create a concise summary of your long-form content.', 'softworld-ai-seo'); ?></p>
                
                <div class="assistant-form">
                    <div class="form-row">
                        <label for="summarize-content"><?php _e('Content to Summarize', 'softworld-ai-seo'); ?> <span class="required">*</span></label>
                        <textarea id="summarize-content" rows="10" placeholder="<?php _e('Paste your content here...', 'softworld-ai-seo'); ?>"></textarea>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="summary-length"><?php _e('Summary Length', 'softworld-ai-seo'); ?></label>
                            <select id="summary-length">
                                <option value="short"><?php _e('Short (2-3 sentences)', 'softworld-ai-seo'); ?></option>
                                <option value="medium" selected><?php _e('Medium (1 paragraph)', 'softworld-ai-seo'); ?></option>
                                <option value="long"><?php _e('Long (2-3 paragraphs)', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label for="summary-format"><?php _e('Format', 'softworld-ai-seo'); ?></label>
                            <select id="summary-format">
                                <option value="paragraph"><?php _e('Paragraph', 'softworld-ai-seo'); ?></option>
                                <option value="bullet_points"><?php _e('Bullet Points', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-primary" id="btn-summarize-content">
                            <span class="dashicons dashicons-editor-contract"></span>
                            <?php _e('Summarize Content', 'softworld-ai-seo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="result-container" id="summarize-result" style="display: none;">
                    <div class="result-header">
                        <h3><?php _e('Summary', 'softworld-ai-seo'); ?></h3>
                        <div class="result-stats">
                            <span class="word-count"></span>
                        </div>
                    </div>
                    <div class="result-content" contenteditable="true"></div>
                    <div class="result-actions">
                        <button type="button" class="button copy-btn"><?php _e('Copy', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button regenerate-btn"><?php _e('Regenerate', 'softworld-ai-seo'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Create Outline Tab -->
            <div class="tab-panel" id="outline-panel">
                <h2><?php _e('Create Content Outline', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Generate a structured outline for your content.', 'softworld-ai-seo'); ?></p>
                
                <div class="assistant-form">
                    <div class="form-row">
                        <label for="outline-topic"><?php _e('Topic', 'softworld-ai-seo'); ?> <span class="required">*</span></label>
                        <textarea id="outline-topic" rows="3" placeholder="<?php _e('Enter your topic or title...', 'softworld-ai-seo'); ?>"></textarea>
                    </div>
                    
                    <div class="form-row-group">
                        <div class="form-row">
                            <label for="outline-sections"><?php _e('Number of Sections', 'softworld-ai-seo'); ?></label>
                            <input type="number" id="outline-sections" value="5" min="3" max="10" step="1">
                        </div>
                        
                        <div class="form-row">
                            <label for="outline-detail"><?php _e('Detail Level', 'softworld-ai-seo'); ?></label>
                            <select id="outline-detail">
                                <option value="basic"><?php _e('Basic', 'softworld-ai-seo'); ?></option>
                                <option value="detailed" selected><?php _e('Detailed', 'softworld-ai-seo'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-primary" id="btn-generate-outline">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('Generate Outline', 'softworld-ai-seo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="result-container" id="outline-result" style="display: none;">
                    <div class="result-header">
                        <h3><?php _e('Generated Outline', 'softworld-ai-seo'); ?></h3>
                        <div class="result-stats">
                            <span class="section-count"></span>
                        </div>
                    </div>
                    <div class="outline-content"></div>
                    <div class="result-actions">
                        <button type="button" class="button copy-btn"><?php _e('Copy', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button expand-outline-btn"><?php _e('Expand Into Full Content', 'softworld-ai-seo'); ?></button>
                        <button type="button" class="button regenerate-btn"><?php _e('Regenerate', 'softworld-ai-seo'); ?></button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <span class="dashicons dashicons-update spin"></span>
            <p><?php _e('AI is generating content...', 'softworld-ai-seo'); ?></p>
        </div>
    </div>
</div>