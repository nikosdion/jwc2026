<?php

/**
 * MufImage — local, on-demand responsive image derivatives for the
 * cassiopeia_muf template.
 *
 * Given a site image (e.g. images/muf/artifact-olfax.jpg) this class
 * generates resized JPEG and (when the server supports it) WebP variants
 * under the template's media directory:
 *
 *     media/templates/site/cassiopeia_muf/derived/<name>.<width>.<ext>
 *
 * and returns a <picture> element whose <source> offers the WebP srcset
 * and whose <img> falls back to the JPEG srcset and finally the original
 * full-size image. Derivatives are regenerated automatically whenever the
 * source file is newer; the derived directory is a build output and is
 * gitignored.
 *
 * Backends: Imagick when available, GD otherwise. WebP when Imagick
 * reports WEBP support or GD provides imagewebp().
 *
 * Usage (inside template layouts):
 *
 *     require_once __DIR__ . '/MufImage.php';
 *     echo MufImage::picture('images/muf/artifact-olfax.jpg', $alt, [
 *         'sizes' => '(max-width: 768px) 100vw, 220px',
 *         'attrs' => ['loading' => 'lazy', 'decoding' => 'async', 'class' => 'muf-tile__img'],
 *     ]);
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/**
 * Responsive image derivative helper.
 *
 * @since  1.1.0
 */
final class MufImage
{
    private static ?bool $webp = null;

    private static ?bool $imagick = null;

    /**
     * Render a <picture> element with local WebP/JPEG srcsets.
     *
     * @param   string  $src      Site-root-relative image path
     * @param   string  $alt      Alt text
     * @param   array   $options  width steps, sizes attribute, extra img attributes
     *
     * @return  string  HTML
     *
     * @since   1.1.0
     */
    public static function picture(string $src, string $alt, array $options = []): string
    {
        $root = \defined('JPATH_ROOT') ? JPATH_ROOT : \dirname(__DIR__, 3);

        $srcPath = $root . '/' . ltrim($src, '/');

        $attrs = $options['attrs'] ?? [];

        // Graceful degradation: no source file → plain escaped <img>.
        if (!is_file($srcPath) || ($dims = @getimagesize($srcPath)) === false) {
            return '<img src="' . htmlspecialchars(Uri::root(false) . ltrim($src, '/'), ENT_QUOTES, 'UTF-8')
                . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"' . self::attrString($attrs) . '>';
        }

        [$srcW, $srcH] = $dims;

        $widths = $options['widths'] ?? [480, 768, 1080, 1440, 1920];
        $widths = array_values(array_filter(array_map('intval', $widths), static fn ($w) => $w > 0 && $w < $srcW));

        if ($widths === []) {
            // Source is tiny: point everything at the original.
            return '<img src="' . htmlspecialchars(Uri::root(false) . ltrim($src, '/'), ENT_QUOTES, 'UTF-8')
                . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" width="' . $srcW . '" height="' . $srcH . '"'
                . self::attrString($attrs) . '>';
        }

        $sizes = $options['sizes'] ?? '(max-width: 768px) 100vw, 66vw';

        // Generate derivatives.
        $jpgSet  = [];
        $webpSet = [];

        foreach ($widths as $w) {
            $jpg = self::derive($srcPath, $w, 'jpg');
            if ($jpg !== null) {
                $jpgSet[] = $jpg . ' ' . $w . 'w';
            }

            if (self::webpAvailable()) {
                $webp = self::derive($srcPath, $w, 'webp');
                if ($webp !== null) {
                    $webpSet[] = $webp . ' ' . $w . 'w';
                }
            }
        }

        $originalUrl = htmlspecialchars(Uri::root(false) . ltrim($src, '/'), ENT_QUOTES, 'UTF-8');
        $altEscaped  = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        $sizesEscaped = htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8');

        $html = '<picture>';

        if ($webpSet !== []) {
            $html .= '<source type="image/webp" srcset="' . htmlspecialchars(implode(', ', $webpSet), ENT_QUOTES, 'UTF-8')
                . '" sizes="' . $sizesEscaped . '">';
        }

        $html .= '<img src="' . $originalUrl . '"';

        if ($jpgSet !== []) {
            $html .= ' srcset="' . htmlspecialchars(implode(', ', $jpgSet), ENT_QUOTES, 'UTF-8') . '" sizes="' . $sizesEscaped . '"';
        }

        $html .= ' width="' . $srcW . '" height="' . $srcH . '" alt="' . $altEscaped . '"'
            . self::attrString($attrs) . '></picture>';

        return $html;
    }

