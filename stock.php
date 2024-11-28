<?php
// if the "submit-cut" button is clicked
if (isset($_POST['submit-cut'])) {
    // Sanitize and get form data
    $out_mikos = floatval($_POST['out_mikos']);
    $out_platos = floatval($_POST['out_platos']);
    // Ternary if statement to set $date_use
    $date_use = (!empty($_POST['date']) && isset($_POST['date'])) ? sanitize_text_field($_POST['date']) : gmdate('Y-m-d');
    $client_s = $_POST['client_s'];
    $notes = sanitize_text_field($_POST['notes']);
    $material_src_id = intval($_POST['material-src']); // ID of the stock item being used


    if ($client_s == 'new') {
        $new_client = sanitize_text_field($_POST['new_client']);
        $new_project = sanitize_text_field($_POST['new_project']);

        // Check if new client name is not empty
        if (!empty($new_client)) {
            // Insert new client into the database
            $result_c = $wpdb->insert(
                $wpdb->prefix . 'bms_clients',
                [
                    'name' => $new_client,
                    'phone' => null,
                    'email' => null,
                    'address' => null,
                    'extra' => null
                ],
                [
                    '%s', // name
                    '%s', // phone
                    '%s', // email
                    '%s', // address
                    '%s'  // extra
                ]
            );

            // Check if the insert was successful
            if ($result_c === false) {
                // Log the error message
                $error_message = 'Failed to insert new client into the database: ' . $wpdb->last_error;
                echo '<div class="alert alert-danger">' . esc_html($error_message) . '</div>';
            } else {
                // Get the inserted client ID
                $client_id = $wpdb->insert_id;

                //for the new client add a new project
                $result_p = $wpdb->insert(
                    $wpdb->prefix . 'bms_projects',
                    [
                        'client_id' => $client_id,
                        'description' => $new_project,
                        'creation_date' => gmdate('Y-m-d'),
                        'vat' => null,
                        'plus_inc_vat' => null,
                        'vat_price' => null,
                        'total_before_vat' => null,
                        'total_after_vat' => null,
                        'discount_description' => null,
                        'discount_val' => null,
                        'total_after_discount' => null,
                        'status' => 'START'
                    ]
                );
                echo '<div class="alert alert-success">Client added successfully!</div>';
                //get the id of the newly created project
                $project_id = $wpdb->insert_id;
            }
        } else {
            echo '<div class="alert alert-danger">Error: Client name cannot be empty.</div>';
        }
    } else {
        //client select not new. Split '-' and get client and project details
        $new_project = sanitize_text_field($_POST['new_project']);
        $parts = explode('-', $client_s);
        $client_id = intval($parts[0]);
        $project_id = intval($parts[1]);
        //only if project_id = 0 instert new
        if ($project_id == 0) {
            $result_p = $wpdb->insert(
                $wpdb->prefix . 'bms_projects',
                [
                    'client_id' => $client_id,
                    'description' => $new_project,
                    'creation_date' => gmdate('Y-m-d'),
                    'vat' => null,
                    'plus_inc_vat' => null,
                    'vat_price' => null,
                    'total_before_vat' => null,
                    'total_after_vat' => null,
                    'discount_description' => null,
                    'discount_val' => null,
                    'total_after_discount' => null,
                    'status' => 'START'
                ]
            );
            $project_id = $wpdb->insert_id;
        }
    }

    // Define table names
    $table_stock_in = $wpdb->prefix . 'bms_stock_in';
    $table_stock_out = $wpdb->prefix . 'bms_stock_out';

    // Query the bms_stock_in table to get the data of the selected material
    $stock_in = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_stock_in WHERE id = %d",
        $material_src_id
    ));
    // print_r($stock_in);
    // Check if stock data exists
    if ($stock_in) {
        $in_mikos = floatval($stock_in->in_mikos);
        $in_platos = floatval($stock_in->in_platos);
        $material_id = intval($stock_in->material_id);

        // Compare the query results with POST data
        if ($in_mikos == $out_mikos && $in_platos == $out_platos) {
            // All content is used, delete the record
            $wpdb->delete($table_stock_in, ['id' => $material_src_id]);

            //add to out
            // Insert the used stock data into bms_stock_out table
            $wpdb->insert(
                $table_stock_out,
                [
                    'material_id' => $material_id,
                    'out_mikos' => $out_mikos,
                    'out_platos' => $out_platos,
                    'date' => $date_use,
                    'client_id' => $client_id,
                    'project_id' => $project_id,
                    'notes' => $notes,
                ]
            );

        } elseif ($in_mikos > $out_mikos) {
            // Partial use: update the in_mikos and insert into bms_stock_out
            // Calculate the new in_mikos value
            $new_in_mikos = $in_mikos - $out_mikos;

            // Update the existing bms_stock_in record with the new in_mikos value
            $wpdb->update(
                $table_stock_in,
                ['in_mikos' => $new_in_mikos], // Updated value
                ['id' => $material_src_id] // Where condition
            );

            // Insert the used stock data into bms_stock_out table
            $wpdb->insert(
                $table_stock_out,
                [
                    'material_id' => $material_id,
                    'out_mikos' => $out_mikos,
                    'out_platos' => $out_platos,
                    'date' => $date_use,
                    'client_id' => $client_id,
                    'project_id' => $project_id,
                    'notes' => $notes,
                ]
            );

            //if cut width is less that total material lenght then insert a new piece with the left width and length to our stock
            if ($in_platos > $out_platos) {
                $left_platos = $in_platos - $out_platos;

                $wpdb->insert(
                    $table_stock_in,
                    [
                        'material_id' => $material_id,
                        'in_mikos' => $out_mikos,
                        'in_platos' => $left_platos,
                        'date' => $date_use,
                        'notes' => $notes,
                    ]
                );

            }//end if smaller length
            // insert was successful message
            echo '<div class="alert alert-success">Stock cut added successfully!</div>';
        } else {
            // Handle cases where the usage is not valid or other edge conditions
            echo 'Invalid operation: The out dimensions cannot be greater than available stock.';
        }
    } else {
        echo 'Error: Stock item not found.';
    }
}//end isset cut -----------------------------------------------------

