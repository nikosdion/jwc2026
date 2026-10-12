<?php

/**
 * muf-main — mod_menu layout for the museum's primary navigation.
 *
 * The menu module passes a FLAT item list; this layout builds a two-level
 * tree (top-level + one child level, per the module's start/end level
 * parameters) and renders Bootstrap nav markup with dropdowns. Each link
 * carries a gallery-style index number via data-index.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/** @var array $list */
/** @var Joomla\Registry\Registry $params */

// Build the two-level tree from the flat list.
$tree    = [];
$current = null;

foreach ($list as $item) {
    if ((int) $item->level === 1) {
        $item->children = [];
        $tree[]         = $item;
        $current        = &$tree[count($tree) - 1];
    } elseif ($current !== null) {
        $current->children[] = $item;
    }
}
unset($current);

$index = 0;
?>
<ul class="nav">
    <?php foreach ($tree as $item) : ?>
        <?php
        $index++;
        $hasChildren = \count($item->children) > 0;
        $isActive    = !empty($item->active);
        ?>
        <li class="nav-item<?php echo $hasChildren ? ' dropdown' : ''; ?>">
            <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                href="<?php echo $item->flink; ?>"
                data-index="<?php echo sprintf('%02d', $index); ?>"
                <?php echo $hasChildren ? 'data-bs-toggle="dropdown" role="button" aria-expanded="false"' : ''; ?>>
                <?php echo $item->title; ?>
            </a>
            <?php if ($hasChildren) : ?>
                <ul class="dropdown-menu">
                    <?php foreach ($item->children as $child) : ?>
                        <li><a class="dropdown-item<?php echo !empty($child->active) ? ' active' : ''; ?>" href="<?php echo $child->flink; ?>"><?php echo $child->title; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
