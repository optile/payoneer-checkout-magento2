/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'payoneerWidget'
    ],
    function ($, ko, Component, payoneerWidget) {
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

            deferUntilJqueryAvailable: function(fn) {
                if (window.jQuery) {
                    // If jquery is available, then run the function
                    fn();
                } else {
                    // Check every 50ms until jQuery is available
                    setTimeout(function() { defer(fn) }, 50);
                }
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

                            // Run this function when window.jQuery is available.
                            self.deferUntilJqueryAvailable(function() {
                                checkoutList('paymentNetworks',configObj);
                            });

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
