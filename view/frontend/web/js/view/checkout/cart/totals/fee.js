/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'ko',
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/get-totals',
    ],
    function (ko, Component, quote, totals, setPaymentInformation, getTotals) {
        "use strict";

        return Component.extend({
            defaults: {
                isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
                displayMode: window.checkoutConfig.reviewHandlingFeeDisplayMode,
                template: 'Webbhuset_SveaWebpay/checkout/cart/totals/fee'
            },
            feeName: 'handling_fee',
            initialize: function () {
                return this._super().initObservable();
            },
            /** Initialize all knockout observables */
            initObservable: function () {
                this.currentMethod = ko.observable(null);
                this.isBothPricesDisplayed = ko.observable(false);

                quote.paymentMethod.subscribe(function(newMethod) {
                    if (this.currentMethod() == newMethod.method) {
                        return;
                    }

                    this.currentMethod(newMethod.method);

                    var payload = _.pick(
                        newMethod,
                        'method',
                        'additional_data',
                        'po_number',
                        'extension_attributes'
                    );

                    setPaymentInformation(this.messageContainer, payload)
                        .done(function() {
                            getTotals([]);
                        });

                }, this);

                return this._super();
            },
            getTitle: function() {
                return totals.getSegment(this.feeName).title;
            },
            isDisplayed: function() {
                var price = totals.getSegment(this.feeName).value;

                return price && this.isFullMode();
            },
            getValue: function() {
                var price = totals.getSegment(this.feeName).value;

                return this.getFormattedPrice(price);
            },
            getIncludingValue: function() {
                var price = 0;

                var segment = totals.getSegment(this.feeName);

                if (
                    segment.extension_attributes
                    && segment.extension_attributes.handling_fee_incl_tax
                ) {
                    price = totals.getSegment(this.feeName).extension_attributes.handling_fee_incl_tax;
                }

                return this.getFormattedPrice(price);
            },
            displayBothPrices: function() {
                return 'both' == this.displayMode
            },
            isIncludingDisplayed: function() {
                return 'including' == this.displayMode;
            },
            isExcludingDisplayed: function() {
                return 'excluding' == this.displayMode;
            }
        });
    }
);
