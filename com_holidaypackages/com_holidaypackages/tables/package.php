<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTablePackage extends Table {
    public function __construct(&$db) {
        parent::__construct('n4gvg__holiday_packages', 'id', $db);
    }

    public function bind($array, $ignore = '') {
        // Handle JSON fields
        if (isset($array['package_details']) && is_array($array['package_details'])) {
            $array['package_details'] = json_encode($array['package_details']);
        }

        // Handle JSON for special_package
        if (isset($array['special_package']) && is_array($array['special_package'])) {
            $array['special_package'] = json_encode($array['special_package']);
        }

        return parent::bind($array, $ignore);
    }

    public function store($updateNulls = false) {
        return parent::store($updateNulls);
    }
    
    
}

