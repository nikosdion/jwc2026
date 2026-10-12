<?php

/**
 * The Museum of Unbuilt Futures — provisioning CLI.
 *
 * Idempotently creates and maintains the site's Joomla structure:
 * categories, custom fields (groups + fields + values), articles, menus,
 * modules, menu assignments, and the cassiopeia_muf child template record.
 *
 * Usage (from anywhere):
 *   php .docs/scripts/provision.php            — run all steps
 *   php .docs/scripts/provision.php --step=articles
 *   php .docs/scripts/provision.php --dry-run
 *
 * The script only uses Joomla APIs and table classes — no direct INSERTs
 * for objects Joomla must register (content, categories, menus, modules,
 * fields). Field VALUES are written through #__fields_values because Joomla
 * has no single-table API for them outside the form flow; the rest goes
 * through models/tables with proper events.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

const _JEXEC = 1;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '1');

$siteRoot = \dirname(__DIR__, 2);

if (!file_exists($siteRoot . '/configuration.php')) {
    fwrite(STDERR, "ERROR: {$siteRoot} does not look like a Joomla site root.\n");
    exit(1);
}

define('JPATH_BASE', $siteRoot);
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = \Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');

$app = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Console\Application::class);
\Joomla\CMS\Factory::$application = $app;

// ---------------------------------------------------------------------------
// Arguments
// ---------------------------------------------------------------------------
$options = getopt('', ['step::', 'dry-run', 'force']);

$stepFilter = $options['step'] ?? null;
$dryRun     = isset($options['dry-run']);
$force      = isset($options['force']);

// ---------------------------------------------------------------------------
// Small utilities
// ---------------------------------------------------------------------------
$db = \Joomla\CMS\Factory::getDbo();

function out(string $message, string $level = 'info'): void
{
    $prefix = ['info' => '  ', 'ok' => '  ✓ ', 'warn' => '  ! ', 'title' => ''][ $level ] ?? '  ';
    echo $prefix . $message . "\n";
}

function title(string $message): void
{
    echo "\n=== {$message} ===\n";
}

function loadFixture(string $name): array
{
    $path = __DIR__ . '/../fixtures/' . $name;
    if (!file_exists($path)) {
        fwrite(STDERR, "ERROR: fixture {$name} not found.\n");
        exit(1);
    }

    $data = json_decode(file_get_contents($path), true);
    if (!\is_array($data)) {
        fwrite(STDERR, "ERROR: fixture {$name} is not valid JSON.\n");
        exit(1);
    }

    return $data;
}

function dbq(string $sql, ?string $key = null)
{
    global $db;
    $db->setQuery($sql);

    return $key ? $db->loadAssocList($key) : $db->loadAssocList();
}

function dbOne(string $sql)
{
    global $db;
    $db->setQuery($sql);

    return $db->loadAssoc();
}

function findContentIdByAlias(string $alias): int
{
    $row = dbOne('SELECT id FROM #__content WHERE alias = ' . $GLOBALS['db']->quote($alias));

    return (int) ($row['id'] ?? 0);
}

function findCategoryId(string $alias): int
{
    $row = dbOne(
        'SELECT id FROM #__categories WHERE alias = ' . $GLOBALS['db']->quote($alias)
        . " AND extension = 'com_content'"
    );

    return (int) ($row['id'] ?? 0);
}

function findFieldId(string $name): int
{
    $row = dbOne(
        'SELECT id FROM #__fields WHERE name = ' . $GLOBALS['db']->quote($name)
        . " AND context = 'com_content.article'"
    );

    return (int) ($row['id'] ?? 0);
}

function findMenuItemId(string $alias, string $menutype): int
{
    $row = dbOne(
        'SELECT id FROM #__menu WHERE alias = ' . $GLOBALS['db']->quote($alias)
        . ' AND menutype = ' . $GLOBALS['db']->quote($menutype)
    );

    return (int) ($row['id'] ?? 0);
}

function extensionId(string $element): int
{
    $row = dbOne(
        'SELECT extension_id FROM #__extensions WHERE element = ' . $GLOBALS['db']->quote($element)
        . " AND type IN ('component','module','template','plugin')"
    );

    return (int) ($row['extension_id'] ?? 0);
}

/**
 * Save a record through a Joomla table class (fires table events, keeps
 * nested sets consistent) and report created/updated.
 */
function saveTable(\Joomla\CMS\Table\Table $table, array $data, string $label): bool
{
    global $dryRun;

    if ($dryRun) {
        out("[dry-run] would save {$label}", 'info');

        return true;
    }

    if (!$table->save($data)) {
        out("FAILED saving {$label}: " . $table->getError(), 'warn');

        return false;
    }

    return true;
}

// State passed between steps
$state = [
    'categoryIds' => [],   // key => id
    'fieldIds'    => [],   // key => id
    'articleIds'  => [],   // key => id
    'menuIds'     => [],   // key => id
    'moduleIds'   => [],   // key => id
    'menuType'    => 'mufmain',
    'footerMenuType' => 'muffooter',
];

// ---------------------------------------------------------------------------
// STEP: child template record
// ---------------------------------------------------------------------------
function stepTemplate(): void
{
    title('Child template (cassiopeia_muf)');

    global $db, $dryRun;

    if (extensionId('cassiopeia_muf')) {
        out('Extension record exists — skipping.');
    } elseif ($dryRun) {
        out('[dry-run] would register cassiopeia_muf extension + style');
    } else {
        $manifestCache = json_encode([
            'name'        => 'cassiopeia_muf',
            'type'        => 'template',
            'creationDate'=> '2026-10',
            'author'      => 'The Museum of Unbuilt Futures',
            'copyright'   => '(C) 2026 The Museum of Unbuilt Futures',
            'authorEmail' => 'hello@unbuiltfutures.museum',
            'authorUrl'   => '',
            'version'     => '1.0.0',
            'description' => 'TPL_CASSIOPEIA_MUF_XML_DESCRIPTION',
            'group'       => '',
            'inheritable' => false,
            'parent'      => 'cassiopeia',
            'filename'    => 'templateDetails',
        ]);

        $db->setQuery(
            'INSERT INTO #__extensions '
            . '(package_id, name, type, element, changelogurl, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state, note) '
            . ' VALUES (0, ' . $db->quote('cassiopeia_muf') . ", 'template', 'cassiopeia_muf', '', '', 0, 1, 1, 0, 1, "
            . $db->quote($manifestCache) . ", '{}', '', NULL, NULL, 0, 0, NULL)"
        );
        $db->execute();
        out('Registered cassiopeia_muf extension.', 'ok');
    }

    $style = dbOne('SELECT id, home FROM #__template_styles WHERE template = ' . $db->quote('cassiopeia_muf'));

    if ($style) {
        out('Template style #' . $style['id'] . ' exists — skipping.');
    } elseif ($dryRun) {
        out('[dry-run] would create default template style');
    } else {
        $db->setQuery(
            'INSERT INTO #__template_styles (template, client_id, home, title, inheritable, parent, params) '
            . " VALUES ('cassiopeia_muf', 0, '1', 'Museum of Unbuilt Futures - Default', 0, 'cassiopeia', "
            . $db->quote(json_encode(['stickyHeader' => '1', 'backTop' => '1'])) . ')'
        );
        $db->execute();

        // Only one style can be the site default.
        $db->setQuery("UPDATE #__template_styles SET home = '0' WHERE client_id = 0 AND template <> 'cassiopeia_muf'");
        $db->execute();
        out('Created default style and set cassiopeia_muf as the site template.', 'ok');
    }
}

