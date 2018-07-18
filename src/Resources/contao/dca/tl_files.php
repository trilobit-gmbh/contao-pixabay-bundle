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

// Load language file(s)
System::loadLanguageFile('tl_pixabay');


/**
 * Table tl_files
 */
if (!in_array('trilobit_dcfolder', $this->Config->getActiveModules()))
{
    $this->log('Pixabay init failed: "trilobit-gmbh/contao-dcfolder" is required.', 'tl_files', TL_ERROR);
    return false;
}

if (\Config::get('pixabayApiKey') !== '')
{
    $GLOBALS['TL_DCA']['tl_files']['list']['global_operations'] = array_merge(
        array('pixabay' => array(
            'label'               => &$GLOBALS['TL_LANG']['tl_pixabay']['operationAddFromPixabay'],
            'href'                => 'act=paste&mode=move&source=pixabay',
            'class'               => 'header_pixabay',
            'icon'                => '/bundles/trilobitpixabay/pixabay.ico',
            'button_callback'     => array('tl_files_pixabay', 'pixabay')
        )),
        $GLOBALS['TL_DCA']['tl_files']['list']['global_operations']
    );
}


/**
 * Class tl_files_pixabay
 */
class tl_files_pixabay extends Backend
{

    /**
     * tl_files_pixabay constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }


    /**
     * @param $href
     * @param $label
     * @param $title
     * @param $class
     * @param $attributes
     * @return string
     */
    public function pixabay($href, $label, $title, $class, $attributes)
    {
        return $this->User->hasAccess('pixabay', 'fop') ? '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'" class="'.$class.'"'.$attributes.'>'.$label.'</a> ' : '';
    }
}
