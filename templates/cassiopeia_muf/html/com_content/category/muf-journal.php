<?php

/**
 * muf-journal — com_content category layout: the Museum Journal.
 * Magazine presentation: a prominent lead story, then varied entries.
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
<div class="muf-category com-content-category-journal">
    <header class="muf-category-head">
        <p class="muf-kicker muf-kicker--violet"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_JOURNAL_KICKER'); ?></p>
        <h1><?php echo $this->escape($this->category->title); ?></h1>
        <?php if ($this->category->description) : ?>
            <div class="category-desc muf-lead"><?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_content.category'); ?></div>
        <?php endif; ?>
    </header>

    <div class="muf-journal-grid">
        <?php foreach ($items as $i => $item) : ?>
            <?php if ($i === 0) : ?>
                <?php [$src, $alt] = muf_image($item); ?>
                <article class="muf-journal-lead">
                    <?php if ($src !== '') : ?>
                        <figure>
                            <?php echo muf_picture($item, '(max-width: 992px) 100vw, 640px', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                        </figure>
                    <?php endif; ?>
                    <div>
                        <p class="muf-meta"><?php echo date('j F Y', strtotime($item->publish_up)); ?> · <?php echo Text::_('TPL_CASSIOPEIA_MUF_JOURNAL_LABEL'); ?></p>
                        <h3 class="muf-journal-lead__title"><a href="<?php echo muf_route($item); ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                        <p><?php echo htmlspecialchars(strip_tags($item->introtext), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </article>
            <?php else : ?>
                <article class="muf-journal-item">
                    <p class="muf-meta"><?php echo date('j F Y', strtotime($item->publish_up)); ?></p>
                    <h3 class="muf-journal-item__title"><a href="<?php echo muf_route($item); ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <p><?php echo htmlspecialchars(strip_tags($item->introtext), ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (($this->pagination ?? null) && $this->pagination->pagesTotal > 1) : ?>
        <nav class="mt-4" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>"><?php echo $this->pagination->getPagesLinks(); ?></nav>
    <?php endif; ?>
</div>
