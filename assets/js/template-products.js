jQuery(function ($) {
  // ========================
  // Delete Single Product
  // ========================
  $(document).on("click", ".ajdwp-action-delete", function () {
    const productId = $(this).data("id");
    if (!confirm("Are you sure you want to delete this item?")) return;

    $.post(
      AJDWP.ajax_url,
      {
        action: "ajdwp_delete_product_url",
        _ajax_nonce: AJDWP.nonce,
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
      AJDWP.ajax_url,
      {
        action: "ajdwp_update_price",
        _ajax_nonce: AJDWP.nonce,
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
      AJDWP.ajax_url,
      {
        action: "ajdwp_full_update_product",
        _ajax_nonce: AJDWP.nonce,
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
      AJDWP.ajax_url,
      {
        action: "ajdwp_bulk_product_action",
        _ajax_nonce: AJDWP.nonce,
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
