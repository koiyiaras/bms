<?php
/** Add new client block */
//include plugin_dir_path(__FILE__) . 'add-client.php';

$clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bms_clients order by id DESC");
$company = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bms_company LIMIT 1");

$table_materials = $wpdb->prefix . 'bms_materials';
$materials = $wpdb->get_results("SELECT id, type FROM $table_materials");

/** status completed */
if (isset($_POST['complete_project'])) {
    $project_id = $_GET['project_id'];

    // Update the project in the database
    $wpdb->update(
        $wpdb->prefix . 'bms_projects', // Table name
        [ 'status' => 'COMPLETED' ], // Data to update
        ['id' => $project_id]      // Where condition
    );
}
/** save payment */
if (isset($_POST['save_pr_payment'])) {
    $project_id = $_GET['project_id'];
    $result = $wpdb->insert(
        $wpdb->prefix . 'bms_balances', // Table name
        [
            'in_out' => 1,
            'rel_invoice' => 0,
            'rel_project' => $project_id,
            'description' => $_POST['payment_description'],
            'payer_payee' => $_POST['client_name'],
            'amount' => $_POST['payment_amount'],
            'type_of_payment' => 'pr_inv',
            'payment_date' => $_POST['payment_date']
        ]
    );

    if ($result) {
        echo '<div id="qu-added-success" class="alert alert-success" role="alert">Project added successfully.</div>';
    } else {
        $error_message = $wpdb->last_error;
        echo '<div id="cl-added-error" class="alert alert-danger" role="alert">Error Saving project: ' . esc_html($error_message) . '</div>';
    }
}
// Get project ID from URL if set
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;

