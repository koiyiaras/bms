<?php
// Ensure direct file access is prevented
if (!defined('ABSPATH')) {
    exit;
}
$table_name = $wpdb->prefix . 'bms_tasks';
$table_clients = $wpdb->prefix . 'bms_clients';
$table_projects = $wpdb->prefix . 'bms_projects';
$table_materials = $wpdb->prefix . 'bms_materials';
$table_others = $wpdb->prefix . 'bms_materials_other';

$selected_date = isset($_GET['task_date']) ? sanitize_text_field($_GET['task_date']) : date('Y-m-d');

$clients = $wpdb->get_results("SELECT * FROM $table_clients ORDER BY id DESC");
$projects = $wpdb->get_results("SELECT * FROM $table_projects ORDER BY id DESC");
$materials = $wpdb->get_results("SELECT * FROM $table_materials");
$others = $wpdb->get_results("SELECT * FROM $table_others WHERE other_type = 'other' ORDER BY description");
$tools = $wpdb->get_results("SELECT * FROM $table_others WHERE other_type = 'tool' ORDER BY description");

//====================== Delete photos and update photos field before updating the rest ==========
if (isset($_POST['deleted_photos'])) {
    $deleted_photos = json_decode(stripslashes($_POST['deleted_photos']), true);
    
    if (!empty($deleted_photos)) {
        // Fetch the current photos from the database
        $task_id = $_POST['task_id']; // Assuming you have a task_id to identify the record
        $table_name = $wpdb->prefix . 'bms_tasks';
        $current_photos = $wpdb->get_var($wpdb->prepare("SELECT photos FROM $table_name WHERE id = %d", $task_id));

        // Decode the current photos JSON array
        $current_photos_array = json_decode($current_photos, true);
        $updated_photos_array = array_values(array_diff($current_photos_array, $deleted_photos));
        $updated_photos_json = json_encode($updated_photos_array, JSON_UNESCAPED_UNICODE);

        // Update the database with the new photos JSON
        $wpdb->update(
            $table_name,
            ['photos' => $updated_photos_json], // New photos data
            ['id' => $task_id] // Where clause
        );
        
        // Delete the actual photo files from the server
        foreach ($deleted_photos as $photo_url) {
            // Convert URL to local file path
            $photo_path = str_replace(site_url(), ABSPATH, $photo_url);

            // Delete file from server
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
    }
}

//=================================SAVE / UPDATE TASK =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $action_type = $_POST['action_type']; // Get action type from hidden input
    $task_date = $selected_date; //you can skip this line and replace task_date to selected date, or rename $selected_date above
    $description = wp_kses_post($_POST['description']);
    $client_s = $_POST['client_s'];
    $map_location = $_POST['map_location'];
    
    // Do the savings of clients/projects if necessary and get ids
    $result = save_client_project($client_s);
    $client_id = $result['client_id'];
    $project_id = $result['project_id'];

    // Handling materials selection
    $selected_materials = [];
    if (!empty($_POST['mat_type'])) {
        foreach ($_POST['mat_type'] as $key => $data) {
            // Skip if mat_type value is 0
            if ($data == 0) {
                continue;
            }

            $selected_materials[] = [
                'name' => sanitize_text_field($data),
                'dimensions' => sanitize_text_field($_POST['mat_dimensions'][$key]),
                'quantity' => $_POST['mat_quantity'][$key]
            ];
        }
    }
    //print_r($selected_materials);
    $checked_materials = json_encode($selected_materials, JSON_UNESCAPED_UNICODE);

    // Handling others selection
    $selected_others = [];
    if (!empty($_POST['others'])) {
        foreach ($_POST['others'] as $other_id => $data) {
            // Skip if the item is not checked
            if (!isset($data['checked']) || $data['checked'] != 1) {
                continue;
            }
    
            // Skip if quantity is empty
            if (empty($data['quantity'])) {
                continue;
            }
    
            // Add only checked items with valid quantity
            $selected_others[] = [
                'id' => intval($other_id),
                'quantity' => sanitize_text_field($data['quantity']),
                'notes' => sanitize_text_field($data['notes'] ?? '') // Handle optional notes
            ];
        }
    }
    $checked_others = json_encode($selected_others);
    

    // Handling tools selection
    $selected_tools = [];
    if (!empty($_POST['tools'])) {
        foreach ($_POST['tools'] as $tool_id => $data) {
            // Skip if the item is not checked
            if (!isset($data['checked']) || $data['checked'] != 1) {
                continue;
            }

            // Skip if quantity is empty
            if (empty($data['quantity'])) {
                continue;
            }

            // Add only checked items with valid quantity
            $selected_tools[] = [
                'id' => intval($tool_id),
                'quantity' => sanitize_text_field($data['quantity']),
                'notes' => sanitize_text_field($data['notes'] ?? '') // Handle optional notes
            ];
        }
    }
    $checked_tools = json_encode($selected_tools);

    // Handling task completion fields
    $completion_status = 'PENDING';

    // Handling return others
    $returned_others = [];
    if (!empty($_POST['return_others'])) {
        foreach ($_POST['return_others'] as $other_id => $data) {
            $returned_others[] = [
                'id' => intval($other_id),
                'quantity' => sanitize_text_field($data['quantity']),
                'notes' => sanitize_text_field($data['notes'])
            ];
        }
    }
    $return_others = json_encode($returned_others);

    // Handling image uploads
    $photo_paths = [];
    if (!empty($_FILES['photos'])) {
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] === 0) {
                $upload_dir = wp_upload_dir();
                $file_name = uniqid() . '-' . basename($_FILES['photos']['name'][$key]);
                $file_path = $upload_dir['path'] . '/' . $file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    $photo_paths[] = $upload_dir['url'] . '/' . $file_name;
                }
            }
        }
    }
    $photos = json_encode($photo_paths);

    if ($action_type === 'update_task') {
        $task_id = intval($_POST['task_id']);
        // Get existing photos from the database
        $existing_photos = $wpdb->get_var($wpdb->prepare(
            "SELECT photos FROM {$wpdb->prefix}bms_tasks WHERE id = %d",
            $task_id
        ));
        // Decode existing photos (handle case where it's empty or null)
        $existing_photos_array = !empty($existing_photos) ? json_decode($existing_photos, true) : [];
        // Decode new uploaded photos
        $new_photos_array = json_decode($photos, true);
        // Merge both arrays
        $merged_photos = array_merge($existing_photos_array, $new_photos_array);
        // Remove duplicates (just in case)
        $merged_photos = array_unique($merged_photos);
        // Encode back to JSON
        $photos = json_encode(array_values($merged_photos));
    }
    

    $sort_order = intval($_POST['sort_order']);
    // Check if tasks already exist for the given date
    $count_tasks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE task_date = %s", $task_date));

    // If updating a task, fetch the existing sort_order
    if ($action_type === 'update_task') {
        $task_id = intval($_POST['task_id']);
        $existing_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $task_id));
        $existing_sort_order = $existing_task->sort_order;
        $completion_status = $existing_task->completion_status;

        // If the sort_order has changed, reorder tasks
        if ($sort_order != $existing_sort_order) {
            if ($sort_order < $existing_sort_order) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_name SET sort_order = sort_order + 1 WHERE task_date = %s AND sort_order >= %d AND sort_order < %d",
                    $task_date,
                    $sort_order,
                    $existing_sort_order
                ));
            } else {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table_name SET sort_order = sort_order - 1 WHERE task_date = %s AND sort_order > %d AND sort_order <= %d",
                    $task_date,
                    $existing_sort_order,
                    $sort_order
                ));
            }
        }
    } else {
        // For new tasks, increment sort_order of existing tasks if necessary
        if ($sort_order <= $count_tasks) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET sort_order = sort_order + 1 WHERE task_date = %s AND sort_order >= %d",
                $task_date,
                $sort_order
            ));
        }
    }

    // Prepare task data
    $task_data = [
        'task_date' => $task_date,
        'description' => $description,
        'photos' => $photos,
        'checked_materials' => $checked_materials,
        'checked_others' => $checked_others,
        'checked_tools' => $checked_tools,
        'completion_status' => $completion_status,
        'map_location' => $map_location,
        'sort_order' => $sort_order,
        'client_id' => $client_id,
        'project_id' => $project_id
    ];

    // Insert or update the task
    if ($action_type === 'update_task') {
        $wpdb->update($table_name, $task_data, ['id' => $task_id]);
        echo '<div class="alert alert-success">Task updated successfully!</div>';
    } else {
        $wpdb->insert($table_name, $task_data);
        echo '<div class="alert alert-success">Task saved successfully!</div>';
    }
}


