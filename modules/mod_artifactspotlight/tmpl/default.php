<?php

/**
 * Artifact Spotlight — default layout.
 *
 * Renders one artifact: image, title (linked), mono year/inventor line from
 * the article's custom fields, and a short unformatted summary. All values
 * are escaped; the module never outputs unpublished content (the helper
 * guarantees a published, access-checked article).
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;

/** @var object $item */
/** @var Joomla\Registry\Registry $params */
/** @var stdClass $module */

if ($item === null) {
    // Nothing published to spotlight: render nothing at all.
    return;
}

// Pull the artifact custom fields (year, inventor, summary) via the fields API.
$item->jcfields = FieldsHelper::getFields('com_content.article', $item, true);

$field = static function (string $name) use ($item): string {
    if (empty($item->jcfields)) {
        return '';
    }

    foreach ($item->jcfields as $f) {
        if ($f->name === $name) {
            return trim(strip_tags((string) $f->value));
        }
    }

    return '';
};

$year     = $field('artifact-year');
$inventor = $field('artifact-inventor');
$summary  = $field('artifact-summary');

// Fall back to the intro text (tags stripped) when no short summary field.
if ($summary === '' && !empty($item->introtext)) {
    $summary = trim(StringHelper::substr(strip_tags($item->introtext), 0, 220));
}

$imageUrl = $item->image !== '' ? Uri::root(false) . ltrim($item->image, '/') : '';
?>
<div class="muf-spotlight <?php echo htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($imageUrl !== '') : ?>
        <a class="muf-spotlight__media" href="<?php echo $item->link; ?>" tabindex="-1" aria-hidden="true">
            <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($item->image_alt !== '' ? $item->image_alt : $item->title, ENT_QUOTES, 'UTF-8'); ?>"
                loading="lazy" decoding="async">
        </a>
    <?php endif; ?>

    <div class="muf-spotlight__body">
        <p class="muf-meta muf-spotlight__meta">
            <?php if ($year !== '') : ?>
                <span class="muf-meta--cyan"><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if ($inventor !== '') : ?>
                <?php echo $year !== '' ? ' · ' : ''; ?>
                <?php echo htmlspecialchars($inventor, ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
        </p>

        <h3 class="muf-spotlight__title">
            <a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></a>
        </h3>

        <?php if ($summary !== '') : ?>
            <p class="muf-spotlight__summary"><?php echo htmlspecialchars($summary, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ((int) $params->get('show_readmore', 1) === 1) : ?>
            <p class="muf-spotlight__cta">
                <a class="btn btn-outline-primary btn-sm" href="<?php echo $item->link; ?>">
                    <?php echo Text::_('MOD_ARTIFACTSPOTLIGHT_VIEW_ARTIFACT'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>
