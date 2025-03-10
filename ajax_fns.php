<?php


/** Ajax call to get quote data */
function load_quote_data()
{
    // Check if the current user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

    global $wpdb;
    // Start output buffering
    ob_start();

    // Check permissions
    if (!is_user_logged_in()) {
        wp_send_json_error('Permission denied');
        wp_die();
    }

    $quote_id = intval($_POST['quote_id']);

    if ($quote_id > 0) {
        $table_name = $wpdb->prefix . 'bms_quotes';
        $table_name2 = $wpdb->prefix . 'bms_quote_items';

        $results = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $quote_id)
        );

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name2 WHERE quote_id = %d", $quote_id)
        );

        // Return the results as a response
        wp_send_json_success(['results' => $results, 'items' => $items]);
    } else {
        wp_send_json_error('Invalid quote ID');
    }

    // Output buffering ends
    ob_end_clean();
}
add_action('wp_ajax_load_quote_data', 'load_quote_data');
add_action('wp_ajax_nopriv_load_quote_data', 'load_quote_data');


add_action('wp_ajax_delete_quote', 'delete_quote');
add_action('wp_ajax_nopriv_delete_quote', 'delete_quote');
function delete_quote()
{
    global $wpdb;
    $quote_id = intval($_POST['quote_id']);
    $quotes_table = $wpdb->prefix . 'bms_quotes';

    // Check if the quote exists
    $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
    if ($quote) {
        // Delete the quote
        $deleted = $wpdb->delete($quotes_table, ['id' => $quote_id], ['%d']);
        if ($deleted !== false) {
            echo wp_json_encode(['success' => true]);
        } else {
            // Provide a more detailed error message
            echo wp_json_encode([
                'success' => false,
                'error'   => esc_html__('Failed to delete quote. Database error: ', 'bms') . esc_html($wpdb->last_error),
            ]);
        }
    } else {
        echo wp_json_encode([
            'success' => false,
            'error'   => esc_html__('Quote not found.', 'bms'),
        ]);
    }
    wp_die();    
}

/** Duplicate Quote Save as new */
/** Ajax call to get data */
function save_quote_as_new()
{
    global $wpdb;
    $quote_id = intval($_POST['quote_id']);

    if ($quote_id > 0) {
        $table_name = $wpdb->prefix . 'bms_quotes';
        $table_name2 = $wpdb->prefix . 'bms_quote_items';

        $results = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $quote_id)
        );

        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name2 WHERE quote_id = %d", $quote_id)
        );

        /** Get the next quote_no in sequence (based on quotes.php for new quotes) */
        /** Generete the new quote number  */
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
        $quote_no = 'QQ' . str_pad($new_quote_number, 3, '0', STR_PAD_LEFT);

        // Return the results as a response
        echo wp_json_encode([
            'results'   => $results,
            'items'     => $items,
            'quote_no'  => $quote_no,
        ]);
    } else {
        echo wp_json_encode([
            'error' => esc_html__('Request failed', 'bms'),
        ]);
    }

    wp_die();
}
add_action('wp_ajax_save_quote_as_new', 'save_quote_as_new');
add_action('wp_ajax_nopriv_save_quote_as_new', 'save_quote_as_new');


/** =============== INVOICES AJAX ===================================== */
/** MODIFY Invoice */
/** Ajax call to get invoice data */
function load_invoice_data()
{
    global $wpdb;
    $invoice_id = intval($_POST['invoice_id']);

    if ($invoice_id > 0 && is_int($invoice_id)) { // Validate invoice ID
        $table_name = $wpdb->prefix . 'bms_invoices';
        $table_name2 = $wpdb->prefix . 'bms_invoice_items';
    
        // Fetch invoice data
        $results = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $invoice_id)
        );
    
        // Fetch invoice items
        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name2 WHERE invoice_id = %d", $invoice_id)
        );
    
        if ($results) {
            // Return results as JSON
            echo wp_json_encode(['results' => $results, 'items' => $items]);
        } else {
            // No invoice found
            echo wp_json_encode(['success' => false, 'error' => 'Invoice not found.']);
        }
    } else {
        // Invalid or missing invoice ID
        echo wp_json_encode(['success' => false, 'error' => 'Invalid invoice ID.']);
    }
    
    wp_die();
}    
add_action('wp_ajax_load_invoice_data', 'load_invoice_data');
add_action('wp_ajax_nopriv_load_invoice_data', 'load_invoice_data');

