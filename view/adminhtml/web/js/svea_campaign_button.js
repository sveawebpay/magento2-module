define(
    [
        'jquery',
        'uiElement',
        'ko'
    ],
    function(
        $,
        Element,
        ko
    ) {
        'use strict';

        return Element.extend({
            defaults: {
                template: 'Webbhuset_SveaWebpay/svea_campaign_button_template'
            },
            initialize: function (config) {
                this._super();
                this.configName = ko.observable(config.configName);
                this.url = config.getCampaignsUrl;
                this.campaigns = ko.observable();
                this.isUpdating = ko.observable(false);
                this.message = ko.observable('');

                this.campaigns(config.campaigns);
            },
            updateCampaigns: function() {
                var data = {
                    form_key: FORM_KEY
                }

                this.isUpdating(true);

                $.ajax({
                    url: this.url,
                    context: this,
                    method: 'GET',
                    data: data,
                    success: function(response) {
                        var campaigns = JSON.parse(response);
                        this.campaigns(campaigns);
                    },
                    error: function(response) {
                        this.message(response.responseJSON.message);
                        this.campaigns([]);
                    },
                    complete: function() {
                        this.isUpdating(false);
                    }
                });
            }
        });
    }
);