// ---------------------------------------------------------------------------
// STEP: categories
// ---------------------------------------------------------------------------
function stepCategories(): void
{
    title('Content categories');

    global $dryRun, $state;

    $fixture = loadFixture('categories.json');

    // Order matters: parents before children. The fixture is already ordered.
    foreach ($fixture['categories'] as $cat) {
        $expectedParent = 1;
        if (!empty($cat['parent'])) {
            $expectedParent = $state['categoryIds'][$cat['parent']]
                ?? findCategoryId($fixture['categories'][array_search($cat['parent'], array_column($fixture['categories'], 'key'))]['alias']);
        }

        $existing = findCategoryId($cat['alias']);
        if ($existing) {
            $state['categoryIds'][$cat['key']] = $existing;
            repairCategoryParent($existing, (int) $expectedParent);
            out("{$cat['title']} (#{$existing}) exists — parent ensured, skipping.");
            continue;
        }

        if ($dryRun) {
            out("[dry-run] would create category {$cat['title']}");
            $state['categoryIds'][$cat['key']] = 0;
            continue;
        }

        /** @var \Joomla\CMS\Table\Category $table */
        $table = new \Joomla\CMS\Table\Category($GLOBALS['db']);

        $parentId = 1; // Root
        if (!empty($cat['parent'])) {
            $parentId = $state['categoryIds'][$cat['parent']] ?? findCategoryId($fixture['categories'][array_search($cat['parent'], array_column($fixture['categories'], 'key'))]['alias']);
        }

        // Nested set: place under the parent as last child.
        $table->setLocation($parentId, 'last-child');

        $data = [
            'id'          => 0,
            'parent_id'   => $parentId,
            'extension'   => 'com_content',
            'title'       => $cat['title'],
            'alias'       => $cat['alias'],
            'description' => $cat['description'],
            'published'   => 1,
            'access'      => 1,
            'language'    => '*',
            'metadata'    => ['author' => 'The Museum of Unbuilt Futures'],
            'params'      => ['category_layout' => '', 'image' => ''],
        ];

        if (saveTable($table, $data, "category {$cat['title']}")) {
            $state['categoryIds'][$cat['key']] = (int) $table->id;
            out("Created category {$cat['title']} (#{$table->id}).", 'ok');
        }
    }

    // Rebuild nested sets and alias paths after the bulk insert/repairs.
    if (!$dryRun) {
        $categoryTable = new \Joomla\CMS\Table\Category($GLOBALS['db']);
        $categoryTable->rebuild();
    }
}

/**
 * Repair pass: re-nest an existing category under the expected parent when
 * the nested set disagrees (early provisioning runs saved without a
 * setLocation() call).
 */
function repairCategoryParent(int $categoryId, int $parentId): void
{
    global $db, $dryRun;

    if ($dryRun) {
        return;
    }

    $row = dbOne('SELECT parent_id FROM #__categories WHERE id = ' . (int) $categoryId);

    if (!$row || (int) $row['parent_id'] === $parentId) {
        return;
    }

    $table = new \Joomla\CMS\Table\Category($db);
    if (!$table->load($categoryId)) {
        return;
    }

    $table->setLocation($parentId, 'last-child');

    if (!$table->store()) {
        out("FAILED re-nesting category #{$categoryId}: " . $table->getError(), 'warn');
    } else {
        out("Re-nested category #{$categoryId} under #{$parentId}.", 'ok');
    }
}

// ---------------------------------------------------------------------------
// STEP: custom fields (groups + fields)
// ---------------------------------------------------------------------------
function stepFields(): void
{
    title('Custom fields (groups + fields)');

    global $db, $dryRun, $state;

    $fixture = loadFixture('fields.json');

    // Map of fixture category keys the groups apply to
    $groupCategories = [
        'artifact-record'    => ['transport', 'domestic', 'comms', 'architecture'],
        'exhibition-record'  => ['exhibitions'],
        'event-record'       => ['events'],
    ];

    foreach ($fixture['groups'] as $group) {
        $groupRow = dbOne('SELECT id FROM #__fields_groups WHERE title = ' . $db->quote($group['title']) . " AND context = 'com_content.article' AND state = 1");

        if ($groupRow) {
            $groupId = (int) $groupRow['id'];
            out("Field group '{$group['title']}' (#{$groupId}) exists — skipping.");
        } elseif ($dryRun) {
            out("[dry-run] would create field group {$group['title']}");
            $groupId = 0;
        } else {
            $db->setQuery(
                'INSERT INTO #__fields_groups (context, title, note, description, state, access, language, params, created, created_by, modified, modified_by) '
                . " VALUES ('com_content.article', " . $db->quote($group['title']) . ", '', "
                . $db->quote($group['description']) . ", 1, 1, '*', '{}', " . $db->quote(date('Y-m-d H:i:s')) . ', 0, '
                . $db->quote(date('Y-m-d H:i:s')) . ', 0)'
            );
            $db->execute();
            $groupId = (int) $db->insertid();
            out("Created field group '{$group['title']}' (#{$groupId}).", 'ok');
        }

        foreach ($group['fields'] as $field) {
            $existing = findFieldId($field['key']);
            if ($existing) {
                $state['fieldIds'][$field['key']] = $existing;
                out("Field '{$field['title']}' (#{$existing}) exists — skipping.");
                continue;
            }

            if ($dryRun) {
                out("[dry-run] would create field {$field['title']}");
                $state['fieldIds'][$field['key']] = 0;
                continue;
            }

            /** @var \Joomla\Component\Fields\Administrator\Table\FieldTable $table */
            $table = new \Joomla\Component\Fields\Administrator\Table\FieldTable($db);

            $params = [
                'hint'     => $field['hint'] ?? '',
                'class'    => 'muf-field',
                'label_class' => '',
                'show_on'  => '2', // show in article editor
                'render_class' => '',
                'showlabel' => '1',
                'label_layout' => '',
                'display'  => '2',
                'prefix'   => '',
                'suffix'   => '',
            ];

            $fieldData = [
                'id'          => 0,
                'context'     => 'com_content.article',
                'group_id'    => $groupId,
                'title'       => $field['title'],
                'name'        => $field['key'],
                'label'       => $field['title'],
                'description' => $field['hint'] ?? '',
                'type'        => $field['type'],
                'required'    => !empty($field['required']) ? 1 : 0,
                'state'       => 1,
                'access'      => 1,
                'language'    => '*',
                'params'      => $params,
                'fieldparams' => $field['type'] === 'textarea' ? ['rows' => $field['rows'] ?? 3, 'cols' => 0, 'maxlength' => 0, 'filter' => 'safehtml'] : ($field['type'] === 'calendar' ? ['showtime' => !empty($field['showtime']) ? '1' : '0', 'translateformat' => '1', 'timeformat' => 24, 'singleheader' => '0', 'minyear' => null, 'maxyear' => null, 'todaybutton' => '1', 'weeknumbers' => '0', 'filltable' => '0', 'layout' => ''] : ['filter' => $field['filter'] ?? 'string', 'maxlength' => 255, 'size' => 40]),
                'assigned_cat_ids' => array_filter(array_map(
                    static fn (string $k): int => (int) ($state['categoryIds'][$k] ?? 0),
                    $groupCategories[$group['key']] ?? []
                )),
            ];

            if (saveTable($table, $fieldData, "field {$field['title']}")) {
                $state['fieldIds'][$field['key']] = (int) $table->id;
                out("Created field '{$field['title']}' (#{$table->id}).", 'ok');
            }
        }
    }
}

// ---------------------------------------------------------------------------
// STEP: articles (artifacts, exhibitions, events, journal, pages)
// ---------------------------------------------------------------------------
function saveArticle(array $data, string $label): int
{
    global $db, $dryRun;

    if ($dryRun) {
        out("[dry-run] would save article {$label}");
        return 0;
    }

    $mvc = \Joomla\CMS\Factory::getApplication()->bootComponent('com_content')->getMVCFactory();
    /** @var \Joomla\Component\Content\Administrator\Model\ArticleModel $model */
    $model = $mvc->createModel('Article', 'Administrator', ['ignore_request' => true]);

    if (!$model->save($data)) {
        out("FAILED saving article {$label}: " . $model->getError(), 'warn');
        return 0;
    }

    return (int) $model->getState('article.id');
}

/**
 * Write custom field values for an article directly to #__fields_values.
 * Idempotent: existing values for (field, item) are replaced.
 */
function setFieldValues(int $articleId, array $values): void
{
    global $db, $dryRun;

    if ($dryRun || $articleId <= 0) {
        return;
    }

    foreach ($values as $fieldName => $value) {
        $fieldId = findFieldId($fieldName);
        if (!$fieldId) {
            out("Field '{$fieldName}' not found — value skipped.", 'warn');
            continue;
        }

        $db->setQuery(
            'DELETE FROM #__fields_values WHERE field_id = ' . (int) $fieldId
            . ' AND item_id = ' . (int) $articleId
        );
        $db->execute();

        if ($value === '' || $value === null) {
            continue;
        }

        $db->setQuery(
            'INSERT INTO #__fields_values (field_id, item_id, value) VALUES ('
            . (int) $fieldId . ', ' . (int) $articleId . ', ' . $db->quote((string) $value) . ')'
        );
        $db->execute();
    }
}

