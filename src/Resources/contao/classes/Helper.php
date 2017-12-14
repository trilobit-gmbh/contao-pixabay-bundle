<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Trilobit\PixabayBundle;

use Contao\DC_File;
use Contao\DC_Table;
use StringUtil;
use Symfony\Component\Yaml\Yaml;


/**
 * Class Helper
 * @package Trilobit\PixabayBundle
 *
 * @author trilobit GmbH <https://github.com/trilobit-gmbh>
 */
class Helper
{

    /**
     * @return string
     */
    public static function getVendowDir()
    {
        return dirname(dirname(__FILE__));
    }


    /**
     * @return mixed
     */
    public static function getConfigData()
    {
        $strYml = file_get_contents(self::getVendowDir() . '/../config/config.yml');

        return Yaml::parse($strYml)['trilobit']['pixabay'];
    }


    /**
     * @return mixed
     */
    public static function getCacheData($strCacheFile)
    {
        // prepare cache controll
        $strCachePath = \StringUtil::stripRootDir(\System::getContainer()->getParameter('kernel.cache_dir'));
        $strCacheFile = $strCachePath . '/contao/pixabay/' . $strCacheFile . '.json';

        // Load the cached result
        if (file_exists(TL_ROOT . '/' . $strCacheFile))
        {
            $objFile = new \File($strCacheFile);

            return json_decode($objFile->getContent(), true);
        }

        return array();
    }


    /**
     * @return mixed
     */
    public static function generateFilterPalette()
    {
        \Controller::loadLanguageFile('tl_pixabay');
        \Contao\Controller::loadDataContainer('tl_pixabay');

        $objPixabay = new DC_File('tl_pixabay');

        return preg_replace('/^(.*?)<fieldset (.*)<\/fieldset>(.*)?$/si', '<fieldset $2</fieldset>', $objPixabay->edit());
    }
}
