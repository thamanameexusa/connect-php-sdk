<?php
require_once 'tests/common.php';
require_once 'tests/support.php';

class LocationApiTest extends TestCase_ext
{
    public function testThrowOnNilAccessTokens()
    {
        $client = new \SquareConnect\Api\LocationApi($GLOBALS['API_CLIENT']);
        $this->assertThrows(
            function() use ($client) { $client->listLocations(NULL); },
            'InvalidArgumentException');
    }

    public function testThrowOnBlankAccessTokens() {
        $client = new \SquareConnect\Api\LocationApi($GLOBALS['API_CLIENT']);
        $this->assertThrows(
            function() use ($client) { $client->listLocations(''); },
            'SquareConnect\ApiException', NULL, 401);
    }

    // [L01] Lists the "Staging SDK test" location
    public function testListLocations()
    {
        $client = new ApiShim(
            new \SquareConnect\Api\LocationApi($GLOBALS['API_CLIENT']),
            $GLOBALS['SANDBOX_ACCESS_TOKEN']
            );
        $locations = $client->listLocations()->GetLocations();
        $this->assertNotEmpty($locations);
        $this->assertContains(
            $GLOBALS['SANDBOX_LOCATION_ID'],
            array_map(function($loc) { return $loc->GetId(); }, $locations));
    }

}
