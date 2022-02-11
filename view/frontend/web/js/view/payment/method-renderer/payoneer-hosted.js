/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payoneer_OpenPaymentGateway/payment/form-hosted',
                transactionResult: ''
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'payoneer';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            processHostedPayment: function() {
                var endpoint = '/payoneer/hosted/processpayment'
                $('body').trigger('processStart');
                $.ajax({
                    //showLoader: true,
                    url: endpoint,
                    type: "GET",
                    dataType: 'json'
                }).done(function (response) {
                    //console.log(response);
                    window.location.href = response.redirectURL;
                }).fail(function (data) {
                    alert('failure');
                });
            }
        });
    }
);
