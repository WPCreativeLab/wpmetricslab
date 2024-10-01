jQuery(document).ready(function($) {
    // Törlés megerősítése
    $('#delete-logs-form').submit(function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete the selected logs?')) {
            return false;
        }

        var logIds = $('.log_checkbox:checked').map(function() { return this.value; }).get();
        var data = {
            'action': 'delete_selected_logs',  // Módosított akció név
            'log_ids': logIds,
            'nonce': wpmetricslabDeleteLogs.delete_logs_nonce
        };

        $.post(wpmetricslabDeleteLogs.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    alert('Logs successfully deleted.');
                    location.reload();
                } else {
                    var errorMessage = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    alert('Error: ' + errorMessage);
                }
            })
            .fail(function(xhr) {
                var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to communicate with server';
                alert('Error: ' + errorMessage);
            });
    });
});
