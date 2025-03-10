<?php
/** Add new client block */
include plugin_dir_path(__FILE__) . 'add-client.php';

/** Get clients list */
$clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bms_clients order by id DESC");
$company = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bms_company LIMIT 1");//to get prefered initial vat
/** Retrieve the last quote value from the database */
$last_quote = $wpdb->get_var("SELECT quote_no FROM {$wpdb->prefix}bms_quotes ORDER BY quote_no DESC LIMIT 1");
// Extract the numeric part of the last quote
if ($last_quote) {
    $last_quote_number = (int) substr($last_quote, 2);
    $new_quote_number = $last_quote_number + 1;
} else {
    // If there are no quotes in the database, start from 1
    $new_quote_number = 1;
}
// Ensure the new quote number has at least 3 digits
$new_quote = 'QQ' . str_pad($new_quote_number, 3, '0', STR_PAD_LEFT);

// Get the current time based on WordPress settings
$current_date = date_i18n('d-m-Y', current_time('timestamp')); // Format: dd-mm-yyyy

// Calculate the valid until date (1 month from now)
$valid_until = date_i18n('d-m-Y', strtotime('+1 month', current_time('timestamp')));


if (get_transient('quote_added_success')): ?>
          <div id="qu-added-success" class="alert alert-success" role="alert">
              Quote added successfully.
          </div>
          <?php delete_transient('quote_added_success'); // Remove the transient after displaying the message?>
        <?php elseif ($error_message = get_transient('quote_added_error')): ?>
            <div id="cl-added-error" class="alert alert-danger" role="alert">
                Error Saving Quote: <?php echo esc_html($error_message); ?>
            </div>
            <?php delete_transient('quote_added_error'); // Remove the transient after displaying the message?>
    <?php endif;
if (get_transient('quote_updated_success')): ?>
          <div id="qu-upd-success" class="alert alert-success" role="alert">
              Quote updated successfully.
          </div>
          <?php delete_transient('quote_updated_success'); // Remove the transient after displaying the message?>
        <?php elseif ($error_message = get_transient('quote_updated_error')): ?>
            <div id="cl-upd-error" class="alert alert-danger" role="alert">
                Error Saving Quote: <?php echo esc_html($error_message); ?>
            </div>
            <?php delete_transient('quote_updated_error'); // Remove the transient after displaying the message?>
    <?php endif;
