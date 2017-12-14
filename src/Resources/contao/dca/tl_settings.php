
<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// Load language file(s)
System::loadLanguageFile('tl_pixabay');


/**
 * System configuration
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace
(
    ';{proxy_legend',
    ';{pixabay_legend:hide},pixabayApiKey,pixabayApiUrl,pixabayHighResolution,pixabayImageSource;{proxy_legend',
    $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']
);

// Fields
$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayApiKey'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayApiKey'],
    'inputType' => 'text',
    'eval'      => array('tl_class'=>'clr w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayHighResolution'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayHighResolution'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class'=>'clr w50'),
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayImageSource'] = array
(
    'label'            => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayImageSource'],
    'inputType'        => 'select',
    'options_callback' => array('tl_settings_pixabay', 'getImageSource'),
    'reference'        => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['image_source'],
    'eval'             => array('chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'clr w50'),
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayApiUrl'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayApiUrl'],
    'inputType' => 'text',
    'eval'      => array('tl_class'=>'w50')
);


/**
 * Class tl_settings_pixabay
 */
class tl_settings_pixabay extends Backend
{

    /**
     * tl_settings_pixabay constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }


    /**
     * @param DataContainer $dc
     * @return array
     */
    public function getImageSource(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['imageSource']);
    }
}