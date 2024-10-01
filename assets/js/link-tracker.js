jQuery(document).ready(function($) {
    // Ellenőrizzük, hogy a fingerprint adatok már elérhetők-e
    function waitForFingerprintData(callback) {
        if (window.wpmetricslabFingerprintData) {
            callback();
        } else {
            setTimeout(function() {
                waitForFingerprintData(callback);
            }, 100);
        }
    }

    // A link kattintások kezelése
    function handleLinkClicks() {
        $('a').on('click', function(e) {
            var $link = $(this);
            var url = $link.attr('href');
            var encodedNumber = $link.data('href');
            var phoneNumber = encodedNumber ? atob(encodedNumber) : null;

            var isPhoneLink = url.startsWith('tel:') || phoneNumber;
            var eventType = isPhoneLink ? 'call' : 'click';
            var message = isPhoneLink ? 'Phone call' : 'Link clicked';
            var logUrl = phoneNumber ? 'tel:' + phoneNumber : url;

            if (window.wpmetricslabFingerprintData) {
                var fingerprintData = window.wpmetricslabFingerprintData.fingerprintData;
                var fingerprintHash = window.wpmetricslabFingerprintData.fingerprint;

                $.post(wpmetricslabLinkTracker.ajax_url, {
                    action: 'track_link_click',
                    url: logUrl,
                    eventType: eventType,
                    message: message,
                    fingerprint: fingerprintHash,
                    fingerprintData: fingerprintData,
                    nonce: wpmetricslabLinkTracker.track_link_nonce
                }).done(function(response) {
                    // console.log('Link click tracked successfully:', response);
                }).fail(function(xhr, status, error) {
                    // console.error('Failed to track link click:', status, error, xhr.responseText);
                });
            } else {
                // console.error('Fingerprint data not available.');
            }
        });
    }

    // Várjuk meg, amíg a fingerprint adatok elérhetők lesznek, majd indítsuk a link kattintások kezelését
    waitForFingerprintData(handleLinkClicks);
});
