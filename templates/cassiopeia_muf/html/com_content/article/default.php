<?php

/**
 * com_content article default override — dispatcher.
 *
 * The child template keeps bespoke article layouts (muf-artifact,
 * muf-exhibition, muf-journal, muf-event, muf-page) and this override
 * routes each article to the right composition based on its custom-field
 * record, so no per-article menu items are required and new artifacts
 * automatically pick up the artifact layout.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/../../muf-tools.php';

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$item = $this->item;

if (muf_field($item, 'artifact-year') !== '') {
    require __DIR__ . '/muf-artifact.php';
} elseif (muf_field($item, 'exhibition-opening') !== '') {
    require __DIR__ . '/muf-exhibition.php';
} elseif (muf_field($item, 'event-date') !== '') {
    require __DIR__ . '/muf-event.php';
} elseif (($item->category_alias ?? '') === 'journal') {
    require __DIR__ . '/muf-journal.php';
} else {
    require __DIR__ . '/muf-page.php';
}
