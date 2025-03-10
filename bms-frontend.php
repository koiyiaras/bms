<?php

/**
* Plugin Name: BMS - Business Management System
* Plugin URI: https://tre-host.com/
* Description: Manage small business: Create Quotes, invoices and manage sales and payments.
* Version: 1.0.1
* Author: G Michail
* Author URI: https://tre-host.com/
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
        'quoteAjax', 'modifyAjax', 'saveNewAjax', 'deleteQuoteAjax', 
        'invoiceAjax', 'modifyInvAjax', 'saveNewInvAjax', 'cancelInvoiceAjax',
        'deleteBalanceAjax', 'editBalanceAjax', 'projInvAjax'
    ];
    foreach ($ajax_urls as $ajax) {
        wp_localize_script('valcode', $ajax, ['ajaxurl' => admin_url('admin-ajax.php')]);
    }
}
add_action('wp_enqueue_scripts', 'bms_enqueue_scripts');

/** Add the style.css for admin pages */
function bms_enqueue_admin_styles() {
    // Enqueue styles for the admin area
    wp_enqueue_style('bms-admin-style', plugins_url('/includes/style.css', __FILE__), array(), '1.0', 'all');
}
add_action('admin_enqueue_scripts', 'bms_enqueue_admin_styles');

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

// tasks page
/** Create client code ========================================================== */
add_shortcode('load_tasks', 'bms_tasks');

function bms_tasks()
{
    // Check if the current user is an administrator
    if (!is_user_logged_in()) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }
    global $wpdb;
    // Start output buffering
    ob_start();
    include plugin_dir_path(__FILE__) . 'tasks.php';
    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of tasks page

/** Dashboard code ========================================================== */
add_shortcode('load_dashboard', 'bms_dashboard_page');
function bms_dashboard_page()
{
    // Check if logged in (give access on admin-worker in some areas)
    if (!is_user_logged_in()) {
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
    if (!current_user_can('administrator')) {
      return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

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
    if (!is_user_logged_in()) {
        return '<div class="alert alert-danger">You don\'t have permission to access this content.</div>';
    }

    global $wpdb;
    // Start output buffering
    ob_start();
    include plugin_dir_path(__FILE__) . 'stock.php';
    // Output buffering ends and content is returned
    return ob_get_clean();
}//end of stock function

// Check if a GET parameter for viewrange is set and update the database
if (isset($_GET['viewrange'])) {
    global $wpdb;
    $viewrange = sanitize_text_field($_GET['viewrange']);
    $wpdb->update(
        $wpdb->prefix . 'bms_company',
        array('view_range' => $viewrange),
        array('id' => 1), // Assuming you are updating the first record, adjust as necessary
        array('%s'),
        array('%d')
    );
}
function view_range(){
    global $wpdb;
    $view_range = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT view_range FROM {$wpdb->prefix}bms_company WHERE id = %d",
        1
    ));
    return $view_range;
}

function years_query($view_range, $dateFieldName){
    $current_year = gmdate('Y');
    $start_date = '';
    $end_date = '';
    $today = date('Y-m-d');

    if ($view_range == 'current') {
        $start_date = "$current_year-01-01";
        $end_date = $today;
        }
        elseif ($view_range == 'two') {
            $start_year = $current_year -1;
            $start_date = "$start_year-01-01";
            $end_date = $today;
        }
        else {
            $start_date = "$view_range-01-01";
            $end_date = "$view_range-12-31";
        }

        $query = " AND ".$dateFieldName." BETWEEN '$start_date' AND '$end_date'";
        return $query;
}

