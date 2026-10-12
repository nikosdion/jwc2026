<?php

/**
 * muf-artifact — com_content article alternative layout for collection
 * artifacts. Long-form history in a readable measure on the left; the
 * archival record card (custom fields) on the right.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

require_once __DIR__ . '/../../muf-tools.php';

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$item  = $this->item;
$year     = muf_field($item, 'artifact-year');
$inventor = muf_field($item, 'artifact-inventor');
$origin   = muf_field($item, 'artifact-origin');
$ref      = muf_field($item, 'artifact-ref');
$summary  = muf_field($item, 'artifact-summary');
$abandoned = muf_field($item, 'artifact-abandoned');
[$src, $alt] = muf_image($item);
?>
<div class="muf-article muf-artifact-page com-content-article">
    <header class="muf-category-head">
        <p class="muf-kicker"><?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></p>
        <h1><?php echo $this->escape($item->title); ?></h1>
        <p class="muf-meta">
            <span class="muf-meta--cyan"><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></span>
            · <?php echo htmlspecialchars($inventor, ENT_QUOTES, 'UTF-8'); ?>
            · <?php echo htmlspecialchars($origin, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </header>

    <div class="muf-artifact">
        <div class="muf-artifact__main">
            <?php if ($src !== '') : ?>
                <figure class="muf-artifact__media">
                    <?php echo muf_picture($item, '(max-width: 992px) 100vw, 780px', ['decoding' => 'async']); ?>
                    <figcaption class="muf-meta"><?php echo Text::_('TPL_CASSIOPEIA_MUF_WIREFRAME_CAPTION'); ?></figcaption>
                </figure>
            <?php endif; ?>

            <div class="muf-prose muf-article-body">
                <?php echo $item->text; ?>
            </div>
        </div>

        <aside class="muf-artifact__record" aria-label="<?php echo Text::_('TPL_CASSIOPEIA_MUF_RECORD_LABEL'); ?>">
            <div class="muf-artifact__ref">
                <span><?php echo Text::_('TPL_CASSIOPEIA_MUF_RECORD_TITLE'); ?></span>
                <span><?php echo htmlspecialchars($ref, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <dl class="muf-spec">
                <div><dt><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_YEAR'); ?></dt><dd><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_INVENTOR'); ?></dt><dd><?php echo htmlspecialchars($inventor, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_ORIGIN'); ?></dt><dd><?php echo htmlspecialchars($origin, ENT_QUOTES, 'UTF-8'); ?></dd></div>
                <div><dt><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_CATEGORY'); ?></dt><dd><?php echo htmlspecialchars($item->category_title, ENT_QUOTES, 'UTF-8'); ?></dd></div>
            </dl>
            <?php if ($abandoned !== '') : ?>
                <div class="muf-artifact__abandoned">
                    <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_ABANDONED'); ?></p>
                    <p><?php echo htmlspecialchars($abandoned, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
