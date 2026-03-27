jQuery(document).ready(function ($) {
  // Registration form handling
  $("#cr-registration-form").on("submit", function (e) {
    // Validate form if needed
  });

  // Check availability
  $(".cr-check-availability").on("click", function () {
    var date = $(this).data("date");
    var congressId = $(this).data("congress-id");

    $.ajax({
      url: cr_public.ajax_url,
      type: "POST",
      data: {
        action: "cr_check_availability",
        date: date,
        congress_id: congressId,
        nonce: cr_public.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
        }
      },
    });
  });
});
