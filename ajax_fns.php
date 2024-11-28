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
    if (!current_user_can('edit_posts')) {
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

function load_tcpdf()
{
    if (!class_exists('TCPDF')) {
        require_once(plugin_dir_path(__FILE__) . 'libraries/tcpdf/tcpdf.php');
    }
}

/** create PDF Quote - Ajax call*/
function generate_quote_pdf()
{
    global $wpdb;

    // Start output buffering
    ob_start();

    $quote_id = intval($_POST['quote_id']);
    $quotes_table = $wpdb->prefix . 'bms_quotes';
    $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
    if ($quote) {
        $pdf_url = create_pdf_from_quote($quote);
        wp_send_json_success(['pdf_url' => $pdf_url]);
    } else {
        wp_send_json_error('Quote not found');
    }

    // Output buffering ends
    ob_end_clean();
}
add_action('wp_ajax_generate_quote_pdf', 'generate_quote_pdf');
add_action('wp_ajax_nopriv_generate_quote_pdf', 'generate_quote_pdf');

function create_pdf_from_quote($quote)
{
    global $wpdb;
    $clients_table = $wpdb->prefix . 'bms_clients';
    $items_table = $wpdb->prefix . 'bms_quote_items';
    $company_table = $wpdb->prefix . 'bms_company';

    // Get client details
    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $quote->client_id));
    // Get company details
    $company = $wpdb->get_row("SELECT * FROM $company_table LIMIT 1");
    // Get quote items
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $items_table WHERE quote_id = %d", $quote->id));

    $englishText = [
        'date' => 'Date',
        'valid_until' => 'Valid until',
        'pelatis' => 'Client',
        'perigrafi' => 'Description',
        'monadas' => 'Unit price',
        'posotita' => 'Quantity',
        'timi' => 'Price',
        'synolo_prin' => '<b>Total</b> before VAT',
        'delivery_time' => 'Delivery time',
        'fpa' => 'VAT',
        'syn' => 'plus VAT',
        'symp' => 'VAT incl.',
        'synolo_meta1' => 'Total after VAT',
        'ekptosi' => 'Discount',
        'synolo_meta2' => 'Total after discount',
        'ektimomenos' => 'Expected delivery time',
        'efxarist' => 'Thanks for doing business with us'
    ];

    $greekText = [
        'date' => 'Ημερομηνία',
        'valid_until' => 'Ισχύει μέχρι',
        'pelatis' => 'Πελάτης',
        'perigrafi' => 'Περιγραφή',
        'monadas' => 'Τιμή μονάδας',
        'posotita' => 'Ποσότητα',
        'timi' => 'Τιμή',
        'synolo_prin' => '<b>Σύνολο</b> πριν το ΦΠΑ',
        'fpa' => 'Φ.Π.Α.',
        'syn' => 'συν ΦΠΑ',
        'symp' => 'ΦΠΑ συμπεριλ.',
        'synolo_meta1' => 'Σύνολο (μετά το ΦΠΑ)',
        'ekptosi' => 'Εκπτωση',
        'synolo_meta2' => 'Σύνολο (μετά την εκπτωση)',
        'ektimomenos' => 'Εκτιμώμενος χρόνος ολοκλήρωσης',
        'efxarist' => 'Ευχαριστούμε για τη συνεργασία'
    ];

    $textVals = $quote->lang == 'en' ? $englishText : $greekText;


    // Load TCPDF only when generating a PDF
    load_tcpdf();

    // Create new PDF document
    $pdf = new TCPDF();

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($company->company_name);
    $pdf->SetTitle('Quote #' . $quote->quote_no);
    $pdf->SetSubject('Quote');

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set font
    $pdf->SetFont('dejavusans', '', 10);

    // Add a page
    $pdf->AddPage();

    // Set content
    $tbl = <<<EOD
    <table cellspacing="0" cellpadding="1">
        <tr>
            <td>
                <p>{$company->company_name}</p>
                <p>{$company->address}</p>
                <p>{$company->phone1}<br>{$company->email}<br></p>
            </td><!--end of left top-->
            <td>
                    <span style='text-align:right;'># <b>{$quote->quote_no}</b></span>
                    <p>
                        {$textVals['date']}: {$quote->creation_date}<br>
                        {$textVals['valid_until']}: {$quote->valid_until}<br>
                    </p>
                    <p>&nbsp;</p>
                    <p><u>{$textVals['pelatis']}:</u><br>{$client->name}<br>{$client->address}<br>{$client->phone}<br>{$client->email}<br>
                    </p>
            </td><!--end of right top-->
            </tr>
    </table>
    EOD;

    $pdf->writeHTML($tbl, true, false, false, false, '');

    $html = <<<EOD
    <table cellspacing="0" cellpadding="1" style="border: 1px dotted gray; width: 98%;">
        <thead>
                    <tr>
                        <th style="width: 37%;"> {$textVals['perigrafi']}</th>
                        <th style="width: 21%;">{$textVals['monadas']}</th>
                        <th style="width: 21%;">{$textVals['posotita']}</th>
                        <th style="width: 21%;">{$textVals['timi']}</th>
                    </tr>
                </thead>
                <tbody>
    EOD;

    foreach ($items as $item) {
        $html .= <<<EOD
                <tr>
                        <td style="width: 37%;"> {$item->description}</td>
                        <td style="width: 21%;">{$item->unit_price}</td>
                        <td style="width: 21%;">{$item->quantity}</td>
                        <td style="width: 21%;">{$item->price}</td>
                    </tr>
    EOD;
    }

    $syn_symp = ($quote->plus_inc_vat == 1) ? $textVals['syn'] : $textVals['symp'];

    $html .= <<<EOD
                <tr>
                    <td> {$textVals['synolo_prin']}</td><td></td><td></td><td>{$quote->total_before_vat}</td>
                </tr>
                <tr>
                    <td> {$textVals['fpa']} ({$quote->vat}%) ({$syn_symp})</td><td></td><td></td><td>{$quote->vat_price}</td>
                </tr>
                <tr>
                    <td><strong> {$textVals['synolo_meta1']}:</strong></td><td></td><td></td><td><b>{$quote->total_after_vat}</b></td>
                </tr>
    EOD;

    if ($quote->discount_val > 0) {
        $html .= <<<EOD
                <tr>
                    <td> {$textVals['ekptosi']}: {$quote->discount_description}</td><td></td><td></td><td>{$quote->discount_val}</td>
                </tr>
                <tr>
                    <td><b> {$textVals['synolo_meta2']}:</b></td><td></td><td></td><td>{$quote->total_after_discount}</td>
                </tr>
    EOD;
    }

    $html .= <<<EOD
                </tbody>
            </table>
    EOD;

    $pdf->writeHTML($html, true, false, false, false, '');


    // Set content
    $tbl = <<<EOD
    <table cellspacing="0" cellpadding="1">
        <tr>
            <td><strong>{$textVals['ektimomenos']}:</strong> {$quote->delivery_time}</td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td style="text-align:center">{$textVals['efxarist']}</td>
        </tr>
    </table>
    EOD;

    $pdf->writeHTML($tbl, true, false, false, false, '');
    // Close and output PDF document
    $upload_dir = wp_upload_dir();
    $pdf_file = $upload_dir['path'] . '/quote_' . $quote->id . '.pdf';
    $pdf->Output($pdf_file, 'F');

    // Return the URL of the generated PDF
    return $upload_dir['url'] . '/quote_' . $quote->id . '.pdf';
}
//-----------End PDF handler function---------

