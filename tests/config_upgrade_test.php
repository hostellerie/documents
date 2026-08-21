<?php

/*
 * Standalone regression test for the Documents 1.1.8 configuration upgrade.
 * Compatible with PHP 5.6+ and intentionally independent from Geeklog runtime.
 */

$_SERVER['PHP_SELF'] = 'tests/config_upgrade_test.php';
require_once dirname(__DIR__) . '/install_defaults.php';

class DocumentsConfigUpgradeTestDouble
{
    public $config;
    public $adds;

    public function __construct($config)
    {
        $this->config = $config;
        $this->adds = array();
    }

    public function get_config($group)
    {
        return $this->config;
    }

    public function add(
        $paramName,
        $defaultValue,
        $type,
        $subgroup,
        $fieldset = null,
        $selectionArray = null,
        $sort = 0,
        $set = true,
        $group = 'Core',
        $tab = null
    ) {
        $this->adds[] = $paramName;
        if ($set) {
            $this->config[$paramName] = $defaultValue;
        }
    }
}

function documents_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

/* Existing customized values must never be replaced. */
$existing = array(
    'fs_images' => null,
    'max_image_width' => 1234,
    'max_image_height' => 2345,
    'max_image_size' => 3456789,
);
$config = new DocumentsConfigUpgradeTestDouble($existing);
DOCUMENTS_addImageConfigItems($config, 'documents');

documents_test_assert(count($config->adds) === 0, 'existing image settings were re-added');
documents_test_assert($config->config['max_image_width'] === 1234, 'custom width was changed');
documents_test_assert($config->config['max_image_height'] === 2345, 'custom height was changed');
documents_test_assert($config->config['max_image_size'] === 3456789, 'custom file size was changed');

/* A partial configuration receives only the missing keys. */
$partial = array(
    'max_image_width' => 1600,
);
$config = new DocumentsConfigUpgradeTestDouble($partial);
DOCUMENTS_addImageConfigItems($config, 'documents');

documents_test_assert($config->config['max_image_width'] === 1600, 'partial custom width was changed');
documents_test_assert(in_array('fs_images', $config->adds, true), 'missing image fieldset was not added');
documents_test_assert(in_array('max_image_height', $config->adds, true), 'missing image height was not added');
documents_test_assert(in_array('max_image_size', $config->adds, true), 'missing image size was not added');
documents_test_assert(!in_array('max_image_width', $config->adds, true), 'existing image width was re-added');

/* Empty configuration receives all defaults. */
$config = new DocumentsConfigUpgradeTestDouble(array());
DOCUMENTS_addImageConfigItems($config, 'documents');

documents_test_assert(count($config->adds) === 4, 'fresh image configuration is incomplete');
documents_test_assert($config->config['max_image_width'] === 3000, 'default width mismatch');
documents_test_assert($config->config['max_image_height'] === 3000, 'default height mismatch');
documents_test_assert($config->config['max_image_size'] === 4194304, 'default file size mismatch');

/* Rerunning the helper must be idempotent. */
$firstAdds = count($config->adds);
DOCUMENTS_addImageConfigItems($config, 'documents');
documents_test_assert(count($config->adds) === $firstAdds, 'configuration upgrade is not idempotent');

echo "Documents configuration upgrade tests: PASS\n";
