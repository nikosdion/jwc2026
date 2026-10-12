<?php

/**
 * cassiopeia_muf — offline page.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$wa  = $this->getWebAssetManager();

$wa->useStyle('template.offline');

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-bs-theme="dark">
<head>
    <jdoc:include type="metas" />
    <jdoc:include type="styles" />
</head>
<body class="site offline muf">
    <main class="muf-offline">
        <div class="muf-band__inner">
            <p class="muf-kicker"><?php echo Text::_('TPL_CASSIOPEIA_MUF_OFFLINE_KICKER'); ?></p>
            <h1><?php echo htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if ($app->get('display_offline_message', 1)) : ?>
                <p class="muf-lead"><?php echo Text::_('TPL_CASSIOPEIA_MUF_OFFLINE_MESSAGE'); ?></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