// Add this PHP code to handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['worker_updated_btn'])) {
    $task_id = intval($_POST['task_id']);
    $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : NULL;
    $end_time = isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : NULL;
    $completion_status = isset($_POST['completion_status']) && $_POST['completion_status'] === 'on' ? 'COMPLETED' : 'NOT COMPLETED';
    // Handle return materials
    $return_materials = [];
    if (!empty($_POST['return_materials'])) {
        foreach ($_POST['return_materials'] as $key => $data) {
            $return_materials[] = [
                'name' => sanitize_text_field($data['name']),
                'dimensions' => sanitize_text_field($data['dimensions']),
                'quantity' => sanitize_text_field($data['quantity']),
                'notes' => sanitize_text_field($data['notes'])
            ];
        }
    }
    
    $return_materials_json = !empty($return_materials) ? json_encode($return_materials) : '[]';

    // Handle return others
    $return_others = [];
    if (!empty($_POST['return_others'])) {
        foreach ($_POST['return_others'] as $key => $data) {
            $return_others[] = [
                'id' => intval($key),
                'quantity' => sanitize_text_field($data['quantity']),
                'notes' => sanitize_text_field($data['notes'])
            ];
        }
    }

    $return_others_json = !empty($return_others) ? json_encode($return_others) : '[]';

    // Update the task in the database
    $wpdb->update(
        $table_name,
        [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'completion_status' => $completion_status,
            'return_materials' => $return_materials_json,
            'return_others' => $return_others_json,
            'worker_updated' => 1
        ],
        ['id' => $task_id],
        ['%s', '%s', '%s', '%s', '%s', '%d'],
        ['%d']
    );

    echo '<div class="alert alert-success">Task updated successfully!</div>';
}

