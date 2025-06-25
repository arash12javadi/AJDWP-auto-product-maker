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
      AJDWP.ajax_url,
      {
        action: "ajdwp_add_template",
        _ajax_nonce: AJDWP.nonce,
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
      AJDWP.ajax_url,
      {
        action: "ajdwp_delete_template",
        id: id,
        _ajax_nonce: AJDWP.nonce,
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
      AJDWP.ajax_url,
      {
        action: "ajdwp_update_template",
        id: id,
        name: newName,
        _ajax_nonce: AJDWP.nonce,
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

  // ==============================
  // Template URL CRUD
  // ==============================

  // Add URL
  $(document).on("click", ".add-template-url", function () {
    const templateId = $(this).data("template-id");
    const input = $(`.new-template-url[data-template-id="${templateId}"]`);
    const url = input.val().trim();

    if (!url) return alert("Please enter a URL.");

    $.post(
      AJDWP.ajax_url,
      {
        action: "ajdwp_add_template_url",
        template_id: templateId,
        url: url,
        _ajax_nonce: AJDWP.nonce,
      },
      function (res) {
        if (res.success) {
          const html = `
            <li data-id="${res.data.id}">
              ${res.data.url}
              <button class="button delete-template-url" data-id="${res.data.id}">🗑️</button>
            </li>
          `;
          const urlList = $(`.url-list[data-template-id="${templateId}"]`);
          urlList.find("em").remove();
          urlList.append(html);
          input.val("");
        }
      }
    );
  });

  // Delete URL
  $(document).on("click", ".delete-template-url", function () {
    const id = $(this).data("id");
    if (!confirm("Delete this URL?")) return;

    $.post(
      AJDWP.ajax_url,
      {
        action: "ajdwp_delete_template_url",
        id: id,
        _ajax_nonce: AJDWP.nonce,
      },
      function (res) {
        if (res.success) {
          $(`li[data-id="${id}"]`).remove();
        }
      }
    );
  });

  // Load Template Panel
  $("#template_id").on("change", function () {
    const templateId = $(this).val();
    if (!templateId) return;

    $.ajax({
      method: "POST",
      url: AJDWP.ajax_url,
      data: {
        action: "ajdwp_get_template_panel",
        _ajax_nonce: AJDWP.nonce,
        template_id: templateId,
      },
      success: function (response) {
        if (response.success) {
          $("#selected-template-panel").html(response.data.html);
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
  // Inline Editable Selector Fields
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
      AJDWP.ajax_url,
      {
        action: "ajdwp_update_single_template_field",
        _ajax_nonce: AJDWP.nonce,
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
