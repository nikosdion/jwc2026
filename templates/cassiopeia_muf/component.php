<?php

/**
 * cassiopeia_muf — component-only document (modals, print, feeds rendering).
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

$wa = $this->getWebAssetManager();

// Minimal asset set for chrome-less component output.
$wa->useStyle('template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'));
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" data-bs-theme="dark">
<head>
    <jdoc:include type="metas" />
    <jdoc:include type="styles" />
</head>
<body class="site component muf">
    <jdoc:include type="message" />
    <jdoc:include type="component" />
</body>
</html>