//if edit cut/out ========================================================
if (isset($_POST['submit-edit'])) {
    // Sanitize input
    $stock_out_id = intval($_POST['stock_out_id_edit']);
    $client_project = intval($_POST['client_project']);
    $parts = explode('-', $client_project);
    $client_id = intval($parts[0]);
    $project_id = intval($parts[1]);
    $notes = sanitize_text_field($_POST['notes_edit']);

    // Update the bms_stock_out table
    $updated = $wpdb->update(
        $wpdb->prefix . 'bms_stock_out',
        [
            'client_id' => $client_id,
            'project_id' => $project_id,
            'notes' => $notes
        ],
        ['id' => $stock_out_id]
    );

    // Check if update was successful
    if ($updated !== false) {
        echo '<div class="alert alert-success">Stock Out entry updated successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Failed to update Stock Out entry.</div>';
    }
}//end edit cut-out -----------------------------------------------------

// type filter =====================
if (isset($_POST['type_filter'])) {
    $selected_type = intval($_POST['type_filter']);

    // Update the database (assuming id=1 is the correct row in prefix_bms_company)
    $result = $wpdb->update(
        $wpdb->prefix . 'bms_company', // Table name
        ['last_stock_select' => $selected_type], // Update last_stock_select field
        ['id' => 1], // Where id=1
        ['%d'], // Data format for last_stock_select (integer)
        ['%d']  // Data format for id (integer)
    );
}//end type filter -------------------------------------

// if the "submit-in" ================================================
if (isset($_POST['submit-in'])) {
    // Sanitize and get form data
    $material_id = intval($_POST['material_id']);
    $in_quantity = intval($_POST['in_quantity']);
    $in_mikos = floatval($_POST['in_mikos']);
    $in_platos = floatval($_POST['in_platos']);
    $date = (!empty($_POST['date']) && isset($_POST['date'])) ? sanitize_text_field($_POST['date']) : gmdate('Y-m-d');
    $notes = sanitize_text_field($_POST['notes']);

    // Define the table name
    $table_stock_in = $wpdb->prefix . 'bms_stock_in';

    // Insert data into the bms_stock_in table loop quantity times
    for ($x = 0; $x < $in_quantity; $x++) {
        $insert_result = $wpdb->insert(
            $table_stock_in,
            [
                'material_id' => $material_id,
                'in_mikos' => $in_mikos,
                'in_platos' => $in_platos,
                'date' => $date,
                'notes' => $notes,
            ],
            [
                '%d',    // material_id
                '%f',    // in_mikos (Length)
                '%f',    // in_platos (Width)
                '%s',    // date
                '%s'     // notes
            ]
        );
    }//end loop quantity

    // Check if the insert was successful
    if ($insert_result) {
        echo '<div class="alert alert-success">Stock added successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error: Failed to add stock. Please try again.</div>';
    }
}
//load session for material type
$last_type_select = $wpdb->get_var($wpdb->prepare("SELECT last_stock_select FROM {$wpdb->prefix}bms_company WHERE id = 1"));
$filter_type_query = ($last_type_select == 0) ? '' : "WHERE m.id = $last_type_select";