function years_links($years_list, $view_range, $redirect, $anchor = ''){
    global $wpdb;
    // Get the home URL
    $home_url = home_url($redirect);
    if ($view_range == 'all') {
        $years_links = '<u>[All]</u>';
    } else {
        $years_links = '<a href="' . $home_url . '/?viewrange=all#'.$anchor.'">[All]</a>';
    }
    if ($view_range == 'current') {
        $years_links .= ' [Current]';
    } else {
        $years_links .= ' <a href="' . $home_url . '/?viewrange=current#'.$anchor.'">[Current]</a>';
    }
    if ($view_range == 'two') {
        $years_links .= ' [Last 2 years]';
    } else {
        $years_links .= ' <a href="' . $home_url . '/?viewrange=two#'.$anchor.'">[Last 2 years]</a>';
    }
    // Output the years
    foreach ($years_list as $year) {
        if (intval($view_range) == intval($year)) {
            $years_links .= " [$year]";
        } else {
            $years_links .= ' <a href="' . $home_url . '/?viewrange=' . $year.'#'.$anchor.'">[' . $year . ']</a>';
        }
    }
    return "Range: " . $years_links;
}

function client_project_select($clients, $projects, $styling = ''){
    ?>
    <label for="client_s" class="form-label <?php echo $styling; ?>">Client - Project</label>
    <select id="client_s" name="client_s" class="form-select">
        <option value="unassigned" selected>Not set</option>
        <option value="new">New client & project</option>
        <!-- PHP code to loop through clients -->
        <?php foreach ($clients as $client): ?>
            <option value="<?php echo esc_attr($client->id); ?>-0"><?php echo esc_html($client->name); ?> - New</option>
            <?php foreach ($projects as $project):
                if ($project->client_id == $client->id && $project->status !== 'COMPLETED') { ?>
                <option value="<?php echo esc_attr($client->id); ?>-<?php echo esc_attr($project->id); ?>"><?php echo esc_html($client->name); ?> - <?php echo esc_html($project->description); ?></option>
            <?php }
                endforeach;
        endforeach; ?>
    </select>
    <input type="text" name="new_client" class="form-control d-none mt-2" id="new_client_input" placeholder="New client name">
    <input type="text" name="new_project" class="form-control d-none mt-2" id="new_project_input" placeholder="New project name">

    <?php
}

