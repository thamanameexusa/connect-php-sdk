<?php
require_once 'tests/common.php';
require_once 'tests/support.php';

class TransactionApiTest extends TestCase_ext
{
    protected static $API_CLIENT;
    protected static $transactionApi;
    protected static $refundApi;

    /**
     * @beforeClass
     */
    public static function setUpApis()
    {
        $API_CLIENT = $GLOBALS['API_CLIENT'];
        self::$API_CLIENT = $API_CLIENT;

        self::$transactionApi = new \ApiShim(
            new \SquareConnect\Api\TransactionApi($API_CLIENT),
            $GLOBALS['SANDBOX_ACCESS_TOKEN'], $GLOBALS['SANDBOX_LOCATION_ID']);
        self::$refundApi = new \ApiShim(
            new \SquareConnect\Api\RefundApi($API_CLIENT),
            $GLOBALS['SANDBOX_ACCESS_TOKEN'], $GLOBALS['SANDBOX_LOCATION_ID']);
    }

    public function testCheckInputs_RejectNullAccessToken()
    {
        $client = new \SquareConnect\Api\TransactionApi(self::$API_CLIENT);
        $this->assertThrows(function() use ($client) {
            $client->listTransactions(NULL, $GLOBALS['SANDBOX_LOCATION_ID']);
        }, 'InvalidArgumentException');
    }

    public function testCheckInputs_RejectNullLocationId()
    {
        $client = new \SquareConnect\Api\TransactionApi(self::$API_CLIENT);
        $this->assertThrows(function() use ($client) {
            $client->listTransactions($GLOBALS['SANDBOX_ACCESS_TOKEN'], NULL);
        }, 'InvalidArgumentException');
    }

    public function testCheckInputs_RejectBlankLocationId()
    {
        $client = new \SquareConnect\Api\TransactionApi(self::$API_CLIENT);
        $this->assertThrows(function() use ($client) {
            // Amusingly, sends a requet to /v2/locations//transactions, which
            // causes a 301 redirect to /v2/locations/transactions
            $client->listTransactions($GLOBALS['SANDBOX_ACCESS_TOKEN'], '');
        }, 'SquareConnect\ApiException');
    }

    /* [TR01] Take a payment and refund it */
    // All of these tests are run in order of definition

    public function testTransactionAndRefund_Idempotency()
    {
        //learn more about testing with sandbox here: https://docs.connect.squareup.com/articles/using-sandbox
        $cardNonce = 'fake-card-nonce-ok';
        $idempotencyKey =  uniqid('',true);

        // Try the same transaction three times and check that only one was created
        $transactionApi = self::$transactionApi;
        $retriedTransactions = array_unique(array_map(
            function ($_) use ($transactionApi, $idempotencyKey, $cardNonce) {
                return $transactionApi->charge(array(
                    'amount_money' => array('amount' => 633, 'currency' => 'USD'),
                    'idempotency_key' => $idempotencyKey,
                    'card_nonce' => $cardNonce,
                ))->getTransaction();
            }, range(0, 2)));

        $this->assertCount(1, array_unique(
            array_map(function($t) { return $t->getId(); }, $retriedTransactions)));
        return $retriedTransactions[0];
    }

    /**
     * @depends testTransactionAndRefund_Idempotency
     */
    public function testTransactionAndRefund_getTransaction(\SquareConnect\Model\Transaction $transaction)
    {
        $transactionApi = self::$transactionApi;
        $this->assertEventually($this->contains($transaction->getId()),
            function() use ($transactionApi) {
                return array_map(function($t) { return $t->getId(); },
                    $transactionApi->listTransactions()->getTransactions());
            });

        $retrievedTransaction =
            self::$transactionApi->retrieveTransaction($transaction->getId())->getTransaction();
        $this->assertEquals($transaction->getId(), $retrievedTransaction->getId());
        $tenders = $transaction->getTenders();
        $retrievedTenders = $retrievedTransaction->getTenders();
        $this->assertEquals($tenders[0]->getAmountMoney(),
            $retrievedTenders[0]->getAmountMoney());

        return $transaction;
    }

    /**
     * @depends testTransactionAndRefund_getTransaction
     */
    public function testTransactionAndRefund_RefundTooMuch(\SquareConnect\Model\Transaction $transaction)
    {
        $this->markTestIncomplete('[XP-1143] Will be implemented after the switch to Omnibus');

        // TODO: this test is skipped. PHPUnit doesn't have an equivalent to
        // RSpec's `pending`
        $refundApi = self::$refundApi;
        $tenders = $transaction->getTenders();
        $this->assertEventually(
            $this->throws('\SquareConnect\ApiException', NULL, 'REFUND_AMOUNT_INVALID'),
            function() use ($transaction, $refundApi, $tenders) {
                $refundApi->createRefund($transaction->getId(), array(
                    'amount_money' => array('amount' => 634, 'currency' => 'USD'),
                    'reason' => 'testing',
                    'idempotency_key' =>  uniqid('',true),
                    'tender_id' => $tenders[0]->getId(),
                ));
            });
    }

