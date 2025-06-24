jQuery(function ($) {
  // ==============================
  // Template CRUD
  // ==============================

  // Add template
  $(document).on("click", "#add-template-btn", function () {
    const name = $("#new-template-name").val().trim();
    if (!name) return alert("Template name is required.");

    $.post(
      AJDWP.ajax_url,
      {
        action: "ajdwp_add_template",
        name: name,
        _ajax_nonce: AJDWP.nonce,
      },
      function (res) {
        if (res.success) {
          const html = `
          <li data-id="${res.data.id}" style="margin-bottom: 15px;">
            <div>
              <strong>${res.data.name}</strong>
              <button class="button rename-template" data-id="${res.data.id}">✏️ Rename</button>
              <button class="button delete-template" data-id="${res.data.id}">🗑️ Delete</button>
            </div>
            <div class="template-urls" style="margin-left: 20px; margin-top: 10px;">
              <input type="url" class="new-template-url" placeholder="Add URL..." data-template-id="${res.data.id}">
              <button class="button add-template-url" data-template-id="${res.data.id}">➕ Add URL</button>
              <ul class="url-list" data-template-id="${res.data.id}" style="margin-top: 10px;">
                <li><em>No URLs yet.</em></li>
              </ul>
            </div>
          </li>
        `;
          $("#template-list").prepend(html);
          $("#new-template-name").val("");
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
          $(`li[data-id="${id}"]`).remove();
        }
      }
    );
  });

  // Rename template
  $(document).on("click", ".rename-template", function () {
    const id = $(this).data("id");
    const currentName = $(`li[data-id="${id}"] strong`).text();
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
          $(`li[data-id="${id}"] strong`).text(newName);
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
          urlList.find("em").remove(); // remove placeholder if exists
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
});

// ==================================

jQuery(document).ready(function ($) {
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
        console.error("AJAX Error:", xhr);
        alert("AJAX failed: " + xhr.status);
      },
    });
  });
});
