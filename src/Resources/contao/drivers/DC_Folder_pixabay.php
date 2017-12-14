<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Trilobit\PixabayBundle;

use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Util\SymlinkUtil;
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Contao\System;
use Environment;
use Imagine\Gd\Imagine;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Trilobit\PixabayBundle\PixabayZone;


/**
 * Class DC_Folder_pixabay
 * @package Trilobit\PixabayBundle
 *
 * @author trilobit GmbH <https://github.com/trilobit-gmbh>
 */
class DC_Folder_pixabay extends \DC_Folder
{

    /**
     * @param string $strMessage
     * @param string $strSource
     * @param bool $blnIsAjax
     * @return string
     * @throws \Exception
     */
    public function pixabay($strMessage='', $strSource='', $blnIsAjax=false)
    {
        $strFolder = \Input::get('pid', true);

        if (!file_exists(TL_ROOT . '/' . $strFolder) || !$this->isMounted($strFolder))
        {
            throw new AccessDeniedException('Folder "' . $strFolder . '" is not mounted or is not a directory.');
        }

        if (!preg_match('/^'.preg_quote(Config::get('uploadPath'), '/').'/i', $strFolder))
        {
            throw new AccessDeniedException('Parent folder "' . $strFolder . '" is not within the files directory.');
        }

        // Empty clipboard
        /** @var SessionInterface $objSession */
        $objSession = \System::getContainer()->get('session');

        $arrClipboard = $objSession->get('CLIPBOARD');
        $arrClipboard[$this->strTable] = array();
        $objSession->set('CLIPBOARD', $arrClipboard);

        /** @var FileUpload $objUploader */
        $objUploader = new PixabayZone();

        // Process the uploaded files
        if (\Input::post('FORM_SUBMIT') == 'tl_upload')
        {
            // Generate the DB entries
            if ($this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($strFolder))
            {
                $arrApiData = Helper::getCacheData(\Input::post('tl_pixabay_cache'));

                $arrApiDataHighResolution = array();

                $blnHighResolution = Config::get('pixabayHighResolution');
                $strImageSource    = (Config::get('pixabayImageSource')    !== '' ? Config::get('pixabayImageSource') : 'largeImageURL');


                if (   $blnHighResolution
                    && isset($arrApiData['__api__']['parameter']['q'])
                )
                {
                    $arrApiData['__api__']['parameter']['response_group'] = 'high_resolution';

                    $arrApiDataHighResolution = PixabayApi::search(false, $arrApiData['__api__']['parameter']);
                }

                if (empty($arrApiData))
                {
                    \Message::addError($GLOBALS['TL_LANG']['ERR']['emptyUpload']);
                    $this->reload();
                }

                // prepare default result
                foreach ($arrApiData['hits'] as $value)
                {
                    if (!in_array($value['id'], \Input::post('tl_pixabay_imageIds'))) continue;

                    $arrPathParts    = pathinfo(urldecode($value['webformatURL']));
                    $strFileNameTmp  = $strFolder . '/' . $arrPathParts['basename'];

                    $arrPathPartsNew = pathinfo(urldecode($value['pageURL']));
                    $strFileNameNew  = $strFolder . '/' . $arrPathPartsNew['basename'] . '.' . $arrPathParts['extension'];

                    $arrApiData['id'][$value['id']] = array
                    (
                        'files' => array
                        (
                            'api'      => $strFileNameTmp,
                            'download' => preg_replace('/^(.*)_(.*?)\.(.*?)$/', '$1_960.$3', $value['webformatURL']),
                            'contao'   => $strFileNameNew,
                        ),
                        'values'       => $value,
                    );

                    // update with high resolution result
                    if ($blnHighResolution && count($arrApiDataHighResolution['hits']))
                    {

                        foreach ($arrApiDataHighResolution['hits'] as $valueHighResolution)
                        {
                            $arrHighResolution = pathinfo($valueHighResolution['webformatURL']);
                            $arrDefault = pathinfo($value['webformatURL']);

                            $intLength = 14;

                            if (substr($arrHighResolution['basename'], 0, $intLength) == substr($arrDefault['basename'], 0, $intLength))
                            {
                                $arrApiData['id'][$value['id']]['values'] = array_merge($arrApiData['id'][$value['id']]['values'], $valueHighResolution);
                                $arrApiData['id'][$value['id']]['files']['download'] = $valueHighResolution[$strImageSource];

                                break;
                            }
                        }
                    }
                }

                // Upload the files
                $arrUploaded = array();

                foreach (\Input::post('tl_pixabay_imageIds') as $value)
                {
                    $strFileTmp      = 'system/tmp/' . md5(uniqid(mt_rand(), true));
                    $strFileDownload = $arrApiData['id'][$value]['files']['download'];
                    $strFileContao   = $arrApiData['id'][$value]['files']['contao'];

                    // get files
                    $stream = file_get_contents($strFileDownload);

                    $fileHandle = fopen(TL_ROOT . '/' . $strFileTmp, "w");

                    fwrite($fileHandle, $stream);
                    fclose($fileHandle);

                    // move file to target
                    $this->import('Files');

                    // Set CHMOD and resize if neccessary
                    if ($this->Files->rename($strFileTmp, $strFileContao))
                    {
                        $this->Files->chmod($strFileContao, Config::get('defaultFileChmod'));

                        $objFile = \Dbafs::addResource($strFileContao);

                        $objFile->meta = serialize(array(
                            $arrApiData['__api__']['parameter']['lang'] => array
                            (
                                'title' => 'ID: ' . $value
                                    . ' | '
                                    . 'Tags: ' . $arrApiData['id'][$value]['values']['tags']
                                    . ' | '
                                    . 'User: ' . $arrApiData['id'][$value]['values']['user'],
                                'alt'   => 'Pixabay: ' . $arrApiData['id'][$value]['values']['pageURL'],
                            )
                        ));

                        $objFile->save();

                        // Notify the user
                        \Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['fileUploaded'], $strFileContao));

                        System::log('File "' . $strFileContao . '" has been uploaded', __METHOD__, TL_FILES);


                        $arrUploaded[] = $strFileContao;
                    }
                }

                if (empty($arrUploaded) && !$objUploader->hasError())
                {
                    \Message::addError($GLOBALS['TL_LANG']['ERR']['emptyUpload']);
                    $this->reload();
                }

            }
            else
            {
                // Not DB-assisted, so just upload the file
                $arrUploaded = $objUploader->uploadTo($strFolder);
            }

