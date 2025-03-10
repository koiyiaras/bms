<?php
// Handle delete client securely with POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client_id'])) {
    $client_id = intval($_POST['delete_client_id']);

    // Check for related quotes and invoices
    $related_quotes = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bms_quotes WHERE client_id = %d", $client_id));
    $related_invoices = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bms_invoices WHERE client_id = %d", $client_id));
    $related_projects = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}bms_projects WHERE client_id = %d", $client_id));
    
    if ($related_quotes > 0 || $related_invoices > 0 || $related_projects > 0) {
        echo '<div id="cl-delete-error" class="alert alert-danger" role="alert">This client has connected quotes/invoices and cannot be deleted. Delete or modify all connected invoices/quotes/projects first.</div>';
    } else {
        // Delete the client
        $deleted = $wpdb->delete($wpdb->prefix . 'bms_clients', ['id' => $client_id]);
        if ($deleted) {
            echo '<div id="cl-deleted-success" class="alert alert-success" role="alert">Client deleted successfully.</div>';
        } else {
            echo '<div id="cl-delete-error" class="alert alert-danger" role="alert">Error occurred while deleting the client. Please try again.</div>';
        }
    }
}
?>
<div class="bms-container">
    <div class="row mt-2">
        <div class="d-grid gap-2 d-md-block text-end"><button id="add-new-cl-btn" class="btn btn-sm btn-success mb-2">Add new client</button></div>
    </div>
    <?php
    include plugin_dir_path(__FILE__) . 'add-client.php';

    // Fetch the client list - place this after add-client.php so it populate list after delete-modifiying etc
    $clients_table = $wpdb->prefix . 'bms_clients';
    $clients = $wpdb->get_results("SELECT * FROM $clients_table ORDER BY id DESC");
    ?>

    <div id="list-clients">
        <div class="row my-3">
            <div class="col-md-6 d-flex align-items-center p-md-0">
                <input type="text" id="client-search" class="form-control" placeholder="Αναζήτηση..." style="font-size:0.9em;">
            </div>
            <div class="col-md-6 text-end">
                <div class="d-grid gap-2 d-md-block">
                    <span class="text-center">View:</span>
                    <button id="sort-name" class="btn btn-sm btn-dark">A-Z</button>
                    <button id="sort-id" class="btn btn-sm btn-secondary">Newer first</button>
                </div>
            </div>
        </div>
        <div class="row cl-row-head">
            <div class="col-md-3 d-none d-md-block">Name</div>
            <div class="col-md-1 d-none d-md-block">Partner</div>
            <div class="col-md-2 d-none d-md-block">Phone</div>
            <div class="col-md-3 d-none d-md-block">Address</div>
            <div class="col-md-3 d-none d-md-block">Actions</div>
        </div>
        <div id="data-list">
            <?php foreach ($clients as $client) : ?>
                <div class="row cl-row mb-1" id="clients-grid">
                    <div class="col-md-3 align-content-center p-2 client-item">
                        <span class="pelatis"><?php echo esc_html($client->name); ?></span>
                        <span class="smaller-gray"><a href="mailto:<?php echo esc_html($client->email); ?>"><?php echo esc_html($client->email); ?></a></span>
                    </div>
                    <div class="col-md-1 align-content-center text-center p-2 client-item">
                        <?php if ($client->partner == '1') { ?>
                            <span class="dashicons dashicons-saved" style="color:green;"></span>
                        <?php } else { ?>
                            <span class="dashicons dashicons-ellipsis"></span>
                        <?php } ?>
                    </div>
                    <div class="col-md-2 align-content-center p-2 client-item">
                        <a href="tel:<?php echo esc_html($client->phone); ?>"><?php echo esc_html($client->phone); ?></a>
                    </div>
                    <div class="col-md-3 align-content-center p-2 client-item"><?php echo esc_html($client->address); ?></div>
                    <div class="col-md-3 align-content-center p-2 client-item">
                        <a href="#" class="edit-client" data-id="<?php echo esc_attr($client->id); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="delete_client_id" value="<?php echo esc_attr($client->id); ?>">
                            <a href="#" class="delete-client" data-client-id="<?php echo esc_attr($client->id); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div><!--end of bms-container -->  
<?php