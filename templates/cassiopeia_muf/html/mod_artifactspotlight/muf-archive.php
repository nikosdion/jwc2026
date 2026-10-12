<?php

/**
 * muf-archive — mod_artifactspotlight layout for "From the Archives".
 *
 * Oversized single-artifact highlight: the image bleeds left, the archival
 * record (year, inventor, reference, abandonment note) sits right, leaning
 * on the artifact custom fields.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once __DIR__ . '/../muf-tools.php';

/** @var object|null $item */
/** @var Joomla\Registry\Registry $params */

if ($item === null) {
    return;
}

$year     = muf_field($item, 'artifact-year');
$inventor = muf_field($item, 'artifact-inventor');
$ref      = muf_field($item, 'artifact-ref');
$abandoned = muf_field($item, 'artifact-abandoned');
[$src, $alt] = muf_image($item);
?>
<header class="muf-section-head">
    <div>
        <p class="muf-kicker muf-kicker--magenta"><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_ARCHIVE_KICKER'); ?></p>
        <h2><?php echo Text::_('TPL_CASSIOPEIA_MUF_HEAD_ARCHIVE'); ?></h2>
    </div>
    <span class="muf-section-head__index">ARC.04 / <?php echo htmlspecialchars($ref, ENT_QUOTES, 'UTF-8'); ?></span>
</header>

<div class="muf-archive">
    <figure class="muf-archive__figure">
        <?php echo muf_picture($item, '(max-width: 992px) 100vw, 680px', ['loading' => 'lazy', 'decoding' => 'async']); ?>
    </figure>
    <div class="muf-archive__aside">
        <p class="muf-meta muf-meta--cyan"><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($inventor, ENT_QUOTES, 'UTF-8'); ?></p>
        <h3 class="muf-archive__title"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></h3>
        <?php if ($abandoned !== '') : ?>
            <p><?php echo htmlspecialchars($abandoned, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <p><a class="btn btn-outline-primary" href="<?php echo $item->link; ?>"><?php echo Text::_('TPL_CASSIOPEIA_MUF_READ_RECORD'); ?> →</a></p>
    </div>
</div>
