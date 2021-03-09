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
namespace Netv\PowermailSlowexport\Domain\Repository;

class MailRepository extends \In2code\Powermail\Domain\Repository\MailRepository
{
	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
	{
		parent::__construct($objectManager);
		$this->objectType = 'In2code\\Powermail\\Domain\\Model\\Mail';
	}

	/**
	 * Taken from powermail 3.20.0
	 *
	 * @param int $pageUid
	 * @return array
	 */
	public function findGroupedFormUidsToGivenPageUid($pageUid = 0)
	{
		/** @var \TYPO3\CMS\Extbase\Persistence\Generic\Query $query */
		$query = $this->createQuery();
		$tableName = $query->getSource()->getSelectorName();
		$sql = 'SELECT MIN(uid) uid,form FROM ' . $tableName . ' WHERE pid = ' . intval($pageUid) . ' AND deleted = 0 GROUP BY form';
		$query->statement($sql);
		$queryResult = $query->execute();
		$forms = [];
		foreach ($queryResult as $mail) {
			/** @var Form $form */
			$form = $mail->getForm();
			if ($form !== null) {
				if ((int)$form->getUid() > 0 && !in_array($form->getUid(), $forms)) {
					$forms[$form->getUid()] = $form->getTitle();
				}
			}
		}
		$this->persistenceManager->clearState();
		return $forms;
	}
}
