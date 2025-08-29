<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

$app = Factory::getApplication();
$input = $app->input;
$packageId = $input->getInt('package_id', 0);
$destinationId = $input->getInt('destination_id', 0);

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

if ($packageId == 0 && !$destinationId) {
    $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
    $app->redirect(Route::_('index.php?option=com_holidaypackages&view=packages', false));
    return false;
}

?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&task=package.save&id=' . (int) $this->item->id . ($destinationId ? '&destination_id=' . $destinationId : '')); ?>" 
      method="post" 
      name="adminForm" 
      id="adminForm" 
      class="form-validate">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderFieldset('general'); ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="destination_id" value="<?php echo (int) $destinationId; ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
