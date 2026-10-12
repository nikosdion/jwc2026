<?php

/**
 * muf-events — com_content category layout: What's On.
 * Chronological listing of upcoming events as editorial rows.
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

// Chronological order by the event-date custom field.
usort($items, static function ($a, $b) {
    return strcmp(muf_field($a, 'event-date'), muf_field($b, 'event-date'));
});
?>
<div class="muf-category com-content-category-events">
    <header class="muf-category-head">
        <p class="muf-kicker muf-kicker--amber"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_EVENTS_KICKER'); ?></p>
        <h1><?php echo $this->escape($this->category->title); ?></h1>
        <?php if ($this->category->description) : ?>
            <div class="category-desc muf-lead"><?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_content.category'); ?></div>
        <?php endif; ?>
    </header>

    <div class="muf-event-list">
        <?php foreach ($items as $item) : ?>
            <?php
            $when  = strtotime(muf_field($item, 'event-date'));
            $venue = muf_field($item, 'event-venue');
            $price = muf_field($item, 'event-price');
            ?>
            <a class="muf-event-row" href="<?php echo muf_route($item); ?>">
                <span class="muf-event-row__date">
                    <span class="day"><?php echo $when ? date('j', $when) : '—'; ?></span>
                    <span class="month"><?php echo $when ? date('M', $when) : ''; ?></span>
                </span>
                <span class="muf-event-row__body">
                    <h3><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?php echo $when ? date('l j F Y · H:i', $when) : ''; ?>
                        <?php echo $venue !== '' ? ' · ' . htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') : ''; ?>
                    </p>
                </span>
                <span class="muf-event-row__meta"><?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (($this->pagination ?? null) && $this->pagination->pagesTotal > 1) : ?>
        <nav class="mt-4" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
    <?php endif; ?>
</div>
