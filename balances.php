<?php

// Fetch projects for the modal search project details popup
$projects_table = $wpdb->prefix . 'bms_projects';
$clients_table = $wpdb->prefix . 'bms_clients';

$years_list = $wpdb->get_col("
    SELECT DISTINCT YEAR(`payment_date`)
    FROM {$wpdb->prefix}bms_balances
    ORDER BY YEAR(`payment_date`) DESC
");

$proj_results = $wpdb->get_results("
    SELECT p.*, c.name as client_name
    FROM $projects_table p
    JOIN $clients_table c ON p.client_id = c.id
    WHERE p.status <> 'COMPLETED'
    ORDER BY p.id DESC
");
?>
    <div class="bms-container">
        <div class="row mt-2">
            <div class="d-grid gap-2 d-md-block text-end">
            <button id="add-new-in-btn" class="btn btn-sm btn-success mb-2">Add income</button>
            <button id="add-new-out-btn" class="btn btn-sm btn-warning mb-2">Add expense</button>
            </div>
        </div>

        <div class="row py-2 bordered-block" id="add-in-block">
            <form id="transaction-form-in" method="post" action="">
                <div class="row mb-3 align-items-end">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label for="rel_project" class="form-label">Related project</label>
                        <input type="text" name="rel_project" id="rel_project" class="form-control" readonly />
                        <input type="hidden" name="rel_project_id" id="rel_project_id" value="0" />
                    </div>
                    <div class="col-md-2">
                    <!--button id="update-rel-inv-fields" class="btn btn-sm btn-secondary">Ενημέρωση</button-->
                    <!--button id="find-inv" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#projectModal">Ψάξε</button-->
                    <button id="find-inv" class="btn btn-sm btn-secondary">Search</button>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo esc_attr($current_date); ?>" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                    <label for="description" class="form-label">Balance Description</label>
                    <input type="text" name="description" id="description" class="form-control" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="payer" class="form-label">Client name</label>
                        <input type="text" name="payer" id="payer" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="text" name="amount" id="amount" class="form-control" required />
                    </div>
                </div>
                <div id="existing-payments" class="row mb-3 d-none">
                    <div class="col-md-6">
                        Previous payments:
                    </div>
                    <div class="col-md-6">
                        Already paid:
                        <span class="already"></span>
                        Remaining:
                        <span class="remain"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="type_of_payment" class="form-label">Type of Payment</label>
                        <select name="type_of_payment" id="type_of_payment" class="form-select" required>
                            <option value="general">General</option>
                            <option value="pr_inv">Project</option>
                            <!--option value="invoice">Invoice</option-->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="pay-method" class="form-label">Payment method</label>
                        <select id="pay-method" name="payment_method" class="form-select">
                            <option value='Cash' selected>Cash</option>
                            <option value='Check' >Check</option>
                            <option value='Transfer' >Transfer</option>
                            <option value='Other' >Other</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <input type="hidden" name="record_id" id="record_id_in" value="0">
                        <input type="submit" name="add-in-transaction" class="btn btn-success" value="Save" />
                        <button type="reset" class="btn btn-secondary" onclick="location.reload();">Cancel</button>
                    </div>
                </div>
            </form>
        </div><!--end of create in transaction block-->

        <!-- ======  CREATE EXPENCES FORM ===================-->
        <div class="row py-2 bordered-block" id="add-out-block">
            <form id="transaction-form-out" method="post" action="">
                <div class="row mb-3 align-items-end">
                    <div class="col-md-6">
                        <label for="rel_invoice" class="form-label">Invoice no</label>
                        <input type="text" name="rel_invoice" id="rel_invoice" class="form-control" />
                    </div>
                    <div class="col-md-6">
                    
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                    <label for="description" class="form-label">Payment description</label>
                    <input type="text" name="description" id="description" class="form-control" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="payer" class="form-label">Receipient name</label>
                        <input type="text" name="payee" id="payer" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="text" name="amount" id="amount" class="form-control" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="type_of_payment" class="form-label">Type of Payment</label>
                        <select name="type_of_payment" id="type_of_payment" class="form-select" required>
                            <option value="general">General</option>
                            <option value="invoice">Invoice</option>
                            <option value="vat">VAT</option>
                            <option value="social_insurance">Soc. insurance</option>
                            <option value="salary">Salary</option>
                            <option value="penalty">Penalty</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_date" class="form-label">Date of payment</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo esc_attr($current_date); ?>" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <input type="hidden" name="record_id" id="record_id_out" value="0">
                        <input type="submit" name="add-out-transaction" class="btn btn-warning" value="Save" />
                        <button type="reset" class="btn btn-secondary" onclick="location.reload();">Cancel</button>
                    </div>
                </div>
            </form>
        </div><!--end of create OUTtransaction block-->
        <?php


// if type filter is selected
$selected_type = isset($_GET['paytype']) ? sanitize_text_field($_GET['paytype']) : 'all';

// Initialize the query
$query = "SELECT * FROM {$wpdb->prefix}bms_balances WHERE 1=1";
$query_params = [];

// Filter by type
if ($selected_type !== 'all') {
    if (strpos($selected_type, 'in') === 0) {
        $query .= ' AND in_out = 1';
        if ($selected_type !== 'incoming') {
            $query .= ' AND type_of_payment = %s';
            $query_params[] = substr($selected_type, 2); // Remove 'in' prefix
        }
    } elseif (strpos($selected_type, 'out') === 0) {
        $query .= ' AND in_out = 2';
        if ($selected_type !== 'outgoing') {
            $query .= ' AND type_of_payment = %s';
            $query_params[] = substr($selected_type, 3); // Remove 'out' prefix
        }
    }
}
// Define date ranges for each term moved to bms-frontend fn
$view_range = view_range();

if($view_range != 'all'){
    // Apply date filter if current term is selected
    $query .= years_query($view_range, 'payment_date');
}

// Finalize query with order
$query .= ' ORDER BY payment_date DESC, id DESC';

// Execute query
$results = $wpdb->get_results($wpdb->prepare($query, $query_params), ARRAY_A);

// Calculate totals
$total_earnings = 0;
$total_expenses = 0;

foreach ($results as $result) {
    if ($result['in_out'] == 1) {
        $total_earnings += $result['amount'];
    } else {
        $total_expenses += $result['amount'];
    }
}

$balance = $total_earnings - $total_expenses;
$balance_status = $balance >= 0 ? 'Profit' : 'Loss';
$balance_class = $balance >= 0 ? 'text-success bg-secondary-subtle' : 'text-danger bg-secondary-subtle';

//get the type of payment to make the select filter
$type_in = $wpdb->get_results("SELECT DISTINCT type_of_payment FROM {$wpdb->prefix}bms_balances WHERE in_out = 1");
$type_out = $wpdb->get_results("SELECT DISTINCT type_of_payment FROM {$wpdb->prefix}bms_balances WHERE in_out = 2");

?>

        <div class="balances-container">
            <div class="row">
                <div class="col-auto">
                    <label for="type-select">Type:</label>
                    <select id="type-select" class="form-select">
                        <option value="all" <?php selected('all', $selected_type); ?>>All</option>
                        <?php
                        if (!empty($type_in)) {
                            echo "<option value='incoming' ".selected('incoming', $selected_type).">In - ALL</option>";
                            foreach ($type_in as $in) {
                                switch ($in->type_of_payment) {
                                    case "pr_inv":
                                        $in_title = "Project";
                                        break;
                                    default:
                                        $in_title = "General";
                                }
                                echo "<option value='in".$in->type_of_payment."' ".selected('in'.$in->type_of_payment, $selected_type).">--$in_title</option>";
                            }
                        }
                        if (!empty($type_out)) {
                            echo "<option value='outgoing' ".selected('outgoing', $selected_type).">In - ALL</option>";
                            foreach ($type_out as $out) {
                                switch ($out->type_of_payment) {
                                    case "vat":
                                        $out_title = "VAT";
                                        break;
                                    case "social_insurance":
                                        $out_title = "Social Insurance";
                                        break;
                                    case "salary":
                                        $out_title = "Salary";
                                        break;
                                    case "penalty":
                                        $out_title = "Penalty";
                                        break;
                                    default:
                                        $out_title = "General";
                                }
                                echo "<option value='out".$out->type_of_payment."' ".selected('out'.$out->type_of_payment, $selected_type).">--$out_title</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <input type="text" id="search-balances" class="form-control" placeholder="Search balances">
                </div>
            </div>
            <div class="row mt-3" style="font-size:0.9em;">
                <div class="col-md-11">
                <?php
                    echo years_links($years_list, $view_range, '/balances/');
                ?>
                </div>
                <div class="col-md-1 text-end"> <!-- Align to the right side -->
                    <a href="#" id="print-table" class="dashicons dashicons-printer" style="color:unset;"></a> <!-- Dashicons printer icon as a clickable link -->
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-hover" id="balances-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>In/Out</th>
                                    <th>Description</th>
                                    <th>Rel. proj.</th>
                                    <th>Pay to/from</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Pay date</th>
                                    <th>Functions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach ($results as $key => $result) : 
                                        $in_descr = esc_html($result['description']);
                                        $rel_proj_id = intval($result['rel_project']);
                                        if ($rel_proj_id == 0){
                                            $rel_project_status =  '<span class="dashicons dashicons-minus" style="color: gray;"></span>';
                                        }else{
                                             $rel_project_status = '<span class="dashicons dashicons-saved" style="color: green;"></span>';
                                             $proj_descr = $wpdb->get_var( $wpdb->prepare(
                                                 "SELECT description FROM {$wpdb->prefix}bms_projects WHERE id = %d",
                                                 $rel_proj_id
                                             ) );
                                             $in_descr .= "<span style='font-size:0.9em;color:#999;'><br><u>Project</u>: ".$proj_descr."</span>";
                                        }
                                          ?>
                                    <!--tr class="<?php echo $result['in_out'] == 1 ? 'table-success' : 'table-danger'; ?>"-->

                                    <tr>
                                        <td><?php echo esc_html($key + 1); ?></td>
                                        <td><?php echo $result['in_out'] == 1 ? "<span style='color:green;' class=\"dashicons dashicons-arrow-down-alt\"></span>" : "<span style='color:red;' class=\"dashicons dashicons-arrow-up-alt\"></span>"; ?></td>
                                        <td><?php echo $in_descr; ?></td>
                                        <td><?php echo $rel_project_status; ?></td>
                                        <td><?php echo esc_html($result['payer_payee']); ?></td>
                                        <td><?php echo esc_html($result['amount']); ?></td>
                                        <td><?php echo esc_html($result['type_of_payment']); ?></td>
                                        <td><?php echo esc_html($result['payment_date']); ?></td>
                                        <td>
                                            <span class="dashicons dashicons-trash delete-icon" data-id="<?php echo esc_attr($result['id']); ?>" style="cursor: pointer;"></span>
                                            <span class="dashicons dashicons-edit edit-icon" data-inout="<?php echo esc_attr($result['in_out']); ?>" data-id="<?php echo esc_attr($result['id']); ?>" style="cursor: pointer;"></span>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div><!--end of responsive table-->
                </div>
            </div>
            <div class="row mt-3 mx-0">
                <div class="bg-success col-md-4 d-flex align-items-center">
                    <h5 class="text-white p-2">Total - Incomings: <?php echo number_format($total_earnings, 2); ?></h5>
                </div>
                <div class="bg-danger-subtle col-md-4 d-flex align-items-center">
                    <h5 class="p-2">Total - Oougoings: <?php echo number_format($total_expenses, 2); ?></h5>
                </div>
                <div class="<?php echo esc_attr($balance_class); ?> col-md-4 d-flex align-items-center">
                    <h5 class="p-2"><?php echo esc_html($balance_status); ?>: <?php echo esc_html(number_format(abs($balance), 2)); ?></h5>
                </div>
            </div>
        </div><!--end balances-container-->
    </div><!--end outer-container-->

    <!-- Modal for Project Selection -->
    <div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalLabel">Select Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="searchProject" class="form-control mb-3" placeholder="Search Projects">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client Name</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Select</th>
                            </tr>
                        </thead>
                        <tbody id="projectList">
                        <?php foreach ($proj_results as $row) :
                            /** Search for related invoices for this invoice */
                            $rel_query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bms_balances WHERE rel_project = $row->id");
                            $already_paid = 0;
                            $full_amount = $row->total_after_discount;//make it total_after_vat and the after discount can be erased

                            foreach ($rel_query as $res):
                                $already_paid += $res->amount;
                            endforeach;
                            $remain_amount = $full_amount - $already_paid;
                            ?>
                            <tr>
                                <td><?php echo esc_html($row->description); ?></td>
                                <td><?php echo esc_html($row->client_name); ?></td>
                                <td><?php echo esc_html($row->creation_date); ?></td>
                                <td><?php echo esc_html($row->total_after_discount); ?></td>
                                <td><button class="btn btn-sm btn-outline-dark useProj" data-project-id="<?php echo esc_html($row->id); ?>" data-remain="<?php echo esc_html($remain_amount); ?>" data-already="<?php echo esc_html($already_paid); ?>" data-description="<?php echo esc_html($row->description); ?>" data-client-name="<?php echo esc_html($row->client_name); ?>" data-amount="<?php echo esc_html($row->total_after_discount); ?>">Επέλεξε</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!--end of load invoices modal-->

   
    <?php