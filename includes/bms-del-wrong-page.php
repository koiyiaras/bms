<?php
global $wpdb;

// Handle deletion for Stock In
if (isset($_POST['delete_stock_in_id'])) {
    $table = $wpdb->prefix . 'bms_stock_in';
    $delete_id = intval($_POST['delete_stock_in_id']);
    $result = $wpdb->delete($table, ['id' => $delete_id]);

    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Stock In record deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Something went wrong while deleting the Stock In record.</p></div>';
    }
}

// Handle bulk stock in Deletions
if (isset($_POST['delete_selected_stock_in']) && !empty($_POST['stock_in_ids'])) {
    $stock_in_ids = array_map('intval', $_POST['stock_in_ids']);
    $placeholders = implode(',', array_fill(0, count($stock_in_ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bms_stock_in WHERE id IN ($placeholders)", $stock_in_ids));
}

// Handle bulk Stock Out Deletion
if (isset($_POST['delete_selected_stock_out']) && !empty($_POST['stock_out_ids'])) {
    $stock_out_ids = array_map('intval', $_POST['stock_out_ids']);
    $placeholders = implode(',', array_fill(0, count($stock_out_ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bms_stock_out WHERE id IN ($placeholders)", $stock_out_ids));
}

// Handle deletion for Stock Out
if (isset($_POST['delete-stock-out'])) {
    $table = $wpdb->prefix . 'bms_stock_out';
    $delete_id = intval($_POST['delete_stock_out_id']);
    $result = $wpdb->delete($table, ['id' => $delete_id]);

    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Stock Out record deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Something went wrong while deleting the Stock Out record.</p></div>';
    }
}

// Handle return to roll
if (isset($_POST['delete-stock-out-return'])) {
    // Sanitize input
    $this_id = intval($_POST['delete_stock_out_id']); // Ensure the ID is an integer
    $table = $wpdb->prefix . 'bms_stock_out';

    // Secure query to get the row
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $this_id
    ));

    // Sanitize inputs
    $ref_roll = intval($row->in_reference); 
    $ref_remain = intval($row->in_ref_remain); 
    $add_length = floatval($row->out_mikos); // Make add_length a float
    $add_width = floatval($row->out_platos);
    $material_id = intval($row->material_id);

    if ($ref_remain > 0) {
        // Get row from prefix.bms_stock_in where id = $ref_remain
        $row_remain = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bms_stock_in WHERE id = %d",
            $ref_remain
        ));
        
        if ($row_remain) {
            $ref_remain_length = floatval($row_remain->in_mikos);
            $ref_remain_width = floatval($row_remain->in_platos);
        }
    }

    // Query prefix.bms_stock_in where id = $ref_roll
    $row_roll = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bms_stock_in WHERE id = %d",
        $ref_roll
    ));

    if ($row_roll) {
        // Update from prefix.bms_stock_in where id = $ref_roll
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}bms_stock_in SET in_mikos = in_mikos + %f WHERE id = %d",
            $add_length,
            $ref_roll
        ));
    } else {
        // Insert into prefix.bms_stock_in
        // Note: Directly setting id when it's auto_increment is not standard practice.
        // If required, remove this section or ensure id is not auto-increment.
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}bms_stock_in (id, material_id, in_mikos, in_platos) VALUES (%d, %d, %f, %f)",
            $ref_roll, // This is not recommended if id is auto_increment.
            $material_id,
            $add_length + $ref_remain_length,
            $add_width
        ));
    }

    if ($ref_remain > 0) {
        // Delete from prefix.bms_stock_in where id = $ref_remain
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}bms_stock_in WHERE id = %d",
            $ref_remain
        ));
    }
    //finally, delete the returned stock
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}bms_stock_out WHERE id = %d",
        $this_id
    ));
}//end return to roll

