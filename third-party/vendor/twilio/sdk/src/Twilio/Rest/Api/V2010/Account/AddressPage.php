<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account;

use WSAL_Vendor\Twilio\Http\Response;
use WSAL_Vendor\Twilio\Page;
use WSAL_Vendor\Twilio\Version;
class AddressPage extends \WSAL_Vendor\Twilio\Page
{
    /**
     * @param Version $version Version that contains the resource
     * @param Response $response Response from the API
     * @param array $solution The context solution
     */
    public function __construct(\WSAL_Vendor\Twilio\Version $version, \WSAL_Vendor\Twilio\Http\Response $response, array $solution)
    {
        parent::__construct($version, $response);
        // Path Solution
        $this->solution = $solution;
    }
    /**
     * @param array $payload Payload response from the API
     * @return AddressInstance \Twilio\Rest\Api\V2010\Account\AddressInstance
     */
    public function buildInstance(array $payload) : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AddressInstance
    {
        return new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\AddressInstance($this->version, $payload, $this->solution['accountSid']);
    }
    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() : string
    {
        return '[Twilio.Api.V2010.AddressPage]';
    }
}
