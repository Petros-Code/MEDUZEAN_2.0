<?php
namespace Meduzean\EanManager\Core;

defined('ABSPATH') || exit;

class Constants
{
    public const VERSION = '1.2.0';
    public const DB_VERSION_OPTION = 'meduzean_db_version';
    public const PLUGIN_SLUG = 'meduzean-ean';
    public const TEXT_DOMAIN = 'meduzean';
    public const NAMESPACE = 'Meduzean\\EanManager\\';
    
    private static ?string $pluginFile = null;
    private static ?string $pluginDir = null;
    private static ?string $pluginUrl = null;
    
    public static function setPluginFile(string $file): void
    {
        self::$pluginFile = $file;
    }
    
    public static function getPluginFile(): string
    {
        return self::$pluginFile ?? '';
    }
    
    public static function getPluginDir(): string
    {
        if (self::$pluginDir === null && self::$pluginFile) {
            self::$pluginDir = plugin_dir_path(self::$pluginFile);
        }
        return self::$pluginDir ?? '';
    }
    
    public static function getPluginUrl(): string
    {
        if (self::$pluginUrl === null && self::$pluginFile) {
            self::$pluginUrl = plugin_dir_url(self::$pluginFile);
        }
        return self::$pluginUrl ?? '';
    }
}