    /**
     * Generate (or reuse) one derivative; returns its root-relative URL.
     */
    private static function derive(string $srcPath, int $width, string $format): ?string
    {
        $root = \defined('JPATH_ROOT') ? JPATH_ROOT : \dirname(__DIR__, 3);

        $dir = $root . '/media/templates/site/cassiopeia_muf/derived';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $info    = pathinfo($srcPath);
        $base    = preg_replace('/[^A-Za-z0-9._-]/', '-', $info['filename']);
        $file    = $dir . '/' . $base . '.' . $width . '.' . $format;
        $relUrl  = 'media/templates/site/cassiopeia_muf/derived/' . $base . '.' . $width . '.' . $format;

        if (is_file($file) && filemtime($file) >= filemtime($srcPath)) {
            return Uri::root(false) . $relUrl;
        }

        $tmp = tempnam($dir, 'muf');

        if ($tmp === false) {
            return null;
        }

        $ok = self::renderImagick($srcPath, $tmp, $width, $format)
            || self::renderGd($srcPath, $tmp, $width, $format);

        if (!$ok) {
            @unlink($tmp);

            return null;
        }

        // Atomic publish.
        if (!@rename($tmp, $file)) {
            @unlink($tmp);

            return null;
        }

        return Uri::root(false) . $relUrl;
    }

    private static function renderImagick(string $src, string $dest, int $width, string $format): bool
    {
        try {
            $im = new \Imagick($src);
            $im->scaleImage($width, 0);
            $im->stripImage();

            if ($format === 'webp') {
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality(82);
            } else {
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(85);
            }

            $ok = $im->writeImage($dest);
            $im->clear();
            $im->destroy();

            return (bool) $ok;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function renderGd(string $src, string $dest, int $width, string $format): bool
    {
        $info = getimagesize($src);

        if ($info === false) {
            return false;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $im = @imagecreatefromjpeg($src);
                break;
            case IMAGETYPE_PNG:
                $im = @imagecreatefrompng($src);
                break;
            case IMAGETYPE_WEBP:
                $im = @imagecreatefromwebp($src);
                break;
            default:
                return false;
        }

        if ($im === false) {
            return false;
        }

        $scaled = imagescale($im, $width);

        if ($scaled === false) {
            imagedestroy($im);

            return false;
        }

        if ($format === 'webp') {
            $ok = function_exists('imagewebp') ? @imagewebp($scaled, $dest, 82) : false;
        } else {
            $ok = @imagejpeg($scaled, $dest, 85);
        }

        imagedestroy($scaled);
        imagedestroy($im);

        return (bool) $ok;
    }

    private static function imagickAvailable(): bool
    {
        if (self::$imagick === null) {
            self::$imagick = extension_loaded('imagick');
        }

        return self::$imagick;
    }

    private static function webpAvailable(): bool
    {
        if (self::$webp === null) {
            self::$webp = false;

            if (extension_loaded('imagick')) {
                try {
                    self::$webp = \in_array('WEBP', \Imagick::queryFormats('WEBP'), true);
                } catch (\Throwable $e) {
                    self::$webp = false;
                }
            }

            // GD's encoder is an equally good source of WebP — a broken
            // Imagick delegate setup must not disable WebP outright.
            if (!self::$webp && \extension_loaded('gd') && \function_exists('imagewebp')) {
                self::$webp = true;
            }
        }

        return self::$webp;
    }

    /**
     * Render extra <img> attributes (loading, decoding, class, ...).
     */
    private static function attrString(array $attrs): string
    {
        $out = '';

        foreach ($attrs as $name => $value) {
            $name = preg_replace('/[^a-zA-Z0-9-]/', '', (string) $name);

            if ($name === '') {
                continue;
            }

            if ($value === true || $value === $name) {
                $out .= ' ' . $name;
            } elseif ($value !== false && $value !== null) {
                $out .= ' ' . $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return $out;
    }
}
