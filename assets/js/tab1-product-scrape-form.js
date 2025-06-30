//_____________________________________ tab1-product-scrape-form.js _____________________________________//

// ==================================
// Load entered template values and Bulk Add Area
// ==================================
jQuery(function ($) {
  const $dropdown = $("#template-select-dropdown");
  const $bulk = $("#ajdwp-add-products-in-bulk");

  // 1) Hide bulk area by default
  $bulk.hide();

  // 2) Only render when user picks a template
  $dropdown.on("change", function () {
    renderBulkUI();
  });

  // 3) No initial renderBulkUI() call here!

  function renderBulkUI() {
    const tpl = $dropdown.val();

    if (!tpl) {
      // nothing selected → hide & clear
      $bulk.hide().empty();
      return;
    }

    // show the container
    $bulk.show();

    // fetch template data & populate selectors
    $.post(
      AJDWP_tab1.ajax_url,
      {
        action: "ajdwp_get_template_data",
        template_id: tpl,
        _ajax_nonce: AJDWP_tab1.nonce,
      },
      function (res) {
        if (!res.success) {
          return $bulk.html('<div class="notice notice-error">Failed to load template settings.</div>');
        }
        const d = res.data;
        // populate your individual selector inputs...
        $("#selector_title").val(d.title_selector);
        $("#selector_short_description").val(d.short_description_selector);
        $("#selector_long_description").val(d.long_description_selector);
        $("#selector_image").val(d.main_image_selector);
        $("#selector_gallery").val(d.gallery_image_selectors);
        $("#selector_price").val(d.price_selector);
        $("#selector_price_calc").val(d.price_multiplier);
        $("#scrape_method").val(d.scrape_method);
        $("#skip_title").prop("checked", !d.title_selector);
        $("#skip_short_description").prop("checked", !d.short_description_selector);
        $("#skip_long_description").prop("checked", !d.long_description_selector);
        $("#skip_image").prop("checked", !d.main_image_selector);
        $("#skip_gallery").prop("checked", !d.gallery_image_selectors);
        $("#skip_price").prop("checked", !d.price_selector);

        // inject bulk-add HTML
        $bulk.html(`
        <h2>Bulk Add Products</h2>
        <div id="bulk-add-urls">
          <div class="bulk-url-row">
            <input type="url"
                   name="bulk_product_urls[]"
                   placeholder="Enter product URL"
                   style="width:80%;"
                   required />
          </div>
        </div>
        <p>
          <button type="button" id="add-bulk-url" class="button">+ Add another URL</button>
          <button type="button" id="add-bulk-submit" class="button button-secondary">
            ✅ Bulk Add to Template
          </button>
        </p>
        <div id="bulk-add-notice"></div>
      `);
      },
      "json"
    ).fail(function () {
      $bulk.html('<div class="notice notice-error">AJAX error loading template.</div>');
    });
  }

  // 4) Delegate add/remove and submit as before
  $bulk
    .on("click", "#add-bulk-url", function () {
      $("#bulk-add-urls").append(`
        <div class="bulk-url-row" style="margin-top:8px;">
          <input type="url" name="bulk_product_urls[]"
                 placeholder="Enter product URL" style="width:80%;" required />
          <button type="button" class="remove-bulk-url button-link-delete">Remove</button>
        </div>`);
    })
    .on("click", ".remove-bulk-url", function () {
      $(this).closest(".bulk-url-row").remove();
    })
    .on("click", "#add-bulk-submit", function () {
      const tpl = $dropdown.val();
      const urls = $('input[name="bulk_product_urls[]"]')
        .map((_, e) => e.value.trim())
        .get()
        .filter((u) => u);

      if (!tpl || !urls.length) {
        $("#bulk-add-notice").html(`<div class="notice notice-error">Please select a template and enter at least one URL.</div>`);
        return;
      }
      $.post(
        AJDWP_tab1.ajax_url,
        {
          action: "ajdwp_bulk_add_product_urls",
          _ajax_nonce: AJDWP_tab1.nonce,
          template_id: tpl,
          urls: urls,
        },
        function (res) {
          if (!res.success) {
            return $("#bulk-add-notice").html(`<div class="notice notice-error">❌ ${res.data.message || "Bulk add failed."}</div>`);
          }

          $("#bulk-add-notice").html(`
            <div class="notice notice-success">
              ✅ Inserted:   ${res.data.inserted} URL(s)<br>
                Updated:    ${res.data.updated} URL(s)<br>
                With errors:${res.data.errors} row(s)
            </div>
          `);

          $(document).trigger("ajdwp-panel-loaded");
        },
        "json"
      );
    });
});