/** Duplicate Invoice Save as new */
/** Ajax call to get data */
function save_invoice_as_new()
{
    global $wpdb;
    $invoice_id = intval($_POST['invoice_id']);

    if ($invoice_id > 0 && is_int($invoice_id)) { // Validate invoice ID
        $table_name = $wpdb->prefix . 'bms_invoices';
        $table_name2 = $wpdb->prefix . 'bms_invoice_items';
    
        // Fetch invoice data
        $results = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $invoice_id)
        );
    
        // Fetch invoice items
        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name2 WHERE invoice_id = %d", $invoice_id)
        );
    
        // Get the next invoice number in sequence
        $last_invoice = $wpdb->get_var(
            "SELECT invoice_no FROM {$wpdb->prefix}bms_invoices ORDER BY invoice_no DESC LIMIT 1"
        );
    
        // Create next invoice number
        $new_invoice_number = ($last_invoice) ? absint($last_invoice) + 1 : 1; // Ensure numeric value
        $invoice_no = str_pad($new_invoice_number, 4, '0', STR_PAD_LEFT); // Ensure 4-digit padding
    
        // Check if invoice data exists
        if ($results) {
            // Return results as JSON
            echo wp_json_encode([
                'results' => $results,
                'items' => $items,
                'invoice_no' => $invoice_no
            ]);
        } else {
            // No invoice found
            echo wp_json_encode([
                'success' => false,
                'error' => 'Invoice not found.'
            ]);
        }
    } else {
        // Invalid or missing invoice ID
        echo wp_json_encode([
            'success' => false,
            'error' => 'Invalid invoice ID.'
        ]);
    }
    wp_die();    
}
add_action('wp_ajax_save_invoice_as_new', 'save_invoice_as_new');
add_action('wp_ajax_nopriv_save_invoice_as_new', 'save_invoice_as_new');

function cancel_invoice()
{
    global $wpdb;
    $invoice_id = intval($_POST['invoice_id']);
    $invoices_table = $wpdb->prefix . 'bms_invoices';
    $status = 'cancelled';

    // Check if the invoice exists
    $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invoices_table WHERE id = %d", $invoice_id));
    if ($invoice) {
        // Cancel the invoice
        $canceld = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $invoices_table SET status = %s WHERE id = %d",
                $status, // Make sure $status is sanitized appropriately
                $invoice_id
            )
        );
    
        if ($canceld !== false) {
            echo wp_json_encode(['success' => true]);
        } else {
            // Provide a more detailed error message
            echo wp_json_encode(['success' => false, 'error' => 'Failed to cancel invoice. Database error: ' . $wpdb->last_error]);
        }
    } else {
        echo wp_json_encode(['success' => false, 'error' => 'Invoice not found.']);
    }
    
    // Ensure proper termination of script execution
    wp_die();    
}
add_action('wp_ajax_cancel_invoice', 'cancel_invoice');
add_action('wp_ajax_nopriv_cancel_invoice', 'cancel_invoice');
//end cancel invoice

/** END INVOICE AJAXES ========================= */

/** START BALANCES AJAXES ========================= */
// Handle the deletion of a balance record
add_action('wp_ajax_delete_balance_record', 'delete_balance_record');
function delete_balance_record()
{
    global $wpdb;
    $id = intval($_POST['id']);

    if ($id > 0) {
        $table = $wpdb->prefix . 'bms_balances';
        $deleted = $wpdb->delete($table, ['id' => $id]);

        if ($deleted) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete record.');
        }
    } else {
        wp_send_json_error('Invalid ID.');
    }
}

add_action('wp_ajax_get_balance_details', 'get_balance_details');

