<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\HTML\HTMLHelper;

class HolidaypackagesViewDetails extends HtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        // Load Bootstrap CSS and JavaScript
        HTMLHelper::_('bootstrap.loadCss');
        HTMLHelper::_('bootstrap.framework');

        // Load custom CSS
        HTMLHelper::stylesheet('com_holidaypackages/details.css', ['relative' => true, 'pathOnly' => false]);

        // Get data from the model
        $this->item = $this->get('Item');

        // Render the layout
        parent::display($tpl);
    }
}