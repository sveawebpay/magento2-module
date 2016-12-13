/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Webbhuset_SveaWebpay/js/view/payment/method-renderer/svea-address-abstract',
    ],
    function (
        $,
        ko,
        $t,
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Webbhuset_SveaWebpay/payment/svea-invoice',
            },
            initialize: function () {
                return this._super().initObservable();
            },
            /** Initialize all knockout observables */
            initObservable: function () {

                return this._super();
            },
            // Get additional data that will be sent to the server on place order
            getData: function() {
                var result  = {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {}
                }

                var additional = result.additional_data;

                additional[this.getAdditionalDataSsnKey()] = this.ssn();
                additional[this.additionalDataSelectedAddressKey()] = this.selectedAddressSelector();

                return result;
            },
            getAdditionalDataSsnKey: function() {
                return window.checkoutConfig.payment.svea_invoice.additionalDataSsnKey;
            },
            additionalDataSelectedAddressKey: function() {
                return window.checkoutConfig.payment.svea_address.additionalDataSelectedAddressKey;
            },
            validate: function() {
                if (!this.validateAddress()) {
                    return false;
                }

                return true;
            }

       });
    }
);
