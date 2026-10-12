<?php

/**
 * muf-exhibitions — mod_articles_category layout for "Currently Showing".
 *
 * Renders the section head plus a three-card composition of exhibition
 * articles. Dates and venues come from the exhibition custom fields;
 * nothing is hard-coded per exhibition.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../muf-tools.php';

/** @var array $list */
/** @var Joomla\Registry\Registry $params */

?>
<header class="muf-section-head">
    <div>
        <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_CURRENT_KICKER'); ?></p>
        <h2><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_CURRENT'); ?></h2>
    </div>
    <span class="muf-section-head__index">GALL.02 / EXHIBITIONS</span>
</header>

<div class="muf-exhibitions-grid">
    <?php foreach ($list as $item) : ?>
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
                <h3><a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                <p><?php echo htmlspecialchars(muf_field($item, 'artifact-summary') !== '' ? muf_field($item, 'artifact-summary') : strip_tags($item->introtext), ENT_QUOTES, 'UTF-8'); ?></p>
                <span class="muf-card-exhibition__cta">
                    <?php echo $venue !== '' ? htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') . ' · ' : ''; ?>
                    <?php echo Text::_('TPL_CASSIOPEIA_MUF_VISIT_EXHIBITION'); ?> →
                </span>
            </div>
        </article>
    <?php endforeach; ?>
</div>
