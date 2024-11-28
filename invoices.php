<?php
/** Add new cliend block */
include plugin_dir_path(__FILE__) . 'add-client.php';

$clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bms_clients order by id DESC");
// Get company details and settings
$company = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bms_company LIMIT 1");

/** Retrieve the last balance value from the database */
$last_invoice = $wpdb->get_var("SELECT invoice_no FROM {$wpdb->prefix}bms_invoices ORDER BY invoice_no DESC LIMIT 1");
// Extract the numeric part of the last invoice
if (!$last_invoice) {
    $new_invoice = '0001';
} else {
    $last_numeric_part = (int)preg_replace('/[^0-9]/', '', $last_invoice); // Extract numeric part
    $new_numeric_part = $last_numeric_part + 1;
    $new_invoice = str_pad($new_numeric_part, strlen($last_invoice), '0', STR_PAD_LEFT);
}

/** If invoice is based on quote get the data from quotes table
 * If is not reference quote then add initial empty values
*/
if (isset($_GET['quoteid'])) {
    $quote_id = intval($_GET['quoteid']); // Sanitize and convert to integer

    $table_name = $wpdb->prefix . 'bms_quotes';
    $init = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $quote_id)
    );

    $table_items = $wpdb->prefix . 'bms_quote_items';
    $items = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_items WHERE quote_id = %d", $quote_id)
    );

    /*
    print_r($init);
    echo "<br>";
    print_r($company); */
} else {
    $init = (object)['client_id' => 0, 'product_description' => '', 'total_before_vat' => 0, 'vat' => $company->vat_prefered, 'vat_price' => 0, 'total_after_vat' => 0, 'plus_inc_vat' => 1, 'discount_description' => '', 'discount_val' => 0, 'total_after_discount' => 0, 'delivery_time' => '', 'lang' => 'el'];
}
/** date functions */
// Use WordPress internal timezone functions
$current_date = current_time('d/m/Y'); // Format the current date as dd/mm/yyyy

if (get_transient('invoice_added_success')): ?>
          <div id="qu-added-success" class="alert alert-success" role="alert">
              Invoice added successfully.
          </div>
          <?php delete_transient('invoice_added_success'); // Remove the transient after displaying the message?>
        <?php elseif ($error_message = get_transient('invoice_added_error')): ?>
            <div id="cl-added-error" class="alert alert-danger" role="alert">
                Error Saving Invoice: <?php echo esc_html($error_message); ?>
            </div>
            <?php delete_transient('invoice_added_error'); // Remove the transient after displaying the message?>
    <?php endif;

