/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'payoneerWidget'
    ],
    function ($, ko, Component, checkoutData, quote, payoneerWidget) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payoneer_OpenPaymentGateway/payment/form',
                transactionResult: '',
            },

            initObservable: function () {
                this._super()
                    .observe({
                        shouldShowMessage: ko.observable(false),
                        isSuccessResponse: ko.observable(false),
                        isPaymentProcessed: ko.observable(false)
                    });

                this.shouldShowMessage.subscribe(function (newValue) {
                });

                this.isSuccessResponse.subscribe(function (newValue) {
                });
                let self=this;
                let prevAddress;
                quote.billingAddress.subscribe(
                    function(newAddress) {
                        if (!newAddress ^ !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                            prevAddress = newAddress;
                            if (newAddress) {
                                if (self.getCurrentPaymentMethod() === self.getCode()){
                                    self.isPaymentProcessed(true);
                                    self.processPayoneerPayment(newAddress);
                                }
                            }
                        }
                    }
                );

                quote.totals.subscribe(function (totals) {
                    if (!self.isPaymentProcessed()){
                        if (self.getCurrentPaymentMethod() === self.getCode()) {
                            self.processPayoneerPayment('');
                        }
                    }
                    self.isPaymentProcessed(false);
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

            getCurrentPaymentMethod: function() {
                return checkoutData.getSelectedPaymentMethod();
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            selectPaymentMethod: function () {
                let isSelected = this._super();
                if (isSelected) {
                    this.isPaymentProcessed(true);
                    this.processPayoneerPayment('');
                }
                return isSelected;
            },

            isActive: function() {
                return window.checkoutConfig.payment.payoneer.config.active;
            },

            isHostedIntegration: function() {
                return window.checkoutConfig.payment.payoneer.config.payment_flow === 'HOSTED';
            },

            getErrorMessage: function() {
                return 'Something went wrong while processing payment';
            },

            /**
             * Get widget css url
             * @returns {*}
             */
            getWidgetCssUrl: function() {
                return window.checkoutConfig.payment.payoneer.config.widgetCssUrl;
            },

            /**
             * Proceed to hosted page
             */
            proceedToPayoneer: function() {
                if (window.checkoutConfig.payment.payoneer.config.redirectURL !== undefined) {
                    window.location.href = window.checkoutConfig.payment.payoneer.config.redirectURL;
                } else {
                    self.shouldShowMessage(true);
                }
            },

            /**
             * Process Payoneer payment
             */
            processPayoneerPayment: function(newAddress) {
                let self = this;
                let integrationType = '';
                let changedAddress = newAddress;
                if(this.isHostedIntegration()) {
                    integrationType = 'hosted';
                } else {
                    integrationType = 'embedded';
                }
                $('.payoneer.message.error').hide();
                let endpoint = '/payoneer/integration/processpayment';
                $('body').trigger('processStart');
                self.shouldShowMessage(false);
                $.ajax({
                    url: endpoint,
                    type: "POST",
                    data: {
                        integration : integrationType,
                        address: JSON.stringify(changedAddress)
                    },
                    dataType: 'json'
                }).done(function (response) {
                    if(integrationType === 'hosted') {
                        if (response.redirectURL) {
                            window.checkoutConfig.payment.payoneer.config.redirectURL = response.redirectURL;
                        } else {
                            self.shouldShowMessage(true);
                        }
                    } else{
                        if (response.links) {
                            var configObj = {
                                payButton: 'submitBtn',
                                payButtonContainer: 'submitBtnContainer',
                                listUrl: response.links.self,
                                smartSwitch: true,
                                fullPageLoading: false,
                                widgetCssUrl: self.getWidgetCssUrl()
                            }
                            $('#paymentNetworks').empty();
                            checkoutList('paymentNetworks',configObj);
                            self.isSuccessResponse(true);
                        } else{
                            self.shouldShowMessage(true);
                        }
                    }
                    $('body').trigger('processStop');
                }).fail(function (response) {
                    $('.payoneer.message.error').show();
                    $('body').trigger('processStop');
                    self.shouldShowMessage(true);
                });
            }
        });
    }
);
