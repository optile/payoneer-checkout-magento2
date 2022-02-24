/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data'
    ],
    function ($, ko, Component, customerData) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payoneer_OpenPaymentGateway/payment/form',
                transactionResult: ''
            },
            isVisible: ko.observable(false),

            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            afterPlaceOrder: function () {
                alert('afterplaceorder');
            },

            initialize: function () {
                $('.payoneer.message.error').hide();
                this._super();
            },

            getCode: function() {
                return 'payoneer';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            isActive: function() {
                return window.checkoutConfig.payment.payoneer.config.active;
            },

            isHostedIntegration: function() {
                return window.checkoutConfig.payment.payoneer.config.payment_flow == 'HOSTED';
            },

            getErrorMessage: function() {
                return 'Something went wrong while processing payment2';
            },

            processHostedPayment: function() {
                $('.payoneer.message.error').hide();
                var endpoint = '/payoneer/hosted/processpayment';
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
                    $('.payoneer.message.error').show();
                    this.isVisible(true);
                    alert('Something went wrong while processing payment2');
                });
            }
        });
    }
);
