<?php

global $wpdb;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bms_add_quote'])) {
    // Get form data
    $quote_no = sanitize_text_field($_POST['quote_no']);
    $client_id = intval($_POST['client_select']);
    $creation_date =  $_POST['date'];
    $valid_until =   $_POST['valid_until'];
    $include = isset($_POST['include']) ? implode(',', $_POST['include']) : '';
    $vat = floatval($_POST['vat']);
    $plus_inc_vat = intval($_POST['plus-inc-vat']);
    $vat_price = floatval($_POST['vat-price']);
    $total_before_vat = floatval($_POST['total-before-vat']);
    $total_after_vat = floatval($_POST['total-after-vat']);
    $product_description = sanitize_textarea_field($_POST['product_description']);
    $discount_description = sanitize_text_field($_POST['discount_descr']);
    $discount_val = floatval($_POST['discount_val']);
    $total_after_discount = floatval($_POST['total-after-discount']);
    $delivery_time = sanitize_text_field($_POST['delivery_time']);
    $save_type = $_POST['save-type'];
    $lang = $_POST['lang'];

    if ($save_type == 'new' || $save_type == 'savenew') {

        //if save type is new, first check if quote_no already exists then save or reject
        //if quote_no already exists do not save we need it to be unique

        /** this is obsolate if we make quote number readonly. Consider removing this query if you keep this format */
        $quote_no_exists = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bms_quotes WHERE quote_no = %s", $quote_no)
        );

        if ($quote_no_exists > 0) {
            // Quote number already exists, show error message and do not save data
            set_transient('quote_added_error', 'Quote number already exists. Please use a unique quote number.', 30); // 30 seconds
        } else {
            // Quote number does not exist, proceed with saving data
            // type is new, Insert into bms_quotes table
            $inserted = $wpdb->insert(
                "{$wpdb->prefix}bms_quotes",
                [
                    'quote_no' => $quote_no,
                    'client_id' => $client_id,
                    'creation_date' => $creation_date,
                    'valid_until' => $valid_until,
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
                    'delivery_time' => $delivery_time,
                    'lang' => $lang
                ],
                [
                    '%s', '%d', '%s', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s', '%s'
                ]
            );

            if ($inserted) {
                // Get the inserted quote ID
                $quote_id = $wpdb->insert_id;

                // Insert each item into bms_quote_items table
                if ($quote_id && !empty($_POST['item_line'])) {
                    foreach ($_POST['item_line'] as $index => $item_line) {
                        $description = sanitize_textarea_field($item_line);
                        $unit_price = floatval($_POST['unit-price'][$index]);
                        $quantity = intval($_POST['item-quantity'][$index]);
                        $price = floatval($_POST['price'][$index]);

                        $item_inserted = $wpdb->insert(
                            "{$wpdb->prefix}bms_quote_items",
                            [
                                'quote_id' => $quote_id,
                                'description' => $description,
                                'unit_price' => $unit_price,
                                'quantity' => $quantity,
                                'price' => $price
                            ],
                            [
                                '%d', '%s', '%f', '%d', '%f'
                            ]
                        );

                    }//end foreach item
                }//end of save items
                // Set a success flag
                set_transient('quote_added_success', true, 30); // 30 seconds
            } else {
                // Set an error flag
                set_transient('quote_added_error', $wpdb->last_error, 30); // 30 seconds
            }
        }//end if quote no exists
    } elseif ($save_type == 'modify') {
        //if save type is update
        $update_result = $wpdb->update(
            "{$wpdb->prefix}bms_quotes",
            [
                'client_id' => $client_id,
                'creation_date' => $creation_date,
                'valid_until' => $valid_until,
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
                'delivery_time' => $delivery_time,
                'lang' => $lang
            ],
            [
                'quote_no' => $quote_no
            ],
            [
                '%d', '%s', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s', '%s'
            ],
            [
                '%s'
            ]
        );



        $quote_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$wpdb->prefix}bms_quotes WHERE quote_no = %s", $quote_no)
        );

        if ($quote_id) {
            // Delete existing item lines
            $deleted = $wpdb->delete(
                "{$wpdb->prefix}bms_quote_items",
                ['quote_id' => $quote_id],
                ['%d']
            );

            if ($deleted !== false) {
                foreach ($_POST['item_line'] as $index => $item_line) {
                    $description = sanitize_textarea_field($item_line);
                    $unit_price = floatval($_POST['unit-price'][$index]);
                    $quantity = intval($_POST['item-quantity'][$index]);
                    $price = floatval($_POST['price'][$index]);

                    $item_inserted = $wpdb->insert(
                        "{$wpdb->prefix}bms_quote_items",
                        [
                            'quote_id' => $quote_id,
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
                set_transient('quote_updated_success', true, 30); // 30 seconds
            } else {
                // Set an error flag for deletion
                set_transient('quote_updated_error', 'Error deleting existing item lines.', 30); // 30 seconds
            }
        } else {
            // Set an error flag if quote ID not found
            set_transient('quote_updated_error', 'Quote ID not found for the given quote number.', 30); // 30 seconds
        }





        if ($update_result !== false) {
            // Update successful
            set_transient('quote_updated_success', true, 30); // 30 seconds
        } else {
            // Update failed
            set_transient('quote_updated_error', $wpdb->last_error, 30); // 30 seconds
        }
    }
}
