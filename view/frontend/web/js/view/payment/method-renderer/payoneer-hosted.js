/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data'
    ],
    function ($, Component, customerData) {
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
                    if(response.redirectURL) {
                        customerData.invalidate(['cart']);
                        window.location.href = response.redirectURL;
                    } else {
                        $('body').trigger('processStop');
                        alert('Something went wrong while processing payment1');
                    }
                }).fail(function (response) {
                    $('body').trigger('processStop');
                    alert('Something went wrong while processing payment2');
                });
            }
        });
    }
);