    /**
     * TODO: make this depend on RefundTooMuch once it's not incomplete anymore
     * @depends testTransactionAndRefund_getTransaction
     */
    public function testTransactionAndRefund_PartialRefunds(\SquareConnect\Model\Transaction $transaction)
    {
        $refundApi = self::$refundApi;
        $tenders = $transaction->getTenders();
        foreach (array(211, 422) as $amount) {
            $idempotencyKey =  uniqid('',true);

            for ($i = 0; $i < 2; $i++) {
                $this->assertEventually($this->doesNotThrow(),
                    function() use ($transaction, $idempotencyKey, $amount, $refundApi, $tenders) {
                        $refundApi->createRefund($transaction->getId(), array(
                            'amount_money' => array('amount' => $amount, 'currency' => 'USD'),
                            'reason' => 'testing',
                            'idempotency_key' => $idempotencyKey,
                            'tender_id' => $tenders[0]->getId(),
                        ));
                    });
            }
        }

        return $transaction;
    }

    /**
     * @depends testTransactionAndRefund_PartialRefunds
     */
    public function testTransactionAndRefund_ListRefunds(\SquareConnect\Model\Transaction $transaction)
    {
        $this->markTestIncomplete('[XP-1143] will pass once we switch to Omnibus');

        $refunds = NULL;
        $refundApi = self::$refundApi;
        $this->assertEventually($this->equalTo(2), function() use ($refundApi, $transaction, &$refunds) {
            $refunds = array_filter($refundApi->listRefunds()->getRefunds(),
                function ($r) use ($refundApi, $transaction) {
                    return $r->getTransactionId() === $transaction->getId();
                });
            return count($refunds);
        });
    }

    /**
     * @depends testTransactionAndRefund_PartialRefunds
     */
    public function testTransactionAndRefund_TransactionWithRefunds(\SquareConnect\Model\Transaction $transaction)
    {
        $this->markTestIncomplete('[XP-1143] will pass once we switch to Omnibus');
        $this->assertCount(2,
            self::$transactionApi->retrieveTransaction($transaction->getId())
                ->getTransaction()->getRefunds());
    }


    /* [TR02] take a delayed capture transaction and void it */
    public function testDelayedCaptureAndVoid()
    {
        //learn more about testing with sandbox here: https://docs.connect.squareup.com/articles/using-sandbox
        $cardNonce = 'fake-card-nonce-ok';
        $transaction = self::$transactionApi->charge(array(
            'amount_money' => array('amount' => 1001, 'currency' => 'USD'),
            'idempotency_key' =>  uniqid('',true),
            'card_nonce' => $cardNonce,
            'delay_capture' => true,
        ))->getTransaction();

        self::$transactionApi->voidTransaction($transaction->getId());

        $transactionApi = self::$transactionApi;
        $this->assertEventually($this->doesNotThrow(), function() use ($transaction, $transactionApi) {
            $transactionApi->retrieveTransaction($transaction->getId())->getTransaction();
        });
        $this->assertEventually($this->equalTo('VOIDED'),
            function() use ($transactionApi, $transaction) {
                $tenders = $transactionApi->retrieveTransaction($transaction->getId())
                    ->getTransaction()
                    ->getTenders();
                return $tenders[0]->getCardDetails()->getStatus();
            });
    }

    /* [TR03] take a delayed capture transaction and capture it */
    public function testDelayedCaptureAndCapture()
    {
        //learn more about testing with sandbox here: https://docs.connect.squareup.com/articles/using-sandbox
        $cardNonce = 'fake-card-nonce-ok';
        $transaction = self::$transactionApi->charge(array(
            'amount_money' => array('amount' => 1001, 'currency' => 'USD'),
            'idempotency_key' =>  uniqid('',true),
            'card_nonce' => $cardNonce,
            'delay_capture' => true,
        ))->getTransaction();

        self::$transactionApi->captureTransaction($transaction->getId());

        $transactionApi = self::$transactionApi;
        $this->assertEventually($this->doesNotThrow(), function() use ($transaction, $transactionApi) {
            $transactionApi->retrieveTransaction($transaction->getId())->getTransaction();
        });
        $this->assertEventually($this->equalTo('CAPTURED'),
            function() use ($transactionApi, $transaction) {
                $tenders = $transactionApi->retrieveTransaction($transaction->getId())
                    ->getTransaction()
                    ->getTenders();
                return $tenders[0]->getCardDetails()->getStatus();
            });
    }

    /* [TR04] pagination for transactions and refunds */
    public function testTransactionAndRefundPagination()
    {
        $response = self::$transactionApi->listTransactions();

        $this->markTestSkipped(' Not enough transactions have been made on this acount yet');
        $this->assertNotEmpty($response->getTransactions());
        $this->assertNotEmpty($response->getCursor());
        $transactionIds = array_map(function($t) { return $t->getId(); },
            $response->getTransactions());

        // I don't know why, but the SDK decided to put all the params into the
        // method signature
        $response = self::$transactionApi->listTransactions(NULL, NULL, NULL,
            $response->getCursor());
        $this->assertNotEmpty($response->getTransactions());

        $nextTransactionIds = array_map(function($t) { return $t->getId(); },
            $response->getTransactions());
        foreach ($transactionIds as $id) {
            $this->assertNotContains($id, $nextTransactionIds);
        }
    }
}
?>
