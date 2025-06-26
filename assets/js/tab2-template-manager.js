jQuery(function ($) {
  // ==============================
  // Template URL CRUD
  // ==============================

  // Load Template Panel
  $("#template_id").on("change", function () {
    const templateId = $(this).val();
    if (!templateId) return;

    $.ajax({
      method: "POST",
      url: AJDWP_tab2.ajax_url,
      data: {
        action: "ajdwp_get_template_panel",
        _ajax_nonce: AJDWP_tab2.nonce,
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
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_update_single_template_field",
        _ajax_nonce: AJDWP_tab2.nonce,
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

  // ========================
  // Delete Single Product
  // ========================
  $(document).on("click", ".ajdwp-action-delete", function () {
    const productId = $(this).data("id");
    if (!confirm("Are you sure you want to delete this item?")) return;

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_delete_product_url",
        _ajax_nonce: AJDWP_tab2.nonce,
        id: productId,
      },
      function (res) {
        if (res.success) {
          $('tr[data-id="' + productId + '"]').remove();
        } else {
          alert("❌ Delete failed.");
        }
      }
    );
  });

  // ========================
  // Update Price
  // ========================
  $(document).on("click", ".ajdwp-action-update-price", function () {
    const productId = $(this).data("id");

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_update_price",
        _ajax_nonce: AJDWP_tab2.nonce,
        id: productId,
      },
      function (res) {
        if (res.success) {
          alert("✅ Price updated to £" + res.data.price);
        } else {
          alert("❌ Price update failed.");
        }
      }
    );
  });

  // ========================
  // Full Update
  // ========================
  $(document).on("click", ".ajdwp-action-full-update", function () {
    const productId = $(this).data("id");

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_full_update_product",
        _ajax_nonce: AJDWP_tab2.nonce,
        id: productId,
      },
      function (res) {
        if (res.success) {
          alert("✅ Product fully updated.");
        } else {
          alert("❌ Full update failed.");
        }
      }
    );
  });

  // ========================
  // Bulk Action
  // ========================
  $("#ajdwp-do-bulk-action").on("click", function () {
    const action = $("#ajdwp-bulk-action-top").val();
    const ids = $("input[name='product_ids[]']:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    if (action === "-1" || ids.length === 0) {
      alert("Please select action and at least one product.");
      return;
    }

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_bulk_product_action",
        _ajax_nonce: AJDWP_tab2.nonce,
        sub_action: action,
        ids: ids,
      },
      function (res) {
        if (res.success) {
          alert("✅ Bulk action completed.");
          location.reload();
        } else {
          alert("❌ Bulk action failed.");
        }
      }
    );
  });
});
