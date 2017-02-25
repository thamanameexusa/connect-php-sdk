<?php
require_once 'tests/common.php';
require_once 'tests/support.php';

class CustomerApiTest extends TestCase_ext
{
    protected static $transactionApi;
    protected static $customerApi;
    protected static $cardApi;

    /**
     * @beforeClass
     */
    public static function setUpApis()
    {
        $API_CLIENT = $GLOBALS['API_CLIENT'];

        self::$transactionApi = new \SquareConnect\Api\TransactionApi($API_CLIENT);
        self::$customerApi = new \ApiShim(
            new \SquareConnect\Api\CustomerApi($API_CLIENT),
            $GLOBALS['SANDBOX_ACCESS_TOKEN']);
        self::$cardApi = new \ApiShim(
            new \SquareConnect\Api\CustomerCardApi($API_CLIENT),
            $GLOBALS['SANDBOX_ACCESS_TOKEN']);
    }

    /* [C01] CRUD customers, links cards, and charges */
    // Before and After really apply only to this test, but I'm too lazy to
    // create a separate class for test C02, so they'll also run for that test
    // as well.

    /**
     * @before
     */
    public function createCustomers()
    {
        $this->asgore = self::$customerApi->createCustomer(array(
            'given_name' => 'Asgore',
            'family_name' => 'Dreemur',
            'reference_id' => 'php-1234abcd',
            ))->getCustomer();
        $this->toriel = self::$customerApi->createCustomer(array(
            'given_name' => 'Toriel',
            'family_name' => 'Dreemur',
            'reference_id' => 'php-abcd1234',
            ))->getCustomer();
    }

    /**
     * @after
     */
    public function deleteCustomers()
    {
        self::$customerApi->deleteCustomer($this->asgore->getId());
        self::$customerApi->deleteCustomer($this->toriel->getId());

    }

    public function testCustomerApi()
    {
        $cardApi = self::$cardApi;
        $asgore = $this->asgore;
        $toriel = $this->toriel;

        $asgoreCardId = null;
        $torielCardId = null;

        $this->assertEventually($this->doesNotThrow(), function() use ($cardApi, $asgore, &$asgoreCardId) {
             //learn more about testing with sandbox here: https://docs.connect.squareup.com/articles/using-sandbox
            $cardNonce = 'fake-card-nonce-ok';
            $asgoreCardId = $cardApi->createCustomerCard(
                $asgore->getId(),
                array(
                    'card_nonce' => $cardNonce,
                    'cardholder_name' => 'ASGORE DREEMUR',
                    'billing_address' => array('postal_code' => '94103'),
                    )
                )->getCard()->getId();
        });
        $this->assertEventually($this->doesNotThrow(), function() use ($cardApi, $toriel, &$torielCardId) {
             //learn more about testing with sandbox here: https://docs.connect.squareup.com/articles/using-sandbox
            $cardNonce = 'fake-card-nonce-ok'; 
            $torielCardId = $cardApi->createCustomerCard(
                $toriel->getId(),
                array(
                    'card_nonce' => $cardNonce,
                    'cardholder_name' => 'TORIEL DREEMUR',
                    'billing_address' => array('postal_code' => '94103'),
                    )
                )->getCard()->getId();
        });

        self::$customerApi->updateCustomer($this->toriel->getId(), array(
            'phone_number' => '(999) 999-9999',
            'family_name' => '',
            ));

        $retrievedAsgore = self::$customerApi->retrieveCustomer($this->asgore->getId())->getCustomer();
        $this->assertEquals('Asgore', $retrievedAsgore->getGivenName());
        $this->assertEquals('Dreemur', $retrievedAsgore->getFamilyName());
        $this->assertCount(1, $retrievedAsgore->getCards());
        $cards = $retrievedAsgore->getCards();
        $this->assertEquals($asgoreCardId, $cards[0]->getId());
        $retrievedToriel = self::$customerApi->retrieveCustomer($this->toriel->getId())->getCustomer();
        $this->assertEquals('Toriel', $retrievedToriel->getGivenName());
        $this->assertNull($retrievedToriel->getFamilyName());
        $this->assertCount(1, $retrievedToriel->getCards());
        $cards = $retrievedToriel->getCards();
        $this->assertEquals($torielCardId, $cards[0]->getId());

        self::$transactionApi->charge($GLOBALS['SANDBOX_ACCESS_TOKEN'],$GLOBALS['SANDBOX_LOCATION_ID'],array(
            'amount_money' => array('amount' => 1337, 'currency' => 'USD'),
            'idempotency_key' => uniqid('',true),
            'customer_id' => $this->asgore->getId(),
            'customer_card_id' => $asgoreCardId,
            ));
        self::$transactionApi->charge($GLOBALS['SANDBOX_ACCESS_TOKEN'],$GLOBALS['SANDBOX_LOCATION_ID'],array(
            'amount_money' => array('amount' => 2048, 'currency' => 'USD'),
            'idempotency_key' => uniqid('',true),
            'customer_id' => $this->toriel->getId(),
            'customer_card_id' => $torielCardId,
            ));

        self::$cardApi->deleteCustomerCard($this->asgore->getId(), $asgoreCardId);
        self::$cardApi->deleteCustomerCard($this->toriel->getId(), $torielCardId);
    }

    /* [C02] lists customers with pagination */
    public function testCustomerPagination()
    {
        $customerApi = self::$customerApi;
        $this->assertDoesNotThrow(function() use ($customerApi) {
            $response = $customerApi->listCustomers();
            $customerApi->listCustomers(array('cursor' => $response->getCursor()));
        });
    }
}

?>
