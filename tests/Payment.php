<?php

use AnetWrapper\Wrapper;
use PHPUnit\Framework\TestCase;

final class Payment extends TestCase
{
    public function testCanBeCreatePayment()
    {
        $wrapper = new Wrapper('65QSkk2E2', '3rF6r84m98nJR67Z');
        $wrapper->addCreditCard(4242424242424242, "2038-12", "142");
        $wrapper->addBillInfo([
            'firstName' => 'Ellen',
            'lastName' => 'Johnson',
            'company' => 'Souveniropolis',
            'address' => '14 Main Street',
            'city' => 'Pecan Springs',
            'state' => 'TX',
            'zip' => '44628',
            'country' => 'USA',
            'phone' => '888-888-8888',
            'fax' => '999-999-9999',
        ]);
        $wrapper->addShippingAddress([
            'firstName' => 'James',
            'lastName' => 'White',
            'company' => 'Addresses R Us',
            'address' => 'North Spring Street',
            'city' => 'Toms River',
            'state' => 'NJ',
            'zip' => '08753',
            'country' => 'USA',
            'phone' => '888-888-8888',
            'fax' => '999-999-9999',
        ]);
        $wrapper->createPaymentProfile();
        $wrapper->createCustomerProfile('Customer 2 Test PHP', 'M' . time(), 'test@onnet.work');
        $response = $wrapper->sendRequest();

        $this->assertEquals('Ok',
            $response->getMessages()->getResultCode()
        );


        $profileId = $wrapper->getCustomerProfileId();
        $paymentProfileIDs = $wrapper->getCustomerPaymentProfileIds();
        $paymentProfileID = array_shift($paymentProfileIDs);

        $payResponse = $wrapper->pay($profileId, $paymentProfileID, 12);

        $this->assertEquals('Ok',
            $payResponse->getMessages()->getResultCode()
        );

        $tresponse = $payResponse->getTransactionResponse();

        $this->assertNotNull($tresponse);

        $this->assertNotNull($tresponse->getMessages());

    }

}