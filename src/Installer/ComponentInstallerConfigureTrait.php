<?php
namespace Cake\Composer\Installer;

trait ComponentInstallerConfigureTrait {

    static protected $types = [
        'js' => ['js'],
        'css' => ['css'],
        'fonts' => ['eot', 'otf', 'svg', 'ttf', 'woff', 'woff2'],
        'less' => ['less'],
        'sass' => ['sass'],
        'scss' => ['scss'],
        'img' => ['png', 'gif', 'jpg', 'swf'],
    ];

    static public $config = [
        'component-dir' => 'vendor',
        'exclude' => [],
    ];

    public static function getSupportedTypes(){
        return static::$types;
    }

    public static function getSupportedExtensions(){
        $extensions = [];
        foreach(static::getSupportedTypes() as $type=>$_extensions){
            $extensions = array_merge($extensions, $_extensions);
        }
        return $extensions;
    }

    public static function getConfig(){
        return static::$config;
    }

    public static function setConfig(array $config){
        static::$config = (array)$config + static::getConfig();
    }

    public static function getConfigParam($key){
        if(isset(static::$config[$key])){
            return static::$config[$key];
        }
        return null;
    }

    private static function configFile($vendorDir)
    {
        return $vendorDir . DIRECTORY_SEPARATOR . 'cakephp-components.php';
    }

    public static function writeConfigFile($configFile, $config)
    {
        $root = dirname(dirname($configFile));
        $data = var_export($config, true);
        $data = str_replace(['array(', 'array ('], '[', $data);
        $data = str_replace(')', ']', $data);
        $contents = <<<PHP
<?php
return $data;
PHP;
        file_put_contents($configFile, $contents);
    }
}
