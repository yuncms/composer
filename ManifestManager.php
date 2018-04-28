<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\composer;

/**
 * Class ManifestManager
 *
 * @author Tongle Xu <xutongle@gmail.com>
 * @since 3.0
 */
class ManifestManager
{
    const PACKAGE_TYPE = 'yii2-extension';
    const EXTRA_FIELD = 'yuncms';
    const MIGRATION_FILE = 'yuncms/migrations.php';//全局迁移
    const EVENT_FILE = 'yuncms/events.php';//全局事件
    const TRANSLATE_FILE = 'yuncms/translates.php';//全局语言包
    const FRONTEND_MODULE_FILE = 'yuncms/frontend.php';//后端配置文件
    const BACKEND_MODULE_FILE = 'yuncms/backend.php';//前端配置文件

    /**
     * The vendor path.
     *
     * @var string
     */
    protected $vendorPath;

    /**
     * @param string $vendorPath
     */
    public function __construct(string $vendorPath)
    {
        $this->vendorPath = $vendorPath;
    }

    /**
     * Build the manifest file.
     */
    public function build()
    {
        $packages = [];
        if (file_exists($installed = $this->vendorPath . '/composer/installed.json')) {
            $packages = json_decode(file_get_contents($installed), true);
        }
        $manifests = ['migrations' => [], 'events' => [],'translations' => [], 'backend' => [], 'frontend' => []];
        foreach ($packages as $package) {
            if ($package['type'] === self::PACKAGE_TYPE && isset($package['extra'][self::EXTRA_FIELD])) {
                $manifest = $this->getManifest($package);

                if (isset($manifest['migrationPath'])) {//迁移
                    $manifests['migrations'][] = $manifest['migrationPath'];
                }
                if (isset($manifest['events'])) {
                    foreach ($manifest['events'] as $event) {
                        $manifests['events'][] = $event;
                    }
                }
                if (isset($manifest['translations'])) {
                    foreach ($manifest['translations'] as $id => $translation) {
                        $manifests['translations'][$id] = $translation;
                    }
                }
                if (isset($manifest['frontend']['class'])) {
                    $manifests['frontend'][$manifest['id']] = $manifest['frontend'];
                }
                if (isset($manifest['backend'])) {
                    $manifests['backend'][$manifest['id']] = $manifest['backend'];
                }
            }
        }

        //写清单文件
        $this->write(self::MIGRATION_FILE, $manifests['migrations']);
        $this->write(self::EVENT_FILE, $manifests['events']);
        $this->write(self::TRANSLATE_FILE, $manifests['translations']);
        $this->write(self::FRONTEND_MODULE_FILE, $manifests['frontend']);
        $this->write(self::BACKEND_MODULE_FILE, $manifests['backend']);
    }

    /**
     * 获取包清单
     * @param array $package
     * @return array
     */
    public function getManifest($package)
    {
        if (is_array($package['extra'][self::EXTRA_FIELD])) {
            return $package['extra'][self::EXTRA_FIELD];
        } else if (is_string($package['extra'][self::EXTRA_FIELD])) {
            $manifestFile = $this->vendorPath . DIRECTORY_SEPARATOR . $package['name'] . DIRECTORY_SEPARATOR . $package['extra'][self::EXTRA_FIELD];
            if (is_file($manifestFile)) {
                return include($manifestFile);
            }
        }
        return [];
    }

    /**
     * Write the manifest array to a file.
     * @param string $file
     * @param array $manifest
     */
    public function write($file, array $manifest)
    {
        $file = $this->vendorPath . '/' . $file;
        $array = var_export($manifest, true);
        file_put_contents($file, "<?php\n\nreturn $array;\n");
        $this->opcacheInvalidate($file);
    }

    /**
     * Disable opcache
     * @param string $file
     * @return void
     */
    protected function opcacheInvalidate($file)
    {
        // invalidate opcache of extensions.php if exists
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }
}