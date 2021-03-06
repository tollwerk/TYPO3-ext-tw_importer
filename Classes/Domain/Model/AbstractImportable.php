<?php

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Joschi <joschi@tollwerk.de>, tollwerk GmbH
 *           Klaus Fiedler <klaus@tollwerk.de>, tollwerk GmbH
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

namespace Tollwerk\TwImporter\Domain\Model;

use DateTime;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Importable model
 */
abstract class AbstractImportable extends AbstractEntity
{
    /**
     * Multivalue delimiter
     *
     * @var string
     */
    const SPLIT_DELIM = '|';
    /**
     * Main language
     *
     * @var string
     */
    const MAIN_LANGUAGE = 'en';
    /**
     * Import date
     *
     * @var int
     */
    protected $import;
    /**
     * Hidden record
     *
     * @var boolean
     */
    protected $hidden;
    /**
     * Deleted record
     *
     * @var boolean
     */
    protected $deleted;

    /**
     * Set the values from import data
     *
     * @param array $data             Import data
     * @param array $mapping          Column mapping
     * @param string $suffix          Language suffix
     * @param array $languageSuffices All language suffices
     * @param array $extConfig        Additional configuration here
     * @param int $level              Import hierarchy level
     *
     * @return boolean Success
     */
    public function import(
        array $data,
        array $mapping,
        $suffix = null,
        $languageSuffices = [],
        array $extConfig = [],
        $level = 0
    ) {
        $className = get_class($this);
        $this->setImport(time());
        $this->setDeleted(false);

        // Run through each column configuration
        foreach ($mapping as $column => $config) {
            // If the column applies to this model
            if (is_array($config) && array_key_exists($className, $config) && $config[$className]) {
                if ($config[$className] === true) {
                    $columnConfig = array(
                        'column'  => $column,
                        'collate' => true,
                    );
                } elseif (is_string($config[$className])) {
                    $columnConfig = array(
                        'column'  => $config[$className],
                        'collate' => true,
                    );
                } else {
                    $columnConfig = (array)$config[$className];
                    if (empty($columnConfig['column'])) {
                        $columnConfig['column'] = $column;
                    }
                    if (!isset($columnConfig['collate'])) {
                        $columnConfig['collate'] = true;
                    } else {
                        $columnConfig['collate'] = !!$columnConfig['collate'];
                    }
                }

                // Prepare the column value

                $columnValue = $this->prepareValue(strval($data[$column]), $columnConfig);

                /*
                if ($columnConfig['addvalue']) {
                    $columnValue = $columnValue.'||'.$data[$columnConfig['addvalue']];
                }
                */

                $columnOrigValue  = null;
                $columnTranslated = GeneralUtility::underscoredToUpperCamelCase($columnConfig['column']);

                // If a language suffix is given
                if ($suffix) {
                    // Run through all columns and skip this one if it's for another language than the current
                    foreach ($languageSuffices as $languageSuffix) {
                        if (preg_match("%\_$languageSuffix$%", $column) && ($languageSuffix != $suffix)) {
                            continue 2;
                        }
                    }

                    // If language columns should be collated
                    if ($columnConfig['collate']) {
                        $columnTranslated = GeneralUtility::underscoredToUpperCamelCase(preg_replace("%\_$suffix$%",
                            '',
                            $columnConfig['column']));
                        $origColumn       = preg_replace("%\_$suffix$%", '_'.self::MAIN_LANGUAGE, $column);
                        $columnOrigValue  = (($suffix == self::MAIN_LANGUAGE) || !array_key_exists($origColumn,
                                $data)) ? null : $this->prepareValue(strval($data[$origColumn]), $columnConfig);
                    }
                }

                // If there's a special importer method
                if (@is_callable(array($this, 'import'.$columnTranslated))) {
                    call_user_func_array(array($this, 'import'.$columnTranslated),
                        array($columnValue, $columnOrigValue, $extConfig, $data));
                    // Else: if there's a setter for the column
                } elseif (@is_callable(array($this, 'set'.$columnTranslated))) {
                    // echo get_class($this) . '->' . 'set' . $columnTranslated.'<br />'.PHP_EOL;
                    call_user_func_array(array($this, 'set'.$columnTranslated),
                        array($columnValue, $columnOrigValue, $extConfig, $data));
                }
            }
        }

        return true;
    }

    /**
     * Prepare a column value
     *
     * @param string $value Column value
     * @param array $config Column configuration
     *
     * @return string                Prepared value
     */
    protected function prepareValue($value, array $config)
    {
        // Float conversion
        if (isset($config['floatval']) && $config['floatval']) {
            $hasDot   = strpos($value, '.');
            $hasComma = strpos($value, ',');

            // If both separators are present
            if (($hasDot !== false) && ($hasComma !== false)) {

                // If the dot is first
                if ($hasDot < $hasComma) {
                    $value = strtr($value, array('.' => '', ',' => '.'));
                } else {
                    $value = strtr($value, array(',' => ''));
                }

                // Else if there's a comma
            } elseif ($hasComma !== false) {
                $value = strtr($value, ',', '.');
            }

            $value = strval(floatval($value));
        }

        // Boolean conversion
        if (isset($config['boolean']) && $config['boolean']) {
            $value = !!floatval($this->prepareValue($value, array('floatval' => true)));
        }

        // Date conversion
        if (isset($config['date']) && $config['date']) {
            $value = trim($value);
            $value = (strlen($value) && strlen($config['date'])) ?
                DateTime::createFromFormat($config['date'], $value) : null;
        }

        return $value;
    }

