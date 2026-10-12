<?php

/**
 * Artifact Spotlight — front controller shim (legacy entry; the real wiring
 * happens through the DI service provider in services/provider.php).
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

// Joomla 4.2+ modules are dispatched through their Dispatcher class, which
// the Module service provider resolves from the DI container. This file only
// exists for environments that still include the module directly.
