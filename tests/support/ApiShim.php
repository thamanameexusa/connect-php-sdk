<?php

/**
 * Shim for the API classes.
 *
 * Example
 * This code:
 *
 *     $api = new \SquareConnect\TransactionApi();
 *     $transaction = $api->retrieveTransaction(ACCESS_TOKEN, LOCATION_ID, $transactionId);
 *
 * Is equivalent to:
 *
 *     $api = new ApiShim(new \SquareConnect\TransactionApi(), ACCESS_TOKEN, LOCATION_ID);
 *     $transaction = $api->retrieveTransaction($transactionId);
 *
 * The $locationId argument to the constructor is optional; omit it if the API
 * methods being called do not require a location ID.
 */
class ApiShim
{
    /**
     * @param $apiInstance Instance of an API class (e.g.
     * \SquareConnect\TransactionApi)
     * @param string $accessToken API access token
     * @param string $locationId Location ID for merchant/developer
     */
    public function __construct($apiInstance, $accessToken, $locationId = NULL)
    {
        $this->api = $apiInstance;
        $this->accessToken = $accessToken;
        $this->locationId = $locationId;
    }

    /**
     * @param string $name Name of the method being called
     * @param array $arguments Argument array
     */
    public function __call($name, array $arguments)
    {
        if (is_null($this->locationId)) {
            array_unshift($arguments, $this->accessToken);
        } else {
            array_unshift($arguments, $this->accessToken, $this->locationId);
        }
        return call_user_func_array(array($this->api, $name), $arguments);
    }
}

?>
