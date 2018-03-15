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
    const TASK_FILE = 'yuncms/tasks.php';//计划任务
    const EVENT_FILE = 'yuncms/events.php';//计划任务

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
        $manifests = [];
        foreach ($packages as $package) {
            if ($package['type'] === self::PACKAGE_TYPE && isset($package['extra'][self::EXTRA_FIELD]) && isset($package['extra'][self::EXTRA_FIELD]['id'])) {
                $extra = $package['extra'][self::EXTRA_FIELD];
                if (isset($extra['migrationPath'])) {//迁移
                    $manifests['migration'][] = $extra['migrationPath'];
                }
                if (isset($extra['events'])) {
                    foreach ($extra['events'] as $event) {
                        $manifests['event'][] = $event;
                    }
                }
                if (isset($extra['tasks'])) {
                    foreach ($extra['tasks'] as $task) {
                        $manifests['task'][] = $task;
                    }
                }
            }
        }

        //写清单文件
        $this->write(self::MIGRATION_FILE, $manifests['migration']);
        $this->write(self::EVENT_FILE, $manifests['event']);
        $this->write(self::TASK_FILE, $manifests['task']);
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