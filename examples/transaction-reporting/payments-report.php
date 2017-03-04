<?php

# Demonstrates generating a 2015 payments report with the Square Connect API.
# Replace the value of the `$accessToken` variable below before running this script.
#
# This sample assumes all monetary amounts are in US dollars. You can alter the
# formatMoney function to display amounts in other currency formats.
#
# This sample requires the Unirest PHP library. See README.md in this directory for
# installation instructions.
#
# Results are rendered in a simple HTML pre block.


require 'vendor/autoload.php';

# Replace this value with your application's personal access token,
# available from your application dashboard (https://connect.squareup.com/apps)
$accessToken = 'sq0atp-p65JKVeVpIkWjxvxC05LnQ';

# Standard HTTP headers for every Connect API request
$requestHeaders = array (
  'Authorization' => 'Bearer ' . $accessToken,
  'Accept' => 'application/json',
  'Content-Type' => 'application/json'
  );

# Helper function to convert cent-based money amounts to dollars and cents
function formatMoney($money) {
  return money_format('%+.2n', $money / 100);
}

# Obtains all of the business's location IDs. Each location has its own collection of payments.
function getLocationIds() {
  $api_instance = new SquareConnect\Api\LocationApi();
  global $accessToken;
  $locations = $api_instance->listLocations($accessToken);
  $locationIds = array();

  foreach ($locations as $location) {
    $locationIds[] = $location->getId();
  }

  return $locationIds;
}

# Retrieves all of a merchant's payments from 2015
function get2017Payments($location_ids) {
  global $accessToken;

  # Restrict the request to the 2015 calendar year, eight hours behind UTC
  # Make sure to URL-encode all parameters
  $parameters = http_build_query(
  	array(
     'begin_time' => '2017-01-01T00:00:00-08:00',
     'end_time'   => '2018-01-01T00:00:00-08:00'
     )
    );

  $payments = array();

  foreach ($location_ids as $location_id) {

    $transactionApi = new SquareConnect\Api\TransactionApi();

    $payments = $transactionApi->listTransactions($accessToken, $location_id, $begin_time, $end_time, null,null)->getTransactions();

    $cursor = $transactionApi->getCursor();

    while ($cursor) {

      # Send a GET request to the List Payments endpoint
     $morePayments = $transactionApi->listTransactions($accessToken, $location_id, $begin_time, $end_time, null,$cursor);

      # Read the converted JSON body into the cumulative array of results
     $payments = array_merge($payments, $morePayments->getTransactions());

     $cursor = $morePayments->getCursor();
  }
}

  # Remove potential duplicate values from the list of payments
$seenPaymentIds = array();
$uniquePayments = array();
foreach ($payments as $payment) {
 if (array_key_exists($payment->id, $seenPaymentIds)) {
   continue;
 }
 $seenPaymentIds[$payment->id] = true;
 array_push($uniquePayments, $payment);
}

return $uniquePayments;
}

# Prints a sales report based on an array of payments
function printSalesReport($payments) {

  # Variables for holding cumulative values of various monetary amounts
  $collectedMoney = $taxes = $tips = $discounts = $processingFees = 0;
  $returned_processingFees = $netMoney = $refunds = 0;

  # Add appropriate values to each cumulative variable
  foreach ($payments as $payment) {
    $collectedMoney  = $collectedMoney  + $payment->total_collected_money->amount;
    $taxes           = $taxes           + $payment->tax_money->amount;
    $tips            = $tips            + $payment->tip_money->amount;
    $discounts       = $discounts       + $payment->discount_money->amount;
    $processingFees  = $processingFees  + $payment->processing_fee_money->amount;
    $netMoney        = $netMoney        + $payment->net_total_money->amount;
    $refunds         = $refunds         + $payment->refunded_money->amount;


    # When a refund is applied to a credit card payment, Square returns to the merchant a percentage
    # of the processing fee corresponding to the refunded portion of the payment. This amount
    # is not currently returned by the Connect API, but we can calculate it as shown:

    # If a processing fee was applied to the payment AND some portion of the payment was refunded...
    if ($payment->processing_fee_money->amount < 0 and $payment->refunded_money->amount < 0) {

      # ...calculate the percentage of the payment that was refunded...
      $percentage_refunded = $payment->refunded_money->amount / (float)$payment->total_collected_money->amount;

      # ...and multiply that percentage by the original processing fee
      $returned_processingFees = $returned_processingFees + ($payment->processing_fee_money->amount * $percentage_refunded);
    }
  }

  # Calculate the amount of pre-tax, pre-tip money collected
  $basePurchases = $collectedMoney - $taxes - $tips;


  # Print a sales report similar to the Sales Summary in the merchant dashboard.
  print '==SALES REPORT FOR 2015==';
  print 'Gross Sales:       ' . formatMoney($basePurchases - $discounts) . '\n';
  print 'Discounts:         ' . formatMoney($discounts);
  print 'Net Sales:         ' . formatMoney($basePurchases);
  print 'Tax collected:     ' . formatMoney($taxes);
  print 'Tips collected:    ' . formatMoney($tips);
  print 'Total collected:   ' . formatMoney($basePurchases + $taxes + $tips);
  print 'Fees:              ' . formatMoney($processingFees);
  print 'Refunds:           ' . formatMoney($refunds);
  print 'Fees returned:     ' . formatMoney($returned_processingFees);
  print 'Net total:         ' . formatMoney($netMoney + $refunds + $returned_processingFees);

}

# Call the functions defined above
$payments = get2017Payments(getLocationIds());
printSalesReport($payments);

?>
