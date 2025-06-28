jQuery(function ($) {
  // ==============================
  // Template URL CRUD
  // ==============================

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

  // ==============================
  // Delete Product
  // ==============================
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
          $(`tr[data-id="${productId}"]`).remove();
        } else {
          alert("❌ Delete failed.");
        }
      }
    );
  });

  // ==============================
  // Update Price
  // ==============================
  $(document).on("click", ".ajdwp-action-update-price", function () {
    const button = $(this);
    const customId = button.data("id");
    const wcProductId = button.data("product-id");
    const priceCell = $(`#product-price-${wcProductId}`);
    priceCell.html("<em>Updating...</em>");

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_update_price",
        _ajax_nonce: AJDWP_tab2.nonce,
        id: customId,
      },
      function (res) {
        if (res.success && res.data.product_id && res.data.price) {
          const newPrice = parseFloat(res.data.price).toFixed(2);
          const oldText = priceCell.text().replace("£", "").trim();
          const oldPrice = parseFloat(oldText) || 0;

          let html = "";
          if (newPrice == oldPrice.toFixed(2)) {
            html = `<span style="color: gray;">£${newPrice}</span>`;
          } else {
            html = `
              <span style="color: red; text-decoration: line-through; margin-right: 5px;">£${oldPrice.toFixed(2)}</span>
              <span style="color: green; font-weight: bold;">£${newPrice}</span>`;
          }
          priceCell.html(html);
        } else {
          alert("❌ Price update failed.");
        }
      }
    );
  });

  // ==============================
  // Full Update
  // ==============================
  $(document).on("click", ".ajdwp-action-full-update", function () {
    const button = $(this);
    const customId = button.data("id");
    const wcProductId = button.data("product-id");

    const priceCell = $(`#product-price-${wcProductId}`);
    const titleCell = $(`#product-title-${wcProductId}`);
    const oldPrice = priceCell.text().replace("£", "").trim();
    const oldTitle = titleCell.text().trim();

    priceCell.html("<em>Updating...</em>");
    titleCell.html("<em>Updating...</em>");

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_full_update_product",
        _ajax_nonce: AJDWP_tab2.nonce,
        id: customId,
      },
      function (res) {
        if (res.success) {
          const { price: newPrice, title: newTitle, edit_link: editLink } = res.data;

          // Update Price
          if (oldPrice !== newPrice) {
            priceCell.html(`
              <span style="color: red; text-decoration: line-through; margin-right: 5px;">£${oldPrice}</span>
              <span style="color: green; font-weight: bold;">£${newPrice}</span>`);
          } else {
            priceCell.html(`<span style="color: gray;">£${newPrice}</span>`);
          }

          // Update Title
          if (oldTitle !== newTitle) {
            const newTitleHtml = editLink
              ? `<a href="${editLink}" target="_blank" style="color: green; font-weight: bold;">${newTitle}</a>`
              : `<span style="color: green; font-weight: bold;">${newTitle}</span>`;
            titleCell.html(`
              <span style="color: red; text-decoration: line-through; margin-right: 5px;">${oldTitle}</span>
              ${newTitleHtml}`);
          } else {
            titleCell.html(
              editLink ? `<a href="${editLink}" target="_blank" style="color: gray;">${newTitle}</a>` : `<span style="color: gray;">${newTitle}</span>`
            );
          }
        } else {
          alert("❌ Full update failed.");
          priceCell.html(`<span style="color:red;">£${oldPrice}</span>`);
          titleCell.html(`<span style="color:red;">${oldTitle}</span>`);
        }
      }
    );
  });

  // ========================
  // Bulk Action
  // ========================
  $(document).on("click", "#ajdwp-do-bulk-action", function () {
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

    console.log("🔍 Bulk action:", action);
    console.log("🧾 Selected Product IDs:", ids);

    // Log which selector you're using if needed (example: price selector)
    ids.forEach((id) => {
      const row = $(`tr[data-id] input[value='${id}']`).closest("tr");
      const title = row.find("td:nth-child(4)").text().trim(); // title column
      const price = row.find("td:nth-child(6)").text().trim(); // price column
      console.log(`🧪 ID: ${id}, Title: ${title}, Price: ${price}`);
    });

    // Then send AJAX
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
          console.log(res);
        }
      }
    );
  });

  // ==============================
  // Bulk Multiplier
  // ==============================
  $(document).on("click", "#ajdwp-bulk-multiplier-all", function () {
    const formula = $("#input_price_multiplier_all").val();
    const ids = $("input[name='product_ids[]']:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    if (!formula || ids.length === 0) {
      alert("Please enter a multiplier formula and select products.");
      return;
    }

    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_bulk_price_multiplier",
        _ajax_nonce: AJDWP_tab2.nonce,
        formula: formula,
        ids: ids,
      },
      function (res) {
        if (res.success) {
          alert("✅ Price multiplier applied.");
          location.reload();
        } else {
          alert("❌ Multiplier failed: " + (res.data || "Unknown error"));
        }
      }
    );
  });

  // ==============================
  // Search & Sort Template Products
  // ==============================
  function ajdwpInitSearchAndSort() {
    let timer;
    let sortField = "id";
    let sortOrder = "asc";

    function fetchProducts(query = "") {
      const templateId = $("#ajdwp-product-search").data("template-id");
      if (!templateId) return;

      $.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
          action: "ajdwp_search_template_products",
          security: AJDWP_tab2.nonce,
          template_id: templateId,
          query: query,
          sort_field: sortField,
          sort_order: sortOrder,
        },
        success: function (res) {
          if (res.success) {
            $("#ajdwp-products-tbody").html(res.data.html);
            updateSortIcons();
          } else {
            console.warn("Search failed:", res.data.message);
          }
        },
      });
    }

    function updateSortIcons() {
      $(".sortable").removeClass("asc desc");
      $(`.sortable[data-sort="${sortField}"]`).addClass(sortOrder);
    }

    $(document)
      .off("input", "#ajdwp-product-search")
      .on("input", "#ajdwp-product-search", function () {
        const q = $(this).val();
        clearTimeout(timer);
        timer = setTimeout(() => fetchProducts(q), 300);
      });

    $(document)
      .off("click", ".sortable")
      .on("click", ".sortable", function () {
        const newField = $(this).data("sort");
        if (!newField) return;

        if (sortField === newField) {
          sortOrder = sortOrder === "asc" ? "desc" : "asc";
        } else {
          sortField = newField;
          sortOrder = "asc";
        }

        fetchProducts($("#ajdwp-product-search").val());
      });

    updateSortIcons();
  }

  ajdwpInitSearchAndSort();
  $(document).on("ajdwp-panel-loaded", ajdwpInitSearchAndSort);
});
