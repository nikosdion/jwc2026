<?php

/**
 * Artifact Spotlight — helper.
 *
 * Retrieves ONE published artifact (com_content article) for display:
 * a specific article, the most recently published one, or a random one —
 * always from a configured source category, never unpublished or
 * access-restricted content.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

namespace Joomla\Module\ArtifactSpotlight\Site\Helper;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_artifactspotlight.
 *
 * @since  1.0.0
 */
class ArtifactSpotlightHelper
{
    /**
     * Fetches the artifact article to spotlight.
     *
     * @param   Registry                $params  The module parameters
     * @param   CMSApplicationInterface $app     The application
     *
     * @return  object|null  Article row (enriched with `link`, `image`,
     *                       `image_alt`) or null when nothing suitable
     *                       is published.
     *
     * @since   1.0.0
     */
    public function getArtifact(Registry $params, CMSApplicationInterface $app): ?object
    {
        // Resolve the database from the application container: it is always
        // registered there, however the helper ended up being instantiated.
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $mode  = $params->get('mode', 'random');
        $catid = (int) $params->get('catid', 0);
        $artId = (int) $params->get('article_id', 0);
        $now   = date('Y-m-d H:i:s');

        $viewLevels = Access::getAuthorisedViewLevels($app->getIdentity()->id);
        $viewLevels = implode(',', array_map('intval', $viewLevels));

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.catid'),
                $db->quoteName('a.language'),
                $db->quoteName('a.introtext'),
                $db->quoteName('a.fulltext'),
                $db->quoteName('a.images'),
                $db->quoteName('a.publish_up'),
                $db->quoteName('c.title', 'category_title'),
                $db->quoteName('c.alias', 'category_alias'),
                $db->quoteName('c.path', 'category_path'),
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->join(
                'INNER',
                $db->quoteName('#__categories', 'c'),
                $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid')
            )
            ->where([
                $db->quoteName('a.state') . ' = 1',
                $db->quoteName('c.published') . ' = 1',
                $db->quoteName('c.extension') . ' = ' . $db->quote('com_content'),
                $db->quoteName('a.publish_up') . ' <= :now',
                '(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' >= :now2)',
                $db->quoteName('a.access') . ' IN (' . $viewLevels . ')',
                $db->quoteName('c.access') . ' IN (' . $viewLevels . ')',
            ])
            ->bind(':now', $now)
            ->bind(':now2', $now);

        // A specific article wins over the category filter.
        if ($mode === 'specific' && $artId > 0) {
            $query->where($db->quoteName('a.id') . ' = :articleId')
                ->bind(':articleId', $artId);
        } elseif ($catid > 0) {
            // Include the chosen category and all of its descendants,
            // so pointing the module at a parent wing (e.g. The Collection)
            // spotlights artifacts across every sub-category.
            $catRow = $db->setQuery(
                $db->getQuery(true)
                    ->select(['lft', 'rgt'])
                    ->from('#__categories')
                    ->where('id = ' . (int) $catid)
            )->loadObject();

            if ($catRow) {
                $subQuery = $db->getQuery(true)
                    ->select('id')
                    ->from('#__categories')
                    ->where("extension = 'com_content'")
                    ->where('lft >= ' . (int) $catRow->lft)
                    ->where('rgt <= ' . (int) $catRow->rgt);

                $query->where($db->quoteName('a.catid') . ' IN (' . $subQuery . ')');
            } else {
                $query->where($db->quoteName('a.catid') . ' = :catid')
                    ->bind(':catid', $catid);
            }
        }

        switch ($mode) {
            case 'latest':
                $query->order($db->quoteName('a.publish_up') . ' DESC');
                break;
            case 'random':
            default:
                $query->order('RAND()');
                break;
        }

        $db->setQuery($query, 0, 1);
        $item = $db->loadObject();

        if (!$item) {
            return null;
        }

        // Canonical, SEF-aware article route.
        $item->link = Route::_(RouteHelper::getArticleRoute($item->id, $item->catid, $item->language));

        // Intro/full image with alt text from the article's JSON images field.
        $item->image     = '';
        $item->image_alt = '';
        $images = json_decode($item->images ?? '{}', true);

        if (!empty($images['image_intro'])) {
            $item->image     = $images['image_intro'];
            $item->image_alt = $images['image_intro_alt'] ?? '';
        } elseif (!empty($images['image_fulltext'])) {
            $item->image     = $images['image_fulltext'];
            $item->image_alt = $images['image_fulltext_alt'] ?? '';
        }

        return $item;
    }
}