// Handle Task Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_id'])) {
    // Get task_id from POST request and sanitize it
    $task_id = intval($_POST['del_id']);

    // Table name
    $table_name = $wpdb->prefix . 'bms_tasks';

    // Delete query
    $delete_result = $wpdb->delete(
        $table_name,
        array('id' => $task_id),
        array('%d')
    );

    // Check if the deletion was successful
    if ($delete_result !== false) {
        echo '<div class="alert alert-success">Record deleted successfully</div>';
    } else {
        echo '<div class="alert alert-danger">Error deleting record: ' . $wpdb->last_error . '</div>';
    }
}

//============================= END SAVE TASK ============================================
?>

<style>
    .view-task-cont:nth-child(even) {
        background-color: #fffff5;
    }

    .view-task-cont p {
        font-size: 1.2em;
    }
    .styled-label {
        font-size: 1.3rem;
        font-weight: bold;
        text-decoration-line: underline;
        text-decoration-style: dashed;
        text-decoration-color: darkorange;
        margin-top:0.8em;
    }
    .styled-label2 {
        font-size: 1.2rem;
        border-bottom: solid 1px #dedede;
        margin-top:0.8em;
        font-weight: bold;
    }
    h2 {
        text-decoration-line: underline;
        text-decoration-style: dashed;
        text-decoration-color: darkorange;
    }
    .bg-colored {
        background:FloralWhite;
    }

    .btn-add-row, .btn-remove-row {
        font-size:.8em;
    }
    .check-label {
        font-size:1.4em;
    }
    .worker-result {
        border: .15em solid darkgray;
        padding: 2px;
        background: white;
    }
</style>

<?php

//this query much be after db operations
$tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE task_date = %s ORDER BY sort_order ASC", $selected_date));

if (is_user_logged_in()) {
    ?>
    <div class="bg-colored">
        <div class="m-3 pb-3">
            <label for="task_date" class="form-label styled-label">Date</label>
            <input type="date" id="task_date" name="task_date" class="form-control" value="<?php echo esc_attr($selected_date); ?>">
        </div>
    </div>
    <?php
    }//date selection for stuff and admin

