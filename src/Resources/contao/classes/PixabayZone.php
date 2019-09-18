<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-pixabay-bundle
 */

namespace Trilobit\PixabayBundle;

use Contao\Config;
use Contao\Controller;
use Contao\Dbafs;
use Contao\Environment;
use Contao\FileUpload;
use Contao\Input;
use Contao\Message;
use Contao\System;

/**
 * Class PixabayZone.
 *
 * @author trilobit GmbH <https://github.com/trilobit-gmbh>
 */
class PixabayZone extends FileUpload
{
    /**
     * Check the uploaded files and move them to the target directory.
     *
     * @param string $strTarget
     *
     * @throws \Exception
     *
     * @return array
     */
    public function uploadTo($strTarget)
    {
        // Prepare file data
        $arrApiData = Helper::getCacheData(Input::post('tl_pixabay_cache'));

        $arrApiDataHighResolution = [];

        $blnHighResolution = Config::get('pixabayHighResolution');
        $strImageSource = (empty(Config::get('pixabayImageSource')) ? 'largeImageURL' : Config::get('pixabayImageSource'));

        if (empty($arrApiData)) {
            Message::addError($GLOBALS['TL_LANG']['ERR']['emptyUpload']);
            $this->reload();
        }

        if ('' === $strTarget || \Validator::isInsecurePath($strTarget)) {
            throw new \InvalidArgumentException('Invalid target path '.$strTarget);
        }

        $blnImageSource = true;

        foreach ($arrApiData['hits'] as $value) {
            if (!\in_array((string) $value['id'], Input::post('tl_pixabay_imageIds'), true)) {
                continue;
            }
            $arrPathParts = pathinfo(urldecode($value['webformatURL']));
            $strFileNameTmp = $strTarget.'/'.$arrPathParts['basename'];

            $arrPathPartsNew = pathinfo(urldecode($value['pageURL']));

            // Sanitize the filename
            try {
                $arrPathPartsNew['basename'] = \StringUtil::sanitizeFileName($arrPathPartsNew['basename']);
            } catch (\InvalidArgumentException $e) {
                \Message::addError($GLOBALS['TL_LANG']['ERR']['filename']);
                $this->blnHasError = true;

                continue;
            }

            $strFileNameNew = $strTarget.'/'.$arrPathPartsNew['basename'].'.'.$arrPathParts['extension'];

            $arrApiData['id'][$value['id']] = [
                'files' => [
                    'api' => $strFileNameTmp,
                    'contao' => $strFileNameNew,
                ],
                'values' => $value,
            ];

            $strDownload = $value[$strImageSource];

            if (empty($value[$strImageSource])) {
                $blnImageSource = false;
                $strDownload = preg_replace('/^(.*)_(.*?)\.(.*?)$/', '$1_960.$3', $value['webformatURL']);
            }

            $arrApiData['id'][$value['id']]['files']['download'] = $strDownload;
        }

        if (!$blnImageSource) {
            Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['imageSourceNotAvailable'], $strImageSource));
            System::log('Pixabay image source "'.$strImageSource.'" not available; extended "webformatURL" used instead', __METHOD__, TL_FILES);
        }

        // Upload the files
        $maxlength_kb = $this->getMaximumUploadSize();
        $maxlength_kb_readable = $this->getReadableSize($maxlength_kb);
        $arrUploaded = [];

        $arrLanguages = \Contao\Database::getInstance()
            ->prepare("SELECT COUNT(language) AS language_count, language FROM tl_page WHERE type='root' AND published=1 GROUP BY language ORDER BY language_count DESC")
            ->limit(1)
            ->execute()
            ->fetchAllAssoc();

        if (empty($arrLanguages[0]['language'])) {
            $arrLanguages[0]['language'] = 'en';
        }

        foreach (Input::post('tl_pixabay_imageIds') as $value) {
            $strFileTmp = 'system/tmp/'.md5(uniqid(mt_rand(), true));
            $strFileDownload = $arrApiData['id'][$value]['files']['download'];
            $strNewFile = $arrApiData['id'][$value]['files']['contao'];

            /*
            // get files
            $stream = file_get_contents($strFileDownload);

            $fileHandle = fopen(TL_ROOT.'/'.$strFileTmp, 'w');

            fwrite($fileHandle, $stream);
            fclose($fileHandle);
            */

            // file handle
            $fileHandle = fopen(TL_ROOT.'/'.$strFileTmp, 'w');

            // get file: curl
            $objCurl = curl_init($strFileDownload);

            curl_setopt($objCurl, CURLOPT_HEADER, false);
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_BINARYTRANSFER, true);

            curl_setopt($objCurl, CURLOPT_USERAGENT, 'Contao Pixabay API');
            curl_setopt($objCurl, CURLOPT_COOKIEJAR, TL_ROOT.'/system/tmp/curl.cookiejar.txt');
            curl_setopt($objCurl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($objCurl, CURLOPT_ENCODING, '');
            curl_setopt($objCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($objCurl, CURLOPT_AUTOREFERER, true);
            curl_setopt($objCurl, CURLOPT_SSL_VERIFYPEER, false);    // required for https urls
            curl_setopt($objCurl, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($objCurl, CURLOPT_TIMEOUT, 30);
            curl_setopt($objCurl, CURLOPT_MAXREDIRS, 10);

            $stream = curl_exec($objCurl);
            $returnCode = curl_getinfo($objCurl, CURLINFO_HTTP_CODE);

            // write
            fwrite($fileHandle, $stream);
            fclose($fileHandle);

            curl_close($objCurl);

            // move file to target
            $this->import('Files');

            // Set CHMOD and resize if neccessary
            if ($this->Files->rename($strFileTmp, $strNewFile)) {
                $this->Files->chmod($strNewFile, Config::get('defaultFileChmod'));

                $objFile = Dbafs::addResource($strNewFile);

                $objFile->meta = serialize([
                    $arrLanguages[0]['language'] => [
                        'title' => 'ID: '.$value
                            .' | '
                            .'Tags: '.$arrApiData['id'][$value]['values']['tags']
                            .' | '
                            .'User: '.$arrApiData['id'][$value]['values']['user'],
                        'alt' => 'Pixabay: '.$arrApiData['id'][$value]['values']['pageURL'],
                    ],
                ]);

                $objFile->save();

                // Notify the user
                Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['fileUploaded'], $strNewFile));

                System::log('File "'.$strNewFile.'" has been uploaded', __METHOD__, TL_FILES);

                // Resize the uploaded image if necessary
                $this->resizeUploadedImage($strNewFile);

                $arrUploaded[] = $strNewFile;
            }
        }

        if (empty($arrUploaded)) {
            Message::addError($GLOBALS['TL_LANG']['ERR']['emptyUpload']);
            $this->reload();
        }

        $this->blnHasError = false;

        return $arrUploaded;
    }

    public function generateMarkup()
    {
        Controller::loadLanguageFile('tl_pixabay');

        $arrCache = Helper::getCacheData(Input::get('cache'));
        $arrApiParameter = Helper::getConfigData()['api'];

        $arrGlobalsConfig = $GLOBALS['TL_CONFIG'];

        $pixabay_search = '';
        $blnPixabayCache = false;

        if (\count($arrCache)) {
            $blnPixabayCache = true;
            $pixabay_search = $arrCache['__api__']['parameter']['q'];
        }

        $this->import('BackendUser', 'User');

        foreach ($arrApiParameter as $key => $value) {
            $GLOBALS['TL_CONFIG'][$key] = $this->User->{('order' === $key ? 'priority' : $key)};

            if ($blnPixabayCache
                && isset($arrCache['__api__']['parameter'][$key])
                && '' !== $arrCache['__api__']['parameter'][$key]
            ) {
                if ('bool' === strtolower($value)) {
                    $GLOBALS['TL_CONFIG'][$key] = 1;
                } elseif ('int' === strtolower($value)) {
                    $GLOBALS['TL_CONFIG'][$key] = \intval($arrCache['__api__']['parameter'][$key], 10);
                } else {
                    $GLOBALS['TL_CONFIG'][$key] = $arrCache['__api__']['parameter'][$key];
                }
            }
        }

        // Generate the markup
        $return = '
<input type="hidden" name="action" value="pixabayupload">

<div id="pixabay_inform">
    <h2>'.$GLOBALS['TL_LANG']['tl_pixabay']['poweredBy'][0].'</h2>
    <br>
    <a href="https://pixabay.com" target="_blank" rel="noopener noreferrer"><img src="/bundles/trilobitpixabay/logo.png" width=100 height=100 style="margin-right:30px"></a>
    <a href="https://www.trilobit.de" target="_blank" rel="noopener noreferrer"><img src="/bundles/trilobitpixabay/trilobit_gmbh.svg" width="auto" height="50"></a><br>
    <div class="hint"><br><br><span>'.$GLOBALS['TL_LANG']['MSC']['pixabay']['hint'].'</span></div>
</div>

</div></div>

<div id="pixabay_form">
    <fieldset id="pal_pixabay_search_legend" class="tl_box">
        <legend onclick="AjaxRequest.toggleFieldset(this,\'pixabay_search_legend\',\'tl_pixabay\')">'.$GLOBALS['TL_LANG']['tl_pixabay']['pixabay_search_legend'].'</legend>
        <div class="w50 widget">
            <h3>'.$GLOBALS['TL_LANG']['tl_pixabay']['searchTerm'][0].'</h3>
            <input name="pixabay_search" type="text" value="'.$pixabay_search.'" class="tl_text search">
            <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_pixabay']['searchTerm'][1].'</p>
        </div>

        <div class="w50 widget">
            <h3>'.$GLOBALS['TL_LANG']['tl_pixabay']['pixabay']['searchPixabay'][0].'</h3>
            <button class="tl_submit">'.$GLOBALS['TL_LANG']['MSC']['pixabay']['searchPixabay'].'</button>
            <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_pixabay']['searchPixabay'][1].'</p>
        </div>
    </fieldset>

    '.Helper::generateFilterPalette().'

    <fieldset id="pal_pixabay_result_legend" class="tl_box collapsed">
        <legend onclick="AjaxRequest.toggleFieldset(this,\'pixabay_result_legend\',\'tl_pixabay\')">'.$GLOBALS['TL_LANG']['tl_pixabay']['pixabay_result_legend'].'</legend>
        <div class="widget clr" id="pixabay_images">
            <div class="widget"><p>'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</a></div>
        </div>
        <div class="tl_box clr" id="pixabay_pagination">
        </div>
    </fieldset>
</div>

<div><div>

<script>
    window.addEventListener("load", function(event) {
        //$$(\'div.tl_formbody_submit\').addClass(\'invisible\');
    });

    var pixabayImages       = $(\'pixabay_images\');
    var pixabayPagination   = $(\'pixabay_pagination\');
    var pixabayPage         = 1;
    var pixabayPages        = 1;
    var resultsPerPage      = \''.(floor(Config::get('resultsPerPage') / 4) * 4).'\';
    var language            = \''.$GLOBALS['TL_LANGUAGE'].'\';
    var strHtmlEmpty        = \'<div class="widget"><p>'.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['noResult']).'<\/p><\/div>\';
    var strHtmlGoToPage     = \''.sprintf(\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['goToPage']), '##PAGE##').'\';
    var strHtmlUser         = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['user'].'\';
    var strHtmlViews        = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['views'].'\';
    var strHtmlLikes        = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['likes'].'\';
    var strHtmlFavorites    = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['favorites'].'\';
    var strHtmlDownloads    = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['downloads'].'\';
    var strHtmlTags         = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['tags'].'\';
    var strHtmlCachedResult = \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['cachedResult'].'\';
    var blnAuoSearch        = \''.($blnPixabayCache ? 'true' : 'false').'\';

    function pixabayGoToPage(page)
    {
        return strHtmlGoToPage.replace("##PAGE##", page);
    }

    function pixabayImagePagination(totalHits)
    {
        var paginationLinks = 7;
        var strHtmlPagination;
        var firstOffset;
        var lastOffset;
        var firstLink;
        var lastLink;

        // get pages
        pixabayPages = Math.ceil(totalHits / resultsPerPage);

        // get links
        paginationLinks = Math.floor(paginationLinks / 2);

        firstOffset = pixabayPage - paginationLinks - 1;

        if (firstOffset > 0) firstOffset = 0;

        lastOffset = pixabayPage + paginationLinks - pixabayPages;

        if (lastOffset < 0) lastOffset = 0;

        firstLink = pixabayPage - paginationLinks - lastOffset;

        if (firstLink < 1) firstLink = 1;

        lastLink = pixabayPage + paginationLinks - firstOffset;

        if (lastLink > pixabayPages) lastLink = pixabayPages;

        // html: open pagination container
        strHtmlPagination = \'<div class="pagination">\'
            + \'<p>'.preg_replace('/^(.*?)%s(.*?)%s(.*?)$/', '$1\' + pixabayPage + \'$2\' + pixabayPages + \'$3', $GLOBALS['TL_LANG']['MSC']['totalPages']).'<\/p>\'
            + \'<ul>\'
            ;

        // html: previous
        if (pixabayPage > 1)
        {
            strHtmlPagination += \'<li class="first">\'
                + \'<a href="#" onclick="return pixabaySearchUpdate(1);" class="first" title="\' + pixabayGoToPage(1) + \'">\'
                + \''.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['first']).'\'
                + \'<\/a>\'
                + \'<\/li>\'
                + \'<li class="previous">\'
                + \'<a href="#" onclick="return pixabaySearchUpdate(pixabayPage-1);" class="previous" title="\' + pixabayGoToPage(pixabayPage-1) + \'">\'
                + \''.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['previous']).'\'
                + \'<\/a>\'
                + \'<\/li>\'
                ;
        }

        // html: links
        if (pixabayPages > 1)
        {
            for (i=firstLink; i<=lastLink; i++)
            {
                if (i == pixabayPage)
                {
                    strHtmlPagination += \'<li><span class="active">\' + pixabayPage + \'<\/span><\/li>\'
                }
                else
                {
                    strHtmlPagination += \'<li><a href="#" onclick="return pixabaySearchUpdate(\' + i + \');" class="link" title="\' + pixabayGoToPage(i) + \'">\' + i + \'<\/a><\/li>\'
                }
            }
        }

        // html: next
        if (pixabayPage < pixabayPages)
        {
            strHtmlPagination += \'<li class="next">\'
                + \'<a href="#" onclick="return pixabaySearchUpdate(pixabayPage+1);" class="next" title="\' + pixabayGoToPage(pixabayPage+1) + \'">\'
                + \''.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['next']).'\'
                + \'<\/a>\'
                + \'<\/li>\'
                + \'<li class="last">\'
                + \'<a href="#" onclick="return pixabaySearchUpdate(\' + pixabayPages + \');" class="last" title="\' + pixabayGoToPage(pixabayPages) + \'">\'
                + \''.\StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['last']).'\'
                + \'<\/a>\'
                + \'<\/li>\'
                ;
        }

        // html: close pagination container
        strHtmlPagination += \'<\/ul>\'
            + \'<\/div>\'
            ;

        pixabayPagination.innerHTML = strHtmlPagination;
    }

    function pixabayImageList(pixabayJsonData)
    {
        var strHtmlImages;

        pixabayImages.innerHTML = strHtmlEmpty;

        if (pixabayJsonData.totalHits > 0)
        {
            strHtmlImages = \'\'
                + \'<input type="hidden" name="tl_pixabay_images" value="">\'
                + \'<input type="hidden" name="tl_pixabay_imageIds" value="">\'
                + \'<input type="hidden" name="tl_pixabay_cache" value="\' + pixabayJsonData.__api__.cache + \'">\'
                + \'<div class="widget">\'
                + \'<h3>\' + pixabayJsonData.totalHits + \' '.$GLOBALS['TL_LANG']['MSC']['pixabay']['searchPixabayResult'].'<\/h3>\'
                + \'<\/div>\'
                + \'<div class="flex-container">\'
                ;

            for (var key in pixabayJsonData.hits)
            {
                if (pixabayJsonData.hits.hasOwnProperty(key))
                {
                    var value = pixabayJsonData.hits[key];

                    strHtmlImages += \'\'
                        + \'<div class="widget preview" id="pixabay_preview_\' + key + \'">\'
                            + \'<label for="pixabay_image_\' + key + \'">\'
                            + \'<div class="image-container" style="background-image:url(\' + value.webformatURL + \')">\'
                                + \'<a href="contao/popup?src=\' + value.pageURL + \'" \'
                                    + \' title="\' + value.tags + \'" \'
                                    + \' onclick="Backend.openModalIframe({title:\\\'\' + value.tags + \'\\\', url:\\\'\' + value.pageURL + \'\\\'});return false" \'
                                + \'>\'
                                    + \'<!---<img src="\' + value.webformatURL + \'" width="\' + value.webformatWidth + \'" height="\' + value.webformatHeight + \'">--->\'
                                + \'<\/a>\'
                            + \'<\/div>\'
                            + \'<br>\'
                            + \'<input type="checkbox" id="pixabay_image_\' + key + \'" value="\' + value.id + \'" name="tl_pixabay_imageIds[]" onclick="$$(\\\'#pixabay_preview_\' + key + \'\\\').toggleClass(\\\'selected\\\')">\'
                                + \'ID: <strong>\' + value.id + \'<\/strong>\'
                            + \'<table class="tl_show">\'
                                + \'<tbody>\'
                                    + \'<tr>\'
                                        + \'<td class="tl_bg"><span class="tl_label">\' + strHtmlDownloads + \': <\/span><\/td>\'
                                        + \'<td class="tl_bg">\' + value.downloads + \'<\/td>\'
                                    + \'<\/tr>\'
                                    + \'<tr>\'
                                        + \'<td><span class="tl_label">\' + strHtmlViews + \': <\/span><\/td>\'
                                        + \'<td>\' + value.views + \'<\/td>\'
                                    + \'<\/tr>\'
                                    + \'<tr>\'
                                        + \'<td class="tl_bg"><span class="tl_label">\' + strHtmlLikes + \': <\/span><\/td>\'
                                        + \'<td class="tl_bg">\' + value.likes + \'<\/td>\'
                                    + \'<\/tr>\'
                                    + \'<tr>\'
                                        + \'<td><span class="tl_label">\' + strHtmlFavorites + \': <\/span><\/td>\'
                                        + \'<td>\' + value.favorites + \'<\/td>\'
                                    + \'<\/tr>\'
                                    + \'<tr>\'
                                        + \'<td class="tl_bg"><span class="tl_label">\' + strHtmlUser + \': <\/span><\/td>\'
                                        + \'<td class="tl_bg">\' + value.user + \'<\/td>\'
                                    + \'<\/tr>\'
                                    + \'<tr>\'
                                        + \'<td><span class="tl_label">\' + strHtmlTags + \': <\/span><\/td>\'
                                        + \'<td>\' + value.tags + \'<\/td>\'
                                    + \'<\/tr>\'
                                + \'<\/tbody>\'
                            + \'<\/table>\'
                            + \'<\/label>\'
                        + \'<\/div>\'
                        ;
                }
            }
            
            strHtmlImages += \'<\/div>\';

            strHtmlImages += (pixabayJsonData.__api__.cachedResult ? \'<br clear="all"><div class="widget"><p class="tl_help tl_tip">\' + strHtmlCachedResult + \'<\/p><\/div>\' : \'\');

            pixabayImages.innerHTML = strHtmlImages;
            pixabayImagePagination(pixabayJsonData.totalHits);

            new Fx.Scroll(window).toElement(\'pal_pixabay_result_legend\');
        }
    }

    function pixabayException(pixabayJsonData)
    {
        pixabayImages.innerHTML = \'<br clear="all">\'
            + \'<div class="widget tl_error">\'
                + \'<p>\'
                    + \'<strong>#\' + pixabayJsonData.__api__.exceptionId + \'</strong>\'
                + \'<\/p>\'
                + \'<p>\'
                    + pixabayJsonData.__api__.exceptionMessage
                + \'<\/p>\'
            + \'<\/div>\'
            ;
    }

    function pixabayApi(search)
    {
        //$$(\'div.tl_formbody_submit\').addClass(\'invisible\');
        
        pixabayPagination.innerHTML = \'&nbsp;\';
        pixabayImages.innerHTML = \'<div class="spinner"><\/div>\';

        var xhr = new XMLHttpRequest();
        var url =\''.ampersand(Environment::get('script'), true).'/trilobit/pixabay\'
            + \'?q=\' + encodeURIComponent(search)
            + \'&lang=\' + language
            
            + \'&editors_choice=\' + $$(\'input[name="editors_choice"]:checked\').length
            + \'&safesearch=\'     + $$(\'input[name="safesearch"]:checked\').length
            + \'&orientation=\'    + $$(\'select[name="orientation"] option:selected\').get(\'value\')
            + \'&order=\'          + $$(\'select[name="order"] option:selected\').get(\'value\')
            + \'&image_type=\'     + $$(\'select[name="image_type"] option:selected\').get(\'value\')
            + \'&category=\'       + $$(\'select[name="category"] option:selected\').get(\'value\')
            + \'&colors=\'         + $$(\'select[name="colors"] option:selected\').get(\'value\')
            + \'&min_width=\'      + $$(\'input[name="min_width"]\').get(\'value\')
            + \'&min_height=\'     + $$(\'input[name="min_height"]\').get(\'value\')
            + \'&colors=\'         + $$(\'select[name="colors"] option:selected\').get(\'value\')
            
            + \'&page=\'     + pixabayPage
            + \'&per_page=\' + resultsPerPage
            ;       
        
        xhr.open(\'GET\', url);
        xhr.onreadystatechange = function()
        {
            var pixabayJsonData = pixabayJsonData || {};

            if (   this.status == 200
                && this.readyState == 4
            )
            {
                pixabayJsonData = JSON.parse(this.responseText);

                if (   pixabayJsonData
                    && pixabayJsonData.__api__
                    && pixabayJsonData.__api__.exceptionId
                )
                {
                    pixabayException(pixabayJsonData);
                }
                else
                {
                    pixabayImageList(pixabayJsonData);
                }

                return false;
            }

            pixabayJsonData = pixabayJsonData || {};
            pixabayJsonData.__api__ = pixabayJsonData.__api__ || {};;

            pixabayJsonData.__api__.exceptionId = this.status;
            pixabayJsonData.__api__.exceptionMessage = \'[ERROR \' + this.status + \'] Please try again...\';

            pixabayException(pixabayJsonData);

        };
        xhr.send();

        return false;
    }

    function pixabaySearchUpdate(page)
    {
        if (page !== undefined)
        {
            pixabayPage = page;
        }

        var search = $$(\'input[name="pixabay_search"]\').get(\'value\');

        $$(\'#pal_pixabay_result_legend\').removeClass(\'collapsed\');
        $$(\'#pal_pixabay_filter_legend\').addClass(\'collapsed\');

        if (   search === undefined
            || search === \'\'
        )
        {
            pixabayImages.innerHTML = \'\';
            pixabayImages.innerHTML = strHtmlEmpty;

            return false;
        }

        pixabayApi(search);
        

        return false;
    }

    function pixabaySearch()
    {
        $$(\'#pixabay_form button.tl_submit\').addEvent(\'click\', function(e) {
            e.stop();

            return pixabaySearchUpdate(1);            
        });
    }

    pixabaySearch();
    
    if (blnAuoSearch) pixabaySearchUpdate('.$GLOBALS['TL_CONFIG']['page'].');
</script>';

        $GLOBALS['TL_CONFIG'] = $arrGlobalsConfig;

        return $return;
    }
}

class_alias(PixabayZone::class, 'PixabayZone');
