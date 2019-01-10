<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface SessionManagementInterface
 * @package FlowCommerce\FlowConnector\Api
 */
interface SessionManagementInterface
{
    /**
     * Retrieve data for Flow session in progress
     *
     * @return array|null
     */
    public function getFlowSessionData();

    /**
     * Start new Flow session
     *
     * @param $country
     */
    public function startFlowSession($country);
  
    /**
     * Get session experience country
     *
     * @return string|null $country
     */
    public function getSessionExperienceCountry();
}