            // HOOK: post upload callback
            if (isset($GLOBALS['TL_HOOKS']['postUpload']) && is_array($GLOBALS['TL_HOOKS']['postUpload']))
            {
                foreach ($GLOBALS['TL_HOOKS']['postUpload'] as $callback)
                {
                    if (is_array($callback))
                    {
                        $this->import($callback[0]);
                        $this->{$callback[0]}->{$callback[1]}($arrUploaded);
                    }
                    elseif (is_callable($callback))
                    {
                        $callback($arrUploaded);
                    }
                }
            }

            // Update the hash of the target folder
            if ($this->blnIsDbAssisted && \Dbafs::shouldBeSynchronized($strFolder))
            {
                \Dbafs::updateFolderHashes($strFolder);
            }

            // Redirect or reload
            if (!$objUploader->hasError())
            {
                if ($blnIsAjax)
                {
                    throw new ResponseException(new Response(\Message::generateUnwrapped(), 201));
                }

                $strCache = \Input::post('tl_pixabay_cache');

                // Do not purge the html folder (see #2898)
                if (isset($_POST['uploadNback']) && !$objUploader->hasResized())
                {
                    \Message::reset();
                    $this->redirect($this->getReferer());
                }

                $arrUnsetParameter = array_merge(array('cache', 'FORM_SUBMIT', 'REQUEST_TOKEN', 'MAX_FILE_SIZE', 'action', 'upload', 'tl_pixabay_images', 'tl_pixabay_cache', 'pixabay_search'), array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['api']));

                $arrRequest = $_REQUEST;

                foreach ($arrUnsetParameter as $key)
                {
                    unset($arrRequest[$key]);
                }

                $arrRequest['cache'] = $strCache;

                $this->redirect('contao?' . http_build_query($arrRequest));

                $this->reload();
            }
        }

        // Submit buttons
        $arrButtons = array();
        $arrButtons['upload'] = '<button type="submit" name="upload" class="tl_submit" accesskey="s">'.$GLOBALS['TL_LANG'][$this->strTable]['upload'].'</button>';
        $arrButtons['uploadNback'] = '<button type="submit" name="uploadNback" class="tl_submit" accesskey="c">'.$GLOBALS['TL_LANG'][$this->strTable]['uploadNback'].'</button>';

        // Call the buttons_callback (see #4691)
        if (is_array($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback']))
        {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['edit']['buttons_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $arrButtons = $this->{$callback[0]}->{$callback[1]}($arrButtons, $this);
                }
                elseif (is_callable($callback))
                {
                    $arrButtons = $callback($arrButtons, $this);
                }
            }
        }

        if (count($arrButtons) < 3)
        {
            $strButtons = implode(' ', $arrButtons);
        }
        else
        {
            $strButtons = array_shift($arrButtons) . ' ';
            $strButtons .= '<div class="split-button">';
            $strButtons .= array_shift($arrButtons) . '<button type="button" id="sbtog">' . \Image::getHtml('navcol.svg') . '</button> <ul class="invisible">';

            foreach ($arrButtons as $strButton)
            {
                $strButtons .= '<li>' . $strButton . '</li>';
            }

            $strButtons .= '</ul></div>';
        }

        return $strMessage . \Message::generate() . '
<div id="tl_buttons">
<a href="'.$this->getReferer(true).'" class="header_back" title="'.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>
<form action="'.ampersand(Environment::get('request'), true).'" id="'.$this->strTable.'" class="tl_form tl_edit_form" method="post"'.(!empty($this->onsubmit) ? ' onsubmit="'.implode(' ', $this->onsubmit).'"' : '').' enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_upload">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
<input type="hidden" name="MAX_FILE_SIZE" value="'. Config::get('maxFileSize').'">
<div class="tl_tbox">
<div class="widget">
  <h3>'.$GLOBALS['TL_LANG']['tl_pixabay']['fileupload'][0].'</h3>
</div>
</div>
'.$objUploader->generateMarkup().'
</div>
<div class="tl_formbody_submit">
<div class="tl_submit_container">
  ' . $strButtons . '
</div>
</div>
</form>';
    }
}
