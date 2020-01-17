<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-pixabay-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Load language file(s)
System::loadLanguageFile('tl_pixabay');

// Fields
$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayApiKey'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayApiKey'],
    'inputType' => 'text',
    'eval' => ['tl_class' => 'clr w50'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayHighResolution'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayHighResolution'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['pixabayImageSource'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['pixabayImageSource'],
    'inputType' => 'select',
    'options_callback' => ['tl_settings_pixabay', 'getImageSource'],
    'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['image_source'],
    'eval' => ['chosen' => true, 'tl_class' => 'w50'],
];

PaletteManipulator::create()
    ->addLegend('pixabay_legend', 'proxy_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['pixabayApiKey', 'pixabayImageSource'], 'pixabay_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings')
;


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
