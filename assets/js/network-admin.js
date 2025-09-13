/**
 * Network Admin JavaScript for Softworld AI SEO
 */
jQuery(document).ready(function ($) {
  "use strict";

  // Global variables
  const networkAdmin = {
    ajax_url: softworldNetwork.ajax_url,
    nonce: softworldNetwork.nonce,
    i18n: softworldNetwork.i18n,
  };

  /**
   * License Management
   */
  function initLicenseManagement() {
    // Activate License
    $("#activate-license").on("click", function (e) {
      e.preventDefault();

      const $button = $(this);
      const licenseKey = $("#license_key").val().trim();

      if (!licenseKey) {
        showNotification("error", "Please enter a license key.");
        return;
      }

      $button.prop("disabled", true).text("Activating...");

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: {
          action: "softworld_network_activate_license",
          nonce: networkAdmin.nonce,
          license_key: licenseKey,
        },
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data.message);
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          } else {
            showNotification(
              "error",
              response.data || "License activation failed."
            );
            $button.prop("disabled", false).text("Activate License");
          }
        },
        error: function () {
          showNotification("error", "Network error occurred.");
          $button.prop("disabled", false).text("Activate License");
        },
      });
    });

    // Deactivate License
    $("#deactivate-license").on("click", function (e) {
      e.preventDefault();

      if (!confirm("Are you sure you want to deactivate your license?")) {
        return;
      }

      const $button = $(this);
      $button.prop("disabled", true).text("Deactivating...");

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: {
          action: "softworld_network_deactivate_license",
          nonce: networkAdmin.nonce,
        },
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data);
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          } else {
            showNotification(
              "error",
              response.data || "License deactivation failed."
            );
            $button.prop("disabled", false).text("Deactivate License");
          }
        },
        error: function () {
          showNotification("error", "Network error occurred.");
          $button.prop("disabled", false).text("Deactivate License");
        },
      });
    });
  }

  /**
   * Network Settings Management
   */
  function initNetworkSettings() {
    // Save Network Settings
    $("#softworld-network-settings-form").on("submit", function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitBtn = $form.find('button[type="submit"]');
      const originalText = $submitBtn.text();

      $submitBtn.prop("disabled", true).text(networkAdmin.i18n.saving);

      const formData = $form.serialize();
      formData.action = "softworld_network_save_settings";
      formData.nonce = networkAdmin.nonce;

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data);
            $submitBtn.text(networkAdmin.i18n.saved);
            setTimeout(() => {
              $submitBtn.text(originalText);
            }, 2000);
          } else {
            showNotification("error", response.data || "Save failed.");
          }
        },
        error: function () {
          showNotification("error", networkAdmin.i18n.error);
        },
        complete: function () {
          $submitBtn.prop("disabled", false);
          if ($submitBtn.text() === networkAdmin.i18n.saving) {
            $submitBtn.text(originalText);
          }
        },
      });
    });

    // Apply to All Sites
    $("#apply-to-all-sites").on("click", function (e) {
      e.preventDefault();

      if (!confirm(networkAdmin.i18n.confirm_apply_all)) {
        return;
      }

      const $button = $(this);
      const originalText = $button.text();

      $button.prop("disabled", true).text("Applying...");

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: {
          action: "softworld_network_apply_to_sites",
          nonce: networkAdmin.nonce,
        },
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data.message);
          } else {
            showNotification("error", response.data || "Apply failed.");
          }
        },
        error: function () {
          showNotification("error", networkAdmin.i18n.error);
        },
        complete: function () {
          $button.prop("disabled", false).text(originalText);
        },
      });
    });

    // Settings change tracking
    let hasUnsavedChanges = false;

    $(
      "#softworld-network-settings-form input, #softworld-network-settings-form select, #softworld-network-settings-form textarea"
    ).on("change", function () {
      hasUnsavedChanges = true;
    });

    // Warn about unsaved changes
    $(window).on("beforeunload", function () {
      if (hasUnsavedChanges) {
        return networkAdmin.i18n.unsaved_changes;
      }
    });

    // Mark saved when form is submitted successfully
    $("#softworld-network-settings-form").on(
      "softworld-settings-saved",
      function () {
        hasUnsavedChanges = false;
      }
    );
  }

  /**
   * Import/Export Management
   */
  function initImportExport() {
    // Export Settings
    $("#softworld-export-form").on("submit", function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitBtn = $form.find('button[type="submit"]');

      // Check if at least one option is selected
      if (!$form.find('input[type="checkbox"]:checked').length) {
        showNotification("error", "Please select at least one export option.");
        return;
      }

      $submitBtn.prop("disabled", true).text("Exporting...");

      // Create a temporary form for file download
      const tempForm = $("<form>", {
        method: "POST",
        action: networkAdmin.ajax_url,
      });

      // Add form data
      $form.find("input").each(function () {
        if ($(this).is(":checkbox") && !$(this).is(":checked")) return;
        tempForm.append(
          $("<input>", {
            type: "hidden",
            name: $(this).attr("name"),
            value: $(this).val(),
          })
        );
      });

      tempForm.append(
        $("<input>", {
          type: "hidden",
          name: "action",
          value: "softworld_network_export_settings",
        })
      );

      tempForm.append(
        $("<input>", {
          type: "hidden",
          name: "nonce",
          value: networkAdmin.nonce,
        })
      );

      $("body").append(tempForm);
      tempForm.submit();
      tempForm.remove();

      setTimeout(() => {
        $submitBtn.prop("disabled", false).text("Export Settings");
        showNotification("success", "Export completed successfully!");
        loadImportExportLog();
      }, 1000);
    });

    // Import Settings
    $("#softworld-import-form").on("submit", function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitBtn = $form.find('button[type="submit"]');
      const fileInput = $form.find('input[type="file"]')[0];

      if (!fileInput.files.length) {
        showNotification("error", "Please select a file to import.");
        return;
      }

      $submitBtn.prop("disabled", true).text("Importing...");

      const formData = new FormData();
      formData.append("action", "softworld_network_import_settings");
      formData.append("nonce", networkAdmin.nonce);
      formData.append("import_file", fileInput.files[0]);

      // Add checkbox values
      $form.find('input[type="checkbox"]:checked').each(function () {
        formData.append($(this).attr("name"), $(this).val());
      });

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data.message);
            if (response.data.details && response.data.details.length) {
              showNotification(
                "info",
                "Details: " + response.data.details.join(", ")
              );
            }
            $form[0].reset();
            loadImportExportLog();
          } else {
            showNotification("error", response.data || "Import failed.");
          }
        },
        error: function () {
          showNotification("error", networkAdmin.i18n.error);
        },
        complete: function () {
          $submitBtn.prop("disabled", false).text("Import Settings");
        },
      });
    });
  }

  /**
   * Site Override Management
   */
  function initSiteOverrides() {
    // Request Override Permission
    $("#override-request-form").on("submit", function (e) {
      e.preventDefault();

      if (!confirm(networkAdmin.i18n.confirm_override_request)) {
        return;
      }

      const $form = $(this);
      const $submitBtn = $form.find('button[type="submit"]');

      if (!$form.find('input[name="request_override"]').is(":checked")) {
        showNotification(
          "error",
          "Please check the override request checkbox."
        );
        return;
      }

      $submitBtn.prop("disabled", true).text("Sending Request...");

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: {
          action: "softworld_site_request_override",
          nonce: networkAdmin.nonce,
          override_reason: $form.find('textarea[name="override_reason"]').val(),
        },
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data);
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          } else {
            showNotification("error", response.data || "Request failed.");
            $submitBtn
              .prop("disabled", false)
              .text("Request Override Permission");
          }
        },
        error: function () {
          showNotification("error", networkAdmin.i18n.error);
          $submitBtn
            .prop("disabled", false)
            .text("Request Override Permission");
        },
      });
    });

    // Revoke Override
    $("#revoke-override").on("click", function (e) {
      e.preventDefault();

      if (
        !confirm(
          "Are you sure you want to revert to network settings? This will remove all custom settings for this site."
        )
      ) {
        return;
      }

      const $button = $(this);
      $button.prop("disabled", true).text("Revoking...");

      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: {
          action: "softworld_site_revoke_override",
          nonce: networkAdmin.nonce,
        },
        success: function (response) {
          if (response.success) {
            showNotification("success", response.data);
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          } else {
            showNotification("error", response.data || "Revoke failed.");
            $button.prop("disabled", false).text("Revert to Network Settings");
          }
        },
        error: function () {
          showNotification("error", networkAdmin.i18n.error);
          $button.prop("disabled", false).text("Revert to Network Settings");
        },
      });
    });
  }

  /**
   * Load Import/Export Log
   */
  function loadImportExportLog() {
    $.ajax({
      url: networkAdmin.ajax_url,
      type: "POST",
      data: {
        action: "softworld_network_get_import_export_log",
        nonce: networkAdmin.nonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          $("#import-export-log").html(response.data);
        }
      },
    });
  }

  /**
   * Real-time Statistics Updates
   */
  function initRealTimeStats() {
    function updateStats() {
      $.ajax({
        url: networkAdmin.ajax_url,
        type: "POST",
        data: {
          action: "softworld_network_get_stats",
          nonce: networkAdmin.nonce,
        },
        success: function (response) {
          if (response.success && response.data) {
            updateStatsDisplay(response.data);
          }
        },
      });
    }

    function updateStatsDisplay(stats) {
      if (stats.license_status) {
        $(".license-status")
          .removeClass("valid invalid")
          .addClass(stats.license_status.valid ? "valid" : "invalid")
          .find("span")
          .text(stats.license_status.valid ? "Active" : "Inactive");
      }

      if (stats.network_stats) {
        $(".softworld-network-card").each(function () {
          const $card = $(this);
          const title = $card.find("h3").text();

          if (title.includes("Network Statistics")) {
            const $list = $card.find("ul");
            $list.find("li").each(function (index) {
              const $li = $(this);
              switch (index) {
                case 0:
                  $li.text(`Total Sites: ${stats.network_stats.total_sites}`);
                  break;
                case 1:
                  $li.text(`Active Sites: ${stats.network_stats.active_sites}`);
                  break;
                case 2:
                  $li.text(
                    `Sites with Override: ${stats.network_stats.override_sites}`
                  );
                  break;
              }
            });
          }
        });
      }
    }

    // Update stats every 30 seconds
    setInterval(updateStats, 30000);
  }

  /**
   * Enhanced Form Validation
   */
  function initFormValidation() {
    // License key validation
    $("#license_key").on("input", function () {
      const value = $(this).val().trim();
      const $button = $("#activate-license");

      if (value.length < 10) {
        $button.prop("disabled", true);
        $(this).addClass("invalid");
      } else {
        $button.prop("disabled", false);
        $(this).removeClass("invalid");
      }
    });

    // File upload validation
    $('input[type="file"]').on("change", function () {
      const file = this.files[0];
      const $form = $(this).closest("form");
      const $submitBtn = $form.find('button[type="submit"]');

      if (file) {
        if (file.type !== "application/json" && !file.name.endsWith(".json")) {
          showNotification("error", "Please select a valid JSON file.");
          $submitBtn.prop("disabled", true);
          return;
        }

        if (file.size > 5 * 1024 * 1024) {
          // 5MB limit
          showNotification("error", "File size must be less than 5MB.");
          $submitBtn.prop("disabled", true);
          return;
        }

        $submitBtn.prop("disabled", false);
      }
    });
  }

  /**
   * Accessibility Enhancements
   */
  function initAccessibility() {
    // Keyboard navigation for cards
    $(".softworld-network-card").attr("tabindex", "0");

    // Focus management for modals/notifications
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") {
        $(".softworld-notification").fadeOut();
      }
    });

    // ARIA labels for status indicators
    $(".license-status").attr("role", "status").attr("aria-live", "polite");
    $(".site-status").attr("role", "status");
  }

  /**
   * Show Notification
   */
  function showNotification(type, message, duration = 5000) {
    // Remove existing notifications
    $(".softworld-notification").remove();

    const $notification = $(`
            <div class="softworld-notification ${type}" role="alert" aria-live="assertive">
                ${message}
                <button type="button" class="notice-dismiss" aria-label="Dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

    // Insert after the h1 or at the top of wrap
    const $target = $(".wrap h1").first();
    if ($target.length) {
      $target.after($notification);
    } else {
      $(".wrap").prepend($notification);
    }

    $notification.fadeIn();

    // Auto-dismiss
    if (duration > 0) {
      setTimeout(() => {
        $notification.fadeOut(() => {
          $notification.remove();
        });
      }, duration);
    }

    // Manual dismiss
    $notification.find(".notice-dismiss").on("click", function () {
      $notification.fadeOut(() => {
        $notification.remove();
      });
    });
  }

  /**
   * Utility Functions
   */
  function addLoadingState($element) {
    $element.addClass("softworld-loading");
  }

  function removeLoadingState($element) {
    $element.removeClass("softworld-loading");
  }

  function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
      const context = this;
      const args = arguments;
      const later = function () {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
    };
  }

  /**
   * Initialize Everything
   */
  function init() {
    initLicenseManagement();
    initNetworkSettings();
    initImportExport();
    initSiteOverrides();
    initRealTimeStats();
    initFormValidation();
    initAccessibility();

    // Load initial data
    if ($("#import-export-log").length) {
      loadImportExportLog();
    }

    console.log("Softworld AI SEO Network Admin initialized successfully");
  }

  // Start initialization
  init();
});

// this is the method of new update ===>
/**
 * Softworld AI SEO Network Admin JavaScript
 * Handles NGINX rules generation, robots.txt management, and other network features
 */

jQuery(document).ready(function ($) {
  "use strict";

  // Initialize all components
  initNetworkDashboard();
  initNginxRulesManager();
  initRobotsTxtManager();
  initGeneralNetworkFeatures();

  /**
   * Initialize Network Dashboard Features
   */
  function initNetworkDashboard() {
    // Quick action buttons on dashboard
    $("#quick-generate-nginx").on("click", function () {
      var $btn = $(this);
      var originalText = $btn.text();

      $btn.text(softworldNetwork.i18n.generating_rules).prop("disabled", true);

      // Trigger NGINX rules generation with default settings
      generateNginxRules(
        {
          include_all_sites: 1,
          include_main_site: 1,
          add_caching: 1,
          add_compression: 1,
          cache_duration: 21600,
        },
        function (success) {
          $btn.text(
            success ? softworldNetwork.i18n.rules_generated : originalText
          );
          setTimeout(function () {
            $btn.text(originalText).prop("disabled", false);
          }, 2000);
        }
      );
    });

    $("#quick-apply-robots").on("click", function () {
      var $btn = $(this);
      var originalText = $btn.text();

      if (!confirm(softworldNetwork.i18n.confirm_apply_all)) {
        return;
      }

      $btn.text(softworldNetwork.i18n.applying_robots).prop("disabled", true);

      applyRobotsToAllSites(function (success) {
        $btn.text(
          success ? softworldNetwork.i18n.robots_applied : originalText
        );
        setTimeout(function () {
          $btn.text(originalText).prop("disabled", false);
        }, 2000);
      });
    });

    // Auto-refresh network stats every 30 seconds
    setInterval(refreshNetworkStats, 30000);
  }

  /**
   * Initialize NGINX Rules Manager
   */
  function initNginxRulesManager() {
    // NGINX rules form submission
    $("#nginx-rules-form").on("submit", function (e) {
      e.preventDefault();

      var formData = $(this).serializeArray();
      var data = {
        action: "softworld_network_generate_nginx_rules",
        nonce: softworldNetwork.nonce,
      };

      // Convert form data to object
      $.each(formData, function (i, field) {
        data[field.name] = field.value;
      });

      generateNginxRules(data, function (success, response) {
        if (success && response.rules) {
          displayNginxRules(response.rules);
        }
      });
    });

    // Copy rules to clipboard
    $(document).on("click", ".copy-rules-btn", function () {
      var target = $($(this).data("clipboard-target"));
      if (target.length) {
        copyToClipboard(target.text());
        showNotification("NGINX rules copied to clipboard!", "success");
      }
    });

    // Copy sitemap URLs
    $(document).on("click", ".copy-url-btn", function () {
      var url = $(this).data("url");
      copyToClipboard(url);
      showNotification("URL copied to clipboard!", "success");
    });

    // Toggle advanced options
    $("#nginx-advanced-toggle").on("click", function () {
      $("#nginx-advanced-options").slideToggle();
      $(this).text(
        $(this).text() === "Show Advanced Options"
          ? "Hide Advanced Options"
          : "Show Advanced Options"
      );
    });
  }

  /**
   * Initialize Robots.txt Manager
   */
  function initRobotsTxtManager() {
    // Network robots.txt form submission
    $("#network-robots-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"]');
      var originalText = $submitBtn.text();

      $submitBtn.text(softworldNetwork.i18n.saving).prop("disabled", true);

      $.ajax({
        url: softworldNetwork.ajax_url,
        type: "POST",
        data:
          $form.serialize() +
          "&action=softworld_network_save_robots_txt&nonce=" +
          softworldNetwork.nonce,
        success: function (response) {
          if (response.success) {
            showNotification(response.data, "success");
          } else {
            showNotification(response.data || "An error occurred", "error");
          }
        },
        error: function () {
          showNotification("Network error occurred", "error");
        },
        complete: function () {
          $submitBtn.text(originalText).prop("disabled", false);
        },
      });
    });

    // Apply robots.txt to all sites
    $("#apply-robots-to-all").on("click", function () {
      if (!confirm(softworldNetwork.i18n.confirm_apply_all)) {
        return;
      }

      var $btn = $(this);
      var originalText = $btn.text();

      $btn.text(softworldNetwork.i18n.applying_robots).prop("disabled", true);

      applyRobotsToAllSites(function (success) {
        $btn.text(originalText).prop("disabled", false);
      });
    });

    // Preview robots.txt
    $("#preview-robots").on("click", function () {
      var robotsContent = $("#network-robots-content").val();
      if (!robotsContent.trim()) {
        showNotification("Please enter robots.txt content first", "warning");
        return;
      }

      showRobotsPreview(robotsContent);
    });

    // Load robots.txt templates
    $(".load-template").on("click", function () {
      var template = $(this).data("template");
      var templateContent = getRobotsTemplate(template);

      if (
        confirm("This will replace the current robots.txt content. Continue?")
      ) {
        $("#network-robots-content").val(templateContent);
        showNotification("Template loaded successfully!", "success");
      }
    });

    // Reset site to network robots.txt
    $(document).on("click", ".reset-to-network", function () {
      var siteId = $(this).data("site-id");
      var $row = $(this).closest("tr");

      if (!confirm("Reset this site to use network robots.txt?")) {
        return;
      }

      var $btn = $(this);
      var originalText = $btn.text();

      $btn.text("Resetting...").prop("disabled", true);

      $.ajax({
        url: softworldNetwork.ajax_url,
        type: "POST",
        data: {
          action: "softworld_reset_site_robots",
          site_id: siteId,
          nonce: softworldNetwork.nonce,
        },
        success: function (response) {
          if (response.success) {
            $row
              .find(".custom-robots")
              .removeClass("custom-robots")
              .addClass("network-robots")
              .text("Network Default");
            $btn.remove();
            showNotification("Site reset to network robots.txt", "success");
          } else {
            showNotification(response.data || "Error occurred", "error");
            $btn.text(originalText).prop("disabled", false);
          }
        },
        error: function () {
          showNotification("Network error occurred", "error");
          $btn.text(originalText).prop("disabled", false);
        },
      });
    });

    // Modal handling
    initRobotsModal();
  }

  /**
   * Initialize General Network Features
   */
  function initGeneralNetworkFeatures() {
    // License management
    $("#activate-license").on("click", function () {
      var licenseKey = $("#license_key").val().trim();
      if (!licenseKey) {
        showNotification("Please enter a license key", "warning");
        return;
      }

      activateLicense(licenseKey);
    });

    $("#deactivate-license").on("click", function () {
      if (!confirm("Are you sure you want to deactivate the license?")) {
        return;
      }

      deactivateLicense();
    });

    // Settings form handling
    $("#softworld-network-settings-form").on("submit", function (e) {
      e.preventDefault();
      saveNetworkSettings();
    });

    // Apply settings to all sites
    $("#apply-to-all-sites").on("click", function () {
      if (!confirm(softworldNetwork.i18n.confirm_apply_all)) {
        return;
      }

      applySettingsToAllSites();
    });

    // Unsaved changes warning
    var formChanged = false;
    $(
      "#softworld-network-settings-form input, #softworld-network-settings-form select, #softworld-network-settings-form textarea"
    ).on("change", function () {
      formChanged = true;
    });

    $(window).on("beforeunload", function () {
      if (formChanged) {
        return softworldNetwork.i18n.unsaved_changes;
      }
    });
  }

  /**
   * Generate NGINX Rules
   */
  function generateNginxRules(data, callback) {
    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: data,
      success: function (response) {
        if (response.success) {
          showNotification(response.data.message, "success");
          if (callback) callback(true, response.data);
        } else {
          showNotification(response.data || "Error generating rules", "error");
          if (callback) callback(false, response);
        }
      },
      error: function () {
        showNotification("Network error occurred", "error");
        if (callback) callback(false);
      },
    });
  }

  /**
   * Display Generated NGINX Rules
   */
  function displayNginxRules(rules) {
    var $rulesSection = $(".nginx-rules-display");
    if ($rulesSection.length === 0) {
      // Create rules display section if it doesn't exist
      $rulesSection = $('<div class="nginx-rules-display"></div>');
      $(".nginx-generator-section").after($rulesSection);
    }

    var rulesHtml = `
            <h2>Generated NGINX Rules</h2>
            <p>Copy the following rules to your NGINX server configuration:</p>
            <div class="nginx-rules-output">
                <div class="rules-header">
                    <span>NGINX Configuration</span>
                    <button type="button" class="copy-rules-btn" data-clipboard-target="#nginx-rules-text">
                        Copy to Clipboard
                    </button>
                </div>
                <pre id="nginx-rules-text"><code>${escapeHtml(
                  rules
                )}</code></pre>
            </div>
        `;

    $rulesSection.html(rulesHtml).show();

    // Scroll to rules
    $("html, body").animate(
      {
        scrollTop: $rulesSection.offset().top - 50,
      },
      500
    );
  }

  /**
   * Apply Robots.txt to All Sites
   */
  function applyRobotsToAllSites(callback) {
    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: {
        action: "softworld_network_apply_robots_to_sites",
        nonce: softworldNetwork.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotification(response.data.message, "success");
          if (callback) callback(true);

          // Refresh the site status table
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          showNotification(
            response.data || "Error applying robots.txt",
            "error"
          );
          if (callback) callback(false);
        }
      },
      error: function () {
        showNotification("Network error occurred", "error");
        if (callback) callback(false);
      },
    });
  }

  /**
   * Initialize Robots Modal
   */
  function initRobotsModal() {
    // Modal close events
    $(".robots-modal-close").on("click", function () {
      $("#robots-preview-modal").hide();
    });

    // Click outside modal to close
    $("#robots-preview-modal").on("click", function (e) {
      if (e.target === this) {
        $(this).hide();
      }
    });

    // Escape key to close modal
    $(document).on("keydown", function (e) {
      if (e.keyCode === 27 && $("#robots-preview-modal").is(":visible")) {
        $("#robots-preview-modal").hide();
      }
    });

    // Preview tab switching
    $(document).on("click", ".preview-tab", function () {
      $(".preview-tab").removeClass("active");
      $(this).addClass("active");

      var siteId = $(this).data("site");
      updateRobotsPreview(siteId);
    });
  }

  /**
   * Show Robots.txt Preview
   */
  function showRobotsPreview(robotsContent) {
    // Update preview content for main site
    var previewContent = robotsContent.replace(
      /%%sitemap_url%%/g,
      window.location.origin + "/sitemap.xml"
    );
    $("#robots-preview-text").text(previewContent);

    $("#robots-preview-modal").show();
  }

  /**
   * Update Robots Preview for specific site
   */
  function updateRobotsPreview(siteId) {
    var robotsContent = $("#network-robots-content").val();
    var sitemapUrl = window.location.origin;

    if (siteId !== "main") {
      // For subsites, you would need to get the actual site URL
      // This is a simplified version
      sitemapUrl += "/" + siteId + "/sitemap.xml";
    } else {
      sitemapUrl += "/sitemap.xml";
    }

    var previewContent = robotsContent.replace(/%%sitemap_url%%/g, sitemapUrl);
    $("#robots-preview-text").text(previewContent);
  }

  /**
   * Get Robots.txt Template
   */
  function getRobotsTemplate(templateType) {
    var templates = {
      standard: `# Standard Robots.txt
User-agent: *
Allow: /

# Block admin areas
Disallow: /wp-admin/
Disallow: /wp-login.php

# Block unnecessary files
Disallow: /wp-config.php
Disallow: /readme.html

# Allow important files
Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

# Sitemap
Sitemap: %%sitemap_url%%`,

      strict: `# Strict Robots.txt
User-agent: *
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /wp-content/plugins/
Disallow: /wp-content/themes/
Disallow: /trackback/
Disallow: /feed/
Disallow: /comments/
Disallow: /category/*/*
Disallow: */trackback/
Disallow: */feed/
Disallow: */comments/
Disallow: /*?*
Disallow: /*?
Allow: /wp-content/uploads/

# Sitemap
Sitemap: %%sitemap_url%%`,

      permissive: `# Permissive Robots.txt
User-agent: *
Allow: /

# Only block sensitive files
Disallow: /wp-config.php
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

# Sitemap
Sitemap: %%sitemap_url%%`,

      ecommerce: `# E-commerce Robots.txt
User-agent: *
Allow: /

# Block admin and checkout areas
Disallow: /wp-admin/
Disallow: /checkout/
Disallow: /cart/
Disallow: /my-account/
Disallow: /customer-logout/
Disallow: /wc-api/

# Block search and filter parameters
Disallow: /*?s=
Disallow: /*&s=
Disallow: /*?filter
Disallow: /*&filter

# Allow important areas
Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

# Sitemap
Sitemap: %%sitemap_url%%

# Crawl delay for heavy bots
User-agent: AhrefsBot
Crawl-delay: 10

User-agent: SemrushBot
Crawl-delay: 10`,
    };

    return templates[templateType] || templates["standard"];
  }

  /**
   * Activate License
   */
  function activateLicense(licenseKey) {
    var $btn = $("#activate-license");
    var originalText = $btn.text();

    $btn.text("Activating...").prop("disabled", true);

    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: {
        action: "softworld_network_activate_license",
        license_key: licenseKey,
        nonce: softworldNetwork.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotification(response.data.message, "success");
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          showNotification(
            response.data || "License activation failed",
            "error"
          );
          $btn.text(originalText).prop("disabled", false);
        }
      },
      error: function () {
        showNotification("Network error occurred", "error");
        $btn.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Deactivate License
   */
  function deactivateLicense() {
    var $btn = $("#deactivate-license");
    var originalText = $btn.text();

    $btn.text("Deactivating...").prop("disabled", true);

    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: {
        action: "softworld_network_deactivate_license",
        nonce: softworldNetwork.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotification(response.data, "success");
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          showNotification(
            response.data || "License deactivation failed",
            "error"
          );
          $btn.text(originalText).prop("disabled", false);
        }
      },
      error: function () {
        showNotification("Network error occurred", "error");
        $btn.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Save Network Settings
   */
  function saveNetworkSettings() {
    var $form = $("#softworld-network-settings-form");
    var $submitBtn = $form.find('button[type="submit"]');
    var originalText = $submitBtn.text();

    $submitBtn.text(softworldNetwork.i18n.saving).prop("disabled", true);

    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: $form.serialize() + "&action=softworld_network_save_settings",
      success: function (response) {
        if (response.success) {
          showNotification(response.data, "success");
          formChanged = false;
        } else {
          showNotification(response.data || "Save failed", "error");
        }
      },
      error: function () {
        showNotification("Network error occurred", "error");
      },
      complete: function () {
        $submitBtn.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Apply Settings to All Sites
   */
  function applySettingsToAllSites() {
    var $btn = $("#apply-to-all-sites");
    var originalText = $btn.text();

    $btn.text("Applying...").prop("disabled", true);

    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: {
        action: "softworld_network_apply_to_sites",
        nonce: softworldNetwork.nonce,
      },
      success: function (response) {
        if (response.success) {
          showNotification(response.data.message, "success");
        } else {
          showNotification(response.data || "Apply failed", "error");
        }
      },
      error: function () {
        showNotification("Network error occurred", "error");
      },
      complete: function () {
        $btn.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Refresh Network Stats
   */
  function refreshNetworkStats() {
    $.ajax({
      url: softworldNetwork.ajax_url,
      type: "POST",
      data: {
        action: "softworld_network_get_stats",
        nonce: softworldNetwork.nonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          updateNetworkStatsDisplay(response.data);
        }
      },
      error: function () {
        // Silently fail for background updates
      },
    });
  }

  /**
   * Update Network Stats Display
   */
  function updateNetworkStatsDisplay(stats) {
    if (stats.network_stats) {
      var ns = stats.network_stats;
      $(".network-stats")
        .find("li")
        .each(function (i) {
          switch (i) {
            case 0:
              $(this).text("Total Sites: " + ns.total_sites);
              break;
            case 1:
              $(this).text("Active Sites: " + ns.active_sites);
              break;
            case 2:
              $(this).text("Sites with Override: " + ns.override_sites);
              break;
            case 3:
              $(this).text("Sitemaps Generated: " + ns.sitemap_count);
              break;
          }
        });
    }
  }

  /**
   * Copy text to clipboard
   */
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text);
    } else {
      // Fallback for older browsers
      var textArea = document.createElement("textarea");
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      try {
        document.execCommand("copy");
      } catch (err) {
        console.error("Failed to copy text");
      }
      document.body.removeChild(textArea);
    }
  }

  /**
   * Show notification
   */
  function showNotification(message, type) {
    type = type || "info";

    var $notification = $(
      '<div class="notice notice-' +
        type +
        ' is-dismissible"><p>' +
        message +
        "</p></div>"
    );

    $(".wrap").prepend($notification);

    // Auto-dismiss after 5 seconds
    setTimeout(function () {
      $notification.fadeOut(function () {
        $(this).remove();
      });
    }, 5000);

    // Manual dismiss
    $notification.on("click", ".notice-dismiss", function () {
      $notification.fadeOut(function () {
        $(this).remove();
      });
    });
  }

  /**
   * Escape HTML
   */
  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // Global variable to track form changes
  var formChanged = false;
});