function stepArticles(): void
{
    title('Articles');

    global $dryRun, $state;

    $state['categoryIds'] = [
        'collection'   => findCategoryId('collection'),
        'transport'    => findCategoryId('transport-exploration'),
        'domestic'     => findCategoryId('domestic-futures'),
        'comms'        => findCategoryId('communication-computing'),
        'architecture' => findCategoryId('architecture-urbanism'),
        'exhibitions'  => findCategoryId('exhibitions'),
        'events'       => findCategoryId('whats-on'),
        'journal'      => findCategoryId('journal'),
    ];

    $artifacts = array_merge(
        loadFixture('artifacts-1.json')['artifacts'],
        loadFixture('artifacts-2.json')['artifacts']
    );
    $exhibitions = loadFixture('exhibitions.json')['exhibitions'];
    $events      = loadFixture('events.json')['events'];
    $journal     = loadFixture('journal.json')['articles'];
    $pages       = loadFixture('pages.json')['pages'];

    $now = new \DateTimeImmutable('2026-10-12 08:00:00', new \DateTimeZone('Europe/London'));

    // ---- Artifacts ---------------------------------------------------------
    $ordering = 0;
    foreach ($artifacts as $a) {
        $ordering++;
        $existing = findContentIdByAlias($a['alias']);
        if ($existing) {
            $state['articleIds'][$a['key']] = $existing;
            setFieldValues($existing, [
                'artifact-year'      => $a['year'],
                'artifact-inventor'  => $a['inventor'],
                'artifact-origin'    => $a['origin'],
                'artifact-ref'       => $a['ref'],
                'artifact-summary'   => $a['summary'],
                'artifact-abandoned' => $a['abandoned'],
            ]);
            out("Artifact {$a['title']} (#{$existing}) exists — fields ensured, skipping.");
            continue;
        }

        $data = [
            'id'          => 0,
            'title'       => $a['title'],
            'alias'       => $a['alias'],
            'introtext'   => '<p>' . $a['summary'] . '</p>',
            'fulltext'    => $a['body'],
            'catid'       => $state['categoryIds'][$a['category']] ?? 0,
            'state'       => 1,
            'access'      => 1,
            'language'    => '*',
            'featured'    => 0,
            'publish_up'  => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            'created'     => $now->format('Y-m-d H:i:s'),
            'images'      => [
                'image_intro'            => 'images/muf/' . $a['image'] . '.jpg',
                'image_intro_alt'        => $a['title'] . ' — wireframe reconstruction',
                'image_intro_caption'    => '',
                'float_intro'            => '',
                'image_fulltext'         => 'images/muf/' . $a['image'] . '.jpg',
                'image_fulltext_alt'     => $a['title'] . ' — wireframe reconstruction',
                'image_fulltext_caption' => '',
                'float_fulltext'         => '',
                'urla' => '', 'urlatext' => '', 'targeta' => '',
                'urlb' => '', 'urlbtext' => '', 'targetb' => '',
                'urlc' => '', 'urlctext' => '', 'targetc' => '',
            ],
            'metadesc'    => $a['summary'],
            'xreference'  => $a['ref'],
        ];

        $id = saveArticle($data, $a['title']);
        if ($id) {
            $state['articleIds'][$a['key']] = $id;
            setFieldValues($id, [
                'artifact-year'      => $a['year'],
                'artifact-inventor'  => $a['inventor'],
                'artifact-origin'    => $a['origin'],
                'artifact-ref'       => $a['ref'],
                'artifact-summary'   => $a['summary'],
                'artifact-abandoned' => $a['abandoned'],
            ]);
            reorderArticle($id, $ordering);
            out("Created artifact {$a['title']} (#{$id}).", 'ok');
        }
    }

    // ---- Exhibitions -------------------------------------------------------
    $ordering = 0;
    foreach ($exhibitions as $e) {
        $ordering++;
        $existing = findContentIdByAlias($e['alias']);
        if ($existing) {
            $state['articleIds'][$e['key']] = $existing;
            setFieldValues($existing, [
                'exhibition-opening' => (new \DateTimeImmutable($e['opening'], new \DateTimeZone('Europe/London')))->format('Y-m-d H:i:s'),
                'exhibition-closing' => (new \DateTimeImmutable($e['closing'], new \DateTimeZone('Europe/London')))->format('Y-m-d H:i:s'),
                'exhibition-venue'   => $e['venue'],
                'exhibition-curator' => $e['curator'],
            ]);
            out("Exhibition {$e['title']} (#{$existing}) exists — fields ensured, skipping.");
            // Heal: exhibitions must never wait for their own opening date
            // to become visible (a future publish_up reads as "not found").
            $GLOBALS['db']->setQuery(
                'UPDATE #__content SET publish_up = ' . $GLOBALS['db']->quote($now->modify('-1 day')->format('Y-m-d H:i:s'))
                . ' WHERE id = ' . (int) $existing . " AND publish_up > '2026-10-12 23:59:59'"
            );
            $GLOBALS['db']->execute();
            continue;
        }

        $data = [
            'id'         => 0,
            'title'      => $e['title'],
            'alias'      => $e['alias'],
            'introtext'  => '<p>' . $e['summary'] . '</p>',
            'fulltext'   => $e['body'],
            'catid'      => $state['categoryIds']['exhibitions'],
            'state'      => 1,
            'access'     => 1,
            'language'   => '*',
            'featured'   => 0,
            'publish_up' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            'images'     => [
                'image_intro'        => 'images/muf/' . $e['image'] . '.jpg',
                'image_intro_alt'    => $e['title'] . ' — exhibition hero, wireframe rendering',
                'image_fulltext'     => 'images/muf/' . $e['image'] . '.jpg',
                'image_fulltext_alt' => $e['title'] . ' — exhibition hero, wireframe rendering',
            ],
            'metadesc'   => $e['summary'],
        ];

        $id = saveArticle($data, $e['title']);
        if ($id) {
            $state['articleIds'][$e['key']] = $id;
            setFieldValues($id, [
                'exhibition-opening' => (new \DateTimeImmutable($e['opening'], new \DateTimeZone('Europe/London')))->format('Y-m-d H:i:s'),
                'exhibition-closing' => (new \DateTimeImmutable($e['closing'], new \DateTimeZone('Europe/London')))->format('Y-m-d H:i:s'),
                'exhibition-venue'   => $e['venue'],
                'exhibition-curator' => $e['curator'],
            ]);
            reorderArticle($id, $ordering);
            out("Created exhibition {$e['title']} (#{$id}).", 'ok');
        }
    }

    // ---- Events -------------------------------------------------------------
    $ordering = 0;
    foreach ($events as $e) {
        $ordering++;
        $existing = findContentIdByAlias($e['alias']);
        if ($existing) {
            $state['articleIds'][$e['key']] = $existing;
            setFieldValues($existing, [
                'event-date'  => (new \DateTimeImmutable($e['date'], new \DateTimeZone('Europe/London')))->format('Y-m-d H:i:s'),
                'event-venue' => $e['venue'],
                'event-price' => $e['price'],
            ]);
            out("Event {$e['title']} (#{$existing}) exists — fields ensured, skipping.");
            continue;
        }

        $data = [
            'id'         => 0,
            'title'      => $e['title'],
            'alias'      => $e['alias'],
            'introtext'  => '',
            'fulltext'   => $e['body'],
            'catid'      => $state['categoryIds']['events'],
            'state'      => 1,
            'access'     => 1,
            'language'   => '*',
            'featured'   => 0,
            'publish_up' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            'images'     => [
                'image_intro'        => 'images/muf/' . $e['image'] . '.jpg',
                'image_intro_alt'    => $e['title'] . ' — event illustration, wireframe rendering',
                'image_fulltext'     => 'images/muf/' . $e['image'] . '.jpg',
                'image_fulltext_alt' => $e['title'] . ' — event illustration, wireframe rendering',
            ],
            'metadesc'   => trim(preg_replace('/\s+/', ' ', strip_tags($e['body']))),
        ];

        $id = saveArticle($data, $e['title']);
        if ($id) {
            $state['articleIds'][$e['key']] = $id;
            setFieldValues($id, [
                'event-date'  => (new \DateTimeImmutable($e['date'], new \DateTimeZone('Europe/London')))->format('Y-m-d H:i:s'),
                'event-venue' => $e['venue'],
                'event-price' => $e['price'],
            ]);
            reorderArticle($id, $ordering);
            out("Created event {$e['title']} (#{$id}).", 'ok');
        }
    }

    // ---- Journal ------------------------------------------------------------
    $ordering = 0;
    foreach ($journal as $j) {
        $ordering++;
        $existing = findContentIdByAlias($j['alias']);
        if ($existing) {
            $state['articleIds'][$j['key']] = $existing;
            out("Journal {$j['title']} (#{$existing}) exists — skipping.");
            continue;
        }

        $data = [
            'id'         => 0,
            'title'      => $j['title'],
            'alias'      => $j['alias'],
            'introtext'  => '<p class="muf-journal-standfirst">' . $j['lead'] . '</p>',
            'fulltext'   => $j['body'],
            'catid'      => $state['categoryIds']['journal'],
            'state'      => 1,
            'access'     => 1,
            'language'   => '*',
            'featured'   => 0,
            'publish_up' => $now->modify('-' . (20 - $ordering) . ' days')->format('Y-m-d H:i:s'),
            'images'     => [
                'image_intro'        => 'images/muf/' . $j['image'] . '.jpg',
                'image_intro_alt'    => $j['image_alt'],
                'image_fulltext'     => 'images/muf/' . $j['image'] . '.jpg',
                'image_fulltext_alt' => $j['image_alt'],
            ],
            'metadesc'   => $j['lead'],
        ];

        $id = saveArticle($data, $j['title']);
        if ($id) {
            $state['articleIds'][$j['key']] = $id;
            reorderArticle($id, $ordering);
            out("Created journal article {$j['title']} (#{$id}).", 'ok');
        }
    }

    // ---- Pages (uncategorised) ----------------------------------------------
    foreach ($pages as $p) {
        $existing = findContentIdByAlias($p['alias']);
        if ($existing) {
            $state['articleIds'][$p['key']] = $existing;
            out("Page {$p['title']} (#{$existing}) exists — skipping.");
            continue;
        }

        $data = [
            'id'         => 0,
            'title'      => $p['title'],
            'alias'      => $p['alias'],
            'introtext'  => '',
            'fulltext'   => $p['body'],
            'catid'      => findCategoryId('uncategorised'),
            'state'      => 1,
            'access'     => 1,
            'language'   => '*',
            'featured'   => 0,
            'publish_up' => $now->format('Y-m-d H:i:s'),
            'images'     => [
                'image_intro'        => 'images/muf/' . $p['image'] . '.jpg',
                'image_intro_alt'    => $p['title'] . ' — the museum at night, wireframe rendering',
                'image_fulltext'     => 'images/muf/' . $p['image'] . '.jpg',
                'image_fulltext_alt' => $p['title'] . ' — the museum at night, wireframe rendering',
            ],
        ];

        $id = saveArticle($data, $p['title']);
        if ($id) {
            $state['articleIds'][$p['key']] = $id;
            out("Created page {$p['title']} (#{$id}).", 'ok');
        }
    }
}

