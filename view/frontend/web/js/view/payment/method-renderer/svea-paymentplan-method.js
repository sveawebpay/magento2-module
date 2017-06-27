/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Webbhuset_SveaWebpay/js/view/payment/method-renderer/svea-address-abstract',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/error-processor',
        'priceUtils',
        'Magento_Checkout/js/model/quote',
    ],
    function (
        $,
        ko,
        $t,
        Component,
        totals,
        errorProcessor,
        priceUtils,
        quote
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Webbhuset_SveaWebpay/payment/svea-paymentplan',
            },
            initialize: function () {

                return this._super();
            },
            /** Initialize all knockout observables */
            initObservable: function () {
                this.campaigns = ko.computed(function() {
                    var allCampaigns = this.getCampaigns();
                    var self = this;

                    var availableCampaigns = ko.utils.arrayFilter(allCampaigns, function(campaign) {
                        if (self.isCampaignAvailable(campaign)) {

                            return campaign;
                        }
                    });

                    return availableCampaigns;

                }, this);

                this.selectedCampaignCode = ko.observable(function() {
                    // Set first available as default value
                    var firstAvailable = this.campaigns()[0];

                    return firstAvailable && firstAvailable.campaignCode;
                }.bind(this)());

                this.selectedCampaign = ko.pureComputed(function() {
                    var campaigns = this.campaigns();
                    var selectedCampaignCode = this.selectedCampaignCode();

                    var selectedCampaign = ko.utils.arrayFilter(campaigns, function(campaign) {
                        return (campaign.campaignCode === selectedCampaignCode);
                    })[0];

                    return selectedCampaign;
                }, this);

                this.pricePerMonthData = ko.observable([]);

                // Fetch monthly cost
                this.getPricePerMonthData();

                return this._super();
            },
            isCampaignAvailable: function(campaign) {
                var total = totals.totals().base_grand_total;

                var fromPrice = campaign['fromAmount'];
                var toPrice = campaign['toAmount'];
                var inPriceRange = (total >= fromPrice) && (total <= toPrice);

                if(!inPriceRange) {

                    return false;
                }

                return true;
            },
            getCampaigns: function() {

                return window.checkoutConfig.payment.svea_paymentplan.campaigns;
            },
            getPricePerMonthData: function() {
                var total = totals.totals().base_grand_total;

                var data = {
                    amount: total,
                    form_key: $.mage.cookies.get('form_key'),
                }

                var url = this.getPricePerMonthUrl();
                $.ajax({
                    url: url,
                    data: data,
                    method: 'POST',
                    context: this,
                    success: function (response) {
                        this.pricePerMonthData(response.values);
                    },
                    error: function(response) {
                        errorProcessor.process(response, this.messageContainer);
                    }
                });
            },
            getPricePerMonthForCampaign: function(campaignCode) {
                var data = this.pricePerMonthData();

                var campaignData = data.find(function(el) {
                    return el.campaignCode === campaignCode;
                });


                if (!campaignData) {
                    return this.formatPrice(0);
                }

                var price = campaignData.pricePerMonth;
                var priceString = this.formatPrice(price);

                return priceString;
            },
            formatPrice: function(price) {

                return priceUtils.formatPrice(price, quote.getPriceFormat());
            },
            getPricePerMonthUrl: function() {

                return window.checkoutConfig.payment.svea_paymentplan.price_per_month_url;
            },
            getData: function() {
                var result = {
                    'method': this.item.method,
                    'po_number': null,
                    additional_data: {}
                }
                var additional = result.additional_data;
                additional[this.getAdditionalDataSsnKey()] = this.ssn();
                additional[this.additionalDataSelectedAddressKey()] = this.selectedAddressSelector();
                additional[this.getAddtionalDataSelectedCampaignKey()] = this.selectedCampaignCode();

                return result;
            },
            getAdditionalDataSsnKey: function() {

                return window.checkoutConfig.payment.svea_paymentplan.additionalDataSsnKey;
            },
            getAddtionalDataSelectedCampaignKey: function() {

                return window.checkoutConfig.payment.svea_paymentplan.additionalDataSelectedCampaignKey;
            },
            additionalDataSelectedAddressKey: function() {

                return window.checkoutConfig.payment.svea_address.additionalDataSelectedAddressKey;
            },
            validate: function() {
                if (!this.validateAddress()) {

                    return false;
                }

                var campaignIsAvailable = this.isCampaignAvailable(this.selectedCampaign());
                if (!campaignIsAvailable) {
                    this.addErrorMessage('This campaign is not available');

                    return false;
                }

                return true;
            }
        });
    }
);
