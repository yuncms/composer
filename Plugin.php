<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;

/**
 * Class Plugin
 *
 * @author Tongle Xu <xutongle@gmail.com>
 * @since 3.0
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public function activate(Composer $composer, IOInterface $io)
    {

    }

    /**
     * Listen events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoloadDump',
        ];
    }

    /**
     * @param \Composer\Script\Event $event
     */
    public function postAutoloadDump(ScriptEvent $event)
    {
        $vendorDir = rtrim($event->getComposer()->getConfig()->get('vendor-dir'), '/');
        $manifest = new ManifestManager($vendorDir);
        $manifest->build();
    }
}