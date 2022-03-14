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
                        isSuccessResponse: ko.observable(false)
                    });

                this.shouldShowMessage.subscribe(function (newValue) {
                });

                this.isSuccessResponse.subscribe(function (newValue) {
                });
                let self=this;
                /** @type {?Object} */
                let prevAddress;
                quote.billingAddress.subscribe(
                    function(newAddress) {
                        if (!newAddress ^ !prevAddress || newAddress.getKey() !== prevAddress.getKey()) {
                            prevAddress = newAddress;
                            if (newAddress) {
                                if (self.getCurrentPaymentMethod() == self.getCode()){
                                    self.processPayoneerPayment();
                                }
                            }
                        }
                    }
                );
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
                if (!this.isHostedIntegration()) {
                    this.processPayoneerPayment();
                }
                return this._super();
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

            getWidgetCssUrl: function() {
                return window.checkoutConfig.payment.payoneer.config.widgetCssUrl;
            },

            processPayoneerPayment: function() {
                var self = this;
                var integrationType = '';
                if(this.isHostedIntegration()) {
                    integrationType = 'hosted';
                } else {
                    integrationType = 'embedded';
                }
                $('.payoneer.message.error').hide();
                var endpoint = '/payoneer/integration/processpayment';
                $('body').trigger('processStart');
                self.shouldShowMessage(false);
                $.ajax({
                    url: endpoint,
                    type: "GET",
                    data: {
                        integration: integrationType
                    },
                    dataType: 'json'
                }).done(function (response) {
                    if(integrationType === 'hosted') {
                        if (response.redirectURL) {
                            window.location.href = response.redirectURL;
                        } else {
                            $('body').trigger('processStop');
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
                        $('body').trigger('processStop');
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
