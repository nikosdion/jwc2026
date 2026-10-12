<?php

/**
 * cassiopeia_muf — error pages (403/404/500).
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$wa  = $this->getWebAssetManager();

$wa->useStyle('template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'));

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
$this->setMetaData('theme-color', '#090B1A');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-bs-theme="dark">
<head>
    <jdoc:include type="metas" />
    <jdoc:include type="styles" />
</head>
<body class="site error muf">
    <main class="muf-error">
        <div class="muf-band__inner">
            <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_ERROR_KICKER'); ?></p>
            <h1 class="muf-error__code"><?php echo $this->error->getCode(); ?></h1>
            <p class="muf-error__message"><?php echo htmlspecialchars($this->error->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="muf-meta"><?php echo Text::_('TPL_CASSIOPEIA_MUF_ERROR_BODY'); ?></p>
            <p class="muf-error__home"><a class="btn btn-primary" href="<?php echo $this->baseurl; ?>/"><?php echo Text::_('TPL_CASSIOPEIA_MUF_ERROR_HOME'); ?></a></p>
        </div>
    </main>
</body>
</html>
