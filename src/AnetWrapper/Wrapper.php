<?php

namespace AnetWrapper;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class Wrapper
{
    private $auth;

    private $creditCardData = array(
        'number' => '',
        'expiration' => '',
        'code' => ''
    );

    private $billData = array(
        'firstName' => '',
        'lastName' => '',
        'company' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => '',
        'phone' => '',
        'fax' => '',
    );

    private $shippingData = array(
        'firstName' => '',
        'lastName' => '',
        'company' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => '',
        'phone' => '',
        'fax' => '',
    );

    private $shippingAddresses = [];

    private $creditCard;

    private $paymentCreditCard = null;

    private $billProfile = null;

    private $paymentProfiles = [];

    private $customerProfile;

    private $request = null;

    private $response = null;


    /**
     * @var string Validation mode. Default - disable. Modes: liveMode, testMode
     */

    public $validationMode = '';

    /**
     *
     */

    public function __construct(string $loginId, string $transactionKey)
    {
        $this->auth = new AnetAPI\MerchantAuthenticationType();
        $this->auth->setName($loginId);
        $this->auth->setTransactionKey($transactionKey);

        return $this;
    }

    /**
     *
     * Add credit card data
     *
     * @param int $number - credit card number (16 digit)
     * @param string $expiration - expiration date (format 2018-05)
     * @param int $code - 3 digit CSV code
     *
     * @return self
     */

    public function addCreditCard(int $number, string $expiration, int $code)
    {
        $this->creditCardData['number'] = $number;
        $this->creditCardData['expiration'] = $expiration;
        $this->creditCardData['code'] = $code;
        $this->applyCreditCard();
        return $this;
    }


    /**
     *  Set credit card data object
     *
     */

    private function applyCreditCard()
    {
        $this->creditCard = new AnetAPI\CreditCardType();
        $this->creditCard->setCardNumber($this->creditCardData['number']);
        $this->creditCard->setExpirationDate($this->creditCardData['expiration']);
        $this->creditCard->setCardCode($this->creditCardData['code']);
        $this->paymentCreditCard = new AnetAPI\PaymentType();
        $this->paymentCreditCard->setCreditCard($this->creditCard);
    }

    /**
     * Adding client billing information
     *
     * array['firstName']        string Fist name.
     *      ['lastName']         string Last name.
     *      ['company']          string Company name
     *      ['address']          string Billing address
     *      ['city']             string City
     *      ['state']            string State
     *      ['zip']              string Zip code
     *      ['country']          string country (for example, USA)
     *      ['phone']            string phone (for example, 888-888-8888)
     *      ['fax']              string Fax (for example, 888-888-8888)
     *
     * @param array $data (See above)
     *
     * @return self
     */

    public function addBillInfo(array $data)
    {
        $keys = array_keys($this->billData);
        foreach ($keys as $key) {
            if (isset ($data[$key])) {
                $billTo[$key] = $data[$key];
            } else {
                $billTo[$key] = '';
            }
        }
        $this->setBillInfo($billTo);

        return $this;
    }

    /**
     *  Set billing info data object
     */

    private function setBillInfo(array $billData)
    {
        $billTo = new AnetAPI\CustomerAddressType();
        $billTo->setFirstName($billData['firstName']);
        $billTo->setLastName($billData['lastName']);
        $billTo->setCompany($billData['company']);
        $billTo->setAddress($billData['address']);
        $billTo->setCity($billData['city']);
        $billTo->setState($billData['state']);
        $billTo->setZip($billData['zip']);
        $billTo->setCountry($billData['country']);
        $billTo->setPhoneNumber($billData['phone']);
        $billTo->setfaxNumber($billData['fax']);

        $this->billProfile = $billTo;
    }

    /**
     * Adding client shipping address information
     * The function can be called more than once
     *
     * array['firstName']        string Fist name.
     *      ['lastName']         string Last name.
     *      ['company']          string Company name
     *      ['address']          string Billing address
     *      ['city']             string City
     *      ['state']            string State
     *      ['zip']              string Zip code
     *      ['country']          string country (for example, USA)
     *      ['phone']            string phone (for example, 888-888-8888)
     *      ['fax']              string Fax (for example, 888-888-8888)
     *
     * @param array $data (See above)
     *
     * @return self
     */

