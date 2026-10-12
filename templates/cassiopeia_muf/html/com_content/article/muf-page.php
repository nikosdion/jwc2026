<?php

/**
 * muf-page — com_content article alternative layout for institutional pages
 * (Plan Your Visit, About the Museum). Comfortable reading measure, prose
 * styling; sidebars (when assigned) carry supporting modules.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$item = $this->item;
?>
<div class="muf-article muf-page com-content-article">
    <header class="muf-category-head">
        <h1><?php echo $this->escape($item->title); ?></h1>
    </header>
    <div class="muf-prose muf-article-body">
        <?php echo $item->text; ?>
    </div>
</div>
