<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-pixabay-bundle
 */

// Load language file(s)
System::loadLanguageFile('tl_pixabay');

/*
 * System configuration
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace(
    ';{proxy_legend',
    ';{pixabay_legend:hide},pixabayApiKey,pixabayImageSource;{proxy_legend',
    $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']
);

// Fields
$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayApiKey'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayApiKey'],
    'inputType' => 'text',
    'eval' => ['tl_class' => 'clr w50'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayHighResolution'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayHighResolution'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr w50'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayImageSource'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayImageSource'],
    'inputType' => 'select',
    'options_callback' => ['tl_settings_pixabay', 'getImageSource'],
    'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['image_source'],
    'eval' => ['chosen' => true, 'tl_class' => 'clr w50'],
];

/**
 * Class tl_settings_pixabay.
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
     *
     * @return array
     */
    public function getImageSource(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['imageSource']);
    }
}
