<?php

/**
 * muf-journal — com_content article alternative layout for Journal entries.
 * A calm, generous reading experience distinct from the artifact record.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../../muf-tools.php';

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$item = $this->item;
[$src, $alt] = muf_image($item);
?>
<div class="muf-article muf-journal-article com-content-article">
    <header class="muf-journal-article__head">
        <p class="muf-kicker muf-kicker--violet"><?php echo Text::_('TPL_CASSIOPEIA_MUF_JOURNAL_LABEL'); ?></p>
        <h1><?php echo $this->escape($item->title); ?></h1>
        <p class="muf-meta"><?php echo date('j F Y', strtotime($item->publish_up)); ?> · <?php echo Text::_('TPL_CASSIOPEIA_MUF_JOURNAL_BYLINE'); ?></p>
    </header>

    <?php if ($src !== '') : ?>
        <figure class="muf-journal-article__image">
            <?php echo muf_picture($item, '(max-width: 1200px) 100vw, 920px', ['decoding' => 'async']); ?>
        </figure>
    <?php endif; ?>

    <div class="muf-prose muf-article-body">
        <?php echo $item->text; ?>
    </div>

    <p class="mt-5"><a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars('/journal', ENT_QUOTES, 'UTF-8'); ?>">← <?php echo Text::_('TPL_CASSIOPEIA_MUF_BACK_TO_JOURNAL'); ?></a></p>
</div>
