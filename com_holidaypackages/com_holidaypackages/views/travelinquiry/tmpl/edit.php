<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Get category from view
$category = $this->category ?? '';
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&task=travelinquiry.save&id=' . (int) $this->item->id . ($category ? '&category=' . urlencode($category) : '')); ?>" 
      method="post" 
      name="adminForm" 
      id="adminForm" 
      class="form-validate">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderFieldset('details'); ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="jform[id]" value="<?php echo (int) $this->item->id; ?>" />

    <?php echo HTMLHelper::_('form.token'); ?>
</form>