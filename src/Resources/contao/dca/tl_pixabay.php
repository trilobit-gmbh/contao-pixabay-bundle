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
 * "Table" tl_pixabay
 */
$GLOBALS['TL_DCA']['tl_pixabay'] = [
    // Config
    'config' => [
        'dataContainer' => 'File',
        'closed' => true,
    ],

    // Palettes
    'palettes' => [
        'default' => '{pixabay_filter_legend},image_type,category,order,orientation,min_width,min_height,colors,editors_choice,safesearch', //,
    ],

    // Fields
    'fields' => [
        'image_type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['image_type'],
            'inputType' => 'select',
            'options_callback' => ['tl_pixabay', 'getImageType'],
            'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['image_type'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'category' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['category'],
            'inputType' => 'select',
            'options_callback' => ['tl_pixabay', 'getCategory'],
            'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['category'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'order' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['order'],
            'inputType' => 'select',
            'options_callback' => ['tl_pixabay', 'getOrder'],
            'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['order'],
            'eval' => ['chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'priority' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['order'],
            'inputType' => 'select',
            'options_callback' => ['tl_pixabay', 'getOrder'],
            'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['order'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'orientation' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['orientation'],
            'inputType' => 'select',
            'options_callback' => ['tl_pixabay', 'getOrientation'],
            'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['orientation'],
            'eval' => ['chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'colors' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['colors'],
            'inputType' => 'select',
            'options_callback' => ['tl_pixabay', 'getColors'],
            'reference' => &$GLOBALS['TL_LANG']['tl_pixabay']['options']['colors'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'editors_choice' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['editors_choice'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'safesearch' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['safesearch'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'min_width' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['min_width'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) NULL',
        ],
        'min_height' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['min_height'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) NULL',
        ],
    ],
];

/**
 * Class tl_pixabay.
 */
class tl_pixabay extends Backend
{
    /**
     * tl_pixabay constructor.
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
    public function getImageType(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['image_type']);
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getCategory(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['category']);
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getOrder(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['order']);
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getOrientation(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['orientation']);
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getColors(DataContainer $dc)
    {
        return array_keys(\Trilobit\PixabayBundle\Helper::getConfigData()['colors']);
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
