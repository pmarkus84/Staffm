<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "staffm"
 *
 * Auto generated by Extension Builder 2016-01-20
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Staffmanage',
	'description' => 'Employee database with qualifications, peronal numbers and so on.',
	'category' => 'misc',
	'author' => 'Markus Puffer',
	'author_email' => 'm.puffer@pm-webdesign.eu',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '8.7.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '8.7.0-8.7.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

// Deprecated: ab Typo3 7.6
//require_once (t3lib_extMgm::extPath('adodb').'adodb/adodb.inc.php');
