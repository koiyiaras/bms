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
        <button type="button" class="btn-close position-absolute top-0 end-0" aria-label="Close" style="font-size: 0.55rem;margin-right:1em;"></button>
        <div class="col-md-7">
            <label for="item-descr" class="form-label">Product</label>
            <textarea name="item_line[]" id="item-descr" class="form-control item-descr" required></textarea>
        </div>
        <div class="col-md-1">
            <label for="item-quantity" class="form-label">#</label>
            <input type="text" name="item-quantity[]" value=1 class="form-control item-quantity only-num" required>
        </div>
        <div class="col-md-2">
            <label for="unit-price_1" class="form-label">Unit price</label>
            <input type="text" name="unit-price[]" id="unit-price_1" value=0 class="form-control unit-price only-num" required>
        </div>
        <div class="col-md-2">
            <label for="price_1" class="form-label">Price (&euro;)</label>
            <input type="text" name="price[]" id="price_1" value=0 readonly class="form-control show-price" required>
        </div>
    </div>
          `;

  $(document).on('click', '.btn-close', function() {
    if (!$(this).hasClass('recalc')) {
      $(this).closest('.dynamic-line').remove();
    }
  });

  $('#add-item-line').on('click', function() {
    //let lineCounter = 0;
    //fix the numbering where are already more than one items (on edid ect)
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

  $(document).on( "change", "#vat" , function() {
    vat = Number($(this).val());
    calc_final_ater_vat();
  });

  $(document).on( "keyup", "#discount_val" , function() {
    discount = Number($(this).val());
    calc_final_ater_vat();
  });

  $('input[type=radio][name=plus-inc-vat]').change(function() {
    vat_checker = $('input[name="plus-inc-vat"]:checked').val();
    //if, means that before it was the oposite state
    if (vat_checker == "1"){ //before it was 2
      price_before = price_after;
      price_after = Math.round((price_before + price_before * vat / 100) * 100) / 100;
    }
    if (vat_checker == "2"){ //before it was 1
      price_after = price_before;
      price_before = Math.round((price_before / (1 + vat/100)) * 100) / 100;;
    }
    calc_final_ater_vat();
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
      calc_final_ater_vat();
  }

function calc_final_ater_vat(){
  price_after = price_before + (price_before * vat / 100);
  price_after = Math.round(price_after * 100) / 100;
  price_vat = price_after - price_before;
  discounted_price = price_after;
  
  if (discount > 0){
    discounted_price = price_after - discount;
    discounted_price = Math.round(discounted_price * 100) / 100;
    price_vat = (discounted_price * vat) / (100 + vat); 
  }
  price_vat = Math.round(price_vat * 100) / 100;

  $("#total-after-vat").html(price_after);
  $("#total-before-vat").html(price_before);
  $("#vat-price").html(price_vat);
  $("#total-after-discount").html(discounted_price);
}

$("#add-discount").click(function(){
  $("#discount_val").val(0);//reset value every time the user toggles the discount option
  discount = 0;
  $("#discount-cont").toggle();
  calc_final_ater_vat();
});

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
            openPrintableForm(response.data);
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

function update_or_save_new(data, request_type, new_quote_no = 0){
    let main=data.results;
    let quote_id = (new_quote_no == 0)? main.quote_no : new_quote_no;
    
    $('#add-quote-block').show();
    $('#quote_no').val(quote_id);
    $('html, body').animate({ scrollTop: 0 }, 'slow');
    let dateValue = main.creation_date;
    let dateValue2 = main.valid_until;
    $('#date').val(dateValue);
    $('#valid_until').val(dateValue2);
    let client = main.client_id;
    $("#client_id option").each(function() {
      if ($(this).val() == client) {
        $(this).prop("selected", true);
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
    $('input[name="save-type"]').val(request_type);
    $('#quote_no').prop("readonly", true);
    (new_quote_no == 0)? $('#bms_add_quote').attr('value', 'Save changes') : $('#bms_add_quote').attr('value', 'Save as new');

    /* Items*/
    $.each(data.items, function(index, item) {
      if (index==0) {
        $('#line0').find('.item-descr').val(item.description);
        $('#line0').find('.item-quantity').val(item.quantity);
        $('#line0').find('.unit-price').val(item.unit_price);
        $('#line0').find('.show-price').val(item.price);
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
    let invoice_id = (new_invoice_no == 0)? main.invoice_no : new_invoice_no;

    // empty the items that will repopulate
    $("#items-container").empty();
    
    $('#add-invoice-block').show();
    $('#invoice_no').val(invoice_id);
    $('html, body').animate({ scrollTop: 0 }, 'slow');
    $('#date').val(main.creation_date);
    let client = main.client_id;
    $("#client_id option").each(function() {
      if ($(this).val() == client) {
        $(this).prop("selected", true);
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
    $('input[name="save-type"]').val(request_type);
    $('#invoice_no').prop("readonly", true);
    (new_invoice_no == 0)? $('#bms_add_invoice').attr('value', 'Save changes') : $('#bms_add_invoice').attr('value', 'Save as new');

    /* Items
       Remove the items content and regenerate it
    */
    $("#items-container").empty();
    $.each(data.items, function(index, item) {
      $("#items-container").append(`
          <div class="row mb-3 dynamic-line position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0" aria-label="Close" style="font-size: 0.55rem;margin-right:1em;"></button>
            <div class="col-md-7">
              <label for="item-descr" class="form-label">Product</label>
              <textarea name="item_line[]" id="item-descr" class="form-control item-descr" required />${item.description}</textarea>
            </div>
            <div class="col-md-1">
              <label for="item-quantity" class="form-label">#</label>
              <input type="text" name="item-quantity[]" value=${item.quantity} class="form-control item-quantity only-num" required />
            </div>
            <div class="col-md-2">
              <label for="unit-price_1" class="form-label">Unit price</label>
              <input type="text" name="unit-price[]" id="unit-price_1" value=${item.unit_price} class="form-control unit-price only-num" required />
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
  const textVals = {
      'date': 'Date',
      'pelatis': 'Client',
      'perigrafi': 'Description',
      'monadas': 'Unit price',
      'posotita': 'Quantity',
      'timi': 'Price',
      'synolo_prin': 'Total (Before VAT)',
      'fpa': 'VAT',
      'syn': 'plus VAT',
      'symp': 'VAT incl.',
      'synolo_meta1': 'Total after VAT',
      'ekptosi': 'Discount',
      'synolo_meta2': 'Total after discount',
      'trapeza': 'Bank details'
  };

  let no = invoiceData.no.toString().padStart(4, '0');

  let dateParts, formattedDate;
  if (source == 'project'){
    dateParts = invoiceData.creation_date.split('-');
    formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
  }else{
    formattedDate = invoiceData.creation_date;
  }

  // Set default values for to_include if it's null or undefined
  let to_include = invoiceData.include || 'our_address,client_address,bank_details,thanks_msg';

  // Initialize variables for dynamic content
  let bankDiv = '', thanksDiv = '', clientAddressDiv = '<br>', ourAddressDiv = '<br>';
 
  // Check if source is not 'project' or empty_checker is true
  if (source !== 'project' || !invoiceData.include) {
    // Generate content based on to_include
    if (to_include.includes('client_address')) {
      clientAddressDiv = invoiceData.client_address ? `<br>${invoiceData.client_address}<br>` : '<br>';
    }

    if (to_include.includes('thanks_msg')) {
      thanksDiv = `
        <div style="text-align:center;margin-top:2em;">
          ${invoiceData.company_thanks}
        </div>
      `;
    }

    if (to_include.includes('bank_details')) {
      bankDiv = `
        <div style="text-align:left;margin-top:2em;">
          <p><u>You can transfer your payment to the following account:</u></p>
          ${invoiceData.company_bank}
        </div>
      `;
    }

    if (to_include.includes('our_address')) {
      ourAddressDiv = '<br>' + invoiceData.company_address;
    }
  } else {
    to_include = JSON.parse(to_include);
    // Handle the case where source is 'project' and include is defined
    if (to_include.client_address && to_include.client_address.set === 'set') {
      var passed_from_s = 'passed else';
      clientAddressDiv = `<br>${to_include.client_address.value}<br>`;
    }

    if (to_include.our_address && to_include.our_address.set === 'set') {
      ourAddressDiv = `<br>${to_include.our_address.value}<br>`;
    }

    if (to_include.thanks_message && to_include.thanks_message.set === 'set') {
      thanksDiv = `
        <div style="text-align:${to_include.thanks_message.position};margin-top:2em;">
            ${to_include.thanks_message.value}
        </div>
      `;
    }

    if (to_include.bank_details && to_include.bank_details.set === 'set') {
      bankDiv = `
        <div style="text-align:${to_include.bank_details.position};margin-top:2em;">
          <p><u>You can transfer your payment to the following account:</u></p>
          ${to_include.bank_details.value}
        </div>
      `;
    }
  }
  var win = window.open('', '_blank');

  // Construct the HTML string
  var html = `
      <html>
      <head>
          <title>Invoice #${invoiceData.no}</title>
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
                      <b>${invoiceData.company_name}</b>
                        ${ourAddressDiv}
                      <p>${invoiceData.company_phone}</p>
                      <p>${invoiceData.company_email}</p>
                  </div>
                  <div>
                      <span style="text-align:right;"># <b>${no}</b></span>
                      <p>${textVals.date}: ${formattedDate}<br></p>
                      <p><u>${textVals.pelatis}: </u><br>
                        ${invoiceData.client_name}<br>
                        ${clientAddressDiv}
                        ${invoiceData.client_phone ? `${invoiceData.client_phone}<br>` : ''}
                        ${invoiceData.client_email ? `${invoiceData.client_email}<br>` : ''}
                      </p>
                  </div>
              </div>
              <h2>${invoiceData.product_description}</h2>
              <table>
                  <thead>
                      <tr>
                          <th>${textVals.perigrafi}</th>
                          <th>${textVals.monadas}</th>
                          <th>${textVals.posotita}</th>
                          <th>${textVals.timi}</th>
                      </tr>
                  </thead>
                  <tbody>`;

  invoiceData.items.forEach(function(item) {
      html += `
                      <tr>
                          <td>${source !== 'project' ? item.description : item.type}</td>
                          <td>${item.unit_price}</td>
                          <td>${item.quantity}</td>
                          <td>${item.price}</td>
                      </tr>`;
  });

  html += `<tr><td colspan="3"><strong>${textVals.synolo_prin}:</strong></td><td>${invoiceData.total_before_vat}</td></tr>
           <tr><td colspan="3">${textVals.fpa} (${invoiceData.vat}%) ${invoiceData.plus_inc_vat == 1 ? textVals.syn : textVals.symp}:</td><td> ${invoiceData.vat_price}</td></tr>
           <tr><td colspan="3"><strong>${textVals.synolo_meta1}:</strong></td><td> <b>${invoiceData.total_after_vat}</b></td></tr>`;

  if (invoiceData.discount_val > 0) {
      html += `
          <tr><td colspan="3">${textVals.ekptosi}: ${invoiceData.discount_description}</td><td>${invoiceData.discount_val}</td></tr>
          <tr><td colspan="3"><b>${textVals.synolo_meta2}:</b></td><td>${invoiceData.total_after_discount}</td></tr>`;
  }

  html += `</tbody></table>
            ${thanksDiv}
            ${bankDiv}
          </div><!--End of Content -->
          <div>
              <button id="printPageButton" onClick="window.print();">🖨️ Print</button>
          </div>
      </body>
      </html>`;

  // Write the HTML to the new window
  win.document.write(html);
  win.document.close();
}

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

  //select related project
  $('#type-select').change(function(){
    var selectedValue = $(this).val();
    window.location.href = '/balances/?paytype=' + selectedValue;
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
  //prevent error with comma in decimal
  $(".decimal-input").on("input", function () {
      $(this).val($(this).val().replace(",", "."));
  });

  //filter outs by client
  $('#out_client_filter').on('change', function () {
      let selectedClientId = $(this).val(); // Get selected client ID

      // Show all rows if "All clients" is selected
      if (selectedClientId === 'all') {
          $('table tbody tr').show();
      } else {
          // Hide all rows and show only those matching the selected client_id
          $('table tbody tr').hide();
          $(`table tbody tr[data-client-id='${selectedClientId}']`).show();
      }
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

  //if user needs to add new client 
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
      var stockOutId = row.find('td.out-td-lead').data('stockoutid');
      
      $('#stock_out_id_edit').val(stockOutId);
      (clientId > 0)? $('#client_project').val(clientId + '-' + projectId) : $('#client_project').val(0);
      $('#notes_edit').val(notes);

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
  });

  $("#add-mat-line-cost").on("click", function () {
    if ($(".add-mat-template-cost:visible").length === 0) {
        // First click: display the initial template
        $("#items-container-cost").append(template_cost.clone());
    } else {
        // Subsequent clicks: clone and append
        $(".add-mat-template-cost:visible").last().after(template_cost.clone());
    }
  });

  // Handle the Add Material Line button click
  $("#add-other-list-line").on("click", function () {
    if ($(".add-other-list-template:visible").length === 0) {
        // First click: display the initial template
        $("#items-container").append(template_other_list.clone());
    } else {
        // Subsequent clicks: clone and append
        $(".add-other-list-template:visible").last().after(template_other_list.clone());
    }
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
    //var itemsContainer = $('#items-container2');
    //var rowCount = (itemsContainer.find('.row').length) ; //subtract the to be deleted line
    $(this).closest('.dynamic-line').remove();
    if ($('#calculations').is(':visible')) {
      $('#calculations').addClass('d-none');
    }
  });  
  

  var total1_initial = 0; //to reset it in case user move from incl vat to plus vat
  // ======================= validator for calc totals ============================
  $('#calc-pr-prices').on('click', function() {
    let total = 0;
    let total_empty_checker = 1;
    let isValid = 0;
    // Find all .show-price inputs and sum their values
    $('#materials-outer .show-price').each(function() {
      let price = parseFloat($(this).val()) || 0;  // Get the value and handle empty or invalid values
      total += price;
      total_empty_checker *= price; //if any line price is zero checker becomes zero
    });

    //checker - price set
    if (total_empty_checker == 0 ){
      alert ("You didn't add any material, or you didn't price them");
      return false;
    }
    //checker - type selected
    $('#materials-outer .mat-type-checker').each(function() {
      if ($(this).val() == 0 ){
        alert ("You must select material type");
        $(this).focus();
        isValid = 1;
        return false;
      }
    });
    //if any nothing is added is empty
    if (total == 0){
      alert ("You did not add any materials");
      isValid = 1;
      if ($('#calculations').is(':visible')) { //in case user first proceeds and then 
        $('#calculations').addClass('d-none');
      }
    }

    if (isValid == 1){
      return false;
    }
    //show next part
    $('#calculations').removeClass('d-none'); 
    total1_initial = total;
    // Display the result inside #pr_total1
    $('#pr_total1').val(total.toFixed(2));  // Set the total, formatted to 2 decimal places
    let vat = parseFloat($('#pr-vat').val()) / 100;
    let vat_price = total * vat;
    let total_after_vat = total + vat_price;
    $('#pr_price_vat').val(vat_price.toFixed(2)); 
    $('#pr_total2').val(total_after_vat.toFixed(2)); 
  });

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

  $('#pr-vat').on('change', function() {
    doCalculations();
  });

  $('input[type=radio][name=plus-inc-vat]').change(function() {
        doCalculations();
  });
  
  $(document).on( "keyup", "#discount_val_pr" , function() {
    doCalculations();
  });

  var discount_pr = 0;
  $("#add-discount-pr").click(function() {
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
    let vat = parseFloat($('#pr-vat').val()); // Parse VAT as a number
    let discount = 0;

    if ($("#discount-cont").is(":visible") && parseFloat($("#discount_val_pr").val()) > 0) { 
      discount = parseFloat($("#discount_val_pr").val()); // Parse discount as a number
    }

    if ($('#pr-radio-vat1').is(':checked')) {
        let price_vat = (total1 * (vat / 100)).toFixed(2); // VAT amount
        let price_after_vat = (Number(price_vat) + total1 - discount).toFixed(2); // Final price after VAT and discount

        // Adjust calculations if discount > 0
        if (discount > 0) {
            total1 = (price_after_vat / (1 + (vat / 100))).toFixed(2); // Recalculate base price
            price_vat = (total1 * (vat / 100)).toFixed(2); // Recalculate VAT
        }

        // Update fields
        $('#pr_total1').val(total1);
        $('#pr_price_vat').val(price_vat);
        $('#pr_total2').val(price_after_vat);
    } else if ($('#pr-radio-vat2').is(':checked')) {
        let total2 = parseFloat(total1_initial); // Final total provided
        let new_total1 = (total2 / (1 + (vat / 100))).toFixed(2); // Base price before VAT
        let vat2 = (total2 - new_total1).toFixed(2); // VAT amount

        // Update fields
        $('#pr_total1').val(new_total1);
        $('#pr_price_vat').val(vat2);
        $('#pr_total2').val(total2.toFixed(2));
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

  // =============== TASKS ============================
  $('.material-checkbox, .tool-checkbox').on('change', function () {
    const parentRow = $(this).closest('.row');
    const quantityInputContainer = parentRow.find('.quantity-input-container');
    const outNoteContainer = parentRow.find('.out-note-container');
    const quantityInput = parentRow.find('.quantity-input');
    const noteInput = parentRow.find('input[name$="[notes]"]');

    if ($(this).is(':checked')) {
        quantityInputContainer.removeClass('d-none');
        outNoteContainer.removeClass('d-none');
        quantityInput.prop('required', true); // Ensure quantity is required
    } else {
        quantityInputContainer.addClass('d-none');
        outNoteContainer.addClass('d-none');
        quantityInput.prop('required', false);
        noteInput.val(''); // Clear note input
        quantityInput.val(''); // Clear quantity input
    }
  });

  $('#createTaskBtn').on('click', function () {
      $('#create-task-box').toggleClass('d-none');
  });

  //if user needs to add new client 
  $(".client_s").change(function() {
      var selectValue = $(this).val();
      if (selectValue === "new") {
          $(".new_client_input").removeClass("d-none");
          $(".new_project_input").removeClass("d-none");
          $(".new_client_input").attr('required', 'required');
          $(".new_project_input").attr('required', 'required');
      } else {
      //get option value and split '-'
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
    });

  // Function to clone and add a new row
  $('.btn-add-row').on('click', function() {
    let row = $(this).closest('.row');
    let newRow = row.clone();
    newRow.find('.btn-add-row').remove();
    newRow.find('input').val(''); // Clear input values
    newRow.find('select').val('0'); // Reset select value
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

  if ($('#taskForm').length > 0) { // i use this to create constants only when on task page
    //edit task 
    $(".edit-task-btn").on("click", function() {
        reset_task_create(); //first initialize the form so it is not add new fields everytime the pencils is clicked
        let taskId = $(this).data("task-id");
        let description = $(this).data("description");
        let clientId = $(this).data("client-id");
        let projectId = $(this).data("project-id");
        let sortOrder = $(this).data("sort-order");
        let materials = $(this).data("materials");//json parce not needed
        let others = $(this).data("others");
        let tools = $(this).data("tools");
        let location = $(this).data("map-location");
        let photos = $(this).data("photos");

        
        // Set values in the form
        $("#task_id").val(taskId);
        tinymce.get('description').setContent(description); // Set WP Editor content
        $("#client_id").val(clientId).trigger("change");
        $("#project_id").val(projectId).trigger("change");
        $("#map-location").val(location);
        $("#sort_order").val(sortOrder);

        let materialsContainer = $("#materials-line");

        // Loop through each material
        $.each(materials, function(index, item) {
            // Create the new material HTML
            let materialHTML = `
                <div class="row my-2 align-items-center">
                    <div class="col-md-3">
                        <select name="mat_type[]" class="form-select mat-type-checker-task">
                            <option value="${item.name}" selected>${item.name}</option> 
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="mat_dimensions[]" class="form-control" value="${item.dimensions}"> 
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="mat_quantity[]" class="form-control" value="${item.quantity}"> 
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-light btn-remove-row">X</button>
                    </div>
                </div>
            `;

            // Add the new content before the initial #materials-line
            materialsContainer.before(materialHTML);
        });

        $.each(others, function(index, other) {
          // Find the checkbox by ID
          let checkbox = $('#other_' + other.id);
          if (checkbox.length) {
              // Check the checkbox
              checkbox.prop('checked', true);

              // Find the parent row
              var row = checkbox.closest('.row');

              // Show the quantity and notes inputs
              row.find('.quantity-input-container, .out-note-container').removeClass('d-none');

              // Populate the quantity and notes fields
              row.find('.quantity-input').val(other.quantity);
              row.find('.out-note-container input').val(other.notes);
          }
        }); 

        $.each(tools, function(index, tool) {
          // Find the checkbox by ID
          let checkbox = $('#tool_' + tool.id);
          if (checkbox.length) {
              // Check the checkbox
              checkbox.prop('checked', true);

              // Find the parent row
              var row = checkbox.closest('.row');

              // Show the quantity and notes inputs
              row.find('.quantity-input-container, .out-note-container').removeClass('d-none');

              // Populate the quantity and notes fields
              row.find('.quantity-input').val(tool.quantity);
              row.find('.out-note-container input').val(tool.notes);
          }
        }); 
  
      // Handle Photos - Preview
      if (photos && photos.length > 0) {
        let previewContainer = $("#photo-preview");
        previewContainer.html(""); // Clear old previews

        photos.forEach((photoURL, index) => {
            let encodedURL = encodeURIComponent(photoURL); // Encode URL for safe storage in data attribute

            let photoHTML = `
                <div class="photo-item d-inline-block m-1" data-photo-url="${encodedURL}">
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

    //for deleting photos after modification
    $(document).on("change", "input[name='delete_photos[]']", function() {
      let deletedPhotos = [];

      $("input[name='delete_photos[]']:checked").each(function() {
          deletedPhotos.push($(this).val()); // Use the original URL (no need to decode here)
      });

      $("#deleted-photos").val(JSON.stringify(deletedPhotos));
    });

    $('#completion_status').on('change', function () {
      if ($(this).is(':checked')) {
          $('#completion-status-label').text('Completed'); // Update label to "Completed"
      } else {
          $('#completion-status-label').text('Not Completed'); // Update label to "Not Completed"
      }
    });

    //reset create/edit task form
     // Store the initial state of the form
    const initialFormState = $('#taskForm').serialize();
    const initialSubmitButtonText = $('.save-update-task').text();
    const initialActionTypeValue = $('#action_type').val();

    // Reset button click event
    $('#reset-form').on('click', function() {
        reset_task_create();
    });

    function reset_task_create(){
      // Reset the form fields
      $('#taskForm')[0].reset();

      // Reset the TinyMCE editor content (if used)
      if (typeof tinymce !== 'undefined' && tinymce.get('description')) {
          tinymce.get('description').setContent('');
      }

      // Reset the submit button text
      $('.save-update-task').text(initialSubmitButtonText);

      // Reset the hidden action_type value
      $('#action_type').val(initialActionTypeValue);

      // Reset any dynamically added material rows (if applicable)
      $('#materials-line').siblings('.row').remove(); // Remove additional material rows
      $('#materials-line').find('input').val(''); // Clear inputs in the first material row

      // Reset photo preview and deleted photos (if applicable)
      $('#photo-preview').empty();
      $('#deleted-photos').val('');

      // Reset checkboxes and their associated inputs (for tools and others)
      $('.material-checkbox, .tool-checkbox').prop('checked', false);
      $('.quantity-input-container, .out-note-container').addClass('d-none');
      $('.quantity-input, .out-note-container input').val('');

      // Optionally, reset any other custom fields or dynamic content
    }
  }

  $(".setup-proj-invoice").on("click", function(event) {
        event.preventDefault();
        new bootstrap.Modal($("#invoiceSettingsModal")).show();
    });
  // =============== end tasks ========================

});//end document ready