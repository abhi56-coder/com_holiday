<?php
/**
 * @package     Holiday Packages
 * @subpackage  com_holidaypackages.site
 * @version     2.0.0
 * @author      Holiday Packages Team
 * @copyright   Copyright (C) 2024 Holiday Packages. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

/**
 * Holiday Packages Component Controller
 *
 * @since  2.0.0
 */
class HolidaypackagesController extends BaseController
{
    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  2.0.0
     */
    protected $default_view = 'packages';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached.
     * @param   mixed    $urlparams  An array of safe URL parameters and their variable types.
     *
     * @return  BaseController|boolean  This object to support chaining or false on failure.
     *
     * @since   2.0.0
     */
    public function display($cachable = false, $urlparams = false)
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        
        // Get the view name
        $vName = $input->get('view', $this->default_view, 'cmd');
        
        // Set safe URL parameters for caching
        $urlparams = array(
            'id'            => 'INT',
            'catid'         => 'INT',
            'destination'   => 'INT',
            'package'       => 'INT',
            'booking'       => 'INT',
            'layout'        => 'CMD',
            'filter'        => 'STRING',
            'search'        => 'STRING',
            'page'          => 'INT',
            'sort'          => 'CMD',
            'order'         => 'CMD',
            'lang'          => 'CMD',
            'Itemid'        => 'INT'
        );

        // Check for user authentication for certain views
        $authViews = array('booking', 'bookings', 'profile', 'wishlist');
        
        if (in_array($vName, $authViews) && !Factory::getUser()->id) {
            $return = base64_encode(Route::_('index.php?option=com_holidaypackages&view=' . $vName));
            $login_url = Route::_('index.php?option=com_users&view=login&return=' . $return);
            
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_LOGIN_REQUIRED'), 'warning');
            $app->redirect($login_url);
            
            return false;
        }

        // Cache settings
        $cachable = true;
        
        // Don't cache user-specific or form views
        $noCacheViews = array('booking', 'bookings', 'profile', 'search');
        
        if (in_array($vName, $noCacheViews) || Factory::getUser()->id) {
            $cachable = false;
        }

