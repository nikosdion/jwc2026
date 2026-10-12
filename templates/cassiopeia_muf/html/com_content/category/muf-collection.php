<?php

/**
 * muf-collection — com_content category layout: the permanent collection.
 * All four wings on one browsable page, each wing a shelf with its own
 * label, description and specimen tiles.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../../muf-tools.php';

/** @var \Joomla\Component\Content\Site\View\Category\HtmlView $this */

$items = $this->items;

// Index wing descriptions from the child categories.
$wingDesc = [];
foreach (($this->children[$this->category->id] ?? []) as $child) {
    $wingDesc[$child->title] = $child->description;
}

// Group by wing, preserving the menu order of subcategories.
$shelves = [];
foreach ($items as $item) {
    $shelves[$item->category_title][] = $item;
}
$shelfIndex = 0;
?>
<div class="muf-category com-content-category-collection">
    <header class="muf-category-head">
        <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_COLLECTION_KICKER'); ?></p>
        <h1><?php echo $this->escape($this->category->title); ?></h1>
        <?php if ($this->category->description) : ?>
            <div class="category-desc muf-lead"><?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_content.category'); ?></div>
        <?php endif; ?>
    </header>

    <div class="muf-shelves">
        <?php foreach ($shelves as $wingTitle => $wingItems) : ?>
            <?php $shelfIndex++; ?>
            <section class="muf-shelf" id="<?php echo $this->escape(strtolower(str_replace(' ', '-', $wingTitle))); ?>" aria-label="<?php echo htmlspecialchars($wingTitle, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="muf-shelf__label">
                    <span class="muf-shelf__index">WING <?php echo sprintf('%02d', $shelfIndex); ?></span>
                    <h3><?php echo htmlspecialchars($wingTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php if (!empty($wingDesc[$wingTitle])) : ?>
                        <p class="muf-meta"><?php echo htmlspecialchars($wingDesc[$wingTitle], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="muf-shelf__items">
                    <?php foreach ($wingItems as $item) : ?>
                        <?php
                        [$src, $alt] = muf_image($item);
                        $year = muf_field($item, 'artifact-year');
                        $ref  = muf_field($item, 'artifact-ref');
                        ?>
                        <a class="muf-tile" href="<?php echo muf_route($item); ?>">
                            <?php echo muf_picture($item, '(max-width: 640px) 46vw, 230px', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                            <span class="muf-tile__body">
                                <span class="muf-tile__ref"><span><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ref, ENT_QUOTES, 'UTF-8'); ?></span></span>
                                <h4><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h4>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <?php if (($this->pagination ?? null) && $this->pagination->pagesTotal > 1) : ?>
        <nav class="mt-4" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
    <?php endif; ?>
</div>
