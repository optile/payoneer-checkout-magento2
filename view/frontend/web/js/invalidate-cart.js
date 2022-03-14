define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function ($, customerData, urlBuilder){
    'use strict';
    $.widget('mage.invalidateCart', {
        _init: function () {
            var url = urlBuilder.build('checkout/onepage/success');
            customerData.invalidate(['cart']);
            window.location.href = url;
        }
    });
    return $.mage.invalidateCart;
});
