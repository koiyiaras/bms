<?php

/**
* Plugin Name: BMS - Business Management System
* Plugin URI: https://tre-host.com/
* Description: Manage small business: Create Quotes, invoices and manage sales and payments.
* Version: 1.0.0
* Author: G Michail
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
**/

require_once plugin_dir_path(__FILE__) . 'includes/bms-functions.php';

// Enqueue styles and scripts
function bms_enqueue_scripts()
{
    if (is_admin()) {
        return;
    } // Prevents scripts from loading in the WordPress admin panel

    wp_enqueue_script('bootstrap_js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    wp_enqueue_style('bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', false, null, 'all');
    wp_enqueue_script('valcode', plugins_url('/valcode.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_style('style', plugins_url('/style.css', __FILE__), false, '1.0', 'all');
    wp_enqueue_style('dashicons');
    // Enqueue Select2
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', ['jquery'], null, true);
    wp_enqueue_script('my-plugin-select2-init', plugins_url('libraries/select2/select2-init.js', __FILE__), ['jquery', 'select2-js'], null, true);



    // Localize AJAX URLs for scripts
    $ajax_urls = [
        'quoteAjax', 'modifyAjax', 'saveNewAjax', 'pdfAjax',
        'emailQuoteAjax', 'deleteQuoteAjax', 'invoiceAjax',
        'modifyInvAjax', 'saveNewInvAjax', 'pdfInvAjax',
        'emailInvoiceAjax', 'cancelInvoiceAjax',
        'deleteBalanceAjax', 'editBalanceAjax'
    ];
    foreach ($ajax_urls as $ajax) {
        wp_localize_script('valcode', $ajax, ['ajaxurl' => admin_url('admin-ajax.php')]);
    }
}
add_action('wp_enqueue_scripts', 'bms_enqueue_scripts');

// Clients page
/** Create client code ========================================================== */
add_shortcode('load_clients', 'bms_clients');

function bms_clients()
{
    // Check if the current user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

    global $wpdb;
    // Start output buffering
    ob_start();
    include plugin_dir_path(__FILE__) . 'clients.php';
    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of create client function

/** Dashboard code ========================================================== */
add_shortcode('load_dashboard', 'bms_dashboard_page');
function bms_dashboard_page()
{
    // Check if the current user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

    global $wpdb;
    // Start output buffering
    ob_start();

    /** The code to save new code in db */
    include plugin_dir_path(__FILE__) . 'dashboard.php';

    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of dashboard page


/** Invoices code */
add_shortcode('load_invoice', 'bms_invoices');
function bms_invoices()
{
    // Check if the current user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }
    global $wpdb;
    // Start output buffering
    ob_start();

    /** The code to save new code in db */
    include plugin_dir_path(__FILE__) . 'save-invoice.php';
    include plugin_dir_path(__FILE__) . 'invoices.php';

    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of invoices page

/** Quotes code */
add_shortcode('load_quote', 'bms_quotes');

function bms_quotes()
{
    // Check if the current user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

    global $wpdb;
    // Start output buffering
    ob_start();

    /** The code to save new code in db */
    include plugin_dir_path(__FILE__) . 'save-quote.php';
    /** The quotes code */
    include plugin_dir_path(__FILE__) . 'quotes.php';

    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of quotes function

/** Balances code ========================================================== */
add_shortcode('load_balances', 'bms_balances');

function bms_balances()
{
    // Check if the current user is an administrator
    if (!current_user_can('administrator')) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

    global $wpdb;
    // Start output buffering
    ob_start();

    $current_date = current_time('Y-m-d'); // Format the current date as yyyy-mm-dd
    
    include plugin_dir_path(__FILE__) . 'save-balance.php';
    /** Balances code */
    include plugin_dir_path(__FILE__) . 'balances.php';
    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of balances code

// Project page
/** Projects code ========================================================== */
add_shortcode('load_project', 'bms_projects');
function bms_projects()
{
    // Check if the current user is an administrator
    //if (!current_user_can('administrator')) {
    //  return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    //}

    global $wpdb;
    // Start output buffering
    ob_start();
    include plugin_dir_path(__FILE__) . 'save-project.php';
    include plugin_dir_path(__FILE__) . 'projects.php';
    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of projects function

// Stock page
/** stock code ========================================================== */
add_shortcode('load_stock', 'bms_stock');
function bms_stock()
{
    // Check if the current user is an administrator
    //if (!current_user_can('administrator')) {
    //  return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    //}

    global $wpdb;
    // Start output buffering
    ob_start();
    include plugin_dir_path(__FILE__) . 'stock.php';
    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of stock function


function bms_create_tables()
{
    global $wpdb;

    // Table names
    $table_balances = $wpdb->prefix . 'bms_balances';
    $table_clients = $wpdb->prefix . 'bms_clients';
    $table_company = $wpdb->prefix . 'bms_company';
    $table_invoices = $wpdb->prefix . 'bms_invoices';
    $table_invoice_items = $wpdb->prefix . 'bms_invoice_items';
    $table_materials = $wpdb->prefix . 'bms_materials';
    $table_projects = $wpdb->prefix . 'bms_projects';
    $table_project_items = $wpdb->prefix . 'bms_project_items';
    $table_quotes = $wpdb->prefix . 'bms_quotes';
    $table_quote_items = $wpdb->prefix . 'bms_quote_items';
    $table_stock_in = $wpdb->prefix . 'bms_stock_in';
    $table_stock_out = $wpdb->prefix . 'bms_stock_out';

    // SQL to create tables
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE $table_balances (
        id INT(11) NOT NULL AUTO_INCREMENT,
        in_out INT(11) NOT NULL,
        rel_invoice VARCHAR(255) NOT NULL,
        rel_project INT(11) NOT NULL,
        description TEXT,
        payer_payee VARCHAR(255) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type_of_payment VARCHAR(255) DEFAULT NULL,
        payment_date DATE DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_clients (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name TEXT NOT NULL,
        phone TEXT,
        email TEXT,
        address TEXT,
        extra TEXT,
        partner INT(3) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_company (
        id INT(11) NOT NULL AUTO_INCREMENT,
        company_name VARCHAR(500) NOT NULL,
        registration VARCHAR(100) NOT NULL,
        vat_number VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        phone1 VARCHAR(100) NOT NULL,
        phone2 VARCHAR(100) NOT NULL,
        fax VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        vat_prefered DECIMAL(10,2) NOT NULL,
        bank_details TEXT NOT NULL,
        thanks_msg INT(11) NOT NULL,
        last_stock_select INT(11) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_invoices (
        id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_no VARCHAR(30) NOT NULL,
        client_id INT(11) DEFAULT NULL,
        creation_date VARCHAR(50) NOT NULL,
        include VARCHAR(200) NOT NULL,
        vat FLOAT NOT NULL,
        plus_inc_vat VARCHAR(50) NOT NULL,
        vat_price DECIMAL(10,2) NOT NULL,
        total_before_vat DECIMAL(10,2) NOT NULL,
        total_after_vat DECIMAL(10,2) NOT NULL,
        product_description TEXT NOT NULL,
        discount_description VARCHAR(1000) NOT NULL,
        discount_val DECIMAL(10,2) NOT NULL,
        total_after_discount DECIMAL(10,2) NOT NULL,
        lang TEXT,
        status VARCHAR(250) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON UPDATE NO ACTION
    ) $charset_collate;

    CREATE TABLE $table_invoice_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_id INT(11) DEFAULT NULL,
        description TEXT,
        unit_price DECIMAL(10,2) DEFAULT NULL,
        quantity INT(11) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (invoice_id) REFERENCES $table_invoices(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_materials (
        id INT(11) NOT NULL AUTO_INCREMENT,
        type TEXT,
        mikos DECIMAL(10,2) DEFAULT NULL,
        platos DECIMAL(10,2) DEFAULT NULL,
        cost DECIMAL(10,2) DEFAULT NULL,
        notes TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_projects (
        id INT(11) NOT NULL AUTO_INCREMENT,
        client_id INT(11) DEFAULT NULL,
        description TEXT NOT NULL,
        creation_date VARCHAR(50) NOT NULL,
        vat FLOAT DEFAULT NULL,
        plus_inc_vat VARCHAR(50) DEFAULT NULL,
        vat_price DECIMAL(10,2) DEFAULT NULL,
        total_before_vat DECIMAL(10,2) DEFAULT NULL,
        total_after_vat DECIMAL(10,2) DEFAULT NULL,
        discount_description VARCHAR(1000) DEFAULT NULL,
        discount_val DECIMAL(10,2) DEFAULT NULL,
        total_after_discount DECIMAL(10,2) DEFAULT NULL,
        status VARCHAR(250) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_project_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) DEFAULT NULL,
        type TEXT,
        mikos DECIMAL(10,2) DEFAULT NULL,
        platos DECIMAL(10,2) DEFAULT NULL,
        area DECIMAL(10,2) DEFAULT NULL,
        quantity INT(11) DEFAULT NULL,
        unit_price DECIMAL(10,2) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_quotes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        quote_no VARCHAR(30) NOT NULL,
        client_id INT(11) DEFAULT NULL,
        creation_date VARCHAR(50) NOT NULL,
        valid_until VARCHAR(50) NOT NULL,
        include VARCHAR(200) NOT NULL,
        vat FLOAT NOT NULL,
        plus_inc_vat VARCHAR(50) NOT NULL,
        vat_price DECIMAL(10,2) NOT NULL,
        total_before_vat DECIMAL(10,2) NOT NULL,
        total_after_vat DECIMAL(10,2) NOT NULL,
        product_description TEXT NOT NULL,
        discount_description VARCHAR(1000) NOT NULL,
        discount_val DECIMAL(10,2) NOT NULL,
        total_after_discount DECIMAL(10,2) NOT NULL,
        delivery_time TEXT,
        lang TEXT,
        PRIMARY KEY (id),
        FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON UPDATE NO ACTION
    ) $charset_collate;

    CREATE TABLE $table_quote_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        quote_id INT(11) DEFAULT NULL,
        description TEXT,
        unit_price DECIMAL(10,2) DEFAULT NULL,
        quantity INT(11) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (quote_id) REFERENCES $table_quotes(id) ON DELETE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_stock_in (
        id INT(11) NOT NULL AUTO_INCREMENT,
        material_id INT(11) NOT NULL,
        in_mikos DECIMAL(10,2) DEFAULT NULL,
        in_platos DECIMAL(10,2) DEFAULT NULL,
        date DATE NOT NULL,
        notes TEXT,
        PRIMARY KEY (id),
        FOREIGN KEY (material_id) REFERENCES $table_materials(id) ON DELETE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_stock_out (
        id INT(11) NOT NULL AUTO_INCREMENT,
        material_id INT(11) NOT NULL,
        out_mikos DECIMAL(10,2) DEFAULT NULL,
        out_platos DECIMAL(10,2) DEFAULT NULL,
        date DATE NOT NULL,
        client_id INT(11) DEFAULT NULL,
        project_id INT(11) DEFAULT NULL,
        notes TEXT,
        assigned VARCHAR(50) NOT NULL DEFAULT '0',
        PRIMARY KEY (id),
        FOREIGN KEY (material_id) REFERENCES $table_materials(id) ON DELETE CASCADE,
        FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON UPDATE NO ACTION,
        FOREIGN KEY (project_id) REFERENCES $table_projects(id) ON UPDATE NO ACTION
    ) $charset_collate;
    ";

    // Execute the SQL to create tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);

    // Check if any tables were created or updated
    if (is_array($result) && !empty($result)) {
        foreach ($result as $table_name) {
            error_log("Table created or updated: $table_name");
        }
    } else {
        error_log('No tables created or updated');
    }
}
// Register activation hook to create tables
register_activation_hook(__FILE__, 'bms_create_tables');

include plugin_dir_path(__FILE__) . 'ajax_fns.php';
