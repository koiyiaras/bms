<?php
/** Add client block shared with quotes and invoices */
// Handle form submission to add or update a client
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bms_add_client'])) {
    $name = sanitize_text_field($_POST['clname']);
    $phone = sanitize_text_field($_POST['clphone']);
    $email = sanitize_text_field($_POST['clemail']);
    $address = sanitize_textarea_field($_POST['claddress']);
    $extra = sanitize_textarea_field($_POST['clextra']);
    $location = $_POST['chk_src'];
    $partner = (isset($_POST['partner'])) ? 1 : 0;

    // Check if we're editing an existing client
    if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
        $client_id = intval($_POST['client_id']);
        $wpdb->update(
            $wpdb->prefix . 'bms_clients',
            ['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address, 'extra' => $extra, 'partner' => $partner],
            ['id' => $client_id]
        );
        echo '<div id="cl-added-success" class="alert alert-success" role="alert">Client updated successfully.</div>';
    } else {
        // Insert a new client
        $wpdb->insert(
            $wpdb->prefix . 'bms_clients',
            ['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address, 'extra' => $extra, 'partner' => $partner]
        );
        echo '<div id="cl-added-success" class="alert alert-success" role="alert">Client added successfully.</div>';
    }
}

?>
<div class="row py-2 bordered-block" id="add-client-block">
    <form id="client-form" method="post" action="">
        <input type="hidden" id="client_id" name="client_id" value="" />
        <div class="form-group row">
            <label for="clname" class="col col-form-label">Client name</label>
            <div class="col-sm-12">
                <input type="text" class="form-control" id="clname" name="clname" required />
            </div>
        </div>
        <div class="form-group row">
            <label for="clphone" class="col col-form-label">Tel</label>
            <div class="col-sm-12">
                <input type="text" class="form-control" id="clphone" name="clphone" minlength="8" required />
            </div>
        </div>
        <div class="form-group row mb-2">
            <label for="clemail" class="col col-form-label">Email</label>
            <div class="col-sm-12">
                <input type="email" class="form-control" id="clemail" name="clemail" required />
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="partnerCheckbox" name="partner" >
                    <label class="form-check-label" for="partnerCheckbox">
                        Partner
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group row">
            <label for="claddress" class="col col-form-label">Address</label>
            <div class="col-sm-12">
                <textarea class="form-control" id="claddress" name="claddress" required></textarea>
            </div>
        </div>
        <div class="form-group row">
            <label for="clextra" class="col col-form-label">Other info</label>
            <div class="col-sm-12">
                <textarea class="form-control" id="clextra" name="clextra"></textarea>
            </div>
        </div>
        <div class="form-group row mt-2">
            <div class="d-grid gap-2 d-md-block">
                <input type="hidden" name="chk_src" value="default" />
                <input type="submit" name="bms_add_client" class="btn btn-primary" value="Add client" />
                <button type="reset" class="btn btn-secondary" onclick="location.reload();">Cancel</button>
            </div>
        </div>
    </form>
</div>

<?php