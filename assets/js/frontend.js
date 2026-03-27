(function ($) {
  "use strict";

  let currentStep = 1;
  let congressId = 0;
  let formData = {};

  $(document).ready(function () {
    // Load from localStorage first
    const restored = loadFromLocalStorage();

    // Get congress ID
    congressId = $("#congress_id").val();

    // Get current step from URL on page load
    var urlStep = getCurrentStepFromUrl();

    // Priority: URL parameter > localStorage > default 1
    if (urlStep) {
      currentStep = urlStep;
      console.log("Using URL step:", currentStep);
    } else if (restored) {
      // currentStep already set by loadFromLocalStorage
      console.log("Using restored step:", currentStep);
    } else {
      currentStep = 1;
      console.log("Using default step:", currentStep);
    }

    // Initialize step classes
    updateStepClasses(currentStep);

    // Load first step
    loadStep(currentStep);

    // frontend.js এ step navigation handler আপডেট করুন
    function handleStepNavigation(targetStep) {
      const currentStepNum = currentStep;

      // Going backward
      if (targetStep < currentStepNum) {
        saveCurrentStep(function () {
          loadStep(targetStep);
        });
        return;
      }

      // Going forward
      if (targetStep > currentStepNum) {
        let isValid = true;

        if (currentStepNum === 7) {
          isValid = validateStep7();
        } else {
          isValid = validateStep(currentStepNum);
        }

        if (!isValid) {
          return;
        }

        saveCurrentStep(function () {
          loadStep(targetStep);
        });
      }
    }

    // Step navigation
    // Update click handlers
    $(document).on("click", ".crs-step-marker, .crs-step-item", function () {
      const targetStep = $(this).data("step");
      handleStepNavigation(targetStep);
    });

    $("#crs-next-step").on("click", function (e) {
      e.preventDefault();
      handleStepNavigation(currentStep + 1);
    });
    // Previous button
    $("#crs-prev-step").on("click", function () {
      navigateToStep(currentStep - 1);
    });

    // Diet checkboxes
    $(".crs-diet-btn").on("click", function (e) {
      e.preventDefault();
      var checkbox = $(this).find('input[type="checkbox"]');
      checkbox.prop("checked", !checkbox.prop("checked"));

      if (checkbox.prop("checked")) {
        $(this).addClass("active");
      } else {
        $(this).removeClass("active");
      }

      if (checkbox.val() === "other") {
        if (checkbox.prop("checked")) {
          $("#diet-other-field").slideDown();
        } else {
          var otherChecked =
            $('input[name="diet[]"][value="other"]:checked').length > 0;
          if (!otherChecked) {
            $("#diet-other-field").slideUp();
            $('input[name="diet_other"]').val("");
          }
        }
      }
    });

    // Option card selection
    $(document).on("click", ".crs-option-card", function () {
      $(".crs-option-card").removeClass("selected");
      $(this).addClass("selected");
      $("#registration_type").val($(this).data("type"));

      if ($(this).data("type") === "third_person") {
        $(".crs-third-person-fields").slideDown();
      } else {
        $(".crs-third-person-fields").slideUp();
      }
    });

    // Registration type selection
    $(document).on("change", 'input[name="registration_type_id"]', function () {
      const proofRequired = $(this)
        .closest(".crs-registration-option")
        .data("proof");
      if (proofRequired == 1) {
        $(".crs-proof-upload").slideDown();
      } else {
        $(".crs-proof-upload").slideUp();
      }
    });

    // Hotel selection
    $(document).on("change", 'input[name="hotel_id"]', function () {
      if ($(this).val() !== "0") {
        $(".crs-date-selection").slideDown();
      } else {
        $(".crs-date-selection").slideUp();
      }
    });

    // Meal selection
    $(document).on(
      "change",
      '.crs-meal-option input[type="checkbox"]',
      function () {
        updateMealsTotal();
      },
    );

    // Invoice request
    $(document).on("change", "#request_invoice", function () {
      if ($(this).is(":checked")) {
        $(".crs-invoice-fields").slideDown();
      } else {
        $(".crs-invoice-fields").slideUp();
      }
    });

    // Image release checkbox change
    $(document).on("change", "#image_release", function () {
      if ($(this).is(":checked")) {
        $("#image-release-error").slideUp();
      }
    });

    // ========== PAYMENT HANDLERS ==========

    // Pay button click handler
    $(document).on("click", ".crs-pay-button", function (e) {
      e.preventDefault();

      if (!validateTerms()) return;

      // Show loading state
      var $btn = $(this);
      $btn.prop("disabled", true).text("Processing...");

      // Save all data first
      saveAllData(function () {
        // Then create Stripe Payment Intent
        createStripePaymentIntent($btn);
      });
    });

    // Save without paying button
    $(document).on("click", ".crs-save-button", function () {
      var $btn = $(this);
      $btn.prop("disabled", true).text("Saving...");

      var formDataObj = new FormData();
      formDataObj.append("action", "crs_save_without_payment");
      formDataObj.append("nonce", crs_ajax.nonce);
      formDataObj.append("data", JSON.stringify(formData));

      var proofFile = $("#proof_file")[0]?.files[0];
      if (proofFile) {
        formDataObj.append("proof_file", proofFile);
      }

      $.ajax({
        url: crs_ajax.ajax_url,
        type: "POST",
        data: formDataObj,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            alert("Registration saved successfully!");
            window.location.href = response.data.redirect;
          } else {
            alert("Error: " + response.data);
            $btn.prop("disabled", false).text("Save without paying");
          }
        },
        error: function () {
          alert("Failed to save registration");
          $btn.prop("disabled", false).text("Save without paying");
        },
      });
    });

    // Cancel payment button (will be added dynamically)
    $(document).on("click", "#crs-cancel-payment", function () {
      $("#crs-payment-container").slideUp(300, function () {
        $(this).empty();
        $(".crs-summary-grid").fadeIn(300);
      });

      // Show the original Pay button again
      $(".crs-pay-button").fadeIn(400);

      // Optional: Scroll back to summary section
      $("html, body").animate(
        {
          scrollTop: $(".crs-summary-grid").offset().top - 100,
        },
        400,
      );
    });

    // Handle browser back/forward buttons
    window.addEventListener("popstate", function () {
      var step = getCurrentStepFromUrl();
      currentStep = step;
      updateStepClasses(step);
      if (typeof loadStep === "function") {
        loadStep(step);
      }
    });
  });

  // ========== STRIPE PAYMENT FUNCTIONS ==========

  // frontend.js এর createStripePaymentIntent function আপডেট করুন
  function createStripePaymentIntent($btn) {
    // ========== CRITICAL: Make sure we're using the latest formData ==========
    // First, collect current UI data to ensure we have the latest values
    const currentCouponCode = $("#applied_coupon_code").val();
    const currentFinalAmount = $("#final_amount").val();
    const currentDiscountAmount = $("#discount_amount").val();
    const currentSubtotal = $("#subtotal_amount").val();

    console.log("Creating payment intent with latest data:");
    console.log("Current coupon code from hidden field:", currentCouponCode);
    console.log("Current final amount:", currentFinalAmount);
    console.log("Current discount amount:", currentDiscountAmount);
    console.log("Current subtotal:", currentSubtotal);

    // Update formData with latest values from UI
    if (currentCouponCode && currentCouponCode !== "") {
      formData.applied_coupon_code = currentCouponCode;
      formData.applied_coupon_id = $("#applied_coupon_id").val();
      formData.discount_amount = parseFloat(currentDiscountAmount);
      formData.final_amount = parseFloat(currentFinalAmount);
      formData.subtotal_amount = parseFloat(currentSubtotal);

      // Also get discount type and value from the displayed badge
      const typeBadgeText = $("#coupon-type-badge").text();
      if (typeBadgeText) {
        if (typeBadgeText.includes("%")) {
          formData.discount_type = "percentage";
          formData.discount_value = parseFloat(
            typeBadgeText.replace("% OFF", ""),
          );
        } else if (typeBadgeText.includes("€")) {
          formData.discount_type = "fixed";
          formData.discount_value = parseFloat(
            typeBadgeText.replace("€", "").replace(" OFF", ""),
          );
        }
      }

      console.log("Updated formData with coupon:", {
        applied_coupon_code: formData.applied_coupon_code,
        discount_amount: formData.discount_amount,
        final_amount: formData.final_amount,
      });
    } else {
      // No coupon applied - clear any existing coupon data
      delete formData.applied_coupon_code;
      delete formData.applied_coupon_id;
      delete formData.discount_amount;
      delete formData.discount_type;
      delete formData.discount_value;
      formData.final_amount = parseFloat(currentSubtotal) || originalSubtotal;
      formData.subtotal_amount =
        parseFloat(currentSubtotal) || originalSubtotal;

      console.log("No coupon applied, formData cleared");
    }

    var formDataObj = new FormData();
    formDataObj.append("action", "crs_create_stripe_payment_intent");
    formDataObj.append("nonce", crs_ajax.nonce);
    formDataObj.append("data", JSON.stringify(formData));

    // Check sessionStorage for file data
    var proofFileData = sessionStorage.getItem("crs_proof_file_data");
    var proofFileName = sessionStorage.getItem("crs_proof_file_name");

    if (proofFileData && proofFileName) {
      formDataObj.append("proof_file_data", proofFileData);
      formDataObj.append("proof_file_name", proofFileName);
    }

    // Debug log - what's being sent to server
    console.log("Sending data for payment intent:", {
      registration_type: formData.registration_type,
      applied_coupon_code: formData.applied_coupon_code,
      discount_amount: formData.discount_amount,
      final_amount: formData.final_amount,
      subtotal_amount: formData.subtotal_amount,
      discount_type: formData.discount_type,
      discount_value: formData.discount_value,
    });

    $.ajax({
      url: crs_ajax.ajax_url,
      type: "POST",
      data: formDataObj,
      processData: false,
      contentType: false,
      timeout: 30000,
      success: function (response) {
        if (response.success) {
          console.log(
            "Payment intent created successfully with amount:",
            response.data.amount,
          );
          clearLocalStorage();
          showStripePaymentForm(
            response.data.temp_booking_id,
            response.data.intentId,
            response.data.clientSecret,
            response.data.publishableKey,
            $btn,
          );
        } else {
          console.error("Payment intent error:", response.data);
          alert("Error: " + (response.data || "Unknown error"));
          $btn.prop("disabled", false).text("Pay");
        }
      },
      error: function (xhr, status, error) {
        console.error("Stripe error:", error);
        console.error("Response:", xhr.responseText);
        alert("Failed to initialize payment. Please try again.");
        $btn.prop("disabled", false).text("Pay");
      },
    });
  }

  // frontend.js এর showStripePaymentForm function আপডেট করুন
  function showStripePaymentForm(
    tempBookingId,
    intentId,
    clientSecret,
    publishableKey,
    $btn,
  ) {
    // Hide the Pay button
    $btn.hide();

    console.log("Loading payment form for temp ID:", tempBookingId);

    // Load payment form via AJAX - include temp_booking_id
    $.ajax({
      url: crs_ajax.ajax_url,
      type: "POST",
      data: {
        action: "crs_load_stripe_payment_form",
        temp_booking_id: tempBookingId,
        intent_id: intentId,
        client_secret: clientSecret,
        publishable_key: publishableKey,
        nonce: crs_ajax.nonce,
      },
      timeout: 30000,
      success: function (response) {
        console.log("Payment form response:", response);
        if (response.success) {
          // Clear and show payment container
          $("#crs-payment-container")
            .empty()
            .html(response.data.html)
            .slideDown(500);

          // Scroll to payment section
          $("html, body").animate(
            {
              scrollTop: $("#crs-payment-container").offset().top - 50,
            },
            500,
          );

          $btn.prop("disabled", false);
        } else {
          console.error("Failed to load payment form:", response.data);
          alert(
            "Failed to load payment form: " +
              (response.data || "Unknown error"),
          );
          $btn.fadeIn(300);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error loading payment form:", status, error);
        alert("Failed to load payment form. Please try again.");
        $btn.fadeIn(300);
      },
    });
  }

  // Validate terms checkbox
  function validateTerms() {
    if (!$("#accept_terms").is(":checked")) {
      alert("Please accept the terms and conditions");
      return false;
    }
    return true;
  }

  // ========== STEP 7 VALIDATION ==========
  function validateStep7() {
    var isChecked = $("#image_release").is(":checked");

    if (!isChecked) {
      $("#image-release-error").slideDown();
      $("html, body").animate(
        {
          scrollTop: $("#image_release").offset().top - 100,
        },
        500,
      );
      return false;
    }

    $("#image-release-error").slideUp();
    return true;
  }

  // Function to update step classes based on current step
  function updateStepClasses(currentStep) {
    // Update step items (crs-step-item)
    $(".crs-step-item").each(function (index) {
      var item = $(this);
      var stepNum = index + 1; // 1-based index

      // Remove all status classes
      item.removeClass("completed-step active-step pending-step");

      // Add appropriate class based on step comparison
      if (stepNum < currentStep) {
        item.addClass("completed-step");
      } else if (stepNum == currentStep) {
        item.addClass("active-step");
      } else {
        item.addClass("pending-step");
      }
    });

    // Update step markers
    $(".crs-step-marker").each(function (index) {
      var marker = $(this);
      var stepNum = index + 1;

      marker.removeClass("crs-step-completed crs-step-active crs-step-pending");

      if (stepNum < currentStep) {
        marker.addClass("crs-step-completed");
      } else if (stepNum == currentStep) {
        marker.addClass("crs-step-active");
      } else {
        marker.addClass("crs-step-pending");
      }
    });

    // Update step labels
    $(".crs-step-label").each(function (index) {
      var label = $(this);
      var stepNum = index + 1;

      label.removeClass(
        "crs-step-label-completed crs-step-label-active crs-step-label-pending",
      );

      if (stepNum < currentStep) {
        label.addClass("crs-step-label-completed");
      } else if (stepNum == currentStep) {
        label.addClass("crs-step-label-active");
      } else {
        label.addClass("crs-step-label-pending");
      }
    });

    // Update connectors
    $(".crs-step-connector").each(function (index) {
      var connector = $(this);
      var stepNum = index + 1;

      connector.removeClass(
        "crs-connector-completed crs-connector-active crs-connector-pending",
      );

      if (stepNum < currentStep) {
        connector.addClass("crs-connector-completed");
      } else if (stepNum == currentStep) {
        connector.addClass("crs-connector-active");
      } else {
        connector.addClass("crs-connector-pending");
      }
    });

    // Update mobile progress steps
    $(".crs-progress-step").each(function (index) {
      var progress = $(this);
      var stepNum = index + 1;

      progress.removeClass(
        "crs-progress-completed crs-progress-active crs-progress-pending",
      );

      if (stepNum < currentStep) {
        progress.addClass("crs-progress-completed");
      } else if (stepNum == currentStep) {
        progress.addClass("crs-progress-active");
      } else {
        progress.addClass("crs-progress-pending");
      }
    });

    // Update mobile header
    $(".crs-step-counter").text("Step " + currentStep + " of 8");

    var stepNames = [
      "Start",
      "Data",
      "Type",
      "Hotel",
      "Meals",
      "Workshops",
      "Others",
      "Summary",
    ];
    $(".crs-current-step").text(stepNames[currentStep - 1]);
  }

  function getCurrentStepFromUrl() {
    var urlParams = new URLSearchParams(window.location.search);
    var step = urlParams.get("step");
    return step ? parseInt(step) : 1;
  }

  function loadStep(step) {
    // Show skeleton loader
    $("#crs-step-container").html(`
        <div class="crs-skeleton-loader">
            <div class="crs-skeleton-header">
                <div class="crs-skeleton-title"></div>
                <div class="crs-skeleton-subtitle"></div>
            </div>
            <div class="crs-skeleton-grid">
                <div class="crs-skeleton-item"></div>
                <div class="crs-skeleton-item"></div>
                <div class="crs-skeleton-item"></div>
                <div class="crs-skeleton-item"></div>
                <div class="crs-skeleton-item large"></div>
            </div>
        </div>
    `);

    // Show loading state on step markers
    $(".crs-step-marker").addClass("crs-step-loading");

    console.log("Loading step:", step);
    console.log("Congress ID:", congressId);
    console.log("FormData:", formData);

    $.ajax({
      url: crs_ajax.ajax_url,
      type: "POST",
      data: {
        action: "crs_load_step",
        step: step,
        congress_id: congressId,
        data: JSON.stringify(formData),
        nonce: crs_ajax.nonce,
      },
      timeout: 30000,
      success: function (response) {
        console.log("✅ Step load response received");
        console.log("Response success:", response.success);
        console.log("Response data:", response.data);

        $(".crs-step-marker").removeClass("crs-step-loading");

        if (response.success) {
          console.log("Response success true");

          if (response.data) {
            console.log("Response data exists");

            if (response.data.html) {
              console.log(
                "✅ HTML found in response, length:",
                response.data.html.length,
              );
              console.log(
                "HTML preview:",
                response.data.html.substring(0, 200) + "...",
              );

              // Inject HTML
              $("#crs-step-container").html(response.data.html);
              console.log("HTML injected to container");

              currentStep = step;
              console.log("Current step updated to:", currentStep);

              setTimeout(function () {
                updateStepClasses(step);
                console.log("Step classes updated");
              }, 100);

              restoreFormData();
              console.log("Form data restored");

              updateUrlStep(step);
              updateNavigationButtons();
              $("#image-release-error").hide();

              // Save to localStorage after successful load
              saveToLocalStorage();
              console.log("Data saved to localStorage");

              // Scroll smoothly
              $("html, body").animate(
                {
                  scrollTop: $(".crs-step-content").offset().top - 50,
                },
                400,
              );
              console.log("Scrolled to step content");
            } else {
              console.error("❌ response.data.html is empty or missing");
              console.log("response.data keys:", Object.keys(response.data));
              showErrorState("Server returned empty HTML");
            }
          } else {
            console.error("❌ response.data is null or undefined");
            showErrorState("Invalid server response");
          }
        } else {
          console.error("❌ Response success is false");
          console.log("Error message:", response.data || "Unknown error");
          showErrorState(response.data || "Failed to load step");
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ AJAX Error:", {
          status: status,
          error: error,
          response: xhr.responseText,
        });
        $(".crs-step-marker").removeClass("crs-step-loading");

        let errorMessage = "Failed to load step. Please try again.";
        if (status === "timeout") {
          errorMessage = "Request timed out. Please check your connection.";
        } else if (status === "parsererror") {
          errorMessage = "Server response error. Please refresh.";
        }

        showErrorState(errorMessage);
      },
    });
  }
  function showErrorState(message) {
    $("#crs-step-container").html(`
    <div class="crs-error-container">
      <div class="crs-error-icon">⚠️</div>
      <h3>Connection Error</h3>
      <p>${message}</p>
      <div class="crs-error-actions">
        <button type="button" class="crs-btn crs-btn-primary" onclick="location.reload()">
          <span class="btn-icon">🔄</span>
          Refresh Page
        </button>
        <button type="button" class="crs-btn crs-btn-secondary" onclick="retryLastStep()">
          <span class="btn-icon">↻</span>
          Try Again
        </button>
      </div>
    </div>
  `);
  }

  function retryLastStep() {
    loadStep(currentStep);
  }

  // Local Storage Functions
  function saveToLocalStorage() {
    try {
      const dataToSave = {
        formData: formData,
        currentStep: currentStep,
        congressId: congressId,
        timestamp: new Date().getTime(),
      };
      localStorage.setItem("crs_registration_data", JSON.stringify(dataToSave));
      console.log("Data saved to localStorage at step", currentStep);
    } catch (e) {
      console.log("Could not save to localStorage:", e);
    }
  }

  function loadFromLocalStorage() {
    try {
      const saved = localStorage.getItem("crs_registration_data");
      if (saved) {
        const parsed = JSON.parse(saved);
        const now = new Date().getTime();
        const savedTime = parsed.timestamp || 0;
        const hoursDiff = (now - savedTime) / (1000 * 60 * 60);

        if (
          hoursDiff < 24 &&
          parsed.formData &&
          Object.keys(parsed.formData).length > 0
        ) {
          formData = parsed.formData;
          if (parsed.currentStep) {
            currentStep = parsed.currentStep; // ← এই লাইনটা আগে ছিল? check করুন
          }
          if (parsed.congressId) {
            congressId = parsed.congressId;
          }
          console.log("Restored from localStorage - Step:", currentStep);
          return true;
        } else {
          localStorage.removeItem("crs_registration_data");
        }
      }
    } catch (e) {
      console.log("Could not load from localStorage:", e);
    }
    return false;
  }

  function clearLocalStorage() {
    try {
      localStorage.removeItem("crs_registration_data");
      console.log("LocalStorage cleared");
    } catch (e) {
      console.log("Could not clear localStorage");
    }
  }
  function navigateToStep(step) {
    if (step < 1 || step > 8) return;

    saveCurrentStep(function () {
      loadStep(step);
    });
  }

  function saveCurrentStep(callback) {
    const stepData = collectStepData(currentStep);

    if (stepData && Object.keys(stepData).length > 0) {
      formData = $.extend(formData, stepData);

      // Save to localStorage after each step
      saveToLocalStorage();
    }

    if (callback && typeof callback === "function") {
      callback();
    }
  }

  function validateStep(step) {
    let isValid = true;
    let firstInvalidField = null;

    // প্রথমে সব field থেকে error class সরান
    $(".crs-error-message").remove();
    $(".crs-input-error").removeClass("crs-input-error");

    switch (step) {
      case 1:
        if (!$("#registration_type").val()) {
          isValid = false;
          showFieldError(
            $("#registration_type").closest(".crs-form-group"),
            "Please select registration type",
          );
        } else if ($("#registration_type").val() === "third_person") {
          const thirdPersonName = $('input[name="third_person_name"]');
          const thirdPersonEmail = $('input[name="third_person_email"]');
          const thirdPersonPass = $('input[name="third_person_password"]');

          if (!thirdPersonName.val()) {
            isValid = false;
            showFieldError(thirdPersonName, "Please enter third person name");
          }
          if (!thirdPersonEmail.val()) {
            isValid = false;
            showFieldError(thirdPersonEmail, "Please enter third person email");
          }
          if (thirdPersonPass.val().length < 8) {
            isValid = false;
            showFieldError(
              thirdPersonPass,
              "Password must be at least 8 characters",
            );
          }

          // NEW: Create third person user immediately on step 1 validation
          if (isValid) {
            console.log("Creating third person user now...");
            // Save current step data first
            saveCurrentStep(function () {
              // Create user via AJAX
              createThirdPersonUser();
            });
          }
        }
        break;

      case 2:
        const required = [
          { selector: 'input[name="first_name"]', label: "First Name" },
          { selector: 'input[name="last_name"]', label: "Last Name" },
          { selector: 'input[name="id_number"]', label: "ID/NIE/Passport" },
          { selector: 'input[name="phone"]', label: "Phone" },
          { selector: 'input[name="address"]', label: "Address" },
          { selector: 'input[name="location"]', label: "Location" },
          { selector: 'input[name="postal_code"]', label: "Postal Code" },
          { selector: 'input[name="country"]', label: "Country" },
          { selector: 'input[name="province"]', label: "Province" },
          { selector: 'input[name="email"]', label: "Email" },
        ];

        for (let field of required) {
          const $field = $(field.selector);
          if (!$field.val()) {
            isValid = false;
            showFieldError($field, `${field.label} is required`);
          }
        }
        break;

      case 3:
        const $selectedType = $('input[name="registration_type_id"]:checked');
        const selectedTypeValue = $selectedType.val();

        if (!selectedTypeValue) {
          isValid = false;
          showFieldError(
            $(".crs-registration-types"),
            "Please select a registration type",
          );
        } else {
          // Find the selected option element to get data-proof attribute
          const $selectedOption = $selectedType.closest(
            ".crs-registration-option",
          );
          const proofRequired = $selectedOption.data("proof");

          // Check if proof is required
          if (proofRequired == 1) {
            // Check if file is uploaded (using sessionStorage)
            const hasFileInSession =
              sessionStorage.getItem("crs_proof_file_data") &&
              sessionStorage.getItem("crs_proof_file_name");

            // Also check if file input has a file
            const $proofFile = $("#proof_file");
            const hasFileInput = $proofFile[0]?.files?.length > 0;

            // Check if window.proofFile exists (from your existing code)
            const hasWindowFile = window.proofFile ? true : false;

            if (!hasFileInSession && !hasFileInput && !hasWindowFile) {
              isValid = false;
              showFieldError(
                $("#proof-upload-section"),
                "Proof document is required for this registration type",
              );
            }
          }
        }
        break;

      case 4:
        const hotelId = $('input[name="hotel_id"]:checked').val();
        const $checkIn = $("#check_in_date");
        const $checkOut = $("#check_out_date");

        if (hotelId && hotelId !== "0") {
          if (!$checkIn.val()) {
            isValid = false;
            showFieldError($checkIn, "Please select check-in date");
          }
          if (!$checkOut.val()) {
            isValid = false;
            showFieldError($checkOut, "Please select check-out date");
          }

          if ($checkIn.val() && $checkOut.val()) {
            const checkInDate = new Date($checkIn.val());
            const checkOutDate = new Date($checkOut.val());
            if (checkOutDate <= checkInDate) {
              isValid = false;
              showFieldError(
                $checkOut,
                "Check-out date must be after check-in date",
              );
            }
          }
        }
        break;

      case 5:
        // Meals step - optional, no validation
        break;

      case 6:
        // Workshops step - optional, no validation
        break;

      case 7:
        if (!$("#image_release").is(":checked")) {
          isValid = false;
          showFieldError(
            $("#image_release").closest(".crs-option-card"),
            "Please authorize image release",
          );
        }
        break;

      case 8:
        if (!$("#accept_terms").is(":checked")) {
          isValid = false;
          showFieldError(
            $("#accept_terms").closest(".crs-option-card"),
            "Please accept the terms and conditions",
          );
        }
        break;
    }

    return isValid;
  }

  // Create third person user immediately after step 1 validation
  function createThirdPersonUser() {
    console.log("Creating third person user...");

    $.ajax({
      url: crs_ajax.ajax_url,
      type: "POST",
      data: {
        action: "crs_create_third_person_user",
        data: JSON.stringify(formData),
        nonce: crs_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          console.log("Third person user created successfully");
          console.log("New user ID:", response.data.user_id);

          // ONLY save the user ID, DO NOT change current session
          formData.third_person_user_id = response.data.user_id;
          formData.third_person_created = true;

          // Save to localStorage
          saveToLocalStorage();

          // Proceed to next step
          saveCurrentStep(function () {
            navigateToStep(2);
          });
        } else {
          console.error("Failed to create third person user:", response.data);
          alert(
            "Failed to create user: " +
              (response.data.message || "Please try again"),
          );
          // Reset the form to allow retry
          $('input[name="third_person_name"]').focus();
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error creating user:", error);
        alert("Failed to create user. Please check your connection.");
      },
    });
  }

  // নতুন function যোগ করুন - error message show করার জন্য
  function showFieldError($element, message) {
    // Add error class to the input
    if ($element.is("input, select, textarea")) {
      $element.addClass("crs-input-error");
    } else {
      $element
        .find("input, select, textarea")
        .first()
        .addClass("crs-input-error");
    }

    // Remove existing error message
    $element.closest(".crs-form-group").find(".crs-error-message").remove();

    // Add error message
    const errorHtml = `<div class="crs-error-message" style="color: #dc2626; font-size: 12px; margin-top: 5px;">⚠️ ${message}</div>`;

    if ($element.is("input, select, textarea")) {
      $element.after(errorHtml);
    } else {
      $element.append(errorHtml);
    }

    // Scroll to first error
    if (!$(".crs-input-error").first().length) {
      $("html, body").animate(
        {
          scrollTop: $element.offset().top - 100,
        },
        500,
      );
    }
  }

  function saveCurrentStep(callback) {
    const stepData = collectStepData(currentStep);

    if (stepData) {
      formData = $.extend(formData, stepData);
    }

    if (callback) {
      callback();
    }
  }

  function saveAllData(callback) {
    formData.congress_id = congressId;

    if (callback) {
      callback();
    }
  }

  function collectStepData(step) {
    const data = {};

    switch (step) {
      case 1:
        data.registration_type = $("#registration_type").val();
        data.third_person_name = $('input[name="third_person_name"]').val();
        data.third_person_email = $('input[name="third_person_email"]').val();
        data.third_person_password = $(
          'input[name="third_person_password"]',
        ).val();
        break;

      case 2:
        data.first_name = $('input[name="first_name"]').val();
        data.last_name = $('input[name="last_name"]').val();
        data.id_number = $('input[name="id_number"]').val();
        data.phone = $('input[name="phone"]').val();
        data.address = $('input[name="address"]').val();
        data.location = $('input[name="location"]').val();
        data.postal_code = $('input[name="postal_code"]').val();
        data.country = $('input[name="country"]').val();
        data.province = $('input[name="province"]').val();
        data.work_center = $('input[name="work_center"]').val();
        data.email = $('input[name="email"]').val();
        break;

      case 3:
        data.registration_type_id = $(
          'input[name="registration_type_id"]:checked',
        ).val();
        data.add_sidi = $('input[name="add_sidi"]').is(":checked");

        var proofFileInput = $("#proof_file")[0];
        if (
          proofFileInput &&
          proofFileInput.files &&
          proofFileInput.files.length > 0
        ) {
          window.pendingProofFile = proofFileInput.files[0];
          data.has_proof_file = true;
          data.proof_file_name = proofFileInput.files[0].name;
        }
        break;

      case 4:
        data.hotel_id = $('input[name="hotel_id"]:checked').val();
        data.check_in_date = $("#check_in_date").val();
        data.check_out_date = $("#check_out_date").val();
        break;

      case 5:
        data.meals = [];
        $('input[name^="meals"]:checked').each(function () {
          data.meals.push($(this).val());
        });

        data.diet = [];
        $('input[name="diet[]"]:checked').each(function () {
          data.diet.push($(this).val());
        });

        if (data.diet.length === 0) {
          data.diet = ["no"];
        }

        data.diet_other = $('input[name="diet_other"]').val();
        data.allergy = $('input[name="allergy"]:checked').val() || "no";
        data.allergy_details = $('input[name="allergy_details"]').val();
        break;

      case 6:
        data.workshops = [];
        $('input[name^="workshops"]:checked').each(function () {
          data.workshops.push($(this).val());
        });
        data.free_communication = $(
          'input[name="free_communication"]:checked',
        ).val();
        break;

      case 7:
        data.image_release = $("#image_release").is(":checked") ? "1" : "";
        data.observations = $('textarea[name="observations"]').val();
        break;

      case 8:
        data.request_invoice = $("#request_invoice").is(":checked");
        data.company_name = $('input[name="company_name"]').val();
        data.tax_address = $('input[name="tax_address"]').val();
        data.cif = $('input[name="cif"]').val();
        data.invoice_phone = $('input[name="invoice_phone"]').val();
        data.invoice_email = $('input[name="invoice_email"]').val();
        data.accept_terms = $("#accept_terms").is(":checked");
        break;
    }

    return data;
  }

  function restoreFormData() {
    if (!formData) return;

    for (let key in formData) {
      const element = $(`[name="${key}"]`);

      if (element.length) {
        if (element.is(":radio")) {
          element.filter(`[value="${formData[key]}"]`).prop("checked", true);
        } else if (element.is(":checkbox")) {
          if (Array.isArray(formData[key])) {
            formData[key].forEach(function (val) {
              $(`[name="${key}"][value="${val}"]`).prop("checked", true);
            });
          } else {
            element.prop("checked", formData[key]);
          }
        } else {
          element.val(formData[key]);
        }
      }
    }
  }

  function updateUrlStep(step) {
    // Check if we are on congress registration page
    const isCongressPage =
      $(".crs-registration-form").length > 0 ||
      $("#crs-step-container").length > 0 ||
      window.location.href.indexOf("congress") !== -1;

    // Only update URL if on congress registration page
    if (isCongressPage) {
      const url = new URL(window.location.href);
      url.searchParams.set("step", step);
      window.history.pushState({}, "", url);
    }
  }

  // frontend.js এ সম্পূর্ণ updateNavigationButtons function প্রতিস্থাপন করুন
  function updateNavigationButtons() {
    const $navigation = $(".crs-navigation");

    // Clear existing buttons
    $navigation.empty();

    // Add Back button (for all steps except first)
    if (currentStep > 1) {
      const backButton = `
      <button type="button" class="crs-btn crs-btn-secondary" id="crs-prev-step">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 12H5"></path>
          <path d="M12 19l-7-7 7-7"></path>
        </svg>
        Back
      </button>
    `;
      $navigation.append(backButton);
    }

    // Add Continue button (for all steps except last)
    if (currentStep < 8) {
      const continueButton = `
      <button type="button" class="crs-btn crs-btn-primary" id="crs-next-step">
        Continue
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M5 12h14"></path>
          <path d="M12 5l7 7-7 7"></path>
        </svg>
      </button>
    `;
      $navigation.append(continueButton);
    }

    // Attach event listeners
    attachNavigationEvents();
  }

  function attachNavigationEvents() {
    $("#crs-next-step")
      .off("click")
      .on("click", function (e) {
        if (currentStep === 7) {
          if (!validateStep7()) {
            e.preventDefault();
            e.stopPropagation();
            return false;
          }
        }

        if (validateStep(currentStep)) {
          saveCurrentStep(function () {
            navigateToStep(currentStep + 1);
          });
        }
      });

    $("#crs-prev-step")
      .off("click")
      .on("click", function () {
        navigateToStep(currentStep - 1);
      });
  }

  function updateMealsTotal() {
    let total = 0;

    $(".crs-meal-option input:checked").each(function () {
      total += $(this).closest(".crs-meal-option").data("price");
    });

    if ($(".crs-meals-total").length) {
      $(".crs-meals-total").text(`€${total}`);
    } else {
      $(".crs-meals-list").after(
        `<div class="crs-meals-total">Total: €${total}</div>`,
      );
    }
  }
})(jQuery);
