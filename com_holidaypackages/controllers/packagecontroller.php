<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class HolidaypackagesControllerPackage extends BaseController
{
    public function display($cachable = false, $urlparams = false)
    {
        $view = $this->input->get('view', 'details');
        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        // Set the view and layout
        $this->input->set('view', $view);
        $this->input->set('layout', $layout);

        parent::display($cachable, $urlparams);
        return $this;
    }
}