//materials
$table_materials = $wpdb->prefix . 'bms_materials';
$query = "SELECT id, type FROM $table_materials";
$materials = $wpdb->get_results($query);

//clients
$table_clients = $wpdb->prefix . 'bms_clients';
$query = "SELECT * FROM $table_clients ORDER BY id DESC";
$clients =  $wpdb->get_results($query);

//projects
$table_projects = $wpdb->prefix . 'bms_projects';
$query = "SELECT * FROM $table_projects ORDER BY id DESC";
$projects =  $wpdb->get_results($query);

// Fetch stock ins data
$table_stock_in = $wpdb->prefix . 'bms_stock_in';
$term_filter = 'all';

// Determine date range based on term filter
$current_year = gmdate('Y');
$current_month = gmdate('n');
$start_date = '';
$end_date = '';

// Define date ranges for each term
if ($term_filter == 'current') {
    if ($current_month <= 3) {
        // First Term: January 1 - March 31
        $start_date = "$current_year-01-01";
        $end_date = "$current_year-03-31";
    } elseif ($current_month <= 6) {
        // Second Term: April 1 - June 30
        $start_date = "$current_year-04-01";
        $end_date = "$current_year-06-30";
    } elseif ($current_month <= 9) {
        // Third Term: July 1 - September 30
        $start_date = "$current_year-07-01";
        $end_date = "$current_year-09-30";
    } else {
        // Fourth Term: October 1 - December 31
        $start_date = "$current_year-10-01";
        $end_date = "$current_year-12-31";
    }
}

// Base query
$query_in = "SELECT s.id, s.date, s.material_id, m.type AS material_type, s.in_mikos, s.in_platos, s.notes
    FROM $table_stock_in s
    JOIN $table_materials m ON s.material_id = m.id
    $filter_type_query
";

// Apply date filter if current term is selected
if ($term_filter == 'current') {
    $query_in .= " WHERE s.date BETWEEN '$start_date' AND '$end_date'";
}

$query_in .= ' ORDER BY s.date DESC';
$stock_ins =  $wpdb->get_results($query_in);

/** OUTS ====================================== */
$table_stock_out = $wpdb->prefix . 'bms_stock_out';

// Base query
$query_out = "SELECT s.*, m.type AS material_type
            FROM $table_stock_out s
            JOIN $table_materials m ON s.material_id = m.id
            $filter_type_query
            ";

// Apply date filter if current term is selected
if ($term_filter == 'current') {
    $query_out .= " WHERE s.date BETWEEN '$start_date' AND '$end_date'";
}

$query_out .= ' ORDER BY s.date ASC';
$stock_outs = $wpdb->get_results($query_out);

?>

<!-- Bootstrap 5 Styling for Stock Management Page -->
<div class="bms-container">
    <!-- Buttons to Add or Use Stock -->
    <div class="d-flex justify-content-end mb-3">
        <button id="addStockBtn" class="btn btn-sm btn-secondary me-2">Add stock</button>
    </div>

    <!-- Add Stock Form -->
    <div id="add_stock" class="card p-3 mb-3 d-none">
        <h5>Add Stock</h5>
        <form id="addStockForm" method="post" >
            <div class="mb-3">
                
                <?php //show the initial only when type is all
                    if($last_type_select == 0) {
                        echo "<label for='material_id' class='form-label'>Select type</label>";
                        echo "<select id='material_id' name='material_id' class='form-select' >";
                        echo "<option value='' selected>Select</option>";
                        foreach ($materials as $material):
                            echo "<option value='".esc_attr($material->id)."'>".esc_html($material->type)."</option>";
                        endforeach;
                        echo '</select>';
                    } else {
                        //load the preselected type and disable option get material id where type = this
                        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}bms_materials WHERE id = %d", $last_type_select);
                        $mat = $wpdb->get_row($query);
                        echo "<label for='r-only-text' class='form-label'>Type</label>";
                        echo "<input type='text' class='form-control' id='r-only-text' name='r-only-text' value='" . esc_attr($mat->type) . "' readonly />";
                        echo "<input type='hidden' id='material_id' name='material_id' value='" . esc_attr($mat->id) . "' />";
                    }
