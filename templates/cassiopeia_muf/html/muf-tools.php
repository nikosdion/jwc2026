<?php

/**
 * Shared helpers for the cassiopeia_muf template layouts.
 *
 * Not a Joomla layout itself — included by the muf-* module and component
 * layouts below. Functions are namespaced with the muf_ prefix to avoid
 * collisions with layout scopes.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Fetch one custom field value (raw) for a content item.
 */
function muf_field(object $item, string $name): string
{
    static $cache = [];

    $itemId = (int) ($item->id ?? 0);
    if ($itemId === 0) {
        return '';
    }

    if (!\array_key_exists($itemId, $cache)) {
        $cache[$itemId] = [];
        foreach (FieldsHelper::getFields('com_content.article', $item, false) as $field) {
            $cache[$itemId][$field->name] = trim((string) $field->rawvalue);
        }
    }

    return $cache[$itemId][$name] ?? '';
}

/**
 * Intro/full image of a content item as [src, alt] with a sane fallback.
 */
function muf_image(object $item): array
{
    $images = \is_string($item->images ?? null) ? json_decode($item->images, true) : ($item->images ?? []);

    if (!empty($images['image_intro'])) {
        return [$images['image_intro'], $images['image_intro_alt'] ?? ''];
    }

    if (!empty($images['image_fulltext'])) {
        return [$images['image_fulltext'], $images['image_fulltext_alt'] ?? ''];
    }

    return ['', ''];
}

/**
 * Root-relative URL for a stored image path.
 */
function muf_image_url(string $path): string
{
    if ($path === '') {
        return '';
    }

    return (str_starts_with($path, 'http') ? $path : Uri::root(false) . ltrim($path, '/'));
}

/**
 * SEF route for a content item in a category listing (items there do not
 * carry a ->link property the way module listings do).
 */
function muf_route(object $item): string
{
    $slug = $item->slug ?? ((int) $item->id . (isset($item->alias) ? ':' . $item->alias : ''));

    return \Joomla\CMS\Router\Route::_(
        \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute(
            $slug,
            (int) ($item->catid ?? 0)
        )
    );
}

/**
 * Render a responsive <picture> for a content item's image via MufImage.
 * Returns '' when the item has no image.
 */
function muf_picture(object $item, string $sizes, array $attrs = []): string
{
    [$src, $alt] = muf_image($item);

    if ($src === '') {
        return '';
    }

    require_once __DIR__ . '/MufImage.php';

    return MufImage::picture($src, $alt !== '' ? $alt : ($item->title ?? ''), [
        'sizes' => $sizes,
        'attrs' => $attrs,
    ]);
}
