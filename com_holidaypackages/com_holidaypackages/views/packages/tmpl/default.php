<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Factory;

$listOrder     = $this->state->get('list.ordering');
$listDirn      = $this->state->get('list.direction');
$input         = Factory::getApplication()->getInput();
$destinationId = $input->getInt('destination_id', $this->state->get('filter.destination_id', 0));
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=packages&destination_id=' . (int) $destinationId); ?>" method="post" name="adminForm" id="adminForm">
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

            <!-- Joomla Search Tools -->
            <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="packageList">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'p.id', $listDirn, $listOrder); ?></th>
                            <th class="text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></th>

                            <?php if (!$destinationId) : ?>
                                <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_DESTINATION_LABEL', 'destination_title', $listDirn, $listOrder); ?></th>
                            <?php endif; ?>

                            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_TITLE_LABEL', 'p.title', $listDirn, $listOrder); ?></th>
                            <th><?php echo Text::_('COM_HOLIDAYPACKAGES_FIELD_IMAGE_LABEL'); ?></th>
                            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_DURATION_LABEL', 'p.duration', $listDirn, $listOrder); ?></th>
                            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_FIELD_PRICE_LABEL', 'p.price', $listDirn, $listOrder); ?></th>
                            <th class="text-center"><?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'p.published', $listDirn, $listOrder); ?></th>
                            <th class="text-center"><?php echo Text::_('Itinerary'); ?></th>
                            <th class="text-center"><?php echo Text::_('Policies'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->items)) : ?>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <?php
                                    $editUrl      = Route::_('index.php?option=com_holidaypackages&view=package&layout=edit&id=' . (int) $item->id . '&destination_id=' . (int) $destinationId);
                                    $itineraryUrl = Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . (int) $item->id . '&destination_id=' . (int) $destinationId);
                                    $policiesUrl  = Route::_('index.php?option=com_holidaypackages&view=policies&package_id=' . (int) $item->id . '&destination_id=' . (int) $destinationId);
                                    $image        = !empty($item->image) ? Uri::root() . $item->image : Uri::root() . 'images/noimage.jpg';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo (int) $item->id; ?></td>
                                    <td class="text-center"><?php echo HTMLHelper::_('grid.id', $i, $item->id); ?></td>

                                    <?php if (!$destinationId) : ?>
                                        <td><?php echo htmlspecialchars($item->destination_title, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php endif; ?>

                                    <td>
                                        <a href="<?php echo $editUrl; ?>">
                                            <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>" style="max-width: 50px; max-height: 50px;" />
                                    </td>
                                    <td><?php echo htmlspecialchars($item->duration, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($item->price_per_person, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center"><?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'packages.', true); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo $itineraryUrl; ?>" class="btn btn-sm btn-outline-primary"><?php echo Text::_('Itinerary'); ?></a>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo $policiesUrl; ?>" class="btn btn-sm btn-outline-success"><?php echo Text::_('Policies'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="<?php echo $destinationId ? 10 : 11; ?>" class="text-center">
                                    <?php echo Text::_('JERROR_NO_ITEMS_FOUND'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="<?php echo $destinationId ? 10 : 11; ?>">
                                <?php echo $this->pagination->getListFooter(); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <input type="hidden" name="filter[destination_id]" value="<?php echo (int) $destinationId; ?>" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </div>
</form>
