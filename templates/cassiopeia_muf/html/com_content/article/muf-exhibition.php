<?php

/**
 * muf-exhibition — com_content article alternative layout for exhibitions.
 * Full-bleed hero, date ribbon, curatorial narrative, facts grid.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../../muf-tools.php';

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$item = $this->item;
$opening = muf_field($item, 'exhibition-opening');
$closing = muf_field($item, 'exhibition-closing');
$venue   = muf_field($item, 'exhibition-venue');
$curator = muf_field($item, 'exhibition-curator');
[$src, $alt] = muf_image($item);
?>
<div class="muf-article muf-exhibition-detail com-content-article">
    <?php if ($src !== '') : ?>
        <figure class="muf-exhibition__hero">
            <?php echo muf_picture($item, '(max-width: 1600px) 100vw, 1440px', ['decoding' => 'async']); ?>
        </figure>
    <?php endif; ?>

    <div class="muf-band__inner">
        <header class="muf-category-head">
            <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_EXHIBITION_KICKER'); ?></p>
            <h1><?php echo $this->escape($item->title); ?></h1>
            <?php if ($opening !== '' && $closing !== '') : ?>
                <p class="muf-exhibition__dates"><span class="muf-dot" aria-hidden="true"></span>
                    <?php echo date('j F Y', strtotime($opening)) . ' — ' . date('j F Y', strtotime($closing)); ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="muf-exhibition__facts">
            <div><p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_VENUE'); ?></p><p><?php echo htmlspecialchars($venue, ENT_QUOTES, 'UTF-8'); ?></p></div>
            <div><p class="muf-kicker muf-kicker--violet"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_CURATOR'); ?></p><p><?php echo htmlspecialchars($curator, ENT_QUOTES, 'UTF-8'); ?></p></div>
            <div><p class="muf-kicker muf-kicker--amber"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_ADMISSION'); ?></p><p><?php echo Text::_('TPL_CASSIOPEIA_MUF_WITH_ADMISSION'); ?></p></div>
        </div>

        <div class="muf-prose muf-article-body">
            <?php echo $item->text; ?>
        </div>
    </div>
</div>
