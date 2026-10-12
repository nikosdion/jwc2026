<?php

/**
 * muf-events — mod_articles_category layout for "Upcoming Events".
 *
 * Chronological strip: date blocks on a hairline rail. The event's date
 * comes from the event-date custom field, so the strip follows the actual
 * schedule rather than article publish order.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../muf-tools.php';

/** @var array $list */
/** @var Joomla\Registry\Registry $params */

// Sort by the event-date custom field.
$items = $list;
usort($items, static function ($a, $b) {
    return strcmp(muf_field($a, 'event-date'), muf_field($b, 'event-date'));
});

?>
<header class="muf-section-head">
    <div>
        <p class="muf-kicker muf-kicker--amber"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_EVENTS_KICKER'); ?></p>
        <h2><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_EVENTS'); ?></h2>
    </div>
    <span class="muf-section-head__index">PROG.05 / <a href="/whats-on"><?php echo Text::_('TPL_CASSIOPEIA_MUF_ALL_EVENTS'); ?> →</a></span>
</header>

<div class="muf-event-rail">
    <?php foreach (array_slice($items, 0, 4) as $item) : ?>
        <?php
        $when = strtotime(muf_field($item, 'event-date'));
        $venue = muf_field($item, 'event-venue');
        ?>
        <a class="muf-event-rail__item" href="<?php echo $item->link; ?>">
            <span class="muf-event-rail__date">
                <span class="day"><?php echo $when ? date('j', $when) : '—'; ?></span>
                <span class="month"><?php echo $when ? date('M', $when) : ''; ?></span>
            </span>
            <span>
                <h3><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>
                    <?php echo $when ? date('l · H:i', $when) : ''; ?>
                    <?php echo $venue !== '' ? ' · ' . htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') : ''; ?>
                </p>
            </span>
        </a>
    <?php endforeach; ?>
</div>
