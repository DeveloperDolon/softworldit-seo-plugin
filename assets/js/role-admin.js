jQuery(document).ready(function ($) {
  // Add visual feedback for role selection
  $('.role-checkbox-group input[type="checkbox"]').on("change", function () {
    if (this.checked) {
      $(this).parent().addClass("role-selected");
    } else {
      $(this).parent().removeClass("role-selected");
    }
  });

  // Initialize selected state on page load
  $('.role-checkbox-group input[type="checkbox"]:checked')
    .parent()
    .addClass("role-selected");

  // Prevent deselecting all roles from allowed access
  $("form").on("submit", function (e) {
    var allowedRoles = $(
      'input[name="softworld_ai_seo_roles[allowed_roles][]"]:checked'
    ).length;
    if (allowedRoles === 0) {
      e.preventDefault();
      alert("At least one role must have access to plugin settings.");
    }
  });
});
