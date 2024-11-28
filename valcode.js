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
$(document).ready(function() {
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
  });

  /** sort clients alphbetically */
  $('#sort-name').on('click', function() {
    //console.log("passed sort fn");
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
$(document).ready(function() {
    setTimeout(function() {
      $('.alert-danger, .alert-success').fadeOut('slow');
    }, 4000); // 4 seconds
  });

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
  $(this).closest('.dynamic-line').remove();
  });

$('#add-item-line').on('click', function() {
  //let lineCounter = 0;
  //fix the numbering where are already more than one items (on edid ect)
  let lineCounter = $('#items-container .row').length;
  console.log("num of lines: "+lineCounter);
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
//trigger calc initially in case of load initila values (for create invoice from quote or other)
$(document).ready(function(){
  calc_final_before_vat ();
});

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
  $(document).ready(function(){
    price_before=0;
    $(".show-price").each(function() {
      price_before+= Number($(this).val());
    });
    price_before = Math.round(price_before * 100) / 100;
    calc_final_ater_vat();
  });
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
        action: 'fetch_quote_data',
        quote_id: quoteId
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
        console.log(data);
        let new_quote_no = data.quote_no;
        update_or_save_new(data, request_type, new_quote_no);
        $('#list-quotes').hide();
      }
    });
  });