// Get Stock In Data
$stock_in_data = $wpdb->get_results("
    SELECT s.id, s.material_id, s.in_mikos, s.in_platos, s.date, s.notes, m.type
    FROM {$wpdb->prefix}bms_stock_in s
    JOIN {$wpdb->prefix}bms_materials m ON s.material_id = m.id
");

// Get Stock Out Data
$stock_out_data = $wpdb->get_results("
    SELECT s.id, s.material_id, s.out_mikos, s.out_platos, s.date, s.notes, m.type
    FROM {$wpdb->prefix}bms_stock_out s
    JOIN {$wpdb->prefix}bms_materials m ON s.material_id = m.id
");

$material_types = $wpdb->get_results("
    SELECT type
    FROM {$wpdb->prefix}bms_materials
");

?>
<style>
    
</style>

<h2>Stock In</h2>
<form method="post" id="stock_in_form">
<div class="form-controls">
    <div>
        <label for="stock_in_type_filter">Filter by Type:</label>
        <select id="stock_in_type_filter">
            <option value="">All</option>
            <?php foreach ($material_types as $type): ?>
                <option value="<?php echo esc_attr($type->type); ?>"><?php echo esc_html($type->type); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="stock_in_search_filter">Search:</label>
        <input type="text" id="stock_in_search_filter" placeholder="Search...">
    </div>
    <button type="submit" name="delete_selected_stock_in" class="button button-primary">Delete Selected</button>
</div>

<div class="bk-responsive">
    <table class="wp-list-table widefat fixed striped" id="stock_in_table">
        <thead>
            <tr>
                <th style="padding-left:2px;"><input type="checkbox" id="stock_in_select_all"></th>
                <th>Material Type</th>
                <th>Length (Mikos)</th>
                <th>Width (Platos)</th>
                <th>Date</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($stock_in_data): ?>
                <?php foreach ($stock_in_data as $stock_in): ?>
                    <tr>
                        <td data-label="Select"><input type="checkbox" name="stock_in_ids[]" class="stock_in_checkbox" value="<?php echo esc_attr($stock_in->id); ?>"></td>
                        <td data-label="Material Type" class="stock-in-type"><?php echo esc_html($stock_in->type); ?></td>
                        <td data-label="Length (Mikos)"><?php echo esc_html($stock_in->in_mikos); ?></td>
                        <td data-label="Width (Platos)"><?php echo esc_html($stock_in->in_platos); ?></td>
                        <td data-label="Date"><?php echo esc_html($stock_in->date); ?></td>
                        <td data-label="Notes"><?php echo esc_html($stock_in->notes); ?></td>
                        <td data-label="Actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_stock_in_id" value="<?php echo esc_attr($stock_in->id); ?>">
                                <button type="submit" class="button delete-stock-in" onclick="return confirm('Are you sure you want to delete this Stock In record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No Stock In records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</form>

<h2>Stock Out</h2>
<form method="post" id="stock_out_form">
<div class="form-controls">
    <div>
        <label for="stock_out_type_filter">Filter by Type:</label>
        <select id="stock_out_type_filter">
            <option value="">All</option>
            <?php foreach ($material_types as $type): ?>
                <option value="<?php echo esc_attr($type->type); ?>"><?php echo esc_html($type->type); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="stock_out_search_filter">Search:</label>
        <input type="text" id="stock_out_search_filter" placeholder="Search...">
    </div>
    <button type="submit" name="delete_selected_stock_out" class="button button-primary">Delete Selected</button>
</div>

<div class="bk-responsive">
    <table class="wp-list-table widefat fixed striped" id="stock_out_table">
        <thead>
            <tr>
                <th style="padding-left:2px;"><input type="checkbox" id="stock_out_select_all"></th>
                <th>Material Type</th>
                <th>Length (Mikos)</th>
                <th>Width (Platos)</th>
                <th>Date</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($stock_out_data): ?>
                <?php foreach ($stock_out_data as $stock_out): ?>
                    <tr>
                        <td data-label="Select"><input type="checkbox" name="stock_out_ids[]" class="stock_out_checkbox" value="<?php echo esc_attr($stock_out->id); ?>"></td>
                        <td data-label="Material Type" class="stock-out-type"><?php echo esc_html($stock_out->type); ?></td>
                        <td data-label="Length (Mikos)"><?php echo esc_html($stock_out->out_mikos); ?></td>
                        <td data-label="Width (Platos)"><?php echo esc_html($stock_out->out_platos); ?></td>
                        <td data-label="Date"><?php echo esc_html($stock_out->date); ?></td>
                        <td data-label="Notes"><?php echo esc_html($stock_out->notes); ?></td>
                        <td data-label="Actions">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_stock_out_id" value="<?php echo esc_attr($stock_out->id); ?>">
                                <button name="delete-stock-out-return" type="submit" class="button eq-width" onclick="return confirm('Are you sure you want to return this piece to its roll?')">Return to roll</button>
                                <button name="delete-stock-out" type="submit" class="button eq-width" onclick="return confirm('Are you sure you want to delete this Stock Out record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No Stock Out records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</form>

<script>
jQuery(document).ready(function ($) {
    //do not submit bulk delete when nothing is checked
    $('#stock_in_form').submit(function() {
        // Check if at least one checkbox is checked
        if ($('.stock_in_checkbox:checked').length === 0) {
            alert('Please select at least one stock in record to delete.');
            return false; // Prevent form submission
        }
    });

    $('#stock_out_form').submit(function() {
        // Check if at least one checkbox is checked
        if ($('.stock_out_checkbox:checked').length === 0) {
            alert('Please select at least one stock in record to delete.');
            return false; // Prevent form submission
        }
    });

    // Filter Stock In Table
    $('#stock_in_type_filter, #stock_in_search_filter').on('input change', function () {
        let typeFilter = $('#stock_in_type_filter').val().toLowerCase();
        let searchFilter = $('#stock_in_search_filter').val().toLowerCase();
        $('#stock_in_table tbody tr').each(function () {
            let typeText = $(this).find('.stock-in-type').text().toLowerCase();
            let matchType = !typeFilter || typeText.includes(typeFilter);
            let matchSearch = !searchFilter || $(this).text().toLowerCase().includes(searchFilter);
            $(this).toggle(matchType && matchSearch);
        });
    });

    // Select All for Stock In
    $('#stock_in_select_all').on('change', function () {
        $('.stock_in_checkbox').prop('checked', this.checked);
    });

    // Filter Stock Out Table
    $('#stock_out_type_filter, #stock_out_search_filter').on('input change', function () {
        let typeFilter = $('#stock_out_type_filter').val().toLowerCase();
        let searchFilter = $('#stock_out_search_filter').val().toLowerCase();
        $('#stock_out_table tbody tr').each(function () {
            let typeText = $(this).find('.stock-out-type').text().toLowerCase();
            let matchType = !typeFilter || typeText.includes(typeFilter);
            let matchSearch = !searchFilter || $(this).text().toLowerCase().includes(searchFilter);
            $(this).toggle(matchType && matchSearch);
        });
    });

    // Select All for Stock Out
    $('#stock_out_select_all').on('change', function () {
        $('.stock_out_checkbox').prop('checked', this.checked);
    });
});
</script>