// If a project ID is set, retrieve that specific project
if ($project_id) {
    $projects_table = $wpdb->prefix . 'bms_projects';
    $clients_table = $wpdb->prefix . 'bms_clients';
    
    // Fetch the project with prepared query
    $project = $wpdb->get_row($wpdb->prepare("
        SELECT q.*, c.name as client_name, c.id as client_id 
        FROM $projects_table q 
        LEFT JOIN $clients_table c ON q.client_id = c.id 
        WHERE q.id = %d", 
        $project_id
    ));
    
    // Use prepared query for stock retrieval
    $query_s = $wpdb->prepare(
        "SELECT so.*, m.* 
        FROM {$wpdb->prefix}bms_stock_out AS so
        JOIN {$wpdb->prefix}bms_materials AS m ON so.material_id = m.id
        WHERE so.project_id = %d AND so.assigned = %s",
        $project->id,  // First placeholder %d for integer
        '0'            // Second placeholder %s for string
    );

    // Get the results
    $stock_r = $wpdb->get_results($query_s);
    //print_r($project);
    if ($project) {
        // Display the specific project details
        ?>
        <div class="bms-container">
            <div id="project-more" class='project-detail'>
                <div class="row p-2 g-0 bg-light border rounded">
                    <div class="col-md-3 align-items-center">
                        <p style="font-size:1.2em;" class="text-primary"><?php echo esc_html($project->client_name); ?></p>
                    </div>
                    <div class="col-md-7 align-items-center">
                        <p class="fw-bold"><?php echo esc_html(mb_strimwidth($project->description, 0, 30, '...')); ?></p>
                    </div>
                    <div class="col-md-2 align-items-center text-center this-pr-badge">
                    <?php
                switch ($project->status) {
                    case 'PROGRESS':
                        echo '<span class="badge bg-success text-white">Progress</span>';
                        break;
                    case 'COMPLETED':
                        echo '<span class="badge bg-primary text-white">Completed</span>';
                        break;
                    case 'ERROR':
                        echo '<span class="badge bg-warning">Error</span>';
                        break;
                    default:
                        echo '<span class="badge bg-dark text-white">Start</span>';
                        break;
                } ?>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col-md-12">
                        <!-- Example content, replace with actual data -->
                        <p>&nbsp;</p>
                        <h3 style="border-bottom: 2px solid darkgray; display: inline-block;">MATERIALS</h3>
                    </div>
                </div> 
                <div id="materials-outer" class="border p-2 rounded">
                <form id="materials-form" method="post" action="" onkeydown="return (event.key != 'Enter')" >
                <div class="row">
                        <div class="col-md-3">Type</div>
                        <div class="col-md-3">Area</div>
                        <div class="col-md-1">#</div>
                        <div class="col-md-2">Item price</div>
                        <div class="col-md-2">Line price</div>
                </div>
                <div class="row">
                    <div class="col-md-12"><!--inside this col put the lines-->
                        <div id="items-container2" class="item-line bg-light p-2" style="padding-left:1em!important;padding-right:1em!important;">
                                <?php foreach ($stock_r as $stock) :
                                    $this_area = $stock->out_mikos * $stock->out_platos;
                                    ?>
                                <div class="row mb-3 border-bottom pb-2">   
                                    <div class="col-md-3"><span><?php echo esc_html($stock->type); ?></span></div>
                                    <input type="hidden" name="mat_type[]" value="<?php echo esc_html($stock->type); ?>" />
                                    <div class="col-md-3"><span><?php echo '<strong>' . esc_html($this_area) . '</strong> (' . esc_html($stock->out_mikos) . 'x' . esc_html($stock->out_platos) . ')'; ?></span></div>
                                    <input type="hidden" name="new-item-length[]" value="<?php echo esc_html($stock->out_mikos); ?>" />
                                    <input type="hidden" name="new-item-width[]" value="<?php echo esc_html($stock->out_platos); ?>" />
                                    <div class="col-md-1">
                                        <input type="text" name="pr_item-quantity[]" value="1" class="form-control item-quantity only-num" required="">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="pr_unit-price[]" value="0" class="form-control unit-price only-num" required="">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="pr_price[]" value="0" readonly="" class="form-control show-price" required="">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <!--here add a new line hide it until the new line button is clicked make somehow different button to add next new lines with js-->
                                <div id="items-container">
                                    <div id="add-mat-template" class="row mb-3 d-none border-bottom pb-2">  
                                    <input type="hidden" name="counter[]" value="dump" /> 
                                        <div class="col-md-3">
                                            <select name="mat_type2[]" class="form-select-sm">
                                                <option value='0' selected>SELECT TYPE</option>
                                                <?php foreach ($materials as $material): ?>
                                                    <option value='<?php echo esc_attr($material->type); ?>'><?php echo esc_html($material->type); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-inline-flex">
                                            <div>
                                                <input type="text" name="new-item-length2[]" value="0" class="form-control only-num" required>
                                            </div>
                                            <div class="mx-2">x</div>
                                            <div>
                                                <input type="text" name="new-item-width2[]" value="0" class="form-control only-num" required>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <input type="text" name="pr_item-quantity2[]" value="1" class="form-control item-quantity only-num" required="">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="pr_unit-price2[]" value="0" class="form-control unit-price only-num" required="">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="pr_price2[]" value="0" readonly="" class="form-control show-price" required="">
                                        </div>
                                    </div><!--end of first material--> 
                                </div>

                                <div id="other-items-container">
                                    <div id="add-other-template" class="row mb-3 d-none border-bottom pb-2">   
                                    <input type="hidden" name="counter[]" value="dump" />
                                        <div class="col-md-6">
                                            <input type="text" name="mat_type3[]" value="0" class="form-control" required>
                                        </div>
                                        <div class="col-md-1">
                                            <input type="text" name="pr_item-quantity3[]" value="1" class="form-control item-quantity only-num" required="">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="pr_unit-price3[]" value="0" class="form-control unit-price only-num" required="">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="pr_price3[]" value="0" readonly="" class="form-control show-price" required="">
                                        </div>
                                    </div><!--end of first other--> 
                                </div><!--end of others container-->
                            </div><!--end #items-container-row -->
                    </div><!--end col -->
                </div><!--end row -->
                <div class="row mt-2"> 
                    <div class="col-md-6">
                        <button type="button" id="add-mat-line" class="btn btn-sm btn-secondary">Add material</button>
                        <button type="button" id="add-other-line" class="btn btn-sm btn-secondary">Add other</button>
                    </div>   
                    <div class="col-md-5 text-end">
                        <button type="button" id="calc-pr-prices" class="btn btn-sm btn-dark px-3">Next</button>
                    </div>  
                </div>
                <!--====================================NEXT STEP CALCULATIONS=====================================-->
                <div id="calculations" class="d-none">
                <div class="row gx-0 mt-3">
                    <div class="col-md-9">
                        <b>Total</b> (before VAT):
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="pr_total1" value="Total1" id="pr_total1" class="form-control light-yellow" required="">
                    </div>
                    <hr class="mt-1">
                </div>
                <div class="row gx-0 mb-3">
                    <div class="col-md-3">
                        <label for="vat" class="form-label">VAT (%)</label>
                        <div class="input-group input-group-sm mb-3">
                            <input type="number" name="vat" id="pr-vat" class="form-control" min="0" max="100" value="0" required />
                            <span class="input-group-text"> %</span>
                        </div>
                    </div><!--end vat val-->
                    <div class="col-md-6 ps-2">
                        <div class="mb-3">&nbsp;</div>
                        <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="plus-inc-vat" id="pr-radio-vat1" value="1" checked="checked" >
                        <label class="form-check-label" for="plus-vat1">Plus VAT</label>
                        </div>
                        <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="plus-inc-vat" id="pr-radio-vat2" value="2" >
                        <label class="form-check-label" for="radio-vat2">Incl. VAT</label>
                        </div>
                    </div><!--end radio col-->
                    <div class="col-2 fw-bold d-flex align-items-center">
                            <input type="text" name="pr_price_vat" value=0 readonly="" id="pr_price_vat" class="form-control light-yellow" required="">
                    </div>
                </div><!--end row -->
                <div class="row gx-0 mb-3">
                    <hr class="mt-1">
                    <div class="col-9">
                        <b>Total</b> (incl. VAT):
                    </div>
                    <div id="" class="col-2 ">
                        <input type="text" name="pr_total2" value=0 readonly="" id="pr_total2" class="form-control light-yellow" required="">
                    </div>
                    <hr class="mt-1">
                </div><!--end row -->
                <div class="row mb-3">
                    <div class="col">
                    <button type="button" id="add-discount" class="btn btn-sm btn-light">Add Discount</button>
                    </div>
                </div>
                <div id="discount-cont">
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label for="discount_type" class="form-label">Disc. description</label>
                            <input type="text" name="discount_descr" id="" class="form-control discount_descr" value = "" />
                        </div>
                        <div class="col-md-3">
                            <label for="discount_val" class="form-label">Discount</label>
                            <input type="text" name="discount_val" id="discount_val" class="form-control only-num" value="" />
                        </div>
                        <div class="col-2">
                        </div>
                    </div>
                    <div class="row mb-3">
                    <hr>
                        <div class="col-9">
                            <b>Total</b> (after discount):
                        </div>
                        <div id="" class="col-2">
                            <input type="text" name="pr_total3" value="..." readonly="" id="pr_total3" class="form-control light-yellow" required="">
                        </div>
                    <hr>
                    </div>
                </div><!--end of discount container-->
            
                <!--====================================END  CALCULATIONS=====================================-->
                <div class="row">
                    <div class="col-md-12 text-center">
                        <button type="submit" name="matInv" class="btn btn-primary">SAVE & ADD TO INVOICE</button>
                    </div>
                </div>
                </div><!--end of calculations div-->
                </form><!--end materials form -->
                </div><!--end materials outer -->

                
                <div class="row p-2">
                    <div class="col-md-12">
                        <p>&nbsp;</p>
                        <h3 style="border-bottom: 2px solid darkgray; display: inline-block;">INVOICE</h3>
                    </div>
                </div>
                <div id="invoice-outer" class="border p-2 rounded">
                <?php
                    // Fetch project items
                    $project_items = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * 
                            FROM {$wpdb->prefix}bms_project_items 
                            WHERE project_id = %d",
                            $project_id
                        )
                    );

                // Fetch project totals
                $project_totals = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * 
                                FROM {$wpdb->prefix}bms_projects 
                                WHERE id = %d",
                        $project_id
                    )
                );
                ?>

                    <div class="pr_inv_container p-2">
                        <!-- Project Items Table -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="row mb-2 bg-dark text-white p-2">
                                    <div class="col-md-2">Type</div>
                                    <div class="col-md-1">Length</div>
                                    <div class="col-md-1">Width</div>
                                    <div class="col-md-2">Area</div>
                                    <div class="col-md-1">#</div>
                                    <div class="col-md-2">Unit Price</div>
                                    <div class="col-md-3">Price (€)</div>
                                </div>

                                <?php if (!empty($project_items)): ?>
                                    <?php foreach ($project_items as $item): ?>
                                        <div class="row mb-2 p-2 border-bottom">
                                            <div class="col-md-2"><?php echo esc_html($item->type); ?></div>
                                            <div class="col-md-1"><?php echo esc_html($item->mikos); ?></div>
                                            <div class="col-md-1"><?php echo esc_html($item->platos); ?></div>
                                            <div class="col-md-2"><?php echo esc_html($item->area); ?></div>
                                            <div class="col-md-1"><?php echo esc_html($item->quantity); ?></div>
                                            <div class="col-md-2"><?php echo esc_html(number_format($item->unit_price, 2)); ?></div>
                                            <div class="col-md-3"><?php echo esc_html(number_format($item->price, 2)); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row p-2">
                                        <div class="col-md-12">No project items found.</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Project Totals -->
                        <?php if ($project_totals): ?>
                            <div class="row mt-4 light-yellow">
                                <div class="col-md-9 border-bottom">
                                    <b>Total before VAT:</b>
                                </div>
                                <div class="col-md-3 border-bottom">
                                    <b><?php echo number_format($project_totals->total_before_vat, 2); ?></b>
                                </div>

                                <div class="col-md-9 border-bottom">
                                    <b>VAT (<?php echo esc_html($project_totals->vat); ?>%):</b>
                                </div>
                                <div class="col-md-3 border-bottom">
                                    <b><?php echo number_format($project_totals->vat_price, 2); ?></b>
                                </div>

                                <div class="col-md-9 border-bottom">
                                    <b>Total after VAT:</b>
                                </div>
                                <div class="col-md-3 border-bottom">
                                    <b><?php echo number_format($project_totals->total_after_vat, 2); ?></b>
                                </div>

                                <?php if (!empty($project_totals->discount_description)): ?>
                                    <div class="col-md-9 border-bottom">
                                        <b>Discount: </b><?php echo esc_html($project_totals->discount_description); ?>
                                    </div>
                                    <div class="col-md-3 border-bottom">
                                        <b><?php echo number_format($project_totals->discount_val, 2); ?></b>
                                    </div>

                                    <div class="col-md-9 border-bottom">
                                        <b>Total after Discount:</b>
                                    </div>
                                    <div class="col-md-3 border-bottom">
                                        <b><?php echo number_format($project_totals->total_after_discount, 2); ?></b>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div><!-- end of invoice outer -->


                <div class="row p-2">
                    <div class="col-md-12">
                        <p>&nbsp;</p>
                        <h3 style="border-bottom: 2px solid darkgray; display: inline-block;">PAYMENTS</h3>
                    </div>
                </div>

                <div id="payments-outer" class="border p-2 mb-3 rounded">
                    <?php
                    // Fetch total amount after discount for the project
                    $project_total_query = $wpdb->get_row($wpdb->prepare("SELECT total_after_discount FROM {$wpdb->prefix}bms_projects WHERE id = %d", $project_id));
                    $project_total = $project_total_query->total_after_discount;

                    // Fetch payments for the project
                    $payments = $wpdb->get_results($wpdb->prepare("SELECT description, payment_date, amount FROM {$wpdb->prefix}bms_balances WHERE rel_project = %d", $project_id));

                    // Calculate the total paid amount
                    $total_paid = 0;
                    foreach ($payments as $payment) {
                        $total_paid += $payment->amount;
                    }

                    // Calculate remaining amount
                    $remaining_amount = $project_total - $total_paid;
                    ?>

                    <!-- Button to show the Add Payment form -->
                    <div class="row mb-3 text-end">
                        <div class="col">
                            <button class="btn btn-primary btn-sm" id="add-payment-btn">Add Payment</button>
                        </div>
                    </div>

                    <!-- Add Payment form (initially hidden) -->
                    <div class="row mb-4 m-0 d-none border" id="add-payment-form">
                        <div class="col-md-12">
                            <form method="post" action="">
                                <div class="row m-0">
                                    <div class="col-md-5 mb-3">
                                        <label for="payment-description" class="form-label">Payment Description</label>
                                        <input type="text" class="form-control" id="payment-description" name="payment_description" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="payment-amount" class="form-label">Amount</label>
                                        <input type="text" class="form-control only-num" id="payment-amount" name="payment_amount" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="payment-date" class="form-label">Date</label>
                                        <input type="date" class="form-control form-control-sm" id="payment-date" name="payment_date" required>
                                    </div>
                                    <div class="col-md-2 mb-3 align-self-end">
                                        <input type="hidden" name="client_name" value="<?php echo esc_attr($project->client_name); ?>" />
                                        <button type="submit" name="save_pr_payment" class="btn btn-sm btn-success">SAVE</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Payments List -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row m-0 mb-2 fw-bold bg-dark text-white">
                                <div class="col-md-5">Payment Description</div>
                                <div class="col-md-3">Date</div>
                                <div class="col-md-2">Amount (€)</div>
                            </div>

                            <?php if ($payments) : ?>
                                <?php foreach ($payments as $payment) : ?>
                                    <div class="row m-0 mb-2 border-bottom">
                                        <div class="col-md-5"><?php echo esc_html($payment->description); ?></div>
                                        <div class="col-md-3">
                                            <?php echo esc_html(date_i18n('d-m-Y', strtotime(get_date_from_gmt($payment->payment_date)))); ?>
                                        </div>
                                        <div class="col-md-2"><?php echo number_format($payment->amount, 2); ?> </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="row">
                                    <div class="col-md-12">No payments found.</div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Total paid and remaining amount -->
                            <div class="row m-0 mt-4" style="background-color: #eff6f3;">
                                <div class="col-md-9 fw-bold">Total Paid:</div>
                                <div class="col-md-3"><?php echo number_format($total_paid, 2); ?></div>
                            </div>
                            <div class="row m-0" style="background-color: #fff5f5;">
                                <div class="col-md-9 fw-bold">Remaining Amount:</div>
                                <div class="col-md-3 text-danger"><?php echo number_format($remaining_amount, 2); ?></div>
                                <?php //badge coding
                        ?>
                            </div>
                        </div>
                    </div>

                </div><!--end of payments outer-->
            </div><!--end project-more row-->
            <div class="row">
                    <div class="col-md-6">
                        <button onclick="window.location.href='/projects/';" class="btn btn-light">Back to projects</button>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if ($project->status != 'COMPLETED') { ?>
                        <form name="complete_project_form" action="" method="post">
                            <button type="submit" name="complete_project" class="btn btn-light">Complete project</button>        
                        </form>
                        <?php } ?>
                    </div>
                </div>
        </div><!--end of bms-container -->  
        <?php
                return; // Stop here if viewing a specific project
        } else {
            echo "<div class='alert alert-warning'>Project not found!</div>";
        }
    }

    // Show the list of projects if no specific project is being viewed
    if (!$project_id) {
        if (get_transient('project_added_success')): ?>
                <div id="qu-added-success" class="alert alert-success" role="alert">
                    Project added successfully.
                </div>
                <?php delete_transient('project_added_success'); ?>
            <?php elseif ($error_message = get_transient('project_added_error')): ?>
                <div id="cl-added-error" class="alert alert-danger" role="alert">
                    Error Saving project: <?php echo esc_html($error_message); ?>
                </div>
                <?php delete_transient('project_added_error'); ?>
            <?php endif; ?>
        <div class="bms-container">
            <div class="row mt-2">
                <div class="d-grid gap-2 d-md-block text-end">
                    <button id="add-new-project-btn" class="btn btn-sm btn-success mb-2">Create project</button>
                </div>
            </div>
            
            <!--===========NEW PROJECT ========================-->
            <div class="row py-2 bordered-block" id="add-project-block" >
                <form id="project-form" method="post" action="/projects/">
                    <div class="row mb-3">
                    <div class="col-md-12 mb-2 mb-md-0">
                        <label for="client_select" class="form-label">Client</label>
                        <select name="client_select" id="client_select" class="form-select me-2" required>
                            <option value="new">New client</option>
                            <!-- PHP code to loop through clients -->
                            <?php if (!isset($location) || (isset($location) && $location == 'default')) { ?>
                            <option value="0" selected>Select client</option>
                            <?php } ?>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo esc_attr($client->id); ?>"><?php echo esc_html($client->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_client" class="form-control d-none mt-1" id="new_client_input" placeholder="'New client name">
            
                    </div>
                    </div>
                    <div class="row mb-3 d-none">
                        <div class="col-12">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" required></textarea>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-block">
                        <input type="submit" name="bms_add_project" id="bms_add_project" class="btn btn-primary" value="Save project" />
                        <button type="reset" class="btn btn-secondary" onclick="location.reload();">Cancel</button>
                    </div>
                </form>
            </div><!--end of create project block-->
            <?php
            /** ============  show projects ======================================================== */
            $projects_table = $wpdb->prefix . 'bms_projects';
            $clients_table = $wpdb->prefix . 'bms_clients';
            $projects = $wpdb->get_results("SELECT q.*, c.name as client_name, c.id as client_id FROM $projects_table q LEFT JOIN $clients_table c ON q.client_id = c.id ORDER BY q.id DESC");
            ?>

            <!-- Project List -->
            <div id="list-projects">
                <div class="row gx-0 my-3">
                    <div class="col-md-6 d-flex align-items-center p-md-0">
                        <input type="text" id="project-search" class="form-control" placeholder="Search..." style="font-size:0.9em;">
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-grid gap-2 d-md-block">
                            <span class="text-center">Sort By:</span>
                            <button id="sort-project-no" class="btn btn-sm btn-dark">Recent first</button>
                            <button id="sort-client-name" class="btn btn-sm btn-secondary">Client name</button>
                        </div>
                    </div>
                </div>

                <div id="data-list">
                    <?php foreach ($projects as $project) :
                        //load material/stock
                        //status functions
                        switch ($project->status) {
                            case 'START':
                                $badge_col = 'bg-dark';
                                $bgcol = '#fbfbfb';
                                break;
                            case 'PROGRESS':
                                $badge_col = 'bg-success';
                                $bgcol = '#f8fff8';
                                break;
                            case 'COMPLETED':
                                $badge_col = 'bg-primary';
                                $bgcol = '#f7fcff';
                                break;
                            case 'ERROR':
                                $badge_col = 'bg-warning';
                                $bgcol = '#fffdf6';
                                break;
                            default:
                                $badge_col = 'bg-secondary';
                                $bgcol = '#f8f9fa';
                                break;
                        }
                        $status = "<span class='badge d-block w-100 $badge_col'>".$project->status.'</span>';
                        ?>
                        <div class="project-row border rounded-top mb-1">
                            <div class="row gx-0">
                            <div class="col-md-3 align-content-center p-2">
                                <?php echo esc_html($project->client_name); ?>
                            </div>
                            <div class="col-md-5 align-content-center p-2">
                                <?php echo esc_html(mb_strimwidth($project->description, 0, 30, '...')); ?>
                            </div>
                            <div class="col-md-2 align-content-center text-center p-2">
                                <?php echo wp_kses_post($status); ?>
                            </div>
                            <div class="col-md-1 align-content-center text-center">
                                <a href="#" style="color:unset;" id="editDescriptionLink" data-initial="<?php echo esc_attr($project->description); ?>" data-project-id="<?php echo esc_attr($project->id); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <form method="post" id="delProjectForm" action="" style="display:inline;">
                                <a href="#" id="delProjectLink" style="color:unset;">
                                    <span class="dashicons dashicons-trash delete-icon-project"></span>
                                    <input type="hidden" name="delete-project-submit" id="del_project" value="<?php echo esc_attr($project->id); ?>" />
                                </a>
                                </form>
                            </div>
                            <div class="col-md-1 align-content-center text-end p-2">
                                <a href="\projects\?project_id=<?php echo esc_html($project->id); ?>" class="btn btn-sm btn-light">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </a>
                                <!--span class="toggle-icon dashicons dashicons-arrow-down-alt2" style="cursor: pointer;"></span-->
                            </div>
                            </div><!--end row-->
                        </div><!--end project row-->
                    <?php endforeach; ?>
                </div><!--end data-list-->
            </div><!-- end list-projects -->
        </div><!--end of bms-container --> 
        <!-- Modal -->
        <div class="modal fade" id="editDescriptionModal" tabindex="-1" aria-labelledby="editDescriptionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editDescriptionModalLabel">Edit Project Description</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="" id="editDescriptionForm">
                        <div class="mb-3">
                            <label for="description" class="form-label">Project Description</label>
                            <input type="text" class="form-control" id="mod_description" name="mod_description" value="<?php echo esc_attr($project->description); ?>">
                        </div>
                        <input type="hidden" name="mod_project_id" id="mod_project_id" value="" />
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" form="editDescriptionForm" class="btn btn-primary" name="update_description">Update</button>
                    </div>
                </div>
            </div>
        </div> 
    <?php } ?>
