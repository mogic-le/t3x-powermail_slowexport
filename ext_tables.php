<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

/* hide the XLS export button (only CSV is supported) */
$GLOBALS['TBE_STYLES']['inDocStyles_TBEstyle'].= '
.powermail-backend .export_icon_xls {
	display:none;
}
';
