<?php

/**
 * FLUX.2 [pro] image generator — The Museum of Unbuilt Futures.
 *
 * Reads .docs/image-prompts.json, assembles each prompt from the fixed
 * global prefix/suffix (see .docs/image-style.md), submits to the BFL API
 * (POST {BFL_BASE_URL}/v1/flux-2-pro, x-key auth), polls the returned
 * polling URL, downloads the result immediately, and records provenance
 * back into the manifest.
 *
 * Usage:
 *   BFL_API_KEY=... php .docs/scripts/generate-images.php --missing
 *   BFL_API_KEY=... php .docs/scripts/generate-images.php --id=artifact-olfax
 *   BFL_API_KEY=... php .docs/scripts/generate-images.php --all
 *
 * Environment:
 *   BFL_API_KEY   (required)  API key — never commit it
 *   BFL_BASE_URL  (optional)  default https://api.bfl.ai
 *                             (e.g. https://api.eu.bfl.ai for the EU region)
 *
 * Exit codes: 0 success / 1 usage or configuration error / 2 generation
 * failure on one or more images.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$siteRoot = \dirname(__DIR__, 2);
$manifestPath = __DIR__ . '/../image-prompts.json';

// ---------------------------------------------------------------------------
// Arguments
// ---------------------------------------------------------------------------
$options = getopt('', ['all', 'missing', 'id:', 'limit:', 'force', 'help']);

if (isset($options['help']) || (!isset($options['all']) && !isset($options['missing']) && !isset($options['id']))) {
    echo <<<USAGE
FLUX.2 [pro] image generator — The Museum of Unbuilt Futures

Usage:
  BFL_API_KEY=<key> php .docs/scripts/generate-images.php --missing [--limit=N]
  BFL_API_KEY=<key> php .docs/scripts/generate-images.php --id=<image-id>
  BFL_API_KEY=<key> php .docs/scripts/generate-images.php --all [--force]

Environment:
  BFL_API_KEY    API key (required)
  BFL_BASE_URL   default https://api.bfl.ai (regional: https://api.eu.bfl.ai)

USAGE;
    exit(isset($options['help']) ? 0 : 1);
}

$apiKey  = getenv('BFL_API_KEY') ?: '';
$baseUrl = rtrim(getenv('BFL_BASE_URL') ?: 'https://api.bfl.ai', '/');
$force   = isset($options['force']);
$limit    = isset($options['limit']) ? max(1, (int) $options['limit']) : null;

if ($apiKey === '') {
    fwrite(STDERR, "ERROR: BFL_API_KEY is not set.\n");
    fwrite(STDERR, "Export your Black Forest Labs API key first, e.g.:\n");
    fwrite(STDERR, "  export BFL_API_KEY='bfl_...'\n");
    exit(1);
}

if (!file_exists($manifestPath)) {
    fwrite(STDERR, "ERROR: manifest not found at {$manifestPath}\n");
    exit(1);
}

$manifest = json_decode(file_get_contents($manifestPath), true);
if (!\is_array($manifest) || empty($manifest['images'])) {
    fwrite(STDERR, "ERROR: manifest is not valid JSON or has no images.\n");
    exit(1);
}

$prefix = $manifest['meta']['prefix'];
$suffix = $manifest['meta']['suffix'];
$outputDir = $siteRoot . '/' . ($manifest['meta']['outputDir'] ?? 'images/muf');

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "ERROR: cannot create output directory {$outputDir}\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// HTTP helpers (cURL)
// ---------------------------------------------------------------------------
function httpRequest(string $method, string $url, string $apiKey, ?array $json = null): array
{
    $ch = curl_init($url);
    $headers = [
        'Content-Type: application/json',
        'x-key: ' . $apiKey,
        'Accept: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 60,
    ]);

    if ($json !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    }

    $body     = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);

    if ($body === false || $curlErr !== '') {
        return ['ok' => false, 'error' => 'cURL: ' . $curlErr, 'http' => $httpCode];
    }

    $data = json_decode($body, true);

    return ['ok' => $httpCode >= 200 && $httpCode < 300, 'http' => $httpCode, 'data' => \is_array($data) ? $data : null, 'raw' => $body];
}

// ---------------------------------------------------------------------------
// Generation
// ---------------------------------------------------------------------------
function generateOne(array $image, string $prefix, string $suffix, string $baseUrl, string $apiKey, string $outputDir): array
{
    $prompt = $prefix . ' ' . $image['subject'] . ' ' . $suffix;
    $target = $outputDir . '/' . $image['file'];

    echo "\n=== {$image['id']} ({$image['width']}x{$image['height']}) ===\n";
    echo "  prompt: " . \strlen($prompt) . " chars\n";

    // 1. Submit
    $payload = [
        'prompt' => $prompt,
        'width'  => (int) $image['width'],
        'height' => (int) $image['height'],
    ];

    if (!empty($image['seed'])) {
        $payload['seed'] = (int) $image['seed'];
    }

    $response = httpRequest('POST', $baseUrl . '/v1/flux-2-pro', $apiKey, $payload);

    if (!$response['ok']) {
        // Retryable rate limit? BFL signals 429.
        if ($response['http'] === 429) {
            return ['ok' => false, 'retryable' => true, 'error' => 'rate limited (HTTP 429)'];
        }

        return ['ok' => false, 'error' => 'submit failed HTTP ' . $response['http'] . ': ' . substr((string) $response['raw'], 0, 300)];
    }

    $pollingUrl = $response['data']['polling_url'] ?? null;
    if (!$pollingUrl) {
        return ['ok' => false, 'error' => 'no polling_url in response: ' . substr((string) $response['raw'], 0, 300)];
    }

    echo "  submitted: {$response['data']['id']}\n";

    // 2. Poll
    $maxAttempts = 120; // ~2 minutes at 1s interval
    $sampleUrl   = null;

    for ($i = 1; $i <= $maxAttempts; $i++) {
        sleep(1);

        $status = httpRequest('GET', $pollingUrl, $apiKey);

        if (!$status['ok']) {
            if ($i === $maxAttempts) {
                return ['ok' => false, 'error' => 'polling failed HTTP ' . $status['http']];
            }
            continue;
        }

        $state = $status['data']['status'] ?? 'Unknown';
        echo "  [{$i}] {$state}\n";

        if ($state === 'Ready') {
            $sampleUrl = $status['data']['result']['sample'] ?? null;
            break;
        }

        if (\in_array($state, ['Error', 'Failed', 'Cancelled'], true)) {
            return ['ok' => false, 'error' => 'generation failed: ' . ($status['data']['error'] ?? $state)];
        }
    }

    if (!$sampleUrl) {
        return ['ok' => false, 'error' => 'timed out waiting for the result'];
    }

    // 3. Download immediately — hosted URLs are temporary.
    $download = httpRequest('GET', $sampleUrl, $apiKey);

    if (!$download['ok'] || $download['raw'] === '' || str_starts_with((string) $download['raw'], '{')) {
        // Try once more without auth headers (signed URLs may reject x-key).
        $ch = curl_init($sampleUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => true]);
        $binary = curl_exec($ch);
        $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($binary === false || $code !== 200) {
            return ['ok' => false, 'error' => "download failed (HTTP {$code})"];
        }

        $download['raw'] = $binary;
    }

    if (file_put_contents($target, $download['raw']) === false) {
        return ['ok' => false, 'error' => "cannot write {$target}"];
    }

    echo "  saved: {$target} (" . \strlen($download['raw']) . " bytes)\n";

    return [
        'ok'          => true,
        'seed'        => $status['data']['result']['seed'] ?? ($status['data']['seed'] ?? null),
        'generatedAt' => date('c'),
    ];
}

// ---------------------------------------------------------------------------
// Selection + main loop
// ---------------------------------------------------------------------------
$selected = [];

foreach ($manifest['images'] as &$image) {
    $path = $outputDir . '/' . $image['file'];
    $wanted = false;

    if (isset($options['all'])) {
        $wanted = true;
    } elseif (isset($options['missing'])) {
        $wanted = !file_exists($path);
    } elseif (isset($options['id'])) {
        $wanted = $image['id'] === $options['id'];
    }

    if (!$wanted) {
        continue;
    }

    if (file_exists($path) && !$force) {
        echo "skip {$image['id']} — {$image['file']} already exists (use --force to regenerate)\n";
        continue;
    }

    $selected[] = $image;
}
unset($image);

if ($limit !== null) {
    $selected = array_slice($selected, 0, $limit);
}

if ($selected === []) {
    echo "Nothing to generate.\n";
    exit(0);
}

echo "Generating " . \count($selected) . " image(s) with flux-2-pro via {$baseUrl}\n";

$failures  = 0;
$manifestChanged = false;

foreach ($selected as $image) {
    $result = generateOne($image, $prefix, $suffix, $baseUrl, $apiKey, $outputDir);

    if (!$result['ok']) {
        $failures++;
        fwrite(STDERR, "FAILED {$image['id']}: {$result['error']}\n");

        // One retry for retryable failures, after a short backoff.
        if (!empty($result['retryable'])) {
            echo "  retrying once after backoff...\n";
            sleep(20);
            $result = generateOne($image, $prefix, $suffix, $baseUrl, $apiKey, $outputDir);
            if ($result['ok']) {
                $failures--;
            } else {
                fwrite(STDERR, "FAILED again {$image['id']}: {$result['error']}\n");
            }
        }
    }

    if ($result['ok']) {
        // Record provenance in the manifest — persisted immediately, so an
        // interrupted batch run never loses provenance for finished images.
        foreach ($manifest['images'] as &$entry) {
            if ($entry['id'] === $image['id']) {
                $entry['provenance'] = [
                    'model'       => $manifest['meta']['model'],
                    'prompt'      => $prefix . ' ' . $image['subject'] . ' ' . $suffix,
                    'width'       => $image['width'],
                    'height'      => $image['height'],
                    'seed'        => $result['seed'],
                    'generatedAt' => $result['generatedAt'],
                    'file'        => $image['file'],
                ];
            }
        }
        unset($entry);

        file_put_contents(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        );
        $manifestChanged = true;
    }
}

if ($manifestChanged) {
    file_put_contents(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
    echo "\nProvenance written to .docs/image-prompts.json\n";
}

echo $failures === 0 ? "\nAll images generated.\n" : "\n{$failures} image(s) failed.\n";
exit($failures === 0 ? 0 : 2);
