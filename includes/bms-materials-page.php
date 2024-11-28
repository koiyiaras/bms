<?php
global $wpdb;
wp_get_current_user();
$userId = get_current_user_id(); // Correct way to get the current user's ID

// Handle form submission (Insert or Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $table = $wpdb->prefix . 'bms_materials';
    
    // Prepare data
    $data = [
        'type' => intval($_POST['mat_type']),
        'description' => sanitize_text_field($_POST['description']),
        'mikos' => floatval($_POST['mikos']),
        'platos' => floatval($_POST['platos']),
        'cost' => floatval($_POST['cost']),
        'notes' => sanitize_text_field($_POST['notes'])
    ];
    
    // Check if this is an update request
    if (isset($_POST['material_id']) && $_POST['material_id'] != '') {
        // Update existing material
        $where = ['id' => intval($_POST['material_id'])];
        $result = $wpdb->update($table, $data, $where);
    } else {
        // Insert new material
        $result = $wpdb->insert($table, $data);
    }
    
    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Material saved successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Something went wrong. Please try again.</p></div>';
    }
}

// Handle material deletion
if (isset($_POST['delete_material_id'])) {
    $table = $wpdb->prefix . 'bms_materials';
    $delete_id = intval($_POST['delete_material_id']);
    $result = $wpdb->delete($table, ['id' => $delete_id]);

    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Material deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Something went wrong while deleting the material.</p></div>';
    }
}

// Get all materials from the database
$materials = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bms_materials");
?>

<!-- Add New Button -->
<button id="add-new-material" class="button button-primary" style="margin-bottom: 20px;margin-top: 10px;">Add New Material</button>

<!-- Form to Add or Edit Material (initially hidden) -->
<div id="material-form" style="display: none; margin-bottom: 20px;">
    <h3 id="form-title">Add New Material</h3>
    <form method="post" action="">
        <input type="hidden" id="material_id" name="material_id" value="">
        <table class="form-table">
            <tr>
                <th><label for="mat_type">Type</label></th>
                <td><input type="text" name="mat_type" id="mat_type" required></td>
            </tr>
            <tr>
                <th><label for="description">Description</label></th>
                <td><input type="text" name="description" id="description" required></td>
            </tr>
            <tr>
                <th><label for="mikos">Length (Mikos)</label></th>
                <td><input type="number" step="0.01" name="mikos" id="mikos" required></td>
            </tr>
            <tr>
                <th><label for="platos">Width (Platos)</label></th>
                <td><input type="number" step="0.01" name="platos" id="platos" required></td>
            </tr>
            <tr>
                <th><label for="cost">Cost</label></th>
                <td><input type="number" step="0.01" name="cost" id="cost"></td>
            </tr>
            <tr>
                <th><label for="notes">Notes</label></th>
                <td><input type="text" name="notes" id="notes"></td>
            </tr>
        </table>
        <button type="submit" name="submit" class="button button-primary">Save Material</button>
    </form>
</div>

<!-- List of Materials -->
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Type</th>
            <th>Description</th>
            <th>Length (Mikos)</th>
            <th>Width (Platos)</th>
            <th>Cost</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($materials): ?>
            <?php foreach ($materials as $material): ?>
                <tr>
                    <td><?php echo esc_html($material->id); ?></td>
                    <td><?php echo esc_html($material->type); ?></td>
                    <td><?php echo esc_html($material->description); ?></td>
                    <td><?php echo esc_html($material->mikos); ?></td>
                    <td><?php echo esc_html($material->platos); ?></td>
                    <td><?php echo esc_html($material->cost); ?></td>
                    <td><?php echo esc_html($material->notes); ?></td>
                    <td>
                        <button class="button edit-material" data-id="<?php echo esc_attr($material->id); ?>" 
                                data-mattype="<?php echo esc_attr($material->type); ?>"
                                data-description="<?php echo esc_attr($material->description); ?>"
                                data-mikos="<?php echo esc_attr($material->mikos); ?>"
                                data-platos="<?php echo esc_attr($material->platos); ?>"
                                data-cost="<?php echo esc_attr($material->cost); ?>"
                                data-notes="<?php echo esc_attr($material->notes); ?>">
                            Edit
                        </button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete_material_id" value="<?php echo esc_attr($material->id); ?>">
                            <button type="submit" class="button delete-material" onclick="return confirm('Are you sure you want to delete this material?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">No materials found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
    // Toggle form visibility when "Add New Material" button is clicked
    document.getElementById('add-new-material').addEventListener('click', function() {
        document.getElementById('material-form').style.display = 'block';
        document.getElementById('form-title').innerText = 'Add New Material';
        document.getElementById('material_id').value = '';
        document.getElementById('mat_type').value = '';
        document.getElementById('description').value = '';
        document.getElementById('mikos').value = '';
        document.getElementById('platos').value = '';
        document.getElementById('cost').value = '';
        document.getElementById('notes').value = '';
    });

    // Handle "Edit" button click
    document.querySelectorAll('.edit-material').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('material-form').style.display = 'block';
            document.getElementById('form-title').innerText = 'Edit Material';
            document.getElementById('material_id').value = button.getAttribute('data-id');
            document.getElementById('mat_type').value = button.getAttribute('data-mattype');
            document.getElementById('description').value = button.getAttribute('data-description');
            document.getElementById('mikos').value = button.getAttribute('data-mikos');
            document.getElementById('platos').value = button.getAttribute('data-platos');
            document.getElementById('cost').value = button.getAttribute('data-cost');
            document.getElementById('notes').value = button.getAttribute('data-notes');
        });
    });
</script>
