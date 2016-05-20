<?php

namespace Tollwerk\TwImporter\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Klaus Fiedler <klaus@tollwerk.de>, tollwerk GmbH
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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for getting bits of information via ajax
 */
class Object
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager = NULL;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager = NULL;

    /**
     * @param string $objectClass
     * @param \Tollwerk\TwImporter\Domain\Repository\AbstractImportableRepository $repository
     * @param int $pid
     * @param int $importId
     * @return \Tollwerk\TwImporter\Domain\Model\AbstractImportable
     */
    protected function createNew($objectClass, $repository, $pid, $importId, $translationParent = NULL, $sysLanguage = NULL){

        /**
         * @var \Tollwerk\TwImporter\Domain\Model\AbstractImportable $object
         */
        $object = $this->objectManager->get($objectClass);
        $object->setPid($pid);
        $object->setTxTwimporterId($importId);

        if($translationParent && $sysLanguage){
            $object->_setProperty('_languageUid', $sysLanguage);
            $object->setTranslationLanguage($sysLanguage);
            $object->setTranslationParent($translationParent);
        }

        $repository->add($object);
        $this->persistenceManager->persistAll();

        return $object;
    }

    /**
     * @param array $hierarchy
     * @param int $importId
     * @param int $sysLanguage
     * @return \Tollwerk\TwImporter\Domain\Model\AbstractImportable
     */
    public function createOrGet($hierarchy, $importId, $sysLanguage)
    {
        $objectClass = key($hierarchy);
        $objectConf = $hierarchy[$objectClass];

        /**
         * @var \Tollwerk\TwImporter\Domain\Repository\AbstractImportableRepository $repository
         */
        $repository = $this->objectManager->get($objectConf['repository']);

        /**
         * @var \Tollwerk\TwImporter\Domain\Model\AbstractImportable $emptyObject
         */
        $emptyObject = $this->objectManager->get($objectClass);



        // 1: Always try to find a existing object for
        // the default language first, we need it anyway
        // ---------------------------------------------
        $object = $repository->findOneBySkuAndPid($importId, NULL);



        // 2: If $sysLanguage is default language, return the found
        // object or create and return a new one on the fly
        // --------------------------------------------------------
        if($sysLanguage == 0){
            $status = 'update';

            // If no existing object found, create a new one out of $emptyObject
            if(!($object instanceof $emptyObject)){
                $object = $this->createNew($objectClass, $repository, $objectConf['pid'], $importId);
                $status = 'create';
            }

            return array(
                'object' => $object,
                'status' => $status
            );
        }



        // 3: Finally, if $sysLanguage is NOT the default language,
        // try to return the found translated object or
        // create and return a new translated object on the fly
        // -------------------------------------------------------
        if(!($object instanceof $emptyObject) && $sysLanguage > 0){
            throw new \ErrorException('Tried to create translated object when there was no object for the default language');
        }




        $status = 'update';
        $translatedObject = $repository->findOneByTranslationParent($object,$sysLanguage);
        if(!($translatedObject instanceof $object)){
            $status = 'create';
            $translatedObject = $this->createNew($objectClass, $repository, $objectConf['pid'], $importId, $object, $sysLanguage);
            $this->persistenceManager->persistAll();
        }

        return array(
            'object' => $translatedObject,
            'status' => $status
        );

    }

    /**
     * @param array $hierarchy
     * @param \Tollwerk\TwImporter\Domain\Model\AbstractImportable $object
     */
    public function update($hierarchy, $object){
        $objectClass = key($hierarchy);
        $objectConf = $hierarchy[$objectClass];

        /**
         * @var \Tollwerk\TwImporter\Domain\Repository\AbstractImportableRepository $repository
         */
        $repository = $this->objectManager->get($objectConf['repository']);
        $repository->update($object);
        $this->persistenceManager->persistAll();
    }
}