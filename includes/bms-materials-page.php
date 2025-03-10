<?php
global $wpdb;
// Get all materials from the database

$badge_colors = [
    '#198754', 'brown', '#1A8487', '#1A871D', '#871A4D', 'orange', '#871983', 'gray', '#541987', 'gold',
    'red', 'blue', 'green', 'purple', 'pink', 'cyan', 'magenta', 'lime', 'yellow', 'navy',
    '#FF5733', '#C70039', '#900C3F', '#581845', '#FFC300', '#DAF7A6', '#FFC0CB', '#800080', '#008080', '#0000FF',
    '#A52A2A', '#7FFF00', '#D2691E', '#FF7F50', '#6495ED', '#FFF8DC', '#DC143C', '#00008B', '#008B8B', '#B8860B',
    '#A9A9A9', '#006400', '#BDB76B', '#8B008B', '#556B2F', '#FF8C00', '#9932CC', '#8B0000', '#E9967A', '#8FBC8F'
];

$total_materials = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bms_materials");


// Handle form submission (Insert or Update) for materials
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_material'])) {
    $table = $wpdb->prefix . 'bms_materials';
    
    // Prepare data
    $data = [
        'type' => sanitize_text_field($_POST['mat_type']),
        'mikos' => floatval($_POST['mikos']),
        'platos' => floatval($_POST['platos']),
        'cost' => floatval($_POST['cost']),
        'notes' => sanitize_text_field($_POST['notes']),
        'badge_color' => $badge_colors[$total_materials]
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

// Handle form submission (Insert or Update) for others
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_other'])) {
    $table = $wpdb->prefix . 'bms_materials_other';

    // Prepare data
    $data = [
        'description' => sanitize_text_field($_POST['description']),
        'quantity' => sanitize_text_field($_POST['quantity']),
        'notes' => sanitize_text_field($_POST['notes']),
        'other_type' => sanitize_text_field($_POST['other_type']) // Add other_type to the data array
    ];

    // Check if this is an update request
    if (isset($_POST['other_id']) && $_POST['other_id'] != '') {
        // Update existing other
        $where = ['id' => intval($_POST['other_id'])];
        $result = $wpdb->update($table, $data, $where);
    } else {
        // Insert new other
        $result = $wpdb->insert($table, $data);
    }

    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Other saved successfully.</p></div>';
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

// Handle other deletion
if (isset($_POST['delete_other_id'])) {
    $table = $wpdb->prefix . 'bms_materials_other';
    $delete_id = intval($_POST['delete_other_id']);
    $result = $wpdb->delete($table, ['id' => $delete_id]);

    if ($result !== false) {
        echo '<div class="notice notice-success is-dismissible"><p>Other deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>Something went wrong while deleting the other.</p></div>';
    }
}

//Retrieve results from db
$materials = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bms_materials");
$others = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bms_materials_other WHERE other_type = 'other'");
$tools = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bms_materials_other WHERE other_type = 'tool'");
?>

<!-- Add New Buttons -->
<button id="add-new-material" class="button button-primary" style="margin-bottom: 20px;margin-top: 10px;">Add New Material</button>
<button id="add-new-other" class="button button-primary" style="margin-bottom: 20px;margin-top: 10px;">Add New Other</button>

<!-- Form to Add or Edit Material (initially hidden) -->
<div id="material-form" style="display: none; margin-bottom: 20px;">
    <h3 id="form-title-material">Add New Material</h3>
    <form method="post" action="">
        <input type="hidden" id="material_id" name="material_id" value="">
        <table class="form-table">
            <tr>
                <th><label for="mat_type">Type</label></th>
                <td><input type="text" name="mat_type" id="mat_type" required></td>
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
        <button type="submit" name="submit_material" class="button button-primary">Save Material</button>
    </form>
</div>

<!-- Form to Add or Edit Other (initially hidden) -->
<div id="other-form" style="display: none; margin-bottom: 20px;">
    <h3 id="form-title-other">Add New Other</h3>
    <form method="post" action="">
        <input type="hidden" id="other_id" name="other_id" value="">
        <table class="form-table">
            <tr>
                <th><label for="description">Description</label></th>
                <td><input type="text" name="description" id="description" required></td>
            </tr>
            <tr>
                <th><label for="other_type">Type</label></th>
                <td>
                    <select name="other_type" id="other_type" required>
                        <option value="other">Other material</option>
                        <option value="tool">Tool</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="quantity">Quantity</label></th>
                <td><input type="text" name="quantity" id="quantity"></td>
            </tr>
            <tr>
                <th><label for="notes-other">Notes</label></th>
                <td><input type="text" name="notes" id="notes-other"></td>
            </tr>
        </table>
        <button type="submit" name="submit_other" class="button button-primary">Save Other</button>
    </form>
</div>

<!-- Title for Rolls List -->
<h2>Rolls List</h2>

<!-- List of Materials -->
<div class="bk-responsive">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
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
                        <td data-label="ID"><?php echo esc_html($material->id); ?></td>
                        <td data-label="Type"><?php echo esc_html($material->type); ?></td>
                        <td data-label="Length (Mikos)"><?php echo esc_html($material->mikos); ?></td>
                        <td data-label="Width (Platos)"><?php echo esc_html($material->platos); ?></td>
                        <td data-label="Cost"><?php echo esc_html($material->cost); ?></td>
                        <td data-label="Notes"><?php echo esc_html($material->notes); ?></td>
                        <td data-label="Actions">
                            <button class="button edit-material eq-width" data-id="<?php echo esc_attr($material->id); ?>" 
                                    data-mattype="<?php echo esc_attr($material->type); ?>"
                                    data-mikos="<?php echo esc_attr($material->mikos); ?>"
                                    data-platos="<?php echo esc_attr($material->platos); ?>"
                                    data-cost="<?php echo esc_attr($material->cost); ?>"
                                    data-notes="<?php echo esc_attr($material->notes); ?>">
                                Edit
                            </button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_material_id" value="<?php echo esc_attr($material->id); ?>">
                                <button type="submit" class="button delete-material eq-width" onclick="return confirm('Are you sure you want to delete this material?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No materials found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Title for Others List -->
<h2>Others List</h2>

<!-- List of Others -->
<div class="bk-responsive">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($others): ?>
                <?php foreach ($others as $other): ?>
                    <tr>
                        <td data-label="ID"><?php echo esc_html($other->id); ?></td>
                        <td data-label="Description"><?php echo esc_html($other->description); ?></td>
                        <td data-label="Quantity"><?php echo esc_html($other->quantity); ?></td>
                        <td data-label="Notes"><?php echo esc_html($other->notes); ?></td>
                        <td data-label="Actions">
                            <button class="button edit-other eq-width" data-id="<?php echo esc_attr($other->id); ?>" 
                                    data-description="<?php echo esc_attr($other->description); ?>"
                                    data-quantity="<?php echo esc_attr($other->quantity); ?>"
                                    data-notes="<?php echo esc_attr($other->notes); ?>"
                                    data-othertype="<?php echo esc_attr($other->other_type); ?>">
                                Edit
                            </button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_other_id" value="<?php echo esc_attr($other->id); ?>">
                                <button type="submit" class="button delete-other eq-width" onclick="return confirm('Are you sure you want to delete this other?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No others found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Title for Tools List -->
<h2>Tools List</h2>

<!-- List of Tools -->
<div class="bk-responsive">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tools): ?>
                <?php foreach ($tools as $tool): ?>
                    <tr>
                        <td data-label="ID"><?php echo esc_html($tool->id); ?></td>
                        <td data-label="Description"><?php echo esc_html($tool->description); ?></td>
                        <td data-label="Quantity"><?php echo esc_html($tool->quantity); ?></td>
                        <td data-label="Notes"><?php echo esc_html($tool->notes); ?></td>
                        <td data-label="Actions">
                            <button class="button edit-other eq-width" data-id="<?php echo esc_attr($tool->id); ?>" 
                                    data-description="<?php echo esc_attr($tool->description); ?>"
                                    data-quantity="<?php echo esc_attr($tool->quantity); ?>"
                                    data-notes="<?php echo esc_attr($tool->notes); ?>"
                                    data-othertype="<?php echo esc_attr($tool->other_type); ?>">
                                Edit
                            </button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="delete_other_id" value="<?php echo esc_attr($tool->id); ?>">
                                <button type="submit" class="button delete-other eq-width" onclick="return confirm('Are you sure you want to delete this tool?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No tools found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Toggle form visibility when "Add New Material" button is clicked
    document.getElementById('add-new-material').addEventListener('click', function() {
        document.getElementById('material-form').style.display = 'block';
        document.getElementById('form-title-material').innerText = 'Add New Material';
        document.getElementById('material_id').value = '';
        document.getElementById('mat_type').value = '';
        document.getElementById('mikos').value = '';
        document.getElementById('platos').value = '';
        document.getElementById('cost').value = '';
        document.getElementById('notes').value = '';
    });

    // Toggle form visibility when "Add New Other" button is clicked
    document.getElementById('add-new-other').addEventListener('click', function() {
        document.getElementById('other-form').style.display = 'block';
        document.getElementById('form-title-other').innerText = 'Add New Other';
        document.getElementById('other_id').value = '';
        document.getElementById('description').value = '';
        document.getElementById('quantity').value = '';
        document.getElementById('notes-other').value = '';
        document.getElementById('other_type').value = 'other';
    });

    // Handle "Edit" button click for materials
    document.querySelectorAll('.edit-material').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('material-form').style.display = 'block';
            document.getElementById('form-title-material').innerText = 'Edit Material';
            document.getElementById('material_id').value = button.getAttribute('data-id');
            document.getElementById('mat_type').value = button.getAttribute('data-mattype');
            document.getElementById('mikos').value = button.getAttribute('data-mikos');
            document.getElementById('platos').value = button.getAttribute('data-platos');
            document.getElementById('cost').value = button.getAttribute('data-cost');
            document.getElementById('notes').value = button.getAttribute('data-notes');
            window.scrollTo(0, 0);
        });
    });

    // Handle "Edit" button click for others
    document.querySelectorAll('.edit-other').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('other-form').style.display = 'block';
            document.getElementById('form-title-other').innerText = 'Edit Other';
            document.getElementById('other_id').value = button.getAttribute('data-id');
            document.getElementById('description').value = button.getAttribute('data-description');
            document.getElementById('other_type').value = button.getAttribute('data-othertype');
            document.getElementById('quantity').value = button.getAttribute('data-quantity');
            document.getElementById('notes-other').value = button.getAttribute('data-notes');
            window.scrollTo(0, 0);
        });
    });
</script>