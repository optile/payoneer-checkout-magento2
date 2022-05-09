require([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'domReady!'
], function ($, alert, $t) {
    window.payoneerValidator = function (endpoint, envId) {
        envId = $('[data-ui-id="' + envId + '"]').val();
        var merchCode = '', storeCode = '', apiKey = '', hostName = '';
        merchCode =  $('[data-ui-id="text-groups-payoneer-fields-merchant-gateway-key-value"]').val();
        if (envId === 'test') {
            apiKey = $('[data-ui-id="password-groups-payoneer-fields-sandbox-api-key-value"]').val();
            storeCode = $('[data-ui-id="text-groups-payoneer-fields-sandbox-store-code-value"]').val();
            hostName = $('[data-ui-id="text-groups-payoneer-fields-sandbox-host-name-value"]').val();
        } else {
            apiKey = $('[data-ui-id="password-groups-payoneer-fields-live-api-key-value"]').val();
            storeCode = $('[data-ui-id="text-groups-payoneer-fields-live-store-code-value"]').val();
            hostName = $('[data-ui-id="text-groups-payoneer-fields-live-host-name-value"]').val();
        }

        /* Remove previous success message if present */
        if ($(".payoneer-credentials-success-message")) {
            $(".payoneer-credentials-success-message").remove();
        }

        /* Basic field validation */
        var errors = [];

        if (!envId || envId !== 'test' && envId !== 'live') {
            errors.push($t("Please select an Environment"));
        }

        if (!merchCode) {
            errors.push($t("Please enter a Merchant Code"));
        }

        if (!apiKey) {
            errors.push($t("Please enter an API Key"));
        }

        if (!hostName) {
            errors.push($t('Please enter a Host Name'));
        }

        if (errors.length > 0) {
            alert({
                title: $t('Payoneer Credential Validation Failed'),
                content:  errors.join('<br />')
            });
            return false;
        }

        $(this).text($t("We're validating your credentials...")).attr('disabled', true);

        var self = this;
        $.post(endpoint, {
            environment: envId,
            merchantCode: merchCode,
            apiKey: apiKey,
            storeCode: storeCode,
            hostName: hostName
        }).done(function (response) {
            if (response.data &&
                response.data.interaction &&
                response.data.interaction.reason &&
                response.data.interaction.reason === 'OK') {
                $('<div class="message message-success payoneer-credentials-success-message">' + $t("Your credentials are valid.") + '</div>').insertAfter(self);
            } else {
                alert({
                    title: $t('Payoneer Credential Validation Failed'),
                    content: $t('Your Payoneer Credentials could not be validated. Please ensure you have selected the correct environment and entered a valid Merchant Code, API Key, Store Code and Host Name.')
                });
            }
        }).fail(function () {
            alert({
                title: $t('Payoneer Credential Validation Failed'),
                content: $t('Your Payoneer Credentials could not be validated. Please ensure you have selected the correct environment and entered a valid Merchant Code, API Key, Store Code and Host Name.')
            });
        }).always(function () {
            $(self).text($t("Validate Credentials")).attr('disabled', false);
        });
    }
});