function load_prices_block($ref = 'inv', $materials, $materials_other){
    //global $materials, $materials_other;//doesn't work

    if ($ref == 'inv'){
        $refClassMat = 'add-mat-template';
        $refClassOtherList = "add-other-list-template";
        $refClassOther = "add-other-template";
        $thisContainer = 'items-container';
        $additional_close_action = 'recalc';
    }else{
        $refClassMat = 'add-mat-template-cost';
        $refClassOtherList = "add-other-list-template-cost";
        $refClassOther = "add-other-template-cost";
        $thisContainer = 'items-container-cost';
        $additional_close_action = 'recalc';
    }
    ?>
     <!--here add a new line hide it until the new line button is clicked make somehow different button to add next new lines with js-->
     <div id="<?php echo $thisContainer; ?>">
        <div class="row mb-3 d-none border-bottom pb-2 position-relative dynamic-line <?php echo $refClassMat; ?>">  
            <button type="button" class="btn-close position-absolute top-0 end-0 <?php echo $additional_close_action; ?>" aria-label="Close" style="font-size: 0.55rem;margin-right:1em;"></button>
            <input type="hidden" name="counter[]" value="dump" /> 
            <div class="col-md-3">
                <select name="mat_type2[]" class="form-select mat-type-checker" required>
                    <option value=0 selected>SELECT TYPE</option>
                    <?php foreach ($materials as $material): ?>
                        <option value='<?php echo esc_attr($material->type); ?>'><?php echo esc_html($material->type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-inline-flex">
                <div>
                    <input type="text" name="new-item-length2[]" value="0" class="form-control only-num" required>
                </div>
                <div class="mx-2">x</div>
                <div>
                    <input type="text" name="new-item-width2[]" value="0" class="form-control only-num" required>
                </div>
            </div>
            <div class="col-md-1">
                <input type="text" name="pr_item-quantity2[]" value="1" class="form-control item-quantity only-num" required="">
            </div>
            <div class="col-md-2">
                <input type="text" name="pr_unit-price2[]" value="0" class="form-control unit-price only-num" required="">
            </div>
            <div class="col-md-2">
                <input type="text" name="pr_price2[]" value="0" readonly="" class="form-control show-price" required="">
            </div>
        </div><!--end of first material--> 
    

        <div class="row mb-3 d-none border-bottom pb-2 position-relative dynamic-line <?php echo $refClassOtherList; ?>">  
            <button type="button" class="btn-close position-absolute top-0 end-0 <?php echo $additional_close_action; ?>" aria-label="Close" style="font-size: 0.55rem;margin-right:1em;"></button>
            <input type="hidden" name="counter[]" value="dump" /> 
            <div class="col-md-6">
                <select name="mat_type4[]" class="form-select mat-type-checker" required>
                    <option value=0 selected>SELECT TYPE</option>
                    <?php foreach ($materials_other as $material): ?>
                        <option value='<?php echo esc_attr($material->description); ?>'><?php echo esc_html($material->description); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <input type="text" name="pr_item-quantity4[]" value="1" class="form-control item-quantity only-num" required="">
            </div>
            <div class="col-md-2">
                <input type="text" name="pr_unit-price4[]" value="0" class="form-control unit-price only-num" required="">
            </div>
            <div class="col-md-2">
                <input type="text" name="pr_price4[]" value="0" readonly="" class="form-control show-price" required="">
            </div>
        </div><!--end of list other--> 

        <div class="row mb-3 d-none border-bottom pb-2 position-relative dynamic-line <?php echo $refClassOther; ?>">   
            <button type="button" class="btn-close position-absolute top-0 end-0 <?php echo $additional_close_action; ?>" aria-label="Close" style="font-size: 0.55rem;margin-right:1em;"></button>
            <input type="hidden" name="counter[]" value="dump" />
            <div class="col-md-6">
                <input type="text" name="mat_type3[]" placeholder="Material name" class="form-control mat-name" required>
            </div>
            <div class="col-md-1">
                <input type="text" name="pr_item-quantity3[]" value="1" class="form-control item-quantity only-num" required="">
            </div>
            <div class="col-md-2">
                <input type="text" name="pr_unit-price3[]" value="0" class="form-control unit-price only-num" required="">
            </div>
            <div class="col-md-2">
                <input type="text" name="pr_price3[]" value="0" readonly="" class="form-control show-price" required="">
            </div>
        </div><!--end of first other--> 

    </div><!-- of items container (for new items) -->
    <?php
}

function load_prices_buttons($ref = 'inv') {
    if ($ref == 'inv'){
        $add_mat_btn = "add-mat-line";
        $add_other_list_btn = "add-other-list-line";
        $add_other_btn = "add-other-line";
    }else{
        $add_mat_btn = "add-mat-line-cost";
        $add_other_list_btn = "add-other-list-line-cost";
        $add_other_btn = "add-other-line-cost";
    }
    echo '
    <div class="row mt-2 ' . ($ref == 'cost' ? 'buttons-row-costs d-none' : '') . '"> 
        <div class="col-md-6">
            <button type="button" id="'.$add_mat_btn.'" class="btn btn-sm btn-secondary">Add roll</button>
            <button type="button" id="'.$add_other_list_btn.'" class="btn btn-sm btn-secondary">Other (from list)</button>
            <button type="button" id="'.$add_other_btn.'" class="btn btn-sm btn-secondary">Other</button>
        </div>   
        <div class="col-md-5 text-end">
            <button name="' . ($ref == 'cost' ? 'matCost' : '') . '" type="' . ($ref == 'cost' ? 'submit' : 'button') . '" id="' . ($ref == 'cost' ? 'calc-pr-prices-cost' : 'calc-pr-prices') . '" class="btn btn-sm btn-dark px-3">' . ($ref == 'cost' ? 'Save cost' : 'Next') . '</button>
        </div>  
    </div>';
}

//saving clients/project from various pages done for Tasks, ... [complete when use]
function save_client_project($client_s){
    global $wpdb;

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
                    return $error_message;
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
        } elseif ($client_s == 'unassigned') {
            $client_id = NULL;
            $project_id = NULL;
        }else {
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
        return ['client_id' => $client_id, 'project_id' => $project_id];
}
/*** Delete task photos older than 1 month ***/
function delete_old_photos_from_tasks() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bms_tasks';

    // Calculate the date 1 month ago
    $one_month_ago = date('Y-m-d H:i:s', strtotime('-1 month'));

    // Fetch tasks older than 1 month
    $tasks = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, photos FROM $table_name WHERE created_at < %s",
            $one_month_ago
        )
    );

    if (empty($tasks)) {
        error_log('No tasks older than 1 month found.');
        return;
    }

    foreach ($tasks as $task) {
        $task_id = $task->id;
        $photos = json_decode($task->photos, true); // Decode JSON array

        if (empty($photos)) {
            continue;
        }

        foreach ($photos as $photo_url) {
            $photo_path = str_replace(site_url(), ABSPATH, $photo_url);

            if (file_exists($photo_path)) {
                unlink($photo_path); // Delete the file
                error_log('Deleted old photo: ' . $photo_path);
            }
        }

        // Update the database to remove photos
        $wpdb->update(
            $table_name,
            ['photos' => json_encode([])], // Ensure correct JSON format
            ['id' => $task_id]
        );

        error_log('Updated task ' . $task_id . ': Removed photos.');
    }
}

