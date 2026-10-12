<?php

/**
 * muf-event — com_content article alternative layout for What's On events.
 * Date ribbon, venue and admission from the event custom fields, then the
 * event description.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../../muf-tools.php';

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */

$item  = $this->item;
$when  = strtotime(muf_field($item, 'event-date'));
$venue = muf_field($item, 'event-venue');
$price = muf_field($item, 'event-price');
?>
<div class="muf-article muf-event com-content-article">
    <header class="muf-category-head">
        <p class="muf-kicker muf-kicker--amber"><?php echo Text::_('TPL_CASSIOPEIA_MUF_EVENT_KICKER'); ?></p>
        <h1><?php echo $this->escape($item->title); ?></h1>
        <?php if ($when) : ?>
            <p class="muf-exhibition__dates"><span class="muf-dot" aria-hidden="true"></span>
                <?php echo date('l, j F Y · H:i', $when); ?>
            </p>
        <?php endif; ?>
    </header>

    <div class="muf-exhibition__facts">
        <?php if ($venue !== '') : ?>
            <div><p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_VENUE'); ?></p><p><?php echo htmlspecialchars($venue, ENT_QUOTES, 'UTF-8'); ?></p></div>
        <?php endif; ?>
        <?php if ($price !== '') : ?>
            <div><p class="muf-kicker muf-kicker--amber"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SPEC_PRICE'); ?></p><p><?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></p></div>
        <?php endif; ?>
        <div><p class="muf-kicker muf-kicker--violet"><?php echo Text::_('TPL_CASSIOPEIA_MUF_WITH_ADMISSION'); ?></p><p><a href="/plan-your-visit"><?php echo Text::_('TPL_CASSIOPEIA_MUF_PLAN_VISIT'); ?> →</a></p></div>
    </div>

    <div class="muf-prose muf-article-body">
        <?php echo $item->text; ?>
    </div>

    <p class="mt-5"><a class="btn btn-outline-secondary" href="/whats-on">← <?php echo Text::_('TPL_CASSIOPEIA_MUF_BACK_TO_EVENTS'); ?></a></p>
</div>
