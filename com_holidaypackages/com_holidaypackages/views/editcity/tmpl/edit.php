<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$app = Factory::getApplication();
$itemId = $app->input->getInt('id', 0);
$form = $this->form;

if (!$form) {
    $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
    $app->redirect(Route::_('index.php?option=com_holidaypackages&view=cities', false));
    return false;
}

$form->bind($this->item);
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&task=editcity.save&id=' . (int)($this->item->id ?? 0)); ?>" 
      method="post" 
      name="adminForm" 
      id="adminForm" 
      class="form-validate form-horizontal">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <fieldset class="adminform">
                        <?php foreach ($this->form->getFieldset('editcity') as $field) : ?>
                            <div class="control-group">
                                <div class="control-label"><?php echo $field->label; ?></div>
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                    <?php if ($field->description) : ?>
                                        <small class="form-text text-muted"><?php echo Text::_($field->description); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="jform[id]" value="<?php echo (int)($this->item->id ?? 0); ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>