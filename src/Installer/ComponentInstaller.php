<?php
namespace Cake\Composer\Installer;

use Cake\Composer\Installer\ComponentInstallerConfigureTrait;
use Composer\Installer\LibraryInstaller;
use Composer\Script\Event;
use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;

class ComponentInstaller extends LibraryInstaller
{

    use ComponentInstallerConfigureTrait;

    public function supports($packageType)
    {
        $rootPackage = isset($this->composer) ? $this->composer->getPackage() : null;
        if (isset($rootPackage)) {
            while ($rootPackage instanceof AliasPackage) {
                $rootPackage = $rootPackage->getAliasOf();
            }
            if (method_exists($rootPackage, 'setScripts')) {
                $scripts = $rootPackage->getScripts();
                $scripts['post-autoload-dump']['component-installer'] = 'Cake\\Composer\\Installer\\ComponentInstaller::postAutoloadDump';
                $rootPackage->setScripts($scripts);
            }
        }
        return $packageType == 'component';
    }

    /**
     * Called whenever composer (re)generates the autoloader
     *
     * @param Event $event the composer event object
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        $composer = $event->getComposer();
        $config = $composer->getConfig();

        $vendorDir = realpath($config->get('vendor-dir'));
        if($config->has('component')){
            static::setConfig($config->get('component'));
        }

        $packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();
        $components = static::determineComponents($packages);
        $webrootDir = dirname($vendorDir) . DIRECTORY_SEPARATOR . 'webroot';

        $config = static::processComponents($components, $vendorDir, $webrootDir);

        $configFile = static::configFile($vendorDir);
        static::writeConfigFile($configFile, $config);
    }

    public static function determineComponents($packages)
    {
        $components = [];

        foreach ($packages as $package) {
            if ($package->getType() !== 'component') {
                continue;
            }
            $extra = $package->getExtra();
            if(isset($extra['component'])){
                $components[$package->getName()] = $extra['component'];
            }
        }
        return $components;
    }

    public static function processComponents($components, $vendorDir = 'vendor', $webrootDir = 'webroot')
    {
        $config = [];
        $componentDir = static::$config['component-dir'];
        foreach($components as $name=>$component){
            //Set component name
            list($vendor, $vendorComponentName) = explode('/', $name);
            $componentName = $vendorComponentName;
            if(isset($component['name'])){
                $componentName = $component['name'];
            }
            $componentPath = $vendorDir . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR . $vendorComponentName;

            $files = array_merge(
                (isset($component['scripts']) ? $component['scripts'] : array()),
                (isset($component['styles']) ? $component['styles'] : array()),
                (isset($component['files']) ? $component['files'] : array())
            );
            $files = static::buildFilesList($files, $componentPath);

            foreach($files as $type=>$assets){
                $targetDir = $webrootDir . DIRECTORY_SEPARATOR . $type;
                if($componentDir !== false){
                    $targetDir .= DIRECTORY_SEPARATOR . $componentDir;
                }
                $targetDir .= DIRECTORY_SEPARATOR . $vendorComponentName;
                $config[$name][$type] = str_replace($webrootDir . DIRECTORY_SEPARATOR, '', $targetDir);
                foreach($assets as $file){
                    $source = $componentPath.DIRECTORY_SEPARATOR.$file;
                    $target = $targetDir . DIRECTORY_SEPARATOR . preg_replace('/^'.$type.'\\'.DIRECTORY_SEPARATOR.'/', '', $file);
                    $_targetDir = dirname($target);
                    if(!is_dir($_targetDir)){
                        mkdir($_targetDir, 0755, true);
                    }
                    if(file_exists($source)&&is_dir($_targetDir)){
                        copy($source, $target);
                    }
                }
            }
        }
        return $config;
    }

    protected static function buildFilesList($files, $componentPath)
    {
        $_files = [];
        $lastType = '';
        foreach($files as $file){
            if(strpos($file, '*')){
                $file = static::glob($componentPath.DIRECTORY_SEPARATOR.$file);
                foreach($file as $i=>$asset){
                    $file[$i] = str_replace($componentPath.DIRECTORY_SEPARATOR, '', $asset);
                }
            } else {
                $file = (array)$file;
            }

            foreach($file as $asset){
                if(static::excluded($asset)){
                    continue;
                }
                list($type) = explode('/', $asset);
                if(!array_keys(static::getSupportedTypes(), $type)){
                    $type = pathinfo($componentPath.DIRECTORY_SEPARATOR.$asset, PATHINFO_EXTENSION);
                    if($type == 'map'){
                        $type = $lastType;
                    } else {
                        foreach(static::getSupportedTypes() as $_type=>$extensions){
                            if(in_array($type, $extensions)){
                                $type = $_type;
                                break;
                            }
                        }
                    }
                }
                if(!isset($_files[$type])){
                    $_files[$type] = [];
                }
                $_files[$type][] = $asset;
                $lastType = $type;
            }
        }
        return $_files;
    }

    protected static function excluded($path)
    {
        $exclude = static::getConfigParam('exclude');
        if(empty($exclude)){
            return false;
        }
        if(empty($path)){
            return true;
        }
        foreach($exclude as $regex){
            if(preg_match("/".$regex."/i", $path)){
                return true;
            }
        }
        return false;
    }

    protected static function glob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        if (strpos($pattern, '**')) {
            $dirs = glob(dirname($pattern).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR|GLOB_NOSORT);
            if ($dirs) {
                foreach ($dirs as $dir) {
                    $files = array_merge($files, static::glob($dir.DIRECTORY_SEPARATOR.basename($pattern), $flags));
                }
            }
        }
        return array_filter($files, 'is_file');
    }


}
