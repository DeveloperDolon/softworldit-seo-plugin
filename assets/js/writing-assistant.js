jQuery(document).ready(function($) {
    'use strict';

    // Tab Switching
    $('.assistant-tab').on('click', function() {
        const tabId = $(this).data('tab');
        
        $('.assistant-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-panel').removeClass('active');
        $('#' + tabId + '-panel').addClass('active');
    });

    // Generate Content
    $('#btn-generate-content').on('click', function() {
        const topic = $('#generate-topic').val().trim();
        const contentType = $('#generate-type').val();
        const wordCount = $('#generate-words').val();
        const tone = $('#generate-tone').val();
        const keywords = $('#generate-keywords').val();

        if (!topic) {
            showNotification('error', softworldWritingAssistant.i18n.topicRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_generate_content',
                nonce: softworldWritingAssistant.nonce,
                topic: topic,
                content_type: contentType,
                word_count: wordCount,
                tone: tone,
                keywords: keywords
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayResult('generate', response.data.content, response.data.word_count);
                    showNotification('success', softworldWritingAssistant.i18n.generated);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldWritingAssistant.i18n.error);
            }
        });
    });

    // Expand Content
    $('#btn-expand-content').on('click', function() {
        const content = $('#expand-content').val().trim();
        const expansionType = $('#expand-type').val();
        const wordCount = $('#expand-words').val();
        const tone = $('#expand-tone').val();

        if (!content) {
            showNotification('error', softworldWritingAssistant.i18n.contentRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_expand_content',
                nonce: softworldWritingAssistant.nonce,
                content: content,
                expansion_type: expansionType,
                word_count: wordCount,
                tone: tone
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayResult('expand', response.data.expanded_content, response.data.word_count);
                    showNotification('success', softworldWritingAssistant.i18n.expanded);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldWritingAssistant.i18n.error);
            }
        });
    });

    // Rewrite Content
    $('#btn-rewrite-content').on('click', function() {
        const content = $('#rewrite-content').val().trim();
        const rewriteStyle = $('#rewrite-style').val();
        const tone = $('#rewrite-tone').val();

        if (!content) {
            showNotification('error', softworldWritingAssistant.i18n.contentRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_rewrite_content',
                nonce: softworldWritingAssistant.nonce,
                content: content,
                rewrite_style: rewriteStyle,
                tone: tone
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayRewriteComparison(content, response.data.rewritten_content, response.data.word_count);
                    showNotification('success', softworldWritingAssistant.i18n.rewritten);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldWritingAssistant.i18n.error);
            }
        });
    });

    // Summarize Content
    $('#btn-summarize-content').on('click', function() {
        const content = $('#summarize-content').val().trim();
        const summaryLength = $('#summary-length').val();
        const format = $('#summary-format').val();

        if (!content) {
            showNotification('error', softworldWritingAssistant.i18n.contentRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_summarize_content',
                nonce: softworldWritingAssistant.nonce,
                content: content,
                summary_length: summaryLength,
                format: format
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayResult('summarize', response.data.summary, response.data.word_count);
                    showNotification('success', softworldWritingAssistant.i18n.summarized);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldWritingAssistant.i18n.error);
            }
        });
    });

    // Generate Outline
    $('#btn-generate-outline').on('click', function() {
        const topic = $('#outline-topic').val().trim();
        const sections = $('#outline-sections').val();
        const detailLevel = $('#outline-detail').val();

        if (!topic) {
            showNotification('error', softworldWritingAssistant.i18n.topicRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_generate_outline',
                nonce: softworldWritingAssistant.nonce,
                topic: topic,
                sections: sections,
                detail_level: detailLevel
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayOutline(response.data.outline, response.data.sections_count);
                    showNotification('success', softworldWritingAssistant.i18n.outlineGenerated);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldWritingAssistant.i18n.error);
            }
        });
    });

    // Copy Button Handler
    $(document).on('click', '.copy-btn', function() {
        const resultContainer = $(this).closest('.result-container, .comparison-container');
        let textToCopy = '';

        if (resultContainer.find('.result-content').length) {
            textToCopy = resultContainer.find('.result-content').text();
        } else if (resultContainer.find('.rewritten-content').length) {
            textToCopy = resultContainer.find('.rewritten-content').text();
        } else if (resultContainer.find('.outline-content').length) {
            textToCopy = resultContainer.find('.outline-content').text();
        }

        copyToClipboard(textToCopy);
        showNotification('success', softworldWritingAssistant.i18n.copied);
    });

    // Regenerate Button Handler
    $(document).on('click', '.regenerate-btn', function() {
        const panel = $(this).closest('.tab-panel');
        const tabId = panel.attr('id').replace('-panel', '');
        
        $('#btn-' + tabId.replace('_', '-') + '-content, #btn-' + tabId + '-content').trigger('click');
    });

    // Append to Original Button
    $(document).on('click', '.append-btn', function() {
        const expandedContent = $('#expand-result .result-content').text();
        const originalContent = $('#expand-content').val();
        
        $('#expand-content').val(originalContent + '\n\n' + expandedContent);
        showNotification('success', softworldWritingAssistant.i18n.appended);
    });

    // Replace Original Button
    $(document).on('click', '.replace-btn', function() {
        const rewrittenContent = $('#rewrite-result .rewritten-content').text();
        $('#rewrite-content').val(rewrittenContent);
        showNotification('success', softworldWritingAssistant.i18n.replaced);
    });

    // Helper Functions
    function showLoading() {
        $('.loading-overlay').fadeIn(200);
    }

    function hideLoading() {
        $('.loading-overlay').fadeOut(200);
    }

    function displayResult(type, content, wordCount) {
        const resultContainer = $('#' + type + '-result');
        resultContainer.find('.result-content').html(formatContent(content));
        resultContainer.find('.word-count').text(wordCount + ' words');
        resultContainer.fadeIn(300);
    }

    function displayRewriteComparison(original, rewritten, wordCount) {
        $('#rewrite-result .original-content').html(formatContent(original));
        $('#rewrite-result .rewritten-content').html(formatContent(rewritten));
        $('#rewrite-result .word-count').text(wordCount + ' words');
        $('#rewrite-result').fadeIn(300);
    }

    function displayOutline(outline, sectionsCount) {
        let html = '<div class="outline-tree">';
        
        $.each(outline, function(key, section) {
            html += '<div class="outline-section">';
            html += '<h4>' + key + '. ' + section.title + '</h4>';
            
            if (section.subsections && section.subsections.length > 0) {
                html += '<ul class="outline-subsections">';
                $.each(section.subsections, function(index, subsection) {
                    html += '<li>' + key + '.' + (index + 1) + ' ' + subsection + '</li>';
                });
                html += '</ul>';
            }
            
            html += '</div>';
        });
        
        html += '</div>';
        
        $('#outline-result .outline-content').html(html);
        $('#outline-result .section-count').text(sectionsCount + ' sections');
        $('#outline-result').fadeIn(300);
    }

    function formatContent(content) {
        // Convert line breaks to <br> for display
        return content.replace(/\n/g, '<br>');
    }

    function copyToClipboard(text) {
        const tempInput = $('<textarea>');
        $('body').append(tempInput);
        tempInput.val(text).select();
        document.execCommand('copy');
        tempInput.remove();
    }

    function showNotification(type, message) {
        const notification = $('<div class="assistant-notification ' + type + '">' + message + '</div>');
        $('.writing-assistant').append(notification);
        
        notification.fadeIn(300);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});