function get_balance_details()
{
    global $wpdb;
    $id = intval($_POST['id']);
    $balances_table = $wpdb->prefix . 'bms_balances';

    $balance = $wpdb->get_row($wpdb->prepare("SELECT * FROM $balances_table WHERE id = %d", $id), ARRAY_A);

    if ($balance) {
        wp_send_json_success($balance);
    } else {
        wp_send_json_error('Record not found');
    }
}

/** END BALANCES AJAXES ========================= */

/** =============== PRINT CODE AJAX FOR INVOICES/PROJECTS/QUOTES ===================================== */
/** VIEW project inv printable form */
function fetch_data_for_print() {
    if (!isset($_POST['this_id']) || !is_numeric($_POST['this_id']) || !isset($_POST['source'])) {
        wp_send_json_error('Invalid request parameters');
        return;
    }

    global $wpdb;
    $id = intval($_POST['this_id']);
    $type = sanitize_text_field($_POST['source']); // 'invoice', 'project', or 'quote'

    // Define table names based on the type
    $main_table = '';
    $items_table = '';
    $client_id_field = 'client_id';
    $item_id_field = '';

    switch ($type) {
        case 'invoice':
            $main_table = $wpdb->prefix . 'bms_invoices';
            $items_table = $wpdb->prefix . 'bms_invoice_items';
            $item_id_field = 'invoice_id';
            break;
        case 'project':
            $main_table = $wpdb->prefix . 'bms_projects';
            $items_table = $wpdb->prefix . 'bms_project_items';
            $item_id_field = 'project_id';
            break;
        case 'quote':
            $main_table = $wpdb->prefix . 'bms_quotes';
            $items_table = $wpdb->prefix . 'bms_quote_items';
            $item_id_field = 'quote_id';
            break;
        default:
            wp_send_json_error('Invalid source type');
            return;
    }

    $clients_table = $wpdb->prefix . 'bms_clients';
    $company_table = $wpdb->prefix . 'bms_company';

    // Fetch main data (invoice, project, or quote)
    $main_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $main_table WHERE id = %d", $id));
    if (!$main_data) {
        wp_send_json_error(ucfirst($type) . ' not found');
        return;
    }

    // Fetch client data
    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $main_data->$client_id_field));
    if (!$client) {
        wp_send_json_error('Client not found');
        return;
    }

    // Fetch company data
    $company = $wpdb->get_row("SELECT * FROM $company_table LIMIT 1");
    if (!$company) {
        wp_send_json_error('Company details not found');
        return;
    }

    // Fetch items data
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $items_table WHERE $item_id_field = %d", $id));

    // Prepare data for response
    $response = [
        'no' => ($type === 'invoice') ? $main_data->invoice_no : (($type === 'quote') ? $main_data->quote_no : $main_data->id),
        'creation_date' => $main_data->creation_date,
        'include' => ($type === 'invoice') ? $main_data->include : (($type === 'project') ? $main_data->pr_inv_incl : 'our_address,client_address,bank_details,thanks_msg'),
        'vat' => $main_data->vat,
        'plus_inc_vat' => $main_data->plus_inc_vat,
        'vat_price' => $main_data->vat_price,
        'total_before_vat' => $main_data->total_before_vat,
        'total_after_vat' => $main_data->total_after_vat,
        'product_description' => ($type === 'invoice') ? $main_data->product_description : (($type === 'quote') ? $main_data->product_description : $main_data->description),
        'discount_description' => $main_data->discount_description,
        'discount_val' => $main_data->discount_val,
        'total_after_discount' => $main_data->total_after_discount,
        'client_name' => $client->name,
        'client_address' => $client->address,
        'client_phone' => $client->phone,
        'client_email' => $client->email,
        'company_name' => $company->company_name,
        'company_address' => $company->address,
        'company_phone' => $company->phone1,
        'company_email' => $company->email,
        'company_bank' => $company->bank_details,
        'company_thanks' => $company->thanks_msg,
        'items' => $items
    ];

    wp_send_json_success($response);
    wp_die();
}
add_action('wp_ajax_fetch_data_for_print', 'fetch_data_for_print');

/** END PRINT AJAXES ========================= */