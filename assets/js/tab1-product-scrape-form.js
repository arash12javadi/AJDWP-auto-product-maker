//_____________________________________ tab1-product-scrape-form.js _____________________________________//

jQuery(function ($) {
  const $form = $("#ajdwp-scrape-form");
  const $bulk = $("#ajdwp-add-products-in-bulk");
  const $dropdown = $("#template-select-dropdown");

  function collectSkipFields() {
    return ["title", "short_description", "long_description", "image", "gallery", "price"].filter((f) => $("#skip_" + f).is(":checked"));
  }

  function collectSelectors() {
    return {
      title: $("#selector_title").val(),
      short_description: $("#selector_short_description").val(),
      long_description: $("#selector_long_description").val(),
      image: $("#selector_image").val(),
      gallery: $("#selector_gallery").val(),
      price: $("#selector_price").val(),
      price_calc: $("#selector_price_calc").val(),
    };
  }

  // ============================
  // Preview Scraped Product
  // ============================
  $form.on("click", "#preview-button", function (e) {
    e.preventDefault();

    const templateId = $dropdown.val();
    const productUrl = $form.find("input[name='product_url']").val();
    const scrapeMethod = $("#scrape_method").val();

    if (!productUrl) {
      $("#ajdwp-preview-container").html('<div class="notice notice-error">❌ Please enter a valid URL.</div>');
      return;
    }

    $("#ajdwp-preview-container").html("⏳ Scraping preview...");

    $.post(
      AJDWP_tab1.ajax_url,
      {
        action: "ajdwp_preview_scrape",
        _ajax_nonce: AJDWP_tab1.nonce,
        template_id: templateId,
        product_url: productUrl,
        scrape_method: scrapeMethod,
        skip_fields: collectSkipFields(),
        selectors: collectSelectors(),
      },
      function (res) {
        if (res.success) {
          $("#ajdwp-preview-container").html(res.data.html);
          $("table.form-table").hide();
          $bulk.hide();
          $("#preview-button").hide();
        } else {
          $("#ajdwp-preview-container").html(`<div class="notice notice-error">❌ ${res.data.message || "Failed to load preview."}</div>`);
        }
      },
      "json"
    ).fail(function () {
      $("#ajdwp-preview-container").html('<div class="notice notice-error">❌ AJAX request failed.</div>');
    });
  });

  // Cancel preview
  $("#ajdwp-preview-container").on("click", "#cancel-button", function (e) {
    e.preventDefault();
    location.reload();
  });

  // ============================
  // Confirm and Save Scraped Product
  // ============================
  $("#ajdwp-preview-container").on("click", "#submit-button", function (e) {
    e.preventDefault();

    const templateId = $dropdown.val();
    const productUrl = $form.find("input[name='product_url']").val();
    const scrapeMethod = $("#scrape_method").val();

    if (!productUrl) {
      alert("Missing product URL.");
      return;
    }

    $("#ajdwp-preview-container").html("⏳ Submitting product...");

    $.post(
      AJDWP_tab1.ajax_url,
      {
        action: "ajdwp_add_single_product_url",
        _ajax_nonce: AJDWP_tab1.nonce,
        template_id: templateId,
        product_url: productUrl,
        skip_fields: collectSkipFields(),
        scrape_method: scrapeMethod,
        selectors: collectSelectors(),
      },
      function (res) {
        if (res.success) {
          $("#ajdwp-preview-container").html(`<div class="notice notice-success">✅ ${res.data.message}: ${res.data.title}</div>`);
          $(document).trigger("ajdwp-panel-loaded");
        } else {
          $("#ajdwp-preview-container").html(`<div class="notice notice-error">❌ ${res.data.message || "Failed to save product."}</div>`);
        }
      }
    ).fail(function () {
      $("#ajdwp-preview-container").html('<div class="notice notice-error">❌ AJAX request failed.</div>');
    });
  });

  // ============================
  // Load Template & Bulk Add UI
  // ============================
  $bulk.hide();
  $dropdown.on("change", function () {
    renderBulkUI();
  });
  renderBulkUI();

  function renderBulkUI() {
    const tpl = $dropdown.val();
    if (!tpl) {
      $bulk.hide().empty();
      return;
    }

    $bulk.show();

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
        $("#selector_title").val(d.title_selector);
        $("#selector_short_description").val(d.short_description_selector);
        $("#selector_long_description").val(d.long_description_selector);
        $("#selector_image").val(d.main_image_selector);
        $("#selector_gallery").val(d.gallery_image_selectors);
        $("#selector_price").val(d.price_selector);
        $("#selector_price_calc").val(d.price_multiplier);
        $("#scrape_method").val(d.scrape_method);
        $("#ai_mode").val(d.ai_mode);
        $("#skip_title").prop("checked", !d.title_selector);
        $("#skip_short_description").prop("checked", !d.short_description_selector);
        $("#skip_long_description").prop("checked", !d.long_description_selector);
        $("#skip_image").prop("checked", !d.main_image_selector);
        $("#skip_gallery").prop("checked", !d.gallery_image_selectors);
        $("#skip_price").prop("checked", !d.price_selector);
      },
      "json"
    ).fail(function () {
      $bulk.html('<div class="notice notice-error">AJAX error loading template.</div>');
    });
  }

  // ============================
  // Sequential Bulk Add Handler
  // ============================
  $bulk
    .on("click", "#add-bulk-url", function () {
      $("#bulk-add-urls").append(`
        <div class="bulk-url-row" style="margin-top:8px;">
          <input type="url" name="bulk_product_urls[]" placeholder="Enter product URL" style="width:100%;max-width:800px;" required />
          <button type="button" class="remove-bulk-url button-link-delete">Remove</button>
        </div>`);
    })
    .on("click", ".remove-bulk-url", function () {
      $(this).closest(".bulk-url-row").remove();
    })
    .on("click", "#add-bulk-submit", function () {
      const tpl = $dropdown.val();
      const $notice = $("#bulk-add-notice");
      const urls = $('input[name="bulk_product_urls[]"]')
        .map((_, el) => el.value.trim())
        .get()
        .filter((u) => u);

      if (!tpl || urls.length === 0) {
        $notice.html('<div class="notice notice-error">Please select a template and enter at least one URL.</div>');
        return;
      }

      let index = 0;
      let inserted = 0,
        updated = 0,
        errors = 0;

      $notice.html(`<ul id="bulk-add-results" style="margin: 1em 0; list-style: square inside;"></ul>`);
      const $results = $("#bulk-add-results");

      function processNext() {
        if (index >= urls.length) {
          $results.append(`<li><strong>✅ All done:</strong> ${inserted} inserted, ${updated} updated, ${errors} errors.</li>`);
          $(document).trigger("ajdwp-panel-loaded");
          return;
        }

        const url = urls[index++];
        const statusItem = $(`<li>🔄 Processing: ${url}</li>`);
        $results.append(statusItem);

        $.post(
          AJDWP_tab1.ajax_url,
          {
            action: "ajdwp_add_single_product_url",
            _ajax_nonce: AJDWP_tab1.nonce,
            template_id: tpl,
            product_url: url,
            skip_fields: collectSkipFields(),
          },
          function (res) {
            if (res.success) {
              const msg = res.data?.message || "✅ Added";
              if (msg.includes("Inserted")) inserted++;
              else updated++;
              statusItem.html(`<span style="color:green;">${msg}</span> – ${res.data?.title || url}`);
            } else {
              errors++;
              statusItem.html(`<span style="color:red;">❌ ${res.data?.message || "Failed"}</span> – ${url}`);
            }
            setTimeout(processNext, 250);
          }
        ).fail(function () {
          errors++;
          statusItem.html(`<span style="color:red;">❌ AJAX error</span> – ${url}`);
          setTimeout(processNext, 250);
        });
      }

      processNext();
    });
});

//==========================
//  AI Refine single product (title, short_description, long_description)
//==========================
jQuery(function ($) {
  function refine(field, selector) {
    const original = $(selector).text().trim();

    $(selector).html("⏳ Refining...");

    $.post(
      AJDWP_tab1.ajax_url,
      {
        action: "ajdwp_ai_refine_single",
        _ajax_nonce: AJDWP_tab1.nonce,
        text: original,
        field: field,
      },
      function (res) {
        if (res.success) {
          $(selector).text(res.data.refined);
        } else {
          alert("❌ " + (res.data.message || "AI refinement failed."));
          $(selector).text(original);
        }
      }
    ).fail(function () {
      alert("❌ AJAX request failed.");
      $(selector).text(original);
    });
  }

  $(document).on("click", "#ai-refine-title", function () {
    refine("title", "#preview-title");
  });

  $(document).on("click", "#ai-refine-short-description", function () {
    refine("short_description", "#preview-short-description");
  });

  $(document).on("click", "#ai-refine-long-description", function () {
    refine("long_description", "#preview-long-description");
  });
});
