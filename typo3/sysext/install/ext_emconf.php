<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'System>Install',
	'description' => 'The Install Tool mounted as the module Tools>Install in TYPO3.',
	'category' => 'module',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'CURBY SOFT Multimedie',
	'version' => '7.6.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-7.6.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
