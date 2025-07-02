//_____________________________________ tab2-product-manager.js _____________________________________//

jQuery(function ($) {
  // ==============================
  // Select a Template and get its data
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
  // Single Product - Delete
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
  // Single Product - Update Price
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
          const originalPriceAttr = priceCell.data("original-price");
          const oldPrice = parseFloat(originalPriceAttr) || 0;

          let html = "";
          if (newPrice == oldPrice.toFixed(2)) {
            html = `<span style="color: gray;">${AJDWP_tab2.currency}${newPrice}</span>`;
          } else {
            html = `
              <span style="color: red; text-decoration: line-through; margin-right: 5px;">${AJDWP_tab2.currency}${oldPrice.toFixed(2)}</span>
              <span style="color: green; font-weight: bold;">${AJDWP_tab2.currency}${newPrice}</span>`;
          }
          priceCell.html(html);
        } else {
          alert("❌ Price update failed.");
        }
      }
    );
  });

  // ==============================
  // Single Product - Full Update
  // ==============================
  $(document).on("click", ".ajdwp-action-full-update", function () {
    const button = $(this);
    const customId = button.data("id");
    const productId = button.data("product-id");

    const priceCell = $(`#product-price-${productId}`);
    const titleCell = $(`#product-title-${productId}`);

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

          // ✅ Price handling
          const originalPriceAttr = priceCell.data("original-price");
          const oldPrice = parseFloat(originalPriceAttr) || 0;

          if (parseFloat(newPrice) !== oldPrice) {
            priceCell.html(`
            <span style="color: red; text-decoration: line-through; margin-right: 5px;">${AJDWP_tab2.currency}${oldPrice.toFixed(2)}</span>
            <span style="color: green; font-weight: bold;">${AJDWP_tab2.currency}${parseFloat(newPrice).toFixed(2)}</span>`);
          } else {
            priceCell.html(`<span style="color: gray;">${AJDWP_tab2.currency}${parseFloat(newPrice).toFixed(2)}</span>`);
          }

          // ✅ Title handling
          const oldTitle = titleCell.data("original-title") || "";

          const titleHtml = editLink ? `<span  style="color: gray;">${newTitle}</span>` : `<span style="color: green; font-weight: bold;">${newTitle}</span>`;

          // Decode entities for accurate comparison
          const decode = (str) => $("<textarea>").html(str).text().trim().toLowerCase();

          if (decode(oldTitle) !== decode(newTitle)) {
            titleCell.html(`
            <span style="color: red; text-decoration: line-through; margin-right: 5px;">${oldTitle}</span>
            ${titleHtml}`);
          } else {
            titleCell.html(titleHtml);
          }

          // ✅ Update data attributes
          priceCell.attr("data-original-price", newPrice);
          titleCell.attr("data-original-title", newTitle);
        } else {
          priceCell.html(`<span style="color:red;">❌</span>`);
          titleCell.html(`<span style="color:red;">❌</span>`);
          console.warn("❌ Full update failed:", res);
        }
      }
    );
  });

  // ==============================
  // Bulk Action - Full Update All
  // ==============================
  $(document).on("click", "#ajdwp-do-bulk-action", function () {
    const action = $("#ajdwp-bulk-action-top").val();
    const ids = $("input[name='product_custom_ids[]']:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    if (action === "-1" || ids.length === 0) {
      alert("Please select action and at least one product.");
      return;
    }

    // ✅ Custom modal for delete_all
    if (action === "delete_all") {
      const productList = $("#ajdwp-delete-product-list");
      productList.empty();

      ids.forEach((customId) => {
        const row = $(`tr[data-id='${customId}']`);
        const title = row.find(".editable-title").text().trim();
        const pid = row.data("product-id") || "—";
        productList.append(`<li><strong>#${pid}</strong> – ${title}</li>`);
      });

      $("#ajdwp-delete-confirm-modal").fadeIn();

      // Cancel: close modal and do nothing
      $("#ajdwp-delete-cancel")
        .off("click")
        .on("click", function () {
          $("#ajdwp-delete-confirm-modal").fadeOut();
        });

      // Confirm: send AJAX and delete
      $("#ajdwp-delete-confirm")
        .off("click")
        .on("click", function () {
          $("#ajdwp-delete-confirm-modal").fadeOut();

          $.post(
            AJDWP_tab2.ajax_url,
            {
              action: "ajdwp_bulk_product_action",
              _ajax_nonce: AJDWP_tab2.nonce,
              sub_action: "delete_all",
              ids: ids,
            },
            function (res) {
              if (res.success) {
                ids.forEach((customId) => {
                  const row = $(`tr[data-id='${customId}']`);
                  row.fadeOut(300, function () {
                    $(this).remove();
                  });
                });
              } else {
                alert("❌ Bulk delete failed.");
                console.log(res);
              }
            }
          );
        });

      return; // 🛑 Don't continue below
    }

    // ✅ For all other actions (update_price_all, full_update_all)
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
          ids.forEach((customId) => {
            const row = $(`tr[data-id='${customId}']`);
            const wcProductId = row.data("product-id") || row.find("td:nth-child(2)").text().trim();
            const priceCell = $(`#product-price-${wcProductId}`);
            const titleCell = $(`#product-title-${wcProductId}`);

            if (action === "update_price_all") {
              priceCell.html("<em>Updating...</em>");
              $.post(
                AJDWP_tab2.ajax_url,
                {
                  action: "ajdwp_update_price",
                  _ajax_nonce: AJDWP_tab2.nonce,
                  id: customId,
                },
                function (res2) {
                  if (res2.success && res2.data.product_id && res2.data.price) {
                    const newPrice = parseFloat(res2.data.price).toFixed(2);
                    const originalPriceAttr = priceCell.data("original-price");
                    const oldPrice = parseFloat(originalPriceAttr) || 0;

                    let html = "";
                    if (newPrice == oldPrice.toFixed(2)) {
                      html = `<span style="color: gray;">${AJDWP_tab2.currency}${newPrice}</span>`;
                    } else {
                      html = `
                      <span style="color: red; text-decoration: line-through; margin-right: 5px;">${AJDWP_tab2.currency}${oldPrice.toFixed(2)}</span>
                      <span style="color: green; font-weight: bold;">${AJDWP_tab2.currency}${newPrice}</span>`;
                    }
                    priceCell.html(html);
                    priceCell.attr("data-original-price", newPrice);
                  } else {
                    priceCell.html(`<span style="color:red;">❌ Error</span>`);
                  }
                }
              );
            }

            if (action === "full_update_all") {
              priceCell.html("<em>Updating...</em>");
              titleCell.html("<em>Updating...</em>");

              $.post(
                AJDWP_tab2.ajax_url,
                {
                  action: "ajdwp_full_update_product",
                  _ajax_nonce: AJDWP_tab2.nonce,
                  id: customId,
                },
                function (res2) {
                  if (res2.success) {
                    const { price: newPrice, title: newTitle, edit_link: editLink } = res2.data;
                    const originalPriceAttr = priceCell.data("original-price");
                    const oldPrice = parseFloat(originalPriceAttr) || 0;

                    if (parseFloat(newPrice) !== oldPrice) {
                      priceCell.html(`
                      <span style="color: red; text-decoration: line-through; margin-right: 5px;">${AJDWP_tab2.currency}${oldPrice.toFixed(2)}</span>
                      <span style="color: green; font-weight: bold;">${AJDWP_tab2.currency}${parseFloat(newPrice).toFixed(2)}</span>`);
                    } else {
                      priceCell.html(`<span style="color: gray;">${AJDWP_tab2.currency}${parseFloat(newPrice).toFixed(2)}</span>`);
                    }

                    const oldTitle = titleCell.data("original-title") || "";
                    const titleHtml = editLink
                      ? `<a href="${editLink}" target="_blank" style="color: green; font-weight: bold;">${newTitle}</a>`
                      : `<span style="color: green; font-weight: bold;">${newTitle}</span>`;

                    const decode = (str) => $("<textarea>").html(str).text().trim().toLowerCase();
                    if (decode(oldTitle) !== decode(newTitle)) {
                      titleCell.html(`
                      <span style="color: red; text-decoration: line-through; margin-right: 5px;">${oldTitle}</span>
                      ${titleHtml}`);
                    } else {
                      titleCell.html(titleHtml);
                    }

                    priceCell.attr("data-original-price", newPrice);
                    titleCell.attr("data-original-title", newTitle);
                  } else {
                    priceCell.html(`<span style="color:red;">❌</span>`);
                    titleCell.html(`<span style="color:red;">❌</span>`);
                  }
                }
              );
            }
          });
        } else {
          alert("❌ Bulk action failed.");
          console.log(res);
        }
      }
    );
  });

  // ==============================
  // Bulk Action - Multiplier
  // ==============================
  $(document).on("click", "#ajdwp-bulk-multiplier-all", function () {
    const formula = $("#input_price_multiplier_all").val();
    const selectedCheckboxes = $("input[name='product_custom_ids[]']:checked");

    const ids = selectedCheckboxes
      .map(function () {
        return $(this).val();
      })
      .get();

    if (!formula || ids.length === 0) {
      alert("Please enter a multiplier formula and select products.");
      return;
    }

    // ⏳ Show "Updating..." in each selected price cell
    selectedCheckboxes.each(function () {
      const customId = $(this).val();
      const productId = $(this).closest("tr").data("product-id");
      const priceCell = $(`#product-price-${productId}`);
      priceCell.html("<em>Updating...</em>");
    });

    // 🔁 Send AJAX to backend
    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_bulk_price_multiplier",
        _ajax_nonce: AJDWP_tab2.nonce,
        formula: formula,
        ids: ids,
      },
      function (res) {
        if (res.success && res.data?.updated_prices) {
          res.data.updated_prices.forEach((entry) => {
            const productId = entry.product_id;
            const newPrice = parseFloat(entry.new_price).toFixed(2);
            const priceCell = $(`#product-price-${productId}`);

            // Extract old price from original cell content, if available
            const oldText = priceCell.text().replace(AJDWP_tab2.currency, "").trim();
            const oldPrice = parseFloat(oldText) || 0;

            let html = "";
            if (newPrice == oldPrice.toFixed(2)) {
              html = `<span style="color: gray;">${AJDWP_tab2.currency}${newPrice}</span>`;
            } else {
              html = `
              <span style="color: red; text-decoration: line-through; margin-right: 5px;">${AJDWP_tab2.currency}${oldPrice.toFixed(2)}</span>
              <span style="color: green; font-weight: bold;">${AJDWP_tab2.currency}${newPrice}</span>`;
            }

            priceCell.html(html);
          });

          // alert("✅ Price multiplier applied.");
        } else {
          alert("❌ Multiplier failed: " + (res.data?.message || "Unknown error"));
        }
      }
    );
  });
}); // _____________________________________ END JQuery_____________________________________//

