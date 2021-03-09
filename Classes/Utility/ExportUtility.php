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
namespace Netv\PowermailSlowexport\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

define('PMSE_LIMIT', 1000);		// chunk size

class ExportUtility
{
	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $mails
	 * @param array $fieldUids
	 * @return string
	 */
	public static function getCSV($mails, &$fieldUids)
	{
		$TYPO3_version = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(TYPO3_version);
		$objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		$persistenceManager = $objectManager->get('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
		$fieldRepository = $objectManager->get('In2code\Powermail\Domain\Repository\FieldRepository');

		if ($TYPO3_version['version_main'] >= 8) {
			$connectionPool = $objectManager->get('TYPO3\CMS\Core\Database\ConnectionPool');
			$connection = $connectionPool->getConnectionForTable('tx_powermail_domain_model_answer');
		}

		$output = fopen('php://memory', 'w');

		$hrow = [];
		foreach($fieldUids as $fieldUid) {
			if (is_numeric($fieldUid)) {
				$field = $fieldRepository->findByUid($fieldUid);
				$hrow[] = $field->getTitle();
			} else {
				$camelFieldUid = GeneralUtility::underscoredToLowerCamelCase($fieldUid);
				$hrow[] = LocalizationUtility::translate(
					'LLL:EXT:powermail/Resources/Private/Language/locallang.xlf:\In2code\Powermail\Domain\Model\Mail.' . $camelFieldUid, 'powermail'
				);
			}
		}
		fputcsv($output, $hrow, ';');

		$offset = 0;
		$query = $mails->getQuery();
		$query->setLimit(PMSE_LIMIT);
		do {
			$query->setOffset($offset);
			$mails_ = $query->execute();

			$mailsavailable = FALSE;
			foreach($mails_ as $mail) {
				$mailsavailable = TRUE;
				$row = [];
				$value = '';
				$fieldsArr = [];
				if ($TYPO3_version['version_main'] >= 8) {
					$qb = $connection->createQueryBuilder();
					$answers = $qb->select('answer.value','answer.value_type','field.uid')
						->from('tx_powermail_domain_model_answer', 'answer')
						->where('answer.deleted=0', 'answer.mail = ' . $mail->getUid())
						->join('answer', 'tx_powermail_domain_model_field', 'field', 'field.uid=answer.field')
						->execute();
					while ($answer = $answers->fetch()) {
						$valuetype = (int)$answer['value_type'];
						$value = self::formatValue($valuetype, $answer['value']);
						$fieldsArr[$answer['uid']] = $value;
					}
				} else {
					$answers = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'answer.value,answer.value_type, field.uid',
						'tx_powermail_domain_model_answer as answer,tx_powermail_domain_model_field as field',
						'answer.mail = ' . $mail->getUid() . ' AND answer.deleted=0 AND field.uid=answer.field'
					);
					while ($answer = $answers->fetch_assoc()) {
						$valuetype = (int)$answer['value_type'];
						$value = self::formatValue($valuetype, $answer['value']);
						$fieldsArr[$answer['uid']] = $value;
					}
					$answers->free();
				}

				foreach($fieldUids as $fieldUid) {
					if (is_int($fieldUid) || ctype_digit($fieldUid)) {
						$row[] = $fieldsArr[$fieldUid];
					} else {
						$funcName = 'get' . GeneralUtility::underscoredToUpperCamelCase($fieldUid);
						if (is_callable([$mail, $funcName]))
							$value = $mail->{$funcName}();
						$row[] = self::formatValue(-1, $value, $fieldUid);
					}
				}
				fputcsv($output, $row, ';');
			}

			// free memory
			$persistenceManager->clearState();

			$offset+= PMSE_LIMIT;
		} while($mailsavailable);

		fseek($output, 0);
		return stream_get_contents($output);
	}

	 /*
	 * @param int
	 * @param mixed
	 * @param string
	 * @return string
	 */
	private static function formatValue($type, $value, $fieldName = '')
	{
		switch ($type) {
		case 0:	//text
			return $value;
		case 1:	//array
		case 3: //file
			$val = json_decode($value);
			if (is_array($val))
				return implode(',', $val);
			return $value;
		case 2:	//date
			return date('Y-m-d H:i:s', (int)$value);
		default:
			if ($fieldName === 'marketing_page_funnel' && is_array($value)) {
				$pages = [];
				foreach($value as $uid) {
					$pages[] = BackendUtility::getRecord('pages', $uid, 'title', '', FALSE);
				}
				return implode(' > ', $pages);
			}
			if (is_object($value)) {
				if (get_class($value) == 'DateTime')
					return $value->format('Y-m-d H:i:s');
				else
					return (string) $value;
			} else if (is_array($value)) {
				return implode(',', $value);
			} else {
				return $value;
			}
		}
	}
}
