/**
 * Advanced Settings JavaScript
 * Handles interactions for SEO email reports and query arg monitoring
 */
(function ($) {
  "use strict";

  var SoftworldAdvancedSettings = {
    init: function () {
      this.bindEvents();
      this.initializeComponents();
    },

    bindEvents: function () {
      // Test email functionality
      $("#send-test-email").on("click", this.sendTestEmail);

      // Clear query logs
      $("#clear-query-logs").on("click", this.clearQueryLogs);

      // Export query data
      $("#export-query-data").on("click", this.exportQueryData);

      // Real-time settings validation
      $('input[name="report_recipients"]').on("blur", this.validateEmails);

      // Toggle sections based on enable/disable checkboxes
      $('input[name="enable_email_reports"]').on(
        "change",
        this.toggleEmailSettings
      );
      $('input[name="enable_query_monitoring"]').on(
        "change",
        this.toggleQuerySettings
      );

      // Auto-save functionality (debounced)
      this.setupAutoSave();
    },

    initializeComponents: function () {
      // Initialize tooltips if available
      if ($.fn.tooltip) {
        $(".description").tooltip();
      }

      // Set initial toggle states
      this.toggleEmailSettings();
      this.toggleQuerySettings();

      // Load dashboard stats periodically
      this.refreshStats();
      setInterval(this.refreshStats, 300000); // Every 5 minutes
    },

    sendTestEmail: function (e) {
      e.preventDefault();

      var button = $(this);
      var status = $("#test-email-status");

      console.log("Send test email clicked"); // Debug log

      // Validate recipients first
      var recipients = $('textarea[name="report_recipients"]').val().trim();
      if (!recipients) {
        status
          .removeClass("success loading")
          .addClass("error")
          .text("Please enter at least one email recipient.");
        return;
      }

      // Validate email format
      var emails = recipients.split(",").map(function (email) {
        return email.trim();
      });

      var invalidEmails = emails.filter(function (email) {
        return !SoftworldAdvancedSettings.isValidEmail(email);
      });

      if (invalidEmails.length > 0) {
        status
          .removeClass("success loading")
          .addClass("error")
          .text("Invalid email addresses: " + invalidEmails.join(", "));
        return;
      }

      button.addClass("loading").prop("disabled", true);
      status
        .removeClass("success error")
        .addClass("loading")
        .text("Sending test email...");

      console.log("Making AJAX request..."); // Debug log
      console.log("AJAX URL:", ajaxurl); // Debug log
      console.log("Nonce:", SoftworldAdvancedSettings.getNonce()); // Debug log

      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "softworld_send_test_email",
          nonce: SoftworldAdvancedSettings.getNonce(),
        },
        success: function (response) {
          console.log("AJAX Success Response:", response); // Debug log

          if (response.success) {
            status
              .removeClass("loading error")
              .addClass("success")
              .text(response.data);

            // Show additional success info
            SoftworldAdvancedSettings.showNotification(
              response.data,
              "success"
            );
          } else {
            status
              .removeClass("loading success")
              .addClass("error")
              .text(response.data || "Failed to send test email");

            console.error("Email send failed:", response.data);
            SoftworldAdvancedSettings.showNotification(
              "Email send failed: " + response.data,
              "error"
            );
          }
        },
        error: function (xhr, textStatus, errorThrown) {
          console.error("AJAX Error:", textStatus, errorThrown); // Debug log
          console.error("XHR Response:", xhr.responseText); // Debug log

          var errorMsg = "Network error: " + errorThrown;
          if (xhr.responseText) {
            try {
              var errorResponse = JSON.parse(xhr.responseText);
              if (errorResponse.data) {
                errorMsg = errorResponse.data;
              }
            } catch (e) {
              // If not JSON, show raw response (first 200 chars)
              errorMsg += " - " + xhr.responseText.substring(0, 200);
            }
          }

          status
            .removeClass("loading success")
            .addClass("error")
            .text(errorMsg);
          SoftworldAdvancedSettings.showNotification(errorMsg, "error");
        },
        complete: function () {
          console.log("AJAX Request completed"); // Debug log
          button.removeClass("loading").prop("disabled", false);
        },
        timeout: 30000, // 30 second timeout
      });
    },
    // Add this new method to the SoftworldAdvancedSettings object:
    checkEmailConfiguration: function () {
      console.log("Checking email configuration...");

      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "softworld_check_email_config",
          nonce: this.getNonce(),
        },
        success: function (response) {
          if (response.success) {
            console.log("Email configuration check:", response.data);
            if (response.data.issues && response.data.issues.length > 0) {
              var warningMsg =
                "Email configuration warnings: " +
                response.data.issues.join(", ");
              SoftworldAdvancedSettings.showNotification(warningMsg, "warning");
            }
          }
        },
        error: function (xhr, status, error) {
          console.error("Failed to check email configuration:", error);
        },
      });
    },
    clearQueryLogs: function (e) {
      e.preventDefault();

      if (
        !confirm(
          "Are you sure you want to clear old query logs? This action cannot be undone."
        )
      ) {
        return;
      }

      var button = $(this);
      button.addClass("loading").prop("disabled", true);

      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "softworld_clear_query_logs",
          nonce: SoftworldAdvancedSettings.getNonce(),
        },
        success: function (response) {
          if (response.success) {
            SoftworldAdvancedSettings.showNotification(
              response.data,
              "success"
            );
            // Refresh the stats and table
            SoftworldAdvancedSettings.refreshStats();
            SoftworldAdvancedSettings.refreshQueryTable();
          } else {
            SoftworldAdvancedSettings.showNotification(
              response.data || "Failed to clear logs",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          SoftworldAdvancedSettings.showNotification(
            "Network error: " + error,
            "error"
          );
        },
        complete: function () {
          button.removeClass("loading").prop("disabled", false);
        },
      });
    },

    exportQueryData: function (e) {
      e.preventDefault();

      var button = $(this);
      button.addClass("loading").prop("disabled", true);

      // Create and trigger download
      var link = document.createElement("a");
      link.href =
        ajaxurl +
        "?action=softworld_export_query_data&nonce=" +
        encodeURIComponent(SoftworldAdvancedSettings.getNonce());
      link.download =
        "query-args-data-" + new Date().toISOString().split("T")[0] + ".csv";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      setTimeout(function () {
        button.removeClass("loading").prop("disabled", false);
      }, 2000);
    },

    validateEmails: function () {
      var textarea = $(this);
      var emails = textarea
        .val()
        .split(",")
        .map(function (email) {
          return email.trim();
        })
        .filter(function (email) {
          return email.length > 0;
        });

      var invalidEmails = emails.filter(function (email) {
        return !SoftworldAdvancedSettings.isValidEmail(email);
      });

      var feedback = textarea.siblings(".email-validation-feedback");
      if (feedback.length === 0) {
        feedback = $('<div class="email-validation-feedback"></div>');
        textarea.after(feedback);
      }

      if (invalidEmails.length > 0) {
        feedback
          .removeClass("success")
          .addClass("error")
          .text("Invalid email addresses: " + invalidEmails.join(", "));
      } else if (emails.length > 0) {
        feedback
          .removeClass("error")
          .addClass("success")
          .text("✓ All email addresses are valid");
      } else {
        feedback.removeClass("success error").text("");
      }
    },

    toggleEmailSettings: function () {
      var isEnabled = $('input[name="enable_email_reports"]').is(":checked");
      var emailForm = $("#softworld-email-reports-form");

      emailForm
        .find("input, select, textarea, button")
        .not('input[name="enable_email_reports"]')
        .prop("disabled", !isEnabled);

      if (isEnabled) {
        emailForm.removeClass("disabled");
      } else {
        emailForm.addClass("disabled");
      }
    },

    toggleQuerySettings: function () {
      var isEnabled = $('input[name="enable_query_monitoring"]').is(":checked");
      var queryForm = $("#softworld-query-monitoring-form");

      queryForm
        .find("input, select, textarea, button")
        .not('input[name="enable_query_monitoring"]')
        .prop("disabled", !isEnabled);

      if (isEnabled) {
        queryForm.removeClass("disabled");
      } else {
        queryForm.addClass("disabled");
      }
    },

    setupAutoSave: function () {
      var autoSaveTimer;
      var originalValues = {};

      // Store original form values
      $(".softworld-settings-section form").each(function () {
        var formData = $(this).serialize();
        originalValues[this.id] = formData;
      });

      // Set up debounced auto-save
      $(
        ".softworld-settings-section input, .softworld-settings-section select, .softworld-settings-section textarea"
      ).on("input change", function () {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
          SoftworldAdvancedSettings.checkForChanges();
        }, 2000);
      });
    },

    checkForChanges: function () {
      $(".softworld-settings-section form").each(function () {
        var form = $(this);
        var formId = this.id;
        var currentData = form.serialize();

        if (originalValues[formId] && originalValues[formId] !== currentData) {
          SoftworldAdvancedSettings.showUnsavedChangesIndicator(form);
        }
      });
    },

    showUnsavedChangesIndicator: function (form) {
      var indicator = form.find(".unsaved-changes-indicator");
      if (indicator.length === 0) {
        indicator = $(
          '<div class="unsaved-changes-indicator">Unsaved changes</div>'
        );
        form.prepend(indicator);
      }
      indicator.show().fadeIn();
    },

    hideUnsavedChangesIndicator: function (form) {
      form.find(".unsaved-changes-indicator").fadeOut();
    },

    refreshStats: function () {
      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "softworld_get_advanced_stats",
          nonce: SoftworldAdvancedSettings.getNonce(),
        },
        success: function (response) {
          if (response.success && response.data) {
            SoftworldAdvancedSettings.updateStatsDisplay(response.data);
          }
        },
        error: function () {
          console.log("Failed to refresh statistics");
        },
      });
    },

    updateStatsDisplay: function (stats) {
      $(".stat-card").each(function () {
        var card = $(this);
        var statType = card.data("stat-type");

        if (stats[statType] !== undefined) {
          card
            .find("h3")
            .text(SoftworldAdvancedSettings.formatNumber(stats[statType]));
        }
      });
    },

    refreshQueryTable: function () {
      location.reload(); // Simple refresh for now
      // TODO: Implement AJAX table refresh
    },

    showNotification: function (message, type) {
      type = type || "success";

      var notification = $(
        '<div class="softworld-notification ' + type + '">' + message + "</div>"
      );
      $(".wrap").prepend(notification);

      notification
        .fadeIn()
        .delay(5000)
        .fadeOut(function () {
          $(this).remove();
        });
    },

    isValidEmail: function (email) {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    formatNumber: function (num) {
      if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + "M";
      } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + "K";
      }
      return num.toString();
    },

    getNonce: function () {
      return (
        $("#softworld_nonce").val() || $('input[name="softworld_nonce"]').val()
      );
    },
  };

  // Global functions for inline onclick handlers
  window.updateQueryStatus = function (id, status) {
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "softworld_update_query_status",
        id: id,
        status: status,
        nonce: SoftworldAdvancedSettings.getNonce(),
      },
      success: function (response) {
        if (response.success) {
          location.reload(); // Refresh to show updated status
        } else {
          alert(
            "Failed to update status: " + (response.data || "Unknown error")
          );
        }
      },
      error: function () {
        alert("Network error occurred");
      },
    });
  };

  window.viewReportData = function (id) {
    // Create modal to display report data
    var modal = $(
      '<div class="report-modal-overlay">' +
        '<div class="report-modal">' +
        '<div class="report-modal-header">' +
        "<h3>Report Details</h3>" +
        '<button class="report-modal-close">&times;</button>' +
        "</div>" +
        '<div class="report-modal-body">Loading...</div>' +
        "</div>" +
        "</div>"
    );

    $("body").append(modal);

    // Load report data
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "softworld_get_report_data",
        id: id,
        nonce: SoftworldAdvancedSettings.getNonce(),
      },
      success: function (response) {
        if (response.success) {
          modal.find(".report-modal-body").html(response.data);
        } else {
          modal
            .find(".report-modal-body")
            .html("<p>Failed to load report data</p>");
        }
      },
      error: function () {
        modal.find(".report-modal-body").html("<p>Network error occurred</p>");
      },
    });

    // Close modal handlers
    modal
      .find(".report-modal-close, .report-modal-overlay")
      .on("click", function (e) {
        if (e.target === this) {
          modal.fadeOut(function () {
            modal.remove();
          });
        }
      });

    modal.fadeIn();
  };

  // Initialize when document is ready
  $(document).ready(function () {
    SoftworldAdvancedSettings.init();
    // Check email configuration on page load
    SoftworldAdvancedSettings.checkEmailConfiguration();
    // Add diagnostic button for debugging (remove in production)
    if (typeof console !== 'undefined' && console.log) {
        var diagnosticBtn = $('<button type="button" class="button button-secondary" id="email-diagnostic">Run Email Diagnostic</button>');
        $('#send-test-email').after(diagnosticBtn);
        
        diagnosticBtn.on('click', function(e) {
            e.preventDefault();
            
            console.group('Email Diagnostic Information');
            console.log('WordPress AJAX URL:', ajaxurl);
            console.log('Available nonce:', SoftworldAdvancedSettings.getNonce());
            console.log('Admin email setting:', $('input[name="admin_email"]').val() || 'Not found on page');
            console.log('Recipients field value:', $('textarea[name="report_recipients"]').val());
            console.log('jQuery version:', $.fn.jquery);
            console.log('Current page URL:', window.location.href);
            
            // Test basic AJAX connectivity
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'heartbeat',
                    _ajax_nonce: $('#_wpnonce').val()
                },
                success: function(response) {
                    console.log('Basic AJAX test: SUCCESS', response);
                },
                error: function(xhr, status, error) {
                    console.error('Basic AJAX test: FAILED', status, error);
                }
            });
            
            console.groupEnd();
            
            alert('Email diagnostic information has been logged to the browser console. Press F12 to view.');
        });
    }

    // Handle form submissions with loading states
    $(".softworld-settings-section form").on("submit", function () {
      var form = $(this);
      var submitButton = form.find(
        'input[type="submit"], button[type="submit"]'
      );

      submitButton.addClass("loading").prop("disabled", true);

      // Remove loading state after a delay (form submission will redirect/reload)
      setTimeout(function () {
        submitButton.removeClass("loading").prop("disabled", false);
      }, 3000);

      SoftworldAdvancedSettings.hideUnsavedChangesIndicator(form);
    });

    // Keyboard shortcuts
    $(document).on("keydown", function (e) {
      // Ctrl/Cmd + S to save
      if ((e.ctrlKey || e.metaKey) && e.key === "s") {
        e.preventDefault();
        $(".softworld-settings-section form").first().submit();
      }
    });

    // Warn about unsaved changes before leaving page
    window.addEventListener("beforeunload", function (e) {
      if ($(".unsaved-changes-indicator:visible").length > 0) {
        e.preventDefault();
        e.returnValue =
          "You have unsaved changes. Are you sure you want to leave?";
        return e.returnValue;
      }
    });
  });
})(jQuery);
