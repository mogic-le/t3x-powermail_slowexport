<?php

$EM_CONF[$_EXTKEY] = [
	'title' => 'Powermail SlowExport',
	'description' => 'Scalable CSV export for powermail',
	'category' => 'module',
	'author' => 'Raphael Graf',
	'author_email' => 'graf@netvertising.ch',
	'author_company' => 'Netvertising AG',
	'state' => 'stable',
	'uploadfolder' => false,
	'clearCacheOnLoad' => 1,
	'version' => '1.1.1',
	'constraints' => [
		'depends' => [
			'typo3' => '7.6.0-10.4.99',
			'powermail' => '3.0.0',
		],
		'conflicts' => [
			'powermail_fastexport' => '',
		],
		'suggests' => [
		],
	],
];
