jQuery(document).ready(function ($) {
  // Edit booking functionality
  $(".cr-edit-booking").on("click", function () {
    var bookingId = $(this).data("id");

    $.ajax({
      url: cr_admin.ajax_url,
      type: "POST",
      data: {
        action: "cr_get_booking_details",
        booking_id: bookingId,
        nonce: cr_admin.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Populate and show edit modal
          showEditModal(response.data);
        }
      },
    });
  });

  function showEditModal(booking) {
    // Create modal HTML
    var modal = `
            <div id="cr-edit-modal" style="display:none;">
                <h3>Edit Booking #${booking.booking_number}</h3>
                <form id="cr-edit-form">
                    <p>
                        <label>Status</label>
                        <select name="booking_status">
                            <option value="pending" ${booking.booking_status === "pending" ? "selected" : ""}>Pending</option>
                            <option value="completed" ${booking.booking_status === "completed" ? "selected" : ""}>Completed</option>
                            <option value="cancelled" ${booking.booking_status === "cancelled" ? "selected" : ""}>Cancelled</option>
                        </select>
                    </p>
                    <p>
                        <label>Payment Status</label>
                        <select name="payment_status">
                            <option value="pending" ${booking.payment_status === "pending" ? "selected" : ""}>Pending</option>
                            <option value="completed" ${booking.payment_status === "completed" ? "selected" : ""}>Completed</option>
                            <option value="failed" ${booking.payment_status === "failed" ? "selected" : ""}>Failed</option>
                        </select>
                    </p>
                    <p>
                        <label>Total Amount</label>
                        <input type="number" name="total_amount" value="${booking.total_amount}" step="0.01">
                    </p>
                </form>
            </div>
        `;

    // Show modal (you can use a proper modal library or simple prompt for now)
    // For simplicity, we'll just use a prompt for now
    var newStatus = prompt(
      "Enter new status (pending/completed/cancelled):",
      booking.booking_status,
    );
    if (newStatus) {
      // Save changes
      $.ajax({
        url: cr_admin.ajax_url,
        type: "POST",
        data: {
          action: "cr_update_booking",
          booking_id: booking.id,
          booking_status: newStatus,
          payment_status: booking.payment_status,
          total_amount: booking.total_amount,
          nonce: cr_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            alert("Booking updated!");
            location.reload();
          }
        },
      });
    }
  }
});
