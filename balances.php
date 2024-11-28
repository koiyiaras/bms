<?php

// Fetch invoices for the popup
$invoices_table = $wpdb->prefix . 'bms_invoices';
$clients_table = $wpdb->prefix . 'bms_clients';

$query_load_inv = "
        SELECT i.*, c.name as client_name
        FROM $invoices_table i
        JOIN $clients_table c ON i.client_id = c.id
        ORDER BY i.id DESC
    ";

$inv_results = $wpdb->get_results($query_load_inv);
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
                    <div class="col-md-6 mb-2 mb-md-0">
                        <label for="rel_invoice" class="form-label">Related invoice</label>
                        <input type="text" name="rel_invoice" id="rel_invoice" class="form-control" readonly />
                    </div>
                    <div class="col-md-6">
                    <!--button id="update-rel-inv-fields" class="btn btn-sm btn-secondary">Ενημέρωση</button-->
                    <!--button id="find-inv" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#invoiceModal">Ψάξε</button-->
                    <button id="find-inv" class="btn btn-sm btn-secondary">Search</button>
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
                            <option value="invoice">Invoice</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo esc_attr($current_date); ?>" required />
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

    $current_year = gmdate('Y');

// Fetch available years
$years = $wpdb->get_col("SELECT DISTINCT YEAR(payment_date) FROM {$wpdb->prefix}bms_balances ORDER BY payment_date DESC");

$selected_year = isset($_GET['selyear']) ? sanitize_text_field($_GET['selyear']) : $current_year;
$selected_period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '';
$selected_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';


// Initialize the query
$query = "SELECT * FROM {$wpdb->prefix}bms_balances WHERE 1=1";
$query_params = [];

// Filter by year unless 'other_period' is selected
if ($selected_year !== 'other_period') {
    $query .= ' AND YEAR(payment_date) = %d';
    $query_params[] = intval($selected_year);
} else {
    // Adjust query based on the selected period
    switch ($selected_period) {
        case 'last_3_months':
            $query .= ' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)';
            break;
        case 'last_4_months':
            $query .= ' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH)';
            break;
        case 'last_6_months':
            $query .= ' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)';
            break;
        case 'last_2_years':
            $query .= ' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)';
            break;
        case 'all':
            // No additional filter needed for all results
            break;
        default:
            // Handle unexpected values
            $query .= ' AND 1=0'; // Invalid period, return no results
            break;
    }
}

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

?>

        <div class="balances-container">
            <div class="row">
                <div class="col-md-4">
                    <label for="year-select">Year:</label>
                    <select id="year-select" class="form-select">
                        <?php foreach ($years as $the_year) : ?>
                            <option value="<?php echo esc_attr($the_year); ?>" <?php selected($the_year, $selected_year); ?>><?php echo esc_html($the_year); ?></option>
                        <?php endforeach; ?>
                        <option value="other_period" <?php selected('other_period', $selected_year); ?>>Other range</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="period-select">Sel. range:</label>
                    <select id="period-select" class="form-select">
                        <option value="current_year" <?php selected('current_year', $selected_period); ?>>Current Year</option>
                        <option value="last_3_months" <?php selected('last_3_months', $selected_period); ?>>Last 3 Months</option>
                        <option value="last_4_months" <?php selected('last_4_months', $selected_period); ?>>Last 4 Months</option>
                        <option value="last_6_months" <?php selected('last_6_months', $selected_period); ?>>Last 6 Months</option>
                        <option value="last_2_years" <?php selected('last_2_years', $selected_period); ?>>Last 2 Years</option>
                        <option value="all" <?php selected('all', $selected_period); ?>>All Results</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="type-select">Type:</label>
                    <select id="type-select" class="form-select">
                        <option value="all" <?php selected('all', $selected_type); ?>>All</option>
                        <option value="incoming" <?php selected('incoming', $selected_type); ?>>In - ALL</option>
                        <option value="inGeneral" <?php selected('inGeneral', $selected_type); ?>>--General</option>
                        <option value="inInvoice" <?php selected('inInvoice', $selected_type); ?>>--Invoices</option>
                        <option value="outgoing" <?php selected('outgoing', $selected_type); ?>>Expens.-ALL</option>
                        <option value="outGeneral" <?php selected('outGeneral', $selected_type); ?>>--General</option>
                        <option value="outVat" <?php selected('outVat', $selected_type); ?>>--VAT</option>
                        <option value="outSocial_insurance" <?php selected('outSocial_insurance', $selected_type); ?>>--Soc.Ins.</option>
                        <option value="outSalary" <?php selected('outSalary', $selected_type); ?>>--Salary</option>
                        <option value="outPenalty" <?php selected('outPenalty', $selected_type); ?>>--Penalty</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <input type="text" id="search-balances" class="form-control" placeholder="Search balances">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-end"> <!-- Align to the right side -->
                    <a href="#" id="print-table" class="dashicons dashicons-printer"></a> <!-- Dashicons printer icon as a clickable link -->
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-hover" id="balances-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>In./Out.</th>
                                    <th>Descr.</th>
                                    <th>Rel. inv.</th>
                                    <th>Payment to/from</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Pay date</th>
                                    <th>Functions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $key => $result) : ?>
                                    <!--tr class="<?php echo $result['in_out'] == 1 ? 'table-success' : 'table-danger'; ?>"-->
                                    <tr>
                                        <td><?php echo esc_html($key + 1); ?></td>
                                        <td><?php echo $result['in_out'] == 1 ? "<span style='color:green;' class=\"dashicons dashicons-arrow-down-alt\"></span>" : "<span style='color:red;' class=\"dashicons dashicons-arrow-up-alt\"></span>"; ?></td>
                                        <td><?php echo esc_html($result['description']); ?></td>
                                        <td><?php echo esc_html($result['rel_invoice']); ?></td>
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

    <!-- Modal for Invoice Selection -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceModalLabel">Select Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="searchInvoice" class="form-control mb-3" placeholder="Search Invoices">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Client Name</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Select</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceList">
                        <?php foreach ($inv_results as $row) :
                            /** Search for related invoices for this invoice */
                            $rel_query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bms_balances WHERE rel_invoice = $row->id");
                            $already_paid = 0;
                            $full_amount = $row->total_after_discount;

                            foreach ($rel_query as $res):
                                $already_paid += $res->amount;
                            endforeach;
                            $remain_amount = $full_amount - $already_paid;
                            ?>
                            <tr>
                                <td><?php echo esc_html($row->invoice_no); ?></td>
                                <td><?php echo esc_html($row->client_name); ?></td>
                                <td><?php echo esc_html($row->product_description); ?></td>
                                <td><?php echo esc_html($row->creation_date); ?></td>
                                <td><?php echo esc_html($row->total_after_discount); ?></td>
                                <td><button class="btn btn-sm btn-outline-dark useInv" data-invoice-no="<?php echo esc_html($row->invoice_no); ?>" data-remain="<?php echo esc_html($remain_amount); ?>" data-already="<?php echo esc_html($already_paid); ?>" data-description="<?php echo esc_html($row->product_description); ?>" data-client-name="<?php echo esc_html($row->client_name); ?>" data-amount="<?php echo esc_html($row->total_after_discount); ?>">Επέλεξε</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!--end of load invoices modal-->

   
    <?php