?>
            </div>
            <div class="mb-3">
                <label for="in_quantity" class="form-label">Quantity</label>
                <input type="number" id="in_quantity" name="in_quantity" value="1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="in_mikos" class="form-label">Length (Μήκος)</label>
                <input type="text" id="in_mikos" name="in_mikos" value="<?php echo ($last_type_select == 0) ? '' : esc_attr($mat->mikos); ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="in_platos" class="form-label">Width (Πλάτος)</label>
                <input type="text" id="in_platos" name="in_platos" value="<?php echo ($last_type_select == 0) ? '' : esc_attr($mat->platos); ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date (Ημ. παραλαβής)</label>
                <input type="date" name="date" class="date_use form-control" >
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <input type="text" id="notes" name="notes" class="form-control">
            </div>
            <button type="submit" name="submit-in" class="btn btn-success">Submit</button>
        </form>
    </div>

    <!-- Stock Filtering and Display -->
    <div id="stock_container">
        <!-- Filtering -->
         <div class="row mb-3">
            <div class="col-md-3">
                <form method="post" id="select_type_form" action="" >
                    <select id="type_filter" name="type_filter" class="form-select">
                        <?php
    echo "<option value='0'" . ($last_type_select == 0 ? ' selected' : '') . '>TYPE: ALL</option>';
foreach ($materials as $material):
    echo "<option value='" . esc_attr($material->id) . "'" . ($last_type_select == $material->id ? ' selected' : '') . '>' . esc_html($material->type) . '</option>';
endforeach;
?>
                    </select>
                </form>
            </div>
            <div class="col-md-3 mb-3">
                <select id="indate_filter" class="form-select">
                    <option value="all" selected>In date - All</option>
                    <?php
                    // Collect unique dates
                    $unique_dates = array_unique(array_map(function ($stock_in) { return $stock_in->date; }, $stock_ins));
foreach ($unique_dates as $date):
    $formatted_date = DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y');
    ?>
                        <option value="<?php echo esc_attr($formatted_date); ?>"><?php echo esc_html($formatted_date); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div><!--end of filter row-->
        <!-- Stock In Table -->
        <div class="text-bg-success pt-2 ps-2">
            <h4 class="text-center text-white" >Stock Ins</h4>
        </div>
        <table id="stock-in-table" class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>In date</th>
                    <th>Length</th>
                    <th>Width</th>
                    <th>Area</th>
                    <th>--</th>
                </tr>
            </thead>
            <tbody>
                <?php $in_total_area = 0; ?>
                <?php foreach ($stock_ins as $index => $stock_in): ?>
                    <?php
    $area = $stock_in->in_mikos * $stock_in->in_platos;
                    $in_total_area += $area;
                    $formatted_date = DateTime::createFromFormat('Y-m-d', $stock_in->date)->format('d-m-Y');
                    ?>
                    <tr>
                        <td><?php echo esc_html($index + 1); ?></td>
                        <td><span class="badge bg-success"><?php echo esc_html($stock_in->material_type); ?></span></td>
                        <td class="indate"><?php echo esc_html($formatted_date); ?></td>
                        <td><?php echo esc_html($stock_in->in_mikos); ?></td>
                        <td><?php echo esc_html($stock_in->in_platos); ?></td>
                        <td><?php echo esc_html($area); ?></td>
                        <td>
                            <button 
                                class="btn btn-sm btn-warning cut-this" 
                                data-inid="<?php echo esc_attr($stock_in->id); ?>" 
                                data-materialname="<?php echo esc_attr($stock_in->material_type); ?>"
                                data-mikos="<?php echo esc_attr($stock_in->in_mikos); ?>"
                                data-platos="<?php echo esc_attr($stock_in->in_platos); ?>"
                            >
                                Cut
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Total Area</th>
                    <th><?php echo esc_html($in_total_area); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <p>&nbsp;</p>
        <!-- Stock Out Table -->
        <div class="text-bg-warning pt-2 ps-2">
            <h4 class="text-center">Stock Outs</h4>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Lenght</th>
                    <th>Width</th>
                    <th>Area</th>
                    <th>Client-Project</th>
                    <th>--</th>
                </tr>
            </thead>
            <tbody>
                <?php $out_total_area = 0; ?>
                <?php foreach ($stock_outs as $index => $stock_out): ?>
                    <?php
                        $area = $stock_out->out_mikos * $stock_out->out_platos;
                    $out_total_area += $area;
                    $client_name = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT name FROM {$wpdb->prefix}bms_clients WHERE id = %d",
                            $stock_out->client_id
                        )
                    );
                    $project_name = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT description FROM {$wpdb->prefix}bms_projects WHERE id = %d",
                            $stock_out->project_id
                        )
                    );
                    ?>
                    <tr>
                        <td data-stockoutid="<?php echo esc_attr($stock_out->id); ?>" data-note="<?php echo esc_attr($stock_out->notes); ?>"><?php echo esc_html($index + 1); ?></td>
                        <td><span class="badge bg-success"><?php echo esc_html($stock_out->material_type); ?></span></td>
                        <td><?php echo esc_html($stock_out->out_mikos); ?></td>
                        <td><?php echo esc_html($stock_out->out_platos); ?></td>
                        <td><?php echo esc_html($area); ?></td>
                        <td data-projectid="<?php echo esc_attr($stock_out->project_id); ?>" data-clientid="<?php echo esc_attr($stock_out->client_id); ?>">
                            <?php echo ($stock_out->client_id == 0) ? '--' : esc_html($client_name) . '-' . esc_html($project_name); ?>
                        </td>
                        <td><button class="edit-out btn btn-sm btn-light">Edit</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Total Area</th>
                    <th><?php echo esc_html($out_total_area); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <!-- In Stock Calculation -->
        <div style="width:30%;background:#d98e71;padding-left:5px; padding-top:5px;">
            <h3>In Stock: <?php echo esc_html($in_total_area); ?>m<sup style="font-size:0.6em;">2</sup></h3>
        </div>
    </div>