// Handle AJAX request to send quote to client
add_action('wp_ajax_send_quote_to_client', 'send_quote_to_client');
add_action('wp_ajax_nopriv_send_quote_to_client', 'send_quote_to_client');

function send_quote_to_client()
{
    global $wpdb;
    $quote_id = intval($_POST['quote_id']);
    $quotes_table = $wpdb->prefix . 'bms_quotes';
    $clients_table = $wpdb->prefix . 'bms_clients';

    $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
    if ($quote) {
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $quote->client_id));
        if ($client) {
            $pdf_url = create_pdf_from_quote($quote);
            $to = $client->email;
            $subject = 'Quote #' . $quote->quote_no;
            $message = 'Dear ' . esc_html($client->name) . ',<br><br>Please find attached the quote.<br><br>';
            $message .= "<a href='" . esc_url($pdf_url) . "'>View Quote</a>";
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $sent = wp_mail($to, $subject, $message, $headers);
    
            if ($sent) {
                echo wp_json_encode(['success' => true, 'email' => $to]);
            } else {
                echo wp_json_encode(['success' => false, 'error' => 'wp_mail failed.']);
            }
        } else {
            echo wp_json_encode(['success' => false, 'error' => 'Client not found.']);
        }
    } else {
        echo wp_json_encode(['success' => false, 'error' => 'Quote not found.']);
    }
    wp_die();
    
}
//end email quote

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


