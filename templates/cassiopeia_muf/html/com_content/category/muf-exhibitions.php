<?php

/**
 * muf-exhibitions — com_content category layout: the exhibitions index.
 * A striking overview grid of exhibition cards.
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
?>
<div class="muf-category com-content-category-exhibitions">
    <header class="muf-category-head">
        <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_CURRENT_KICKER'); ?></p>
        <h1><?php echo $this->escape($this->category->title); ?></h1>
        <?php if ($this->category->description) : ?>
            <div class="category-desc muf-lead"><?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_content.category'); ?></div>
        <?php endif; ?>
    </header>

    <div class="muf-exhibitions-grid">
        <?php foreach ($items as $item) : ?>
            <?php
            [$src, $alt] = muf_image($item);
            $opening = muf_field($item, 'exhibition-opening');
            $closing = muf_field($item, 'exhibition-closing');
            $venue   = muf_field($item, 'exhibition-venue');
            ?>
            <article class="muf-card-exhibition">
                <figure class="muf-card-exhibition__media">
                    <?php echo muf_picture($item, '(max-width: 768px) 100vw, (max-width: 1200px) 46vw, 430px', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                    <?php if ($opening !== '' && $closing !== '') : ?>
                        <span class="muf-card-exhibition__dates">
                            <?php echo date('j M Y', strtotime($opening)) . ' – ' . date('j M Y', strtotime($closing)); ?>
                        </span>
                    <?php endif; ?>
                </figure>
                <div class="muf-card-exhibition__body">
                    <h3><a href="<?php echo muf_route($item); ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <p><?php echo htmlspecialchars(strip_tags($item->introtext), ENT_QUOTES, 'UTF-8'); ?></p>
                    <span class="muf-card-exhibition__cta">
                        <?php echo $venue !== '' ? htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') . ' · ' : ''; ?>
                        <?php echo Text::_('TPL_CASSIOPEIA_MUF_VISIT_EXHIBITION'); ?> →
                    </span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (($this->pagination ?? null) && $this->pagination->pagesTotal > 1) : ?>
        <nav class="mt-4" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
    <?php endif; ?>
</div>
