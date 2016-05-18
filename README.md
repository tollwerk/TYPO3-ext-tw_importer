# tollwerk Import Extension

tbd

## Registering your own extension

### Requirements


* Folder with readable .ods file inside *fileadmin/user_upload/tw_importer/*. Example: *fileadmin/user_upload/tw_importer/yourextensionkey/import_me.ods*
* ext_localconf.php inside your extension directory registering the extension via *$TYPO3_CONF_VARS['EXTCONF']['tw_importer']['registeredImports']['yourextensionkey']*
* Valid array for the 'registeredImports' hook, see [Hooks > registeredImports](#hooks_registeredImports)
* There must be a column named "tx\_twimporter\_id" for every model you wish to be importable in it's corresponding database table. So change your *ext_tables.sql* accordingly. 
* All corresponding models must implement *\Tollwerk\TwImporter\Domain\Model\AbstractImportable*
* All models extending *\Tollwerk\TwImporter\Domain\Model\AbstractImportable* must overide the procted property *$translationParent* with "@var \yourvendor\yournamespace\domain\model\yourmodel" (fully qualified namespace is mandatory!)
* All corresponding repositories must implement *\Tollwerk\TwImporter\Domain\Repository\AbstractImportableRepository*
* Put a *ext_typoscript_setup.txt* in your extension directory. Each importable class must map some fields (tx_twimporter_id, sys_language_uid, l10n_parent, hidden, deleted).
* All your importable classes must have translation enabled must be disabable and deletable. So the following columns must exist in each corresponding table: sys_language_uid, l10n_parent, hidden, deleted
   

**Important:** Don't forget to clear all caches via the **install tool** after addding or changing stuff inside your ext_localconf.php!  

## Hooks

Register for tw\_importer hooks for inside the following global array. The last "[ ]" can be empty or must be filled with an array key / index of your choice, depending on the hook.
 
      $TYPO3_CONF_VARS['EXTCONF']['tw_importer']['HOOK NAME'][] = YOUR VALUES OR CLASSES

**Important:** Don't forget to clear all caches via the **install tool** after registering or changing hooks!   

<a name="hooks_registeredImports"></a>
### registeredImports
Use this hook to register your own extension for import. You must set the extension key of your extenion as index for the array. Example:

    $TYPO3_CONF_VARS['EXTCONF']['tw_importer']['registeredImports']['tx_news'] = array(
		'title' => 'News Extensios, baby!',
		'mapping' => array(
			// see "mapping" in this manual..
		)
	);