// ==============================
// Search & Sort Template Products
// ==============================
jQuery(function ($) {
  let sortField = "id",
    sortOrder = "asc";
  let lastPage = 1,
    totalPages = 1;
  let searchTimer;

  function loadPage(page) {
    const tpl = $("#ajdwp-product-search").data("template-id");
    const qry = $("#ajdwp-product-search").val() || "";
    const fld = sortField;
    const ord = sortOrder;
    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_table_list_products",
        security: AJDWP_tab2.nonce,
        template_id: tpl,
        query: qry,
        sort_field: fld,
        sort_order: ord,
        page: page,
      },
      function (res) {
        if (!res.success) {
          $("#ajdwp-products-tbody").html("<tr><td colspan='10'>Failed to load.</td></tr>");
          return;
        }
        $("#ajdwp-products-tbody").html(res.data.html);
        $("#ajdwp-pagination").html(res.data.pagination);
        lastPage = page;
        totalPages = res.data.total_pages;
        bindPagination();
      }
    );
  }

  function bindPagination() {
    $(document)
      .off("click", ".ajdwp-pagination-btn, .ajdwp-pagination-prev, .ajdwp-pagination-next")
      .on("click", ".ajdwp-pagination-btn", function (e) {
        e.preventDefault();
        const p = parseInt($(this).data("page"), 10);
        if (p && p !== lastPage) loadPage(p);
      })
      .on("click", ".ajdwp-pagination-prev", function (e) {
        e.preventDefault();
        if (lastPage > 1) loadPage(lastPage - 1);
      })
      .on("click", ".ajdwp-pagination-next", function (e) {
        e.preventDefault();
        if (lastPage < totalPages) loadPage(lastPage + 1);
      });
  }

  // Search box
  $(document)
    .off("input", "#ajdwp-product-search")
    .on("input", "#ajdwp-product-search", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => loadPage(1), 300);
    });

  // Sort headers
  $(document)
    .off("click", ".sortable")
    .on("click", ".sortable", function () {
      const nf = $(this).data("sort");
      if (!nf) return;
      if (sortField === nf) {
        sortOrder = sortOrder === "asc" ? "desc" : "asc";
      } else {
        sortField = nf;
        sortOrder = "asc";
      }
      $(".sortable").removeClass("asc desc");
      $(`.sortable[data-sort="${sortField}"]`).addClass(sortOrder);
      loadPage(1);
    });

  // On panel load
  $(document).on("ajdwp-panel-loaded", function () {
    sortField = "id";
    sortOrder = "asc";
    $(".sortable").removeClass("asc desc");
    $(`.sortable[data-sort="${sortField}"]`).addClass(sortOrder);
    loadPage(1);
  });

  // If panel already there
  if ($("#ajdwp-products-tbody").length) {
    loadPage(1);
  }
});

