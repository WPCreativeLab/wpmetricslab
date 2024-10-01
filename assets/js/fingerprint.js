jQuery(document).ready(function($) {
    if (window.location.pathname.indexOf('admin-ajax.php') === -1) { // Kizárjuk az admin-ajax.php kéréseket
        ThumbmarkJS.getFingerprintData().then(function(fingerprintData) {
            // console.log(fingerprintData);  // Ellenőrizzük a fingerprintData struktúráját a konzolban
            // console.log('Fingerprint Hash:', fingerprintHash);  // Naplózzuk a fingerprint hash értékét
            const fingerprintHash = fingerprintData.canvas.commonImageDataHash; // Használjuk a canvas hash értéket

            // Állítsuk be a fingerprint adatokat a window objektumon
            window.wpmetricslabFingerprintData = {
                fingerprint: fingerprintHash,
                fingerprintData: JSON.stringify(fingerprintData)
            };

            $.post(wpmetricslabLinkTracker.ajax_url, {
                action: 'track_page_view',
                fingerprint: fingerprintHash,
                fingerprintData: JSON.stringify(fingerprintData),
                currentUrl: window.location.href, // Az aktuális URL hozzáadása
                nonce: wpmetricslabLinkTracker.track_link_nonce
            }).done(function(response) {
                // console.log('Page view tracked successfully:', response);
            }).fail(function(xhr, status, error) {
                // console.error('Failed to track page view:', status, error, xhr.responseText);
            });
        });
    }
});
