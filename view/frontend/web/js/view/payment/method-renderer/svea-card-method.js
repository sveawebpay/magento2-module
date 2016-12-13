/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/error-processor',
    ],
    function (
        $,
        ko,
        $t,
        Component,
        errorProcessor
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Webbhuset_SveaWebpay/payment/svea-card',
            },
            // Use our own redirect on success
            redirectAfterPlaceOrder: false,
            initialize: function () {
                return this._super().initObservable();
            },
            /** Initialize all knockout observables */
            initObservable: function () {
                this.sveaForm       = ko.observable([]);

                return this._super();
            },
            /**
             * Get card icons (mastercard etc)
             */
            getIcons: function() {
                return window.checkoutConfig.payment.svea_card.icons;
            },
            /**
             * Get data to be sent to backend on place order
             *
             * @returns {{method: (*|String), po_number: null, additional_data: {}}}
             */
            getData: function() {
                var result  = {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {}
                }

                var selectedMethodKey = window.checkoutConfig.payment.svea_card.selected_hosted_method_key;
                var selectedMethod = window.checkoutConfig.payment.svea_card.selected_hosted_method_value;
                result.additional_data[selectedMethodKey] = selectedMethod;

                return result;
            },
            /**
             * After successful order, fetch and post svea form
             */
            afterPlaceOrder: function() {
                var self = this;

                var data = {
                    method: self.getCode(),
                    form_key: $.mage.cookies.get('form_key')
                }

                $.ajax({
                    url: window.checkoutConfig.payment.svea_card.redirectOnSuccessUrl,
                    context: this,
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        self.sveaForm(response.form);
                        $('#svea-payment-form').submit();
                    },
                    error: function(response) {
                        fullscreenLoader.stopLoader();
                        errorProcessor.process(response, this.messageContainer);
                    }
                });
            }
        });
    }
);
