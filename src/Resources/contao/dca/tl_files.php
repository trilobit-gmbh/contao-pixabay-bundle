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
 * Table tl_files
 */

if ('' !== \Config::get('pixabayApiKey')) {
    $GLOBALS['TL_DCA']['tl_files']['config']['onload_callback'][] = ['tl_files_pixabay', 'setUploader'];

    $GLOBALS['TL_DCA']['tl_files']['list']['global_operations'] = array_merge(
        ['pixabay' => [
            'label' => &$GLOBALS['TL_LANG']['tl_pixabay']['operationAddFromPixabay'],
            'href' => 'act=paste&mode=move&source=pixabay',
            'class' => 'header_pixabay',
            'icon' => '/bundles/trilobitpixabay/pixabay.ico',
            'button_callback' => ['tl_files_pixabay', 'pixabay'],
        ]],
        $GLOBALS['TL_DCA']['tl_files']['list']['global_operations']
    );
}

/**
 * Class tl_files_pixabay.
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
     *
     * @return string
     */
    public function pixabay($href, $label, $title, $class, $attributes)
    {
        $canUpload = $this->User->hasAccess('f1', 'fop');
        $canPixabay = $this->User->hasAccess('pixabay', 'fop');

        return $canPixabay && $canUpload ? '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'" class="'.$class.'"'.$attributes.'>'.$label.'</a> ' : '';
    }

    public function setUploader()
    {
        if ('move' === \Input::get('act') && 'pixabay' === \Input::get('source')) {
            $this->import('BackendUser', 'User');
            $this->User->uploader = 'Trilobit\PixabayBundle\PixabayZone';
        }
    }
}
