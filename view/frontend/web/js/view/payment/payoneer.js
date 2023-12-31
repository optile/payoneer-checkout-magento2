/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'payoneer',
                component: 'Payoneer_OpenPaymentGateway/js/view/payment/method-renderer/payoneer'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
