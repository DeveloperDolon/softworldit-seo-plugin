/**
 * Link Assistant JavaScript
 *
 * Handles all frontend interactions for the Link Assistant feature
 */

jQuery(document).ready(function ($) {
  "use strict";

  const LinkAssistant = {
    init: function () {
      this.bindEvents();
      this.initTabs();
      // Don't auto-load reports on init to avoid errors
    },

    bindEvents: function () {
      const self = this;

      // Tab switching
      $(document).on("click", ".nav-tab", function (e) {
        e.preventDefault();
        const targetTab = $(this).data("tab");
        self.switchTab(targetTab);
        window.location.hash = targetTab;
      });

      // Links Report
      $(document).on("click", "#apply-filters", function () {
        self.loadLinksReport();
      });

      $(document).on("click", ".refresh-single-analysis", function () {
        self.refreshSingleAnalysis($(this));
      });

      // Link Suggestions
      $(document).on("click", "#load-suggestions", function () {
        self.loadSuggestions();
      });

      // Cornerstone Content
      $(document).on("click", "#search-cornerstone", function () {
        self.searchCornerstoneContent();
      });

      $(document).on("keypress", "#cornerstone-search", function (e) {
        if (e.which === 13) {
          self.searchCornerstoneContent();
        }
      });

      $(document).on("click", ".add-cornerstone", function () {
        const postId = $(this).data("post-id");
        self.addCornerstoneContent(postId);
      });

      $(document).on("click", ".remove-cornerstone", function () {
        self.removeCornerstoneContent($(this));
      });

      $(document).on("click", ".search-result-item", function () {
        const postId = $(this).data("post-id");
        self.addCornerstoneContent(postId);
      });
    },

    initTabs: function () {
      const hash = window.location.hash;
      if (hash) {
        const targetTab = hash.substring(1);
        this.switchTab(targetTab);
      } else {
        this.switchTab("links-report");
      }
    },

    switchTab: function (targetTab) {
      // Remove active class from all tabs and content
      $(".nav-tab").removeClass("nav-tab-active");
      $(".tab-content").removeClass("active");

      // Add active class to target tab and content
      $(`[data-tab="${targetTab}"]`).addClass("nav-tab-active");
      $(`#${targetTab}`).addClass("active");

      // Load specific tab content if needed
      switch (targetTab) {
        case "links-report":
          this.loadLinksReport();
          break;
        case "link-suggestions":
          // Don't auto-load suggestions, wait for user to click Load Suggestions
          break;
        case "cornerstone-content":
          // Content is already loaded on page load
          break;
      }
    },

    loadLinksReport: function () {
      if (typeof softworldLinkAssistant === "undefined") {
        console.error("softworldLinkAssistant is not defined");
        return;
      }

      const $container = $("#links-report-table");
      $container.html('<div class="loading-spinner">Loading...</div>');

      const data = {
        action: "get_links_report",
        nonce: softworldLinkAssistant.nonce,
        post_type: $("#filter-post-type").val() || "",
        link_status: $("#filter-link-status").val() || "",
        page: 1,
      };

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        timeout: 30000,
        success: (response) => {
          if (response.success) {
            this.renderLinksReport(response.data);
          } else {
            $container.html(
              "<p>Error loading links report: " +
                (response.data || "Unknown error") +
                "</p>"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("AJAX Error:", status, error);
          console.error("Response:", xhr.responseText);
          $container.html(
            "<p>Error loading links report. Please check the console for details.</p>"
          );
        },
      });
    },

    renderLinksReport: function (data) {
      // Update statistics
      $("#total-posts").text(data.stats.total_posts || 0);
      $("#internal-links").text(data.stats.internal_links || 0);
      $("#external-links").text(data.stats.external_links || 0);
      $("#orphaned-content").text(data.stats.orphaned_content || 0);

      // Render table
      let tableHtml = `
                <table class="softworld-links-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Internal Links</th>
                            <th>External Links</th>
                            <th>Link Density</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

      if (data.posts && data.posts.length > 0) {
        data.posts.forEach(function (post) {
          const cornerstoneIcon = post.is_cornerstone
            ? '<span class="cornerstone-badge">Cornerstone</span>'
            : "";
          const statusClass = `link-status-${post.link_status}`;

          tableHtml += `
                        <tr>
                            <td>
                                <strong>${post.title}</strong>
                                ${cornerstoneIcon}
                            </td>
                            <td>${post.post_type}</td>
                            <td>${post.date}</td>
                            <td>${post.internal_links}</td>
                            <td>${post.external_links}</td>
                            <td>${post.link_density}%</td>
                            <td><span class="link-status-badge ${statusClass}">${post.link_status}</span></td>
                            <td>
                                <a href="${post.edit_url}" class="button button-small">Edit</a>
                                <a href="${post.view_url}" class="button button-small" target="_blank">View</a>
                                <button type="button" class="button button-small refresh-single-analysis" data-post-id="${post.id}">Refresh</button>
                            </td>
                        </tr>
                    `;
        });
      } else {
        tableHtml += '<tr><td colspan="8">No posts found</td></tr>';
      }

      tableHtml += "</tbody></table>";
      $("#links-report-table").html(tableHtml);
    },

    refreshSingleAnalysis: function ($button) {
      if (typeof softworldLinkAssistant === "undefined") {
        console.error("softworldLinkAssistant is not defined");
        return;
      }

      const postId = $button.data("post-id");
      $button.prop("disabled", true).text("Refreshing...");

      const data = {
        action: "refresh_links_analysis",
        nonce: softworldLinkAssistant.nonce,
        post_id: postId,
      };

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        timeout: 30000,
        success: (response) => {
          if (response.success) {
            this.loadLinksReport();
            this.showNotice(response.data.message, "success");
          } else {
            this.showNotice(
              "Error refreshing analysis: " +
                (response.data || "Unknown error"),
              "error"
            );
          }
          $button.prop("disabled", false).text("Refresh");
        },
        error: (xhr, status, error) => {
          console.error("AJAX Error:", status, error);
          this.showNotice(
            "Error refreshing analysis. Please try again.",
            "error"
          );
          $button.prop("disabled", false).text("Refresh");
        },
      });
    },

    loadSuggestions: function () {
      if (typeof softworldLinkAssistant === "undefined") {
        console.error("softworldLinkAssistant is not defined");
        $("#link-suggestions-content").html(
          "<p>JavaScript configuration error. Please refresh the page.</p>"
        );
        return;
      }

      const $container = $("#link-suggestions-content");
      $container.html(
        '<div class="loading-spinner">Loading suggestions...</div>'
      );

      const data = {
        action: "get_link_suggestions",
        nonce: softworldLinkAssistant.nonce,
        post_type: $("#suggestions-post-type").val() || "",
        priority: $("#suggestions-priority").val() || "",
      };

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        timeout: 60000,
        success: (response) => {
          if (response.success) {
            this.renderSuggestions(response.data.suggestions);
          } else {
            $container.html(
              "<p>Error loading suggestions: " +
                (response.data || "Unknown error") +
                "</p>"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("AJAX Error:", status, error);
          console.error("Response Text:", xhr.responseText);

          // Try to parse the response for more details
          let errorDetails = "Unknown error";
          try {
            const response = JSON.parse(xhr.responseText);
            errorDetails = response.data || "No details available";
          } catch (e) {
            errorDetails = xhr.responseText || "No response text";
          }

          $container.html(`
        <div class="error-message">
            <h3>Error Loading Suggestions</h3>
            <p><strong>Status:</strong> ${status}</p>
            <p><strong>Error:</strong> ${error}</p>
            <p><strong>Details:</strong> ${errorDetails}</p>
            <p>Please check your WordPress error logs for more details.</p>
        </div>
    `);
        },
      });
    },

    renderSuggestions: function (suggestions) {
      const $container = $("#link-suggestions-content");

      if (!suggestions || suggestions.length === 0) {
        $container.html(`
                    <div class="no-suggestions">
                        <h3>No suggestions found</h3>
                        <p>This could mean:</p>
                        <ul>
                            <li>Your internal linking strategy is already optimized</li>
                            <li>You may need to create more content to get meaningful suggestions</li>
                            <li>Try adjusting the filters above to see different types of suggestions</li>
                            <li>Run the "Refresh Analysis" to update your content analysis</li>
                        </ul>
                    </div>
                `);
        return;
      }

      let html = "";
      suggestions.forEach(function (suggestion) {
        const priorityClass = `priority-${suggestion.priority}`;

        html += `
                    <div class="suggestion-item">
                        <div class="suggestion-header">
                            <div class="suggestion-title">
                                <h3>${suggestion.post_title}</h3>
                                <div class="suggestion-meta">
                                    <span>${suggestion.post_type}</span>
                                    <span class="suggestion-priority ${priorityClass}">${suggestion.priority} priority</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="suggestion-reason">
                            ${suggestion.reason}
                        </div>
                        
                        <div class="related-posts">
                            <h4>Suggested links to add:</h4>
                `;

        if (suggestion.related_posts && suggestion.related_posts.length > 0) {
          suggestion.related_posts.forEach(function (relatedPost) {
            html += `
                            <div class="related-post-item">
                                <div class="related-post-info">
                                    <a href="${relatedPost.url}" class="related-post-title" target="_blank">${relatedPost.title}</a>
                                    <span class="related-post-reason">${relatedPost.reason}</span>
                                </div>
                            </div>
                        `;
          });
        } else {
          html += "<p>No specific suggestions available for this post.</p>";
        }

        html += `
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <a href="${suggestion.edit_url}" class="button button-primary">Edit Post</a>
                        </div>
                    </div>
                `;
      });

      $container.html(html);
    },

    searchCornerstoneContent: function () {
      const searchTerm = $("#cornerstone-search").val().trim();
      if (searchTerm.length < 3) {
        this.showNotice(
          "Please enter at least 3 characters to search",
          "error"
        );
        return;
      }

      const $results = $("#cornerstone-search-results");
      $results.html('<div class="loading-spinner">Searching...</div>').show();

      const data = {
        action: "softworld_search_posts",
        nonce: softworldLinkAssistant.nonce,
        term: searchTerm,
      };

      $.ajax({
        url: ajaxurl,
        type: "GET",
        data: data,
        success: (response) => {
          if (response.success && response.data.length > 0) {
            this.renderSearchResults(response.data);
          } else {
            $results.html('<p style="padding: 15px;">No results found</p>');
          }
        },
        error: (xhr, status, error) => {
          console.error("Search error:", status, error);
          $results.html('<p style="padding: 15px;">Error searching posts</p>');
        },
      });
    },

    renderSearchResults: function (results) {
      const $container = $("#cornerstone-search-results");
      let html = "";

      results.forEach(function (result) {
        html += `
                    <div class="search-result-item" data-post-id="${result.id}">
                        <div class="search-result-info">
                            <div class="search-result-title">${result.label}</div>
                            <div class="search-result-meta">Click to add as cornerstone content</div>
                        </div>
                    </div>
                `;
      });

      $container.html(html);
    },

    addCornerstoneContent: function (postId) {
      if (!postId) {
        console.error("No post ID provided");
        return;
      }

      const data = {
        action: "update_cornerstone_content",
        nonce: softworldLinkAssistant.nonce,
        post_id: postId,
        action_type: "add",
      };

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        success: (response) => {
          if (response.success) {
            this.showNotice(response.data.message, "success");
            setTimeout(() => location.reload(), 1000);
          } else {
            this.showNotice(
              "Error adding cornerstone content: " +
                (response.data || "Unknown error"),
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("Error adding cornerstone:", error);
          this.showNotice(
            "Error adding cornerstone content. Please try again.",
            "error"
          );
        },
      });
    },

    removeCornerstoneContent: function ($button) {
      const postId = $button.data("post-id");

      if (
        !confirm(
          "Are you sure you want to remove this from cornerstone content?"
        )
      ) {
        return;
      }

      const data = {
        action: "update_cornerstone_content",
        nonce: softworldLinkAssistant.nonce,
        post_id: postId,
        action_type: "remove",
      };

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        success: (response) => {
          if (response.success) {
            this.showNotice(response.data.message, "success");
            $(`.cornerstone-item[data-post-id="${postId}"]`).fadeOut(
              300,
              function () {
                $(this).remove();
                if ($(".cornerstone-item").length === 0) {
                  $("#cornerstone-content-list").html(
                    '<p class="no-cornerstone-content">No cornerstone content set. Use the search above to add important content.</p>'
                  );
                }
              }
            );
          } else {
            this.showNotice(
              "Error removing cornerstone content: " +
                (response.data || "Unknown error"),
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          console.error("Error removing cornerstone:", error);
          this.showNotice(
            "Error removing cornerstone content. Please try again.",
            "error"
          );
        },
      });
    },

    showNotice: function (message, type) {
      $(".softworld-notice").remove();

      const noticeClass = type === "success" ? "updated" : "error";
      const notice = $(`
                <div class="notice ${noticeClass} softworld-notice is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

      $(".wrap h1").after(notice);

      setTimeout(function () {
        notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);

      notice.on("click", ".notice-dismiss", function () {
        notice.fadeOut(300, function () {
          $(this).remove();
        });
      });
    },
  };

  // Initialize only if the required object exists
  if (typeof softworldLinkAssistant !== "undefined") {
    console.log("softworldLinkAssistant found:", softworldLinkAssistant);
    LinkAssistant.init();
  } else {
    console.error(
      "softworldLinkAssistant object not found. Make sure wp_localize_script is called."
    );
    console.log("Available globals:", Object.keys(window));
  }
});