</div>
<!-- cut Modal -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
          <form id="useStockForm" method="post">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalLabel">Use Stock </h5> <span class="material-name-span" style="padding-left:0.6em;padding-bottom:0.6em;"></span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Use Stock Form -->
                <div id="use_stock" class="p-3 mb-3">
                        <div class="mb-3">
                            <label for="out_mikos" class="form-label">Length </label>
                            <input type="text" id="out_mikos" name="out_mikos" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="out_platos" class="form-label">Width</label>
                            <input type="text" id="out_platos" name="out_platos" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <span class="toggle_date" style="cursor: pointer;">+ Date</span>
                            <input type="date" name="date" class="date_use form-control" style="display: none;">
                        </div>
                        <div class="mb-3">
                            <label for="client_s" class="form-label">Client - Project</label>
                            <select id="client_s" name="client_s" class="form-select">
                                <option value="" selected>None</option>
                                <option value="new">New client & project</option>
                                <!-- PHP code to loop through clients -->
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo esc_attr($client->id); ?>-0"><?php echo esc_html($client->name); ?> - New</option>
                                    <?php foreach ($projects as $project):
                                        if ($project->client_id == $client->id) { ?>
                                        <option value="<?php echo esc_attr($client->id); ?>-<?php echo esc_attr($project->id); ?>"><?php echo esc_html($client->name); ?> - <?php echo esc_html($project->description); ?></option>
                                    <?php }
                                        endforeach;
                                endforeach; ?>
                            </select>
                            <input type="text" name="new_client" class="form-control d-none mt-2" id="new_client_input" placeholder="'New client name">
                            <input type="text" name="new_project" class="form-control d-none mt-2" id="new_project_input" placeholder="'New project name">
                        </div>
                        <div class="mb-3">
                            <label for="notes_use" class="form-label">Notes</label>
                            <input type="text" id="notes_use" name="notes" class="form-control">
                        </div>
                        <input type="hidden" name="material-src" id="material-src" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="submit-cut" class="btn btn-success">Submit</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </form>
        </div>
    </div>
</div>
<!-- end cut Modal -->


<!-- Edit cut Modal -->
<div class="modal fade" id="editStockOutModal" tabindex="-1" aria-labelledby="editStockOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStockOutModalLabel">Edit Stock Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStockOutForm" method="post">
                    <input type="hidden" id="stock_out_id_edit" name="stock_out_id_edit">
                    <div class="mb-3">
                        <label for="client_project" class="form-label">Client-Project</label>
                        <select id="client_project" name="client_project" class="form-select" required>
                            <option value="0">--</option>
                            <!-- Dynamically load clients via PHP -->
                            <?php foreach ($clients as $client): ?>
                                    <?php foreach ($projects as $project):
                                        if ($project->client_id == $client->id) { ?>
                                        <option value="<?php echo esc_attr($client->id); ?>-<?php echo esc_attr($project->id); ?>"><?php echo esc_html($client->name); ?> - <?php echo esc_html($project->description); ?></option>
                                    <?php }
                                        endforeach;
                            endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes_edit" class="form-label">Notes</label>
                        <input type="text" id="notes_edit" name="notes_edit" class="form-control">
                    </div>
                    <button type="submit" name="submit-edit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- end Edit cut Modal -->