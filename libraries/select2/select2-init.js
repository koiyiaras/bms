jQuery(document).ready(function($) {
    // Ensure the select element is available before initializing
    if ($('#client_select').length > 0) {
        // Initialize Select2
        $('#client_select').select2({
            placeholder: 'Search', // Set the placeholder
            allowClear: true,
            width: '100%', // Adjust as needed
        });
    }
});