?>
  <div class="bms-container">
    <div class="row mt-2">
        <div class="d-grid gap-2 d-md-block text-end"><button id="add-new-quote-btn" class="btn btn-sm btn-success mb-2">Create quote</button></div>
    </div>
    <div class="row py-2 bordered-block" id="add-quote-block" style="<?php echo (isset($location) && $location == 'q_inv') ? 'display: block;' : ''; ?>" >
      <form id="quote-form" method="post" action="">
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="quote_no" class="form-label">Quote no</label>
            <input type="text" name="quote_no" id="quote_no" value="<?php echo esc_attr($new_quote); ?>" class="form-control" required readonly />
          </div>
          <div class="col-md-2">
            <label for="lang" class="form-label">Lang</label>
            <select name="lang" id="lang" class="form-select form-select-sm me-2">
                <option value="el" selected>Ελληνικά</option>
                <option value="en">English</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="date" class="form-label">Date</label>
            <input type="text" name="date" id="date" class="form-control" value="<?php echo esc_attr($current_date); ?>" required />
            <!-- <input type="hidden" name="converted_date" id="converted_date"  / -->
          </div>
          <div class="col-md-3">
            <label for="valid_until" class="form-label">Valid until</label>
            <input type="text" name="valid_until" id="valid_until" class="form-control" value="<?php echo esc_attr($valid_until); ?>" required />
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-8 mb-2 mb-md-0">
            <label for="client_select" class="form-label">Client</label>
              <select name="client_select" id="client_select" class="form-select me-2" required>
                <!-- PHP code to loop through clients -->
                <?php if (!isset($location) || (isset($location) && $location == 'default')) { ?>
                <option value="0" selected>Select client</option>
                <?php } ?>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo esc_attr($client->id); ?>"><?php echo esc_html($client->name); ?></option>
                <?php endforeach; ?>
              </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
              <button type="button" id="add-new-cl-btn" class="btn btn-sm btn-secondary">New client</button>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-12">
            <label for="include_in_quote" class="form-label">Include</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="include_our_address" name="include[]" value="our_address" checked>
              <label class="form-check-label" for="include_our_address">Our details</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="include_client_address" name="include[]" value="client_address" checked>
              <label class="form-check-label" for="include_client_address">Client details</label>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-12">
            <label for="product_description" class="form-label">Description</label>
            <textarea name="product_description" id="product_description" class="form-control" required></textarea>
          </div>
        </div>
        <div id="items-container" class="item-line bg-light p-2">
          <div id="line0" class="row mb-3">
            <div class="col-md-7">
              <label for="item-descr" class="form-label">Product</label>
              <textarea name="item_line[]" id="item-descr" class="form-control item-descr" required /></textarea>
            </div>
            <div class="col-md-1">
              <label for="item-quantity" class="form-label">#</label>
              <input type="text" name="item-quantity[]" value=1 class="form-control item-quantity only-num" required />
            </div>
            <div class="col-md-2">
              <label for="unit-price_1" class="form-label">Unut price</label>
              <input type="text" name="unit-price[]" id="unit-price_1" value=0 class="form-control unit-price only-num" required />
            </div>
            <div class="col-md-2">
              <label for="price_1" class="form-label">Price (&euro;)</label>
              <input type="text" name="price[]" id="price_1" value=0 readonly class="form-control show-price" required />
            </div>
          </div>
        </div><!--end of items container-->
        <div class="row mb-3">
          <div class="col-12">
            <button type="button" id="add-item-line" class="btn btn-secondary">Add field</button>
          </div>
        </div>
        <div class="row mb-3">
          <hr>
          <div class="col-md-10">
            <b>Total</b> (before VAT):
          </div>
          <div id="total-before-vat" class="col-2 fw-bold bg-warning">
            0
          </div>
          <hr>
        </div>
        <div class="row mb-3">
          <div class="col-md-3">
          <label for="vat" class="form-label">VAT (%)</label>
            <div class="input-group mb-3">
            <input type="number" name="vat" id="vat" class="form-control" min="0" max="100" value="<?php echo esc_attr($company->vat_prefered); ?>" required />
              <span class="input-group-text"> %</span>
            </div>
          </div><!--end vat val-->
          <div class="col-md-7">
            <div class="mb-3">&nbsp;</div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="plus-inc-vat" id="radio-vat1" value="1" checked>
              <label class="form-check-label" for="plus-vat1">Plus VAT</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="plus-inc-vat" id="radio-vat2" value="2">
              <label class="form-check-label" for="radio-vat2">Included</label>
            </div>
          </div><!--end radio col-->
          <div class="col-2 fw-bold d-flex align-items-center">
            <div id = "vat-price" style="border-bottom: 1px #ccc solid;width:100%;background-color:#eee;">
            0
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <hr>
          <div class="col-10">
            <b>Total</b> (icl. VAT):
          </div>
          <div id="total-after-vat" class="col-2 fw-bold bg-warning">
            0
          </div>
          <hr>
        </div>
        <div class="row mb-3">
          <div class="col">
           <button type="button" id="add-discount" class="btn btn-sm btn-light">Add discount</button>
          </div>
        </div>
      <div id="discount-cont">
        <div class="row mb-3">
          <div class="col-md-7">
            <label for="discount_type" class="form-label">Disc. description</label>
            <input type="text" name="discount_descr" id="discount_descr" class="form-control" />
          </div>
          <div class="col-md-3">
            <label for="discount_val" class="form-label">Disc. amount</label>
            <input type="text" name="discount_val" id="discount_val" class="form-control only-num" value=0 required />
          </div>
          <div class="col-2">
          </div>
        </div>
        <div class="row mb-3">
          <hr>
          <div class="col-10">
            <b>Total</b> (Incl. discount):
          </div>
          <div id="total-after-discount" class="col-2 fw-bold bg-warning">
            0
          </div>
          <hr>
        </div>
      </div><!--end of discount container-->
        <div class="row mb-3">
          <div class="col-12">
            <label for="delivery_time" class="form-label">Delivery time</label>
            <input type="text" name="delivery_time" id="delivery_time" class="form-control" required />
          </div>
        </div>
        <div class="d-grid gap-2 d-md-block">
          <input type="hidden" name="total-before-vat" value=0 />
          <input type="hidden" name="total-after-vat" value=0 />
          <input type="hidden" name="total-after-discount" value=0 />
          <input type="hidden" name="vat-price" value=0 />
          <input type="hidden" name="save-type" value="new" />
          <input type="submit" name="bms_add_quote" id="bms_add_quote" class="btn btn-primary" value="Save quote" />
          <button type="reset" class="btn btn-secondary" onclick="location.reload();">Cancel</button>
        </div>
      </form>
    </div><!--end of create quote block-->

    <?php
    /** ============  show quotes ======================================================== */
    $quotes_table = $wpdb->prefix . 'bms_quotes';
    $clients_table = $wpdb->prefix . 'bms_clients';
    $quotes = $wpdb->get_results("SELECT q.*, c.name as client_name FROM $quotes_table q LEFT JOIN $clients_table c ON q.client_id = c.id ORDER BY q.id DESC");
    ?>

    <div id="list-quotes" style="<?php echo (isset($location) && $location == 'q_inv') ? 'display:none;' : ''; ?>" >
        <div class="row my-3">
            <div class="col-md-6 d-flex align-items-center p-md-0">
                <input type="text" id="quote-search" class="form-control" placeholder="Search..." style="font-size:0.9em;">
            </div>
            <div class="col-md-6 text-end">
                <div class="d-grid gap-2 d-md-block">
                    <span class="text-center">Sort By:</span> 
                    <button id="sort-quote-no" class="btn btn-sm btn-dark">Quote no</button>
                    <button id="sort-client-name" class="btn btn-sm btn-secondary">Client name</button>
                </div> 
            </div>
        </div>
        <div class="row quote-row-head">
            <div class="col-md-2 d-none d-md-block">Quote no</div>
            <div class="col-md-3 d-none d-md-block">Description</div>
            <div class="col-md-3 d-none d-md-block">Client</div>
            <div class="col-md-4 d-none d-md-block text-center">Functions</div>
        </div>
        <div id="data-list">
            <?php foreach ($quotes as $quote) : ?>
              <div class="row quote-row mb-1" id="quotes-grid">
                  <div class="col-md-2 align-content-center p-2"><?php echo esc_html($quote->quote_no); ?></div>
                  <div class="col-md-3 align-content-center p-2"><?php echo esc_html(mb_strimwidth($quote->product_description, 0, 50, '...')); ?></div>
                  <div class="col-md-3 align-content-center p-2"><?php echo esc_html($quote->client_name); ?></div>
                  <div class="col-md-4 align-content-center text-center">
                      <div class="btn-group btn-group-sm" role="group">
                          <button class="btn btn-sm btn-outline-secondary view-quote" data-quote-id="<?php echo esc_attr($quote->id); ?>">View</button>
                          <button class="btn btn-sm btn-outline-success modify-quote" data-quote-id="<?php echo esc_attr($quote->id); ?>">Modify</button>
                          <div class="btn-group btn-group-sm" role="group">
                              <button id="btnGroupDrop1" type="button" class="btn btn-sm btn-outline-secondary more-actions dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                  More...
                              </button>
                              <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="list-style-type: none; padding: 1px;">
                                  <li><a class="dropdown-item save-new" data-quote-id="<?php echo esc_attr($quote->id); ?>" href="#">Duplicate</a></li>
                                  <li><a class="dropdown-item make-invoice" href="<?php echo esc_url('/invoices/?quoteid=' . $quote->id); ?>">Create inv.</a></li>
                                  <li><a class="dropdown-item delete-quote" data-quote-id="<?php echo esc_attr($quote->id); ?>" href="#">Delete</a></li>
                              </ul>
                          </div>
                      </div>
                  </div>
              </div>
          <?php endforeach; ?>

        </div>
    </div><!--end of list quotes block-->
  </div><!--end of bms-container -->  

    <?php
