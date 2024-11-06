function download(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

function slm_exportlicense(){
    var license_expt_id = document.getElementById("lic-json-data");
    var filelicname = license_expt_id.getAttribute('data-lickey');
    var license_data = document.getElementById("lic-json-data").textContent;
    var text = license_data;
    var filename = "license-" + filelicname + ".json";
    download(filename, text);
}

jQuery(document).ready(function($) {
    $('.user-search-input').on('input', function() {
        const field = $(this).attr('id');
        const value = $(this).val();
        const suggestionsBox = $(`.user-search-suggestions[data-field="${field}"]`);

        if (value.length > 1) { // Trigger search on 2+ characters
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'slm_user_search',
                    value: value,
                },
                success: function(response) {
                    suggestionsBox.empty(); // Clear previous suggestions

                    if (response.data.length > 0) {
                        response.data.forEach(user => {
                            const suggestion = $('<div>')
                                .addClass('suggestion-item')
                                .text(`${user.first_name} ${user.last_name} (${user.email})`);
                            suggestion.data('user', user);
                            suggestionsBox.append(suggestion);
                        });
                        suggestionsBox.show(); // Show suggestions box if results are found
                    } else {
                        suggestionsBox.hide(); // Hide suggestions box if no results
                    }

                    // Handle suggestion click
                    $('.suggestion-item').on('click', function() {
                        const user = $(this).data('user');
                        $('#user_id').val(user.ID);
                        $('#first_name').val(user.first_name);
                        $('#last_name').val(user.last_name);
                        $('#email').val(user.email);
                        $('#subscr_id').val(user.subscr_id); // Populate the subscr_id field

                        // Populate company_name if available
                        if (user.company_name) {
                            $('#company_name').val(user.company_name);
                        } else {
                            $('#company_name').val(''); // Clear if no company name is available
                        }

                        suggestionsBox.hide(); // Hide suggestions after selection
                    });
                },
            });
        } else {
            suggestionsBox.hide(); // Hide suggestions if input length < 2
        }
    });

    // Hide suggestions if clicking outside of them
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.user-search-input, .user-search-suggestions').length) {
            $('.user-search-suggestions').hide();
        }
    });
});


jQuery(document).ready(function($) {
    let userSelectedDay = null; // Store manually set day of the month

    // Helper to calculate expiry date based on length, interval, and stored day
    function calculateExpiryDate() {
        // Exit if lifetime is selected
        if ($('#lic_type').val() === 'lifetime') return;

        const dateCreated = new Date($('#date_created').val());
        const billingLength = parseInt($('#slm_billing_length').val()) || 0;
        const billingInterval = $('#slm_billing_interval').val();
        
        // Use user-selected day if it exists, otherwise default to the day in `dateCreated`
        const dayToPreserve = userSelectedDay || dateCreated.getDate();

        // Adjust expiry date based on interval and preserved day
        let expiryDate = new Date(dateCreated);
        if (billingInterval === 'years') {
            expiryDate.setFullYear(dateCreated.getFullYear() + billingLength);
        } else if (billingInterval === 'months') {
            expiryDate.setMonth(dateCreated.getMonth() + billingLength);
        } else if (billingInterval === 'days') {
            expiryDate.setDate(dateCreated.getDate() + billingLength);
        }

        // Set the day to the preserved day, adjusting for month-end overflow
        expiryDate.setDate(Math.min(dayToPreserve, daysInMonth(expiryDate)));

        // Format and set expiry date
        $('#date_expiry').val(expiryDate.toISOString().split('T')[0]);
    }

    // Helper to calculate the number of days in a given month/year
    function daysInMonth(date) {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    }

    // Calculate interval and length based on expiry date
    function calculateIntervalAndLengthFromExpiry() {
        if ($('#lic_type').val() === 'lifetime') return; // Skip if lifetime

        const dateCreated = new Date($('#date_created').val());
        const dateExpiry = new Date($('#date_expiry').val());

        if (dateExpiry < dateCreated) {
            alert('Expiration date cannot be before the creation date.');
            $('#date_expiry').val($('#date_created').val()); // Reset to creation date
            return;
        }

        const diffTime = dateExpiry - dateCreated;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const diffMonths = Math.ceil(diffDays / 30);
        const diffYears = Math.ceil(diffMonths / 12);

        if (diffYears >= 1) {
            $('#slm_billing_interval').val('years');
            $('#slm_billing_length').val(diffYears);
        } else if (diffMonths >= 1) {
            $('#slm_billing_interval').val('months');
            $('#slm_billing_length').val(diffMonths);
        } else {
            $('#slm_billing_interval').val('days');
            $('#slm_billing_length').val(diffDays);
        }
    }

    // Adjust fields based on license type
    function adjustFieldsBasedOnType() {
        const licType = $('#lic_type').val();
        const isLifetime = licType === 'lifetime';

        if (isLifetime) {
            // Set expiration far in the future for lifetime licenses
            const expiryDate = new Date();
            expiryDate.setFullYear(expiryDate.getFullYear() + 200);
            $('#date_expiry').val(expiryDate.toISOString().split('T')[0]);
            userSelectedDay = null; // Clear any selected day
        }

        // Disable or enable fields based on license type
        $('#date_expiry, #slm_billing_length, #slm_billing_interval, #date_renewed')
            .prop('disabled', isLifetime)
            .closest('tr').toggle(!isLifetime);
    }

    // Track user-selected day when the expiration date is set manually
    $('#date_expiry').on('change', function() {
        const selectedDate = new Date($(this).val());
        userSelectedDay = selectedDate.getDate(); // Store selected day (e.g., 15)
        calculateIntervalAndLengthFromExpiry(); // Update interval and length if needed
    });

    // Set today's date for date_created if new record and disable the field
    const isEditRecord = window.location.search.includes('edit_record') || window.location.search.includes('slm_save_license');
    if (!isEditRecord) {
        const today = new Date().toISOString().split('T')[0];
        $('#date_created').val(today).prop('disabled', true);
    }

    // Recalculate expiry date when billing length or interval changes
    $('#slm_billing_length, #slm_billing_interval').on('change', function() {
        if ($('#lic_type').val() !== 'lifetime') {
            calculateExpiryDate();
        }
    });

    // Attach change event to license type and initialize on page load
    $('#lic_type').on('change', adjustFieldsBasedOnType);
    adjustFieldsBasedOnType(); // Initialize

    // Calculate initial expiry date on page load for new records
    calculateExpiryDate();
});