/** VIEW quote printable form */
function fetch_quote_data()
{
    // Check if quote_id is set and is a valid number
    if (!isset($_POST['quote_id']) || !is_numeric($_POST['quote_id'])) {
        wp_send_json_error('Invalid quote ID');
        return;
    }

    global $wpdb;
    $quote_id = intval($_POST['quote_id']);
    $quotes_table = $wpdb->prefix . 'bms_quotes';
    $quote_items_table = $wpdb->prefix . 'bms_quote_items';
    $clients_table = $wpdb->prefix . 'bms_clients';
    $company_table = $wpdb->prefix . 'bms_company';

    // Fetch quote data
    $quote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $quotes_table WHERE id = %d", $quote_id));
    if (!$quote) {
        wp_send_json_error('Quote not found');
        return;
    }

    // Fetch client data
    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $quote->client_id));
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

    // Fetch quote items
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $quote_items_table WHERE quote_id = %d", $quote_id));

    // Prepare data for response
    $response = [
        'quote_no' => $quote->quote_no,
        'creation_date' => $quote->creation_date,
        'valid_until' => $quote->valid_until,
        'product_description' => $quote->product_description,
        'total_before_vat' => $quote->total_before_vat,
        'vat' => $quote->vat,
        'plus_inc_vat' => $quote->plus_inc_vat,
        'vat_price' => $quote->vat_price,
        'total_after_vat' => $quote->total_after_vat,
        'discount_val' => $quote->discount_val,
        'discount_description' => $quote->discount_description,
        'total_after_discount' => $quote->total_after_discount,
        'delivery_time' => $quote->delivery_time,
        'lang' => $quote->lang,
        'client_name' => $client->name,
        'client_address' => $client->address,
        'client_phone' => $client->phone,
        'client_email' => $client->email,
        'company_name' => $company->company_name,
        'company_address' => $company->address,
        'company_phone' => $company->phone1,
        'company_email' => $company->email,
        'items' => $items
    ];

    wp_send_json_success($response);
    wp_die();
}

add_action('wp_ajax_fetch_quote_data', 'fetch_quote_data');
add_action('wp_ajax_nopriv_fetch_quote_data', 'fetch_quote_data');



/** =============== INVOICES AJAX ===================================== */
/** VIEW invoice printable form */
function fetch_invoice_data()
{
    // Check if invoice_id is set and is a valid number
    if (!isset($_POST['invoice_id']) || !is_numeric($_POST['invoice_id'])) {
        wp_send_json_error('Invalid invoice ID');
        return;
    }

    global $wpdb;
    $invoice_id = intval($_POST['invoice_id']);
    $invoices_table = $wpdb->prefix . 'bms_invoices';
    $invoice_items_table = $wpdb->prefix . 'bms_invoice_items';
    $clients_table = $wpdb->prefix . 'bms_clients';
    $company_table = $wpdb->prefix . 'bms_company';

    // Fetch invoice data
    $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invoices_table WHERE id = %d", $invoice_id));
    if (!$invoice) {
        wp_send_json_error('Invoice not found');
        return;
    }

    // Fetch client data
    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $invoice->client_id));
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

    // Fetch invoice items
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $invoice_items_table WHERE invoice_id = %d", $invoice_id));

    // Prepare data for response
    $response = [
        'invoice_no' => $invoice->invoice_no,
        'creation_date' => $invoice->creation_date,
        'include' => $invoice->include,
        'vat' => $invoice->vat,
        'plus_inc_vat' => $invoice->plus_inc_vat,
        'vat_price' => $invoice->vat_price,
        'total_before_vat' => $invoice->total_before_vat,
        'total_after_vat' => $invoice->total_after_vat,
        'product_description' => $invoice->product_description,
        'discount_description' => $invoice->discount_description,
        'discount_val' => $invoice->discount_val,
        'total_after_discount' => $invoice->total_after_discount,
        'lang' => $invoice->lang,
        'client_name' => $client->name,
        'client_address' => $client->address,
        'client_phone' => $client->phone,
        'client_email' => $client->email,
        'company_name' => $company->company_name,
        'company_address' => $company->address,
        'company_phone' => $company->phone1,
        'company_email' => $company->email,
        'company_bank' => $company->bank_details,
        'items' => $items
    ];

    wp_send_json_success($response);
    wp_die();
}

add_action('wp_ajax_fetch_invoice_data', 'fetch_invoice_data');
add_action('wp_ajax_nopriv_fetch_invoice_data', 'fetch_invoice_data');

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

