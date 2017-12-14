<?php

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['DC_Folder_moveSource'][] = array('\Trilobit\PixabayBundle\DC_Folder_pixabay', 'pixabay');


/**
 * Add css
 */
if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/trilobitpixabay/css/backend.css';
}
