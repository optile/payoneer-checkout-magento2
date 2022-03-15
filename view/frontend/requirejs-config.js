var config = {
    map: {
        '*': {
            invalidateCart:'Payoneer_OpenPaymentGateway/js/invalidate-cart',
            payoneerWidget:'Payoneer_OpenPaymentGateway/js/op-payment-widget-v3'
        }
    },
    shim: {
        payoneerWidget: {
            deps: ['jquery']
        }
    }
};
