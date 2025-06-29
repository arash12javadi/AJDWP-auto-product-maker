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
        short_description_selector: wrapper.find("input[name='short_description_selector']").val().trim(),
        long_description_selector: wrapper.find("input[name='long_description_selector']").val().trim(),
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

document.querySelectorAll(".temp-default-exp").forEach((span) => {
  span.style.cursor = "pointer";
  span.title = "Click to use this default";

  span.addEventListener("click", function () {
    const id = this.id;
    const input = document.querySelector(`input[name="${id}"]`);
    if (input) {
      input.value = this.textContent.trim();
      input.focus();
    }
  });
});

//<p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>;
//<p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>;
//<p>⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘⫘</p>;

jQuery(function ($) {
  // ==============================
  // Select a Template and get its data
  // ==============================

  $("#tab3_template_id").on("change", function () {
    const templateId = $(this).val();
    if (!templateId) return;

    $.ajax({
      method: "POST",
      url: AJDWP_tab3.ajax_url,
      data: {
        action: "ajdwp_tab3_get_template_panel",
        _ajax_nonce: AJDWP_tab3.nonce,
        template_id: templateId,
      },
      success: function (response) {
        if (response.success) {
          $("#tab3-selected-template-panel").html(response.data.html);
          $(document).trigger("ajdwp-panel-loaded");
        } else {
          alert(response.data.message || "Unknown error");
        }
      },
      error: function (xhr) {
        alert("AJAX failed: " + xhr.status);
        console.error("AJAX Error:", xhr);
      },
    });
  });

  // ==============================
  // Editable Selector Fields
  // ==============================
  $(document).on("click", ".ajdwp-editable-selector", function () {
    const el = $(this);
    const field = el.data("field");
    const current = el.data("value");
    const templateId = el.data("id");
    let label = el.closest("tr").find("th").text();

    const newVal = prompt(`Edit ${label}`, current);
    if (newVal === null || newVal === current) return;

    $.post(
      AJDWP_tab3.ajax_url,
      {
        action: "ajdwp_update_single_template_field",
        _ajax_nonce: AJDWP_tab3.nonce,
        id: templateId,
        field: field,
        value: newVal,
      },
      function (res) {
        if (res.success) {
          el.text(newVal).data("value", newVal);
        } else {
          alert("❌ Update failed.");
        }
      }
    );
  });
});
