<?php
namespace Meduzean\EanManager\Core;

defined('ABSPATH') || exit;

class Autoloader
{
    private string $prefix;
    private string $baseDir;
    
    public function __construct(string $prefix, string $baseDir)
    {
        $this->prefix = $prefix;
        $this->baseDir = $baseDir;
    }
    
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    public function loadClass(string $class): void
    {
        if (strpos($class, $this->prefix) !== 0) {
            return;
        }
        
        $relativeClass = substr($class, strlen($this->prefix));
        $file = $this->baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Meduzean Autoload] Trying to load $class from $file");
        }
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