/**
 * Set the manual ordering of an article within its category.
 */
function reorderArticle(int $id, int $ordering): void
{
    global $db, $dryRun;

    if ($dryRun || $id <= 0) {
        return;
    }

    $db->setQuery('UPDATE #__content SET ordering = ' . (int) $ordering . ' WHERE id = ' . (int) $id);
    $db->execute();
}

// ---------------------------------------------------------------------------
// STEP: resolve %ROUTE:key% placeholders inside article bodies
// ---------------------------------------------------------------------------
function stepResolveRoutes(): void
{
    title('Resolve internal %ROUTE% placeholders');

    global $db, $dryRun;

    // Build key => SEF route map for all artifacts. Fixture keys are short;
    // article aliases carry the full "the-..." form, so resolve explicitly.
    $keyToAlias = [
        'nuclear-automobile'    => 'the-personal-nuclear-automobile',
        'pneumatic-railway'     => 'the-transatlantic-pneumatic-railway',
        'servo-valet'           => 'the-servo-valet-3000',
        'weather-controller'    => 'the-home-weather-controller',
        'pocket-parliament'     => 'the-pocket-parliament',
        'lunar-postal'          => 'the-lunar-postal-service',
        'stratoliner'           => 'the-stratoliner-meridian',
        'videophone'            => 'the-videophone-modele-68',
        'olfax'                 => 'the-olfax-scent-telegraph',
        'cognition-armchair'    => 'the-cognition-armchair-mk-ii',
        'aerosol-dinner'        => 'the-aerosol-dinner-programme',
        'arcology-spire'        => 'arcology-spire-heliopolis-one',
        'panorama-dome'         => 'the-panorama-dome-theatre',
        'garden-city-elevator'  => 'the-garden-city-elevator',
        'vacuum-mall'           => 'the-subatlantic-vacuum-mall',
    ];

    $map = [];
    foreach ($keyToAlias as $key => $alias) {
        $row = dbOne(
            'SELECT a.id, a.alias, c.path FROM #__content a LEFT JOIN #__categories c ON c.id = a.catid'
            . ' WHERE a.alias = ' . $GLOBALS['db']->quote($alias)
        );

        if ($row && $row['path'] !== '' && $row['path'] !== null) {
            // Deterministic SEF path. The Collection menu item alias
            // ('the-collection') fronts the wing (last category path
            // segment) and the article alias. Hand-built because the
            // console context has no site router.
            $segments = explode('/', $row['path']);
            $wing     = end($segments);
            $map[$key] = '/the-collection/' . $wing . '/' . $row['alias'];
        } else {
            out("WARN: artifact article '{$alias}' not found for %ROUTE:{$key}%.", 'warn');
        }
    }

    if ($dryRun) {
        out('[dry-run] would resolve ' . count($map) . ' routes across exhibition articles');
        return;
    }

    // Heal exhibition bodies: rebuild from the fixture when the body still
    // contains unresolved placeholders or damaged (empty) links. Bodies that
    // were resolved correctly — or later hand-edited in the admin — are left
    // untouched.
    foreach (loadFixture('exhibitions.json')['exhibitions'] as $exhibition) {
        $current = dbOne('SELECT `fulltext` FROM #__content WHERE alias = ' . $db->quote($exhibition['alias']));

        if (!$current) {
            continue;
        }

        $text = (string) $current['fulltext'];

        if (strpos($text, '%ROUTE:') === false && strpos($text, 'href=""') === false && strpos($text, 'href="/collection/') === false) {
            continue;
        }

        $body = $exhibition['body'];
        foreach ($map as $key => $route) {
            $body = str_replace('%ROUTE:' . $key . '%', $route, $body);
        }

        $db->setQuery(
            'UPDATE #__content SET `fulltext` = ' . $db->quote($body)
            . ' WHERE alias = ' . $db->quote($exhibition['alias'])
        );
        $db->execute();
        out("Resolved routes in exhibition '{$exhibition['title']}'.", 'ok');
    }
}

// ---------------------------------------------------------------------------
// STEP: menus + menu items
// ---------------------------------------------------------------------------
function ensureMenu(string $menutype, string $title, string $description): void
{
    global $db, $dryRun;

    if (dbOne('SELECT id FROM #__menu_types WHERE menutype = ' . $db->quote($menutype))) {
        out("Menu type '{$menutype}' exists — skipping.");
        return;
    }

    if ($dryRun) {
        out("[dry-run] would create menu {$title}");
        return;
    }

    /** @var \Joomla\Component\Menus\Administrator\Table\MenuTypeTable $table */
    $table = new \Joomla\Component\Menus\Administrator\Table\MenuTypeTable($db);
    saveTable($table, [
        'id'          => 0,
        'menutype'    => $menutype,
        'title'       => $title,
        'description' => $description,
    ], "menu {$title}");
    out("Created menu {$title}.", 'ok');
}

function saveMenuItem(array $data, string $label, int $parentId): int
{
    global $db, $dryRun;

    if ($dryRun) {
        out("[dry-run] would create menu item {$label}");
        return 0;
    }

    /** @var \Joomla\CMS\Table\Menu $table */
    $table = new \Joomla\CMS\Table\Menu($db);

    // Nested set: place the node under its parent, as the last child.
    $table->setLocation($parentId, 'last-child');

    if (!saveTable($table, $data, "menu item {$label}")) {
        return 0;
    }

    return (int) $table->id;
}