/** create PDF Invoice - Ajax call*/
function generate_invoice_pdf()
{
    global $wpdb;
    $invoice_id = intval($_POST['invoice_id']);
    $invoices_table = $wpdb->prefix . 'bms_invoices';
    $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invoices_table WHERE id = %d", $invoice_id));
    if ($invoice) {
        // Generate the PDF URL from the invoice
        $pdf_url = create_pdf_from_invoice($invoice);
    
        // Return success response with the PDF URL
        echo wp_json_encode([
            'success' => true,
            'pdf_url' => esc_url($pdf_url)
        ]);
    } else {
        // Return failure response
        echo wp_json_encode([
            'success' => false
        ]);
    }
    // Ensure proper termination of script execution
    wp_die();    
}
add_action('wp_ajax_generate_invoice_pdf', 'generate_invoice_pdf');
add_action('wp_ajax_nopriv_generate_invoice_pdf', 'generate_invoice_pdf');

function create_pdf_from_invoice($invoice)
{
    global $wpdb;
    $clients_table = $wpdb->prefix . 'bms_clients';
    $items_table = $wpdb->prefix . 'bms_invoice_items';
    $company_table = $wpdb->prefix . 'bms_company';

    // Get client details
    $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $invoice->client_id));
    // Get company details
    $company = $wpdb->get_row("SELECT * FROM $company_table LIMIT 1");
    // Get invoice items
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $items_table WHERE invoice_id = %d", $invoice->id));

    $englishText = [
        'date' => 'Date',
        'pelatis' => 'Client',
        'perigrafi' => 'Description',
        'monadas' => 'Unit price',
        'posotita' => 'Quantity',
        'timi' => 'Price',
        'synolo_prin' => '<b>Total</b> before VAT',
        'delivery_time' => 'Delivery time',
        'fpa' => 'VAT',
        'syn' => 'plus VAT',
        'symp' => 'VAT incl.',
        'synolo_meta1' => 'Total after VAT',
        'ekptosi' => 'Discount',
        'synolo_meta2' => 'Total after discount',
        'trapeza' => 'Bank details',
        'efxarist' => 'Thanks for doing business with us'
    ];

    $greekText = [
        'date' => 'Ημερομηνία',
        'pelatis' => 'Πελάτης',
        'perigrafi' => 'Περιγραφή',
        'monadas' => 'Τιμή μονάδας',
        'posotita' => 'Ποσότητα',
        'timi' => 'Τιμή',
        'synolo_prin' => '<b>Σύνολο</b> πριν το ΦΠΑ',
        'fpa' => 'Φ.Π.Α.',
        'syn' => 'συν ΦΠΑ',
        'symp' => 'ΦΠΑ συμπεριλ.',
        'synolo_meta1' => 'Σύνολο (μετά το ΦΠΑ)',
        'ekptosi' => 'Εκπτωση',
        'synolo_meta2' => 'Σύνολο (μετά την εκπτωση)',
        'trapeza' => 'Τραπεζ. λογαριασμός',
        'efxarist' => 'Ευχαριστούμε για τη συνεργασία'
    ];

    $textVals = $invoice->lang == 'en' ? $englishText : $greekText;


    // Load TCPDF only when generating a PDF
    load_tcpdf();

    // Create new PDF document
    $pdf = new TCPDF();

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($company->company_name);
    $pdf->SetTitle('Invoice #' . $invoice->invoice_no);
    $pdf->SetSubject('Invoice');

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set font
    $pdf->SetFont('dejavusans', '', 10);

    // Add a page
    $pdf->AddPage();

    // Set content
    $tbl = <<<EOD
    <table cellspacing="0" cellpadding="1">
        <tr>
            <td>
                <p>{$company->company_name}</p>
                <p>{$company->address}</p>
                <p>{$company->phone1}<br>{$company->email}<br></p>
            </td><!--end of left top-->
            <td>
                    <span style='text-align:right;'># <b>{$invoice->invoice_no}</b></span>
                    <p>
                        {$textVals['date']}: {$invoice->creation_date}<br>
                    </p>
                    <p>&nbsp;</p>
                    <p><u>{$textVals['pelatis']}:</u><br>{$client->name}<br>{$client->address}<br>{$client->phone}<br>{$client->email}<br>
                    </p>
            </td><!--end of right top-->
            </tr>
    </table>
    EOD;

    $pdf->writeHTML($tbl, true, false, false, false, '');

    $html = <<<EOD
    <table cellspacing="0" cellpadding="1" style="border: 1px dotted gray; width: 98%;">
        <thead>
                    <tr>
                        <th style="width: 37%;"> {$textVals['perigrafi']}</th>
                        <th style="width: 21%;">{$textVals['monadas']}</th>
                        <th style="width: 21%;">{$textVals['posotita']}</th>
                        <th style="width: 21%;">{$textVals['timi']}</th>
                    </tr>
                </thead>
                <tbody>
    EOD;

    foreach ($items as $item) {
        $html .= <<<EOD
                <tr>
                        <td style="width: 37%;"> {$item->description}</td>
                        <td style="width: 21%;">{$item->unit_price}</td>
                        <td style="width: 21%;">{$item->quantity}</td>
                        <td style="width: 21%;">{$item->price}</td>
                    </tr>
    EOD;
    }

    $syn_symp = ($invoice->plus_inc_vat == 1) ? $textVals['syn'] : $textVals['symp'];

    $html .= <<<EOD
                <tr>
                    <td> {$textVals['synolo_prin']}</td><td></td><td></td><td>{$invoice->total_before_vat}</td>
                </tr>
                <tr>
                    <td> {$textVals['fpa']} ({$invoice->vat}%) ({$syn_symp})</td><td></td><td></td><td>{$invoice->vat_price}</td>
                </tr>
                <tr>
                    <td><strong> {$textVals['synolo_meta1']}:</strong></td><td></td><td></td><td><b>{$invoice->total_after_vat}</b></td>
                </tr>
    EOD;

    if ($invoice->discount_val > 0) {
        $html .= <<<EOD
                <tr>
                    <td> {$textVals['ekptosi']}: {$invoice->discount_description}</td><td></td><td></td><td>{$invoice->discount_val}</td>
                </tr>
                <tr>
                    <td><b> {$textVals['synolo_meta2']}:</b></td><td></td><td></td><td>{$invoice->total_after_discount}</td>
                </tr>
    EOD;
    }

    $html .= <<<EOD
                </tbody>
            </table>
    EOD;

    $pdf->writeHTML($html, true, false, false, false, '');


    // Set content
    $tbl = <<<EOD
    <table cellspacing="0" cellpadding="1">
        <tr>
            <td><strong>{$textVals['trapeza']}:</strong><br>{$company->bank_details}</td>
        </tr>
        <tr>
            <td></td>
        </tr>
        <tr>
            <td style="text-align:center">{$textVals['efxarist']}</td>
        </tr>
    </table>
    EOD;

    $pdf->writeHTML($tbl, true, false, false, false, '');
    // Close and output PDF document
    $upload_dir = wp_upload_dir();
    $pdf_file = $upload_dir['path'] . '/invoice_' . $invoice->id . '.pdf';
    $pdf->Output($pdf_file, 'F');

    // Return the URL of the generated PDF
    return $upload_dir['url'] . '/invoice_' . $invoice->id . '.pdf';
}
//-----------End PDF handler function for invoices---------