        // Call parent display
        return parent::display($cachable, $urlparams);
    }

    /**
     * Method to handle AJAX requests
     *
     * @return  void
     * @since   2.0.0
     */
    public function ajax()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        
        // Set JSON response header
        $app->mimeType = 'application/json';
        $app->setHeader('Content-Type', $app->mimeType . '; charset=' . $app->charSet);
        
        $task = $input->get('task', '', 'cmd');
        $response = array('success' => false, 'message' => '', 'data' => null);
        
        try {
            switch ($task) {
                case 'getPackageDetails':
                    $response = $this->getPackageDetails();
                    break;
                    
                case 'addToWishlist':
                    $response = $this->addToWishlist();
                    break;
                    
                case 'removeFromWishlist':
                    $response = $this->removeFromWishlist();
                    break;
                    
                case 'getAvailableDates':
                    $response = $this->getAvailableDates();
                    break;
                    
                case 'calculatePrice':
                    $response = $this->calculatePrice();
                    break;
                    
                case 'checkAvailability':
                    $response = $this->checkAvailability();
                    break;
                    
                default:
                    $response['message'] = Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_TASK');
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        $app->close();
    }

    /**
     * Get package details via AJAX
     *
     * @return  array
     * @since   2.0.0
     */
    private function getPackageDetails()
    {
        $input = Factory::getApplication()->getInput();
        $packageId = $input->getInt('package_id', 0);
        
        if (!$packageId) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_PACKAGE'));
        }
        
        $model = $this->getModel('Package');
        $package = $model->getItem($packageId);
        
        if (!$package) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_NOT_FOUND'));
        }
        
        return array(
            'success' => true,
            'data' => $package,
            'message' => Text::_('COM_HOLIDAYPACKAGES_SUCCESS_PACKAGE_LOADED')
        );
    }

    /**
     * Add package to wishlist
     *
     * @return  array
     * @since   2.0.0
     */
    private function addToWishlist()
    {
        $user = Factory::getUser();
        
        if (!$user->id) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_LOGIN_REQUIRED'));
        }
        
        $input = Factory::getApplication()->getInput();
        $packageId = $input->getInt('package_id', 0);
        
        if (!$packageId) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_PACKAGE'));
        }
        
        $model = $this->getModel('Packages');
        
        if ($model->addToWishlist($user->id, $packageId)) {
            return array(
                'success' => true,
                'message' => Text::_('COM_HOLIDAYPACKAGES_SUCCESS_ADDED_TO_WISHLIST')
            );
        } else {
            return array(
                'success' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_ADD_TO_WISHLIST')
            );
        }
    }

    /**
     * Remove package from wishlist
     *
     * @return  array
     * @since   2.0.0
     */
    private function removeFromWishlist()
    {
        $user = Factory::getUser();
        
        if (!$user->id) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_LOGIN_REQUIRED'));
        }
        
        $input = Factory::getApplication()->getInput();
        $packageId = $input->getInt('package_id', 0);
        
        if (!$packageId) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_PACKAGE'));
        }
        
        $model = $this->getModel('Packages');
        
        if ($model->removeFromWishlist($user->id, $packageId)) {
            return array(
                'success' => true,
                'message' => Text::_('COM_HOLIDAYPACKAGES_SUCCESS_REMOVED_FROM_WISHLIST')
            );
        } else {
            return array(
                'success' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_REMOVE_FROM_WISHLIST')
            );
        }
    }

    /**
     * Get available dates for a package
     *
     * @return  array
     * @since   2.0.0
     */
    private function getAvailableDates()
    {
        $input = Factory::getApplication()->getInput();
        $packageId = $input->getInt('package_id', 0);
        
        if (!$packageId) {
            return array('success' => false, 'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_PACKAGE'));
        }
        
        $model = $this->getModel('Package');
        $dates = $model->getAvailableDates($packageId);
        
        return array(
            'success' => true,
            'data' => $dates,
            'message' => Text::_('COM_HOLIDAYPACKAGES_SUCCESS_DATES_LOADED')
        );
    }

    /**
     * Calculate package price based on travelers and dates
     *
     * @return  array
     * @since   2.0.0
     */
    private function calculatePrice()
    {
        $input = Factory::getApplication()->getInput();
        
        $data = array(
            'package_id' => $input->getInt('package_id', 0),
            'adults' => $input->getInt('adults', 1),
            'children' => $input->getInt('children', 0),
            'infants' => $input->getInt('infants', 0),
            'seniors' => $input->getInt('seniors', 0),
            'departure_date' => $input->get('departure_date', '', 'string'),
            'promo_code' => $input->get('promo_code', '', 'string')
        );
        
        $model = $this->getModel('Package');
        $pricing = $model->calculatePrice($data);
        
        if ($pricing) {
            return array(
                'success' => true,
                'data' => $pricing,
                'message' => Text::_('COM_HOLIDAYPACKAGES_SUCCESS_PRICE_CALCULATED')
            );
        } else {
            return array(
                'success' => false,
                'message' => Text::_('COM_HOLIDAYPACKAGES_ERROR_PRICE_CALCULATION')
            );
        }
    }

    /**
     * Check availability for booking
     *
     * @return  array
     * @since   2.0.0
     */
    private function checkAvailability()
    {
        $input = Factory::getApplication()->getInput();
        
        $data = array(
            'package_id' => $input->getInt('package_id', 0),
            'departure_date' => $input->get('departure_date', '', 'string'),
            'travelers' => $input->getInt('travelers', 1)
        );
        
        $model = $this->getModel('Package');
        $available = $model->checkAvailability($data);
        
        return array(
            'success' => true,
            'data' => array('available' => $available),
            'message' => $available ? 
                Text::_('COM_HOLIDAYPACKAGES_SUCCESS_AVAILABLE') : 
                Text::_('COM_HOLIDAYPACKAGES_ERROR_NOT_AVAILABLE')
        );
    }
}