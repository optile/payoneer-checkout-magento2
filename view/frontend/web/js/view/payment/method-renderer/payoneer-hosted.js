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
                transactionResult: '',
            },

            initObservable: function () {
                this._super()
                    .observe({
                        shouldShowMessage: ko.observable(false)
                    });
                this.shouldShowMessage.subscribe(function (newValue) {
                });
                return this;
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
                return 'Something went wrong while processing payment';
            },

            processHostedPayment: function() {
                var self = this;
                $('.payoneer.message.error').hide();
                var endpoint = '/payoneer/hosted/processpayment';
                $('body').trigger('processStart');
                self.shouldShowMessage(false);
                $.ajax({
                    url: endpoint,
                    type: "GET",
                    dataType: 'json'
                }).done(function (response) {
                    if(response.redirectURL) {
                        //customerData.invalidate(['cart']);
                        window.location.href = response.redirectURL;
                    } else {
                        $('body').trigger('processStop');
                        self.shouldShowMessage(true);
                    }
                }).fail(function (response) {
                    $('body').trigger('processStop');
                    $('.payoneer.message.error').show();
                    self.shouldShowMessage(true);
                });
            }
        });
    }
);
