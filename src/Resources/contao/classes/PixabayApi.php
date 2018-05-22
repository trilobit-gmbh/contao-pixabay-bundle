<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2014 Leo Feyer
 *
 * @package     Trilobit
 * @author      trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license     LGPL-3.0-or-later
 * @copyright   trilobit GmbH
 */

namespace Trilobit\PixabayBundle;

use Contao\Config;
use Contao\File;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\PixabayBundle\Helper;

/**
 * Class PixabayApi
 * @package Trilobit\PixabayBundle
 *
 * @author trilobit GmbH <https://github.com/trilobit-gmbh>
 */
class PixabayApi
{

    /**
     * @return mixed
     */
    protected function prepareApiPrameter()
    {
        // check query
        if (   (!Input::get('q')  || Input::get('q') === '')
            && (!Input::get('id') || Input::get('id') === '')
        )
        {
            self::apiResponse();
        }


        $strResponseGroup = 'image_details';

        if (   Input::get('response_group')
            && in_array(Input::get('response_group'), array('image_details', 'high_resolution')))
        {
            $strResponseGroup = Input::get('response_group');
        }

        // prepare parameter for api call
        $arrApiParameter = Helper::getConfigData()['api'];
        $arrApiParameter['response_group'] = $strResponseGroup;

        foreach ($arrApiParameter as $key => $value)
        {
            if (Input::get($key) && Input::get($key) !== '')
            {
                if (strtolower($value) === 'bool')
                {
                    $arrApiParameter[$key] = 'true';
                }
                else if (strtolower($value) === 'int')
                {
                    $arrApiParameter[$key] = intval(Input::get($key), 10);
                }
                else
                {
                    $arrApiParameter[$key] = Input::get($key);
                }

                continue;
            }

            unset($arrApiParameter[$key]);
        }

        return $arrApiParameter;
    }

    /**
     * @param bool $blnIsAjax
     * @param array $arrApiParameter
     * @return array|mixed|null
     * @throws \Exception
     */
    public function search($blnIsAjax=true, $arrApiParameter=array())
    {
        $strApiUrl = Config::get('pixabayApiUrl');
        $strApiKey = Config::get('pixabayApiKey');

        // check api-url, api-key
        if (   $strApiUrl === ''
            || $strApiKey === ''
        )
        {
            if ($blnIsAjax)
            {
                self::apiResponse();
            }

            return array();
        }


        if ($blnIsAjax)
        {
            $arrApiParameter = self::prepareApiPrameter();
        }


        // prepare cache controll
        $strCachePath = StringUtil::stripRootDir(System::getContainer()->getParameter('kernel.cache_dir'));

        $arrResult = null;
        $strChecksum = md5(implode($arrApiParameter));

        $strCacheFile = $strCachePath . '/contao/pixabay/' . $strChecksum . '.json';

        // Load the cached result
        if (file_exists(TL_ROOT . '/' . $strCacheFile))
        {
            $objFile = new File($strCacheFile);

            if ($objFile->mtime > time() - 60*60*24)
            {
                $arrResult = json_decode($objFile->getContent(), true);
                $arrResult['__api__']['cachedResult'] = true;
            }
            else
            {
                $objFile->delete();
            }
        }

        // Cache the result
        if ($arrResult === null)
        {
            try
            {
                $arrResult = json_decode(self::apiCall($strApiUrl, $strApiKey, $arrApiParameter), true);

                $arrResult['__api__']['url']       = $strApiUrl;
                $arrResult['__api__']['key']       = $strApiKey;
                $arrResult['__api__']['parameter'] = $arrApiParameter;
                $arrResult['__api__']['cache']     = $strChecksum;
                $arrResult['__api__']['tstamp']    = time();

                File::putContent($strCacheFile, json_encode($arrResult));

                $arrResult['__api__']['cachedResult'] = false;
            }
            catch (\Exception $e)
            {
                System::log('Pixabay search failed: ' . $e->getMessage(), __METHOD__, TL_ERROR);
                $arrResult = array();

                $arrResult['__api__']['exceptionId']      = explode('|', $e->getMessage())[0];
                $arrResult['__api__']['exceptionMessage'] = explode('|', $e->getMessage())[1];
            }
        }

        // Response
        if ($blnIsAjax)
        {
            self::apiResponse($arrResult);
        }

        return $arrResult;
    }

    /**
     * @param $strApiUrl
     * @param $strApiKey
     * @param $arrApiParameter
     * @return mixed
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    protected function apiCall($strApiUrl, $strApiKey, $arrApiParameter)
    {
        $strUrl = $strApiUrl . '?key=' . $strApiKey . '&' . http_build_query($arrApiParameter);

        $objCurl = curl_init();

        curl_setopt($objCurl, CURLOPT_URL, $strUrl);

        curl_setopt($objCurl, CURLOPT_USERAGENT,      "Contao Pixabay API");
        curl_setopt($objCurl, CURLOPT_COOKIEJAR,      TL_ROOT . '/system/tmp/curl.cookiejar.txt');
        curl_setopt($objCurl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($objCurl, CURLOPT_ENCODING,       "");
        curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($objCurl, CURLOPT_AUTOREFERER,    true);
        curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);    # required for https urls
        curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($objCurl, CURLOPT_TIMEOUT,        30);
        curl_setopt($objCurl, CURLOPT_MAXREDIRS,      10);

        $returnValue = curl_exec($objCurl);
        $returnCode  = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);

        curl_close($objCurl);

        if ($returnCode !== 200)
        {
            throw new Exception($returnCode . '|' . $returnValue);

            /*
            $returnValue = array();
            $returnValue['__api__']['request'] = $strUrl;
            $returnValue['__api__']['exception'] = $returnCode;
            self::apiResponse($returnValue);
            */
        }

        //$returnValue['__api__']['request'] = $strUrl;

        return $returnValue;
    }

    /**
     * @param array $arrResult
     */
    protected function apiResponse($arrResult=array())
    {
        $response = new Response();

        $response->setContent(json_encode($arrResult));

        $response->setStatusCode(Response::HTTP_OK);

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Pragma',       'no-cache');
        $response->headers->set('Expires',      '0');

        $response->send();

        die();
    }
}