/** Project invoice print settings */
function get_checked($settings, $key) {
    return (isset($settings[$key]['set']) && $settings[$key]['set'] === 'set') || !isset($settings[$key]) ? 'checked' : '';
}

function get_value($settings, $key, $default) {
    return isset($settings[$key]['value']) ? $settings[$key]['value'] : $default;
}

function get_position($settings, $key, $default = 'center') {
    return isset($settings[$key]['position']) ? $settings[$key]['position'] : $default;
}

// Schedule the cron job on plugin activation
function schedule_photo_cleanup() {
    if (!wp_next_scheduled('delete_old_photos_event')) {
        wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'delete_old_photos_event'); // Run daily at midnight
    }
}
register_activation_hook(__FILE__, 'schedule_photo_cleanup');

// Hook the function to the scheduled event
add_action('delete_old_photos_event', 'delete_old_photos_from_tasks');

// Clear the schedule on plugin deactivation
function clear_photo_cleanup_schedule() {
    $timestamp = wp_next_scheduled('delete_old_photos_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'delete_old_photos_event');
    }
}
register_deactivation_hook(__FILE__, 'clear_photo_cleanup_schedule');

//Create tables operations
function bms_create_tables() {
    global $wpdb;

    $table_balances = $wpdb->prefix . 'bms_balances';
    $table_clients = $wpdb->prefix . 'bms_clients';
    $table_company = $wpdb->prefix . 'bms_company';
    $table_invoices = $wpdb->prefix . 'bms_invoices';
    $table_invoice_items = $wpdb->prefix . 'bms_invoice_items';
    $table_materials = $wpdb->prefix . 'bms_materials';
    $table_materials_other = $wpdb->prefix . 'bms_materials_other';
    $table_projects = $wpdb->prefix . 'bms_projects';
    $table_project_costs = $wpdb->prefix . 'bms_project_costs';
    $table_project_items = $wpdb->prefix . 'bms_project_items';
    $table_quotes = $wpdb->prefix . 'bms_quotes';
    $table_quote_items = $wpdb->prefix . 'bms_quote_items';
    $table_stock_in = $wpdb->prefix . 'bms_stock_in';
    $table_stock_out = $wpdb->prefix . 'bms_stock_out';
    $table_tasks = $wpdb->prefix . 'bms_tasks';

    // SQL to create tables
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE $table_balances (
        id INT(11) NOT NULL AUTO_INCREMENT,
        in_out INT(11) NOT NULL,
        rel_invoice INT(11) NOT NULL,
        rel_project INT(11) NOT NULL,
        description TEXT COLLATE utf8_unicode_ci,
        payer_payee VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        pay_method VARCHAR(250) COLLATE utf8_unicode_ci NOT NULL,
        type_of_payment VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        payment_date DATE DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_clients (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name TEXT COLLATE utf8_unicode_ci NOT NULL,
        phone TEXT COLLATE utf8_unicode_ci,
        email TEXT COLLATE utf8_unicode_ci,
        address TEXT COLLATE utf8_unicode_ci,
        extra TEXT COLLATE utf8_unicode_ci,
        partner INT(3) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_company (
        id INT(11) NOT NULL AUTO_INCREMENT,
        company_name VARCHAR(500) COLLATE utf8_unicode_ci NOT NULL,
        registration VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
        vat_number VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
        address TEXT COLLATE utf8_unicode_ci NOT NULL,
        phone1 VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
        phone2 VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
        fax VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
        email VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
        vat_prefered DECIMAL(10,2) NOT NULL,
        bank_details TEXT COLLATE utf8_unicode_ci NOT NULL,
        thanks_msg TEXT COLLATE utf8_unicode_ci NOT NULL,
        last_stock_select INT(11) NOT NULL,
        view_range VARCHAR(250) COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_invoices (
        id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_no VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL,
        client_id INT(11) DEFAULT NULL,
        creation_date VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
        include VARCHAR(200) COLLATE utf8_unicode_ci NOT NULL,
        vat FLOAT NOT NULL,
        plus_inc_vat VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
        vat_price DECIMAL(10,2) NOT NULL,
        total_before_vat DECIMAL(10,2) NOT NULL,
        total_after_vat DECIMAL(10,2) NOT NULL,
        product_description TEXT COLLATE utf8_unicode_ci NOT NULL,
        discount_description VARCHAR(1000) COLLATE utf8_unicode_ci NOT NULL,
        discount_val DECIMAL(10,2) NOT NULL,
        total_after_discount DECIMAL(10,2) NOT NULL,
        lang TEXT COLLATE utf8_unicode_ci,
        status VARCHAR(250) COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON UPDATE NO ACTION
    ) $charset_collate;

    CREATE TABLE $table_invoice_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_id INT(11) DEFAULT NULL,
        description TEXT COLLATE utf8_unicode_ci,
        unit_price DECIMAL(10,2) DEFAULT NULL,
        quantity INT(11) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (invoice_id) REFERENCES $table_invoices(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_materials (
        id INT(11) NOT NULL AUTO_INCREMENT,
        type TEXT COLLATE utf8_unicode_ci,
        mikos DECIMAL(10,2) DEFAULT NULL,
        platos DECIMAL(10,2) DEFAULT NULL,
        cost DECIMAL(10,2) DEFAULT NULL,
        notes TEXT COLLATE utf8_unicode_ci,
        badge_color VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_materials_other (
        id INT(11) NOT NULL AUTO_INCREMENT,
        other_type VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
        description VARCHAR(1000) COLLATE utf8_unicode_ci NOT NULL,
        quantity VARCHAR(2500) COLLATE utf8_unicode_ci DEFAULT NULL,
        notes VARCHAR(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_projects (
        id INT(11) NOT NULL AUTO_INCREMENT,
        client_id INT(11) DEFAULT NULL,
        description TEXT COLLATE utf8_unicode_ci NOT NULL,
        creation_date DATE NOT NULL,
        vat FLOAT DEFAULT NULL,
        plus_inc_vat VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT NULL,
        vat_price DECIMAL(10,2) DEFAULT NULL,
        total_before_vat DECIMAL(10,2) DEFAULT NULL,
        total_after_vat DECIMAL(10,2) DEFAULT NULL,
        discount_description VARCHAR(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
        discount_val DECIMAL(10,2) DEFAULT NULL,
        total_after_discount DECIMAL(10,2) DEFAULT NULL,
        status VARCHAR(250) COLLATE utf8_unicode_ci DEFAULT NULL,
        pr_inv_incl TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;

    CREATE TABLE $table_project_costs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) DEFAULT NULL,
        type TEXT COLLATE utf8_unicode_ci,
        mikos DECIMAL(10,2) DEFAULT NULL,
        platos DECIMAL(10,2) DEFAULT NULL,
        area DECIMAL(10,2) DEFAULT NULL,
        quantity INT(11) DEFAULT NULL,
        unit_price DECIMAL(10,2) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (project_id) REFERENCES $table_projects(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_project_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) DEFAULT NULL,
        type TEXT COLLATE utf8_unicode_ci,
        mikos DECIMAL(10,2) DEFAULT NULL,
        platos DECIMAL(10,2) DEFAULT NULL,
        area DECIMAL(10,2) DEFAULT NULL,
        quantity INT(11) DEFAULT NULL,
        unit_price DECIMAL(10,2) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (project_id) REFERENCES $table_projects(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_quotes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        quote_no VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL,
        client_id INT(11) DEFAULT NULL,
        creation_date VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
        valid_until VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
        include VARCHAR(200) COLLATE utf8_unicode_ci NOT NULL,
        vat FLOAT NOT NULL,
        plus_inc_vat VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
        vat_price DECIMAL(10,2) NOT NULL,
        total_before_vat DECIMAL(10,2) NOT NULL,
        total_after_vat DECIMAL(10,2) NOT NULL,
        product_description TEXT COLLATE utf8_unicode_ci NOT NULL,
        discount_description VARCHAR(1000) COLLATE utf8_unicode_ci NOT NULL,
        discount_val DECIMAL(10,2) NOT NULL,
        total_after_discount DECIMAL(10,2) NOT NULL,
        delivery_time TEXT COLLATE utf8_unicode_ci,
        lang TEXT COLLATE utf8_unicode_ci,
        PRIMARY KEY (id),
        FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON UPDATE NO ACTION
    ) $charset_collate;

    CREATE TABLE $table_quote_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        quote_id INT(11) DEFAULT NULL,
        description TEXT COLLATE utf8_unicode_ci,
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
        notes TEXT COLLATE utf8_unicode_ci,
        PRIMARY KEY (id),
        FOREIGN KEY (material_id) REFERENCES $table_materials(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) $charset_collate;

    CREATE TABLE $table_stock_out (
        id INT(11) NOT NULL AUTO_INCREMENT,
        material_id INT(11) NOT NULL,
        out_mikos DECIMAL(10,2) DEFAULT NULL,
        out_platos DECIMAL(10,2) DEFAULT NULL,
        date DATE NOT NULL,
        client_id INT(11) DEFAULT NULL,
        project_id INT(11) DEFAULT NULL,
        notes TEXT COLLATE utf8_unicode_ci,
        assigned VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
        in_reference INT(11) NOT NULL DEFAULT '0',
        in_ref_remain INT(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (id),
        FOREIGN KEY (material_id) REFERENCES $table_materials(id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON UPDATE NO ACTION,
        FOREIGN KEY (project_id) REFERENCES $table_projects(id) ON UPDATE NO ACTION
    ) $charset_collate;

    CREATE TABLE $table_tasks (
        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        task_date DATE NOT NULL,
        description LONGTEXT COLLATE utf8_unicode_ci NOT NULL,
        photos LONGTEXT COLLATE utf8_unicode_ci,
        checked_materials TEXT COLLATE utf8_unicode_ci,
        checked_others TEXT COLLATE utf8_unicode_ci,
        checked_tools TEXT COLLATE utf8_unicode_ci,
        start_time VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT NULL,
        end_time VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT NULL,
        map_location TEXT COLLATE utf8_unicode_ci,
        completion_status VARCHAR(50) COLLATE utf8_unicode_ci DEFAULT 'PENDING',
        worker_updated TINYINT(1) NOT NULL DEFAULT '0',
        return_materials TEXT COLLATE utf8_unicode_ci,
        return_others TEXT COLLATE utf8_unicode_ci,
        sort_order INT(11) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        client_id INT(11) DEFAULT NULL,
        project_id INT(11) DEFAULT NULL,
        PRIMARY KEY (id)
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
