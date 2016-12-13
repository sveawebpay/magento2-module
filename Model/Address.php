<?php
/**
 * Svea address model
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model;
use Symfony\Component\Config\Definition\Exception\Exception;
use Svea\WebPay\WebPay;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;

/**
 * A svea address
 *
 * @package Webbhuset\SveaWebpay\Model
 */
class Address
{

    /**
     * String constant for company addresses.
     *
     * This comes from SVEA for some reason.
     */
    const SVEA_BUSINESS_TYPE = 'Business';

    /**
     * Internal constant for company.
     */
    const COMPANY_TYPE = 'company';

    /**
     * String constant for protected persion addresses.
     *
     * This comes from SVEA.
     */
    const PRIVATE_TYPE = 'private';

    /**
     * Required keys for an address.
     */
    const REQUIRED_KEYS = [
        "customerType",
        "addressSelector",
        "publicKey",

        "nationalIdNumber",
        "firstname",
        "lastname",
        "fullname",
        "company",
        "telephone",
        "coAddress",
        "street",
        "postcode",
        "city",
        "region",
        "regionId",
        "countryId",
    ];

    /**
     * Create a new address
     *
     * @param Configuration $apiConfig
     */
    public function __construct(Configuration $apiConfig)
    {
        $this->apiConfig = $apiConfig;
    }

    /**
     * Get address
     *
     * @param array $params
     * @return array|bool
     */
    public function getAddress(array $params)
    {
        $response = $this->fetchAddress($params);
        if (!$response->accepted) {
            return false;
        }

        $addresses = $this->mapToAddressArray($response, $params);

        return $addresses;
    }

    /**
     * Perform a lookup based on ssn
     *
     * @param array $params Array with 'ssn', 'countryId', 'customerType' and 'paymentMethodCode' set.
     * @return \Webbhuset\SveaWebpay\Model\AddressResponse
     * @throws \Exception
     */
    protected function fetchAddress(array $params)
    {
        $countryId = $params['countryId'];
        $paymentMethodCode = $params['paymentMethodCode'];
        $customerType = $params['customerType'];
        $ssn = $params['ssn'];

        $apiConfig = $this->apiConfig;

        $service = Webpay::getAddresses($apiConfig)
            ->setCountryCode($countryId)
            ->setCustomerIdentifier($ssn);

        $service->orderType = $apiConfig->paymentMethodCodeToSveaMethodCode($paymentMethodCode);
        $service->testmode = $apiConfig->isTestMode($paymentMethodCode);
        if ($customerType === self::COMPANY_TYPE) {
            $service->setCompany($ssn);
        } else {
            $service->setIndividual($ssn);
        }

        $response = $service->doRequest();

        return $response;
    }

    // magento field => svea field
    public $mapper = [
        'addressSelector'   => 'addressSelector',
        'publicKey'         => 'publicKey',
        'nationalIdNumber'  => 'nationalIdNumber',

        'firstname' => 'firstName',
        'lastname'  => 'lastName',
        'fullname'  => 'fullName',
        'coAddress' => 'coAddress',
        'telephone' => 'phoneNumber',
        'postcode'  => 'zipCode',
        'city'      => 'locality',
    ];

    /**
     * Map svea address to magento address compatible array
     * @param \Svea\WebPay\WebService\WebServiceResponse\GetAddressesResponse $response
     * @param array $params
     * @return array
     */
    protected function mapToAddressArray(
        \Svea\WebPay\WebService\WebServiceResponse\GetAddressesResponse $response,
        array $params
    ) {
        $customerIdentity = $response->customerIdentity;

        $addresses = [];
        foreach ($customerIdentity as $identity) {
            $address = [];

            foreach ($this->mapper as $newField => $sveaField) {
                $address[$newField] = $identity->$sveaField;
            }

            $address['street'] = [$identity->street]; // make street array

            $address['regionId'] = null;
            $address['region'] = null;
            $address['country_id'] = $params['countryId'];

            if ($identity->customerType === self::SVEA_BUSINESS_TYPE) {
                $address['customer_type'] = self::COMPANY_TYPE;
                $address['company'] = $identity->fullName;
            } else {
                $address['customer_type'] = self::PRIVATE_TYPE;
                $address['company'] = '';
            }

            $addresses[] = $address;
        }

        return $addresses;
    }

}
