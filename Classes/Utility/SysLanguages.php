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
class SysLanguages {
    /**
     * Language suffices
     *
     * @var \array
     */
    protected static $_languageSuffices = null;

    /**
     * Return a list of all registered system language suffices
     *
     * @return array			Language suffices
     */
    public static function suffices($mainLanguageSuffix = 'en') {
        if (self::$_languageSuffices === null) {
            // TODO: Hard wired main language not good
            self::$_languageSuffices = array(0 => $mainLanguageSuffix);

            // Gather all languages
            $languageResult				= $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,language_isocode', 'sys_language', '');
            if ($languageResult) {
                while ($language = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($languageResult)) {
                    self::$_languageSuffices[intval($language['uid'])] = $language['language_isocode'];
                }
            }
        }

        // TODO: Remove this return statement. It's for testing developing purposes only
        return array(
            0 => 'de',
            1 => 'en'
        );

        return self::$_languageSuffices;
    }

    /**
     * Return the language suffix for a system language ID
     *
     * @param \int $sysLanguageUid		System lanugage ID
     * @return \string					Language suffix
     */
    public static function suffix($sysLanguageUid) {
        $languageSuffices				= self::suffices();
        return array_key_exists($sysLanguageUid, $languageSuffices) ? $languageSuffices[$sysLanguageUid] : null;
    }

    /**
     * Return the ID of a language by suffix
     *
     * @param string $suffix			Language suffix
     * @return int						System language ID
     */
    public static function id($suffix) {
        return array_search($suffix, self::suffices());
    }
}