if (get_transient('invoice_updated_success')): ?>
      <div id="qu-added-success" class="alert alert-success" role="alert">
          Invoice added successfully.
      </div>
      <?php delete_transient('invoice_updated_success'); // Remove the transient after displaying the message?>
    <?php elseif ($error_message = get_transient('invoice_updated_error')): ?>
        <div id="cl-added-error" class="alert alert-danger" role="alert">
            Error Saving Invoice: <?php echo esc_html($error_message); ?>
        </div>
        <?php delete_transient('invoice_updated_error'); // Remove the transient after displaying the message?>
    <?php endif; ?>
    <div class="bms-container">
      <div class="row mt-2">
          <div class="d-grid gap-2 d-md-block text-end"><button id="add-new-invoice-btn" class="btn btn-sm btn-success mb-2">Create invoice</button></div>
      </div>
      <?php //echo "this:".(isset($location) && $location !== 'q_inv');?>
      <div class="row py-2 bordered-block" id="add-invoice-block" style="<?php echo ((isset($location) && $location == 'q_inv') || isset($_GET['quoteid'])) ? 'display: block;' : ''; ?>" >
      
      <?php /*
          if (!isset($_GET['quoteid']) || (isset($location) && $location !== 'q_inv')) {
              echo "style='display:none;'";
          } else {
              echo "style='display:block;'";
          }*/
      ?>
        <form id="invoice-form" method="post" action="/invoices/">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="invoice_no" class="form-label">Invoice no</label>
              <input type="text" name="invoice_no" id="invoice_no" value="<?php echo esc_attr($new_invoice); ?>" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label for="date" class="form-label">Date</label>
              <input type="text" name="date" id="date" class="form-control" value="<?php echo esc_attr($current_date); ?>" required />
            </div>
            <div class="col-md-3">
              <div style="display: flex; flex-direction: column;">
                  <label for="lang" class="form-label">Lang</label>
                  <select name="lang" class="form-select-sm me-2">
                      <option value="el" <?php if ($init->lang != 'en') {
                          echo 'selected';
                      } ?> >Ελληνικά</option>
                      <option value="en" <?php if ($init->lang == 'en') {
                          echo 'selected';
                      } ?> >English</option>
                  </select>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-8 mb-2 mb-md-0">
              <label for="client_select" class="form-label">Client</label>
                <select name="client_select" id="client_select" class="form-select me-2" required>
                  <!-- PHP code to loop through clients, if location exists means that new client saved inside this form so load the last client -->
                  <?php if (!isset($location) || (isset($location) && $location == 'default')) { ?>
                  <option value="0" <?php if ($init->client_id == 0) {
                      echo 'selected';
                  } ?> >Select Client</option>
                  <?php }
                  foreach ($clients as $client): ?>
                        <option <?php if ($init->client_id == $client->id) {
                            echo 'selected';
                        } ?> value="<?php echo esc_attr($client->id); ?>"><?php echo esc_html($client->name); ?></option>
                  <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" id="add-new-cl-btn" class="btn btn-sm btn-secondary">New client</button>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-12">
              <label for="include_in_invoice" class="form-label">Include</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="include_our_address" name="include[]" value="our_address" checked>
                <label class="form-check-label" for="include_our_address">Our details</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="include_client_address" name="include[]" value="client_address" checked>
                <label class="form-check-label" for="include_client_address">Client details</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="include_bank_details" name="include[]" value="bank_details" checked>
                <label class="form-check-label" for="include_bank_details">Bank acc.</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="include_thanks_msg" name="include[]" value="thanks_msg" checked>
                <label class="form-check-label" for="include_thanks_msg">Thank you msg</label>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-12">
              <label for="product_description" class="form-label">Description</label>
              <textarea name="product_description" id="product_description" class="form-control" required><?php echo esc_textarea($init->product_description); ?></textarea>
            </div>
          </div>
          <div id="items-container" class="item-line bg-light p-2">
            <?php if (!isset($_GET['quoteid'])) { ?>
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
                <label for="unit-price_1" class="form-label">Unit price</label>
                <input type="text" name="unit-price[]" id="unit-price_1" value=0 class="form-control unit-price only-num" required />
              </div>
              <div class="col-md-2">
                <label for="price_1" class="form-label">Price (&euro;)</label>
                <input type="text" name="price[]" id="price_1" value=0 readonly class="form-control show-price" required />
              </div>
            </div>
            <?php } else {
                foreach ($items as $item) {
                    ?>
                <div class="row mb-3">
                <div class="col-md-7">
                  <label for="item_line_1" class="form-label">Product</label>
                  <textarea name="item_line[]" id="item_line_1" class="form-control" required><?php echo esc_textarea($item->description); ?></textarea>
                </div>
                <div class="col-md-1">
                  <label for="item-quantity" class="form-label">#</label>
                  <input type="text" name="item-quantity[]" value="<?php echo esc_attr(isset($_GET['quoteid']) ? $item->quantity : 1); ?>" class="form-control item-quantity only-num" required />
                </div>
                <div class="col-md-2">
                  <label for="unit-price_1" class="form-label">Unit price</label>
                  <input type="text" name="unit-price[]" id="unit-price_1" value="<?php echo esc_attr(isset($_GET['quoteid']) ? $item->unit_price : 0); ?>" class="form-control unit-price only-num" required />
                </div>
                <div class="col-md-2">
                  <label for="price_1" class="form-label">Price (&euro;)</label>
                  <input type="text" name="price[]" id="price_1" value="<?php echo (isset($_GET['quoteid'])) ? esc_attr($item->price) : esc_attr(0); ?>" readonly class="form-control show-price" required />
                </div>
              </div>
                <?php
                }
            }//end else quoteid set?>
          </div><!-- end items-container -->
          <div class="row mb-3">
            <div class="col-12">
              <button type="button" id="add-item-line" class="btn btn-secondary">Add new field</button>
            </div>
          </div>
          <div class="row mb-3">
            <hr>
            <div class="col-md-10">
              <b>Total</b> (before VAT):
            </div>
            <div id="total-before-vat" class="col-2 fw-bold bg-warning">
              <?php echo esc_html($init->total_before_vat); ?>
            </div>
            <hr>
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
            <label for="vat" class="form-label">VAT (%)</label>
              <div class="input-group mb-3">
              <input type="number" name="vat" id="vat" class="form-control" min="0" max="100" value="<?php echo esc_attr($init->vat); ?>" required />
                <span class="input-group-text"> %</span>
              </div>
            </div><!--end vat val-->
            <div class="col-md-7">
              <div class="mb-3">&nbsp;</div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="plus-inc-vat" id="radio-vat1" value="1" <?php echo ($init->plus_inc_vat == 1) ? 'checked' : '' ; ?> >
                <label class="form-check-label" for="plus-vat1">Plus VAT</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="plus-inc-vat" id="radio-vat2" value="2" <?php echo ($init->plus_inc_vat == 2) ? 'checked' : '' ; ?>>
                <label class="form-check-label" for="radio-vat2">Included</label>
              </div>
            </div><!--end radio col-->
            <div class="col-2 fw-bold d-flex align-items-center">
              <div id = "vat-price" style="border-bottom: 1px #ccc solid;width:100%;background-color:#eee;">
                <?php echo esc_html($init->vat_price); ?>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <hr>
            <div class="col-10">
              <b>Total</b> (incl. VAT):
            </div>
            <div id="total-after-vat" class="col-2 fw-bold bg-warning">
              <?php echo esc_html($init->total_after_vat); ?>
            </div>
            <hr>
          </div>
          <div class="row mb-3">
            <div class="col">
            <button type="button" id="add-discount" class="btn btn-sm btn-light">Add discount</button>
            </div>
          </div>
        <div id="discount-cont" <?php echo ($init->discount_val > 0) ? 'style="display:block;"' : ''; ?> >
          <div class="row mb-3">
            <div class="col-md-7">
              <label for="discount_type" class="form-label">Disc. description</label>
              <input type="text" name="discount_descr" id="discount_descr" class="form-control" value="<?php echo esc_attr($init->discount_description); ?>" />
            </div>
            <div class="col-md-3">
              <label for="discount_val" class="form-label">Disc. amount</label>
              <input type="text" name="discount_val" id="discount_val" class="form-control only-num" value="<?php echo esc_attr($init->discount_val); ?>" />
            </div>
            <div class="col-2">
            </div>
          </div>
          <div class="row mb-3">
            <hr>
            <div class="col-10">
              <b>Total</b> (after discount):
            </div>
            <div id="total-after-discount" class="col-2 fw-bold bg-warning">
              <?php echo esc_html($init->total_after_discount); ?>
            </div>
            <hr>
          </div>
        </div><!--end of discount container-->
        <div class="d-grid gap-2 d-md-block">
          <input type="hidden" name="total-before-vat" value=0 />
          <input type="hidden" name="total-after-vat" value=0 />
          <input type="hidden" name="total-after-discount" value=0 />
          <input type="hidden" name="vat-price" value=0 />
          <input type="hidden" name="save-type" value="new" />
          <input type="submit" name="bms_add_invoice" id="bms_add_invoice" class="btn btn-primary" value="Save invoice" />
          <button type="reset" class="btn btn-secondary" onclick="location.reload();">Cancel</button>
        </div>
        </form>
        
      </div><!--end of create invoice block-->
      <?php
      /** ============  show invoices ======================================================== */
      $invoices_table = $wpdb->prefix . 'bms_invoices';
  $clients_table = $wpdb->prefix . 'bms_clients';
  $invoices = $wpdb->get_results("SELECT q.*, c.name as client_name FROM $invoices_table q LEFT JOIN $clients_table c ON q.client_id = c.id ORDER BY q.id DESC");
  ?>

      <div id="list-invoices" style="<?php echo (isset($location) && $location == 'q_inv') ? 'display:none;' : ''; ?>" >
          <div class="row my-3">
              <div class="col-md-6 d-flex align-items-center p-md-0">
                  <input type="text" id="invoice-search" class="form-control" placeholder="Search..." style="font-size:0.9em;">
              </div>
              <div class="col-md-6 text-end">
                  <div class="d-grid gap-2 d-md-block">
                      <span class="text-center">Sort By:</span> 
                      <button id="sort-invoice-no" class="btn btn-sm btn-dark">Invoice Number</button>
                      <button id="sort-client-name" class="btn btn-sm btn-secondary">Client Name</button>
                  </div> 
              </div>
          </div>
          <div class="row invoice-row-head">
              <div class="col-md-2 d-none d-md-block">Inv. no</div>
              <div class="col-md-2 d-none d-md-block">Description</div>
              <div class="col-md-3 d-none d-md-block">Client</div>
              <div class="col-md-1 d-none d-md-block text-center">Status</div>
              <div class="col-md-4 d-none d-md-block text-center">Functions</div>
          </div>
          <div id="data-list">
              <?php foreach ($invoices as $invoice) :
                  //status functions
                  $status = $invoice->status;
                  $total_after_discount = $invoice->total_after_discount;
                  $status_badge = '';
                  if ($status == 'cancelled') {
                      $status_badge = '<span class="badge bg-secondary">Cancelled</span>';
                  } else {
                      // If not cancelled, calculate the sum of payments
                      $sum_paid = $wpdb->get_var($wpdb->prepare(
                          "SELECT SUM(amount) FROM {$wpdb->prefix}bms_balances WHERE rel_invoice = %d",
                          $invoice->id
                      ));

                      // Determine which badge to show based on the sum_paid
                      if ($sum_paid == 0) {
                          $status_badge = '<span class="badge bg-dark">Not paid</span>';
                      } elseif ($sum_paid > 0 && $sum_paid < $total_after_discount) {
                          $status_badge = '<span class="badge bg-info">Partially paid</span>';
                      } elseif ($sum_paid == $total_after_discount) {
                          $status_badge = '<span class="badge bg-success">Fully paid</span>';
                      } elseif ($sum_paid > $total_after_discount) {
                          $status_badge = '<span class="badge bg-warning">Overpaid</span>';
                      }
                  }
                  ?>
                  <div class="row invoice-row mb-1" id="invoices-grid">
                      <div class="col-md-2 align-content-center p-2"><?php echo esc_html($invoice->invoice_no); ?></div>
                      <div class="col-md-2 align-content-center p-2"><?php echo esc_html(mb_strimwidth($invoice->product_description, 0, 50, '...')); ?></div>
                      <div class="col-md-3 align-content-center p-2"><?php echo esc_html($invoice->client_name); ?></div>
                      <div class="col-md-1 align-content-center text-center p-2"><?php echo wp_kses_post($status_badge); ?></div>
                      <div class="col-md-4 align-content-center text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-sm btn-outline-secondary view-invoice" data-invoice-id="<?php echo esc_attr($invoice->id); ?>">View</button>
                            <button class="btn btn-sm btn-outline-success modify-invoice" data-invoice-id="<?php echo esc_attr($invoice->id); ?>">Modify</button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button id="btnGroupDrop1" type="button" class="btn btn-sm btn-outline-secondary more-actions dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    More...
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="list-style-type: none; padding: 1px;">
                                    <li><a class="dropdown-item save-new-inv" data-invoice-id="<?php echo esc_attr($invoice->id); ?>" href="#">Duplicate</a></li>
                                    <li><a class="dropdown-item create-pdf-inv" data-invoice-id="<?php echo esc_attr($invoice->id); ?>" href="#">Create PDF</a></li>
                                    <li><a class="dropdown-item email-invoice" data-invoice-id="<?php echo esc_attr($invoice->id); ?>" href="#">Email client</a></li>
                                    <?php if ($status != 'cancelled') { ?>
                                        <li><a class="dropdown-item cancel-invoice" data-invoice-id="<?php echo esc_attr($invoice->id); ?>" href="#">Cancel</a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>   
                    </div>
                  </div>
              <?php endforeach; ?>
          </div>
      </div><!--end of list invoices block-->
</div><!--end of bms-container -->      

<?php
