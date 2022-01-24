<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel\Directory\Country;

use Magento\Directory\Model\Country;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;

/**
 * @package FlowCommerce\FlowConnector\Model\Directory\Country
 */
class Collection extends CountryCollection
{
    /**
     * @return array
     */
    public function getIso3CodeToNameMapping()
    {
        $mapping = [];
        /** @var Country $item */
        foreach($this->getItems() as $item) {
            $name = (string)$this->_localeLists->getCountryTranslation($item->getData('iso2_code'));
            if (!empty($name)) {
                $mapping[$item->getData('iso3_code')] = $name;
            }
        }

        return $mapping;
    }
}
