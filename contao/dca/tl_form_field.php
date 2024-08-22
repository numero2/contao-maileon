<?php

/**
 * Maileon Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


/**
 * Add fields to tl_form_field
 */
$GLOBALS['TL_DCA']['tl_form_field']['fields']['maileon_field_name'] = [
    'exclude'           => true
,   'inputType'         => 'select'
,   'eval'              => ['includeBlankOption'=>true, 'tl_class'=>'w50'
    ,   'supportedType'=>['text', 'textarea', 'select', 'radio', 'checkbox', 'range', 'hidden']
    ]
,   'sql'               => "varchar(64) NOT NULL default ''"
];
