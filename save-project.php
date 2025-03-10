<?php

global $wpdb;

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
            'pay_method' => $_POST['payment_method'],
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

/** ==============Materials Costs================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matCost'])) {
    //error_log("passed");
    $project_id = intval($_GET['project_id']);
    $success = false;

    if (isset($_POST['mat_type2'])){
        for ($index = 0; $index < count($_POST['mat_type2']); $index++) {
            $mat_type = $_POST['mat_type2'][$index];
            $mikos = isset($_POST['new-item-length2'][$index]) ? floatval($_POST['new-item-length2'][$index]) : null;
            $platos = isset($_POST['new-item-width2'][$index]) ? floatval($_POST['new-item-width2'][$index]) : null;
            $area = ($mikos > 0 && $platos > 0) ? $mikos * $platos : null;
            $quantity = $_POST['pr_item-quantity2'][$index];
            $unit_price = $_POST['pr_unit-price2'][$index];
            $price = $_POST['pr_price2'][$index];

            // Prepare insert data
            $insert_data = [
                'project_id' => $project_id,
                'type' => $mat_type,
                'mikos' => $mikos,
                'platos' => $platos,
                'area' => $area,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'price' => $price,
            ];

            // Insert into project costs table
            $wpdb->insert(
                $wpdb->prefix . 'bms_project_costs', // Table name
                $insert_data                        // Data to insert
            );
            $success = true; // Set success flag to true
        }
    }//end of isset mat_type2

    // Step 2iii: Insert into bms_project_costs table
    if (isset($_POST['mat_type3'])){
        for ($index = 0; $index < count($_POST['mat_type3']); $index++) {
            $mat_type = $_POST['mat_type3'][$index];
            $quantity = $_POST['pr_item-quantity3'][$index];
            $unit_price = $_POST['pr_unit-price3'][$index];
            $price = $_POST['pr_price3'][$index];

            // Prepare insert data
            $insert_data = [
                'project_id' => $project_id,
                'type' => $mat_type,
                'mikos' => null,
                'platos' => null,
                'area' => null,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'price' => $price,
            ];

            // Insert into project costs table
            $wpdb->insert(
                $wpdb->prefix . 'bms_project_costs', // Table name
                $insert_data                         // Data to insert
            );
            $success = true; // Set success flag to true
        }
    }//end isset mat_type3

     // Step 2iii: Insert into bms_project_costs table
     if (isset($_POST['mat_type4'])){
        for ($index = 0; $index < count($_POST['mat_type4']); $index++) {
            $mat_type = $_POST['mat_type4'][$index];
            $quantity = $_POST['pr_item-quantity4'][$index];
            $unit_price = $_POST['pr_unit-price4'][$index];
            $price = $_POST['pr_price4'][$index];

            // Prepare insert data
            $insert_data = [
                'project_id' => $project_id,
                'type' => $mat_type,
                'mikos' => null,
                'platos' => null,
                'area' => null,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'price' => $price,
            ];

            // Insert into project costs table
            $wpdb->insert(
                $wpdb->prefix . 'bms_project_costs', // Table name
                $insert_data                         // Data to insert
            );
            $success = true; // Set success flag to true
        }
    }//end isset mat_type4

    // Show success message if insertion was successful
    if ($success) {
        echo '<div id="qu-added-success" class="alert alert-success" role="alert">Project costs added successfully.</div>';
    }
}

/** ==============Materials to pr_Inv================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matInv'])) {
    $project_id = intval($_GET['project_id']);
    if ($project_id) {
        // Step 0: Get extisting values
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bms_projects WHERE id = %d",
                $project_id
            )
        );
    
        //null values in $existing are becoming 0.0 so we are covered
        $existing_vat = $existing->vat_price;
        $existing_total_before_vat = $existing->total_before_vat;
        $existing_total_after_vat = $existing->total_after_vat;
        $existing_discount_val = $existing->discount_val;
        $set_status = ($existing->status == 'START')? 'PROGRESS' : $existing->status;

        // Step 1: Update the bms_projects table
        $vat = floatval($_POST['vat']);
        $plus_inc_vat = $_POST['plus-inc-vat'];
        $pr_price_vat = floatval($_POST['pr_price_vat']) + floatval($existing_vat);
        $pr_total1 = floatval($_POST['pr_total1']) + floatval($existing_total_before_vat);
        $pr_total2 = floatval($_POST['pr_total2']) + floatval($existing_total_after_vat);
        $discount_descr = (isset($_POST['discount_descr']))?sanitize_text_field($_POST['discount_descr']) : '';
        $discount_val = floatval($_POST['discount_val_pr']) + floatval($existing_discount_val);
        //$pr_total3 = $pr_total2 - $discount_val;

        // Prepare update data
        $update_data = [
            'vat' => $vat,
            'plus_inc_vat' => $plus_inc_vat,
            'vat_price' => $pr_price_vat,
            'total_before_vat' => $pr_total1,
            'total_after_vat' => $pr_total2,
            'discount_description' => $discount_descr,
            'discount_val' => $discount_val,
            'total_after_discount' => $pr_total2,//logic changed total afte discount is not used. with new logig this is redundant now
            'status' => $set_status,
        ];

        // Update the project in the database
        $wpdb->update(
            $wpdb->prefix . 'bms_projects', // Table name
            $update_data,                   // Data to update
            ['id' => $project_id]      // Where condition
        );

        //Check if it was any mat_type coming from stock outs
        // Step 2i: Insert into bms_project_items table
        if (isset($_POST['mat_type'])){
            foreach ($_POST['mat_type'] as $index => $c) {
                $mat_type = $_POST['mat_type'][$index];
                $mikos = isset($_POST['new-item-length'][$index]) ? floatval($_POST['new-item-length'][$index]) : null;
                $platos = isset($_POST['new-item-width'][$index]) ? floatval($_POST['new-item-width'][$index]) : null;
                $area = ($mikos > 0 && $platos > 0) ? $mikos * $platos : null;
                $quantity = $_POST['pr_item-quantity'][$index];
                $unit_price = $_POST['pr_unit-price'][$index];
                $price = $_POST['pr_price'][$index];

                // Prepare insert data
                $insert_data = [
                    'project_id' => $project_id,
                    'type' => $mat_type,
                    'mikos' => $mikos,
                    'platos' => $platos,
                    'area' => $area,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'price' => $price,
                ];

                // Insert into project items table
                $wpdb->insert(
                    $wpdb->prefix . 'bms_project_items', // Table name
                    $insert_data                        // Data to insert
                );
            }
        }//end isset mat_type
        // Step 2ii: Insert into bms_project_items table
        if (isset($_POST['mat_type2'])){
            for ($index = 0; $index < count($_POST['mat_type2']); $index++) {
                $mat_type = $_POST['mat_type2'][$index];
                $mikos = isset($_POST['new-item-length2'][$index]) ? floatval($_POST['new-item-length2'][$index]) : null;
                $platos = isset($_POST['new-item-width2'][$index]) ? floatval($_POST['new-item-width2'][$index]) : null;
                $area = ($mikos > 0 && $platos > 0) ? $mikos * $platos : null;
                $quantity = $_POST['pr_item-quantity2'][$index];
                $unit_price = $_POST['pr_unit-price2'][$index];
                $price = $_POST['pr_price2'][$index];

                // Prepare insert data
                $insert_data = [
                    'project_id' => $project_id,
                    'type' => $mat_type,
                    'mikos' => $mikos,
                    'platos' => $platos,
                    'area' => $area,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'price' => $price,
                ];

                // Insert into project items table
                $wpdb->insert(
                    $wpdb->prefix . 'bms_project_items', // Table name
                    $insert_data                        // Data to insert
                );
            }
        }//end of isstet mat_type2

        // Step 2iii: Insert into bms_project_items table
        if (isset($_POST['mat_type3'])){
            for ($index = 0; $index < count($_POST['mat_type3']); $index++) {
                $mat_type = $_POST['mat_type3'][$index];
                $quantity = $_POST['pr_item-quantity3'][$index];
                $unit_price = $_POST['pr_unit-price3'][$index];
                $price = $_POST['pr_price3'][$index];

                // Prepare insert data
                $insert_data = [
                    'project_id' => $project_id,
                    'type' => $mat_type,
                    'mikos' => null,
                    'platos' => null,
                    'area' => null,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'price' => $price,
                ];

                // Insert into project items table
                $wpdb->insert(
                    $wpdb->prefix . 'bms_project_items', // Table name
                    $insert_data                         // Data to insert
                );
            }
        }//end isset mat_type3

         // Step 2iii: Insert into bms_project_items table
         if (isset($_POST['mat_type4'])){
            for ($index = 0; $index < count($_POST['mat_type4']); $index++) {
                $mat_type = $_POST['mat_type4'][$index];
                $quantity = $_POST['pr_item-quantity4'][$index];
                $unit_price = $_POST['pr_unit-price4'][$index];
                $price = $_POST['pr_price4'][$index];

                // Prepare insert data
                $insert_data = [
                    'project_id' => $project_id,
                    'type' => $mat_type,
                    'mikos' => null,
                    'platos' => null,
                    'area' => null,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'price' => $price,
                ];

                // Insert into project items table
                $wpdb->insert(
                    $wpdb->prefix . 'bms_project_items', // Table name
                    $insert_data                         // Data to insert
                );
            }
        }//end isset mat_type4

        // Step 3: Update sthock_out if is done
        if (isset($_POST['mat_type'])){
        $wpdb->update(
            $wpdb->prefix . 'bms_stock_out', // Table name
            ['assigned' => '1'],        // Data to update
            ['project_id' => $project_id]      // Where condition
        );
        }   
        // Redirect or success message
        echo '<div id="qu-added-success" class="alert alert-success" role="alert">Project materials invoiced successfully.</div>';
    } else {
        echo 'Invalid project ID!';
    }
}
/** =============end save this project materials to pr_invoice ========================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bms_add_project'])) {
    // Get form data
    $client_id = $_POST['client_select'];
    $description = sanitize_textarea_field($_POST['description']);
    $creation_date = gmdate('Y-m-d');
    $save_type = 'new'; //maybe arbitrary

    if ($client_id == 'new') {
        $new_client = sanitize_text_field($_POST['new_client']);

        // Check if new client name is not empty
        if (!empty($new_client)) {
            // Insert new client into the database
            $result = $wpdb->insert(
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
            if ($result === false) {
                // Log the error message
                $error_message = 'Failed to insert new client into the database: ' . $wpdb->last_error;
                echo '<div class="alert alert-danger">' . esc_html($error_message) . '</div>';
            } else {
                // Get the inserted client ID
                $client_id = $wpdb->insert_id;
                echo '<div class="alert alert-success">Client added successfully!</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Error: Client name cannot be empty.</div>';
        }
    }
    // Insert into bms_projects table as new entry
    if ($save_type == 'new' || $save_type == 'savenew') {
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}bms_projects",
            [
                'client_id' => $client_id,
                'description' => $description,
                'creation_date' => $creation_date,
                'status' => 'START'
            ],
            [
                '%d', '%s', '%s', '%s'
            ]
        );
    }//end if new entry
    elseif ($save_type == 'modify') { //not completed...
        //if save type is update
        $update_result = $wpdb->update(
            "{$wpdb->prefix}bms_projects",
            [
                'client_id' => $client_id,
                'creation_date' => $creation_date,
                'include' => $include,
                'vat' => $vat,
                'plus_inc_vat' => $plus_inc_vat,
                'vat_price' => $vat_price,
                'total_before_vat' => $total_before_vat,
                'total_after_vat' => $total_after_vat,
                'product_description' => $product_description,
                'discount_description' => $discount_description,
                'discount_val' => $discount_val,
                'total_after_discount' => $total_after_discount,
                'lang' => $lang
            ],
            [
                'project_no' => $project_no
            ],
            [
                '%d', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s'
            ],
            [
                '%s'
            ]
        );



        $project_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$wpdb->prefix}bms_projects WHERE project_no = %s", $project_no)
        );

        if ($project_id) {
            // Delete existing item lines
            $deleted = $wpdb->delete(
                "{$wpdb->prefix}bms_project_items",
                ['project_id' => $project_id],
                ['%d']
            );

            if ($deleted !== false) {
                foreach ($_POST['item_line'] as $index => $item_line) {
                    $description = sanitize_textarea_field($item_line);
                    $unit_price = floatval($_POST['unit-price'][$index]);
                    $quantity = intval($_POST['item-quantity'][$index]);
                    $price = floatval($_POST['price'][$index]);

                    $item_inserted = $wpdb->insert(
                        "{$wpdb->prefix}bms_project_items",
                        [
                            'project_id' => $project_id,
                            'description' => $description,
                            'unit_price' => $unit_price,
                            'quantity' => $quantity,
                            'price' => $price
                        ],
                        [
                            '%d', '%s', '%f', '%d', '%f'
                        ]
                    );
                }
                // Set a success flag
                set_transient('project_updated_success', true, 30); // 30 seconds
            } else {
                // Set an error flag for deletion
                set_transient('project_updated_error', 'Error deleting existing item lines.', 30); // 30 seconds
            }
        } else {
            // Set an error flag if project ID not found
            set_transient('project_updated_error', 'Project ID not found for the given project number.', 30); // 30 seconds
        }





        if ($update_result !== false) {
            // Update successful
            set_transient('project_updated_success', true, 30); // 30 seconds
        } else {
            // Update failed
            set_transient('project_updated_error', $wpdb->last_error, 30); // 30 seconds
        }
    }//end of outer modify
}

//update project name
if (isset($_POST['update_description'])) {
    // Sanitize inputs
    $project_id = intval($_POST['mod_project_id']);
    $description = sanitize_text_field($_POST['mod_description']);
    $status = (isset($_POST['mod_status']))? sanitize_text_field($_POST['mod_status']) : 'START';
    $table_name = $wpdb->prefix . 'bms_projects';  // Adjust the table name if needed

    // Update the description in the database
    $updated = $wpdb->update(
        $table_name,
        [
            'description' => $description,
            'status' => $status
        ],
        ['id' => $project_id],
        ['%s', '%s'], // Format for data array
        ['%d'] // Format for WHERE condition
    );

    // Redirect or show a success/failure message
    if ($updated !== false) {
        // Optional: You can redirect back to the same page or display a success message
        echo '<div class="alert alert-success">Description updated successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Failed to update the description.</div>';
    }
}//end update name

//delete project ================================================================================
if (isset($_POST['delete-project-submit'])) {
    // Retrieve project ID from POST data
    $projectId = $_POST['delete-project-submit'];

    // Check if a row exists in prefix_bms_balances
    $balance_row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bms_balances WHERE project_id = $projectId");
    $material_row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bms_stock_out WHERE project_id = $projectId");
    
    if (empty($balance_row) && empty($material_row)) {
        // No balance found, proceed with deleting the project
        $delete_project = $wpdb->delete(
            "{$wpdb->prefix}bms_projects",
            [ 'id' => $projectId ],
            [ '%d' ]
        );

        if ($delete_project !== false) {
            echo '<div class="alert alert-success">Project deleted successfully!</div>';
        } else {
            echo '<div class="alert alert-danger">Failed to delete.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">You cannot delete projects that have related payments or used materials.</div>';
    }
}//end del project


//Delete invoice item ==============================================================
if (isset($_POST['delete_item'])) {
     $item_id = intval($_POST['delete_item_id']);

    // Fetch the project_id from the item to be deleted
    $project_id = $wpdb->get_var($wpdb->prepare(
        "SELECT project_id FROM {$wpdb->prefix}bms_project_items WHERE id = %d",
        $item_id
    ));

    // Delete the item
    $wpdb->delete(
        "{$wpdb->prefix}bms_project_items",
        array('id' => $item_id),
        array('%d')
    );

    // Recalculate totals
    $remaining_items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bms_project_items WHERE project_id = %d",
        $project_id
    ));

    $project_totals = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bms_projects WHERE id = %d",
        $project_id
    ));

    if (empty($remaining_items)) {
        // If no items left, set all values to NULL
        $wpdb->update(
            "{$wpdb->prefix}bms_projects",
            array(
                'vat' => NULL,
                'plus_inc_vat' => NULL,
                'total_before_vat' => NULL,
                'vat_price' => NULL,
                'total_after_vat' => NULL,
                'discount_description' => NULL,
                'discount_val' => NULL,
                'total_after_discount' => NULL
            ),
            array('id' => $project_id),
            array(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
            array('%d')
        );
    } else {
        // Recalculate totals
        $total_before_vat = 0;
        foreach ($remaining_items as $item) {
            $total_before_vat += $item->price;
        }

        $vat = $project_totals->vat;
        $vat_price = $total_before_vat * ($vat / 100);
        $total_after_vat = $total_before_vat + $vat_price;

        if ($project_totals->plus_inc_vat == 1) {
            // If VAT is included
            $total_after_vat = $total_before_vat + $vat_price;
        } else {
            // If VAT is not included
            $total_after_vat = $total_before_vat;
        }

        // Update the project totals
        $wpdb->update(
            "{$wpdb->prefix}bms_projects",
            array(
                'total_before_vat' => $total_before_vat,
                'vat_price' => $vat_price,
                'total_after_vat' => $total_after_vat,
                'discount_description' => NULL,
                'discount_val' => NULL,
                'total_after_discount' => NULL
            ),
            array('id' => $project_id),
            array('%f', '%f', '%f', '%s', '%f', '%f'),
            array('%d')
        );
    }
}

//delete cost item =============================================================================
if (isset($_POST['delete_cost_item'])) {
    $delete_cost_item_id = intval($_POST['delete_cost_item_id']);
    $table_name = $wpdb->prefix . 'bms_project_costs';

    // Delete the record
    $wpdb->delete($table_name, ['id' => $delete_cost_item_id]);
}

if (isset($_POST['save_invoice_settings']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $settings = $_POST['settings'];

    // Normalize and clean data
    $final_settings = [];
    foreach ($settings as $key => $value) {
        if (isset($value['set']) && $value['set'] === 'set') {
            $final_settings[$key] = [
                'set' => 'set',
                'value' => isset($value['value']) ? sanitize_text_field($value['value']) : '',
                'position' => isset($value['position']) ? sanitize_text_field($value['position']) : ''
            ];
        } else {
            $final_settings[$key] = ['set' => 'unset']; 
        }
    }

    // Convert to JSON and save in pr_inv_incl
    $wpdb->update(
        "{$wpdb->prefix}bms_projects",
        ['pr_inv_incl' => json_encode($final_settings)],
        ['id' => $project_id],
        ['%s'],
        ['%d']
    );

    echo '<div class="alert alert-success">Project invoice settings set successfully!</div>';
}