    public function addShippingAddress(array $data)
    {
        $shippingAddress = [];
        foreach ($this->shippingData as $key => $value) {
            if (isset ($data[$key])) {
                $shippingAddress[$key] = $data[$key];
            } else {
                $shippingAddress[$key] = '';
            }
        }

        $this->setShippingAddress($shippingAddress);

        return $this;
    }

    /**
     * Add shipping address to addresses object
     */

    private function setShippingAddress(array $shippingAddress)
    {
        $customerShippingAddress = new AnetAPI\CustomerAddressType();
        $customerShippingAddress->setFirstName($shippingAddress['firstName']);
        $customerShippingAddress->setLastName($shippingAddress['lastName']);
        $customerShippingAddress->setCompany($shippingAddress['company']);
        $customerShippingAddress->setAddress($shippingAddress['address']);
        $customerShippingAddress->setCity($shippingAddress['city']);
        $customerShippingAddress->setState($shippingAddress['state']);
        $customerShippingAddress->setZip($shippingAddress['zip']);
        $customerShippingAddress->setCountry($shippingAddress['country']);
        $customerShippingAddress->setPhoneNumber($shippingAddress['phone']);
        $customerShippingAddress->setFaxNumber($shippingAddress['fax']);

        $this->shippingAddresses[] = $customerShippingAddress;
    }

    /**
     * Add profile data to profiles
     *
     * @param string $type Type of profile (for example, individual). Default value - individual
     * @param bool $default Will this profile be used by default? (Default, true)
     *
     * @return self
     */

    public function createPaymentProfile(string $type = 'individual', bool $default = true)
    {
        $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
        $paymentProfile->setCustomerType($type);
        $paymentProfile->setBillTo($this->billProfile);
        $paymentProfile->setPayment($this->paymentCreditCard);
        $paymentProfile->setDefaultPaymentProfile(true);
        $this->paymentProfiles[] = $paymentProfile;

        return $this;
    }

    /**
     * @param string $customerProfileId
     * @param string $customerPaymentProfileId
     * @param string $environment
     * @return AnetAPI\AnetApiResponseType
     */
    public function updatePaymentProfile(
        string $customerProfileId,
        string $customerPaymentProfileId,
        string $environment = 'sandbox'
    ) {

        $env = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        switch ($environment) {
            case 'sandbox':
                $env = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
                break;
            case 'production':
                $env = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
                break;
        }

        $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->auth);
        $request->setCustomerProfileId($customerProfileId);
//        $controller = new AnetController\GetCustomerProfileController($request);

        // Create the Customer Payment Profile object
        $paymentProfile = new AnetAPI\CustomerPaymentProfileExType();
        $paymentProfile->setCustomerPaymentProfileId($customerPaymentProfileId);

        $receiveProfile = $this->getCustomerPaymentProfile($customerProfileId, $customerPaymentProfileId);

        if ($this->billProfile != null) {
            $paymentProfile->setBillTo($this->billProfile);
        }elseif ($receiveProfile != null){
            $paymentProfile->setBillTo($receiveProfile->getPaymentProfile()->getbillTo());
        }
        if ($this->paymentCreditCard != null) {
            $paymentProfile->setPayment($this->paymentCreditCard);
        }elseif($receiveProfile != null){
            $paymentProfile->setPayment($receiveProfile->getPaymentProfile()->getPayment());
        }

        // Submit a UpdatePaymentProfileRequest
        if (!empty($this->validationMode)
            and ($this->validationMode == 'testMode' or $this->validationMode == 'liveMode')) {
            $request->setValidationMode($this->validationMode);
        }

        $request->setPaymentProfile($paymentProfile);

        $controller = new AnetController\UpdateCustomerPaymentProfileController($request);

        $response = $controller->executeWithApiResponse($env);

