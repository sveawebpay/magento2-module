define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
    ],
    function(
        $,
        Component,
        ko,
        quote,
        fullScreenLoader,
        resourceUrlManager,
        storage,
        errorProcessor,
        $t
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Webbhuset_SveaWebpay/svea_address'
            },
            initialize: function (config) {
                this.initObservable();

                return this._super();
            },
            /**
             * Init observables
             *
             * @returns {*}
             */
            initObservable: function () {
                this.ssn = ko.observable('');

                // If client changes the ssn, remove addresses
                this.ssn.subscribe(function(ssn) {
                    this.addresses([]);
                    this.selectedAddressSelector('');
                }, this);

                this.customerType = ko.observable('private');

                this.addresses = ko.observable([]);
                this.selectedAddressSelector = ko.observable('');

                // If there is only one address, choose the first one
                this.addresses.subscribe(function(addresses) {
                    if (addresses.length === 1) {
                        this.selectedAddressSelector(addresses[0].addressSelector);
                    }
                }, this);

                this.hasAddresses = ko.pureComputed(function() {
                    return this.addresses().length > 0;
                }, this);

                this.hasMultipleAddresses = ko.pureComputed(function() {
                    return this.addresses().length > 1;
                }, this);

                this.hasSingleAddress = ko.pureComputed(function() {
                    return this.addresses().length === 1;
                }, this);

                /**
                 * Prepare addresses for dropdown
                 */
                this.addressOptions = ko.pureComputed(function() {
                    var options = [];

                    ko.utils.arrayForEach(this.addresses(), function(item) {
                        options.push({value: item.addressSelector, text: item.fullname + ' ' + item.street});
                    });

                    return options;
                }, this);

                this.selectedAddress = ko.pureComputed(function() {
                    var selectedAddress = null;
                    var selectedAddressSelector = this.selectedAddressSelector();
                    ko.utils.arrayForEach(this.addresses(), function(item) {
                        if (item.addressSelector === selectedAddressSelector) {
                            selectedAddress = item;
                        }
                    });
                    return selectedAddress;
                }, this);

                this.customerTypeAttrName = ko.pureComputed(function() {
                    return 'svea-customer-type-' + this.getCode();
                }, this);

                return this._super();
            },
            /**
             * Get config selected address key
             *
             * @returns {exports.additionalDataSelectedAddressKey}
             */
            additionalDataSelectedAddressKey: function() {
                return window.checkoutConfig.payment.svea_address.additionalDataSelectedAddressKey;
            },
            /**
             * Get shipping country code
             *
             * @returns {string}
             */
            getShippingAddressCountryId: function() {
                return quote.shippingAddress().countryId;
            },
            /**
             * Fetch address(es) associated with ssn from svea
             *
             * @returns {void}
             */
            getAddress: function() {

                if (!this.shouldReplaceAddress()) {
                    return false;
                }
                var url = window.checkoutConfig.payment.svea_address.getAddressUrl;

                var data = {
                    ssn: this.ssn(),
                    countryId: this.getShippingAddressCountryId(),
                    customerType: this.customerType(),
                    paymentMethodCode: this.getCode(),
                    form_key: $.mage.cookies.get('form_key')
                };

                $.ajax({
                    url: url,
                    data: data,
                    showLoader: true,
                    context: this,
                    success: function (response) {
                        this.addresses(response || []);
                    },
                    error: function(response) {
                        errorProcessor.process(response, this.messageContainer);
                        this.addresses([]);
                    }
                });
            },
            /**
             * Sometimes we dont have to fetch the address, svea is satisfied with only the ssn
             *
             * @returns {boolean}
             */
            shouldReplaceAddress: function() {

                var config = window.checkoutConfig.payment.svea_address.config;
                var countryCode = quote.shippingAddress().countryId;
                if (
                    typeof(config[countryCode]) != 'undefined' &&
                    typeof(config[countryCode][this.customerType()]) != 'undefined' &&
                    typeof(config[countryCode][this.customerType()]['replace_address']) != 'undefined'
                ) {
                    var replaceAddress = config[countryCode][this.customerType()]['replace_address'];

                    return (replaceAddress == true);
                }

                return true;
            },
            addErrorMessage: function(message) {
                this.messageContainer.addErrorMessage({ message: $t(message) });
            },

            /**
             * Validate address
             *
             * @returns {boolean}
             */
            validateAddress: function() {
                if (!this.ssn()) {
                    this.addErrorMessage('Please enter ssn.');

                    return false;
                }

                if (this.shouldReplaceAddress() && !this.selectedAddressSelector()) {
                    this.addErrorMessage('No address selected');

                    return false;
                }

                return true;
            }
        });
    }
);
