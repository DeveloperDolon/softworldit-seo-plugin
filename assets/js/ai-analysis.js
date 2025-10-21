jQuery(document).ready(function($) {
    'use strict';

    // ========== SEO AUDIT FUNCTIONALITY ==========
    
    // Enable audit button when post is selected
    $('#audit-post-selector').on('change', function() {
        $('#btn-run-audit').prop('disabled', !$(this).val());
    });

    // Run SEO Audit
    $('#btn-run-audit').on('click', function() {
        const postId = $('#audit-post-selector').val();
        const url = $('#audit-post-selector option:selected').data('url');

        if (!postId) return;

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_run_seo_audit',
                nonce: softworldAIAnalysis.nonce,
                post_id: postId
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayAuditResults(response.data, url);
                    updateAuditHistory();
                    showNotification('success', softworldAIAnalysis.i18n.auditComplete);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldAIAnalysis.i18n.error);
            }
        });
    });

    // Display audit results
    function displayAuditResults(audit, url) {
        $('#audit-results').fadeIn(300);
        
        // Update score
        const score = audit.score || 0;
        $('.score-value').text(score);
        updateScoreColor(score);
        
        // Update URL
        $('#audited-url').attr('href', url).text(url);
        
        // Display critical errors
        displayIssues('#critical-errors-list', audit.critical_errors, 'critical');
        $('#critical-count').text(audit.critical_errors.length);
        
        // Display warnings
        displayIssues('#warnings-list', audit.warnings, 'warning');
        $('#warnings-count').text(audit.warnings.length);
        
        // Display recommendations
        displayIssues('#recommendations-list', audit.recommendations, 'recommendation');
        $('#recommendations-count').text(audit.recommendations.length);
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#audit-results').offset().top - 50
        }, 500);
    }

    // Display issues function
    function displayIssues(container, issues, type) {
        const $container = $(container);
        $container.empty();

        if (!issues || issues.length === 0) {
            $container.html('<p class="no-issues">' + softworldAIAnalysis.i18n.noIssues + '</p>');
            return;
        }

        issues.forEach(function(issue) {
            const html = `
                <div class="issue-item ${type}">
                    <div class="issue-icon">
                        <span class="dashicons dashicons-${getIssueIcon(type)}"></span>
                    </div>
                    <div class="issue-content">
                        <h4>${issue.issue || issue.area}</h4>
                        <p>${issue.action || issue.suggestion || issue.tip}</p>
                    </div>
                </div>
            `;
            $container.append(html);
        });
    }

    // Get issue icon
    function getIssueIcon(type) {
        const icons = {
            'critical': 'warning',
            'warning': 'info',
            'recommendation': 'lightbulb'
        };
        return icons[type] || 'info';
    }

    // Update score color
    function updateScoreColor(score) {
        const $circle = $('.score-circle');
        $circle.removeClass('score-low score-medium score-high');

        if (score >= 80) {
            $circle.addClass('score-high');
        } else if (score >= 50) {
            $circle.addClass('score-medium');
        } else {
            $circle.addClass('score-low');
        }
    }

    // Re-run audit
    $('#btn-rerun-audit').on('click', function() {
        $('#btn-run-audit').trigger('click');
    });

    // Export audit report
    $('#btn-export-audit').on('click', function() {
        const postTitle = $('#audit-post-selector option:selected').text();
        const score = $('.score-value').text();
        const url = $('#audited-url').text();
        
        let report = 'SEO AUDIT REPORT\n';
        report += '==================\n\n';
        report += 'Page: ' + postTitle + '\n';
        report += 'URL: ' + url + '\n';
        report += 'Score: ' + score + '/100\n';
        report += 'Date: ' + new Date().toLocaleString() + '\n\n';
        
        // Add critical errors
        report += 'CRITICAL ERRORS\n';
        report += '---------------\n';
        $('#critical-errors-list .issue-item').each(function() {
            const title = $(this).find('h4').text();
            const desc = $(this).find('p').text();
            report += '- ' + title + '\n  ' + desc + '\n\n';
        });
        
        // Add warnings
        report += '\nWARNINGS\n';
        report += '--------\n';
        $('#warnings-list .issue-item').each(function() {
            const title = $(this).find('h4').text();
            const desc = $(this).find('p').text();
            report += '- ' + title + '\n  ' + desc + '\n\n';
        });
        
        // Add recommendations
        report += '\nRECOMMENDATIONS\n';
        report += '---------------\n';
        $('#recommendations-list .issue-item').each(function() {
            const title = $(this).find('h4').text();
            const desc = $(this).find('p').text();
            report += '- ' + title + '\n  ' + desc + '\n\n';
        });
        
        downloadTextFile(report, 'seo-audit-report-' + Date.now() + '.txt');
    });

    // Update audit history
    function updateAuditHistory() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_get_audit_history',
                nonce: softworldAIAnalysis.nonce,
                limit: 5
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    const $list = $('#audit-history-list');
                    $list.empty();
                    
                    response.data.forEach(function(audit) {
                        const path = getUrlPath(audit.url);
                        const timeAgo = getTimeAgo(audit.audit_date);
                        const scoreClass = getScoreClass(audit.score);
                        
                        const html = `
                            <div class="history-item" data-url="${audit.url}">
                                <div class="history-score ${scoreClass}" data-score="${audit.score}">
                                    ${audit.score}
                                </div>
                                <div class="history-details">
                                    <div class="history-url">${path}</div>
                                    <div class="history-date">${timeAgo}</div>
                                </div>
                            </div>
                        `;
                        $list.append(html);
                    });
                    
                    updateAuditStats(response.data);
                }
            }
        });
    }

    // Update audit stats
    function updateAuditStats(audits) {
        const totalAudits = audits.length;
        const avgScore = audits.reduce((sum, a) => sum + parseInt(a.score), 0) / totalAudits;
        
        $('#total-audits').text(totalAudits);
        $('#avg-score').text(Math.round(avgScore));
    }

    // ========== KEYWORD TRACKER FUNCTIONALITY ==========
    
    // Tab switching
    $('.tracker-tab').on('click', function() {
        const tabId = $(this).data('tab');
        
        $('.tracker-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.tracker-panel').removeClass('active');
        $('#' + tabId + '-panel').addClass('active');
    });

    // Generate keyword suggestions
    $('#btn-suggest-keywords').on('click', function() {
        const topic = $('#suggestion-topic').val().trim();
        const intent = $('#suggestion-intent').val();

        if (!topic) {
            showNotification('error', softworldAIAnalysis.i18n.topicRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_suggest_keywords',
                nonce: softworldAIAnalysis.nonce,
                topic: topic,
                seed_keywords: '',
                intent: intent
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayKeywordSuggestions(response.data);
                    showNotification('success', softworldAIAnalysis.i18n.suggestionsGenerated);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldAIAnalysis.i18n.error);
            }
        });
    });

    // Display keyword suggestions
    function displayKeywordSuggestions(suggestions) {
        $('#suggestions-results').fadeIn(300);
        
        // Primary keywords
        displayKeywordCategory('#primary-keywords-list', suggestions.primary_keywords, 'primary');
        
        // Long-tail keywords
        displayKeywordCategory('#longtail-keywords-list', suggestions.long_tail_keywords, 'longtail');
        
        // Question keywords
        displayKeywordCategory('#question-keywords-list', suggestions.question_keywords, 'question');
        
        // Related topics
        displayKeywordCategory('#related-topics-list', suggestions.related_topics, 'related');
        
        // Competitor keywords
        displayKeywordCategory('#competitor-keywords-list', suggestions.competitor_keywords, 'competitor');
    }

    // Display keyword category
    function displayKeywordCategory(container, keywords, type) {
        const $container = $(container);
        $container.empty();

        if (!keywords || keywords.length === 0) {
            $container.html('<p class="no-keywords">No keywords found</p>');
            return;
        }

        keywords.forEach(function(kw) {
            let html = '<div class="keyword-item">';
            html += '<div class="keyword-text">' + kw.keyword + '</div>';
            
            if (type === 'primary') {
                html += '<span class="keyword-badge difficulty-' + kw.difficulty + '">' + kw.difficulty + '</span>';
                html += '<span class="keyword-badge volume-' + kw.volume + '">' + kw.volume + '</span>';
            } else if (type === 'longtail') {
                html += '<div class="keyword-meta">' + kw.intent + '</div>';
            } else if (type === 'question') {
                html += '<span class="keyword-badge">' + kw.type + '</span>';
            } else if (type === 'related') {
                html += '<div class="keyword-meta">' + kw.connection + '</div>';
            } else if (type === 'competitor') {
                html += '<div class="keyword-meta">' + kw.opportunity + '</div>';
            }
            
            html += '<button class="button-link track-keyword" data-keyword="' + kw.keyword + '">Track</button>';
            html += '</div>';
            
            $container.append(html);
        });
    }

    // Track keyword from suggestions
    $(document).on('click', '.track-keyword', function() {
        const keyword = $(this).data('keyword');
        $('#modal-keyword').val(keyword);
        $('#add-keyword-modal').fadeIn(300);
    });

    // Enable analyze button when post is selected
    $('#analysis-post').on('change', function() {
        $('#btn-analyze-keywords').prop('disabled', !$(this).val());
    });

    // Analyze keywords
    $('#btn-analyze-keywords').on('click', function() {
        const postId = $('#analysis-post').val();
        const keywords = $('#analysis-keywords').val().trim();

        if (!postId) {
            showNotification('error', softworldAIAnalysis.i18n.postRequired);
            return;
        }

        if (!keywords) {
            showNotification('error', softworldAIAnalysis.i18n.keywordsRequired);
            return;
        }

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        // Get post content
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_post',
                post_id: postId
            },
            success: function(postData) {
                // Now analyze keywords
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'softworld_analyze_keywords',
                        nonce: softworldAIAnalysis.nonce,
                        content: postData.post_content || '',
                        target_keywords: keywords,
                        current_rankings: {}
                    },
                    success: function(response) {
                        hideLoading();
                        button.prop('disabled', false);

                        if (response.success) {
                            displayKeywordAnalysis(response.data);
                            showNotification('success', softworldAIAnalysis.i18n.analysisComplete);
                        } else {
                            showNotification('error', response.data);
                        }
                    },
                    error: function() {
                        hideLoading();
                        button.prop('disabled', false);
                        showNotification('error', softworldAIAnalysis.i18n.error);
                    }
                });
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldAIAnalysis.i18n.error);
            }
        });
    });

    // Display keyword analysis
    function displayKeywordAnalysis(analysis) {
        $('#analysis-results').fadeIn(300);
        
        // Keyword density
        displayDensityResults(analysis.density);
        
        // Keyword placement
        displayPlacementResults(analysis.placement);
        
        // Related keywords
        displayRelatedResults(analysis.related_keywords);
        
        // LSI keywords
        displayLSIResults(analysis.lsi_keywords);
        
        // Opportunities
        displayOpportunitiesResults(analysis.opportunities);
    }

    // Display density results
    function displayDensityResults(density) {
        const $container = $('#density-results');
        $container.empty();

        if (!density || density.length === 0) {
            $container.html('<p>No density data available</p>');
            return;
        }

        density.forEach(function(item) {
            const statusClass = item.status === 'good' ? 'status-good' : (item.status === 'low' ? 'status-low' : 'status-high');
            const html = `
                <div class="density-item ${statusClass}">
                    <strong>${item.keyword}</strong>
                    <span class="density-value">${item.percentage}%</span>
                    <span class="density-status">${item.status}</span>
                </div>
            `;
            $container.append(html);
        });
    }

    // Display placement results
    function displayPlacementResults(placement) {
        const $container = $('#placement-results');
        $container.empty();

        if (!placement || placement.length === 0) {
            $container.html('<p>No placement data available</p>');
            return;
        }

        placement.forEach(function(item) {
            const html = `
                <div class="placement-item">
                    <strong>${item.keyword}</strong>
                    <div class="placement-details">
                        <span class="placement-found">✓ Found in: ${item.found_in.join(', ')}</span>
                        ${item.missing_from.length > 0 ? '<span class="placement-missing">✗ Missing from: ' + item.missing_from.join(', ') + '</span>' : ''}
                    </div>
                </div>
            `;
            $container.append(html);
        });
    }

    // Display related keywords
    function displayRelatedResults(related) {
        const $container = $('#related-results');
        $container.empty();

        if (!related || related.length === 0) {
            $container.html('<p>No related keywords found</p>');
            return;
        }

        related.forEach(function(item) {
            const html = `
                <div class="related-item">
                    <strong>${item.keyword}</strong>
                    <p>${item.reason}</p>
                </div>
            `;
            $container.append(html);
        });
    }

    // Display LSI keywords
    function displayLSIResults(lsi) {
        const $container = $('#lsi-results');
        $container.empty();

        if (!lsi || lsi.length === 0) {
            $container.html('<p>No LSI keywords found</p>');
            return;
        }

        lsi.forEach(function(item) {
            const html = `
                <div class="lsi-item">
                    <strong>${item.keyword}</strong>
                    <p>${item.context}</p>
                </div>
            `;
            $container.append(html);
        });
    }

    // Display opportunities
    function displayOpportunitiesResults(opportunities) {
        const $container = $('#opportunities-results');
        $container.empty();

        if (!opportunities || opportunities.length === 0) {
            $container.html('<p>No opportunities identified</p>');
            return;
        }

        opportunities.forEach(function(item) {
            const html = `
                <div class="opportunity-item">
                    <strong>${item.keyword}</strong>
                    <p>${item.action}</p>
                </div>
            `;
            $container.append(html);
        });
    }

    // Load keyword tracking
    $('#btn-load-tracking').on('click', function() {
        const url = $('#tracking-url').val();
        const days = $('#tracking-days').val();

        const button = $(this);
        showLoading();
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_get_keyword_tracking',
                nonce: softworldAIAnalysis.nonce,
                url: url,
                days: days
            },
            success: function(response) {
                hideLoading();
                button.prop('disabled', false);

                if (response.success) {
                    displayKeywordTracking(response.data);
                    showNotification('success', softworldAIAnalysis.i18n.trackingLoaded);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                hideLoading();
                button.prop('disabled', false);
                showNotification('error', softworldAIAnalysis.i18n.error);
            }
        });
    });

    // Display keyword tracking
    function displayKeywordTracking(trackingData) {
        $('#tracking-results').fadeIn(300);
        
        if (!trackingData || trackingData.length === 0) {
            $('#tracking-chart-container').html('<p>No tracking data available</p>');
            return;
        }

        // Render chart
        renderTrackingChart(trackingData);
        
        // Render table
        renderTrackingTable(trackingData);
    }

    // Render tracking chart
    function renderTrackingChart(data) {
        const ctx = document.getElementById('keyword-tracking-chart').getContext('2d');
        
        // Destroy existing chart if any
        if (window.keywordChart) {
            window.keywordChart.destroy();
        }

        const datasets = data.map(function(item, index) {
            const colors = ['#0073aa', '#d63638', '#00a32a', '#f0b849', '#826eb4'];
            const color = colors[index % colors.length];
            
            return {
                label: item.keyword,
                data: item.history.map(h => ({ x: h.date, y: h.position })),
                borderColor: color,
                backgroundColor: color + '20',
                tension: 0.4
            };
        });

        window.keywordChart = new Chart(ctx, {
            type: 'line',
            data: { datasets: datasets },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day' }
                    },
                    y: {
                        reverse: true,
                        min: 1,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Position'
                        }
                    }
                }
            }
        });
    }

    // Render tracking table
    function renderTrackingTable(data) {
        const $tbody = $('#tracking-table-body');
        $tbody.empty();

        data.forEach(function(item) {
            const history = item.history;
            const current = history[0] ? history[0].position : 0;
            const previous = history[1] ? history[1].position : current;
            const change = previous - current;
            const trend = change > 0 ? 'up' : (change < 0 ? 'down' : 'stable');
            const trendIcon = trend === 'up' ? '↑' : (trend === 'down' ? '↓' : '→');
            const trendClass = trend === 'up' ? 'trend-up' : (trend === 'down' ? 'trend-down' : 'trend-stable');
            
            const html = `
                <tr>
                    <td><strong>${item.keyword}</strong></td>
                    <td>${current}</td>
                    <td>${previous}</td>
                    <td class="${trendClass}">${Math.abs(change)}</td>
                    <td class="${trendClass}">${trendIcon}</td>
                </tr>
            `;
            $tbody.append(html);
        });
    }

    // Add keyword modal
    $('#btn-add-keyword').on('click', function() {
        $('#add-keyword-modal').fadeIn(300);
    });

    $('.modal-close, #btn-cancel-keyword').on('click', function() {
        $('#add-keyword-modal').fadeOut(300);
    });

    // Save keyword
    $('#btn-save-keyword').on('click', function() {
        const keyword = $('#modal-keyword').val().trim();
        const url = $('#modal-url').val();
        const position = $('#modal-position').val();

        if (!keyword) {
            showNotification('error', softworldAIAnalysis.i18n.keywordRequired);
            return;
        }

        const button = $(this);
        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'softworld_track_keyword',
                nonce: softworldAIAnalysis.nonce,
                keyword: keyword,
                url: url,
                position: position
            },
            success: function(response) {
                button.prop('disabled', false);

                if (response.success) {
                    $('#add-keyword-modal').fadeOut(300);
                    $('#modal-keyword').val('');
                    $('#modal-position').val(0);
                    showNotification('success', response.data);
                } else {
                    showNotification('error', response.data);
                }
            },
            error: function() {
                button.prop('disabled', false);
                showNotification('error', softworldAIAnalysis.i18n.error);
            }
        });
    });

    // ========== HELPER FUNCTIONS ==========
    
    function showLoading() {
        $('.loading-overlay').fadeIn(200);
    }

    function hideLoading() {
        $('.loading-overlay').fadeOut(200);
    }

    function showNotification(type, message) {
        const notification = $('<div class="ai-analysis-notification ' + type + '">' + message + '</div>');
        $('.wrap').append(notification);
        
        notification.fadeIn(300);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    function downloadTextFile(text, filename) {
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    function getUrlPath(url) {
        try {
            const urlObj = new URL(url);
            return urlObj.pathname;
        } catch (e) {
            return url;
        }
    }

    function getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (let key in intervals) {
            const interval = Math.floor(seconds / intervals[key]);
            if (interval >= 1) {
                return interval + ' ' + key + (interval > 1 ? 's' : '') + ' ago';
            }
        }
        
        return 'Just now';
    }

    function getScoreClass(score) {
        if (score >= 80) return 'score-high';
        if (score >= 50) return 'score-medium';
        return 'score-low';
    }

    // Initialize on page load
    if ($('.softworld-seo-audit').length) {
        updateAuditHistory();
    }
});