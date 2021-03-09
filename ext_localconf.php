<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['In2code\\Powermail\\Controller\\ModuleController'] = [
	'className' => 'Netv\\PowermailSlowexport\\Controller\\ModuleController'
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['In2code\\Powermail\\Domain\\Service\\ExportService'] = [
	'className' => 'Netv\\PowermailSlowexport\\Domain\\Service\\ExportService'
];

$powermailVersion = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('powermail');
if (version_compare($powermailVersion, '3.20.0') <= 0) {
	// fix backend module performance using code from powermail 3.20.0
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['In2code\\Powermail\\Domain\\Repository\\MailRepository'] = [
		'className' => 'Netv\\PowermailSlowexport\\Domain\\Repository\\MailRepository'
	];
}
