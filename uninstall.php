<?php
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Define all table names
$tables = [
    $wpdb->prefix . 'bms_balances',
    $wpdb->prefix . 'bms_clients',
    $wpdb->prefix . 'bms_company',
    $wpdb->prefix . 'bms_invoices',
    $wpdb->prefix . 'bms_invoice_items',
    $wpdb->prefix . 'bms_materials',
    $wpdb->prefix . 'bms_materials_other',
    $wpdb->prefix . 'bms_projects',
    $wpdb->prefix . 'bms_project_costs',
    $wpdb->prefix . 'bms_project_items',
    $wpdb->prefix . 'bms_quotes',
    $wpdb->prefix . 'bms_quote_items',
    $wpdb->prefix . 'bms_stock_in',
    $wpdb->prefix . 'bms_stock_out',
    $wpdb->prefix . 'bms_tasks',
];

// Drop each table
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

/* Optionally, delete any plugin-specific options or transients
delete_option('bms_plugin_version'); // Example: Delete a plugin version option
delete_option('bms_plugin_settings'); // Example: Delete plugin settings
*/