<?php

/**
 * Maileon Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Add palettes to tl_form
 */
PaletteManipulator::create()
    ->addLegend('maileon_legend', 'store_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['sendToMaileon'], 'maileon_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_form')
;

$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'sendToMaileon';
$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'maileon_use_doi';

$GLOBALS['TL_DCA']['tl_form']['subpalettes']['sendToMaileon'] = 'maileon_api_key,maileon_permission,maileon_sync_mode,maileon_src,maileon_use_doi';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['maileon_use_doi'] = 'maileon_doimailing,maileon_doiplus';


/**
 * Add fields to tl_form
 */
$GLOBALS['TL_DCA']['tl_form']['fields']['sendToMaileon'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'filter'            => true
,   'eval'              => ['submitOnChange'=>true, 'tl_class'=>'clr']
,   'sql'               => ['type'=>'boolean', 'default'=>false]
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_api_key'] = [
    'exclude'           => true
,   'inputType'         => 'text'
,   'eval'              => ['mandatory'=>true, 'tl_class'=>'w50']
,   'sql'               => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_permission'] = [
    'exclude'           => true
,   'inputType'         => 'select'
,   'options'           => ['1', '2', '3', '4', '5', '6']
,   'reference'         => &$GLOBALS['TL_LANG']['tl_form']['maileon_permissions']
,   'eval'              => ['mandatory'=>true, 'tl_class'=>'w50 clr']
,   'sql'               => "varchar(16) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_sync_mode'] = [
    'exclude'           => true
,   'inputType'         => 'select'
,   'options'           => ['1', '2']
,   'reference'         => &$GLOBALS['TL_LANG']['tl_form']['maileon_sync_modes']
,   'eval'              => ['mandatory'=>true, 'tl_class'=>'w50']
,   'sql'               => "varchar(16) NOT NULL default '2'"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_src'] = [
    'exclude'           => true
,   'inputType'         => 'text'
,   'eval'              => ['tl_class'=>'w50 clr']
,   'sql'               => "varchar(128) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_use_doi'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['submitOnChange'=>true, 'tl_class'=>'clr']
,   'sql'               => ['type'=>'boolean', 'default'=>false]
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_doimailing'] = [
    'exclude'           => true
,   'inputType'         => 'text'
,   'eval'              => ['tl_class'=>'w50']
,   'sql'               => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['maileon_doiplus'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['tl_class'=>'w50 m12']
,   'sql'               => ['type'=>'boolean', 'default'=>false]
];
