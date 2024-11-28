<?php

global $wpdb;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bms_add_invoice'])) {
    // Get form data
    $invoice_no = sanitize_text_field($_POST['invoice_no']);
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
    $lang = $_POST['lang'];
    $save_type = $_POST['save-type'];


    // Insert into bms_invoices table as new entry
    if ($save_type == 'new' || $save_type == 'savenew') {
        $inserted = $wpdb->insert(
            "{$wpdb->prefix}bms_invoices",
            [
                'invoice_no' => $invoice_no,
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
                '%s', '%d', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s'
            ]
        );

        if ($inserted) {
            // Get the inserted invoice ID
            $invoice_id = $wpdb->insert_id;

            // Insert each item into bms_invoice_items table
            if ($invoice_id && !empty($_POST['item_line'])) {
                foreach ($_POST['item_line'] as $index => $item_line) {
                    $description = sanitize_textarea_field($item_line);
                    $unit_price = floatval($_POST['unit-price'][$index]);
                    $quantity = intval($_POST['item-quantity'][$index]);
                    $price = floatval($_POST['price'][$index]);

                    $item_inserted = $wpdb->insert(
                        "{$wpdb->prefix}bms_invoice_items",
                        [
                            'invoice_id' => $invoice_id,
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
            set_transient('invoice_added_success', true, 30); // 30 seconds
        } else {
            // Set an error flag
            set_transient('invoice_added_error', $wpdb->last_error, 30); // 30 seconds
        }//end of check inserted
    }//end if new entry
    elseif ($save_type == 'modify') {
        //if save type is update
        $update_result = $wpdb->update(
            "{$wpdb->prefix}bms_invoices",
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
                'invoice_no' => $invoice_no
            ],
            [
                '%d', '%s', '%s', '%f', '%d', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%s'
            ],
            [
                '%s'
            ]
        );



        $invoice_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$wpdb->prefix}bms_invoices WHERE invoice_no = %s", $invoice_no)
        );

        if ($invoice_id) {
            // Delete existing item lines
            $deleted = $wpdb->delete(
                "{$wpdb->prefix}bms_invoice_items",
                ['invoice_id' => $invoice_id],
                ['%d']
            );

            if ($deleted !== false) {
                foreach ($_POST['item_line'] as $index => $item_line) {
                    $description = sanitize_textarea_field($item_line);
                    $unit_price = floatval($_POST['unit-price'][$index]);
                    $quantity = intval($_POST['item-quantity'][$index]);
                    $price = floatval($_POST['price'][$index]);

                    $item_inserted = $wpdb->insert(
                        "{$wpdb->prefix}bms_invoice_items",
                        [
                            'invoice_id' => $invoice_id,
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
                set_transient('invoice_updated_success', true, 30); // 30 seconds
            } else {
                // Set an error flag for deletion
                set_transient('invoice_updated_error', 'Error deleting existing item lines.', 30); // 30 seconds
            }
        } else {
            // Set an error flag if invoice ID not found
            set_transient('invoice_updated_error', 'Invoice ID not found for the given invoice number.', 30); // 30 seconds
        }





        if ($update_result !== false) {
            // Update successful
            set_transient('invoice_updated_success', true, 30); // 30 seconds
        } else {
            // Update failed
            set_transient('invoice_updated_error', $wpdb->last_error, 30); // 30 seconds
        }
    }//end of outer modify
}