function update_or_save_new(data, request_type, new_quote_no = 0){
    let main=data.results;
    let quote_id = (new_quote_no == 0)? main.quote_no : new_quote_no;
    //console.log('results: ', data.results);
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
function openPrintableForm(quoteData) {
  const textVals = {}; // Declare textVals outside the if-else block

  if (quoteData.lang == 'en'){
      Object.assign(textVals, {
          'date': 'Date',
          'valid_until': 'Valid until',
          'pelatis': 'Client',
          'perigrafi': 'Description',
          'monadas': 'Unit price',
          'posotita': 'Quantity', 
          'timi': 'Price',
          'synolo_prin': 'Total (Before VAT)', 
          'delivery_time': 'Delivery time',
          'fpa': 'VAT',
          'syn': 'plus VAT',
          'symp': 'VAT incl.',
          'synolo_meta1': 'Total after VAT',
          'ekptosi': 'Discount',
          'synolo_meta2': 'Total after discount',
          'ektimomenos': 'Expected delivery time',  
          'efxarist': 'Thanks for doing business with us'
      });
  } else {
      Object.assign(textVals, {
          'date': 'Ημερομηνία',
          'valid_until': 'Ισχύει μέχρι',
          'pelatis': 'Πελάτης',
          'perigrafi': 'Περιγραφή',
          'monadas': 'Τιμή μονάδας',
          'posotita': 'Ποσότητα', 
          'timi': 'Τιμή',
          'synolo_prin': 'Σύνολο (πριν το ΦΠΑ)', 
          'fpa': 'Φ.Π.Α.',
          'syn': 'συν ΦΠΑ',
          'symp': 'ΦΠΑ συμπεριλ.',
          'synolo_meta1': 'Σύνολο (μετά το ΦΠΑ)',
          'ekptosi': 'Εκπτωση',
          'synolo_meta2': 'Σύνολο (μετά την εκπτωση)',
          'ektimomenos': 'Εκτιμώμενος χρόνος ολοκλήρωσης',  
          'efxarist': 'Ευχαριστούμε για τη συνεργασία'
      });
  } // end of el texts
  

  var win = window.open('', '_blank');
  var html = `
      <html>
      <head>
          <title>Quote #${quoteData.quote_no}</title>
          <style>
              @media print {
                  #printPageButton {
                      display: none;
                  }
              }
              body { font-family: 'Roboto', Arial, sans-serif; background:#ccc; }
              .header, .footer { width:190mm; text-align: center; position: fixed; background: #fff; }
              .header { top: 0px; }
              .top {display:flex; justify-content: space-between;}
              .footer { bottom: 0px; width:210mm;background: #fff; }
              .content { margin: auto; padding:50px; background: #fff; width:98%;max-width:210mm; }
              table { width: 100%; border-collapse: collapse; }
              table, th, td { border: 1px solid black; }
              th, td { padding: 10px; text-align: left; }
          </style>
      </head>
      <body>
          <div class="content">
              <div class="top">
                  <div><!--Left top-->
                      <b>${quoteData.company_name}</b><br>
                      ${quoteData.company_address}
                      <p>${quoteData.company_phone}</p>
                      <p>${quoteData.company_email}</p>
                  </div>
                  <div><!--Right top-->
                      <span style="text-align:right;"># <b>${quoteData.quote_no}</b></span>
                      
                      
                      <p>
                        ${textVals.date}: ${quoteData.creation_date}<br>
                        ${textVals.valid_until}: ${quoteData.valid_until}<br>
                      </p>
                      <p>&nbsp;</p>
                      <p><u>${textVals.pelatis}: </u><br>
                       ${quoteData.client_name}<br>
                      ${quoteData.client_address}<br>
                      ${quoteData.client_phone}<br>
                      ${quoteData.client_email}<br></p>
                  </div>
              </div>
              <h2>${quoteData.product_description}</h2>
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
  
  quoteData.items.forEach(function(item) {
      html += `
                      <tr>
                          <td>${item.description}</td>
                          <td>${item.unit_price}</td>
                          <td>${item.quantity}</td>
                          <td>${item.price}</td>
                      </tr>`;
  });

  html += `       <tr><td colspan="3"><strong>${textVals.synolo_prin}:</strong></td><td>${quoteData.total_before_vat}</td></tr>
                  <tr><td colspan="3">${textVals.fpa} (${quoteData.vat}%) ${quoteData.plus_inc_vat == 1 ? textVals.syn : textVals.symp}:</td><td> ${quoteData.vat_price}</td></tr>
                  <tr><td colspan="3"><strong>${textVals.synolo_meta1}:</strong></td><td> <b>${quoteData.total_after_vat}</b></td></tr>
              `;
          
  
  if (quoteData.discount_val > 0) {
      html += `
          <tr><td colspan="3">${textVals.ekptosi}: ${quoteData.discount_description}</td><td>${quoteData.discount_val}</td></tr>
          <tr><td colspan="3"><b>${textVals.synolo_meta2}:</b></td><td>${quoteData.total_after_discount}</td></tr>
          `;
  }

  html += `
          </tbody>
          </table>
          <p><strong>${textVals.ektimomenos}:</strong> ${quoteData.delivery_time}</p>
          <p>&nbsp;</p>
          <p style="text-align:center">${textVals.efxarist}</p>
      </div>
      <div><button id="printPageButton" onClick="window.print();">Print</button> 
  </body>
  </html>`;

  win.document.write(html);
  win.document.close();
}

/** PDF ======================================================= */
  // Create PDF button click
  jQuery(document).ready(function($) {
    $('.create-pdf').on('click', function() {
        let quoteId = $(this).data('quote-id');
        
        // Generate the PDF
        $.ajax({
            url: pdfAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_quote_pdf',
                quote_id: quoteId
            },
            success: function(response) {
                // No need to use JSON.parse, as response is already parsed
                if (response.success) {
                    // Provide a download link or automatically download the PDF
                    window.open(response.data.pdf_url, '_blank');
                } else {
                    alert('Failed to create PDF.');
                }
            },
            error: function() {
                alert('Error occurred while creating PDF.');
            }
        });
    });
});

//pdf end

$('.email-quote').on('click', function() {
  var quoteId = $(this).data('quote-id');
  $.ajax({
      url: emailQuoteAjax.ajaxurl,
      type: 'POST',
      data: {
          action: 'send_quote_to_client',
          quote_id: quoteId
      },
      success: function(response) {
          var responseObj = JSON.parse(response);
          if (responseObj.success) {
              alert('Quote sent to client on: ' + responseObj.email);
          } else {
              alert('Failed to send quote: ' + responseObj.error);
          }
      }
  });
});

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
        action: 'fetch_invoice_data',
        invoice_id: invoiceId
    },
    success: function(response) {
        console.log('AJAX Success: ', response);
        if (response.success) {
            openPrintableFormInv(response.data);
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
        console.log(data);
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

function openPrintableFormInv(invoiceData) {
  const textVals = {}; // Declare textVals outside the if-else block

  if (invoiceData.lang == 'en'){
      Object.assign(textVals, {
          'date': 'Date',
          'pelatis': 'Client',
          'perigrafi': 'Description',
          'monadas': 'Unit price',
          'posotita': 'Quantity', 
          'timi': 'Price',
          'synolo_prin': 'Total (Before VAT)', 
          'delivery_time': 'Delivery time',
          'fpa': 'VAT',
          'syn': 'plus VAT',
          'symp': 'VAT incl.',
          'synolo_meta1': 'Total after VAT',
          'ekptosi': 'Discount',
          'synolo_meta2': 'Total after discount',
          'trapeza': 'Bank details',  
          'efxarist': 'Thanks for doing business with us'
      });
  } else {
      Object.assign(textVals, {
          'date': 'Ημερομηνία',
          'pelatis': 'Πελάτης',
          'perigrafi': 'Περιγραφή',
          'monadas': 'Τιμή μονάδας',
          'posotita': 'Ποσότητα', 
          'timi': 'Τιμή',
          'synolo_prin': 'Σύνολο (πριν το ΦΠΑ)', 
          'fpa': 'Φ.Π.Α.',
          'syn': 'συν ΦΠΑ',
          'symp': 'ΦΠΑ συμπεριλ.',
          'synolo_meta1': 'Σύνολο (μετά το ΦΠΑ)',
          'ekptosi': 'Εκπτωση',
          'synolo_meta2': 'Σύνολο (μετά την εκπτωση)',
          'trapeza': 'Τραπεζ. λογαριασμός',  
          'efxarist': 'Ευχαριστούμε για τη συνεργασία'
      });
  } // end of el texts
  
  var win = window.open('', '_blank');
  var html = `
      <html>
      <head>
          <title>Invoice #${invoiceData.invoice_no}</title>
          <style>
              @media print {
                  #printPageButton {
                      display: none;
                  }
              }
              body { font-family: 'Roboto', Arial, sans-serif; background:#ccc; }
              .header, .footer { width:190mm; text-align: center; position: fixed; background: #fff; }
              .header { top: 0px; }
              .top {display:flex; justify-content: space-between;}
              .footer { bottom: 0px; width:210mm;background: #fff; }
              .content { margin: auto; padding:50px; background: #fff; width:98%;max-width:210mm; }
              table { width: 100%; border-collapse: collapse; }
              table, th, td { border: 1px solid black; }
              th, td { padding: 10px; text-align: left; }
          </style>
      </head>
      <body>
          <div class="content">
              <div class="top">
                  <div><!--Left top-->
                      <b>${invoiceData.company_name}</b><br>
                      ${invoiceData.company_address}
                      <p>${invoiceData.company_phone}</p>
                      <p>${invoiceData.company_email}</p>
                  </div>
                  <div><!--Right top-->
                      <span style="text-align:right;"># <b>${invoiceData.invoice_no}</b></span>
                      
                      
                      <p>
                        ${textVals.date}: ${invoiceData.creation_date}<br>
                      </p>
                      <p>&nbsp;</p>
                      <p><u>${textVals.pelatis}: </u><br>
                       ${invoiceData.client_name}<br>
                      ${invoiceData.client_address}<br>
                      ${invoiceData.client_phone}<br>
                      ${invoiceData.client_email}<br></p>
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
                          <td>${item.description}</td>
                          <td>${item.unit_price}</td>
                          <td>${item.quantity}</td>
                          <td>${item.price}</td>
                      </tr>`;
  });

  html += `       <tr><td colspan="3"><strong>${textVals.synolo_prin}:</strong></td><td>${invoiceData.total_before_vat}</td></tr>
                  <tr><td colspan="3">${textVals.fpa} (${invoiceData.vat}%) ${invoiceData.plus_inc_vat == 1 ? textVals.syn : textVals.symp}:</td><td> ${invoiceData.vat_price}</td></tr>
                  <tr><td colspan="3"><strong>${textVals.synolo_meta1}:</strong></td><td> <b>${invoiceData.total_after_vat}</b></td></tr>
              `;
          
  
  if (invoiceData.discount_val > 0) {
      html += `
          <tr><td colspan="3">${textVals.ekptosi}: ${invoiceData.discount_description}</td><td>${invoiceData.discount_val}</td></tr>
          <tr><td colspan="3"><b>${textVals.synolo_meta2}:</b></td><td>${invoiceData.total_after_discount}</td></tr>
          `;
  }

  let include = invoiceData.include;
  console.log(include);
  html += `
          </tbody>
          </table>
          <p>&nbsp;</p>
          <p style="text-align:center"><strong>${textVals.trapeza}:</strong></p>
          ${include.indexOf('bank_details') !== -1 ? '<p style="text-align:center">' + invoiceData.company_bank + '</p>' : ''}
      </div>
      <div><button id="printPageButton" onClick="window.print();">Print</button> 
  </body>
  </html>`;

  win.document.write(html);
  win.document.close();
}

/** PDF ======================================================= */
  // Create PDF button click
  jQuery(document).ready(function($) {
    $('.create-pdf-inv').on('click', function() {
        let invoiceId = $(this).data('invoice-id');
        // Generate the PDF
        $.ajax({
            url: pdfInvAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_invoice_pdf',
                invoice_id: invoiceId
            },
            success: function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    // Provide a download link or automatically download the PDF
                    window.open(response.pdf_url, '_blank');
                } else {
                    alert('Failed to create PDF.');
                }
            },
            error: function() {
                alert('Error occurred while creating PDF.');
            }
        });
    });
});
//pdf end

$('.email-invoice').on('click', function() {
  let invoiceId = $(this).data('invoice-id');
  $.ajax({
      url: emailInvoiceAjax.ajaxurl,
      type: 'POST',
      data: {
          action: 'send_invoice_to_client',
          invoice_id: invoiceId
      },
      success: function(response) {
          let responseObj = JSON.parse(response);
          if (responseObj.success) {
              alert('Invoice sent to client on: ' + responseObj.email);
          } else {
              alert('Failed to send invoice: ' + responseObj.error);
          }
      }
  });
});

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



  /** Balances ===================== */

  //select related invoice
  jQuery(document).ready(function($) {
    // Function to load invoices from the server
    $("#find-inv").click(function(e) {
      e.preventDefault();
        $('#invoiceModal').appendTo("body").modal('show');
    });

    // Function to filter invoices in the modal
    $('#searchInvoice').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#invoiceList tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Handle the click event on the useInv button to populate the form fields
    $(document).on('click', '.useInv', function() {
      $("#existing-payments").removeClass('d-none');
        let invoiceNo = $(this).data('invoice-no');
        let description = $(this).data('description');
        let clientName = $(this).data('client-name');
        let amount = $(this).data('amount');
        let already = $(this).data('already');
        let remain = $(this).data('remain');

        $('#rel_invoice').val(invoiceNo);
        $('#description').val(description);
        $('#payer').val(clientName);
        $('#amount').val(amount);
        $('#type_of_payment').val('invoice');
        $('.already').html(already);
        $('.remain').html(remain);

        $('#invoiceModal').modal('hide');
    });
}); //end select related invoice

/** Balances, view, sort */
jQuery(document).ready(function($) {
  // Handle changes to any of the select elements
  $('#year-select, #period-select, #type-select').change(function() {
    var year = $('#year-select').val();
    var period = $('#period-select').val();
    var type = $('#type-select').val();

    // Construct the URL with the new parameters
    var url = new URL(window.location.href);
    
    // Set the year parameter
    if (year !== 'other_period') {
        url.searchParams.set('selyear', year);
        // Remove period param if year is selected
        url.searchParams.delete('period');
    } else {
        url.searchParams.set('selyear', 'other_period');
        url.searchParams.set('period', period);
    }

    // Set the type parameter
    url.searchParams.set('type', type);

    // Reload the page with the updated URL
    window.location.href = url.href;
  });

  //show or hide other period in balances
  function togglePeriodSelect() {
      if ($('#year-select').val() === 'other_period') {
          $('#period-select').closest('.col-md-4').show();
      } else {
          $('#period-select').closest('.col-md-4').hide();
      }
  }

  // Initial check on page load
  togglePeriodSelect();

  // Handle change event
  $('#year-select').on('change', function() {
      togglePeriodSelect();
  });

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
                      $('#transaction-form-in #rel_invoice').val(data.rel_invoice);
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

}); // end of document ready balances view, sort, del, edit

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
jQuery(document).ready(function($) {
  $('#print-table').on('click', function() {
      var printContents = document.getElementById('balances-table').outerHTML;
      var originalContents = document.body.innerHTML;

      document.body.innerHTML = '<html><head><title>Print</title><style>body { font-family: Arial, sans-serif; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; } </style></head><body>' + printContents + '</body></html>';

      window.print();

      document.body.innerHTML = originalContents;
      location.reload();  // Reload the page to reset the state
  });
});

/** =============== STOCK ================ */
$(document).ready(function() {
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
    let platos = $(this).data("platos");
    $("label[for='out_platos']").text("Length (max: " + platos + ")");
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
    } else {
      //get option value and split '-'
      var parts = selectValue.split('-');
        if (parts[1] != '0'){
          $("#new_project_input").addClass("d-none");
        }else{
          $("#new_project_input").removeClass("d-none");
        }
        $("#new_client_input").addClass("d-none");
    }
  });

  //When the Edit (for out/cut) button is clicked
  $('.edit-out').on('click', function() {
      // Get data from the row
      var row = $(this).closest('tr');
      var clientId = row.find('td:eq(5)').text() == '--' ? 0 : row.find('td:eq(5)').data('clientid'); 
      var projectId = row.find('td:eq(5)').text() == '--' ? 0 : row.find('td:eq(5)').data('projectid'); 
      var notes = row.find('td:first').data('note');
      var stockOutId = row.find('td:first').data('stockoutid');
      
      $('#stock_out_id_edit').val(stockOutId);
      (clientId > 0)? $('#client_project').val(clientId + '-' + projectId) : $('#client_project').val(0);
      $('#notes_edit').val(notes);

      // Show the modal
      $('#editStockOutModal').modal('show');
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
  });

});//end of stock ready

/** =============== PROJECTS ================ */
$(document).ready(function() {
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
  // Toggle show/hide on click of the icon
  $('.toggle-icon').on('click', function () {
    // Find the closest .project-row, then find the next .project-more sibling and toggle its visibility
    $(this).closest('.project-row').find('.project-more').toggleClass('d-none');
    //when project card is exbanded creade a colored bg head for this card - remove on minimized
    $(this).parent().toggleClass('project-head-bg');
    $(this).parent().prev().toggleClass('project-details');
    $(this).parent().prev().prev().toggleClass('project-details');
    $(this).parent().prev().prev().prev().toggleClass('project-details');

    // Toggle the icon between arrow up and arrow down
    $(this).toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
  });
  //add items lines
                

  $(document).on("click", "#add-mat-line", function() {
    // Clone the first other item template
    let newMat = $('#add-mat-template').first().clone();
    newMat.removeClass('d-none'); // Remove the hidden class
    newMat.appendTo('#items-container'); // Append the cloned item
  });


  $(document).on("click", "#add-other-line", function() {
      // Clone the first other item template
      let newOther = $('#add-other-template').first().clone();
      newOther.removeClass('d-none'); // Remove the hidden class
      newOther.appendTo('#other-items-container'); // Append the cloned item
  });

  var total1_initial = 0; //to reset it in case user move from incl vat to plus vat
  //calculate totals
  $('#calc-pr-prices').on('click', function() {
    var total = 0;
    $('#calculations').removeClass('d-none'); 
    // Find all .show-price inputs and sum their values
    $('.show-price').each(function() {
        var price = parseFloat($(this).val()) || 0;  // Get the value and handle empty or invalid values
        total += price;
    });
    total1_initial = total;
    // Display the result inside #pr_total1
    $('#pr_total1').val(total.toFixed(2));  // Set the total, formatted to 2 decimal places
    $('#pr_price_vat').val(0); 
    $('#pr_total2').val(total.toFixed(2)); 
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

  function doCalculations(){
    var total1 = total1_initial;
    var vat = parseFloat($('#pr-vat').val());
    
    if ($('#pr-radio-vat1').is(':checked')) {
        $('#pr_total1').val(total1);
        var result = (total1 * (vat / 100)).toFixed(2);
        $('#pr_price_vat').val(result);
        $('#pr_total2').val((parseFloat(result) + total1).toFixed(2));
    } else if ($('#pr-radio-vat2').is(':checked')) {
        var total2 = total1_initial;
        let new_total1 = (total2 / (1 + (vat / 100))).toFixed(2);
        $('#pr_total1').val(new_total1);
        let var2 = (total2 - new_total1).toFixed(2);
        $('#pr_price_vat').val(var2);
        $('#pr_total2').val(total2.toFixed(2));
    }
  }

  //badge based on state
  /*
  if ($('#rem_amount').length) {
    var value = parseFloat($('#rem_amount').val());
    if (value === 0) {
        $('.this-pr-badge span').text('Completed').removeClass('bg-dark bg-success').addClass('bg-secondary');
    } else if (value > 0) {
        $('.this-pr-badge span').text('Progress').removeClass('bg-dark bg-secondary').addClass('bg-success');
    }
  } */
    $('#editDescriptionLink').on('click', function(e) {
      e.preventDefault();
      var initial = $(this).data('initial');
      var prId = $(this).data('project-id');
      $('#mod_description').val(initial);
      $('#mod_project_id').val(prId);

      // Open the modal
      $('#editDescriptionModal').modal('show');
  });
  //delete project
  $('.delete-icon-project').on('click', function() {
    if(confirm("Are you sure you want to delete this project?")) {
      $('#delProjectForm').submit();
    }
  });
});