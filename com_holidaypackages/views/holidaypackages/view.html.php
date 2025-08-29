<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;

class HolidaypackagesViewHolidaypackages extends HtmlView {
    protected $items;

    public function display($tpl = null) {
        $this->items = $this->get('Items');
        parent::display($tpl);
    }
}
?>