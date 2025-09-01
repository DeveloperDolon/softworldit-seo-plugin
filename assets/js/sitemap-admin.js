jQuery(document).ready(function ($) {
  // Post search functionality
  $("#softworld-search-post-btn").on("click", function () {
    var searchTerm = $("#softworld-post-search").val();
    if (searchTerm.length < 2) {
      alert("Please enter at least 2 characters to search");
      return;
    }

    $.ajax({
      url: softworld_sitemap.ajax_url,
      type: "GET",
      data: {
        action: "softworld_search_posts",
        term: searchTerm,
        nonce: softworld_sitemap.search_posts_nonce,
      },
      beforeSend: function () {
        $("#softworld-post-results")
          .html('<div class="softworld-loading">Searching...</div>')
          .show();
      },
      success: function (response) {
        if (response.length > 0) {
          var html =
            '<div class="softworld-results-header">Search Results</div>';
          $.each(response, function (index, post) {
            html +=
              '<div class="softworld-result-item" data-id="' + post.id + '">';
            html +=
              '<span class="softworld-result-title">' + post.label + "</span>";
            html +=
              '<button type="button" class="button button-small softworld-add-item">Exclude</button>';
            html += "</div>";
          });
          $("#softworld-post-results").html(html).show();
        } else {
          $("#softworld-post-results")
            .html('<div class="softworld-no-results">No posts found</div>')
            .show();
        }
      },
    });
  });

  // Page search functionality
  $("#softworld-search-page-btn").on("click", function () {
    var searchTerm = $("#softworld-page-search").val();
    if (searchTerm.length < 2) {
      alert("Please enter at least 2 characters to search");
      return;
    }

    $.ajax({
      url: softworld_sitemap.ajax_url,
      type: "GET",
      data: {
        action: "softworld_search_pages",
        term: searchTerm,
        nonce: softworld_sitemap.search_pages_nonce,
      },
      beforeSend: function () {
        $("#softworld-page-results")
          .html('<div class="softworld-loading">Searching...</div>')
          .show();
      },
      success: function (response) {
        if (response.length > 0) {
          var html =
            '<div class="softworld-results-header">Search Results</div>';
          $.each(response, function (index, page) {
            html +=
              '<div class="softworld-result-item" data-id="' + page.id + '">';
            html +=
              '<span class="softworld-result-title">' + page.label + "</span>";
            html +=
              '<button type="button" class="button button-small softworld-add-item">Exclude</button>';
            html += "</div>";
          });
          $("#softworld-page-results").html(html).show();
        } else {
          $("#softworld-page-results")
            .html('<div class="softworld-no-results">No pages found</div>')
            .show();
        }
      },
    });
  });

  // Add item to exclusion list
  $(document).on("click", ".softworld-add-item", function () {
    var item = $(this).closest(".softworld-result-item");
    var id = item.data("id");
    var title = item.find(".softworld-result-title").text();

    // Check if already excluded
    if (
      $("#softworld-excluded-posts").find('input[value="' + id + '"]').length >
        0 ||
      $("#softworld-excluded-pages").find('input[value="' + id + '"]').length >
        0
    ) {
      alert("This item is already excluded");
      return;
    }

    // Determine if it's a post or page based on which results we're in
    var isPost = item.closest("#softworld-post-results").length > 0;
    var container = isPost
      ? "#softworld-excluded-posts"
      : "#softworld-excluded-pages";

    // Add to exclusion list
    var html = '<div class="softworld-excluded-item" data-id="' + id + '">';
    html += '<span class="softworld-item-title">' + title + "</span>";
    html +=
      '<button type="button" class="softworld-remove-item">Remove</button>';
    html +=
      '<input type="hidden" name="' +
      (isPost ? "excluded_posts[]" : "excluded_pages[]") +
      '" value="' +
      id +
      '">';
    html += "</div>";

    $(container).append(html);
    $(container).find(".softworld-no-items").remove();

    // Remove from search results
    item.remove();

    // Hide results if empty
    if ($(".softworld-result-item").length === 0) {
      $("#softworld-post-results, #softworld-page-results").hide();
    }
  });

  // Remove item from exclusion list
  $(document).on("click", ".softworld-remove-item", function () {
    $(this).closest(".softworld-excluded-item").remove();

    // Show no items message if empty
    if (
      $("#softworld-excluded-posts").children(".softworld-excluded-item")
        .length === 0
    ) {
      $("#softworld-excluded-posts").html(
        '<p class="softworld-no-items">No posts excluded</p>'
      );
    }
    if (
      $("#softworld-excluded-pages").children(".softworld-excluded-item")
        .length === 0
    ) {
      $("#softworld-excluded-pages").html(
        '<p class="softworld-no-items">No pages excluded</p>'
      );
    }
  });

  // Enter key support in search fields
  $("#softworld-post-search, #softworld-page-search").on(
    "keypress",
    function (e) {
      if (e.which === 13) {
        e.preventDefault();
        var isPost = $(this).attr("id") === "softworld-post-search";
        if (isPost) {
          $("#softworld-search-post-btn").click();
        } else {
          $("#softworld-search-page-btn").click();
        }
      }
    }
  );

  // Priority slider functionality
  $(".softworld-priority-slider").on("input", function () {
    var value = $(this).val();
    var container = $(this).closest(".softworld-priority-control");
    container.find(".softworld-priority-value").text(value);
    container.find(".softworld-priority-indicator").css("--priority", value);
  });

  // Initialize priority sliders
  $(".softworld-priority-slider").each(function () {
    var value = $(this).val();
    var container = $(this).closest(".softworld-priority-control");
    container.find(".softworld-priority-value").text(value);
    container.find(".softworld-priority-indicator").css("--priority", value);
  });

  // Tooltip functionality
  $(".softworld-tooltip")
    .on("mouseenter", function () {
      $(this).find(".softworld-tooltip-text").fadeIn(200);
    })
    .on("mouseleave", function () {
      $(this).find(".softworld-tooltip-text").fadeOut(200);
    });

  // Expand/collapse sections
  $(".softworld-section-toggle").on("click", function () {
    var section = $(this).closest(".softworld-sitemap-section");
    var content = section.find(".softworld-section-content");
    content.slideToggle(300);
    $(this).toggleClass("softworld-section-expanded");
  });

  // Manual ping functionality
  $("#softworld-manual-ping").on("click", function (e) {
    e.preventDefault();

    var button = $(this);
    var originalText = button.text();

    button.text("Pinging...").prop("disabled", true);

    $.ajax({
      url: softworld_sitemap.ajax_url,
      type: "POST",
      data: {
        action: "softworld_manual_ping",
        nonce: softworld_sitemap.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert("Search engines pinged successfully!");
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Error pinging search engines. Please try again.");
      },
      complete: function () {
        button.text(originalText).prop("disabled", false);
      },
    });
  });

  // Toggle search engine options based on main ping setting
  $('input[name="ping_search_engines"]')
    .on("change", function () {
      var isEnabled = $(this).is(":checked");
      $(
        'input[name="ping_google"], input[name="ping_bing"], input[name="ping_yandex"]'
      ).prop("disabled", !isEnabled);
    })
    .trigger("change");

  // Image exclusion explanation
  $('input[name="exclude_images"]')
    .on("change", function () {
      if ($(this).is(":checked")) {
        $("#softworld-image-exclusion-info").show();
      } else {
        $("#softworld-image-exclusion-info").hide();
      }
    })
    .trigger("change");
});
