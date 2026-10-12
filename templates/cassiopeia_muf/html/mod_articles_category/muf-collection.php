<?php

/**
 * muf-collection — mod_articles_category layout for "Explore the Collection".
 *
 * Groups the artifacts by their wing (sub-category) and renders each wing
 * as a shelf: sticky label on the left, specimen tiles on the right.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../muf-tools.php';

/** @var array $list */
/** @var Joomla\Registry\Registry $params */

// Group items by category title, keeping first-seen order.
$shelves = [];
foreach ($list as $item) {
    $group = $item->category_title ?? Text::_('TPL_CASSIOPEIA_MUF_MISC_SHELF');
    if (!isset($shelves[$group])) {
        $shelves[$group] = [];
    }
    $shelves[$group][] = $item;
}

$shelfIndex = 0;
?>
<header class="muf-section-head">
    <div>
        <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_COLLECTION_KICKER'); ?></p>
        <h2><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_COLLECTION'); ?></h2>
    </div>
    <span class="muf-section-head__index">COLL.03 / <?php echo count($list); ?> OBJECTS ON VIEW</span>
</header>

<div class="muf-shelves">
    <?php foreach ($shelves as $shelfTitle => $items) : ?>
        <?php $shelfIndex++; ?>
        <section class="muf-shelf" aria-label="<?php echo htmlspecialchars($shelfTitle, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="muf-shelf__label">
                <span class="muf-shelf__index">WING <?php echo sprintf('%02d', $shelfIndex); ?></span>
                <h3><?php echo htmlspecialchars($shelfTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                <a href="<?php echo $items[0]->category_link ?? '/the-collection'; ?>"><?php echo Text::_('TPL_CASSIOPEIA_MUF_BROWSE_WING'); ?> →</a>
            </div>
            <div class="muf-shelf__items">
                <?php foreach ($items as $item) : ?>
                    <?php
                    [$src, $alt] = muf_image($item);
                    $year = muf_field($item, 'artifact-year');
                    $ref  = muf_field($item, 'artifact-ref');
                    ?>
                    <a class="muf-tile" href="<?php echo $item->link; ?>">
                        <?php echo muf_picture($item, '(max-width: 640px) 46vw, 230px', ['loading' => 'lazy', 'decoding' => 'async']); ?>
                        <span class="muf-tile__body">
                            <span class="muf-tile__ref"><span><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($ref, ENT_QUOTES, 'UTF-8'); ?></span></span>
                            <h4><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h4>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
