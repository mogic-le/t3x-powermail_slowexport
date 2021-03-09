<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2018 Raphael Graf <graf@netvertising.ch>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
namespace Netv\PowermailSlowexport\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use In2code\Powermail\Utility\StringUtility;
use Netv\PowermailSlowexport\Utility\ExportUtility;

define('PMSE_DEBUG', FALSE);

class ModuleController extends \In2code\Powermail\Controller\ModuleController
{
	/**
	 * @return string
	 */
	public function exportCsvAction()
	{
		if (PMSE_DEBUG)
			$starttime =  time();
		ini_set('max_execution_time', 60 * 15);	//omg

		$fieldUids = GeneralUtility::trimExplode(
			',',
			StringUtility::conditionalVariable($this->piVars['export']['fields'], ''),
			TRUE
		);

		$mails = $this->mailRepository->findAllInPid($this->id, $this->settings, $this->piVars);
		$fileName = StringUtility::conditionalVariable($this->settings['export']['filenameCsv'], 'export.csv');

		header('Content-Type: text/x-csv');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Pragma: no-cache');
		print(pack('CCC', 239, 187, 191)); //BOM
		print(ExportUtility::getCSV($mails, $fieldUids, $fileName));

		if (PMSE_DEBUG) {
			$time =  time() - $starttime;
			$this->addFlashMessage('CSV export time: ' . $time . 's, peak: ' . (int)(memory_get_peak_usage()/(1024*1024)) . 'MB', 'DEBUG', AbstractMessage::INFO);
		}

		return '';
	}
}
