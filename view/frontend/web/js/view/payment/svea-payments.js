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
                type: 'svea_invoice',
                component: 'Webbhuset_SveaWebpay/js/view/payment/method-renderer/svea-invoice-method'
            },
            {
                type: 'svea_card',
                component: 'Webbhuset_SveaWebpay/js/view/payment/method-renderer/svea-card-method'
            },
            {
                type: 'svea_direct_bank',
                component: 'Webbhuset_SveaWebpay/js/view/payment/method-renderer/svea-bank-method'
            },
            {
                type: 'svea_paymentplan',
                component: 'Webbhuset_SveaWebpay/js/view/payment/method-renderer/svea-paymentplan-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
