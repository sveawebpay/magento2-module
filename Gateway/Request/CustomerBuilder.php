<?php
/**
 * Customer data request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Webbhuset\SveaWebpay\Model\Address;
use Svea\WebPay\WebPayItem;

class CustomerBuilder
{
    public function build($data)
    {
        $address = $data['address'];
        $nationalIdNumber = $data['national_id_number'];

        $customerType = $address->getCustomerType();

        if ($customerType == Address::COMPANY_TYPE) {
            return $this->getCompanyCustomer($address, $nationalIdNumber);
        }

        return $this->getIndividualCustomer($address, $nationalIdNumber);
    }

    protected function getIndividualCustomer($address, $nationalIdNumber)
    {
        $customer = WebPayItem::individualCustomer()
            ->setName($address->getFirstname(), $address->getLastname())             // String // invoice, payment plan: required, use (firstName, lastName) for customers in NL and DE
            ->setStreetAddress(implode('\n', $address->getStreet()))   // String // invoice, payment plan: required, use (street, houseNumber) in NL and DE
            ->setZipCode($address->getPostcode())           // String    // invoice, payment plan: required in NL and DE
            ->setPhoneNumber($address->getTelephone())      // String // invoice, payment plan: optional but desirable
            ->setEmail($address->getEmail())         // String   // invoice, payment plan: optional but desirable
            ->setLocality($address->getCity())
            ->setNationalIdNumber($nationalIdNumber);


        switch ($address['country_id']) {
            case 'SE':
            case 'NO':
            case 'DK':
            case 'FI':
                break;
            case 'DE':
            case 'NL':
                $customer->setBirthDate(); // TODO use date format YYYY-MM-DD
                break;
            default:
                break;
        }

        return $customer;
    }

    protected function getCompanyCustomer($address, $nationalIdNumber)
    {
        $customer = WebPayItem::companyCustomer()
            ->setCompanyName($address->getCompany())      // String // invoice: required (companyName) for company customers in NL and DE
            ->setCoAddress($address->getData('coAddress'))        // String // invoice: optional
            ->setStreetAddress(implode('\n', $address->getStreet()))    // String // invoice: required, use (street, houseNumber) in NL and DE
            ->setZipCode($address->getPostcode())           // String    // invoice: required in NL and DE
            ->setLocality($address->getCity())         // String // invoice: required in NL and DE
            ->setPhoneNumber($address->getTelephone())      // String // invoice: optional but desirable
            ->setEmail($address->getEmail())         // String   // invoice, payment plan: optional but desirable
            ->setAddressSelector($address->getData('addressSelector'))  // String // invoice: optional but recommended; received from WebPay::getAddresses() request response
            ->setPublicKey($address->getData('publicKey'))        // String       // invoice, payment plan: opt
            ->setNationalIdNumber($nationalIdNumber);

        $countryId = $address->getCountryId();
        switch ($countryId) {
            case 'SE':
            case 'NO':
            case 'DK':
            case 'FI':
                break;
            case 'DE':
            case 'NL':
                $customer->setVatNumber();        // TODO    // invoice: required for company customers in NL and DE
                break;
            default:
                break;
        }

        return $customer;
    }
}
