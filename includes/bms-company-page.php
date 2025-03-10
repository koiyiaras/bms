<?php
/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $table = $wpdb->prefix . 'bms_company';
    $data = [
        'company_name' => sanitize_text_field($_POST['company_name']),
        'registration' => sanitize_text_field($_POST['registration']),
        'vat_number' => sanitize_text_field($_POST['vat_number']),
        'address' => wp_kses_post($_POST['address']), // Preserve HTML tags
        'phone1' => sanitize_text_field($_POST['phone1']),
        'phone2' => sanitize_text_field($_POST['phone2']),
        'fax' => sanitize_text_field($_POST['fax']),
        'email' => sanitize_email($_POST['email']),
        'vat_prefered' => intval($_POST['vat_prefered']),
        'bank_details' => wp_kses_post($_POST['bank_details']), // New field
        'thanks_msg' => sanitize_text_field($_POST['thanks_msg']), // New field
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
          <?php
          wp_editor($details->address, 'address', [
              'textarea_name' => 'address',
              'media_buttons' => false, // Disable media buttons
              'tinymce' => true, // Enable TinyMCE
              'quicktags' => true, // Enable quicktags
              'wpautop' => false, // allow <p> and <br> tags
          ]);
          ?>
      </div>

      <div class="form-group">
          <label for="bank-details">Bank Details</label>
          <?php
          wp_editor($details->bank_details, 'bank_details', [
              'textarea_name' => 'bank_details',
              'media_buttons' => false, // Disable media buttons
              'tinymce' => true, // Enable TinyMCE
              'quicktags' => true, // Enable quicktags
              'wpautop' => false, // allow <p> and <br> tags
          ]);
          ?>
      </div>

      <div class="form-group">
          <label for="thanks-msg">Thanks Message</label>
          <input type="text" name="thanks_msg" id="thanks-msg" value="<?php echo esc_attr($details->thanks_msg); ?>">
      </div>

      <div class="form-group">
          <input type="submit" name="submit" value="Save">
      </div>
  </form>
</div>