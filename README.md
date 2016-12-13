# SVEA Ekonomi Webpay Magneto 2 module

## Dependencies

This module uses Svea Ekonomi's integration packages
The integration packages requires php extensions for SOAP and cURL.

## Installation
Make sure your minimum stability in your
composer.json is set to "dev".


```composer require sveaekonomi/magento2-module```

Run upgrade scripts

```php bin/magento setup:upgrade```


## Configuration

In your magento admin, go to Stores->Configuration->Sales->Payment Methods

"Hosted Settings" refers to credentials for both Bank and Card payments.

For invoice and payment plan, make sure your Svea account's country the allowed country in Magento.

### Invoice
You can set a payment handling fee on your invoice payments. Go to the settings for Invoice and enter the fee excluding tax.
You can set the tax class for the fee under Stores->Configuration->Sales->Tax->Tax Classes

### Payment plan
To update you active payment plan campaigns, enter your credentials and save. Then click on the ``Update Campaigns`` button.
When your campaigns appear, save your settings again.

### Card payment
If `Capture card order on confirmation` is set to yes, the Magento order will be invoiced when receiving the callback from Svea. If set to no, you will invioce the order manually.

### Direct bank payment
Bank orders cannot be invoiced until they are captured at Svea.
