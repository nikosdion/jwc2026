<?php

/**
 * muf-journal — mod_articles_latest layout for "From the Journal".
 *
 * Asymmetric magazine grid: the newest entry renders as a lead story with
 * image and larger type; the rest render as compact entries.
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
        <p class="muf-kicker muf-kicker--violet"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_JOURNAL_KICKER'); ?></p>
        <h2><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_JOURNAL'); ?></h2>
    </div>
    <span class="muf-section-head__index">JRN.07 / <a href="/journal"><?php echo Text::_('TPL_CASSIOPEIA_MUF_ALL_JOURNAL'); ?> →</a></span>
</header>

<div class="muf-journal-grid">
    <?php foreach ($list as $i => $item) : ?>
        <?php if ($i === 0) : ?>
            <?php [$src, $alt] = muf_image($item); ?>
            <article class="muf-journal-lead">
                <?php if ($src !== '') : ?>
                    <figure>
                        <?php echo muf_picture($item, '(max-width: 992px) 100vw, 580px', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                    </figure>
                <?php endif; ?>
                <div>
                    <p class="muf-meta"><?php echo date('j F Y', strtotime($item->publish_up)); ?> · <?php echo Text::_('TPL_CASSIOPEIA_MUF_JOURNAL_LABEL'); ?></p>
                    <h3 class="muf-journal-lead__title"><a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <p><?php echo htmlspecialchars(strip_tags($item->introtext), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </article>
        <?php else : ?>
            <article class="muf-journal-item">
                <p class="muf-meta"><?php echo date('j F Y', strtotime($item->publish_up)); ?></p>
                <h3 class="muf-journal-item__title"><a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                <p><?php echo htmlspecialchars(strip_tags($item->introtext), ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
