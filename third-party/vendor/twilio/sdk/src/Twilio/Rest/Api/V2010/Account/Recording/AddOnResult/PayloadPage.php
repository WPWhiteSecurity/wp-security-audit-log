<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */
namespace WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Recording\AddOnResult;

use WSAL_Vendor\Twilio\Http\Response;
use WSAL_Vendor\Twilio\Page;
use WSAL_Vendor\Twilio\Version;
class PayloadPage extends \WSAL_Vendor\Twilio\Page
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
     * @return PayloadInstance \Twilio\Rest\Api\V2010\Account\Recording\AddOnResult\PayloadInstance
     */
    public function buildInstance(array $payload) : \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Recording\AddOnResult\PayloadInstance
    {
        return new \WSAL_Vendor\Twilio\Rest\Api\V2010\Account\Recording\AddOnResult\PayloadInstance($this->version, $payload, $this->solution['accountSid'], $this->solution['referenceSid'], $this->solution['addOnResultSid']);
    }
    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() : string
    {
        return '[Twilio.Api.V2010.PayloadPage]';
    }
}