/**
 * Repair pass for menu items created by earlier runs: re-nest an existing
 * item under the expected parent when the nested set disagrees.
 */
function repairMenuItemParent(int $itemId, int $parentId, string $expectedLink = '', array $expectedParams = []): void
{
    global $db, $dryRun;

    if ($dryRun) {
        return;
    }

    $table = new \Joomla\CMS\Table\Menu($db);
    if (!$table->load($itemId)) {
        return;
    }

    $needsNest = (int) $table->parent_id !== $parentId;
    $needsLink = $expectedLink !== '' && $table->link !== $expectedLink;

    // Keep the stored params in sync with the fixture as well.
    $currentParams = (array) json_decode($table->params ?? '{}', true);
    $needsParams   = $expectedParams !== [] && array_diff_assoc($expectedParams, $currentParams) !== [];

    if (!$needsNest && !$needsLink && !$needsParams) {
        return;
    }

    if ($needsLink) {
        $table->link = $expectedLink;
    }
    if ($needsParams) {
        $table->params = (string) new \Joomla\Registry\Registry(array_merge($currentParams, $expectedParams));
    }

    if ($needsNest) {
        $table->setLocation($parentId, 'last-child');
    }

    if (!$table->store()) {
        out("FAILED repairing menu item #{$itemId}: " . $table->getError(), 'warn');
    } else {
        out("Repaired menu item #{$itemId} (nest=" . ($needsNest ? 'yes' : 'no') . ", link=" . ($needsLink ? 'yes' : 'no') . ").", 'ok');
    }
}

