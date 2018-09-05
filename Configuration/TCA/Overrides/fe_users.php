<?php

/* 
 * Copyright (c) 2018 Markus Puffer <m.puffer@pm-webdesign.eu>.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Markus Puffer <m.puffer@pm-webdesign.eu> - initial API and implementation and/or initial documentation
 */
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// Configure new fields 
$fields = array(
    'personalnummer' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.personalnummer',
        'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
        ),
    ),
    'handy' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.handy',
        'config' => array(
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
        ),
    ),
    'date_of_birth_show' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.date_of_birth_show',
        'config' => array(
                'type' => 'input',
                'size' => 1,
                'eval' => 'trim',
                'checkbox' => 1,
                'default' => 0
        ),
    ),
    'date_of_birth' => array(
        'exclude' => 1,      
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.date_of_birth',
        'config' => array(
                'type' => 'input', 
                'renderType' => 'inputDateTime',
                'size' => 13,                
                'eval' => 'datetime',
                'checkbox' => 0,
                'default' => 0,
                'range' => array(
                        'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
                ),
                'allowLanguageSynchronization' => true,
        ),
    ),
    'kostenstelle' => array(
        'exclude' => 1,        
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.kostenstelle',
        'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_staffm_domain_model_kostenstelle',
                'minitems' => 0,
                'maxitems' => 1,
        ),
    ),
    'firma' => array(
        'exclude' => 1,        
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.firma',
        'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_staffm_domain_model_firma',
                'minitems' => 0,
                'maxitems' => 1,
        ),
    ),
    'position' => array(
        'exclude' => 1,        
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.position',
        'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_staffm_domain_model_position',
                'minitems' => 0,
                'maxitems' => 1,
        ),
    ),
    'qualifikationen' => array(
        //'exclude' => 1,        
        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.qualifikationen',
        'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'multiple' => 1,
                'foreign_table' => 'tx_staffm_domain_model_qualifikation',            
                'MM' => 'tx_staffm_domain_model_mitarbeiter_qualifikation_mm',                
                'foreign_table_where' => ' AND tx_staffm_domain_model_qualifikation.pid=###CURRENT_PID### ORDER BY tx_staffm_domain_model_qualifikation.bezeichnung ',
                'multiple' => 1,
                'minitems' => 0,
                'maxitems' => 1000,                
        ),
    ),
//    'qualifikationen' => array(
//        //'exclude' => 1,        
//        'label' => 'LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.qualifikationen',
//        'config' => array(
//                'type' => 'inline',
//                'foreign_table' => 'tx_staffm_domain_model_mitarbeiter_qualifikation',
//                'foreign_field' => 'mitarbeiter',                
//                'foreign_label' => 'qualifikation',
//        ),
//    ),
    'image' => [
        'exclude' => 0,
        'label' => 'LLL:EXT:Staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.image',
        'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('image', [
            'appearance' => [
                'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference'
            ],
            // custom configuration for displaying fields in the overlay/reference table
            // to use the imageoverlayPalette instead of the basicoverlayPalette
            'foreign_match_fields' => [
                'fieldname' => 'image',
                //'tablenames' => 'tx_staffm_domain_model_mitarbeiter',
                'tablenames' => 'fe_users',
                'table_local' => 'sys_file',
            ],
            'foreign_types' => [
                '0' => [
                    'showitem' => '
                        --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                        --palette--;;filePalette'
                ],
                \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                    'showitem' => '
                        --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                        --palette--;;filePalette'
                ],
                \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                    'showitem' => '
                        --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                        --palette--;;filePalette'
                ],
                \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                    'showitem' => '
                        --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                        --palette--;;filePalette'
                ],
                \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                    'showitem' => '
                        --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                        --palette--;;filePalette'
                ],
                \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                    'showitem' => '
                        --palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                        --palette--;;filePalette'
                ]
            ]
        ], $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])
    ]
);

// Add new fields to fe_users
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $fields);
      
// Make fields visible in the TCEforms:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
  'fe_users', // Table name
  '--palette--;LLL:EXT:staffm/Resources/Private/Language/locallang_db.xlf:tx_staffm_domain_model_mitarbeiter.personalnummer;tx_staffm', // Field list to add
  '1', // List of specific types to add the field list to. (If empty, all type entries are affected)
  'after:nav_title' // Insert fields before (default) or after one, or replace a field
);
  
// Add the new palette:
//$GLOBALS['TCA']['pages']['palettes']['tx_pagesaddfields'] = array(
//  'showitem' => 'tx_pagesaddfields_customcheckbox,tx_pagesaddfields_customtext'
//);