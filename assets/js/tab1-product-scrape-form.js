jQuery(function ($) {
  // ==================================
  // Load entered template values
  // ==================================

  // ✅ FIXED: Load default template values
  jQuery(document).ready(function ($) {
    $("#template-select-dropdown").on("change", function () {
      const templateId = $(this).val();
      if (!templateId) return;

      $.ajax({
        url: AJDWP_tab1.ajax_url,
        type: "POST",
        dataType: "json",
        data: {
          action: "ajdwp_get_template_data",
          template_id: templateId,
          _ajax_nonce: AJDWP_tab1.nonce,
        },
        success: function (response) {
          if (response.success) {
            const d = response.data;

            // Set values
            $("#selector_title").val(d.title_selector);
            $("#selector_short_description").val(d.short_description_selector);
            $("#selector_long_description").val(d.long_description_selector);
            $("#selector_image").val(d.main_image_selector);
            $("#selector_gallery").val(d.gallery_image_selectors);
            $("#selector_price").val(d.price_selector);
            $("#selector_price_calc").val(d.price_multiplier);
            $("#scrape_method").val(d.scrape_method);

            // Check "Ignore if not found" if field is empty
            $("#skip_title").prop("checked", !d.title_selector);
            $("#skip_short_description").prop("checked", !d.short_description_selector);
            $("#skip_long_description").prop("checked", !d.long_description_selector);
            $("#skip_image").prop("checked", !d.main_image_selector);
            $("#skip_gallery").prop("checked", !d.gallery_image_selectors);
            $("#skip_price").prop("checked", !d.price_selector);

            console.log("✅ Loaded template:", d);
          } else {
            alert("❌ Failed to fetch template data.");
          }
        },
        error: function () {
          alert("❌ AJAX request failed.");
        },
      });
    });
  });
});
