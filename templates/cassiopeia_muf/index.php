<?php

/**
 * The Museum of Unbuilt Futures — cassiopeia_muf child template index.
 *
 * A Cassiopeia child template: the parent/child relationship is declared in
 * templateDetails.xml (<parent>cassiopeia</parent>). Parent assets are
 * registered first by the application; this template's joomla.asset.json
 * redefines template.cassiopeia.ltr/rtl so the Web Asset Manager resolves
 * them to this child's compiled CSS (see .docs/design.md).
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app   = Factory::getApplication();
$input = $app->getInput();
$wa    = $this->getWebAssetManager();

// Detecting active variables
$option    = $input->getCmd('option', '');
$view      = $input->getCmd('view', '');
$layout    = $input->getCmd('layout', '');
$task      = $input->getCmd('task', '');
$itemid    = $input->getCmd('Itemid', '');
$sitename  = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');
$menu      = $app->getMenu()->getActive();
$pageclass = $menu !== null ? $menu->getParams()->get('pageclass_sfx', '') : '';
$isHome    = $menu !== null && (bool) $menu->home;

// Favicons (Joomla system images)
$this->addHeadLink(HTMLHelper::_('image', 'joomla-favicon.svg', '', [], true, 1), 'icon', 'rel', ['type' => 'image/svg+xml']);
$this->addHeadLink(HTMLHelper::_('image', 'favicon.ico', '', [], true, 1), 'alternate icon', 'rel', ['type' => 'image/vnd.microsoft.icon']);

// Enable assets: the preset loads the (overridden) template style + script.
$wa->usePreset('template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'))
    ->useStyle('template.active.language')
    ->useStyle('template.user')
    ->useScript('template.user')
    // Bootstrap behaviours used by this template's markup. Joomla serves
    // these from its own vendored Bootstrap 5.3 — a single distribution.
    ->useScript('bootstrap.collapse')
    ->useScript('bootstrap.dropdown');

// Defer Font Awesome: the icon font is not needed for first paint.
if ($wa->assetExists('style', 'fontawesome')) {
    $wa->getAsset('style', 'fontawesome')->setAttribute('rel', 'lazy-stylesheet');
}

// Override 'template.active' so dependencies resolve to the active direction.
$wa->registerStyle('template.active', '', [], [], ['template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr')]);

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$this->setMetaData('theme-color', '#090B1A');

$stickyHeader = $this->params->get('stickyHeader') ? 'position-sticky sticky-top' : '';

// Sidebar awareness for interior pages.
$hasClass = '';
if ($this->countModules('sidebar-left', true)) {
    $hasClass .= ' has-sidebar-left';
}
if ($this->countModules('sidebar-right', true)) {
    $hasClass .= ' has-sidebar-right';
}

// Homepage bands, in display order. The band modifier controls the surface
// treatment; the modules assigned to each position provide the content.
$mufBands = [
    'muf-hero'       => 'muf-band--feature',
    'muf-current'    => '',
    'muf-archive'    => 'muf-band--raised',
    'muf-collection' => '',
    'muf-events'     => 'muf-band--raised',
    'muf-curator'    => 'muf-band--feature',
    'muf-journal'    => '',
    'muf-visit'      => 'muf-band--raised',
];

$hasFooterColumn = static function (string $pos) use ($app): bool {
    return (bool) $app->getDocument()->countModules($pos, true);
};

// Mobile navigation collapse id
$navCollapseId = 'muf-nav-collapse';
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-bs-theme="dark">

<head>
    <jdoc:include type="metas" />
    <jdoc:include type="styles" />
    <jdoc:include type="scripts" />
</head>

<body id="top" class="site muf <?php echo $option
    . ' view-' . $view
    . ($layout ? ' layout-' . $layout : ' no-layout')
    . ($task ? ' task-' . $task : ' no-task')
    . ($itemid ? ' itemid-' . $itemid : '')
    . ($isHome ? ' muf-home' : '')
    . ($pageclass ? ' ' . $pageclass : '')
    . $hasClass
    . ($this->direction == 'rtl' ? ' rtl' : '');
?>">
    <a class="sr-only sr-only-focusable" href="#main-content"><?php echo Text::_('TPL_CASSIOPEIA_MUF_SKIP_TO_CONTENT'); ?></a>

    <header class="header container-header full-width<?php echo $stickyHeader ? ' ' . $stickyHeader : ''; ?>">
        <div class="muf-band__inner header-grid">

            <a class="muf-brand" href="<?php echo $this->baseurl; ?>/">
                <span class="muf-brand__name"><?php echo $sitename; ?><span>.</span></span>
                <span class="muf-brand__tag"><?php echo Text::_('TPL_CASSIOPEIA_MUF_TAGLINE'); ?></span>
            </a>

            <?php if ($this->countModules('menu', true) || $this->countModules('search', true)) : ?>
                <button
                    class="muf-nav-toggle d-lg-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?php echo $navCollapseId; ?>"
                    aria-controls="<?php echo $navCollapseId; ?>"
                    aria-expanded="false"
                    aria-label="<?php echo Text::_('TPL_CASSIOPEIA_MUF_MENU'); ?>"
                >
                    <span class="icon-bar" aria-hidden="true"></span>
                    <span><?php echo Text::_('TPL_CASSIOPEIA_MUF_MENU'); ?></span>
                </button>

                <nav class="muf-nav d-none d-lg-block" aria-label="<?php echo Text::_('TPL_CASSIOPEIA_MUF_MAIN_NAV'); ?>">
                    <jdoc:include type="modules" name="menu" style="none" />
                </nav>

                <div id="<?php echo $navCollapseId; ?>" class="muf-nav-collapse collapse d-lg-none">
                    <jdoc:include type="modules" name="menu" style="none" />
                    <?php if ($this->countModules('search', true)) : ?>
                        <jdoc:include type="modules" name="search" style="none" />
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($this->countModules('search', true)) : ?>
                <div class="header-search d-none d-lg-block">
                    <jdoc:include type="modules" name="search" style="none" />
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="site-grid">
        <?php if ($this->countModules('topbar', true)) : ?>
            <div class="full-width container-topbar">
                <div class="muf-band__inner">
                    <jdoc:include type="modules" name="topbar" style="none" />
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isHome) : ?>
            <?php foreach ($mufBands as $position => $modifier) : ?>
                <?php if ($this->countModules($position, true)) : ?>
                    <section class="muf-band <?php echo $modifier; ?>" aria-label="<?php echo Text::_('TPL_CASSIOPEIA_MUF_SECTION_' . strtoupper(str_replace('-', '_', $position))); ?>">
                        <div class="muf-band__inner">
                            <jdoc:include type="modules" name="<?php echo $position; ?>" style="muf-raw" />
                        </div>
                    </section>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!$isHome || $this->countModules('breadcrumbs main-top main-bottom', true) || true) : ?>
            <div class="grid-child container-main">
                <?php if (!$isHome) : ?>
                    <jdoc:include type="modules" name="breadcrumbs" style="none" />
                <?php endif; ?>
                <jdoc:include type="modules" name="main-top" style="muf-frame" />
                <jdoc:include type="message" />
                <?php if (!$isHome) : ?>
                    <main id="main-content">
                        <jdoc:include type="component" />
                    </main>
                <?php else : ?>
                    <main id="main-content" class="visually-hidden" aria-hidden="true"></main>
                <?php endif; ?>
                <jdoc:include type="modules" name="main-bottom" style="muf-frame" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('sidebar-left', true)) : ?>
            <div class="grid-child container-sidebar-left">
                <jdoc:include type="modules" name="sidebar-left" style="muf-frame" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('sidebar-right', true)) : ?>
            <div class="grid-child container-sidebar-right">
                <jdoc:include type="modules" name="sidebar-right" style="muf-frame" />
            </div>
        <?php endif; ?>
    </div>

    <?php if ($hasFooterColumn('muf-footer-brand') || $hasFooterColumn('muf-footer-nav') || $hasFooterColumn('muf-footer-visit')) : ?>
        <footer class="container-footer footer full-width">
            <div class="muf-band__inner footer-grid">
                <?php if ($hasFooterColumn('muf-footer-brand')) : ?>
                    <div class="footer-brand">
                        <jdoc:include type="modules" name="muf-footer-brand" style="muf-raw" />
                    </div>
                <?php endif; ?>
                <?php if ($hasFooterColumn('muf-footer-nav')) : ?>
                    <div class="footer-nav">
                        <jdoc:include type="modules" name="muf-footer-nav" style="muf-raw" />
                    </div>
                <?php endif; ?>
                <?php if ($hasFooterColumn('muf-footer-visit')) : ?>
                    <div class="footer-visit">
                        <jdoc:include type="modules" name="muf-footer-visit" style="muf-raw" />
                    </div>
                <?php endif; ?>
            </div>
            <div class="muf-band__inner footer-legal">
                <span>&copy; <?php echo date('Y'); ?> <?php echo $sitename; ?> — <?php echo Text::_('TPL_CASSIOPEIA_MUF_LEGAL_LINE'); ?></span>
                <span><?php echo Text::_('TPL_CASSIOPEIA_MUF_LEGAL_FICTION'); ?></span>
            </div>
        </footer>
    <?php endif; ?>

    <?php if ($this->params->get('backTop') == 1) : ?>
        <a href="#top" id="back-top" class="back-to-top-link" aria-label="<?php echo Text::_('TPL_CASSIOPEIA_MUF_BACKTOTOP'); ?>">
            <span class="icon-arrow-up icon-fw" aria-hidden="true"></span>
        </a>
    <?php endif; ?>

    <jdoc:include type="modules" name="debug" style="none" />
</body>

</html>