function stepMenus(): void
{
    title('Menus');

    global $db, $dryRun, $state;

    $contentComponentId = extensionId('com_content');

    ensureMenu($state['menuType'], 'MUF Main', 'Primary navigation for the Museum of Unbuilt Futures.');
    ensureMenu($state['footerMenuType'], 'MUF Footer', 'Footer navigation.');

    $items = [
        ['key' => 'home', 'title' => 'Home', 'alias' => 'welcome', 'parent' => null,
            'link' => 'index.php?option=com_content&view=featured', 'type' => 'component',
            'component_id' => $contentComponentId,
            'params' => ['show_page_heading' => 0]],
        ['key' => 'exhibitions', 'title' => 'Exhibitions', 'alias' => 'exhibitions', 'parent' => null,
            'link' => 'index.php?option=com_content&view=category&layout=muf-exhibitions&id=' . findCategoryId('exhibitions'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-exhibitions', 'display_num' => 0, 'show_page_heading' => 0]],
        ['key' => 'tomorrow-cancelled', 'title' => 'Tomorrow, Cancelled', 'alias' => 'tomorrow-cancelled', 'parent' => 'exhibitions',
            'link' => 'index.php?option=com_content&view=article&id=' . findContentIdByAlias('tomorrow-cancelled'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-exhibition', 'show_page_heading' => 0]],
        ['key' => 'house-of-future', 'title' => 'The House of the Future', 'alias' => 'the-house-of-the-future', 'parent' => 'exhibitions',
            'link' => 'index.php?option=com_content&view=article&id=' . findContentIdByAlias('the-house-of-the-future'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-exhibition', 'show_page_heading' => 0]],
        ['key' => 'cities-in-sky', 'title' => 'Cities in the Sky', 'alias' => 'cities-in-the-sky', 'parent' => 'exhibitions',
            'link' => 'index.php?option=com_content&view=article&id=' . findContentIdByAlias('cities-in-the-sky'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-exhibition', 'show_page_heading' => 0]],
        ['key' => 'collection', 'title' => 'The Collection', 'alias' => 'the-collection', 'parent' => null,
            'link' => 'index.php?option=com_content&view=category&layout=muf-collection&id=' . findCategoryId('collection'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-collection', 'show_subcategory_content' => '-1', 'display_num' => 0, 'show_page_heading' => 0]],
        ['key' => 'whats-on', 'title' => "What's On", 'alias' => 'whats-on', 'parent' => null,
            'link' => 'index.php?option=com_content&view=category&layout=muf-events&id=' . findCategoryId('whats-on'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-events', 'display_num' => 0, 'show_page_heading' => 0]],
        ['key' => 'journal', 'title' => 'Journal', 'alias' => 'journal', 'parent' => null,
            'link' => 'index.php?option=com_content&view=category&layout=muf-journal&id=' . findCategoryId('journal'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-journal', 'display_num' => 0, 'show_page_heading' => 0]],
        ['key' => 'visit', 'title' => 'Plan Your Visit', 'alias' => 'plan-your-visit', 'parent' => null,
            'link' => 'index.php?option=com_content&view=article&id=' . findContentIdByAlias('plan-your-visit'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-page', 'show_page_heading' => 0]],
        ['key' => 'about', 'title' => 'About', 'alias' => 'about-the-museum', 'parent' => null,
            'link' => 'index.php?option=com_content&view=article&id=' . findContentIdByAlias('about-the-museum'),
            'type' => 'component', 'component_id' => $contentComponentId,
            'params' => ['layout' => 'muf-page', 'show_page_heading' => 0]],
    ];

    $menuOrdering = 0;
    foreach ($items as $item) {
        $parentId = 1;
        $level    = 1;
        if (!empty($item['parent'])) {
            $parentId = $state['menuIds'][$item['parent']] ?? 1;
            $level    = 2;
        }

        $existingId = findMenuItemId($item['alias'], $state['menuType']);

        if ($existingId) {
            $state['menuIds'][$item['key']] = $existingId;
            repairMenuItemParent($existingId, $parentId, $item['link'], $item['params']);
            out("Menu item {$item['title']} exists — parent ensured, skipping.");
            continue;
        }

        $menuOrdering++;

        $id = saveMenuItem([
            'id'           => 0,
            'menutype'     => $state['menuType'],
            'title'        => $item['title'],
            'alias'        => $item['alias'],
            'link'         => $item['link'],
            'type'         => $item['type'],
            'component_id' => $item['component_id'],
            'parent_id'    => $parentId,
            'level'        => $level,
            'published'    => 1,
            'access'       => 1,
            'language'     => '*',
            'params'       => $item['params'],
            'ordering'     => $menuOrdering,
            'home'         => 0,
            'browserNav'   => 0,
            'template_style_id' => 0,
        ], $item['title'], $parentId);

        if ($id) {
            $state['menuIds'][$item['key']] = $id;
            out("Created menu item {$item['title']} (#{$id}).", 'ok');
        }
    }

    // Rebuild the menu tree (nested set) after the bulk insert.
    if (!$dryRun) {
        $menuTable = new \Joomla\CMS\Table\Menu($GLOBALS['db']);
        $menuTable->rebuild();
    }

    // The old sample "Home" item must stop being the default, and the
    // sample mainmenu is retired entirely (our own menu module takes over).
    if (!$dryRun) {
        $oldHome = findMenuItemId('home', 'mainmenu');
        if ($oldHome) {
            $db->setQuery('UPDATE #__menu SET home = 0 WHERE id = ' . (int) $oldHome);
            $db->execute();
        }

        $db->setQuery("UPDATE #__menu SET published = 0 WHERE menutype = 'mainmenu' AND client_id = 0 AND level > 0");
        $db->execute();

        $newHome = (int) ($state['menuIds']['home'] ?? 0);
        if ($newHome) {
            $db->setQuery('UPDATE #__menu SET home = 1 WHERE id = ' . $newHome);
            $db->execute();
            out("Set menu item #{$newHome} as the site home.", 'ok');
        }
    }

    // Footer menu: alias items pointing at the main entries.
    $footerItems = [
        'exhibitions' => 'Exhibitions',
        'collection'  => 'The Collection',
        'whats-on'    => "What's On",
        'visit'       => 'Plan Your Visit',
        'about'       => 'About the Museum',
    ];

    $fOrdering = 0;
    foreach ($footerItems as $key => $title) {
        $alias = 'footer-' . $key;
        if (findMenuItemId($alias, $state['footerMenuType'])) {
            continue;
        }
        $fOrdering++;

        saveMenuItem([
            'id'           => 0,
            'menutype'     => $state['footerMenuType'],
            'title'        => $title,
            'alias'        => $alias,
            'link'         => 'index.php?Itemid=' . (int) ($state['menuIds'][$key] ?? 0),
            'type'         => 'alias',
            'component_id' => 0,
            'parent_id'    => 1,
            'level'        => 1,
            'published'    => 1,
            'access'       => 1,
            'language'     => '*',
            'params'       => ['aliasoptions' => (int) ($state['menuIds'][$key] ?? 0), 'menu-anchor_title' => ''],
            'ordering'     => $fOrdering,
            'home'         => 0,
            'browserNav'   => 0,
            'template_style_id' => 0,
        ], "footer: {$title}", 1);
    }

    if (!$dryRun) {
        $menuTable = new \Joomla\CMS\Table\Menu($GLOBALS['db']);
        $menuTable->rebuild();
    }
}

// ---------------------------------------------------------------------------
// STEP: install the custom module, then create all modules + assignments
// ---------------------------------------------------------------------------
function stepInstallModule(): void
{
    title('Install mod_artifactspotlight');

    if (extensionId('mod_artifactspotlight')) {
        out('mod_artifactspotlight already installed — skipping.');
        return;
    }

    global $dryRun, $db;

    if ($dryRun) {
        out('[dry-run] would register mod_artifactspotlight');
        return;
    }

    // The Installer API cannot run in this console bootstrap (it imports
    // extension plugins whose web-registered namespaces are unavailable
    // here, and fails silently). Registration is therefore done exactly as
    // core's discover-install would write it: the module's files already
    // live in modules/mod_artifactspotlight, so only the extensions row
    // and its manifest cache are needed for ModuleHelper to load it.
    $manifest = JPATH_ROOT . '/modules/mod_artifactspotlight/mod_artifactspotlight.xml';
    $details  = \Joomla\CMS\Installer\Installer::parseXMLInstallFile($manifest);

    if (!$details) {
        out('FAILED to parse mod_artifactspotlight manifest.', 'warn');
        return;
    }

    $db->setQuery(
        'INSERT INTO #__extensions '
        . '(package_id, name, type, element, changelogurl, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state, note) '
        . ' VALUES (0, ' . $db->quote($details['name']) . ", 'module', 'mod_artifactspotlight', "
        . $db->quote($details['changelogurl'] ?? '') . ", '', 0, 1, 1, 0, 0, "
        . $db->quote(json_encode($details)) . ", '{}', '', NULL, NULL, 0, 0, NULL)"
    );
    $db->execute();

    out('Registered mod_artifactspotlight (manifest: ' . $details['version'] . ').', 'ok');
}

function ensureModule(array $def): int
{
    global $db, $dryRun, $state;

    $title    = $def['title'];
    $position = $def['position'];

    $existing = dbOne(
        'SELECT id FROM #__modules WHERE module = ' . $db->quote($def['module'])
        . ' AND title = ' . $db->quote($title)
    );

    if ($existing) {
        $moduleId = (int) $existing['id'];
        $state['moduleIds'][$def['key']] = $moduleId;

        // Heal: keep the module's managed parameters in sync with the fixture.
        if (!$dryRun) {
            $params = new \Joomla\Registry\Registry($def['params'] ?? []);
            $db->setQuery(
                'UPDATE #__modules SET position = ' . $db->quote($position)
                . ', published = ' . (int) ($def['published'] ?? 1)
                . ', showtitle = ' . (int) ($def['showtitle'] ?? 0)
                . ', params = ' . $db->quote((string) $params)
                . ($def['module'] === 'mod_custom' ? ', content = ' . $db->quote($def['content'] ?? '') : '')
                . ' WHERE id = ' . $moduleId
            );
            $db->execute();

            // Re-apply the menu assignment (pure junction table).
            $assignment = $def['assignment'] ?? 1;
            $assigned   = $def['assigned'] ?? [];

            $db->setQuery('DELETE FROM #__modules_menu WHERE moduleid = ' . $moduleId);
            $db->execute();

            if ($assignment === 1) {
                $db->setQuery('INSERT INTO #__modules_menu (moduleid, menuid) VALUES (' . $moduleId . ', 0)');
                $db->execute();
            } elseif ($assignment === 2 && \count($assigned)) {
                foreach ($assigned as $menuId) {
                    $db->setQuery('INSERT INTO #__modules_menu (moduleid, menuid) VALUES (' . $moduleId . ', ' . (int) $menuId . ')');
                    $db->execute();
                }
            } elseif ($assignment === -1 && \count($assigned)) {
                $db->setQuery('INSERT INTO #__modules_menu (moduleid, menuid) VALUES (' . $moduleId . ', 0)');
                $db->execute();
                foreach ($assigned as $menuId) {
                    $db->setQuery('DELETE FROM #__modules_menu WHERE moduleid = ' . $moduleId . ' AND menuid = ' . (int) $menuId);
                    $db->execute();
                }
            }
        }

        out("Module {$title} (#{$moduleId}) exists — params healed.");
        return $moduleId;
    }

    if ($dryRun) {
        out("[dry-run] would create module {$title} in {$position}");
        return 0;
    }

    /** @var \Joomla\CMS\Table\Module $table */
    $table = new \Joomla\CMS\Table\Module($db);

    $params = new \Joomla\Registry\Registry($def['params'] ?? []);

    $data = [
        'id'        => 0,
        'title'     => $title,
        'note'      => 'muf-provisioned',
        'module'    => $def['module'],
        'position'  => $position,
        'published' => $def['published'] ?? 1,
        'access'    => 1,
        'showtitle' => $def['showtitle'] ?? 0,
        'params'    => (string) $params,
        'client_id' => 0,
        'language'  => '*',
        'content'   => $def['content'] ?? '',
        'ordering'  => $def['ordering'] ?? 0,
    ];

    if (!saveTable($table, $data, "module {$title}")) {
        return 0;
    }

    $moduleId = (int) $table->id;
    $state['moduleIds'][$def['key']] = $moduleId;

    // Menu assignment (pure junction table).
    $assignment = $def['assignment'] ?? 1; // 1 = all pages
    $assigned   = $def['assigned'] ?? [];

    $db->setQuery('DELETE FROM #__modules_menu WHERE moduleid = ' . $moduleId);
    $db->execute();

    if ($assignment === 1) {
        $db->setQuery('INSERT INTO #__modules_menu (moduleid, menuid) VALUES (' . $moduleId . ', 0)');
        $db->execute();
    } elseif ($assignment === 2 && \count($assigned)) {
        foreach ($assigned as $menuId) {
            $db->setQuery('INSERT INTO #__modules_menu (moduleid, menuid) VALUES (' . $moduleId . ', ' . (int) $menuId . ')');
            $db->execute();
        }
    } elseif ($assignment === -1 && \count($assigned)) {
        // All pages EXCEPT the selected ones.
        $db->setQuery('INSERT INTO #__modules_menu (moduleid, menuid) VALUES (' . $moduleId . ', 0)');
        $db->execute();
        foreach ($assigned as $menuId) {
            $db->setQuery('DELETE FROM #__modules_menu WHERE moduleid = ' . $moduleId . ' AND menuid = ' . (int) $menuId);
            $db->execute();
        }
    }

    out("Created module {$title} (#{$moduleId}) in '{$position}'.", 'ok');

    return $moduleId;
}

function moduleFixtures(): array
{
    global $state;

    $cat = static fn (string $key): int => (int) ($state['categoryIds'][$key] ?? findCategoryId($key));
    $catIds = [
        'exhibitions' => findCategoryId('exhibitions'),
        'collection'  => findCategoryId('collection'),
        'events'      => findCategoryId('whats-on'),
        'journal'     => findCategoryId('journal'),
    ];

    $homeId  = findMenuItemId('welcome', 'mufmain');
    $visitId = findMenuItemId('plan-your-visit', 'mufmain');

    return [
        [
            'key' => 'menu-main', 'module' => 'mod_menu', 'title' => 'MUF — Main navigation',
            'position' => 'menu', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 1,
            'params' => ['menutype' => 'mufmain', 'startLevel' => 1, 'endLevel' => 2, 'showAllChildren' => 1, 'layout' => '_:muf-main', 'cache' => 1],
        ],
        [
            'key' => 'breadcrumbs', 'module' => 'mod_breadcrumbs', 'title' => 'MUF — Breadcrumbs',
            'position' => 'breadcrumbs', 'showtitle' => 0, 'ordering' => 1, 'assignment' => -1, 'assigned' => [$homeId],
            'params' => ['showHere' => 0, 'showHome' => 1, 'homeText' => 'Entrance', 'showLast' => 1, 'separator' => ''],
        ],
        [
            'key' => 'footer-brand', 'module' => 'mod_custom', 'title' => 'MUF — Footer: museum',
            'position' => 'muf-footer-brand', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 1,
            'content' => <<<HTML
<a class="muf-brand" href="/"><span class="muf-brand__name">The Museum of Unbuilt Futures<span>.</span></span><span class="muf-brand__tag">Preserving the tomorrows that never happened.</span></a>
<p>Founded in 1979 in a decommissioned planetarium, the museum collects the technologies, buildings and schemes that were confidently announced — and never built.</p>
<p class="muf-meta">REG. CHARITY NO. 1979-MUF-01 · MARSHAVEN</p>
HTML,
            'params' => ['prepare_content' => 0],
        ],
        [
            'key' => 'footer-nav', 'module' => 'mod_menu', 'title' => 'MUF — Footer: navigate',
            'position' => 'muf-footer-nav', 'showtitle' => 1, 'ordering' => 2, 'assignment' => 1,
            'params' => ['menutype' => 'muffooter', 'startLevel' => 1, 'endLevel' => 1, 'showAllChildren' => 0],
        ],
        [
            'key' => 'footer-visit', 'module' => 'mod_custom', 'title' => 'MUF — Footer: visit',
            'position' => 'muf-footer-visit', 'showtitle' => 1, 'ordering' => 3, 'assignment' => 1,
            'content' => <<<HTML
<p>1 Meridian Parade<br>Marghaven, MB1 9UF</p>
<p class="muf-meta">TUE – SUN 10:00 – 18:00<br>THU LATE UNTIL 21:00<br>MON CLOSED</p>
<p class="muf-meta"><a href="/plan-your-visit">Plan your visit →</a></p>
HTML,
            'params' => ['prepare_content' => 0],
        ],
        // ---- Homepage bands (home menu item only) ---------------------------
        [
            'key' => 'hero', 'module' => 'mod_custom', 'title' => 'MUF — Homepage: hero',
            'position' => 'muf-hero', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'content' => <<<HTML
<div class="muf-hero">
  <div class="muf-hero__grid">
    <div class="muf-hero__copy">
      <p class="muf-kicker">Featured exhibition</p>
      <h1 class="muf-hero__title muf-display">Tomorrow, <span class="muf-display__ghost">Cancelled.</span></h1>
      <p class="muf-hero__lede">Spectacular technology promises that never materialised — from atomic family cars to rocket post — presented with the respect they almost earned. Open now in Gallery One.</p>
      <p class="d-flex flex-wrap gap-3">
        <a class="btn btn-primary btn-lg" href="/exhibitions/tomorrow-cancelled">Visit the exhibition</a>
        <a class="btn btn-outline-secondary btn-lg" href="/exhibitions">All exhibitions</a>
      </p>
    </div>
    <figure class="muf-hero__figure">
      <picture>
        <source type="image/webp" srcset="/media/templates/site/cassiopeia_muf/derived/exhibition-tomorrow-cancelled.480.webp 480w, /media/templates/site/cassiopeia_muf/derived/exhibition-tomorrow-cancelled.768.webp 768w, /media/templates/site/cassiopeia_muf/derived/exhibition-tomorrow-cancelled.1080.webp 1080w" sizes="(max-width: 992px) 100vw, 620px">
        <img src="/images/muf/exhibition-tomorrow-cancelled.jpg" srcset="/media/templates/site/cassiopeia_muf/derived/exhibition-tomorrow-cancelled.480.jpg 480w, /media/templates/site/cassiopeia_muf/derived/exhibition-tomorrow-cancelled.768.jpg 768w, /media/templates/site/cassiopeia_muf/derived/exhibition-tomorrow-cancelled.1080.jpg 1080w" sizes="(max-width: 992px) 100vw, 620px" width="1440" height="810" alt="Wireframe reconstruction of the Tomorrow, Cancelled exhibition — an atomic family car beneath a glass canopy" loading="eager" decoding="async">
      </picture>
      <figcaption><span>EXH.2026.01</span><span>GALLERY ONE · UNTIL 22 FEB 2027</span></figcaption>
    </figure>
  </div>
  <div class="muf-hero__readout" aria-hidden="true">
    <span>COLLECTION <strong>1,500 OBJECTS</strong></span>
    <span>REGISTER <strong>40,000 CARDS</strong></span>
    <span>EST. <strong>1979</strong></span>
    <span>UNBUILT FUTURES PRESERVED <strong>ALL OF THEM</strong></span>
  </div>
</div>
HTML,
            'params' => ['prepare_content' => 0],
        ],
        [
            'key' => 'current', 'module' => 'mod_articles_category', 'title' => 'MUF — Homepage: currently showing',
            'position' => 'muf-current', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'params' => [
                'catid' => [$catIds['exhibitions']], 'count' => 0, 'article_ordering' => 'a.ordering', 'article_ordering_direction' => 'ASC',
                'show_child_category_articles' => 0, 'show_front' => 'show', 'show_introtext' => 0,
                'link_titles' => 1, 'show_date' => 0, 'show_category' => 0, 'show_author' => 0,
                'layout' => '_:muf-exhibitions',
            ],
        ],
        [
            'key' => 'archive', 'module' => 'mod_artifactspotlight', 'title' => 'MUF — Homepage: from the archives',
            'position' => 'muf-archive', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'params' => ['mode' => 'random', 'catid' => $catIds['collection'], 'show_readmore' => 1, 'layout' => '_:muf-archive'],
        ],
        [
            'key' => 'collection', 'module' => 'mod_articles_category', 'title' => 'MUF — Homepage: explore the collection',
            'position' => 'muf-collection', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'params' => [
                'catid' => [$catIds['collection']], 'count' => 12, 'article_ordering' => 'a.ordering', 'article_ordering_direction' => 'ASC',
                'show_child_category_articles' => 1, 'levels' => 1, 'show_front' => 'show', 'show_introtext' => 0,
                'show_date' => 0, 'show_category' => 1, 'show_author' => 0, 'link_titles' => 1,
                'layout' => '_:muf-collection',
            ],
        ],
        [
            'key' => 'events', 'module' => 'mod_articles_category', 'title' => 'MUF — Homepage: upcoming events',
            'position' => 'muf-events', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'params' => [
                'catid' => [$catIds['events']], 'count' => 4, 'article_ordering' => 'a.ordering', 'article_ordering_direction' => 'ASC',
                'show_child_category_articles' => 0, 'show_front' => 'show', 'show_introtext' => 0,
                'show_date' => 0, 'show_category' => 0, 'show_author' => 0, 'link_titles' => 1,
                'layout' => '_:muf-events',
            ],
        ],
        [
            'key' => 'curator', 'module' => 'mod_custom', 'title' => "MUF — Homepage: curator's pick",
            'position' => 'muf-curator', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'content' => <<<HTML
<div class="muf-curator">
  <figure class="muf-curator__figure">
    <picture>
      <source type="image/webp" srcset="/media/templates/site/cassiopeia_muf/derived/curator-lab.480.webp 480w, /media/templates/site/cassiopeia_muf/derived/curator-lab.768.webp 768w" sizes="(max-width: 992px) 100vw, 420px">
      <img src="/images/muf/curator-lab.jpg" srcset="/media/templates/site/cassiopeia_muf/derived/curator-lab.480.jpg 480w, /media/templates/site/cassiopeia_muf/derived/curator-lab.768.jpg 768w" sizes="(max-width: 992px) 100vw, 420px" width="1080" height="1350" alt="Wireframe reconstruction of the restoration laboratory, a machine suspended over a workbench" loading="lazy" decoding="async">
    </picture>
  </figure>
  <blockquote>
    <p class="muf-kicker muf-kicker--magenta">Curator's pick</p>
    <p>“People assume we keep these objects to laugh at them. We keep them because every one of them was somebody's best idea. The laughing is just the sound of recognition.”</p>
    <footer>PROF. BARNABY QUILL · KEEPER OF IMPLAUSIBLE DEVICES<br><a href="/about-the-museum">About the museum →</a></footer>
  </blockquote>
</div>
HTML,
            'params' => ['prepare_content' => 0],
        ],
        [
            'key' => 'journal', 'module' => 'mod_articles_latest', 'title' => 'MUF — Homepage: from the journal',
            'position' => 'muf-journal', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'params' => [
                'catid' => [$catIds['journal']], 'count' => 3, 'ordering' => 'published',
                'ordering_direction' => 'DESC', 'show_front' => 'show', 'show_introtext' => 1, 'introtext_limit' => 220,
                'link_titles' => 1, 'show_date' => 1, 'show_category' => 0, 'show_author' => 1,
                'layout' => '_:muf-journal',
            ],
        ],
        [
            'key' => 'visit', 'module' => 'mod_custom', 'title' => 'MUF — Homepage: plan your visit',
            'position' => 'muf-visit', 'showtitle' => 0, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$homeId],
            'content' => <<<HTML
<div class="muf-visit">
  <div class="muf-visit__panel">
    <p class="muf-kicker">Plan your visit</p>
    <h2 class="h3">The museum is open today until 18:00</h2>
    <dl class="muf-spec">
      <dt>Address</dt><dd>1 Meridian Parade, Marghaven, MB1 9UF</dd>
      <dt>Hours</dt><dd>Tue – Sun 10:00 – 18:00 · Thu until 21:00</dd>
      <dt>Admission</dt><dd>Adults £9 · Concessions £6 · Under 12s free</dd>
    </dl>
    <p class="muf-visit__cta">
      <a class="btn btn-primary" href="/plan-your-visit">Visitor information</a>
      <a class="btn btn-outline-primary" href="/whats-on">What's on</a>
    </p>
  </div>
  <div class="muf-visit__map" role="img" aria-label="Stylised transit map showing the museum at Meridian Parade, served by tram line 4">
    <span class="muf-visit__map-pin">MERIDIAN PARADE</span>
  </div>
</div>
HTML,
            'params' => ['prepare_content' => 0],
        ],
        // ---- Interior page modules ------------------------------------------
        [
            'key' => 'visit-sidebar', 'module' => 'mod_custom', 'title' => 'MUF — Visit sidebar',
            'position' => 'sidebar-right', 'showtitle' => 1, 'ordering' => 1, 'assignment' => 2, 'assigned' => [$visitId],
            'content' => <<<HTML
<dl class="muf-spec">
  <dt>Today</dt><dd>10:00 – 18:00</dd>
  <dt>Thursday</dt><dd>10:00 – 21:00</dd>
  <dt>Monday</dt><dd>Closed</dd>
  <dt>Tram</dt><dd>Line 4 · Meridian Parade</dd>
  <dt>Phone</dt><dd>+44 (0)1603 555 0179</dd>
</dl>
HTML,
            'params' => ['prepare_content' => 0],
        ],
        [
            'key' => 'visit-callout', 'module' => 'mod_custom', 'title' => 'MUF — Visit: becoming a member',
            'position' => 'sidebar-right', 'showtitle' => 1, 'ordering' => 2, 'assignment' => 2, 'assigned' => [$visitId],
            'content' => <<<HTML
<p>Members enter free, always, and receive the quarterly <em>Register of Regrets</em>, the museum's journal of newly accessioned disappointments.</p>
<p><a class="btn btn-outline-primary btn-sm" href="/plan-your-visit#faq">Membership FAQ →</a></p>
HTML,
            'params' => ['prepare_content' => 0],
        ],
    ];
}

function stepModules(): void
{
    title('Modules');

    global $db, $dryRun, $state;

    foreach (moduleFixtures() as $def) {
        ensureModule($def);
    }

    // Unpublish sample site modules that this provisioning does not manage.
    if (!$dryRun) {
        $managed = implode(',', array_map('intval', $state['moduleIds']));
        $sql     = "UPDATE #__modules SET published = 0 WHERE client_id = 0 AND note <> 'muf-provisioned'";
        if ($managed !== '') {
            $sql .= ' AND id NOT IN (' . $managed . ')';
        }
        $db->setQuery($sql);
        $db->execute();
        out('Unpublished unmanaged sample site modules.', 'ok');
    }
}

// ---------------------------------------------------------------------------
// Runner
// ---------------------------------------------------------------------------
$steps = [
    'template' => 'stepTemplate',
    'categories' => 'stepCategories',
    'fields' => 'stepFields',
    'articles' => 'stepArticles',
    'routes' => 'stepResolveRoutes',
    'menus' => 'stepMenus',
    'install-module' => 'stepInstallModule',
    'modules' => 'stepModules',
];

out(($dryRun ? 'DRY RUN — ' : '') . 'Provisioning The Museum of Unbuilt Futures on ' . JPATH_BASE);

// The dispatcher normally defines these per request; the provisioner only
// drives com_content's administrator model, so point them there.
if (!defined('JPATH_COMPONENT')) {
    define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_content');
}
if (!defined('JPATH_COMPONENT_SITE')) {
    define('JPATH_COMPONENT_SITE', JPATH_ROOT . '/components/com_content');
}
if (!defined('JPATH_COMPONENT_ADMINISTRATOR')) {
    define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_content');
}

// Component table classes live outside the core autoloader; load the ones we
// instantiate directly (their parent classes and interfaces come from core).
require_once JPATH_ADMINISTRATOR . '/components/com_fields/src/Table/FieldTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_fields/src/Table/GroupTable.php';
require_once JPATH_ADMINISTRATOR . '/components/com_menus/src/Table/MenuTypeTable.php';

// In the console context, component service providers do not register their
// PSR-4 namespaces with the Composer autoloader, so bootComponent() cannot
// resolve the component's own classes. Register the src/ directories for the
// components we drive, then booting works exactly as on the web.
$composerLoader = require JPATH_LIBRARIES . '/vendor/autoload.php';
$registerComponent = static function (string $component) use ($composerLoader): void {
    $base = 'Joomla\\Component\\' . ucfirst($component) . '\\';
    if (is_dir(JPATH_ADMINISTRATOR . '/components/com_' . $component . '/src')) {
        $composerLoader->addPsr4($base . 'Administrator\\', JPATH_ADMINISTRATOR . '/components/com_' . $component . '/src/');
    }
    if (is_dir(JPATH_ROOT . '/components/com_' . $component . '/src')) {
        $composerLoader->addPsr4($base . 'Site\\', JPATH_ROOT . '/components/com_' . $component . '/src/');
    }
};
foreach (glob(JPATH_ADMINISTRATOR . '/components/com_*', GLOB_ONLYDIR) as $dir) {
    $registerComponent(substr(basename($dir), 4));
}

// Enabled plugins: register their src/ namespaces the way the web bootstrap
// would, so content events (which the models trigger) can load plugins.
foreach ($db->setQuery(
    $db->getQuery(true)->select(['folder', 'element'])->from('#__extensions')
        ->where("type = 'plugin'")->where('enabled = 1')
)->loadObjectList() as $plugin) {
    $pluginDir = JPATH_PLUGINS . '/' . $plugin->folder . '/' . $plugin->element;
    $manifest  = $pluginDir . '/' . $plugin->element . '.xml';

    if (!is_file($manifest)) {
        continue;
    }

    $xml = simplexml_load_file($manifest);
    $namespace = $xml && $xml->namespace ? trim((string) $xml->namespace) : '';
    $srcPath   = $xml && $xml->namespace && $xml->namespace['path']
        ? $pluginDir . '/' . trim((string) $xml->namespace['path']) . '/'
        : $pluginDir . '/src/';

    if ($namespace !== '' && is_dir($srcPath)) {
        $composerLoader->addPsr4($namespace . '\\', $srcPath);
    }
}

// Component table classes (FieldTable, MenuTypeTable, …) register their
// namespace autoloaders when the component boots — do that up front.
foreach (['com_content', 'com_fields', 'com_menus', 'com_modules', 'com_templates'] as $component) {
    try {
        \Joomla\CMS\Factory::getApplication()->bootComponent($component);
    } catch (\Throwable $e) {
        out("Could not boot {$component}: " . $e->getMessage(), 'warn');
    }
}

foreach ($steps as $name => $callable) {
    if ($stepFilter && $stepFilter !== $name) {
        continue;
    }
    $callable();
}

if (!$dryRun) {
    // Clear the runtime caches so the new structure is picked up immediately.
    foreach ([JPATH_ROOT . '/cache', JPATH_ADMINISTRATOR . '/cache'] as $cacheDir) {
        if (is_dir($cacheDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }
        }
    }
    out('Cleared site caches.', 'ok');
}

out('Provisioning complete.');
