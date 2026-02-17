jQuery(document).ready(function($) {
const initial_client_list = $("#data-list").html();

$('.delete-client').click(function(e) {
      e.preventDefault();
      var clientId = $(this).data('client-id');
      if (confirm('Are you sure you want to delete this client?')) {
          $('input[name="delete_client_id"][value="' + clientId + '"]').closest('form').submit();
      }
  });

$("#add-new-cl-btn").click(function(){
    $("#add-client-block").toggle();
    $('input[name="chk_src"]').val('q_inv');
    if ($('#add-client-block').is(':visible')) {
      // If #add-in-block is visible, hide .balances-container
      $('#list-clients').hide();
    } else {
        // If #add-in-block is not visible, show .balances-container
        $('#list-clients').show();
    }
  });

$("#add-new-quote-btn").click(function(){
    $("#add-quote-block").toggle();
    if ($('#add-quote-block').is(':visible')) {
      // If #add-in-block is visible, hide .balances-container
      $('#list-quotes').hide();
    } else {
        // If #add-in-block is not visible, show .balances-container
        $('#list-quotes').show();
    }
  });

$("#add-new-invoice-btn").click(function(){
    $("#add-invoice-block").toggle();
  });

/** filter clients on search */
  $('#client-search').on('keyup', function() {
    let searchText = $(this).val().toLowerCase();
    $('.cl-row').each(function() {
      let clientText = $(this).text().toLowerCase();
      if (clientText.includes(searchText)) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });

  /** sort clients alphbetically */
  $('#sort-name').on('click', function() {
    let clients = $('.cl-row').sort(function(a, b) {
      let nameA = $(a).find('.pelatis').text().toLowerCase();
      let nameB = $(b).find('.pelatis').text().toLowerCase();
      return nameA.localeCompare(nameB);
    });
    $('#data-list').html(clients);
  });

  let partnersOnly = false;

  $('#sort-partners').on('click', function() {
    if (!partnersOnly) {
        // Show only partner clients
        $('.cl-row').each(function() {
            // Check if this row has the green saved icon (partner == 1)
            if ($(this).find('.dashicons-saved').length > 0) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        partnersOnly = true;
        $(this).text('All Clients'); // change button label
    } else {
        // Reset to show all clients
        $('.cl-row').show();
        partnersOnly = false;
        $(this).text('Partners'); // revert button label
    }
  });

  /** reload initial list sort is newer first */
  $('#sort-id').on('click', function() {
    $('#data-list').html(initial_client_list);
  });

  /** fade out client added message */
  setTimeout(function() {
    $('.alert-danger, .alert-success').fadeOut('slow');
  }, 4000); // 4 seconds

  //edit client entry
  $('.edit-client').click(function(e) {
    e.preventDefault();
    var clientId = $(this).data('id');
    
    $("#add-client-block").show();
    if ($('#add-client-block').is(':visible')) {
      // If #add-in-block is visible, hide .balances-container
      $('#list-clients').hide();
    } else {
        // If #add-in-block is not visible, show .balances-container
        $('#list-clients').show();
    }
    // Find the client data
    var clientRow = $(this).closest('.cl-row');
    var name = clientRow.find('.pelatis').text().trim();
    var phone = clientRow.find('a[href^="tel:"]').text().trim();
    var email = clientRow.find('.smaller-gray').text().trim();
    var address = clientRow.find('.client-item').eq(2).text().trim();
    var extra = "";  // You can retrieve extra info here if needed

    // Populate the form with the client data
    $('#client_id').val(clientId);
    $('#clname').val(name);
    $('#clphone').val(phone);
    $('#clemail').val(email);
    $('#claddress').val(address);
    $('#clextra').val(extra);

    // Change the button text to "Αποθήκευση αλλαγών"
    $('input[name="bms_add_client"]').val('Save Changes');
  });

  //triger edit client on request from stock
  // Get the srcstock value from the URL query parameters
  const urlParams = new URLSearchParams(window.location.search);
  const srcstock = urlParams.get('srcstock');
  
  // Find the .edit-client anchor tag with matching data-id and trigger its click event
  $('.edit-client[data-id="' + srcstock + '"]').trigger('click');

  /* Prevent form resubmiting on refresh */
  if ( window.history.replaceState ) {
          window.history.replaceState( null, null, window.location.href );
  }

  /** add new item line */
  const empty_item_line = `
      <div id="line0" class="row mb-3 dynamic-line position-relative">
        <div class="position-absolute top-0 start-0 drag-icon" style="width:50px;">
                <span class="drag-handle" style="cursor: grab;">☰</span>
        </div>
        <button type="button" class="btn-close position-absolute top-0 end-0 btn-close-in-item" aria-label="Close"></button>
        <div class="col-md-7">
            <label for="item-descr" class="form-label">Product</label>
            <textarea name="item_line[]" id="item-descr" class="form-control item-descr" required></textarea>
        </div>
        <div class="col-md-1">
            <label for="item-quantity" class="form-label">#</label>
            <input type="text" name="item-quantity[]" value=1 class="form-control item-quantity only-num" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Unit price</label>
            <input type="text" name="unit-price[]" value="0" placeholder="0" class="form-control unit-price only-num" required>
        </div>
        <div class="col-md-2">
            <label for="price_1" class="form-label">Price (&euro;)</label>
            <input type="text" name="price[]" id="price_1" value=0 readonly class="form-control show-price" required>
        </div>
    </div>
          `;

  $(document).on('click', '.btn-close', function() {
    if (!$(this).hasClass('recalc')) {
      resetCalc();//to put vat in initial state
      $(this).closest('.dynamic-line').remove();
    }
  });

  //hide #calculations is any change to the items occur
  $(document).on('change', '.unit-price, .item-quantity, .add-price', function() {
      resetCalc();
  });

  $('#add-item-line').on('click', function() {
    resetCalc();
    //fix the numbering where are already more than one items (on edit ect)
    let lineCounter = $('#items-container .row').length;
    let newLine = empty_item_line.replace(/line0/g, 'line' + lineCounter);
    $("#items-container").append(newLine); // Append
    lineCounter++;
  });

  /** Prices calculations */
  var price_before = 0;
  var price_after = 0;
  var price_vat = 0;
  var vat_checker = 1;
  var vat = Number($("#vat").val());
  var discount = 0;
  var discounted_price = 0;
  //trigger calc initially in case of load initial values (for create invoice from quote or other)
  calc_final_before_vat ();

  
  $(document).on( "keyup", "input.item-quantity" , function() {
    let quantity = $(this).val();
    let unit_price = $(this).parent().next().find('.unit-price').val();
    let res = calc_price(quantity, unit_price);
    $(this).parent().next().next().find('.show-price').val(res);
    calc_final_before_vat ();
  });

  $(document).on( "keyup", "input.unit-price" , function() {
    let unit_price = $(this).val();
    let quantity = $(this).parent().prev().find('.item-quantity').val();
    let res = calc_price(quantity, unit_price);
    $(this).parent().next().find('.show-price').val(res);
    calc_final_before_vat ();
  });

  function calc_price (quantity, unit_price){
    let result = quantity * unit_price;
    let roundedResult = Math.round(result * 100) / 100;
    return roundedResult;
  }

  function calc_final_before_vat (){
      price_before=0;
      $(".show-price").each(function() {
        price_before+= Number($(this).val());
      });
      price_before = Math.round(price_before * 100) / 100;
  }

/** prevent typing non valid chars on number fields */
$('.only-num').on('input', function() {
  // Get the current value of the input
  var currentValue = $(this).val();

  // Replace any non-numeric characters except the decimal point
  var sanitizedValue = currentValue.replace(/[^0-9.]/g, '');

  // Only allow one decimal point
  var parts = sanitizedValue.split('.');
  if (parts.length > 2) {
    sanitizedValue = parts[0] + '.' + parts.slice(1).join('');
  }

  // Set the sanitized value back to the input
  $(this).val(sanitizedValue);
});

// Optional: Prevent pasting non-numeric content
$('.only-num').on('paste', function(e) {
  var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
  var pastedData = clipboardData.getData('text');

  if (!/^\d*\.?\d*$/.test(pastedData)) {
    e.preventDefault();
  }
});
/** ================= QUOTES SECTION START ========================= */
/** Validate date and convert to dd-mm-yyyy */
//var date_checker = 0;
$('input[name="date"]').on('input', function() {
  /** gpt advanced date handling == postponed
  let inputValue = $(this).val().trim();
  let dateRegex = /^(\d{1,2})(\/|-|\.)(\d{1,2})\2(\d{2}|\d{4})$/;
  let match = dateRegex.exec(inputValue);
  date_checker = (!match)? 0 : 1;

  // Extract day, month, year from the matched groups
  if (date_checker == 1) {
  let day = match[1].padStart(2, '0');
  let month = match[3].padStart(2, '0');
  let year = match[4];

  // Correct two-digit years to four-digit years
  if (year.length === 2) {
    year = '20' + year;  
  }
  // Construct the formatted date in "dd-mm-yyyy"
  let formattedDate = `${day}-${month}-${year}`;
  //$(this).val(formattedDate);

  // If there's a hidden input to save the formatted date, you can do it like this
  $('#converted_date').val(formattedDate);
  }
  Instead we annlow numberics and date breakers*/
  $(this).val($(this).val().replace(/[^0-9\/\-.]/g, ''));
});

$('input[name="valid_until"]').on('input', function() {
  $(this).val($(this).val().replace(/[^0-9\/\-.]/g, ''));
});

/** validate before submitting quote */
$('#quote-form').on('submit', function(e) {
  var isValid = true;

  // Validate client selection - FIXED: changed #client_id to .client-select
  if ($('.client-select').val() == '0') {
    alert('Select client');
    isValid = false;
  }
  
  // Prevent form submission if validation failed
  if (!isValid) {
    e.preventDefault();
  }
});

/** ============ List quotes =================== */
$('#quote-search').on('keyup', function() {
  var value = $(this).val().toLowerCase();
  $('#data-list .quote-row').filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
  });
});

// Sorting functionality
$('#sort-quote-no').on('click', function() {
  sortQuotes('quote_no');
});

$('#sort-client-name').on('click', function() {
  sortQuotes('client_name');
});

function sortQuotes(field) {
  var quotes = $('#data-list .quote-row').get();
  quotes.sort(function(a, b) {
      var aValue = $(a).find('div:eq(0)').text().toLowerCase();
      var bValue = $(b).find('div:eq(0)').text().toLowerCase();
      if (field === 'client_name') {
          aValue = $(a).find('div:eq(2)').text().toLowerCase();
          bValue = $(b).find('div:eq(2)').text().toLowerCase();
      }
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
  });
  $.each(quotes, function(index, quote) {
      $('#data-list').append(quote);
  });
}//end of list quotes =====================================

/** ============ Quote Actions: View, Modify, Save As New, Pdf ================ */
$('.view-quote').on('click', function() {
  var quoteId = $(this).data('quote-id');
  $.ajax({
    url: quoteAjax.ajaxurl,  // Use the localized ajaxurl
    type: 'POST',
    data: {
        action: 'fetch_data_for_print',
        this_id: quoteId,
        source: 'quote'
    },
    success: function(response) {
        console.log('AJAX Success: ', response);
        if (response.success) {
            openPrintableForm(response.data, "quote");
        } else {
            alert('Failed to fetch quote data: ' + response.data);
        }
    },
    error: function(xhr, status, error) {
        console.log('AJAX Error: ', xhr.responseText);
        alert('An error occurred while fetching quote data.');
    }
  });
});

$('.modify-quote').on('click', function() {
  let quoteId = $(this).data('quote-id');

  // Load quote data and show the form to modify
  $.ajax({
    url: modifyAjax.ajaxurl,
    type: 'POST',
    data: {
      action: 'load_quote_data',
      quote_id: quoteId
    },
    success: function(response) {
      if(response.success) {
        let data = response.data;
        let request_type = "modify";
        update_or_save_new(data, request_type);
        $('#list-quotes').hide();
      } else {
        console.error('Error:', response.data); // Log the error if needed
      }
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error:', error);
    }
  });
});


$('.save-new').on('click', function(e) {
  e.preventDefault();
  let quoteId = $(this).data('quote-id');
  // Load quote data and show the form to modify
  $.ajax({
      url: saveNewAjax.ajaxurl,
      type: 'POST',
      data: {
          action: 'save_quote_as_new',
          quote_id: quoteId
      },
      success: function(response) {
        let data = JSON.parse(response);
        let request_type = "savenew";
        let new_quote_no = data.quote_no;
        update_or_save_new(data, request_type, new_quote_no);
        $('#list-quotes').hide();
      }
    });
  });

/** the const is used in the function to add the close button on modify */

const CLOSE_BTN_HTML = `
  <button type="button"
          class="btn-close position-absolute top-0 end-0 btn-close-in-item"
          aria-label="Close"
          >
  </button>`;


function update_or_save_new(data, request_type, new_quote_no = 0){
    let main=data.results;
    let quote_no = (new_quote_no == 0)? main.quote_no : new_quote_no;
    
    $('#add-quote-block').show();
    $('#quote_id').val(main.id);
    $('#quote_no').val(quote_no);
    $('#to_include').val(main.include);
    $('html, body').animate({ scrollTop: 0 }, 'slow');
    let dateValue = main.creation_date;
    let dateValue2 = main.valid_until;
    $('#date').val(dateValue);
    $('#valid_until').val(dateValue2);
    let client = main.client_id;
    $("#client_select_simple option").each(function() {
      if ($(this).val() == client) {
        $("#client_select_simple").val(String(client)).trigger('change');
        return false; // Stop iterating once the option is found
      }
    });
    $('select[name="lang"]').val('el');
    if (main.lang == 'en'){
      $('select[name="lang"]').val('en');
    }
    $('#product_description').val(main.product_description);
    $('#total-before-vat').text(main.total_before_vat);
    price_before = Number(main.total_before_vat);
    $('#vat').val(main.vat);
    $('#vat-price').text(main.vat_price);
    price_vat = Number(main.vat_price);
    $('#total-after-vat').text(main.total_after_vat);
    price_after = Number(main.total_after_vat);
    $('#total-after-discount').text(main.total_after_discount);
    discounted_price = Number(main.total_after_discount);
    $('#delivery_time').val(main.delivery_time);
    /** add the following two lines if #discount-cont is shown i.e. display block check this */
    if ($('#discount-cont').css('display', 'block')){
      $('#discount_descr').val(main.discount_description);
      $('#discount_val').val(main.discount_val);
    }
    $('input[name="save_type"]').val(request_type);
    //$('#quote_no').prop("readonly", true);
    (new_quote_no == 0)? $('#bms_add_quote').attr('value', 'Save changes') : $('#bms_add_quote').attr('value', 'Save as new');

    /* Items*/
    $.each(data.items, function(index, item) {
      if (index==0) {
        $('#line0').find('.item-descr').val(item.description);
        $('#line0').find('.item-quantity').val(item.quantity);
        $('#line0').find('.unit-price').val(item.unit_price);
        $('#line0').find('.show-price').val(item.price);
        $('#line0').prepend(CLOSE_BTN_HTML);
      }else{
        let c = index - 1;
        let outer = $('#line' + c).clone();
        outer.attr('id', 'line' + index); // Update the ID of the cloned element
        let newLine = outer.prop('outerHTML');
        $('#items-container').append(newLine);
        $('#line' + index).find('.item-descr').val(item.description);
        $('#line' + index).find('.item-quantity').val(item.quantity);
        $('#line' + index).find('.unit-price').val(item.unit_price);
        $('#line' + index).find('.show-price').val(item.price);
        $('#line' + index).prepend(CLOSE_BTN_HTML);
      }
  });
}

$('.delete-quote').on('click', function() {
  let quoteId = $(this).data('quote-id');
  if (confirm('Are you sure that you want to delete this Quote?')) {
      $.ajax({
          url: deleteQuoteAjax.ajaxurl,
          type: 'POST',
          data: {
              action: 'delete_quote',
              quote_id: quoteId
          },
          success: function(response) {
              let responseObj = JSON.parse(response);
              if (responseObj.success) {
                  //remove this row
                  let successAlert = `
                        <div class="alert alert-success" role="alert">
                            Quote deleted successfully.
                        </div>
                    `;
                  $('.entry-content').prepend(successAlert); // You can prepend to a specific container if needed
                  $('[data-quote-id="'+quoteId+'"]').closest('div').parent().parent().remove();
                  //location.reload(); // this is replaced with the above
                  setTimeout(function() {
                    $('.alert-success').fadeOut('slow', function() {
                        $(this).remove();
                    });
                  }, 4000);
                } else {
                  alert('Failed to delete quote: ' + responseObj.error);
              }
          },
          error: function(xhr, status, error) {
              alert('AJAX request failed: ' + error);
          }
      });
  }
});
// End of quote actions ====================


/** =============      INVOICES SECTION ================================================== */
/** validate before submitting invoice */
//###################### fix similar to quotes new approach #########################
$('#invoice-form').on('submit', function(e) {
  var isValid = true;

  // Validate client selection
  if ($('#client_id').val() == '0') {
    alert('Select client');
    isValid = false;
  }

  $('input[name="total-before-vat"]').val(price_before);
  $('input[name="vat-price"]').val(price_vat);
  $('input[name="total-after-vat"]').val(price_after);
  $('input[name="total-after-discount"]').val(discounted_price);

  // Prevent form submission if validation failed
  if (!isValid) {
    e.preventDefault();
  }
});

/** ============ List invoices =================== */
//prevent invoice submit if no client is selected
  $('#bms_add_invoice').on('click', function(e) {
      var clientVal = $('#client_select_simple').val();

      if (!clientVal || clientVal === "0") {
          e.preventDefault(); // stop form submission
          alert('Please select a client before saving the invoice.');
          $('#client_select_simple').focus();
      }
  });


$('#invoice-search').on('keyup', function() {
  var value = $(this).val().toLowerCase();
  $('#data-list .invoice-row').filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
  });
});

// Sorting functionality
$('#sort-invoice-no').on('click', function() {
  sortInvoices('invoice_no');
});

$('#sort-client-name').on('click', function() {
  sortInvoices('client_name');
});

function sortInvoices(field) {
  var invoices = $('#data-list .invoice-row').get();
  invoices.sort(function(a, b) {
      var aValue = $(a).find('div:eq(0)').text().toLowerCase();
      var bValue = $(b).find('div:eq(0)').text().toLowerCase();
      if (field === 'client_name') {
          aValue = $(a).find('div:eq(2)').text().toLowerCase();
          bValue = $(b).find('div:eq(2)').text().toLowerCase();
      }
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
  });
  $.each(invoices, function(index, invoice) {
      $('#data-list').append(invoice);
  });
}//end of list invoices =====================================

/** ============ Invoice Actions: View, Modify, Save As New, Pdf ================ */
$('.view-invoice').on('click', function() {
  var invoiceId = $(this).data('invoice-id');
  $.ajax({
    url: invoiceAjax.ajaxurl,  // Use the localized ajaxurl
    type: 'POST',
    data: {
        action: 'fetch_data_for_print',
        this_id: invoiceId,
        source: 'invoice'
    },
    success: function(response) {
        console.log('AJAX Success: ', response);
        if (response.success) {
            openPrintableForm(response.data, 'invoice');
        } else {
            alert('Failed to fetch invoice data: ' + response.data);
        }  
      },
    error: function(xhr, status, error) {
        console.log('AJAX Error: ', xhr.responseText);
        alert('An error occurred while fetching invoice data.');
    }
  });
});

$('.modify-invoice').on('click', function() {
  let invoiceId = $(this).data('invoice-id');
  // Load invoice data and show the form to modify
  $.ajax({
      url: modifyInvAjax.ajaxurl,
      type: 'POST',
      data: {
          action: 'load_invoice_data',
          invoice_id: invoiceId
      },
      success: function(response) {
        let data = JSON.parse(response);
        let request_type = "modify";
        update_or_save_new_inv(data, request_type);
        $('#list-invoices').hide();
      }
    });
  });

$('.save-new-inv').on('click', function(e) {
  e.preventDefault();
  let invoiceId = $(this).data('invoice-id');
  // Load invoice data and show the form to modify
  $.ajax({
      url: saveNewInvAjax.ajaxurl,
      type: 'POST',
      data: {
          action: 'save_invoice_as_new',
          invoice_id: invoiceId
      },
      success: function(response) {
        let data = JSON.parse(response);
        let request_type = "savenew";
        let new_invoice_no = data.invoice_no;
        update_or_save_new_inv(data, request_type, new_invoice_no);
        $('#list-invoices').hide();
      }
    });
  });

function update_or_save_new_inv(data, request_type, new_invoice_no = 0){
    let main=data.results;
    let invoice_no = (new_invoice_no == 0)? main.invoice_no : new_invoice_no;

    // empty the items that will repopulate
    $("#items-container").empty();
    
    $('#add-invoice-block').show();
    $('#invoice_id').val(main.id);
    $('#invoice_no').val(invoice_no);
    $('html, body').animate({ scrollTop: 0 }, 'slow');
    $('#date').val(main.creation_date);
    let client = main.client_id;
    $("#client_select_simple option").each(function() {
      if ($(this).val() == client) {
        $("#client_select_simple").val(String(client)).trigger('change');
        return false; // Stop iterating once the option is found
      }
    });
    $('select[name="lang"]').val('el');
    if (main.lang == 'en'){
      $('select[name="lang"]').val('en');
    }
    $('#product_description').val(main.product_description);
    $('#total-before-vat').text(main.total_before_vat);
    price_before = Number(main.total_before_vat);
    $('#vat').val(main.vat);
    $('#vat-price').text(main.vat_price);
    price_vat = Number(main.vat_price);
    $('#total-after-vat').text(main.total_after_vat);
    price_after = Number(main.total_after_vat);
    $('#total-after-discount').text(main.total_after_discount);
    discounted_price = Number(main.total_after_discount);
    //$('#delivery_time').val(main.delivery_time);
    /** add the following two lines if #discount-cont is shown i.e. display block check this */
    if ($('#discount-cont').css('display', 'block')){
      $('#discount_descr').val(main.discount_description);
      $('#discount_val').val(main.discount_val);
    }
    $('input[name="save_type"]').val(request_type);
    (new_invoice_no == 0)? $('#bms_add_invoice').attr('value', 'Save changes') : $('#bms_add_invoice').attr('value', 'Save as new');

    /* Items
       Remove the items content and regenerate it
    */
    $("#items-container").empty();
    $.each(data.items, function(index, item) {
      $("#items-container").append(`
          <div class="row mb-3 dynamic-line position-relative">
            <div class="position-absolute top-0 start-0 drag-icon" style="width:50px;">
                <span class="drag-handle" style="cursor: grab;">☰</span>
            </div>
            <button type="button" class="btn-close position-absolute top-0 end-0 btn-close-in-item" aria-label="Close"></button>
            <div class="col-md-7">
              <label for="item-descr" class="form-label">Product</label>
              <textarea name="item_line[]" id="item-descr" class="form-control item-descr" required />${item.description}</textarea>
            </div>
            <div class="col-md-1">
              <label for="item-quantity" class="form-label">#</label>
              <input type="text" name="item-quantity[]" value=${item.quantity} class="form-control item-quantity only-num" required />
            </div>
            <div class="col-md-2">
              <label class="form-label">Unit price</label>
              <input type="text" name="unit-price[]" value=${item.unit_price} class="form-control unit-price only-num" required />
            </div>
            <div class="col-md-2">
              <label for="price_1" class="form-label">Price (&euro;)</label>
              <input type="text" name="price[]" id="price_1" value=${item.price} readonly class="form-control show-price" required />
            </div>
        </div>
        `);
  });
}

// ========================= PRINT : Shared Between Invoices and Projects ================================================
function openPrintableForm(invoiceData, source = 'project') {
  /* -------------------------
  LANGUAGE / TEXTS
  -------------------------- */
  const lang = invoiceData.lang;
  const textVals = lang === 'el'
    ? invoiceData.invoice_texts.el
    : invoiceData.invoice_texts.en;

  /* -------------------------
     TEMPLATE & COLORS
  -------------------------- */
  let template_and_pattern = +invoiceData.template;
  let template = 'one';
  let pattern = ['#7a8dc5', '#3b4e87', '#808b9c'];

  if (template_and_pattern > 30) template = 'three';
  else if (template_and_pattern > 20) template = 'two';

  let i = template_and_pattern % 10;
  if (i === 2) pattern = ['#ea2c0c', '#ea580c', '#f9a07f'];
  else if (i === 3) pattern = ['#d9d8df', '#c5c6cc', '#555859'];

  /* -------------------------
    UNIFIED SETTINGS (GLOBALS + LOCALS)
  -------------------------- */

  let invoiceGlobals = {};
  let invoiceLocals  = null;

  // decode globals
  try {
      invoiceGlobals = typeof invoiceData.invoice_globals === 'string'
          ? JSON.parse(invoiceData.invoice_globals)
          : (invoiceData.invoice_globals || {});
  } catch {
      invoiceGlobals = {};
  }

  // decode locals
  try {
      if (invoiceData.include) {
          invoiceLocals = typeof invoiceData.include === 'string'
              ? JSON.parse(invoiceData.include)
              : invoiceData.include;
      }
  } catch {
      invoiceLocals = null;
  }

  // 👉 single source now
  const activeSettings = invoiceLocals || invoiceGlobals;

  // KEEP YOUR FUNCTION NAMES
  const gSet = k => activeSettings[k]?.set === 'set';
  const gPos = (k, d='left') => activeSettings[k]?.position || d;
  const gVal = (k, d='') => activeSettings[k]?.value ?? d;

  /* -------------------------
     DATE
  -------------------------- */
  let formattedDate = invoiceData.creation_date;

  if (gSet('date')) {
    formattedDate = gVal('date', formattedDate);
  }else {
    //fix project date structure when nothing else isset
    if (source === 'project' && invoiceData.creation_date) {
      const d = invoiceData.creation_date.split('-');
      if (d.length === 3)
        formattedDate = `${d[2]}-${d[1]}-${d[0]}`;
    }
  }

  /* -------------------------
     LOGO
  -------------------------- */
  let logoDiv = '';
  if (gSet('logo') && invoiceData.company_logo) {
    logoDiv = `
      <div id="logo" style="text-align:${gPos('logo','center')};margin-bottom:1.5em;">
        <img src="${invoiceData.company_logo}">
        <hr>
      </div>`;
  }

  /* -------------------------
     COMPANY IDENTITY (ALWAYS ON)
  -------------------------- */
  let companyIdentityDiv = '';
  if (gSet('our_address')) {
    let extras = '';

    if (gSet('top1_registration'))
      extras += `<div>${invoiceData.company_registration}</div>`;
    if (gSet('top1_vat'))
      extras += `<div>${invoiceData.company_vat_number}</div>`;
    if (gSet('top1_website'))
      extras += `<div>${invoiceData.company_website}</div>`;

    companyIdentityDiv = `
      <div>
        ${gVal('our_address', invoiceData.company_address)}
        ${extras}
      </div>`;
  }

  /* -------------------------
   CLIENT BLOCK
  -------------------------- */
  let clientAddressDiv = '';
  if (invoiceData.client_name || invoiceData.client_address || invoiceData.client_phone || invoiceData.client_email) {

      const align = gPos('top2_client','right');

      const clientAddrLocal = activeSettings?.top2_client?.value;
      const clientAddr = gVal('top2_client', invoiceData.client_address);
      const addrLineBreak = clientAddrLocal ? '' : '<br>';

      clientAddressDiv = `
        <div style="text-align:${align}">
          <div class="text-smaller grey under">Client</div>

          ${invoiceData.client_name ? `<strong>${invoiceData.client_name}</strong><br>` : ''}
          ${clientAddr ? `${clientAddr}${addrLineBreak}` : ''}
          ${invoiceData.client_phone ? `${invoiceData.client_phone}<br>` : ''}
          ${invoiceData.client_email ? `${invoiceData.client_email}` : ''}
        </div>`;
  }


  /* -------------------------
    DESCRIPTION BLOCK
  -------------------------- */
  let descriptionDiv = '';
  if (source === 'project' && gSet('description')) {

      // project + custom settings description
      descriptionDiv = `
          <div class="project-description">
              ${gVal('description', invoiceData.product_description)}
          </div>`;

  } else if (invoiceData.product_description) {

      // fallback for quotes / invoices / anything else
      descriptionDiv = `
          <div id="top4_full_descr">
              <h2>${invoiceData.product_description}</h2>
          </div>`;
  }

  /* -------------------------
     FOOTER BLOCKS (GLOBALS)
  -------------------------- */
  let bankDiv = '';
  if (gSet('bank_details')) {
    bankDiv = `
        <div style="text-align:${gPos('bank_details','left')}">
           ${gVal('bank_details', invoiceData.company_bank)}
        </div>`;
  }

  let otherDiv = '';
  if (gSet('other_details')) {
      otherDiv = `
          <div style="text-align:${gPos('other_details','left')}">
              ${gVal('other_details', invoiceData.company_other)}
          </div>`;
  }

  /* -------------------------
    THANKS LINE
  -------------------------- */
  let thanksDiv = '';
  if (gSet('thanks_message')) {
    thanksDiv = `
        <div style="text-align:${gPos('thanks_message','center')};margin-top:2em;">
            ${gVal('thanks_message', invoiceData.company_thanks)}
        </div>`;
  }

  /* -------------------------
  DELIVERY TIME FOR QUOTES
  -------------------------- */
  let deliveryDiv = '';
  if (source === 'quote' && gSet('delivery_time')) {
    deliveryDiv = `
        <div style="text-align:${gPos('delivery_time','left')}">
            <u>${textVals.delivery_time}</u>: ${gVal('delivery_time', invoiceData.delivery_time)}
        </div>`;
  }

  /* -------------------------
  FOOTER LINE
  -------------------------- */
  let footerDiv = '';
  if (gSet('notes')) {
    footerDiv = `
        <div style="text-align:${gPos('notes','left')}">
            ${gVal('notes')}
        </div>`;
  }

  const no = invoiceData.no.toString().padStart(4, '0');
  const print_title = lang === 'el'
    ? (source === 'quote' ? 'Προσφορά' : 'Τιμολόγιο')
    : (source === 'quote' ? 'Quote' : 'Invoice');

  const print_title_en = source === "quote" ? "Quote" : "Invoice"; //en is always used to make the invoice file title


  function removeAccents(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  var win = window.open('', '_blank');
  //declare styles (common in every template)
  var styles = `
    <style>
              @media print {
                  #printPageButton { display: none; }
              }
              @page { size: A4; margin: 10mm; }
              body { 
                      font-family: 'Roboto', Arial, sans-serif; background:#ccc; 
                    }
              p {margin-block:3px;}
              .header, .footer { 
                    width:100%; text-align: center; position: fixed; background: #fff; 
                    }
              .header { 
                    top: 0px; 
                    }
              .flex-between { 
                    display:flex; justify-content: space-between; 
                    }
              .flex-v-between { 
                    display:flex; 
                    justify-content: space-between; 
                    display: flex; flex-direction: column; 
                    justify-content: space-between;
                    }

              .footer { 
                    bottom: 0px; width:210mm; background: #fff; 
                    }
              .content { 
                    width: 100%;
                    margin: auto; padding:10mm; background: #fff; 
                    max-width:190mm; 
                    min-height: 277mm;
                    }
              #logo img {
                    max-width:120px;
                    }
              table { 
                    width: 100%; border-collapse: collapse; 
                    }
              table, th, td { 
                    border: 1px solid black; 
                    }

              #table-first th, #table-first td { 
                    padding: 10px; text-align: left; 
                    }

              #table-second th, #table-second td { 
                    padding: 5px; text-align: left; 
                    }
              #table-third table {
                    border: none;
                    }

              #table-third table td {
                border: 1px solid #ccc;
              }

              #table-third table th {
                border: none;
                font-weight:normal;
              }

              #table-third th, #table-third td { 
                    padding: 6px; text-align: left; 
                    }
             
              .v-align-top {align-items: flex-start;}
              .text-size-32 {font-size:32px;}
              .text-size-24 {font-size:24px;}
              .text-size-14 {font-size:14px;}
              .text-size-10 {font-size:10px;}
              .text-smaller {font-size:0.85rem;}
              .text-col-1 {color: ${pattern[0]};}
              .text-col-2 {color: ${pattern[1]};}
              .text-col-3 {color: ${pattern[2]};}
              .text-bg-1 {background-color: ${pattern[0]};}
              .text-bg-2 {background-color: ${pattern[1]};}
              .text-bg-3 {background-color: ${pattern[2]};}
              .col-white {color:#ffffff;}
              .uppercase {text-transform:uppercase;}
              .mt-20 {margin-top:20px;}
              .mt-2em {margin-top:2em;}
              .mt-07em {margin-top:0.7em;}
              .mb-07em {margin-bottom:0.7em;}
              .p-2 {padding:2px 5px;}
              .bold {font-weight:600;}
              .align-right {text-align:right;}
              .mb-8 {margin-bottom:8px;}
              .grid {display: grid; grid-template-columns: 1fr 1fr; width: 300px;}
              .grid-left { text-align: right; padding: 8px; }
              .grid-right {text-align: center; padding: 8px; border: 1px solid #000;}
              .grey {color: #7f7f7f;}
              .under: {text-decoration:underline;}
              .text-right {text-align:right!important;}
          </style>
  `;

  // Construct the default HTML string
  var html = `
      <html>
      <head>
          <title>${print_title_en}#${invoiceData.no}</title>
          ${styles}
      </head>
      <body>
          <div class="content flex-v-between">
            <div id="upper-content">
              <div id="logo-outer">
                ${logoDiv}
              </div>
              <div id="top1" class="flex-between">
                <div>
                    <div class="text-size-24 text-col-3 bold mb-8">${invoiceData.company_name}</div>
                    ${companyIdentityDiv}
                    <div class="text-smaller text-col-3">${invoiceData.company_phone}</div>
                    <div class="text-smaller text-col-3">${invoiceData.company_email}</div>
                    ${invoiceData.company_website ? `<div class="text-smaller text-col-3">${invoiceData.company_website}</div>` : ''}
                </div>
                <div>
                    <div class="align-right text-size-32 uppercase text-col-3 bold mt-2em mt-07em mb-07em">${removeAccents(print_title)}</div>
                    <div>${textVals.date}: ${formattedDate}</div>
                    <div class="mb-8">${print_title} # <b>${no}</b></div>
                    <!--div><u>${textVals.pelatis}: </u></div-->
                      ${clientAddressDiv}
                </div>
              </div>
              <div id="top3_full">
              </div>
              <div id="top4_full_descr">
                <h2>${descriptionDiv}</h2>
              </div>
              <div id="table-first">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>${textVals.perigrafi}</th>
                            ${source != 'project' ? `<th>${textVals.monadas}</th>` : ''}
                            ${source != 'project' ? `<th>${textVals.posotita}</th>` : ''}
                            <th>${textVals.timi}</th>
                        </tr>
                    </thead>
                    <tbody>`;

          html += table_core(invoiceData, textVals, source);

          html += `</tbody>
                </table>
              </div><!--end of content div-->
              <div id="bottom1_full_delivery">
                  ${deliveryDiv}
                </div>
                <div id="bottom2_full_thanks" class="">
                  ${thanksDiv}
                </div>
                <div id="bottom3_full">
                </div>
                
                <div id="bottom5_full">
                </div>
              </div><!--end of upper content-->
              <div id="down-content">
                <div id="bottom4-row" class="flex-between text-smaller">
                  <div id="bottom4_half_bank">
                    <p><u>${textVals.trapeza}</u></p>
                    ${bankDiv}
                  </div>
                  <div id="bottom4_half_other">
                    ${otherDiv}
                  </div>
                </div><!--end of bottom4 row-->
                <div id="invoice-footer" class="text-smaller mt-2em">
                  ${footerDiv}
                </div>
              </div><!--end of down content-->
            </div><!--End of Content -->
            <div>
                <button id="printPageButton" onClick="window.print();">🖨️ Print</button>
            </div>
        </body>
      </html>`;

  //remove tonous gia na min fainontai se uppercase
  function removeAccents(str) {
    return str
      .normalize("NFD")               // split letters + accents
      .replace(/[\u0300-\u036f]/g, ""); // remove accents
  }
  textVals.pelatis = removeAccents(textVals.pelatis); 
  //design of the second template with bg colors
  if (template == 'two'){
    html = `
      <html>
      <head>
          <title>${print_title_en}#${invoiceData.no}</title>
          ${styles}
      </head>
      <body>
          <div class="content flex-v-between">
            <div id="upper-content"> 
              <div id="logo-outer">
                ${logoDiv}
              </div>
              <div id="top1" class="flex-between">
                <div>
                    <div class="text-size-24 text-col-2 bold mb-8">${invoiceData.company_name}</div>
                    ${companyIdentityDiv}
                    <div>${invoiceData.company_phone}</div>
                    <div>${invoiceData.company_email}</div>
                    ${invoiceData.company_website ? `<div>${invoiceData.company_website}</div>` : ''}
                    <div>&nbsp;</div>
                    
                    <div class="text-bg-1 col-white uppercase p-2">${textVals.pelatis}</div>
                    <div>${invoiceData.client_name}</div>
                      ${clientAddressDiv}
                </div>
                <div>
                    <div class="align-right text-size-32 uppercase text-col-1 bold mb-8">${removeAccents(print_title)}</div>
                    <div class='grid uppercase'>
                      <div class="grid-left">${removeAccents(textVals.date)}</div>
                      <div class="grid-right">${formattedDate}</div>
                      <div class="grid-left">${removeAccents(print_title)} #</div>
                      <div class="grid-right text-bg-3">${no}</div>
                    </div>
                </div>
              </div><!--End top2-->
              <div id="top3_full">
              </div>
              <div id="top4_full_descr">
                <h2>${descriptionDiv}</h2>
              </div>
              <div id="table-second">
                <table>
                  <thead>
                      <tr>
                          <th class="text-bg-1 col-white">#</th>
                          <th class="text-bg-1 col-white">${textVals.perigrafi}</th>
                          ${source != 'project' ? `<th class="text-bg-1 col-white">${textVals.monadas}</th>` : ''}
                          ${source != 'project' ? `<th class="text-bg-1 col-white">${textVals.posotita}</th>` : ''}
                          <th class="text-bg-1 col-white">${textVals.timi}</th>
                      </tr>
                  </thead>
                  <tbody>`;

        html += table_core(invoiceData, textVals, source);

        html += `</tbody>
              </table>
            </div>       
            <div id="bottom1_half" class="flex-between mt-20 v-align-top">
              ${bankDiv !== '' ? `
              <div id="bottom1_bank" style="width:66%;border:1px solid ${pattern[1]};text-align:${gPos('bank_details','left')}">
                <div class="text-bg-1 col-white p-2">
                  ${textVals.trapeza}
                </div>
                <div class="p-2" style="text-align:${gPos('bank_details','left')}">
                  ${bankDiv}
                </div>
              </div>
            ` : ''}
              <div id="bottom1_delivery">
                ${deliveryDiv}
              </div>
            </div><!--end of bottom1-->
            <div id="bottom3_full">
              <div style="text-align:${gPos('thanks_message','center')};margin-top:2em;">
                ${lang != 'el'? 'Contact us if you have any questions<br>' : 'Επικοινωνήστε μαζί μας για διευκρινίσεις<br>'}
                <h3>${thanksDiv}</h3>
              </div>
            </div>
          </div><!--end of upper content-->
          <div><!--down content-->
            <div id="bottom4_half_custom">
              ${otherDiv}
            </div>
            <div id="bottom5_full">
                ${footerDiv}
            </div>
          </div><!--end of down content-->
        </div><!--End of Content -->
        <div>
            <button id="printPageButton" onClick="window.print();">🖨️ Print</button>
        </div>
    </body>
    </html>`;
  }

  if (template == 'three'){
    html = `
      <html>
      <head>
          <title>${print_title_en}#${invoiceData.no}</title>
          ${styles}
      </head>
      <body>
          <div class="content flex-v-between">
            <div id="upper-content">
              <div id="logo-outer">
                ${logoDiv}
              </div>
              <div id="top1" class="flex-between">
                <div>
                    <div class="text-size-24 text-col-1 bold mb-8">${invoiceData.company_name}</div>
                    ${companyIdentityDiv}
                </div>
                <div>
                    <div class="align-right text-size-32 uppercase text-col-2 bold mt-2em mt-07em mb-07em">${removeAccents(print_title)}</div>
                    <div>${textVals.date}: ${formattedDate}</div>
                    <div class="mb-8">${print_title} # <b>${no}</b></div>
                    <!--div><u>${textVals.pelatis}: </u></div-->
                      ${clientAddressDiv}
                </div>
              </div>
              <div id="top3_full">
              </div>
              <div id="top4_full_descr">
                <h2>${descriptionDiv}</h2>
              </div>
              <div id="table-third">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>${textVals.perigrafi}</th>
                            ${source != 'project' ? `<th>${textVals.monadas}</th>` : ''}
                            ${source != 'project' ? `<th>${textVals.posotita}</th>` : ''}
                            <th>${textVals.timi}</th>
                        </tr>
                    </thead>
                    <tbody>`;

          html += table_core(invoiceData, textVals, source);

          html += `</tbody>
                </table>
              </div><!--end of content div-->
              <div id="bottom1_full_delivery">
                  ${deliveryDiv}
                </div>
                <div id="bottom2_full_thanks" class="">
                  ${thanksDiv}
                </div>
                <div id="bottom3_full">
                </div>
                
                <div id="bottom5_full">
                </div>
              </div><!--end of upper content-->
              <div id="down-content">
                <div id="bottom4-row" class="flex-between text-smaller">
                  <div id="bottom4_half_bank">
                    <p><u>${textVals.trapeza}</u></p>
                    ${bankDiv}
                  </div>
                  <div id="bottom4_half_other">
                    ${otherDiv}
                  </div>
                </div><!--end of bottom4 row-->
                <div id="invoice-footer" class="text-smaller mt-2em">
                  ${footerDiv}
                </div>
              </div><!--end of down content-->
            </div><!--End of Content -->
            <div>
                <button id="printPageButton" onClick="window.print();">🖨️ Print</button>
            </div>
        </body>
      </html>`;
  }

  // Write the HTML to the new window
  win.document.write(html);
  win.document.close();
}
/** Reusable part of tables for the 3 templates */
function table_core(invoiceData, textVals, source) {
    let html = "";

    // 1. Generate Item Rows
    invoiceData.items.forEach(function(item, index) {
        html += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.description}</td>
                ${source != 'project' ? `<td>${item.unit_price}</td>` : ''}
                ${source != 'project' ? `<td>${item.quantity}</td>` : ''}
                <td class="text-right">${item.price}</td>
            </tr>`;
    });

    // 2. Setup Footer Logic
    let inv_col_span = source != 'project' ? 4 : 2;

    if (invoiceData.plus_inc_vat != 3) {
        html += `
            <tr>
                <td colspan="${inv_col_span}"><strong>${textVals.synolo_prin}:</strong></td>
                <td class="text-right">${invoiceData.total_before_vat}</td>
            </tr>
            <tr>
                <td colspan="${inv_col_span}">${textVals.fpa} (${invoiceData.vat}%) ${invoiceData.plus_inc_vat == 1 ? textVals.syn : textVals.symp}:</td>
                <td class="text-right">${invoiceData.vat_price}</td>
            </tr>`;

        if (invoiceData.discount_val > 0) {
            html += `<tr><td colspan="${inv_col_span}">${textVals.ekptosi} - ${invoiceData.discount_description}:</td><td class="text-right">-${invoiceData.discount_val}</td></tr>`;
        }

        html += `<tr><td colspan="${inv_col_span}"><strong>${textVals.synolo_meta1} ${invoiceData.currency}:</strong></td><td class="text-right"> <b>${invoiceData.total_after_vat}</b></td></tr>`;
    } else {
        if (invoiceData.discount_val > 0) {
            html += `<tr><td colspan="${inv_col_span}">${textVals.ekptosi} - ${invoiceData.discount_description}:</td><td class="text-right">-${invoiceData.discount_val}</td></tr>`;
        }

        html += `<tr><td colspan="${inv_col_span}"><strong>${textVals.synolo} ${invoiceData.currency}:</strong></td><td class="text-right"> <b>${invoiceData.total_after_vat}</b></td></tr>`;
    }

    return html;
}

/** ========== Waybill print =================================================*/
function openWaybillPrintForm(waybillData) {
  const textVals = {
      'date': 'Date',
      'project': 'Project',
      'amount': 'Amount',
      'description': 'Description',
      'total': 'Total'
  };

  let currentDate = new Date();
  let formattedDate = currentDate.toLocaleDateString();

  var win = window.open('', '_blank');

  // Construct the HTML string
  var html = `
      <html>
      <head>
          <title>Waybill for ${waybillData.client_name}</title>
          <style>
              @media print {
                  #printPageButton { display: none; }
              }
              @page { size: A4; margin: 10mm; }
              body { 
                      font-family: 'Roboto', Arial, sans-serif; background:#ccc; 
                    }
              .header, .footer { 
                    width:100%; text-align: center; position: fixed; background: #fff; 
                    }
              .header { 
                    top: 0px; 
                    }
              .top { 
                    display:flex; justify-content: space-between; 
                    }
              .footer { 
                    bottom: 0px; width:210mm; background: #fff; 
                    }
              .content { 
                    width: 100%;
                    margin: auto; padding:10mm; background: #fff; 
                    max-width:190mm; 
                    min-height: 277mm;
                    }
              table { 
                    width: 100%; border-collapse: collapse; 
                    }
              table, th, td { 
                    border: 1px solid black; 
                    }
              th, td { 
                    padding: 10px; text-align: left; 
                    }
          </style>
      </head>
      <body>
          <div class="content">
              <div class="top">
                  <div>
                      <b>${waybillData.company_name}</b>
                      <br>${waybillData.company_address}<br>
                      <p>${waybillData.company_phone}</p>
                      <p>${waybillData.company_email}</p>
                  </div>
                  <div>
                      <h2>Waybill</h2>
                      <p>${textVals.date}: ${formattedDate}<br></p>
                      <p><u>Client: </u><br>
                        ${waybillData.client_name}<br>
                        ${waybillData.client_address}<br>
                        ${waybillData.client_phone}<br>
                        ${waybillData.client_email}
                      </p>
                  </div>
              </div>
              <h3>Period: ${waybillData.period_display}</h3>
              <table>
                  <thead>
                      <tr>
                          <th>${textVals.date}</th>
                          <th>${textVals.project}</th>
                          <th>${textVals.description}</th>
                          <th>${textVals.amount}</th>
                      </tr>
                  </thead>
                  <tbody>`;

  waybillData.payments.forEach(function(payment) {
      html += `
                      <tr>
                          <td>${payment.payment_date}</td>
                          <td>${payment.project_name || 'N/A'}</td>
                          <td>${payment.description || ''}</td>
                          <td>${payment.amount}</td>
                      </tr>`;
  });

  html += `<tr><td colspan="3"><strong>${textVals.total}:</strong></td><td>${waybillData.total_amount}</td></tr>
          </tbody></table>
          </div><!--End of Content -->
          <div>
              <button id="printPageButton" onClick="window.print();">🖨️ Print</button>
          </div>
      </body>
      </html>`;

  // Write the HTML to the new window
  win.document.write(html);
  win.document.close();
}//end of way bill print

$('.cancel-invoice').on('click', function() {
  let invoiceId = $(this).data('invoice-id');
  let cancelButton = $(this); // Store a reference to the clicked element
  if (confirm('Cancel invoice?')) {
      $.ajax({
          url: cancelInvoiceAjax.ajaxurl,
          type: 'POST',
          data: {
              action: 'cancel_invoice',
              invoice_id: invoiceId
          },
          success: function(response) {
              let responseObj = JSON.parse(response);
              if (responseObj.success) {
                  //remove this row
                  $('[data-invoice-id="'+invoiceId+'"]').closest('div').parent().prev().html('<span class="badge bg-secondary">Cancelled</span>');
                  cancelButton.remove(); // Use the stored reference to remove the element
                  //location.reload();// Optionally, refresh the page or remove the deleted invoice from the DOM
              } else {
                  alert('Failed to delete invoice: ' + responseObj.error);
              }
          },
          error: function(xhr, status, error) {
              alert('AJAX request failed: ' + error);
          }
      });
  }
});
// End of invoices actions ====================



  /** ==========BALANCES ================================================= */

  /*/select related project
  $('#type-select').change(function(){
    var selectedValue = $(this).val();
    window.location.href = '/balances/?paytype=' + selectedValue;
  });*/
 
  $('#type-select').change(function(){
    var selectedValue = $(this).val();
    // Use PHP-generated URL instead of hardcoded slug
    window.location.href = '<?php echo esc_url($balances_url); ?>?paytype=' + encodeURIComponent(selectedValue);
  });

  // Function to load projects from the server
  $("#find-inv").click(function(e) {
    e.preventDefault();
      $('#projectModal').appendTo("body").modal('show');
  });

  // Function to filter projects in the modal
  $('#searchProject').on('keyup', function() {
      var value = $(this).val().toLowerCase();
      $("#projectList tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
  });

  // Handle the click event on the useProj button to populate the form fields
  $(document).on('click', '.useProj', function() {
    $("#existing-payments").removeClass('d-none');
      let projectId = $(this).data('project-id');
      let description = $(this).data('description');
      let clientName = $(this).data('client-name');
      let amount = $(this).data('amount');
      let already = $(this).data('already');
      let remain = $(this).data('remain');

      $('#rel_project_id').val(projectId);
      $('#rel_project').val(description);
      $('#description').val(description);
      $('#payer').val(clientName);
      $('#amount').val(amount);
      $('#type_of_payment').val('pr_inv');
      $('.already').html(already);
      $('.remain').html(remain);

      $('#projectModal').modal('hide');
  });//end of related project

  /** Balances, view, sort */
  $('#search-balances').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $('#balances-table tbody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  $('.delete-icon').on('click', function() {
    if (confirm('Are you sure you want to delete this record?')) {
        var id = $(this).data('id');
        $.ajax({
            url: deleteBalanceAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_balance_record',
                id: id,
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload the page to reflect the changes
                } else {
                    alert('Error deleting record.');
                }
            }
        });
    }
  });

  // edit balance
  $('.edit-icon').on('click', function() {
      var rowId = $(this).data('id');
      var inOut = $(this).data('inout'); // 1 for income, 2 for outcome
      $('.balances-container').hide();

      $.ajax({
        url: editBalanceAjax.ajaxurl,
          type: 'POST',
          data: {
              action: 'get_balance_details',
              id: rowId,
          },
          success: function(response) {
            if (response.success) {
              var data = response.data;

              if (inOut == 1) {
                  $('#add-in-block').show();
                  $('#add-out-block').hide();
                  $('#transaction-form-in #rel_project_id').val(data.rel_project_id);
                  $('#transaction-form-in #rel_project').val(data.description);
                  $('#transaction-form-in #description').val(data.description);
                  $('#transaction-form-in #payer').val(data.payer_payee);
                  $('#transaction-form-in #amount').val(data.amount);
                  $('#transaction-form-in #type_of_payment').val(data.type_of_payment);
                  $('#transaction-form-in #payment_date').val(data.payment_date);
                  $('#transaction-form-in #record_id_in').val(data.id);//also pass the record id
                  $(".btn[name='add-in-transaction']").val("Save changes");
                  //$('#transaction-form-in').attr('action', ''); // Set appropriate action
              } else {
                  $('#add-out-block').show();
                  $('#add-in-block').hide();
                  $('#transaction-form-out #rel_invoice').val(data.rel_invoice);
                    $('#transaction-form-out #description').val(data.description);
                    $('#transaction-form-out #payer').val(data.payer_payee);
                    $('#transaction-form-out #amount').val(data.amount);
                    $('#transaction-form-out #type_of_payment').val(data.type_of_payment);
                    $('#transaction-form-out #payment_date').val(data.payment_date);
                    $('#transaction-form-out #record_id_out').val(data.id);
                  $(".btn[name='add-out-transaction']").val("Save changes");
                  //$('#transaction-form-out').attr('action', ''); // Set appropriate action
              }
            } else {
                // Handle errors
                alert('Error fetching data');
            }
          }
      });
  });

 // === end of balances: view, sort, del, edit ===

  $('#add-new-in-btn').click(function(){
    $('#add-in-block').toggle();
    $('#add-out-block').hide();
    if ($('#add-in-block').is(':visible')) {
      // If #add-in-block is visible, hide .balances-container
      $('.balances-container').hide();
    } else {
        // If #add-in-block is not visible, show .balances-container
        $('.balances-container').show();
    }
  });

  $('#add-new-out-btn').click(function(){
    $('#add-out-block').toggle();
    $('#add-in-block').hide();
    if ($('#add-out-block').is(':visible')) {
      // If #add-in-block is visible, hide .balances-container
      $('.balances-container').hide();
    } else {
      // If #add-in-block is not visible, show .balances-container
      $('.balances-container').show();
    }
  });


  //print balances table
  $('#print-table').on('click', function() {
      var printContents = document.getElementById('balances-table').outerHTML;
      var originalContents = document.body.innerHTML;

      document.body.innerHTML = '<html><head><title>Print</title><style>body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } </style></head><body>' + printContents + '</body></html>';

      window.print();

      document.body.innerHTML = originalContents;
      location.reload();  // Reload the page to reset the state
  });

/** =============== STOCK ================ */
  // When clicking the pencil icon next to Notes
  $('.edit-note-icon').on('click', function () {

      let inid    = $(this).data('inid');
      let note  = $(this).data('note');
      let color = $(this).data('color');

      // Fill modal fields
      $('#note_edit_id').val(inid);
      $('#note_edit_text').val(note);

      if (color) {
          $('#note_edit_color').val(color.toLowerCase());
      } else {
          $('#note_edit_color').val('');
      }
  });
  //pencil to edit in dimensions
  $(document).on('click', '.edit-dimensions-btn', function() {
    let id     = $(this).data('id');
    let mikos  = $(this).data('mikos');
    let platos = $(this).data('platos');

    $('#edit-stock-in-id').val(id);
    $('#edit-mikos').val(mikos);
    $('#edit-platos').val(platos);
  });

  //prevent error with comma in decimal
  $(".decimal-input").on("input", function () {
      $(this).val($(this).val().replace(",", "."));
  });

  //filter outs by client
  $('#out_client_filter').on('change', function () {
      let selectedClientId = $(this).val();

      if (selectedClientId === 'all') {
          $('[data-client-id]').show();
          return;
      }

      // Hide all rows with data-client-id
      $('[data-client-id]').hide();

      // Show only selected client's rows
      $(`[data-client-id='${selectedClientId}']`).show();
  });
  
  //show hide stock-ins
  $('#toggle-ins-btn').on('click', function() {
      var $btn = $(this);
      var $container = $('#in_container');
      
      // Toggle visibility of #in_container
      $container.toggle();
      
      // Change button text
      if ($container.is(':visible')) {
          $btn.text('Hide Ins');
      } else {
          $btn.text('Show Ins');
      }
  });

  //show hide stock-outs
  $('#toggle-outs-btn').on('click', function() {
      var $btn = $(this);
      var $container = $('#out_container');
      
      // Toggle visibility of #in_container
      $container.toggle();
      
      // Change button text
      if ($container.is(':visible')) {
          $btn.text('Hide Outs');
      } else {
          $btn.text('Show Outs');
      }
  });

  $('#addStockBtn').on('click', function() {
      $('#add_stock').toggleClass('d-none');
      $('#stock_container').toggleClass('d-none');
  });

  $('.cut-this').on('click', function() {
    $('#stockModal').modal('show');
    let inId = $(this).data('inid'); // Get the ID from data attribute
    let materialName = $(this).data('materialname');
    $("#material-src").val(inId);
    $('.material-name-span').text(materialName);
    let mikos = $(this).data("mikos");
    $("label[for='out_mikos']").text("Length (max: " + mikos + ")");
    $('#validate-mikos-max').val(mikos);
    let platos = $(this).data("platos");
    $("label[for='out_platos']").text("Width (max: " + platos + ")");
    $('#validate-platos-max').val(platos);
  });
  
  //show date input
  $(".toggle_date").click(function() {
    $(this).next(".date_use").toggle();
  });

  /*/if user needs to add new client 
  $("#client_s").change(function() {
    var selectValue = $(this).val();
    if (selectValue === "new") {
        $("#new_client_input").removeClass("d-none");
        $("#new_project_input").removeClass("d-none");
        $("#new_client_input").attr('required', 'required');
        $("#new_project_input").attr('required', 'required');
    } else {
      //get option value and split '-'
      var parts = selectValue.split('-');
        if (parts[1] != '0'){
          $("#new_project_input").addClass("d-none");
          $("#new_project_input").removeAttr('required');
        }else{
          $("#new_project_input").removeClass("d-none");
          $("#new_project_input").attr('required', 'required');
        }
        $("#new_client_input").addClass("d-none");
        $("#new_client_input").removeAttr('required');
    }
  });*/

  // When client changes, load his projects
  $("#client_select").change(function() {
    let clientID = $(this).val();

    // Reset project area
    $("#project_select").addClass("d-none");
    $("#project_label").addClass("d-none");
    $("#new_project_input").addClass("d-none").val("").removeAttr("required");

    // If NEW CLIENT selected
    if (clientID === "new") {
        $("#new_client_input").removeClass("d-none").attr("required", "required");

        // Show new project input too
        $("#new_project_input").removeClass("d-none").attr("required", "required");

        return;
    }

    // Hide New client input
    $("#new_client_input").addClass("d-none").removeAttr("required");

    // Load projects via AJAX
    $.ajax({
        url: loadClientProject.ajaxurl,
        type: "POST",
        data: {
            action: "get_projects_by_client",
            client_id: clientID
        },
        success: function(response) {

            // Show project dropdown
            $("#project_select").removeClass("d-none");
            $("#project_label").removeClass("d-none");

            // Clear old options
            $("#project_select").html(`
                <option value="">Select project</option>
                <option value="new">+ New project</option>
            `);

            // Append returned projects
            if (response.length > 0) {
                response.forEach(function(project) {
                    $("#project_select").append(`
                        <option value="${project.id}">${project.description}</option>
                    `);
                });
            }
        }
    });
  });

    // Handle project selection
    $("#project_select").change(function() {
        let val = $(this).val();

        if (val === "new") {
            $("#new_project_input").removeClass("d-none").attr("required", "required");
        } else {
            $("#new_project_input").addClass("d-none").val("").removeAttr("required");
        }
    });

  //When the Edit (for out/cut) button is clicked
  $('.edit-out').on('click', function() {
      // Get data from the row
      var row = $(this).closest('tr');
      //var clientId = row.find('td:eq(5)').text() == '--' ? 0 : row.find('td:eq(5)').data('clientid'); 
      //var projectId = row.find('td:eq(5)').text() == '--' ? 0 : row.find('td:eq(5)').data('projectid'); 
      var clientId = row.find('td.out-td-lead').data('clientid');
      var projectId = row.find('td.out-td-lead').data('projectid');
      var notes = row.find('td.out-td-lead').data('note');
      var note_color = row.find('td.out-td-lead').data('note-color');
      var stockOutId = row.find('td.out-td-lead').data('stockoutid');
      
      $('#stock_out_id_edit').val(stockOutId);
      (clientId > 0)? $('#client_project').val(clientId + '-' + projectId) : $('#client_project').val(0);
      $('#notes_edit').val(notes);
      $('#note_color_out').val(note_color);

      // Show the modal
      $('#editStockOutModal').modal('show');
  });

    //When the return-piece (for out/cut) button is clicked
    $('.return-piece').on('click', function() {
      // Get data from the row
      let row = $(this).closest('tr');
      let length = row.find('td:first').data('l');
      let width = row.find('td:first').data('w');
      let stockOutId = row.find('td:first').data('stockoutid');

      //set maxes to check before submit
      $('#max_l').val(length); 
      $('#max_w').val(width);      
      $('#stock_out_id_return').val(stockOutId);
      // Set the placeholder value using jQuery
      $('#r_length').attr('placeholder', 'Return length (max: ' + length + ')');
      $('#r_width').attr('placeholder', 'Return width (max: ' + width + ')');
      
      // Show the modal
      $('#returnPieceModal').modal('show');
  });

  //validate not exceeding max mikos platos on cuts
  $('#useStockForm').on('submit', function(event) {
    var outMikos = parseFloat($('#out_mikos').val());
    var outPlatos = parseFloat($('#out_platos').val());
    var maxMikos = parseFloat($('#validate-mikos-max').val());
    var maxPlatos = parseFloat($('#validate-platos-max').val());

    if (outMikos > maxMikos || outPlatos > maxPlatos) {
        alert("The length/width you set cannot be greater than the max available");
        event.preventDefault(); // Prevent the form from submitting
    }
  });

  //validate return piece before submit
  $('#returnPieceForm').on('submit', function(event) {
    var max_l = parseFloat($('#max_l').val());
    var max_w = parseFloat($('#max_w').val());
    var r_length = parseFloat($('#r_length').val());
    var r_width = parseFloat($('#r_width').val());
    
    if (r_width > max_w || r_length > max_l) {
        alert("Cannot exceed maximum values");
        event.preventDefault();
    } 
  });

  //select type on change action
  $('#type_filter').change(function() {
    $('#select_type_form').submit();
  });
  $('#indate_filter').on('change', function() {
    var selectedDate = $(this).val();
    
    // Filter rows based on the selected date
    $('#stock-in-table tbody tr').each(function() {
        var rowDate = $(this).find('.indate').text();
        
        if (selectedDate === 'all' || rowDate === selectedDate) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    //then sum-up the visible to get total area
    let sum = 0;
    $('.this-in-area:visible').each(function () {
      let value = parseFloat($(this).text()); // Convert the text inside <td> to float
      if (!isNaN(value)) { // Check if the value is a valid number
          sum += value;
      }
    });
    $('.in-total-th').text(sum.toFixed(2));
  });

// ===============    end of stock scripts ===

/** =========================== PROJECTS ==================================================================== */
  /* new approached used, opening directly from href del after check
  $(".setup-proj-invoice").on("click", function(event) {
        event.preventDefault();
        //alert('clicked');
        new bootstrap.Modal($("#invoiceSettingsModal")).show();
    });*/

  $('#project-search').on('input', function() {
    var searchTerm = $(this).val().toLowerCase(); // Get the search term and convert to lowercase

    // Filter the project rows based on the search term
    $('#data-list .project-row').each(function() {
        var clientName = $(this).find('.col-md-3').text().toLowerCase(); // Client name column
        var projectDescription = $(this).find('.col-md-5').text().toLowerCase(); // Project description column

        // Check if the search term is present in either clientName or projectDescription
        if (clientName.includes(searchTerm) || projectDescription.includes(searchTerm)) {
            $(this).show(); // Show matching rows
        } else {
            $(this).hide(); // Hide non-matching rows
        }
    });
  });

  $('#filter-status').on('change', function() {
    $('#status-filter-form').submit(); // Submit the form on status change
  });

  $("#add-new-project-btn").click(function(){
    $("#add-project-block").toggle();
  });

  //if user needs to add new client 
  $("#client_select").change(function() {
    var selectValue = $(this).val();
    if (selectValue === "new") {
        $("#new_client_input").removeClass("d-none");
    } else {
        $("#new_client_input").addClass("d-none");
    }
  });

  //add items lines              
  // Store a reference to the original .add-mat-template
  const template = $(".add-mat-template").clone(true).removeClass("d-none");
  const template_other = $(".add-other-template").clone(true).removeClass("d-none");
  const template_other_list = $(".add-other-list-template").clone(true).removeClass("d-none");
  $(".add-mat-template").remove();
  $(".add-other-template").remove();
  $(".add-other-list-template").remove();
  const template_cost = $(".add-mat-template-cost").clone(true).removeClass("d-none");
  const template_other_cost = $(".add-other-template-cost").clone(true).removeClass("d-none");
  const template_other_list_cost = $(".add-other-list-template-cost").clone(true).removeClass("d-none");
  $(".add-mat-template-cost").remove();
  $(".add-other-template-cost").remove();
  $(".add-other-list-template-cost").remove();
  

  // Handle the Add Material Line button click
  $("#add-mat-line").on("click", function () {
      if ($(".add-mat-template:visible").length === 0) {
          // First click: display the initial template
          $("#items-container").append(template.clone());
      } else {
          // Subsequent clicks: clone and append
          $(".add-mat-template:visible").last().after(template.clone());
      }
      resetCalc();
  });

  /*
  $("#add-mat-line-cost").on("click", function () {
    if ($(".add-mat-template-cost:visible").length === 0) {
        // First click: display the initial template
        $("#items-container-cost").append(template_cost.clone());
    } else {
        // Subsequent clicks: clone and append
        $(".add-mat-template-cost:visible").last().after(template_cost.clone());
    }
  }); */

  // Handle the Add Material Line button click
  $("#add-other-list-line").on("click", function () {
    if ($(".add-other-list-template:visible").length === 0) {
        // First click: display the initial template
        $("#items-container").append(template_other_list.clone());
    } else {
        // Subsequent clicks: clone and append
        $(".add-other-list-template:visible").last().after(template_other_list.clone());
    }
    resetCalc();
  });

    // Handle the Add Material Line button click
    $("#add-other-list-line-cost").on("click", function () {
      if ($(".add-other-list-template-cost:visible").length === 0) {
          // First click: display the initial template
          $("#items-container-cost").append(template_other_list_cost.clone());
      } else {
          // Subsequent clicks: clone and append
          $(".add-other-list-template-cost:visible").last().after(template_other_list_cost.clone());
      }
    });

  $("#add-other-line").on("click", function () {
    if ($(".add-other-template:visible").length === 0) {
      // First click: display the initial template
      $("#items-container").append(template_other.clone());
    } else {
        // Subsequent clicks: clone and append
        $(".add-other-template:visible").last().after(template_other.clone());
    }
    resetCalc();
  });

  $("#add-other-line-cost").on("click", function () {
    if ($(".add-other-template-cost:visible").length === 0) {
      // First click: display the initial template
      $("#items-container-cost").append(template_other_cost.clone());
    } else {
        // Subsequent clicks: clone and append
        $(".add-other-template-cost:visible").last().after(template_other_cost.clone());
    }
  });
  // ========= end of materials and others lines adds

  //Show hide add costs
  $('#add-cost-btn').on('click', function() { 
    $('.buttons-row-costs').toggleClass('d-none'); 
    $('.items-container1').toggleClass('d-none'); 
  });

  $(document).on('click', '.recalc', function() {
    $(this).closest('.dynamic-line').remove();
    resetCalc();
  });  

function resetCalc(){
  if ($('#calculations').is(':visible')) {
    //reset the vat default option
    $('#calculations #radio-vat1').prop('checked', true);
    $('#calculations').addClass('d-none');
  }
}
  

  var total1_initial = 0; //to reset it in case user move from incl vat to plus vat
  // ======================= validator for calc totals ============================
  $('#calc-pr-prices').on('click', function() {
    let total = 0;
    let hasPrice = false;
    let isValid = false;

    // Sum all .show-price values
    $('#materials-outer .show-price').each(function() {
      let price = parseFloat($(this).val());
      hasPrice = true;
      total += price;
      if (isNaN(price) || price <= 0 || $(this).val().trim() === '') {
        hasPrice = false;
        return false; // Break out of the loop early
      }
    });

    // Check if any price was set
    if (!hasPrice) {
        alert("You didn't add any material, or you didn't price them");
        return false;
    }

    // Check material type selects
    $('#materials-outer .mat-type-checker').each(function() {
        if ($(this).val() == 0 || $(this).val() === "") {
            alert("You must select material type");
            $(this).focus();
            isValid = true;
            return false; // break loop
        }
    });

    // Check custom material name inputs
    if (!isValid) {
        $('#materials-outer .mat-description').each(function() {
            if ($(this).val().trim() === "") {
                alert("You must add a material description");
                $(this).focus();
                isValid = true;
                return false; // break loop
            }
        });
    }

    // Final check: total must be > 0
    if (total <= 0) {
        alert("You did not add any materials");
        isValid = true;
        if ($('#calculations').is(':visible')) {
            $('#calculations').addClass('d-none');
        }
    }

    if (isValid) {
        return false;
    }

    // Show calculations section
    $('#calculations').removeClass('d-none'); 
    total1_initial = total;

    // Update totals
    $('#total-before-vat').val(total.toFixed(2));
    let vat = parseFloat($('#vat').val()) / 100 || 0;
    let vat_price = total * vat;
    let total_after_vat = total + vat_price;
    $('#vat-price').val(vat_price.toFixed(2));
    $('#total-after-vat').val(total_after_vat.toFixed(2));
  });

  var total1_initial_q = 0; //to reset it in case user move from incl vat to plus vat
  // ======================= validator for calc totals ============================
  // This is shared with invoices code (so you can change the name or move it...)
  $('#calc-q-prices').on('click', function() {
    let total = 0;
    let total_empty_checker = 1;
    let isValid = 0;
    // Find all .show-price inputs and sum their values
    $('.show-price').each(function() {
      let price = parseFloat($(this).val()) || 0;  // Get the value and handle empty or invalid values
      total += price;
      total_empty_checker *= price; //if any line price is zero checker becomes zero
    });
    
    //checker - price set
    if (total_empty_checker == 0 ){
      alert ("You didn't price all item lines");
      return false;
    }
    //checker - type selected
    $('.item-descr').each(function() {
      if ($(this).val() == 0 ){
        alert ("You must select a description");
        $(this).focus();
        isValid = 1;
        return false;
      }
    });

    if (isValid == 1){
      if ($('#calculations').is(':visible')) { //in case user first proceeds and then 
        $('#calculations').addClass('d-none');
      }
      return false;
    }
    //show next part
    $('#calculations').removeClass('d-none'); 
    total1_initial = total;
    //alert(total);
    // Display the result inside #total-before-vat
    //console.log('total from items is ' + total);
    $('#total-before-vat').val(total.toFixed(2));  // Set the total, formatted to 2 decimal places
    let vat = parseFloat($('#vat').val()) / 100;
    let vat_price = total * vat;
    let total_after_vat = total + vat_price;
    $('#vat-price').val(vat_price.toFixed(2)); 
    $('#total-after-vat').val(total_after_vat.toFixed(2)); 
  });//end of new validation for calculations for quotes

  // ======================= validator for costs ================================
  $('#costs-form').on('submit', function(event) {
    let isValid = true;
    let local_total = 0;
    let total_empty_checker = 1;

    $('#items-container-cost .show-price').each(function() {
        let price = parseFloat($(this).val()) || 0;  
        local_total += price;
        total_empty_checker *= price; // if any line price is zero checker becomes zero
    });

    // checker - price set
    if (total_empty_checker == 0) {
        alert("You didn't add any material, or you didn't price them");
        return false; // Prevent form submission
    }

    $('#items-container-cost .mat-type-checker').each(function() {
        if ($(this).val() == 0) {
            alert("You must select material type");
            $(this).focus();
            isValid = false;
            return false; // Exit loop and prevent form submission
        }
    });

    if ($('#items-container-cost .show-price').length == 0) {
        alert("You did not add any materials");
        return false; // Prevent form submission
    }

    return isValid; // If valid, form will submit naturally
  });

  
  // ================ complete project ==============================================
  $('button[name="complete_project"]').click(function(event) {
      var remainingAmount = parseFloat($('#project-remaining').text().replace(/,/g, '')); // Get and parse the value of #project-remaining
      if (remainingAmount > 0) {
          var confirmMessage = "The project has still not paid balance. Are you sure that you want to mark it as completed?";
          if (!confirm(confirmMessage)) {
              event.preventDefault(); // Prevent form submission if the user cancels
          }
      }
  });

  $('#add-payment-btn').on('click', function() {
     $('#add-payment-form').toggleClass('d-none');
  });

  $('#vat').on('change', function() {
    doCalculations();
  });

  $('input[type=radio][name=plus-inc-vat]').change(function() {
        doCalculations();
  });
  
  $(document).on( "keyup", "#discount_val" , function() {
    doCalculations();
  });

  var discount_pr = 0;
  $("#add-discount").click(function() {
    $("#discount_val").val(0); // Reset value every time the user toggles the discount option
    discount_pr = 0;
    $("#discount-cont").toggle(); // Toggle the discount container
    
    // Check the display state and set button text accordingly
    if ($("#discount-cont").is(":visible")) {
        $(this).text("Remove Discount");
    } else {
        $(this).text("Add Discount");
    }
    doCalculations();
  });

  function doCalculations() {
    let total1 = parseFloat(total1_initial); // Ensure initial total is a number
    let vat = parseFloat($('#vat').val()); // Parse VAT as a number
    let discount = 0;

    if ($("#discount-cont").is(":visible") && parseFloat($("#discount_val").val()) > 0) { 
      discount = parseFloat($("#discount_val").val()); // Parse discount as a number
    }

    if ($('#radio-vat1').is(':checked')) {
      //console.log('passed vat1');
        let price_vat = (total1 * (vat / 100)).toFixed(2); // VAT amount
        let price_after_vat = (Number(price_vat) + total1 - discount).toFixed(2); // Final price after VAT and discount

        // Adjust calculations if discount > 0
        if (discount > 0) {
            total1 = (price_after_vat / (1 + (vat / 100))).toFixed(2); // Recalculate base price
            price_vat = (total1 * (vat / 100)).toFixed(2); // Recalculate VAT
        }

        // Update fields
        $('#total-before-vat').val(total1);
        $('#vat-price').val(price_vat);
        $('#total-after-vat').val(price_after_vat);
    } else if ($('#radio-vat2').is(':checked')) {
      
        let total2 = parseFloat(total1_initial); // Final total provided
        let new_total1 = (total2 / (1 + (vat / 100))).toFixed(2); // Base price before VAT
        let vat2 = (total2 - new_total1).toFixed(2); // VAT amount
        //console.log('passed vat2 and after vat price is ' + total2);
        // Update fields
        $('#total-before-vat').val(new_total1);
        $('#vat-price').val(vat2);
        $('#total-after-vat').val(total2.toFixed(2));
    }

    // Display discount note if applicable
    if (discount > 0) {
        $('#disc_note').text('(€' + discount + ' discount applied)');
    }
  }

  $('.editDescriptionLink').on('click', function (e) {
    e.preventDefault();
    let descr = $(this).data('descr'); // Get description data
    let prId = $(this).data('project-id'); // Get project ID
    let status = $(this).data('status'); // Get current status

    // Set initial values in modal fields
    $('#mod_description').val(descr); 
    $('#mod_project_id').val(prId); 

    // Handle the status dropdown
    const $modStatus = $('#mod_status');
    $modStatus.empty(); // Clear previous options

    if (status === "START") {
        // Disable or remove the dropdown if status is "START"
        $modStatus.prop('disabled', true).append('<option value="START" selected>You cannot modify status on START</option>');
    } else {
        // Enable the dropdown and populate it with options
        $modStatus.prop('disabled', false).append(`
            <option value="PROGRESS" ${status === 'PROGRESS' ? 'selected' : ''}>IN PROGRESS</option>
            <option value="COMPLETED" ${status === 'COMPLETED' ? 'selected' : ''}>COMPLETED</option>
            <option value="INCOMPLETE" ${status === 'INCOMPLETE' ? 'selected' : ''}>INCOMPLETE</option>
        `);
    }

    // Open the modal
    $('#editDescriptionModal').modal('show');
  });

  $('.create-proj-invoice').on('click', function() {
    let projectId = $(this).data('project-id');
    
    $.ajax({
      url: projInvAjax.ajaxurl,  // Use the localized ajaxurl
      type: 'POST',
      data: {
          action: 'fetch_data_for_print',
          this_id: projectId,
          source: 'project'
      },
      success: function(response) {
          console.log('AJAX Success: ', response);
          if (response.success) {
              openPrintableForm(response.data, 'project');
          } else {
              alert('Failed to fetch project inv data: ' + response.data);
          }  
        },
      error: function(xhr, status, error) {
          console.log('AJAX Error: ', xhr.responseText);
          alert('An error occurred while fetching project inv data.');
      }
    });
  });
  // ===============    end of projects scripts

  // =============== way bill triger ==============
  // Show/hide waybill options
  $('.show-waybill').click(function(e) {
    e.preventDefault();
    var clientId = $(this).data('client-id');
    $('#waybill-options-' + clientId).toggleClass('d-none');
  });

  // Handle waybill period selection
  $('.waybill-period').click(function(e) {
    e.preventDefault();
    var period = $(this).data('period');
    var clientId = $(this).data('client-id');
    
    if (period === 'custom') {
        $('#custom-period-' + clientId).removeClass('d-none');
        return;
    }
    
    $('#custom-period-' + clientId).addClass('d-none');
    generateWaybill(clientId, period);
  });

  // Apply custom period
  $('[id^="apply-custom-period-"]').click(function() {
    var clientId = $(this).attr('id').replace('apply-custom-period-', '');
    var startDate = $('#start-date-' + clientId).val();
    var endDate = $('#end-date-' + clientId).val();
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    generateWaybill(clientId, 'custom', startDate, endDate);
  });

  function generateWaybill(clientId, period, startDate = null, endDate = null) {
    $.ajax({
        url: waybillAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'generate_client_waybill',
            client_id: clientId,
            period: period,
            start_date: startDate,
            end_date: endDate
        },
        success: function(response) {
            if (response.success) {
                openWaybillPrintForm(response.data);
            } else {
                alert(response.data);
            }
        },
        error: function() {
            alert('Error generating waybill');
        }
    });
  }

  // =============== TASKS ============================
  // When material/tool checkbox changes: show/hide single notes input
  $('.material-checkbox, .tool-checkbox').on('change', function () {
    const parentRow = $(this).closest('.row');
    const notesContainer = parentRow.find('.notes-input-container');
    const notesInput = parentRow.find('.notes-input');

    if ($(this).is(':checked')) {
        notesContainer.removeClass('d-none');
    } else {
        notesContainer.addClass('d-none');
        notesInput.val(''); // Clear note input
    }
  });

  $('#createTaskBtn').on('click', function () {
      $('#create-task-box').toggleClass('d-none');
  });

  /*/ new client/project toggles (unchanged)
  $(".client_s").change(function() {
      var selectValue = $(this).val();
      if (selectValue === "new") {
          $(".new_client_input").removeClass("d-none");
          $(".new_project_input").removeClass("d-none");
          $(".new_client_input").attr('required', 'required');
          $(".new_project_input").attr('required', 'required');
      } else {
          var parts = selectValue.split('-');
          if (parts[1] != '0'){
              $(".new_project_input").addClass("d-none");
              $(".new_project_input").removeAttr('required');
          }else{
              $(".new_project_input").removeClass("d-none");
              $(".new_project_input").attr('required', 'required');
          }
          $(".new_client_input").addClass("d-none");
          $(".new_client_input").removeAttr('required');
      }
  }); */

  // Function to clone and add a new material row (now includes notes input)
  $(document).on('click', '.btn-add-row', function() {
    let row = $(this).closest('.row');
    let newRow = row.clone();
    // clear values in inputs and uncheck checkboxes
    newRow.find('input').val('');
    newRow.find('select').val('0');
    newRow.find('.notes-input-container').addClass('d-none');
    newRow.insertAfter(row);
  });

  // Function to remove a row
  $(document).on('click', '.btn-remove-row', function() {
      $(this).closest('.row').remove();
  });

  //show the workers form part
  $('.updateTaskBtn').on('click', function () {
    var target = $(this).data('target'); // Get the data-target value
    $('#' + target).toggleClass('d-none'); // Toggle the form with the matching ID
  });

  $('#task_date').on('change', function() {
    var selectedDate = $(this).val();
    window.location.href = '?task_date=' + selectedDate;
  });

  if ($('#taskForm').length > 0) {

    // EDIT TASK: populate the form including new notes fields
    $(".edit-task-btn").on("click", function() {
        reset_task_create();
        let taskId = $(this).data("task-id");
        let description = $(this).data("description");
        let clientId = $(this).data("client-id");
        let projectId = $(this).data("project-id");
        let sortOrder = $(this).data("sort-order");
        let materials = $(this).data("materials"); // expected JSON array of {name,note}
        let others = $(this).data("others");       // expected array of {id,notes}
        let tools = $(this).data("tools");         // expected array of {id,notes}
        let location = $(this).data("map-location");
        let photos = $(this).data("photos");

        // Set values in the form
        $("#task_id").val(taskId);
        if (typeof tinymce !== 'undefined' && tinymce.get('description')) {
            tinymce.get('description').setContent(description);
        } else {
            $("#description").val(description);
        }
        $("#client_s").val(clientId+"-"+projectId).trigger("change");
        //$("#client_id").val(clientId).trigger("change");
        //$("#project_id").val(projectId).trigger("change");
        $("#map-location").val(location);
        $("#sort_order").val(sortOrder);

        let materialsContainer = $("#materials-line");

        // Populate materials (mat_type[] + mat_note[])
        if (Array.isArray(materials)) {
            $.each(materials, function(index, item) {
                let rowHtml = `
                    <div class="row my-2 align-items-center">
                        <div class="col-md-3">
                            <select name="mat_type[]" class="form-select mat-type-checker-task">
                                <option value="${item.name}" selected>${item.name}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="mat_note[]" class="form-control" value="${item.note || ''}" placeholder="Notes (optional)">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-light btn-remove-row">X</button>
                        </div>
                    </div>
                `;
                materialsContainer.before(rowHtml);
            });
        }

        // Populate others: check box, show notes input and set its value
        if (Array.isArray(others)) {
            $.each(others, function(index, other) {
                let checkbox = $('#other_' + other.id);
                if (checkbox.length) {
                    checkbox.prop('checked', true);
                    let row = checkbox.closest('.row');
                    row.find('.notes-input-container').removeClass('d-none');
                    row.find('.notes-input').val(other.notes || '');
                }
            });
        }

        // Populate tools: check box, show notes input and set its value
        if (Array.isArray(tools)) {
            $.each(tools, function(index, tool) {
                let checkbox = $('#tool_' + tool.id);
                if (checkbox.length) {
                    checkbox.prop('checked', true);
                    let row = checkbox.closest('.row');
                    row.find('.notes-input-container').removeClass('d-none');
                    row.find('.notes-input').val(tool.notes || '');
                }
            });
        }

        // Photos preview (unchanged but safe)
        if (photos && photos.length > 0) {
          let previewContainer = $("#photo-preview");
          previewContainer.html("");
          photos.forEach((photoURL) => {
              let photoHTML = `
                  <div class="photo-item d-inline-block m-1" data-photo-url="${encodeURIComponent(photoURL)}">
                      <img src="${photoURL}" class="img-thumbnail" width="100">
                      <input type="checkbox" name="delete_photos[]" value="${photoURL}" class="form-check-input ms-2"> Delete
                  </div>
              `;
              previewContainer.append(photoHTML);
          });
        }

        // Change button text and action
        $('#action_type').val('update_task');
        $(".save-update-task").text("Apply changes");

        // Show form (if hidden)
        $("#create-task-box").removeClass("d-none");

        $('html, body').animate({
          scrollTop: $('#create-task-box').offset().top
        }, 800);
    });

    // deleting photos handler (unchanged)
    $(document).on("change", "input[name='delete_photos[]']", function() {
      let deletedPhotos = [];
      $("input[name='delete_photos[]']:checked").each(function() {
          deletedPhotos.push($(this).val());
      });
      $("#deleted-photos").val(JSON.stringify(deletedPhotos));
    });

    $('#completion_status').on('change', function () {
      if ($(this).is(':checked')) {
          $('#completion-status-label').text('Completed'); 
      } else {
          $('#completion-status-label').text('Not Completed'); 
      }
    });

    const initialSubmitButtonText = $('.save-update-task').text();
    const initialActionTypeValue = $('#action_type').val();

    // Reset button click event
    $('#reset-form').on('click', function() {
        reset_task_create();
    });

    // Reset helper (updated to clear notes inputs)
    function reset_task_create(){
      $('#taskForm')[0].reset();
      if (typeof tinymce !== 'undefined' && tinymce.get('description')) {
          tinymce.get('description').setContent('');
      }
      $('.save-update-task').text(initialSubmitButtonText);
      $('#action_type').val(initialActionTypeValue);
      $('#materials-line').siblings('.row').remove();
      $('#materials-line').find('input,select').val('');
      $('#photo-preview').empty();
      $('#deleted-photos').val('');
      $('.material-checkbox, .tool-checkbox').prop('checked', false);
      $('.notes-input-container').addClass('d-none');
      $('.notes-input').val('');
    }
  } // end if #taskForm
// =============== end tasks ========================

/** Make dynamic lines sortable */
jQuery(function ($) {
  $("#items-container").sortable({
    items: ".dynamic-line",
    handle: ".drag-handle",
    axis: "y"
  });
});

});//end document ready