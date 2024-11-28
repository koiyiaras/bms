<?php

if (isset($_POST['add-in-transaction'])) {
    // Sanitize form data
    $record_id = intval($_POST['record_id']);
    $rel_invoice = sanitize_text_field($_POST['rel_invoice']);
    $description = sanitize_text_field($_POST['description']);
    $payer_payee = sanitize_text_field($_POST['payer']);
    $amount = sanitize_text_field($_POST['amount']);
    $type_of_payment = sanitize_text_field($_POST['type_of_payment']);
    $payment_date = sanitize_text_field($_POST['payment_date']);
    $in_out = 1;

    // Check if rel_invoice is not empty and get the corresponding ID
    $rel_invoice_id = 0;
    if (!empty($rel_invoice)) {
        $invoice_table = $wpdb->prefix . 'bms_invoices';
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT id FROM $invoice_table WHERE invoice_no = %s", $rel_invoice));
        if ($invoice) {
            $rel_invoice_id = $invoice->id;
        }
        if ($invoice == null) {
            $rel_invoice_id = $rel_invoice;
        }
    }

    // Insert or Update data into balances table
    $balances_table = $wpdb->prefix . 'bms_balances';
    $data = [
        'in_out' => $in_out,
        'description' => $description,
        'rel_invoice' => $rel_invoice_id,
        'payer_payee' => $payer_payee,
        'amount' => $amount,
        'type_of_payment' => $type_of_payment,
        'payment_date' => $payment_date,
    ];
    $format = ['%d', '%s', '%s', '%s', '%f', '%s', '%s'];

    if ($record_id > 0) {
        // Update existing record
        $updated = $wpdb->update($balances_table, $data, ['id' => $record_id], $format, ['%d']);
        if ($updated !== false) {
            echo '<div id="qu-updated-success" class="alert alert-success" role="alert">Payment updated successfully.</div>';
        } else {
            echo '<div id="cl-updated-error" class="alert alert-danger" role="alert">Error updating payment.</div>';
        }
    } else {
        // Insert new record
        $inserted = $wpdb->insert($balances_table, $data, $format);
        if ($inserted) {
            echo '<div id="qu-added-success" class="alert alert-success" role="alert">Payment added successfully.</div>';
        } else {
            echo '<div id="cl-added-error" class="alert alert-danger" role="alert">Error adding payment.</div>';
        }
    }
}

if (isset($_POST['add-out-transaction'])) {
    // Sanitize form data
    $record_id = intval($_POST['record_id']);
    $rel_invoice = sanitize_text_field($_POST['rel_invoice']);
    $description = sanitize_text_field($_POST['description']);
    $payer_payee = sanitize_text_field($_POST['payee']);
    $amount = sanitize_text_field($_POST['amount']);
    $type_of_payment = sanitize_text_field($_POST['type_of_payment']);
    $payment_date = sanitize_text_field($_POST['payment_date']);
    $in_out = 2;

    // Check if rel_invoice is not empty and get the corresponding ID
    $rel_invoice_id = 0;
    if (!empty($rel_invoice)) {
        $invoice_table = $wpdb->prefix . 'bms_invoices';
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT id FROM $invoice_table WHERE invoice_no = %s", $rel_invoice));
        if ($invoice) {
            $rel_invoice_id = $invoice->id;
        }
        if ($invoice == null) {
            $rel_invoice_id = $rel_invoice;
        }
    }

    // Insert or Update data into balances table
    $balances_table = $wpdb->prefix . 'bms_balances';
    $data = [
        'in_out' => $in_out,
        'description' => $description,
        'rel_invoice' => $rel_invoice_id,
        'payer_payee' => $payer_payee,
        'amount' => $amount,
        'type_of_payment' => $type_of_payment,
        'payment_date' => $payment_date,
    ];
    $format = ['%d', '%s', '%s', '%s', '%f', '%s', '%s'];

    if ($record_id > 0) {
        // Update existing record
        $updated = $wpdb->update($balances_table, $data, ['id' => $record_id], $format, ['%d']);
        if ($updated !== false) {
            echo '<div id="qu-updated-success" class="alert alert-success" role="alert">Payment updated successfully.</div>';
        } else {
            echo '<div id="cl-updated-error" class="alert alert-danger" role="alert">Error updating payment.</div>';
        }
    } else {
        // Insert new record
        $inserted = $wpdb->insert($balances_table, $data, $format);
        if ($inserted) {
            echo '<div id="qu-added-success" class="alert alert-success" role="alert">Payment added successfully.</div>';
        } else {
            echo '<div id="cl-added-error" class="alert alert-danger" role="alert">Error adding payment.</div>';
        }
    }
}