//==========================
//  inline product title and price edit popup
//==========================

jQuery(function ($) {
  // Show popup for title
  $(document).on("click", ".editable-title", function () {
    const cid = $(this).data("id");
    const wid = $(this).data("product-id");
    const title = $(this).data("original-title") || $(this).text().trim();

    $("#ajdwp-edit-popup-title").text("Edit Product Title");
    $("#ajdwp-edit-popup-type").val("title");
    $("#ajdwp-edit-popup-cid").val(cid);
    $("#ajdwp-edit-popup-wid").val(wid);
    $("#ajdwp-edit-popup-field").html(
      `<input type="text" id="ajdwp-edit-popup-input" class="regular-text" value="${$("<div>").text(title).html()}" style="width:100%;">`
    );
    $("#ajdwp-edit-popup").show();
    setTimeout(() => {
      $("#ajdwp-edit-popup-input").focus();
    }, 100);
  });

  // Show popup for price
  $(document).on("click", ".editable-price", function () {
    const cid = $(this).data("id");
    const wid = $(this).data("product-id");
    const price = $(this).data("original-price") || $(this).text().replace(AJDWP_tab2.currency, "").trim();

    $("#ajdwp-edit-popup-title").text("Edit Product Price");
    $("#ajdwp-edit-popup-type").val("price");
    $("#ajdwp-edit-popup-cid").val(cid);
    $("#ajdwp-edit-popup-wid").val(wid);
    $("#ajdwp-edit-popup-field").html(
      `<input type="number" step="0.01" id="ajdwp-edit-popup-input" class="regular-text" value="${price}" style="width:100%;">`
    );
    $("#ajdwp-edit-popup").show();
    setTimeout(() => {
      $("#ajdwp-edit-popup-input").focus();
    }, 100);
  });

  // Hide/cancel popup
  $("#ajdwp-edit-popup-cancel").on("click", function () {
    $("#ajdwp-edit-popup").hide();
  });

  // Save handler for popup
  $("#ajdwp-edit-popup-form").on("submit", function (e) {
    e.preventDefault();
    const type = $("#ajdwp-edit-popup-type").val();
    const cid = $("#ajdwp-edit-popup-cid").val();
    const wid = $("#ajdwp-edit-popup-wid").val();
    const value = $("#ajdwp-edit-popup-input").val();

    if (!cid || !type || !value) return;

    $("#ajdwp-edit-popup").hide();

    // AJAX: save field
    $.post(
      AJDWP_tab2.ajax_url,
      {
        action: "ajdwp_update_single_field",
        _ajax_nonce: AJDWP_tab2.nonce,
        id: cid,
        type: type,
        value: value,
      },
      function (res) {
        if (res.success) {
          if (type === "title") {
            $(`#product-title-${wid}`).text(value);
            $(`#product-title-${wid}`).data("original-title", value);
          } else if (type === "price") {
            $(`#product-price-${wid}`).text(AJDWP_tab2.currency + parseFloat(value).toFixed(2));
            $(`#product-price-${wid}`).data("original-price", parseFloat(value).toFixed(2));
          }
        } else {
          alert("❌ Update failed.");
        }
      }
    );
  });
});
