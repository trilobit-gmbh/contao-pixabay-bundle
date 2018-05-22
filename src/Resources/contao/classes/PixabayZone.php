<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2017 Leo Feyer
 *
 * @package     Trilobit
 * @author      trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license     LGPL-3.0-or-later
 * @copyright   trilobit GmbH
 */

namespace Trilobit\PixabayBundle;

use Contao\Config;
use Contao\FileUpload;
use Contao\Controller;
use Contao\Input;
use Trilobit\PixabayBundle\Helper;

/**
 * Class PixabayZone
 * @package Trilobit\PixabayBundle
 *
 * @author trilobit GmbH <https://github.com/trilobit-gmbh>
 */
class PixabayZone extends FileUpload
{

    /**
     * @return string
     * @throws \Exception
     */
    public function generateMarkup()
    {
        Controller::loadLanguageFile('tl_pixabay');

        $arrCache        = Helper::getCacheData(Input::get('cache'));
        $arrApiParameter = Helper::getConfigData()['api'];

        $arrGlobalsConfig = $GLOBALS['TL_CONFIG'];

        $pixabay_search = '';
        $blnPixabayCache = false;

        if (count($arrCache))
        {
            $blnPixabayCache = true;
            $pixabay_search = $arrCache['__api__']['parameter']['q'];
        }

        $this->import('BackendUser', 'User');


        foreach ($arrApiParameter as $key => $value)
        {
            $GLOBALS['TL_CONFIG'][$key] = $this->User->{($key === 'order' ? 'priority' : $key)};

            if (   $blnPixabayCache
                && isset($arrCache['__api__']['parameter'][$key])
                && $arrCache['__api__']['parameter'][$key] !== ''
            )
            {
                if (strtolower($value) === 'bool')
                {
                    $GLOBALS['TL_CONFIG'][$key] = 1;
                }
                else if (strtolower($value) === 'int')
                {
                    $GLOBALS['TL_CONFIG'][$key] = intval($arrCache['__api__']['parameter'][$key], 10);
                }
                else
                {
                    $GLOBALS['TL_CONFIG'][$key] = $arrCache['__api__']['parameter'][$key];
                }
            }
        }

        // Generate the markup
        $return = '
<input type="hidden" name="action" value="pixabayupload">

<div class="tl_box">
    <div class="widget">
        <div id="pixabay_inform">
            <h2>'.$GLOBALS['TL_LANG']['tl_pixabay']['poweredBy'][0] .'</h2>
            <br>
            <a href="https://pixabay.com" target="_blank" rel="noopener noreferrer"><img src="/bundles/trilobitpixabay/logo.png" width=100 height=100 style="margin-right: 15px"></a>
            <a href="https://www.trilobit.de" target="_blank" rel="noopener noreferrer"><img src="/bundles/trilobitpixabay/trilobit_gmbh.svg" width="auto" height="50"></a><br>
            <div class="hint"><br><br><span>'.$GLOBALS['TL_LANG']['MSC']['pixabay']['hint'].'</span></div>
        </div>
    </div>
</div>

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
      
        <!---<div class="w50 widget cbx">
            <div id="ctrl_pixabay_id_search" class="tl_checkbox_single_container">
                <input type="hidden" name="pixabay_id_search" value="">
                <input type="checkbox" name="pixabay_id_search" id="opt_pixabay_id_search_0" class="tl_checkbox" value="1" onclick="($$(\'#opt_pixabay_id_search_0:checked\').length ? $$(\'#pal_pixabay_filter_legend\').addClass(\'invisible\') : $$(\'#pal_pixabay_filter_legend\').removeClass(\'invisible\'))" onfocus="Backend.getScrollOffset()">
                <label for="opt_pixabay_id_search_0">'.$GLOBALS['TL_LANG']['tl_pixabay']['searchId'][0].'</label>
            </div>

            <p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_pixabay']['searchId'][1].'</p>
        </div>--->
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
            + \'<p>Seite \' + pixabayPage + \' von \' + pixabayPages + \'<\/p>\'
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
            //$$(\'div.tl_formbody_submit\').removeClass(\'invisible\');

            strHtmlImages = \'\'
                + \'<input type="hidden" name="tl_pixabay_images" value="">\'
                + \'<input type="hidden" name="tl_pixabay_imageIds" value="">\'
                + \'<input type="hidden" name="tl_pixabay_cache" value="\' + pixabayJsonData.__api__.cache + \'">\'
                + \'<div class="widget">\'
                + \'<h3>\' + pixabayJsonData.totalHits + \''.$GLOBALS['TL_LANG']['MSC']['pixabay']['searchPixabayResult'].'<\/h3>\'
                + \'<\/div>\'
                ;

            for (var key in pixabayJsonData.hits)
            {
                if (pixabayJsonData.hits.hasOwnProperty(key))
                {
                    var value = pixabayJsonData.hits[key];

                    var previewWidth  = value.webformatWidth;
                    var previewHeight = value.webformatHeight;
                    var pageURL       = value.pageURL;
                    var tags          = value.tags;
                    var previewURL    = value.webformatURL;
                    var downloadId    = value.id;
    
                    strHtmlImages += \'\'
                        + \'<div class="widget preview" id="pixabay_preview_\' + key + \'">\'
                            + \'<label for="pixabay_image_\' + key + \'">\'
                            + \'<div class="image-container">\'
                                + \'<a href="contao/popup?src=\' + pageURL + \'" \'
                                    + \' title="\' + tags + \'" \'
                                    + \' onclick="Backend.openModalIframe({title:\\\'\' + tags + \'\\\', url:\\\'\' + pageURL + \'\\\'});return false" \'
                                + \'>\'
                                    + \'<img src="\' + previewURL + \'" width="\' + previewWidth + \'" height="\' + previewHeight + \'">\'
                                + \'<\/a>\'
                            + \'<\/div>\'
                            + \'<br>\'
                            + \'<input type="checkbox" id="pixabay_image_\' + key + \'" value="\' + downloadId + \'" name="tl_pixabay_imageIds[]" onclick="$$(\\\'#pixabay_preview_\' + key + \'\\\').toggleClass(\\\'selected\\\')">\'
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

        var url =\'trilobit/pixabay\'
            + \'?\' + ($$(\'input[name="pixabay_id_search"]:checked\').length ? \'id\' : \'q\') + \'=\' + encodeURIComponent(search)
            + \'&lang=\' + language
            ;
        
        if (!$$(\'input[name="pixabay_id_search"]:checked\').length)
        {
            url += \'&editors_choice=\' + $$(\'input[name="editors_choice"]:checked\').length
                + \'&safesearch=\'     + $$(\'input[name="safesearch"]:checked\').length
                + \'&orientation=\'    + $$(\'select[name="orientation"] option:selected\').get(\'value\')
                + \'&order=\'          + $$(\'select[name="order"] option:selected\').get(\'value\')
                + \'&image_type=\'     + $$(\'select[name="image_type"] option:selected\').get(\'value\')
                + \'&category=\'       + $$(\'select[name="category"] option:selected\').get(\'value\')
                + \'&colors=\'         + $$(\'select[name="colors"] option:selected\').get(\'value\')
                + \'&min_width=\'      + $$(\'input[name="min_width"]\').get(\'value\')
                + \'&min_height=\'     + $$(\'input[name="min_height"]\').get(\'value\')
                + \'&colors=\'         + $$(\'select[name="colors"] option:selected\').get(\'value\')
                + \'&page=\'           + pixabayPage
                + \'&per_page=\'       + resultsPerPage
                ;       
        }
        
        xhr.open(\'GET\', url);
        xhr.onreadystatechange = function()
        {
            if (   this.status == 200
                && this.readyState == 4
            )
            {
                var pixabayJsonData = JSON.parse(this.responseText);

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

            var pixabayJsonData = pixabayJsonData || {};
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
