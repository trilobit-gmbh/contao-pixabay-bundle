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

$GLOBALS['TL_HOOKS']['DC_Folder_moveSource'][] = array('\Trilobit\PixabayBundle\DC_Folder_pixabay', 'pixabay');


/**
 * Add css
 */
if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/trilobitpixabay/css/backend.css';
}
