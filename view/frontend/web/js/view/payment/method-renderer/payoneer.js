/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url',
        'payoneerWidget'
    ],
    function (
        $,
        ko,
        Component,
        checkoutData,
        quote,
        fullScreenLoader,
        urlBuilder,
        payoneerWidget
    ) {
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
                        showPaymentMethod: ko.observable(false)
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
                                    self.processPayoneerPayment(newAddress);
                                }
                            }
                        }
                    }
                );

                quote.shippingAddress.subscribe(
                    function (newAddress) {
                        self.showHidePaymentMethod(newAddress)
                    }
                )

                quote.totals.subscribe(function (totals) {
                    if (self.getCurrentPaymentMethod() === self.getCode()) {
                        self.processPayoneerPayment('');
                    }
                });
                return this;
            },

            initialize: function () {
                $('.payoneer.message.error').hide();
                this._super();
                this.showHidePaymentMethod('');
            },

            getCode: function() {
                return 'payoneer';
            },

            getTitle: function () {
                let environment = window.checkoutConfig.payment.payoneer.config.environment;
                if (environment === 'test'){
                    return 'Test Mode: ' + this.item.title;
                }
                return this.item.title;
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
                let endpoint = window.checkoutConfig.payment.payoneer.config.processPaymentUrl;
                $('body').trigger('processStart');
                self.shouldShowMessage(false);
                $.ajax({
                    url: urlBuilder.build(endpoint),
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
                        if (response && response.links && response.links.self) {
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
                    fullScreenLoader.stopLoader();
                }).fail(function (response) {
                    $('.payoneer.message.error').show();
                    $('body').trigger('processStop');
                    self.shouldShowMessage(true);
                    fullScreenLoader.stopLoader();
                });
            },

            /**
             * Show or hide static payment icons
             */
             showStaticPaymentIcons: function() {
                if (
                    window.checkoutConfig.payment.payoneer.config.payment_icon_type == 'static' ||
                    window.checkoutConfig.payment.payoneer.config.payment_icon_type == 'both'
                ) {
                    return true;
                }
                return false;
            },

            /**
             * Get #paymentNetworks div class attribute value
             */
             getPaymentNetworkDivClassAttribute: function() {
                return 'payment-networks-container ' + window.checkoutConfig.payment.payoneer.config.payment_icon_type;
            },

            /**
             * On Payment page load, show/hide Payoneer payment method based on MoR
             */
            showHidePaymentMethod: function(newAddress) {
                this.showPaymentMethod(false); //start off by hiding it. And then show if needed.
                let self = this;
                let integrationType = '';
                if(this.isHostedIntegration()) {
                    integrationType = 'hosted';
                } else {
                    integrationType = 'embedded';
                }
                let endpoint = window.checkoutConfig.payment.payoneer.config.processPaymentUrl;
                    $('body').trigger('processStart');
                self.shouldShowMessage(false);
                $.ajax({
                    url: urlBuilder.build(endpoint),
                    type: "POST",
                    data: {
                        integration : integrationType,
                        shipAddress: JSON.stringify(newAddress)
                    },
                    dataType: 'json'
                }).done(function (response) {
                    if (response.hidePayment) {
                        self.showPaymentMethod(false);
                        $('#paymentNetworks').empty();
                    }
                    else {
                        self.showPaymentMethod(true);
                    }
                    $('body').trigger('processStop');
                    fullScreenLoader.stopLoader();
                }).fail(function (response) {
                    $('.payoneer.message.error').show();
                    $('body').trigger('processStop');
                    self.shouldShowMessage(true);
                    fullScreenLoader.stopLoader();
                });
            },
        });
    }
);