// Handle AJAX request to send invoice to client
add_action('wp_ajax_send_invoice_to_client', 'send_invoice_to_client');
add_action('wp_ajax_nopriv_send_invoice_to_client', 'send_invoice_to_client');

function send_invoice_to_client()
{
    global $wpdb;
    $invoice_id = intval($_POST['invoice_id']);
    $invoices_table = $wpdb->prefix . 'bms_invoices';
    $clients_table = $wpdb->prefix . 'bms_clients';

    $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invoices_table WHERE id = %d", $invoice_id));
    if ($invoice) {
        // Retrieve the client associated with the invoice
        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $clients_table WHERE id = %d", $invoice->client_id));
    
        if ($client) {
            // Generate PDF URL for the invoice
            $pdf_url = create_pdf_from_invoice($invoice);
    
            // Set up the email parameters
            $to = $client->email;
            $subject = 'Invoice #' . $invoice->invoice_no;
            $message = 'Dear ' . $client->name . ',<br><br>Please find attached the invoice.<br><br>';
            $message .= "<a href='" . esc_url($pdf_url) . "'>View Invoice</a>";
            $headers = ['Content-Type: text/html; charset=UTF-8'];
    
            // Send the email
            $sent = wp_mail($to, $subject, $message, $headers);
    
            // Return the response
            if ($sent) {
                echo wp_json_encode(['success' => true, 'email' => $to]);
            } else {
                echo wp_json_encode(['success' => false, 'error' => 'wp_mail failed.']);
            }
        } else {
            echo wp_json_encode(['success' => false, 'error' => 'Client not found.']);
        }
    } else {
        echo wp_json_encode(['success' => false, 'error' => 'Invoice not found.']);
    }
    
    // Ensure proper termination of script execution
    wp_die();    
}
//end email invoice

add_action('wp_ajax_cancel_invoice', 'cancel_invoice');
add_action('wp_ajax_nopriv_cancel_invoice', 'cancel_invoice');

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
