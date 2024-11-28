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

// Handle deletion for Stock Out
if (isset($_POST['delete_stock_out_id'])) {
    $table = $wpdb->prefix . 'bms_stock_out';
    $delete_id = intval($_POST['delete_stock_out_id']);
    $result = $wpdb->delete($table, ['id' => $delete_id]);

    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Stock Out record deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Something went wrong while deleting the Stock Out record.</p></div>';
    }
}

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

?>

<h2>Stock In</h2>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
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
                    <td><?php echo esc_html($stock_in->type); ?></td>
                    <td><?php echo esc_html($stock_in->in_mikos); ?></td>
                    <td><?php echo esc_html($stock_in->in_platos); ?></td>
                    <td><?php echo esc_html($stock_in->date); ?></td>
                    <td><?php echo esc_html($stock_in->notes); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete_stock_in_id" value="<?php echo esc_attr($stock_in->id); ?>">
                            <button type="submit" class="button delete-stock-in" onclick="return confirm('Are you sure you want to delete this Stock In record?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No Stock In records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h2>Stock Out</h2>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
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
                    <td><?php echo esc_html($stock_out->type); ?></td>
                    <td><?php echo esc_html($stock_out->out_mikos); ?></td>
                    <td><?php echo esc_html($stock_out->out_platos); ?></td>
                    <td><?php echo esc_html($stock_out->date); ?></td>
                    <td><?php echo esc_html($stock_out->notes); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete_stock_out_id" value="<?php echo esc_attr($stock_out->id); ?>">
                            <button type="submit" class="button delete-stock-out" onclick="return confirm('Are you sure you want to delete this Stock Out record?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No Stock Out records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

