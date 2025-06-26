jQuery(function ($) {
  // ==============================
  // Template CRUD
  // ==============================

  // Add template
  $(document).on("click", "#add-template-btn", function () {
    const wrapper = $("#ajdwp-add-template");
    const name = $("#new-template-name").val().trim();
    if (!name) return alert("Template name is required.");

    $.post(
      AJDWP_tab3.ajax_url,
      {
        action: "ajdwp_add_template",
        _ajax_nonce: AJDWP_tab3.nonce,
        name: name,
        scrape_method: wrapper.find("select[name='scrape_method']").val(),
        title_selector: wrapper.find("input[name='title_selector']").val().trim(),
        short_desc_selector: wrapper.find("input[name='short_desc_selector']").val().trim(),
        long_desc_selector: wrapper.find("input[name='long_desc_selector']").val().trim(),
        main_image_selector: wrapper.find("input[name='main_image_selector']").val().trim(),
        gallery_image_selectors: wrapper.find("input[name='gallery_image_selectors']").val().trim(),
        price_selector: wrapper.find("input[name='price_selector']").val().trim(),
        price_multiplier: wrapper.find("input[name='price_multiplier']").val().trim(),
      },
      function (res) {
        if (res.success) {
          alert("✅ Template added successfully.");
          location.reload();
        } else {
          alert("❌ Failed to add template.");
          console.error(res);
        }
      }
    );
  });

  // Delete template
  $(document).on("click", ".delete-template", function () {
    const id = $(this).data("id");
    if (!confirm("Delete this template? URLs will be moved to the Default Template.")) return;

    $.post(
      AJDWP_tab3.ajax_url,
      {
        action: "ajdwp_delete_template",
        id: id,
        _ajax_nonce: AJDWP_tab3.nonce,
      },
      function (res) {
        if (res.success) {
          alert("✅ Template deleted successfully.");
          location.reload();
        } else {
          alert("❌ Failed to delete template.");
        }
      }
    );
  });

  // Rename template
  $(document).on("click", ".rename-template", function () {
    const id = $(this).data("id");
    const currentName = $(".ajdwp-template-header h2").text().trim();
    const newName = prompt("Enter new template name:", currentName);
    if (!newName || newName === currentName) return;

    $.post(
      AJDWP_tab3.ajax_url,
      {
        action: "ajdwp_update_template",
        id: id,
        name: newName,
        _ajax_nonce: AJDWP_tab3.nonce,
      },
      function (res) {
        if (res.success) {
          alert("✅ Template renamed successfully.");
          location.reload();
        } else {
          alert("❌ Failed to rename template.");
        }
      }
    );
  });
});
