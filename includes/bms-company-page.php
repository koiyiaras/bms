<?php
wp_get_current_user();
$userId = $current_user->ID;

/* what
 * is on the
 * backend
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $table = $wpdb->prefix . 'bms_company';
    $data = [
        'company_name' => sanitize_text_field($_POST['company_name']),
        'registration' => sanitize_text_field($_POST['registration']),
        'vat_number' => sanitize_text_field($_POST['vat_number']),
        'address' => wp_kses_post($_POST['address']),
        'phone1' => sanitize_text_field($_POST['phone1']),
        'phone2' => sanitize_text_field($_POST['phone2']),
        'fax' => sanitize_text_field($_POST['fax']),
        'email' => sanitize_email($_POST['email']),
        'vat_prefered' => intval($_POST['vat_prefered']),
    ];

    $existing_row = $wpdb->get_row("SELECT * FROM $table WHERE id = 1");

    if ($existing_row) {
        $result = $wpdb->update($table, $data, ['id' => 1]);
    } else {
        $result = $wpdb->insert($table, $data);
    }

    if ($result !== false) {
        echo '<div class="success-message">Company Details updated successfully.</div>';
    } else {
        echo '<div class="error-message">Something went wrong.</div>';
    }
}

$details = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'bms_company WHERE id=1');
?>

<div class="company-details-form">
  <h1>Company Details</h1>
  <h2>Configure Details and Parameters</h2>
  <form method="post" action="">
      <div class="form-group">
          <label for="company-name">Company name</label>
          <input type="text" name="company_name" id="company-name" value="<?php echo esc_attr($details->company_name); ?>" required>
      </div>

      <div class="form-group">
          <label for="registration">Registration no</label>
          <input type="text" name="registration" id="registration" value="<?php echo esc_attr($details->registration); ?>" required>
      </div>

      <div class="form-group">
          <label for="vat-number">VAT reg no</label>
          <input type="text" name="vat_number" id="vat-number" value="<?php echo esc_attr($details->vat_number); ?>" required>
      </div>

      <div class="form-group">
          <label for="phone1">Phone 1</label>
          <input type="text" name="phone1" id="phone1" value="<?php echo esc_attr($details->phone1); ?>" required>
      </div>

      <div class="form-group">
          <label for="phone2">Phone 2</label>
          <input type="text" name="phone2" id="phone2" value="<?php echo esc_attr($details->phone2); ?>">
      </div>

      <div class="form-group">
          <label for="fax">Fax</label>
          <input type="text" name="fax" id="fax" value="<?php echo esc_attr($details->fax); ?>">
      </div>

      <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" value="<?php echo esc_attr($details->email); ?>" required>
      </div>

      <div class="form-group">
          <label for="vat-prefered">Default VAT (%)</label>
          <input type="number" name="vat_prefered" id="vat-prefered" value="<?php echo esc_attr($details->vat_prefered); ?>" required>
      </div>

      <div class="form-group">
          <label for="address">Address</label>
          <?php wp_editor($details->address, 'address'); ?>
      </div>

      <div class="form-group">
          <input type="submit" name="submit" value="Save">
      </div>
  </form>
</div>


<style>
.company-details-form {
    max-width: 600px;
    margin: 10px auto;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.company-details-form .form-group {
    margin-bottom: 15px;
}

.company-details-form label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.company-details-form input[type="text"],
.company-details-form input[type="email"],
.company-details-form input[type="number"],
.company-details-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

.company-details-form input[type="submit"] {
    background-color: #0073aa;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.company-details-form input[type="submit"]:hover {
    background-color: #005177;
}

.success-message {
    padding: 10px;
    margin-bottom: 15px;
    color: green;
    text-align: center;
    /* border: 1px solid green;
    background-color: #d4edda;
    border-radius: 5px; */
}

.error-message {
    padding: 10px;
    margin-bottom: 15px;
    color: red; 
    text-align: center; /*
    border: 1px solid red;
    background-color: #f8d7da;
    border-radius: 5px; */
}
</style>