if (current_user_can('administrator')) { //create task - admin access
    ?>
    <div class="text-end">
        <button id="createTaskBtn" class="btn btn-primary mb-3">Create Task</button>
    </div>
    <div id="create-task-box" class="bms-container bordered-block p-3 d-none">
        <form id="taskForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="task_id" id="task_id" value="">
            <div class="mb-3 row">
                <div class="col-md-10">
                    <?php client_project_select($clients, $projects, 'styled-label'); ?>
                </div>
                <div class="col-md-2">
                    <label for="sort_order" class="form-label styled-label">Order</label>
                    <select name="sort_order" id="sort_order" class="form-select">
                        <?php
                        $count_tasks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE task_date = %s", $selected_date));
                        for ($i = 1; $i <= $count_tasks + 1; $i++) {
                            echo "<option value='$i'" . ($i == $count_tasks + 1 ? ' selected' : '') . ">$i</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label styled-label">Task Description</label>
                <?php
                $content = '';
                $editor_id = 'description';
                $settings = [
                    'textarea_name' => 'description',
                    'textarea_rows' => 8,
                    'media_buttons' => false, // Show media buttons
                    'tinymce' => [
                        'content_css' => get_template_directory_uri() . '/css/editor-style.css'
                    ]
                ];
                wp_editor($content, $editor_id, $settings);
                ?>
            </div>

            <div class="mb-3">
                <label class="form-label styled-label">Select Materials</label>
                <div>
                    <div id="materials-line" class="row my-2 align-items-center">
                        <div class="col-md-3">
                            <select name="mat_type[]" class="form-select mat-type-checker-task">
                                <option value="0" selected>SELECT TYPE</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value='<?php echo esc_attr($material->type); ?>'><?php echo esc_html($material->type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="mat_dimensions[]" class="form-control" placeholder="Dimensions">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="mat_quantity[]" class="form-control" placeholder="Quantity">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-light btn-remove-row">X</button>
                            <button type="button" class="btn btn-light btn-add-row">+</button>
                        </div>
                    </div>
                    <?php foreach ($others as $other): ?>
                        <div class="row my-2 align-items-center">
                            <div class="col-md-3">
                                <div class="form-check form-check-inline">
                                    <!-- Hidden input to ensure unchecked items are not processed -->
                                    <input type="hidden" name="others[<?php echo esc_attr($other->id); ?>][checked]" value="0">
                                    <!-- Checkbox input -->
                                    <input class="form-check-input material-checkbox" type="checkbox" 
                                        name="others[<?php echo esc_attr($other->id); ?>][checked]" 
                                        id="other_<?php echo esc_attr($other->id); ?>" value="1">
                                    <label class="form-check-label check-label" for="other_<?php echo esc_attr($other->id); ?>">
                                        <?php echo esc_html($other->description); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 quantity-input-container d-none">
                                <input type="text" class="form-control quantity-input" name="others[<?php echo esc_attr($other->id); ?>][quantity]" placeholder="Quantity">
                            </div>
                            <div class="col-md-3 out-note-container d-none">
                                <input type="text" class="form-control" name="others[<?php echo esc_attr($other->id); ?>][notes]" placeholder="Out notes">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label styled-label">Select Tools</label>
                <div>
                <?php foreach ($tools as $tool): ?>
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="form-check form-check-inline">
                                <!-- Hidden input to ensure unchecked items are not processed -->
                                <input type="hidden" name="tools[<?php echo esc_attr($tool->id); ?>][checked]" value="0">
                                <!-- Checkbox input -->
                                <input class="form-check-input tool-checkbox" type="checkbox" 
                                    name="tools[<?php echo esc_attr($tool->id); ?>][checked]" 
                                    id="tool_<?php echo esc_attr($tool->id); ?>" value="1">
                                <label class="form-check-label check-label" for="tool_<?php echo esc_attr($tool->id); ?>">
                                    <?php echo esc_html($tool->description); ?>
                                </label>
                            </div>
                        </div>
                        <div class="col-auto quantity-input-container d-none">
                            <input type="text" class="form-control quantity-input" name="tools[<?php echo esc_attr($tool->id); ?>][quantity]" placeholder="Quantity">
                        </div>
                        <div class="col-auto out-note-container d-none">
                            <input type="text" class="form-control" name="tools[<?php echo esc_attr($tool->id); ?>][notes]" placeholder="Out-note">
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="map-location" class="form-label styled-label">Add Location</label>
                <input type="text" id="map-location" name="map_location" class="form-control" placeholder="Enter the link to the map">
            </div>
            <div class="mb-3">
                <label for="photos" class="form-label styled-label">Upload Photos</label>
                <input type="file" id="photos" name="photos[]" class="form-control" accept="image/png, image/jpeg" multiple>
            </div>
            <div id="photo-preview" class="mb-3">
                
            </div>
            <!-- Hidden input for deleted photos -->
            <input type="hidden" id="deleted-photos" name="deleted_photos">
            <input type="hidden" name="action_type" id="action_type" value="save_task">
            <button type="submit" class="btn btn-success save-update-task ms-0 ms-md-2">Save Task</button>
            <button type="button" id="reset-form" class="btn btn-secondary">Reset</button>
        </form>
    </div>
    <script>
 
    </script>
    <?php
} 

if (is_user_logged_in()) {
    ?>
    <div class="bms-container bordered-block p-3 mt-4">
        <h2>Day Tasks - <?php echo esc_attr(date("d-m-Y", strtotime($selected_date))); ?></h2>
        <?php if (!empty($tasks)): ?>
            <?php foreach ($tasks as $count => $task): 
                $count++;
            ?>
                <div class="mt-4 p-2 view-task-cont position-relative">
                <?php if (current_user_can('administrator')): ?>
                    <form id="deleteForm_<?php echo esc_attr($task->id); ?>" method="post" style="display:inline;">
                        <input type="hidden" name="del_id" value="<?php echo esc_attr($task->id); ?>">
                        <!-- Dashicon for Delete -->
                        <span class="dashicons dashicons-trash" 
                            style="position: absolute; top: 10px; right: 10px; cursor: pointer;"
                            onclick="confirmDelete(<?php echo esc_attr($task->id); ?>)">
                        </span>
                    </form>

                    <script>
                    function confirmDelete(taskId) {
                        if (confirm('Are you sure you want to delete this item?')) {
                            document.getElementById('deleteForm_' + taskId).submit();
                        }
                    }
                    </script>


                    <!-- Dashicon for Edit -->
                    <span class="dashicons dashicons-edit edit-task-btn" 
                        style="position: absolute; top: 10px; right: 40px; cursor: pointer;" 
                        data-task-id="<?php echo esc_attr($task->id); ?>"
                        data-description="<?php echo esc_attr($task->description); ?>"
                        data-client-id="<?php echo esc_attr($task->client_id); ?>"
                        data-project-id="<?php echo esc_attr($task->project_id); ?>"
                        data-sort-order="<?php echo esc_attr($task->sort_order); ?>"
                        data-materials='<?php echo htmlspecialchars($task->checked_materials, ENT_QUOTES, 'UTF-8'); ?>'
                        data-others='<?php echo htmlspecialchars($task->checked_others, ENT_QUOTES, 'UTF-8'); ?>'
                        data-tools='<?php echo htmlspecialchars($task->checked_tools, ENT_QUOTES, 'UTF-8'); ?>'
                        data-map-location="<?php echo esc_attr($task->map_location); ?>"
                        data-photos='<?php echo htmlspecialchars($task->photos, ENT_QUOTES, 'UTF-8'); ?>'>
                    </span>
                <?php endif; ?>
                    <h5><span class="badge bg-dark">Task <?php echo $count; ?></span></h5>
                    <div class="styled-label2">Task Description</div>
                    <div class="m-3">
                        <?php echo wpautop(wp_kses_post($task->description)); ?>
                    </div>
                    <div class="styled-label2">Client</div>
                    <div class="m-3">
                        <?php
                        if (!empty($task->client_id)) { // Check if client_id is not null and not zero
                            $client = $wpdb->get_row(
                                $wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $task->client_id)
                            );

                            if ($client) { // Check if the client exists in the database
                                echo "<p>" . esc_html($client->name) . "</p>";
                                echo "<p>" . esc_html($client->address) . "</p>";
                                echo "<p>" . esc_html($client->phone) . "</p>";
                            } else {
                                echo "<p>No client found in the database.</p>";
                            }
                        } else {
                            echo "<p>No client set</p>";
                        }
                        ?>
                    </div>
                    <div class="styled-label2">Materials to bring</div>
                    <div class="m-3">
                        <?php
                        $show_materials = !empty($task->checked_materials) ? json_decode($task->checked_materials, true) : [];

                        if (!empty($show_materials)) {
                            foreach ($show_materials as $material) {
                                echo "<p>" . esc_html($material['name']) . " - " . esc_html($material['dimensions']) . " - Qty: " . esc_html($material['quantity']) . "</p>";
                            }
                        } 
                        
                        $show_others = !empty($task->checked_others) ? json_decode($task->checked_others, true) : [];

                        if (!empty($show_others)) {
                            foreach ($show_others as $other) {
                                $description = $wpdb->get_var($wpdb->prepare("SELECT description FROM {$table_others} WHERE id = %d", $other['id']));
                                echo '<p>';
                                echo esc_html($description) . ' - Qty: ' . esc_html($other['quantity']);
                                if (!empty($other['notes'])) {
                                    echo ' - Notes: ' . esc_html($other['notes']);
                                }
                                echo '</p>';
                            }
                        } 
                        ?>
                    </div>
                    <div class="styled-label2">Tools to bring</div>
                    <div class="m-3">
                        <?php
                        $show_tools = !empty($task->checked_tools) ? json_decode($task->checked_tools, true) : [];

                        if (!empty($show_tools)) {
                            foreach ($show_tools as $tool) {
                                $description = $wpdb->get_var($wpdb->prepare("SELECT description FROM {$table_others} WHERE id = %d", $tool['id']));
                                echo '<p>';
                                echo esc_html($description) . ' - Qty: ' . esc_html($tool['quantity']);
                                if (!empty($tool['notes'])) {
                                    echo ' - Notes: ' . esc_html($tool['notes']);
                                }
                                echo '</p>';
                            }
                        } else {
                            echo "<p>No tools set</p>";
                        }
                        ?>
                    </div>
                    <?php
                         if (!empty($task->map_location)) {
                            $google_maps_url = $task->map_location;
                            echo '<div class="styled-label2">Location</div>';
                            echo '<div class="col-md-3">';
                                echo "<a href='$google_maps_url' target='_blank' class='m-2 btn btn-dark btn-sm'>Open location on map</a>";
                            echo '</div>';
                         }
                    ?>
                    <div class="styled-label2">Photos</div>
                    <div class="m-3">
                        <?php
                        $photos = !empty($task->photos) ? json_decode($task->photos, true) : [];

                        if (!empty($photos)) {
                            echo '<div class="row g-2">';
                            foreach ($photos as $photo_url) {
                                echo '<div class="col-md-3">';
                                echo '<a href="' . esc_url($photo_url) . '" target="_blank">';
                                echo '<img src="' . esc_url($photo_url) . '" class="img-fluid rounded shadow-sm" alt="Task Photo">';
                                echo '</a>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<p class="text-muted">No photos available.</p>';
                        }
                        ?>
                    </div>
                    <?php
                    if ($task->worker_updated == 1) { ?>
                    <div class="worker-result">
                        <h5 class="text-center">WORKER DATA INPUT</h5>
                        <div class="styled-label2">Start time - End time</div>
                        <div class="m-3">
                            <?php
                            echo $task->start_time . " - " . $task->end_time;
                            ?>
                        </div>

                        <!--Show returned materials-->
                        <div class="styled-label2">Materials to return</div>
                        <div class="m-3">
                            <?php
                            $show_r_materials = !empty($task->return_materials) ? json_decode($task->return_materials, true) : [];

                            if (!empty($show_r_materials)) {
                                foreach ($show_r_materials as $material) {
                                    echo "<p>" . esc_html($material['name']) . " - " . esc_html($material['dimensions']) . " - Qty: " . esc_html($material['quantity']) . "</p>";
                                }
                            } 
                            
                            $show_r_others = !empty($task->return_others) ? json_decode($task->return_others, true) : [];

                            if (!empty($show_r_others)) {
                                foreach ($show_r_others as $other) {
                                    $description = $wpdb->get_var($wpdb->prepare("SELECT description FROM {$table_others} WHERE id = %d", $other['id']));
                                    echo '<p>';
                                    echo esc_html($description) . ' - Qty: ' . esc_html($other['quantity']);
                                    if (!empty($other['notes'])) {
                                        echo ' - Notes: ' . esc_html($other['notes']);
                                    }
                                    echo '</p>';
                                }
                            } 
                            //if empty
                            if (empty($show_r_materials) && empty($show_r_others)) {
                                echo "<p>Nothing to return</p>";
                            }
                            ?>
                        </div><!--end return materials-->
                        

                    </div><!--END Of Worker Result Div-->
                    <?php
                    }
                    ?>

                    <div class="styled-label2">Task status</div>
                    <div class="m-3">
                    <?php
                        $taskStatus = strtoupper($task->completion_status);
                        $text_class = 'text-secondary'; // Default class

                        if ($taskStatus === 'COMPLETED') {
                            $text_class = 'text-primary';
                        } elseif ($taskStatus === 'PENDING') {
                            $text_class = 'text-warning';
                        } elseif ($taskStatus === 'NOT COMPLETED') {
                            $text_class = 'text-danger';
                        }

                        echo '<span class="' . esc_attr($text_class) . '">' . esc_html($taskStatus) . '</span>';
                    ?>
                    </div>
                    <button class="btn btn-warning mt-3 updateTaskBtn" data-target = "worker_update<? echo $task->id; ?>">Worker update</button>

                    <!-- Worker Update Section -->
                    <div id="worker_update<? echo $task->id; ?>" class="d-none mt-3 p-2 bg-colored">
                        <form method="post">
                            <div class="row">
                                <!-- Start Time -->
                                <div class="col-md-6">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="text" name="start_time" id="start_time" class="form-control" value="<?php echo ($task->worker_updated == 1) ? $task->start_time : ''; ?>" required>
                                </div>
                                <!-- End Time -->
                                <div class="col-md-6">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="text" name="end_time" id="end_time" class="form-control" value="<?php echo ($task->worker_updated == 1) ? $task->end_time : ''; ?>" required>
                                </div>
                            </div>

                            <!-- Return Materials -->
                            <?php
                            $show_materials = json_decode($task->checked_materials, true);
                            $show_others = json_decode($task->return_others, true);

                            if (!empty($show_materials)|| !empty($show_others)) {
                                echo "<h5>Return Materials</h5>";
                            }

                            if (!empty($show_materials)) {
                                foreach ($show_materials as $key => $material) {
                                    echo "<div class='row'>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='text' readonly name='return_materials[{$key}][name]' class='form-control' value='" . esc_html($material['name']) . "' />";
                                    echo "</div>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='text' name='return_materials[{$key}][dimensions]' class='form-control' value='" . esc_html($material['dimensions']) . "' />";
                                    echo "</div>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='text' name='return_materials[{$key}][quantity]' class='form-control' placeholder='Quantity' value='" . esc_html($material['quantity']) . "' required>";
                                    echo "</div>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='text' name='return_materials[{$key}][notes]' class='form-control mt-1' placeholder='Notes' value='" . esc_html($material['notes'] ?? '') . "'>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                            }
                            ?>

                            <!-- Return Others -->
                            <?php
                            if (!empty($show_others)) {
                                foreach ($show_others as $key => $other) {
                                    $description = $wpdb->get_var($wpdb->prepare("SELECT description FROM {$table_others} WHERE id = %d", $other['id']));
                                    echo "<div class='row'>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='hidden' name='return_others[{$key}][id]' class='form-control mt-1' value='" . esc_html($other['id']) . "'>";
                                    echo "<input type='text' readonly name='doesnmatter' class='form-control mt-1' placeholder='Description' value='" . esc_html($description) . "'>";
                                    echo "</div>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='text' name='return_others[{$key}][quantity]' class='form-control' placeholder='Quantity' value='" . esc_html($other['quantity']) . "' required>";
                                    echo "</div>";
                                    echo "<div class='col-md-3'>";
                                    echo "<input type='text' name='return_others[{$key}][notes]' class='form-control mt-1' placeholder='Notes' value='" . esc_html($other['notes'] ?? '') . "'>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                            }
                            ?>

                            <!-- Completion Switch -->
                            <div class="mb-3">
                                <label class="custom-switch">
                                    <input type="checkbox" name="completion_status" id="completion_status">
                                    <span class="slider round"></span>
                                </label>
                                <span id="completion-status-label">Not Completed</span> <!-- Dynamic label -->
                            </div>
                            <!-- Save Button -->
                            <input type="hidden" name="task_id" value="<?php echo $task->id; ?>" />
                            <button type="submit" name="worker_updated_btn" class="btn btn-primary">Save</button>
                        </form>
                    </div>

                        <!--======================================END WORKER=============================================-->
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning mt-4">No tasks found for the selected date.</div>
        <?php endif; ?>
    </div>
    <?php
}