<?php

/**
 * cassiopeia_muf — module chrome ("styles").
 *
 * Available styles:
 *   none      — module output with no wrapping chrome (title hidden).
 *   card      — Cassiopeia-style card chrome (title in card-header).
 *   muf-raw   — output used verbatim; title kept for a11y but visually hidden.
 *   muf-frame — bordered "museum label" frame with a mono uppercase title.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;

/**
 * none — no chrome at all.
 */
function modChrome_none($module, $params): void
{
    if ($module->content === '' || $module->content === null) {
        return;
    }

    echo $module->content;
}

/**
 * card — Cassiopeia card chrome.
 */
function modChrome_card($module, $params): void
{
    if ($module->content === '' || $module->content === null) {
        return;
    }

    $headerClass = trim($params->get('header_class', ''));
    ?>
    <div class="card <?php echo $params->get('moduleclass_sfx'); ?>">
        <?php if ($module->showtitle) : ?>
            <div class="card-header <?php echo $headerClass; ?>">
                <?php echo $module->title; ?>
            </div>
        <?php endif; ?>
        <div class="card-body">
            <?php echo $module->content; ?>
        </div>
    </div>
    <?php
}

/**
 * muf-raw — module content verbatim; the title remains in the document for
 * screen readers and section navigation but is visually hidden.
 */
function modChrome_muf_raw($module, $params): void
{
    if ($module->content === '' || $module->content === null) {
        return;
    }

    $moduleTag = htmlspecialchars($params->get('module_tag', 'div'), ENT_QUOTES, 'UTF-8');
    ?>
    <<?php echo $moduleTag; ?> class="muf-raw <?php echo $params->get('moduleclass_sfx'); ?>">
        <?php if ($module->showtitle) : ?>
            <h2 class="visually-hidden"><?php echo $module->title; ?></h2>
        <?php endif; ?>
        <?php echo $module->content; ?>
    </<?php echo $moduleTag; ?>>
    <?php
}

/**
 * muf-frame — "museum label" chrome: hairline frame + mono uppercase title.
 */
function modChrome_muf_frame($module, $params): void
{
    if (($module->content === '' || $module->content === null) && !$module->showtitle) {
        return;
    }

    $moduleTag = htmlspecialchars($params->get('module_tag', 'div'), ENT_QUOTES, 'UTF-8');
    ?>
    <<?php echo $moduleTag; ?> class="muf-frame <?php echo $params->get('moduleclass_sfx'); ?>">
        <?php if ($module->showtitle) : ?>
            <h2><?php echo $module->title; ?></h2>
        <?php endif; ?>
        <?php echo $module->content; ?>
    </<?php echo $moduleTag; ?>>
    <?php
}