    /**
     * Return the import date
     *
     * @return int Import date
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * Set the import date
     *
     * @param int $tstamp Import date
     */
    public function setImport($import)
    {
        $this->import = $import;
    }

    /**
     * Return the hidden state
     *
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden state
     *
     * @param boolean $hidden Hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Return the deleted state
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set the deleted state
     *
     * @param boolean $deleted Deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * Finalize the import of this record
     *
     * @param array $recordCache Internal record cache
     *
     * @return void
     */
    public function finalizeImport(array $recordCache)
    {

    }

    /**
     * Import property objects
     *
     * @param string $property           Property
     * @param string $identifiers        Object identifiers
     * @param string $origIdentifiers    Original language object identifiers
     * @param string $identifierProperty Identifier property
     * @param string $model              Model class name
     * @param string $repository         Repository class name
     * @param boolean $identifierAndPid  Find objects by identfier and PID
     */
    protected function importPropertyObjects(
        $property,
        $identifiers,
        $origIdentifiers,
        $identifierProperty,
        $model,
        $repository,
        $identifierAndPid = true,
        $skipIfUnknown = false
    ) {
        $identifiers      = GeneralUtility::trimExplode(self::SPLIT_DELIM, $identifiers, true);
        $identifierValues = $identifiers;
        if ($origIdentifiers !== null) {
            foreach (
                array_slice(GeneralUtility::trimExplode(self::SPLIT_DELIM, $origIdentifiers, true), 0,
                    count($identifiers)) as $identifierIndex => $origIdentifier
            ) {
                $identifierValues[$identifierIndex] = $origIdentifier;
            }
        }
        $identifierPairs    = array_combine($identifiers, $identifierValues);
        $property           = GeneralUtility::underscoredToUpperCamelCase($property);
        $identifierProperty = GeneralUtility::underscoredToUpperCamelCase($identifierProperty);
        $getter             = 'get'.$property;
        $adder              = 'add'.$property;
        $remover            = 'remove'.$property;
        $identifierGetter   = 'get'.$identifierProperty;
        $identifierSetter   = 'set'.$identifierProperty;
        $model              = '\\'.ltrim($model, '\\');

        // Run through all registered objects
        foreach ($this->$getter() as $object) {
            if (!array_key_exists($object->$identifierGetter(), $identifierPairs)) {
                $this->$remover($object);
            } else {
                unset($identifierPairs[$object->$identifierGetter()]);
            }
        }

        if (count($identifierPairs)) {
            $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
            $repositoryInstance  = $this->objectManager->get($repository);

            // Create new shelfmarks
            foreach ($identifierPairs as $identifier => $origIdentifier) {
                $sysLanguage = $this->getTranslationLanguage();

                // If this is a main language record
                if (!$sysLanguage || !is_subclass_of($model, '\Tollwerk\TwBlog\Domain\Model\AbstractTranslatable')) {

                    // If objects should get fetched via identifier and PID
                    if ($identifierAndPid) {
                        $object = $repositoryInstance->findOneByIdentifierAndPid($identifier, $this->getPid());

                        // Else (e.g. countries)
                    } else {
                        $object = $repositoryInstance->{"findOneBy$identifierProperty"}($identifier);
                    }

                    // If the object couldn't be found but may be created
                    if (!($object instanceof $model) && !$skipIfUnknown) {
                        $object = new $model();
                        $object->$identifierSetter($identifier);
                        $object->setPid($this->getPid());
                    }

                    // Register the object
                    if ($object instanceof $model) {
                        $this->$adder($object);
                    }

                    // Else: Find by parent translation
                } else {
                    $parentObject = $repositoryInstance->findOneByIdentifierAndPid($origIdentifier, $this->getPid(), 0);
                    if ($parentObject instanceof $model) {
                        $object = $repositoryInstance->findOneByTranslationParent($parentObject, $sysLanguage);

                        // If the object couldn't be found but may be created
                        if (!($object instanceof $model) && !$skipIfUnknown) {
                            $object = new $model();
                            $object->$identifierSetter($identifier);
                            $object->setPid($this->getPid());
                            $object->_setProperty('_languageUid', $sysLanguage);
                            $object->setTranslationLanguage($sysLanguage);
                            $object->setTranslationParent($parentObject);

                            // Else: Update the identifier property if necessary
                        } elseif ($object->$identifierGetter() != $identifier) {
                            $object->$identifierSetter($identifier);
                        }

                        // Register the object
                        if ($object instanceof $model) {
                            $this->$adder($object);
                        }
                    }
                }
            }
        }
    }
}
