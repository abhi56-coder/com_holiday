<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class HolidaypackagesTableTravelinquiries extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('n4gvg__travel_inquiries', 'id', $db);
    }
}
