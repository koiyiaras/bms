<?php

/*
 * Add BMS menu to the Admin Control Panel
 */
add_action('admin_menu', 'bms_Add_My_Admin_Link');

// Add the BMS menu with Settings as the first submenu
function bms_Add_My_Admin_Link()
{
    // Add the top-level menu page (parent BMS)
    add_menu_page(
        'Business Management System', // Title of the page
        'BMS', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'bms-settings', // Menu slug
        'bms_settings_page_callback', // Callback function to display the page content
        '', // Optional: Set an icon URL or Dashicon class
        2 // Optional: Position of the menu item
    );

    // Rename the first automatically added submenu to "Settings" and link it to the Settings page
    add_submenu_page(
        'bms-settings', // Parent slug
        'Business Management System - Settings', // Title of the page
        'Settings', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'bms-settings', // Menu slug for the settings
        'bms_settings_page_callback' // Callback function to display the settings page content
    );

    // Add the "Company" submenu item
    add_submenu_page(
        'bms-settings', // Parent slug
        'BMS - Company Details', // Title of the page
        'Company', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'bms/includes/bms-company-page.php' // Menu slug or URL of the page
    );

    // Add the "Materials" submenu item
    add_submenu_page(
        'bms-settings', // Parent slug
        'Materials - Add new', // Title of the page
        'Materials', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'bms/includes/bms-materials-page.php' // Menu slug or URL of the page
    );

    // Add the "Materials" submenu item
    add_submenu_page(
        'bms-settings', // Parent slug
        'Remove wrong entry', // Title of the page
        'Deletes', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'bms/includes/bms-del-wrong-page.php' // Menu slug or URL of the page
    );
}

// Callback function for the Settings page content
function bms_settings_page_callback()
{
    echo "
    <div class='settings-box'>
      <h1 style='text-align:center'>Business Management System</h1>
      <h2>To setup BMS, add the following [shortcodes] to your pages</h2>
      <div>
        Dashboard page: <strong>[load_dashboard]</strong>
      </div>
      <div>
        Clients page: <strong>[load_clients]</strong>
      </div>
       <div>
        Quotes page: <strong>[load_quote]</strong>
      </div>
      <div>
        Invoices page: <strong>[load_invoice]</strong>
      </div>
      <div>
        Balances page: <strong>[load_balances]</strong>
      </div>
      <div>
        Projects page: <strong>[load_project]</strong>
      </div>
       <div>
        Stock page: <strong>[load_stock]</strong>
      </div>
       <div>
        Tasks page: <strong>[load_tasks]</strong>
      </div>
    </div>
    ";

}
