<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class HolidaypackagesController extends BaseController {
    public function display($cachable = false, $urlparams = false) {
        // Get the requested view, default to 'destinations'
        $view = $this->input->get('view', 'destinations', 'cmd');
        $this->input->set('view', $view);

        // Verify the view exists
        $viewObject = $this->getView($view, 'Html', 'HolidaypackagesView');
        if (!$viewObject) {
            throw new Exception('View "' . $view . '" not found.', 404);
        }

        parent::display($cachable, $urlparams);
        return $this;
    }
}