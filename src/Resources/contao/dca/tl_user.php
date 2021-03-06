<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-pixabay-bundle
 */

// Load language file(s)
System::loadLanguageFile('tl_pixabay');

// Load data container
Controller::loadDataContainer('tl_pixabay');

/*
 * Table tl_user
 */
unset($GLOBALS['TL_DCA']['tl_pixabay']['fields']['order']);

$GLOBALS['TL_DCA']['tl_user']['fields'] = array_merge($GLOBALS['TL_DCA']['tl_pixabay']['fields'], $GLOBALS['TL_DCA']['tl_user']['fields']);

$GLOBALS['TL_DCA']['tl_pixabay']['palettes']['default'] = str_replace('pixabay_filter_legend', 'pixabay_legend', $GLOBALS['TL_DCA']['tl_pixabay']['palettes']['default']);
$GLOBALS['TL_DCA']['tl_pixabay']['palettes']['default'] = str_replace('order', 'priority', $GLOBALS['TL_DCA']['tl_pixabay']['palettes']['default']);

foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $key => $value) {
    $GLOBALS['TL_DCA']['tl_user']['palettes'][$key] = str_replace(
        ';{password_legend',
        ';'.$GLOBALS['TL_DCA']['tl_pixabay']['palettes']['default'].';{password_legend',
        $value
    );
}