        return $response;

//        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
//        {
//            echo "Update Customer Payment Profile SUCCESS: " . "\n";
//        }
//        else
//        {
//            echo "Update Customer Payment Profile: ERROR Invalid response\n";
//            $errorMessages = $response->getMessages()->getMessage();
//            echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
//        }
    }

    public function getCustomerPaymentProfile(
        string $customerProfileId,
        string $customerPaymentProfileId
    ) {

        // Set the transaction's refId
        $refId = 'ref' . time();

        //request requires customerProfileId and customerPaymentProfileId
        $request = new AnetAPI\GetCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication($this->auth);
        $request->setRefId($refId);
        $request->setCustomerProfileId($customerProfileId);
        $request->setCustomerPaymentProfileId($customerPaymentProfileId);

        $controller = new AnetController\GetCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        if (($response != null)) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                return $response;
            }
        }
        return null;
    }

    /**
     * Create customer profile
     *
     * @param string $description - Profile description
     * @param string $merchantCustomerId - Specific merchant customer ID
     * @param string $email - Customer email address
     *
     * @return self
     */

    public function createCustomerProfile(string $description, string $merchantCustomerId, string $email)
    {
        $customerProfile = new AnetAPI\CustomerProfileType();
        $customerProfile->setDescription($description);
        $customerProfile->setMerchantCustomerId($merchantCustomerId);
        $customerProfile->setEmail($email);
        $customerProfile->setPaymentProfiles($this->paymentProfiles);
        $customerProfile->setShipToList($this->shippingAddresses);
        $this->customerProfile = $customerProfile;

        return $this;
    }

    /**
     * Prepare request. Need add bill and shipping info before
     *
     */

    private function prepareRequest()
    {
        $this->request = new AnetAPI\CreateCustomerProfileRequest();
        $this->request->setMerchantAuthentication($this->auth);
        $this->request->setRefId('ref' . time());
        $this->request->setProfile($this->customerProfile);

        if (!empty($this->validationMode)
            and ($this->validationMode == 'testMode' or $this->validationMode == 'liveMode')) {
            $this->request->setValidationMode($this->validationMode);
        }

    }

    /**
     * Send prepared request
     *
     * @param string $environment - sandbox or production. Default, sandbox
     *
     * @return \net\authorize\api\contract\v1\AnetApiResponseType
     */

    public function sendRequest(string $environment = 'sandbox')
    {
        $this->prepareRequest();
        $controller = new AnetController\CreateCustomerProfileController($this->request);
        $env = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        switch ($environment) {
            case 'sandbox':
                $env = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
                break;
            case 'production':
                $env = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
                break;
        }
        $this->response = $controller->executeWithApiResponse($env);


        return $this->response;
    }

    /**
     *
     * Get customer payment profile IDs
     *
     * @return  array|null  Customer payment profile ID list or null
     */

    public function getCustomerPaymentProfileIds()
    {
        if (($this->response != null) && ($this->response->getMessages()->getResultCode() == "Ok")) {
//            return $this->response->getCustomerPaymentProfileId();
            return $this->response->getCustomerPaymentProfileIdList();
        }
        return null;
    }

    /**
     *
     * Get customer profile ID
     *
     * @return  string|null  Customer profile ID or null
     */

    public function getCustomerProfileId()
    {
        if (($this->response != null) && ($this->response->getMessages()->getResultCode() == "Ok")) {
            return $this->response->getCustomerProfileId();
        }
        return null;
    }

    /**
     *
     *
     */

    public function getValidationResult()
    {
        if (($this->response != null) && ($this->response->getMessages()->getResultCode() == "Ok")) {
            return $this->response->getMessages()->getMessage();
        }
        return null;
    }

    /**
     *
     * @param string $profileId
     * @param string $paymentProfileId
     * @param float $amount
     * @param string $environment Default - sandbox. sandbox or production
     *
     * @return AnetAPI\AnetApiResponseType
     */
    public function pay(string $profileId, string $paymentProfileId, float $amount, string $environment = 'sandbox')
    {
        $refId = 'ref' . time();

        $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
        $profileToCharge->setCustomerProfileId($profileId);
        $paymentProfile = new AnetAPI\PaymentProfileType();
        $paymentProfile->setPaymentProfileId($paymentProfileId);
        $profileToCharge->setPaymentProfile($paymentProfile);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType('authCaptureTransaction');
        $transactionRequestType->setAmount($amount);
        $transactionRequestType->setProfile($profileToCharge);

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->auth);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);
        $controller = new AnetController\CreateTransactionController($request);

        $env = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        switch ($environment) {
            case 'sandbox':
                $env = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
                break;
            case 'production':
                $env = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
                break;
        }

        return $controller->executeWithApiResponse($env);
    }
}