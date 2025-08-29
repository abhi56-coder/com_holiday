<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();
$input = $app->input;
$user = Factory::getUser();
$canEdit = $user->authorise('core.edit', 'com_holidaypackages');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

// Get category filter from input or state
$categoryFilter = $input->getString('category', $this->state->get('filter.category', ''));
$categoryParam = $categoryFilter ? '&category=' . urlencode($categoryFilter) : '';
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=destinations' . $categoryParam); ?>" method="post" name="adminForm" id="adminForm">
    <div class="container-fluid">
        <div class="row">
            <?php if (!empty($this->sidebar)) : ?>
                <div id="j-sidebar-container" class="col-md-2 col-12 mb-3">
                    <?php echo $this->sidebar; ?>
                </div>
                <div id="j-main-container" class="col-md-10 col-12">
            <?php else : ?>
                <div id="j-main-container" class="col-12">
            <?php endif; ?>

            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

            <?php if (empty($this->items)) : ?>
                <div class="alert alert-info mt-3">
                    <span class="icon-info-circle" aria-hidden="true"></span>
                    <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                </div>
            <?php else : ?>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered" id="destinationList">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'd.id', $listDirn, $listOrder); ?>
                                </th>
                                <th>
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_TITLE_LABEL', 'd.title', $listDirn, $listOrder); ?>
                                </th>
                                <th>
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_IMAGE_LABEL', 'd.image', $listDirn, $listOrder); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'd.published', $listDirn, $listOrder); ?>
                                </th>
                                <th class="text-center">
                                    <?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_ACTION_LABEL'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                    <td>
                                        <?php if ($canEdit) : ?>
                                            <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . $item->id . $categoryParam); ?>">
                                                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $imgPath = !empty($item->image) ? Uri::root() . $item->image : Uri::root() . 'images/noimage.jpg';
                                        ?>
                                        <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 50px; max-height: 50px;" onerror="this.src='<?php echo Uri::root(); ?>images/noimage.jpg';" />
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'destinations.', $canEdit); ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=packages&filter[destination_id]=' . (int) $item->id); ?>" class="btn btn-sm btn-outline-primary">
                                            <?php echo Text::_('COM_HOLIDAYPACKAGES_ACTION_ADD_PACKAGES'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">
                                    <?php echo $this->pagination->getListFooter(); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>" />
            <input type="hidden" name="boxchecked" value="0" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>
