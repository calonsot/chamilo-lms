<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 * This file contains functions used by the install and upgrade scripts.
 *
 * Ideas for future additions:
 * - a function get_old_version_settings to retrieve the config file settings
 *   of older versions before upgrading.
 */

use Doctrine\ORM\EntityManager;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;

/*      CONSTANTS */
define('SYSTEM_CONFIG_FILENAME', 'configuration.dist.php');

/**
 * This function detects whether the system has been already installed.
 * It should be used for prevention from second running the installation
 * script and as a result - destroying a production system.
 * @return bool     The detected result;
 * @author Ivan Tcholakov, 2010;
 */
function isAlreadyInstalledSystem()
{
    global $new_version, $_configuration;

    if (empty($new_version)) {
        return true; // Must be initialized.
    }

    $current_config_file = api_get_path(CONFIGURATION_PATH).'configuration.php';
    if (!file_exists($current_config_file)) {
        return false; // Configuration file does not exist, install the system.
    }
    require $current_config_file;

    $current_version = null;
    if (isset($_configuration['system_version'])) {
        $current_version = trim($_configuration['system_version']);
    }

    // If the current version is old, upgrading is assumed, the installer goes ahead.
    return empty($current_version) ? false : version_compare($current_version, $new_version, '>=');
}

/**
 * This function checks if a php extension exists or not and returns an HTML status string.
 *
 * @param   string  $extensionName Name of the PHP extension to be checked
 * @param   string  $returnSuccess Text to show when extension is available (defaults to 'Yes')
 * @param   string  $returnFailure Text to show when extension is available (defaults to 'No')
 * @param   boolean $optional Whether this extension is optional (then show unavailable text in orange rather than red)
 * @return  string  HTML string reporting the status of this extension. Language-aware.
 * @author  Christophe Gesch??
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
 */
function checkExtension($extensionName, $returnSuccess = 'Yes', $returnFailure = 'No', $optional = false)
{
    if (extension_loaded($extensionName)) {
        return Display::label($returnSuccess, 'success');
    } else {
        if ($optional) {
            return Display::label($returnFailure, 'warning');
        } else {
            return Display::label($returnFailure, 'important');
        }
    }
}

/**
 * This function checks whether a php setting matches the recommended value
 * @param   string $phpSetting A PHP setting to check
 * @param   string  $recommendedValue A recommended value to show on screen
 * @param   mixed  $returnSuccess What to show on success
 * @param   mixed  $returnFailure  What to show on failure
 * @return  string  A label to show
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function checkPhpSetting($phpSetting, $recommendedValue, $returnSuccess = false, $returnFailure = false)
{
    $currentPhpValue = getPhpSetting($phpSetting);
    if ($currentPhpValue == $recommendedValue) {
        return Display::label($currentPhpValue.' '.$returnSuccess, 'success');
    } else {
        return Display::label($currentPhpValue.' '.$returnSuccess, 'important');
    }
}


/**
 * This function return the value of a php.ini setting if not "" or if exists,
 * otherwise return false
 * @param   string  $phpSetting The name of a PHP setting
 * @return  mixed   The value of the setting, or false if not found
 */
function checkPhpSettingExists($phpSetting)
{
    if (ini_get($phpSetting) != "") {
        return ini_get($phpSetting);
    }

    return false;
}

/**
 * Returns a textual value ('ON' or 'OFF') based on a requester 2-state ini- configuration setting.
 *
 * @param string $val a php ini value
 * @return boolean: ON or OFF
 * @author Joomla <http://www.joomla.org>
 */
function getPhpSetting($val)
{
    return ini_get($val) == '1' ? 'ON' : 'OFF';
}

/**
 * This function returns a string "true" or "false" according to the passed parameter.
 *
 * @param integer  $var  The variable to present as text
 * @return  string  the string "true" or "false"
 * @author Christophe Gesch??
 */
function trueFalse($var)
{
    return $var ? 'true' : 'false';
}

/**
 * Removes memory and time limits as much as possible.
 */
function remove_memory_and_time_limits()
{
    if (function_exists('ini_set')) {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 0);
        error_log('Update-db script: memory_limit set to -1', 0);
        error_log('Update-db script: max_execution_time 0', 0);
    } else {
        error_log('Update-db script: could not change memory and time limits', 0);
    }
}

/**
 * Detects browser's language.
 * @return string       Returns a language identificator, i.e. 'english', 'spanish', ...
 * @author Ivan Tcholakov, 2010
 */
function detect_browser_language()
{
    static $language_index = array(
        'ar' => 'arabic',
        'ast' => 'asturian',
        'bg' => 'bulgarian',
        'bs' => 'bosnian',
        'ca' => 'catalan',
        'zh' => 'simpl_chinese',
        'zh-tw' => 'trad_chinese',
        'cs' => 'czech',
        'da' => 'danish',
        'prs' => 'dari',
        'de' => 'german',
        'el' => 'greek',
        'en' => 'english',
        'es' => 'spanish',
        'eo' => 'esperanto',
        'eu' => 'basque',
        'fa' => 'persian',
        'fr' => 'french',
        'fur' => 'friulian',
        'gl' => 'galician',
        'ka' => 'georgian',
        'hr' => 'croatian',
        'he' => 'hebrew',
        'hi' => 'hindi',
        'id' => 'indonesian',
        'it' => 'italian',
        'ko' => 'korean',
        'lv' => 'latvian',
        'lt' => 'lithuanian',
        'mk' => 'macedonian',
        'hu' => 'hungarian',
        'ms' => 'malay',
        'nl' => 'dutch',
        'ja' => 'japanese',
        'no' => 'norwegian',
        'oc' => 'occitan',
        'ps' => 'pashto',
        'pl' => 'polish',
        'pt' => 'portuguese',
        'pt-br' => 'brazilian',
        'ro' => 'romanian',
        'qu' => 'quechua_cusco',
        'ru' => 'russian',
        'sk' => 'slovak',
        'sl' => 'slovenian',
        'sr' => 'serbian',
        'fi' => 'finnish',
        'sv' => 'swedish',
        'th' => 'thai',
        'tr' => 'turkish',
        'uk' => 'ukrainian',
        'vi' => 'vietnamese',
        'sw' => 'swahili',
        'yo' => 'yoruba'
    );

    $system_available_languages = & get_language_folder_list();

    $accept_languages = strtolower(str_replace('_', '-', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
    foreach ($language_index as $code => $language) {
        if (strpos($accept_languages, $code) === 0) {
            if (!empty($system_available_languages[$language])) {
                return $language;
            }
        }
    }

    $user_agent = strtolower(str_replace('_', '-', $_SERVER['HTTP_USER_AGENT']));
    foreach ($language_index as $code => $language) {
        if (@preg_match("/[\[\( ]{$code}[;,_\-\)]/", $user_agent)) {
            if (!empty($system_available_languages[$language])) {
                return $language;
            }
        }
    }

    return 'english';
}

/*      FILESYSTEM RELATED FUNCTIONS */

/**
 * This function checks if the given folder is writable
 * @param   string  $folder Full path to a folder
 * @param   bool    $suggestion Whether to show a suggestion or not
 * @return  string
 */
function check_writable($folder, $suggestion = false)
{
    if (is_writable($folder)) {
        return Display::label(get_lang('Writable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(get_lang('NotWritable'), 'info');
        } else {
            return Display::label(get_lang('NotWritable'), 'important');
        }
    }
}

/**
 * This function checks if the given folder is readable
 * @param   string  $folder Full path to a folder
 * @param   bool    $suggestion Whether to show a suggestion or not
 *
 * @return  string
 */
function checkReadable($folder, $suggestion = false)
{
    if (is_readable($folder)) {
        return Display::label(get_lang('Readable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(get_lang('NotReadable'), 'info');
        } else {
            return Display::label(get_lang('NotReadable'), 'important');
        }
    }
}

/**
 * This function is similar to the core file() function, except that it
 * works with line endings in Windows (which is not the case of file())
 * @param   string  $filename
 *
 * @return  array   The lines of the file returned as an array
 */
function file_to_array($filename)
{
    if (!is_readable($filename) || is_dir($filename)) {
        return array();
    }
    $fp = fopen($filename, 'rb');
    $buffer = fread($fp, filesize($filename));
    fclose($fp);

    return explode('<br />', nl2br($buffer));
}

/**
 * We assume this function is called from install scripts that reside inside the install folder.
 */
function set_file_folder_permissions()
{
    @chmod('.', 0755); //set permissions on install dir
    @chmod('..', 0755); //set permissions on parent dir of install dir
}

/**
 * Add's a .htaccess file to the courses directory
 * @param string $url_append The path from your webroot to your chamilo root
 * @return bool Result of writing the file
 */
/*function write_courses_htaccess_file($url_append)
{
    $content = file_get_contents(dirname(__FILE__).'/'.COURSES_HTACCESS_FILENAME);
    $content = str_replace('{CHAMILO_URL_APPEND_PATH}', $url_append, $content);
    $fp = @fopen(api_get_path(SYS_COURSE_PATH).'.htaccess', 'w');
    if ($fp) {
        fwrite($fp, $content);
        return fclose($fp);
    }
    return false;
}*/

/**
 * Write the main system config file
 * @param string $path Path to the config file
 */
function write_system_config_file($path)
{
    global $dbHostForm;
    global $dbPortForm;
    global $dbUsernameForm;
    global $dbPassForm;
    global $dbNameForm;
    global $urlForm;
    global $pathForm;
    global $urlAppendPath;
    global $languageForm;
    global $encryptPassForm;
    global $session_lifetime;
    global $new_version;
    global $new_version_stable;

    $root_sys = api_add_trailing_slash(str_replace('\\', '/', realpath($pathForm)));
    $content = file_get_contents(dirname(__FILE__).'/'.SYSTEM_CONFIG_FILENAME);

    $config['{DATE_GENERATED}'] = date('r');
    $config['{DATABASE_HOST}'] = $dbHostForm;
    $config['{DATABASE_PORT}'] = $dbPortForm;
    $config['{DATABASE_USER}'] = $dbUsernameForm;
    $config['{DATABASE_PASSWORD}'] = $dbPassForm;
    $config['{DATABASE_MAIN}'] = $dbNameForm;
    $config['{ROOT_WEB}'] = $urlForm;
    $config['{ROOT_SYS}'] = $root_sys;
    $config['{URL_APPEND_PATH}'] = $urlAppendPath;
    $config['{PLATFORM_LANGUAGE}'] = $languageForm;
    $config['{SECURITY_KEY}'] = md5(uniqid(rand().time()));
    $config['{ENCRYPT_PASSWORD}'] = $encryptPassForm;

    $config['SESSION_LIFETIME'] = $session_lifetime;
    $config['{NEW_VERSION}'] = $new_version;
    $config['NEW_VERSION_STABLE'] = trueFalse($new_version_stable);

    foreach ($config as $key => $value) {
        $content = str_replace($key, $value, $content);
    }

    $fp = @ fopen($path, 'w');

    if (!$fp) {
        echo '<strong><font color="red">Your script doesn\'t have write access to the config directory</font></strong><br />
                        <em>('.str_replace('\\', '/', realpath($path)).')</em><br /><br />
                        You probably do not have write access on Chamilo root directory,
                        i.e. you should <em>CHMOD 777</em> or <em>755</em> or <em>775</em>.<br /><br />
                        Your problems can be related on two possible causes:<br />
                        <ul>
                          <li>Permission problems.<br />Try initially with <em>chmod -R 777</em> and increase restrictions gradually.</li>
                          <li>PHP is running in <a href="http://www.php.net/manual/en/features.safe-mode.php" target="_blank">Safe-Mode</a>. If possible, try to switch it off.</li>
                        </ul>
                        <a href="http://forum.chamilo.org/" target="_blank">Read about this problem in Support Forum</a><br /><br />
                        Please go back to step 5.
                        <p><input type="submit" name="step5" value="&lt; Back" /></p>
                        </td></tr></table></form></body></html>';
        exit;
    }

    fwrite($fp, $content);
    fclose($fp);
}

/**
 * Returns a list of language directories.
 */
function & get_language_folder_list()
{
    static $result;
    if (!is_array($result)) {
        $result = array();
        $exceptions = array('.', '..', 'CVS', '.svn');
        $search       = array('_latin',   '_unicode',   '_corporate',   '_org'  , '_KM',   '_');
        $replace_with = array(' (Latin)', ' (unicode)', ' (corporate)', ' (org)', ' (KM)', ' ');
        $dirname = api_get_path(SYS_LANG_PATH);
        $handle = opendir($dirname);
        while ($entries = readdir($handle)) {
            if (in_array($entries, $exceptions)) {
                continue;
            }
            if (is_dir($dirname.$entries)) {
                if (is_file($dirname.$entries.'/install_disabled')) {
                    // Skip all languages that have this file present, just for
                    // the install process (languages incomplete)
                    continue;
                }
                $result[$entries] = ucwords(str_replace($search, $replace_with, $entries));
            }
        }
        closedir($handle);
        asort($result);
    }
    return $result;
}

/**
 * TODO: my_directory_to_array() - maybe within the main API there is already a suitable function?
 * @param   string  $directory  Full path to a directory
 * @return  array   A list of files and dirs in the directory
 */
function my_directory_to_array($directory)
{
    $array_items = array();
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($directory. "/" . $file)) {
                    $array_items = array_merge($array_items, my_directory_to_array($directory. '/' . $file));
                    $file = $directory . "/" . $file;
                    $array_items[] = preg_replace("/\/\//si", '/', $file);
                }
            }
        }
        closedir($handle);
    }
    return $array_items;
}

/**
 * This function returns the value of a parameter from the configuration file
 *
 * WARNING - this function relies heavily on global variables $updateFromConfigFile
 * and $configFile, and also changes these globals. This can be rewritten.
 *
 * @param   string  $param  the parameter of which the value is returned
 * @param   string  If we want to give the path rather than take it from POST
 * @return  string  the value of the parameter
 * @author Olivier Brouckaert
 * @author Reworked by Ivan Tcholakov, 2010
 */
function get_config_param($param, $updatePath = '')
{
    global $configFile, $updateFromConfigFile;

    // Look if we already have the queried parameter.
    if (is_array($configFile) && isset($configFile[$param])) {
        return $configFile[$param];
    }
    if (empty($updatePath) && !empty($_POST['updatePath'])) {
        $updatePath = $_POST['updatePath'];
    }

    if (empty($updatePath)) {
        $updatePath = api_get_path(SYS_PATH);
    }
    $updatePath = api_add_trailing_slash(str_replace('\\', '/', realpath($updatePath)));
    $updateFromInstalledVersionFile = '';

    if (empty($updateFromConfigFile)) {
        // If update from previous install was requested,
        // try to recover config file from Chamilo 1.9.x
        if (file_exists($updatePath.'main/inc/conf/configuration.php')) {
            $updateFromConfigFile = 'main/inc/conf/configuration.php';
        } else {
            // Give up recovering.
            //error_log('Chamilo Notice: Could not find previous config file at '.$updatePath.'main/inc/conf/configuration.php nor at '.$updatePath.'claroline/inc/conf/claro_main.conf.php in get_config_param(). Will start new config (in '.__FILE__.', line '.__LINE__.')', 0);
            return null;
        }
    }

    if (file_exists($updatePath.$updateFromConfigFile) &&
        !is_dir($updatePath.$updateFromConfigFile)
    ) {
        require $updatePath.$updateFromConfigFile;
        $config = new Zend\Config\Config($_configuration);
        return $config->get($param);
    }

    error_log('Config array could not be found in get_config_param()', 0);
    return null;

    /*if (file_exists($updatePath.$updateFromConfigFile)) {
        return $val;
    } else {
        error_log('Config array could not be found in get_config_param()', 0);
        return null;
    }*/
}

/*      DATABASE RELATED FUNCTIONS */

/**
 * Gets a configuration parameter from the database. Returns returns null on failure.
 * @param   string  $param Name of param we want
 * @return  mixed   The parameter value or null if not found
 */
function get_config_param_from_db($param = '')
{
    if (($res = Database::query("SELECT * FROM settings_current WHERE variable = '$param'")) !== false) {
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            return $row['selected_value'];
        }
    }
    return null;
}

/**
 * Connect to the database and returns the entity manager
 * @param string  $dbHostForm DB host
 * @param string  $dbUsernameForm DB username
 * @param string  $dbPassForm DB password
 * @param string  $dbNameForm DB name
 * @param int     $dbPortForm DB port
 *
 * @return EntityManager
 */
function connectToDatabase($dbHostForm, $dbUsernameForm, $dbPassForm, $dbNameForm, $dbPortForm = 3306)
{
    $dbParams = array(
        'driver' => 'pdo_mysql',
        'host' => $dbHostForm,
        'port' => $dbPortForm,
        'user' => $dbUsernameForm,
        'password' => $dbPassForm,
        'dbname' => $dbNameForm
    );

    $database = new \Database();
    $database->connect($dbParams);

    return $database->getManager();
}

/*      DISPLAY FUNCTIONS */

/**
 * This function prints class=active_step $current_step=$param
 * @param   int $param  A step in the installer process
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function step_active($param)
{
    global $current_step;
    if ($param == $current_step) {
        echo 'class="current-step" ';
    }
}

/**
 * This function displays the Step X of Y -
 * @return  string  String that says 'Step X of Y' with the right values
 */
function display_step_sequence()
{
    global $current_step;
    return get_lang('Step'.$current_step).' &ndash; ';
}

/**
 * Displays a drop down box for selection the preferred language.
 */
function display_language_selection_box($name = 'language_list', $default_language = 'english')
{
    // Reading language list.
    $language_list = get_language_folder_list();

    /*
    // Reduction of the number of languages shown. Enable this fragment of code for customization purposes.
    // Modify the language list according to your preference. Don't exclude the 'english' item.
    $language_to_display = array('asturian', 'bulgarian', 'english', 'italian', 'french', 'slovenian', 'slovenian_unicode', 'spanish');
    foreach ($language_list as $key => & $value) {
        if (!in_array($key, $language_to_display)) {
            unset($language_list[$key]);
        }
    }
    */

    // Sanity checks due to the possibility for customizations.
    if (!is_array($language_list) || empty($language_list)) {
        $language_list = array('english' => 'English');
    }

    // Sorting again, if it is necessary.
    //asort($language_list);

    // More sanity checks.
    if (!array_key_exists($default_language, $language_list)) {
        if (array_key_exists('english', $language_list)) {
            $default_language = 'english';
        } else {
            $language_keys = array_keys($language_list);
            $default_language = $language_keys[0];
        }
    }

    // Displaying the box.
    $html = '';
    $html .= "\t\t<select class='selectpicker show-tick' name=\"$name\">\n";
    foreach ($language_list as $key => $value) {
        if ($key == $default_language) {
            $option_end = ' selected="selected">';
        } else {
            $option_end = '>';
        }
        $html .= "\t\t\t<option value=\"$key\"$option_end";
        $html .= $value;
        $html .= "</option>\n";
    }
    $html .= "\t\t</select>\n";
    return $html;
}

/**
 * This function displays a language dropdown box so that the installatioin
 * can be done in the language of the user
 */
function display_language_selection()
{ ?>
    <h2><?php get_lang('WelcomeToTheChamiloInstaller'); ?></h2>
    <div class="RequirementHeading">
        <h2><?php echo display_step_sequence(); ?>
            <?php echo get_lang('InstallationLanguage');?>
        </h2>
        <p><?php echo get_lang('PleaseSelectInstallationProcessLanguage'); ?>:</p>
        <form id="lang_form" method="post" action="<?php echo api_get_self(); ?>">
        <div class="form-group">
            <div class="col-sm-4">
                <?php echo display_language_selection_box('language_list', api_get_interface_language()); ?>
            </div>
            <div class="col-sm-6">
                <button type="submit" name="step1" class="btn btn-success" value="<?php echo get_lang('Next'); ?>">
                    <em class="fa fa-forward"> </em>
                    <?php echo get_lang('Next'); ?></button>
            </div>
        </div>

        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        </form>

    </div>
    <div class="RequirementHeading">
        <?php echo get_lang('YourLanguageNotThereContactUs'); ?>
    </div>
<?php
}

/**
 * This function displays the requirements for installing Chamilo.
 *
 * @param string $installType
 * @param boolean $badUpdatePath
 * @param boolean $badUpdatePath
 * @param string $updatePath The updatePath given (if given)
 * @param array $update_from_version_8 The different subversions from version 1.9
 *
 * @author unknow
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function display_requirements(
    $installType,
    $badUpdatePath,
    $updatePath = '',
    $update_from_version_8 = array()
) {
    global $_setting;
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Requirements')."</h2></div>";
    echo '<div class="RequirementText">';
    echo '<strong>'.get_lang('ReadThoroughly').'</strong><br />';
    echo get_lang('MoreDetails').' <a href="../../documentation/installation_guide.html" target="_blank">'.get_lang('ReadTheInstallationGuide').'</a>.<br />'."\n";

    if ($installType == 'update') {
        echo get_lang('IfYouPlanToUpgradeFromOlderVersionYouMightWantToHaveAlookAtTheChangelog').'<br />';
    }
    echo '</div>';

    //  SERVER REQUIREMENTS
    echo '<div class="RequirementHeading"><h4>'.get_lang('ServerRequirements').'</h4>';

    $timezone = checkPhpSettingExists("date.timezone");
    if (!$timezone) {
        echo "<div class='warning-message'>".
            Display::return_icon('warning.png', get_lang('Warning'), '', ICON_SIZE_MEDIUM).
            get_lang("DateTimezoneSettingNotSet")."</div>";
    }

    echo '<div class="RequirementText">'.get_lang('ServerRequirementsInfo').'</div>';
    echo '<div class="RequirementContent">';
    echo '<table class="table">
            <tr>
                <td class="requirements-item">'.get_lang('PHPVersion').' >= '.REQUIRED_PHP_VERSION.'</td>
                <td class="requirements-value">';
    if (phpversion() < REQUIRED_PHP_VERSION) {
        echo '<strong><font color="red">'.get_lang('PHPVersionError').'</font></strong>';
    } else {
        echo '<strong><font color="green">'.get_lang('PHPVersionOK'). ' '.phpversion().'</font></strong>';
    }
    echo '</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.session.php" target="_blank">Session</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('session', get_lang('Yes'), get_lang('ExtensionSessionsNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.mysql.php" target="_blank">MySQL</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('mysql', get_lang('Yes'), get_lang('ExtensionMySQLNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.zlib.php" target="_blank">Zlib</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('zlib', get_lang('Yes'), get_lang('ExtensionZlibNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.pcre.php" target="_blank">Perl-compatible regular expressions</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('pcre', get_lang('Yes'), get_lang('ExtensionPCRENotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.xml.php" target="_blank">XML</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('xml', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.intl.php" target="_blank">Internationalization</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('intl', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
               <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.json.php" target="_blank">JSON</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('json', get_lang('Yes'), get_lang('No')).'</td>
            </tr>

             <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.image.php" target="_blank">GD</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('gd', get_lang('Yes'), get_lang('ExtensionGDNotAvailable')).'</td>
            </tr>

            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.mbstring.php" target="_blank">Multibyte string</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('mbstring', get_lang('Yes'), get_lang('ExtensionMBStringNotAvailable'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.iconv.php" target="_blank">Iconv</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('iconv', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.ldap.php" target="_blank">LDAP</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('ldap', get_lang('Yes'), get_lang('ExtensionLDAPNotAvailable'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://xapian.org/" target="_blank">Xapian</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('xapian', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>

            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.curl.php" target="_blank">cURL</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('curl', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>

          </table>';
    echo '  </div>';
    echo '</div>';

    // RECOMMENDED SETTINGS
    // Note: these are the settings for Joomla, does this also apply for Chamilo?
    // Note: also add upload_max_filesize here so that large uploads are possible
    echo '<div class="RequirementHeading"><h4>'.get_lang('RecommendedSettings').'</h4>';
    echo '<div class="RequirementText">'.get_lang('RecommendedSettingsInfo').'</div>';
    echo '<div class="RequirementContent">';
    echo '<table class="table">
            <tr>
                <th>'.get_lang('Setting').'</th>
                <th>'.get_lang('Recommended').'</th>
                <th>'.get_lang('Actual').'</th>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/features.safe-mode.php">Safe Mode</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('safe_mode', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.errorfunc.php#ini.display-errors">Display Errors</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('display_errors', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.file-uploads">File Uploads</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('file_uploads', 'ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-gpc">Magic Quotes GPC</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('magic_quotes_gpc', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-runtime">Magic Quotes Runtime</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('magic_quotes_runtime', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/security.globals.php">Register Globals</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('register_globals', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.session.php#ini.session.auto-start">Session auto start</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('session.auto_start', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.short-open-tag">Short Open Tag</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('short_open_tag', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly">Cookie HTTP Only</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('session.cookie_httponly', 'ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.upload-max-filesize">Maximum upload file size</a></td>
                <td class="requirements-recommended">'.Display::label('>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('upload_max_filesize'), REQUIRED_MIN_UPLOAD_MAX_FILESIZE).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.post-max-size">Maximum post size</a></td>
                <td class="requirements-recommended">'.Display::label('>= '.REQUIRED_MIN_POST_MAX_SIZE.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('post_max_size'), REQUIRED_MIN_POST_MAX_SIZE).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit">Memory Limit</a></td>
                <td class="requirements-recommended">'.Display::label('>= '.REQUIRED_MIN_MEMORY_LIMIT.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('memory_limit'), REQUIRED_MIN_MEMORY_LIMIT).'</td>
            </tr>
          </table>';
    echo '  </div>';
    echo '</div>';

    // DIRECTORY AND FILE PERMISSIONS
    echo '<div class="RequirementHeading"><h4>'.get_lang('DirectoryAndFilePermissions').'</h4>';
    echo '<div class="RequirementText">'.get_lang('DirectoryAndFilePermissionsInfo').'</div>';
    echo '<div class="RequirementContent">';

    $course_attempt_name = '__XxTestxX__';
    $course_dir = api_get_path(SYS_COURSE_PATH).$course_attempt_name;

    //Just in case
    @unlink($course_dir.'/test.php');
    @rmdir($course_dir);

    $perms_dir = array(0777, 0755, 0775, 0770, 0750, 0700);
    $perms_fil = array(0666, 0644, 0664, 0660, 0640, 0600);

    $course_test_was_created = false;

    $dir_perm_verified = 0777;
    foreach ($perms_dir as $perm) {
        $r = @mkdir($course_dir, $perm);
        if ($r === true) {
            $dir_perm_verified = $perm;
            $course_test_was_created = true;
            break;
        }
    }

    $fil_perm_verified = 0666;
    $file_course_test_was_created = false;

    if (is_dir($course_dir)) {
        foreach ($perms_fil as $perm) {
            if ($file_course_test_was_created == true) {
                break;
            }
            $r = @touch($course_dir.'/test.php',$perm);
            if ($r === true) {
                $fil_perm_verified = $perm;
                if (check_course_script_interpretation($course_dir, $course_attempt_name, 'test.php')) {
                    $file_course_test_was_created = true;
                }
            }
        }
    }

    @unlink($course_dir.'/test.php');
    @rmdir($course_dir);

    $_SESSION['permissions_for_new_directories'] = $_setting['permissions_for_new_directories'] = $dir_perm_verified;
    $_SESSION['permissions_for_new_files'] = $_setting['permissions_for_new_files'] = $fil_perm_verified;

    $dir_perm = Display::label('0'.decoct($dir_perm_verified), 'info');
    $file_perm = Display::label('0'.decoct($fil_perm_verified), 'info');

    $courseTestLabel = Display::label(get_lang('No'), 'important');

    if ($course_test_was_created && $file_course_test_was_created) {
        $courseTestLabel = Display::label(get_lang('Yes'), 'success');
    }

    if ($course_test_was_created && !$file_course_test_was_created) {
        $courseTestLabel = Display::label(
            sprintf(
                get_lang('InstallWarningCouldNotInterpretPHP'),
                api_get_path(WEB_COURSE_PATH).$course_attempt_name.'/test.php'
            ),
            'warning'
        );
    }

    if (!$course_test_was_created && !$file_course_test_was_created) {
        $courseTestLabel = Display::label(get_lang('No'), 'important');
    }

    $oldConf = '';
    if (file_exists(api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php')) {
        $oldConf = '<tr>
            <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'inc/conf</td>
            <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'inc/conf').'</td>
        </tr>';
    }

    echo '<table class="table">
            '.$oldConf.'
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_APP_PATH).'</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_APP_PATH)).'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'default_course_document/images/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'default_course_document/images/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'lang/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'lang/', true).' <br />('.get_lang('SuggestionOnlyToEnableSubLanguageFeature').')</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_PATH).'vendor/</td>
                <td class="requirements-value">'.checkReadable(api_get_path(SYS_PATH).'vendor').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_PUBLIC_PATH).'</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_PUBLIC_PATH)).'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.get_lang('CourseTestWasCreated').'</td>
                <td class="requirements-value">'.$courseTestLabel.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.get_lang('PermissionsForNewDirs').'</td>
                <td class="requirements-value">'.$dir_perm.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.get_lang('PermissionsForNewFiles').'</td>
                <td class="requirements-value">'.$file_perm.' </td>
            </tr>
            ';
    echo '    </table>';
    echo '  </div>';
    echo '</div>';

    if ($installType == 'update' && (empty($updatePath) || $badUpdatePath)) {
        if ($badUpdatePath) { ?>
            <div class="alert alert-warning">
                <?php echo get_lang('Error'); ?>!<br />
                Chamilo <?php echo implode('|', $update_from_version_8).' '.get_lang('HasNotBeenFoundInThatDir'); ?>.
            </div>
        <?php }
        else {
            echo '<br />';
        }
        ?>
            <div class="row">
                <div class="col-md-12">
                    <p><?php echo get_lang('OldVersionRootPath'); ?>:
                        <input type="text" name="updatePath" size="50" value="<?php echo ($badUpdatePath && !empty($updatePath)) ? htmlentities($updatePath) : api_get_path(SYS_SERVER_ROOT_PATH).'old_version/'; ?>" />
                    </p>
                    <p>
                        <button type="submit" class="btn btn-default" name="step1" value="<?php echo get_lang('Back'); ?>" >
                            <em class="fa fa-backward"> <?php echo get_lang('Back'); ?></em>
                        </button>
                        <input type="hidden" name="is_executable" id="is_executable" value="-" />
                        <button type="submit" class="btn btn-success" name="<?php echo (isset($_POST['step2_update_6']) ? 'step2_update_6' : 'step2_update_8'); ?>" value="<?php echo get_lang('Next'); ?> &gt;" >
                            <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
                        </button>
                    </p>
                </div>
            </div>

        <?php
    } else {
        $error = false;
        // First, attempt to set writing permissions if we don't have them yet
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();

        $notWritable = array();

        $checked_writable = api_get_path(SYS_APP_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_PUBLIC_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        if ($course_test_was_created == false) {
            $error = true;
        }

        $checked_writable = api_get_path(CONFIGURATION_PATH).'configuration.php';
        if (file_exists($checked_writable) && !is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm_file);
        }

        // Second, if this fails, report an error

        //--> The user would have to adjust the permissions manually
        if (count($notWritable) > 0) {
            $error = true;
            echo '<div class="error-message">';
                echo '<center><h3>'.get_lang('Warning').'</h3></center>';
                printf(get_lang('NoWritePermissionPleaseReadInstallGuide'), '</font>
                <a href="../../documentation/installation_guide.html" target="blank">', '</a> <font color="red">');
            echo '</div>';
            echo '<ul>';
            foreach ($notWritable as $value) {
                echo '<li>'.$value.'</li>';
            }
            echo '</ul>';
        } elseif (file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php')) {
            // Check wether a Chamilo configuration file already exists.
            echo '<div class="alert alert-warning"><h4><center>';
            echo get_lang('WarningExistingLMSInstallationDetected');
            echo '</center></h4></div>';
        }

        // And now display the choice buttons (go back or install)
        ?>
        <p align="center" style="padding-top:15px">
        <button type="submit" name="step1" class="btn btn-default" onclick="javascript: window.location='index.php'; return false;" value="<?php echo get_lang('Previous'); ?>" >
            <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
        </button>
        <button type="submit" name="step2_install" class="btn btn-success" value="<?php echo get_lang("NewInstallation"); ?>" <?php if ($error) echo 'disabled="disabled"'; ?> >
            <em class="fa fa-forward"> </em> <?php echo get_lang('NewInstallation'); ?>
        </button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        <?php
        // Real code
        echo '<button type="submit" class="btn btn-default" name="step2_update_8" value="Upgrade from Chamilo 1.9.x"';
        if ($error) echo ' disabled="disabled"';
        echo ' ><em class="fa fa-forward"> </em> '.get_lang('UpgradeFromLMS19x').'</button>';

        echo '</p>';
    }
}

/**
 * Displays the license (GNU GPL) as step 2, with
 * - an "I accept" button named step3 to proceed to step 3;
 * - a "Back" button named step1 to go back to the first step.
 */

function display_license_agreement()
{
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Licence').'</h2>';
    echo '<p>'.get_lang('LMSLicenseInfo').'</p>';
    echo '<p><a href="../../documentation/license.html" target="_blank">'.get_lang('PrintVers').'</a></p>';
    echo '</div>';
    ?>
    <div class="row">
        <div class="col-md-12">
            <pre style="overflow: auto; height: 200px; margin-top: 5px;">
                <?php echo api_htmlentities(@file_get_contents(api_get_path(SYS_PATH).'documentation/license.txt')); ?>
            </pre>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="accept" id="accept_licence" value="1" />
                    <?php echo get_lang('IAccept'); ?>
                </label>
            </div>
            <button type="submit" class="btn btn-default" name="step1" value="&lt; <?php echo get_lang('Previous'); ?>" >
                <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
            </button>
            <input type="hidden" name="is_executable" id="is_executable" value="-" />
            <button type="submit" class="btn btn-success" name="step3" onclick="javascript: if(!document.getElementById('accept_licence').checked) { alert('<?php echo get_lang('YouMustAcceptLicence')?>');return false;}" value="<?php echo get_lang('Next'); ?> &gt;" >
                <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
            </button>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <p class="alert alert-info"><?php echo get_lang('LMSMediaLicense'); ?></p>
        </div>
    </div>

    <!-- Contact information form -->
    <div>
        <a href="javascript://" class = "advanced_parameters" >
        <span id="img_plus_and_minus">&nbsp;<img src="<?php echo api_get_path(WEB_IMG_PATH) ?>div_hide.gif" alt="<?php echo get_lang('Hide') ?>" title="<?php echo get_lang('Hide')?>" style ="vertical-align:middle" />&nbsp;<?php echo get_lang('ContactInformation') ?></span>
        </a>
    </div>

    <div id="id_contact_form" style="display:block">
        <div class="normal-message"><?php echo get_lang('ContactInformationDescription') ?></div>
        <div id="contact_registration">
            <p><?php echo get_contact_registration_form() ?></p><br />
        </div>
    </div>
    <?php
}


/**
 * Get contact registration form
 */
function get_contact_registration_form()
{

    $html ='
   <form class="form-horizontal">
    <div class="panel panel-default">
    <div class="panel-body">
    <div id="div_sent_information"></div>
    <div class="form-group">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('Name').'</label>
            <div class="col-sm-9"><input id="person_name" class="form-control" type="text" name="person_name" size="30" /></div>
    </div>
    <div class="form-group">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('Email').'</label>
            <div class="col-sm-9"><input id="person_email" class="form-control" type="text" name="person_email" size="30" /></div>
    </div>
    <div class="form-group">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('CompanyName').'</label>
            <div class="col-sm-9"><input id="company_name" class="form-control" type="text" name="company_name" size="30" /></div>
    </div>
    <div class="form-group">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('CompanyActivity').'</label>
            <div class="col-sm-9">
                    <select class="selectpicker show-tick" name="company_activity" id="company_activity" >
                            <option value="">--- '.get_lang('SelectOne').' ---</option>
                            <Option value="Advertising/Marketing/PR">Advertising/Marketing/PR</Option><Option value="Agriculture/Forestry">Agriculture/Forestry</Option>
                            <Option value="Architecture">Architecture</Option><Option value="Banking/Finance">Banking/Finance</Option>
                            <Option value="Biotech/Pharmaceuticals">Biotech/Pharmaceuticals</Option><Option value="Business Equipment">Business Equipment</Option>
                            <Option value="Business Services">Business Services</Option><Option value="Construction">Construction</Option>
                            <Option value="Consulting/Research">Consulting/Research</Option><Option value="Education">Education</Option>
                            <Option value="Engineering">Engineering</Option><Option value="Environmental">Environmental</Option>
                            <Option value="Government">Government</Option><Option value="Healthcare">Health Care</Option>
                            <Option value="Hospitality/Lodging/Travel">Hospitality/Lodging/Travel</Option><Option value="Insurance">Insurance</Option>
                            <Option value="Legal">Legal</Option><Option value="Manufacturing">Manufacturing</Option>
                            <Option value="Media/Entertainment">Media/Entertainment</Option><Option value="Mortgage">Mortgage</Option>
                            <Option value="Non-Profit">Non-Profit</Option><Option value="Real Estate">Real Estate</Option>
                            <Option value="Restaurant">Restaurant</Option><Option value="Retail">Retail</Option>
                            <Option value="Shipping/Transportation">Shipping/Transportation</Option>
                            <Option value="Technology">Technology</Option><Option value="Telecommunications">Telecommunications</Option>
                            <Option value="Other">Other</Option>
                    </select>
            </div>
    </div>

    <div class="form-group">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('PersonRole').'</label>
            <div class="col-sm-9">
                    <select class="selectpicker show-tick" name="person_role" id="person_role" >
                            <option value="">--- '.get_lang('SelectOne').' ---</option>
                            <Option value="Administration">Administration</Option><Option value="CEO/President/ Owner">CEO/President/ Owner</Option>
                            <Option value="CFO">CFO</Option><Option value="CIO/CTO">CIO/CTO</Option>
                            <Option value="Consultant">Consultant</Option><Option value="Customer Service">Customer Service</Option>
                            <Option value="Engineer/Programmer">Engineer/Programmer</Option><Option value="Facilities/Operations">Facilities/Operations</Option>
                            <Option value="Finance/ Accounting Manager">Finance/ Accounting Manager</Option><Option value="Finance/ Accounting Staff">Finance/ Accounting Staff</Option>
                            <Option value="General Manager">General Manager</Option><Option value="Human Resources">Human Resources</Option>
                            <Option value="IS/IT Management">IS/IT Management</Option><Option value="IS/ IT Staff">IS/ IT Staff</Option>
                            <Option value="Marketing Manager">Marketing Manager</Option><Option value="Marketing Staff">Marketing Staff</Option>
                            <Option value="Partner/Principal">Partner/Principal</Option><Option value="Purchasing Manager">Purchasing Manager</Option>
                            <Option value="Sales/ Business Dev. Manager">Sales/ Business Dev. Manager</Option><Option value="Sales/ Business Dev.">Sales/ Business Dev.</Option>
                            <Option value="Vice President/Senior Manager">Vice President/Senior Manager</Option><Option value="Other">Other</Option>
                    </select>
            </div>
    </div>

    <div class="form-group">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('CompanyCountry').'</label>
            <div class="col-sm-9">'.get_countries_list_from_array(true).'</div>
    </div>
    <div class="form-group">
            <label class="col-sm-3">'.get_lang('CompanyCity').'</label>
            <div class="col-sm-9">
                    <input type="text" class="form-control" id="company_city" name="company_city" size="30" />
            </div>
    </div>
    <div class="form-group">
            <label class="col-sm-3">'.get_lang('WhichLanguageWouldYouLikeToUseWhenContactingYou').'</label>
            <div class="col-sm-9">
                    <select class="selectpicker show-tick" id="language" name="language">
                            <option value="bulgarian">Bulgarian</option>
                            <option value="indonesian">Bahasa Indonesia</option>
                            <option value="bosnian">Bosanski</option>
                            <option value="german">Deutsch</option>
                            <option selected="selected" value="english">English</option>
                            <option value="spanish">Spanish</option>
                            <option value="french">Français</option>
                            <option value="italian">Italian</option>
                            <option value="hungarian">Magyar</option>
                            <option value="dutch">Nederlands</option>
                            <option value="brazilian">Português do Brasil</option>
                            <option value="portuguese">Português europeu</option>
                            <option value="slovenian">Slovenčina</option>
                    </select>
            </div>
    </div>

    <div class="form-group">
            <label class="col-sm-3">'.get_lang('HaveYouThePowerToTakeFinancialDecisions').'</label>
            <div class="col-sm-9">
                <div class="radio">
                    <label>
                        <input type="radio" name="financial_decision" id="financial_decision1" value="1" checked /> ' . get_lang('Yes') . '
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="financial_decision" id="financial_decision2" value="0" /> '.get_lang('No').'
                    </label>
                </div>
            </div>
    </div>
    <div class="clear"></div>
    <div class="form-group">
            <div class="col-sm-3">&nbsp;</div>
            <div class="col-sm-9"><button type="button" class="btn btn-default" onclick="javascript:send_contact_information();" value="'.get_lang('SendInformation').'" ><em class="fa fa-floppy-o"></em> '.get_lang('SendInformation').'</button></div>
    </div>
    <div class="form-group">
            <div class="col-sm-3">&nbsp;</div>
            <div class="col-sm-9"><span class="form_required">*</span><small>'.get_lang('FieldRequired').'</small></div>
    </div></div></div>
</form>';

    return $html;
}

/**
 * Displays a parameter in a table row.
 * Used by the display_database_settings_form function.
 * @param   string  Type of install
 * @param   string  Name of parameter
 * @param   string  Field name (in the HTML form)
 * @param   string  Field value
 * @param   string  Extra notice (to show on the right side)
 * @param   boolean Whether to display in update mode
 * @param   string  Additional attribute for the <tr> element
 * @return  void    Direct output
 */
function displayDatabaseParameter(
    $installType,
    $parameterName,
    $formFieldName,
    $parameterValue,
    $extra_notice,
    $displayWhenUpdate = true,
    $tr_attribute = ''
) {
    //echo "<tr ".$tr_attribute.">";
    echo "<label class='col-sm-4'>$parameterName</label>";

    if ($installType == INSTALL_TYPE_UPDATE && $displayWhenUpdate) {
        echo '<input type="hidden" name="'.$formFieldName.'" id="'.$formFieldName.'" value="'.api_htmlentities($parameterValue).'" />'.$parameterValue;
    } else {
        $inputType = $formFieldName == 'dbPassForm' ? 'password' : 'text';

        //Slightly limit the length of the database prefix to avoid having to cut down the databases names later on
        $maxLength = $formFieldName == 'dbPrefixForm' ? '15' : MAX_FORM_FIELD_LENGTH;
        if ($installType == INSTALL_TYPE_UPDATE) {
            echo '<input type="hidden" name="'.$formFieldName.'" id="'.$formFieldName.'" value="'.api_htmlentities($parameterValue).'" />';
            echo api_htmlentities($parameterValue);
        } else {
            echo '<div class="col-sm-5"><input type="' . $inputType . '" class="form-control" size="' . DATABASE_FORM_FIELD_DISPLAY_LENGTH . '" maxlength="' . $maxLength . '" name="' . $formFieldName . '" id="' . $formFieldName . '" value="' . api_htmlentities($parameterValue) . '" />' . "</div>";
            echo '<div class="col-sm-3">' . $extra_notice . '</div>';
        }

    }

}

/**
 * Displays step 3 - a form where the user can enter the installation settings
 * regarding the databases - login and password, names, prefixes, single
 * or multiple databases, tracking or not...
 * @param string $installType
 * @param string $dbHostForm
 * @param string $dbUsernameForm
 * @param string $dbPassForm
 * @param string $dbNameForm
 * @param int    $dbPortForm
 * @param string $installationProfile
 */
function display_database_settings_form(
    $installType,
    $dbHostForm,
    $dbUsernameForm,
    $dbPassForm,
    $dbNameForm,
    $dbPortForm = 3306,
    $installationProfile = ''
) {
    if ($installType == 'update') {
        global $_configuration;
        $dbHostForm = $_configuration['db_host'];
        $dbUsernameForm = $_configuration['db_user'];
        $dbPassForm = $_configuration['db_password'];
        $dbNameForm = $_configuration['main_database'];
        $dbPortForm = isset($_configuration['db_port']) ? $_configuration['db_port'] : '';

        echo '<div class="RequirementHeading"><h2>' . display_step_sequence() .get_lang('DBSetting') . '</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('DBSettingUpgradeIntro');
        echo '</div>';
    } else {
        echo '<div class="RequirementHeading"><h2>' . display_step_sequence() .get_lang('DBSetting') . '</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('DBSettingIntro');
        echo '</div>';
    }
    ?>
    <div class="panel panel-default">
        <div class="panel-body">
        <div class="form-group">
            <label class="col-sm-4"><?php echo get_lang('DBHost'); ?> </label>
            <?php if ($installType == 'update'){ ?>
            <div class="col-sm-5">
                <input type="hidden" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" /><?php echo $dbHostForm; ?>
            </div>
            <div class="col-sm-3"></div>
            <?php }else{ ?>
            <div class="col-sm-5">
                <input type="text" class="form-control" size="25" maxlength="50" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" />
            </div>
            <div class="col-sm-3"><?php echo get_lang('EG').' localhost'; ?></div>
            <?php } ?>
        </div>
        <div class="form-group">
            <label class="col-sm-4"><?php echo get_lang('DBPort'); ?> </label>
            <?php if ($installType == 'update'){ ?>
            <div class="col-sm-5">
                <input type="hidden" name="dbPortForm" value="<?php echo htmlentities($dbPortForm); ?>" /><?php echo $dbPortForm; ?>
            </div>
            <div class="col-sm-3"></div>
            <?php }else{ ?>
            <div class="col-sm-5">
                <input type="text" class="form-control" size="25" maxlength="50" name="dbPortForm" value="<?php echo htmlentities($dbPortForm); ?>" />
            </div>
            <div class="col-sm-3"><?php echo get_lang('EG').' 3306'; ?></div>
            <?php } ?>
        </div>
        <div class="form-group">
            <?php
                //database user username
                $example_login = get_lang('EG').' root';
                displayDatabaseParameter($installType, get_lang('DBLogin'), 'dbUsernameForm', $dbUsernameForm, $example_login);
            ?>
        </div>
        <div class="form-group">
            <?php
            //database user password
            $example_password = get_lang('EG').' '.api_generate_password();
            displayDatabaseParameter($installType, get_lang('DBPassword'), 'dbPassForm', $dbPassForm, $example_password);

            ?>
        </div>
        <div class="form-group">
            <?php
            //Database Name fix replace weird chars
            if ($installType != INSTALL_TYPE_UPDATE) {
                $dbNameForm = str_replace(array('-','*', '$', ' ', '.'), '', $dbNameForm);
                $dbNameForm = api_replace_dangerous_char($dbNameForm);
            }

            displayDatabaseParameter(
                $installType,
                get_lang('MainDB'),
                'dbNameForm',
                $dbNameForm,
                '&nbsp;',
                null,
                'id="optional_param1"'
                );
            ?>
        </div>
       <?php if ($installType != INSTALL_TYPE_UPDATE) { ?>
        <div class="form-group">
            <div class="col-sm-3"></div>
            <div class="col-sm-9">
            <button type="submit" class="btn btn-primary" name="step3" value="step3">
                <em class="fa fa-refresh"> </em>
                <?php echo get_lang('CheckDatabaseConnection'); ?>
            </button>
            </div>
        </div>
        <?php } ?>

        </div>
    </div>

        <?php

        $database_exists_text = '';
        $manager = null;
        try {
            $manager = connectToDatabase(
                $dbHostForm,
                $dbUsernameForm,
                $dbPassForm,
                null,
                $dbPortForm
            );
            $databases = $manager->getConnection()->getSchemaManager()->listDatabases();
            if (in_array($dbNameForm, $databases)) {
                $database_exists_text = '<div class="alert alert-warning">'.get_lang('ADatabaseWithTheSameNameAlreadyExists').'</div>';
            }
        } catch (Exception $e) {
            $database_exists_text = $e->getMessage();
        }

        if ($manager->getConnection()->isConnected()): ?>

            <?php echo $database_exists_text ?>
            <div id="db_status" class="alert alert-success">
                Database host: <strong><?php echo $manager->getConnection()->getHost(); ?></strong><br />
                Database port: <strong><?php echo $manager->getConnection()->getPort(); ?></strong><br />
                Database driver: <strong><?php echo $manager->getConnection()->getDriver()->getName(); ?></strong><br />

            </div>

        <?php else: ?>

            <?php echo $database_exists_text ?>
            <div id="db_status" style="float:left;" class="alert alert-danger">
                <div style="float:left;">
                    <?php echo get_lang('FailedConectionDatabase'); ?></strong>
                </div>
            </div>

        <?php endif; ?>
   <div class="form-group">
       <div class="col-sm-6">
           <button type="submit" name="step2" class="btn btn-default pull-right" value="&lt; <?php echo get_lang('Previous'); ?>" >
               <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
           </button>
       </div>
      <div class="col-sm-6">
       <input type="hidden" name="is_executable" id="is_executable" value="-" />
       <?php if ($manager) { ?>
           <button type="submit"  class="btn btn-success" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" >
               <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
           </button>
       <?php } else { ?>
           <button disabled="disabled" type="submit" class="btn btn-success disabled" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" >
               <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
           </button>
       <?php } ?>
      </div>
   </div>

    <?php
}
function panel($content = null, $title = null, $id = null, $style = null) {
    $html = '';
    if (empty($style)) {
        $style = 'default';
    }
    if (!empty($title)) {
        $panelTitle = Display::div($title, array('class' => 'panel-heading'));
        $panelBody = Display::div($content, array('class' => 'panel-body'));
        $panelParent = Display::div($panelTitle . $panelBody, array('id' => $id, 'class' => 'panel panel-'.$style));
    } else {
        $panelBody = Display::div($html, array('class' => 'panel-body'));
        $panelParent = Display::div($panelBody, array('id' => $id, 'class' => 'panel panel-'.$style));
    }
    $html .= $panelParent;
    return $html;
}
/**
 * Displays a parameter in a table row.
 * Used by the display_configuration_settings_form function.
 * @param string $installType
 * @param string $parameterName
 * @param string $formFieldName
 * @param string $parameterValue
 * @param string $displayWhenUpdate
 */
function display_configuration_parameter(
    $installType,
    $parameterName,
    $formFieldName,
    $parameterValue,
    $displayWhenUpdate = 'true'
) {
    $html = '<div class="form-group">';
    $html .= '<label class="col-sm-6 control-label">' . $parameterName . '</label>';
    if ($installType == INSTALL_TYPE_UPDATE && $displayWhenUpdate) {
        $html .= '<input type="hidden" name="' . $formFieldName . '" value="'. api_htmlentities($parameterValue, ENT_QUOTES). '" />' . $parameterValue;
    } else {
        $html .= '<div class="col-sm-6"><input class="form-control" type="text" size="'.FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.MAX_FORM_FIELD_LENGTH.'" name="'.$formFieldName.'" value="'.api_htmlentities($parameterValue, ENT_QUOTES).'" />'."</div>";
    }
    $html .= "</div>";
    return $html;
}

/**
 * Displays step 4 of the installation - configuration settings about Chamilo itself.
 * @param string $installType
 * @param string $urlForm
 * @param string $languageForm
 * @param string $emailForm
 * @param string $adminFirstName
 * @param string $adminLastName
 * @param string $adminPhoneForm
 * @param string $campusForm
 * @param string $institutionForm
 * @param string $institutionUrlForm
 * @param string $encryptPassForm
 * @param bool $allowSelfReg
 * @param bool $allowSelfRegProf
 * @param string $loginForm
 * @param string $passForm
 */
function display_configuration_settings_form(
    $installType,
    $urlForm,
    $languageForm,
    $emailForm,
    $adminFirstName,
    $adminLastName,
    $adminPhoneForm,
    $campusForm,
    $institutionForm,
    $institutionUrlForm,
    $encryptPassForm,
    $allowSelfReg,
    $allowSelfRegProf,
    $loginForm,
    $passForm
) {
    if ($installType != 'update' && empty($languageForm)) {
        $languageForm = $_SESSION['install_language'];
    }
    echo '<div class="RequirementHeading">';
    echo "<h2>" . display_step_sequence() . get_lang("CfgSetting") . "</h2>";
    echo '</div>';

    echo '<p>'.get_lang('ConfigSettingsInfo').' <strong>app/config/configuration.php</strong></p>';

    // Parameter 1: administrator's login

    $html = '';

    $html .= display_configuration_parameter($installType, get_lang('AdminLogin'), 'loginForm', $loginForm, $installType == 'update');

    // Parameter 2: administrator's password
    if ($installType != 'update') {
        $html .= display_configuration_parameter($installType, get_lang('AdminPass'), 'passForm', $passForm, false);
    }

    // Parameters 3 and 4: administrator's names

    $html .=  display_configuration_parameter($installType, get_lang('AdminFirstName'), 'adminFirstName', $adminFirstName);
    $html .=  display_configuration_parameter($installType, get_lang('AdminLastName'), 'adminLastName', $adminLastName);

    //Parameter 3: administrator's email
    $html .=  display_configuration_parameter($installType, get_lang('AdminEmail'), 'emailForm', $emailForm);

    //Parameter 6: administrator's telephone
    $html .=  display_configuration_parameter($installType, get_lang('AdminPhone'), 'adminPhoneForm', $adminPhoneForm);


    echo panel($html, get_lang('Administrator'), 'administrator');


    //echo '<table class="table">';

    //First parameter: language
    $html = '<div class="form-group">';
    $html .= '<label class="col-sm-6 control-label">'.get_lang('MainLang')."</label>";
    if ($installType == 'update') {
        $html .= '<input type="hidden" name="languageForm" value="'.api_htmlentities($languageForm, ENT_QUOTES).'" />'.$languageForm;

    } else { // new installation
        $html .= '<div class="col-sm-6">';
        $html .= display_language_selection_box('languageForm', $languageForm);
        $html .= '</div>';
    }
    $html.= "</div>";


    //Second parameter: Chamilo URL
    $html .= '<div class="form-group">';
    $html .= '<label class="col-sm-6 control-label">'.get_lang('ChamiloURL') .get_lang('ThisFieldIsRequired').'</label>';



    if ($installType == 'update') {
        $html .= api_htmlentities($urlForm, ENT_QUOTES)."\n";
    } else {
        $html .= '<div class="col-sm-6">';
        $html .= '<input class="form-control" type="text" size="40" maxlength="100" name="urlForm" value="'.api_htmlentities($urlForm, ENT_QUOTES).'" />';
        $html .= '</div>';
    }
    $html .= '</div>';

    //Parameter 9: campus name
    $html .= display_configuration_parameter($installType, get_lang('CampusName'), 'campusForm', $campusForm);

    //Parameter 10: institute (short) name
    $html .= display_configuration_parameter($installType, get_lang('InstituteShortName'), 'institutionForm', $institutionForm);

    //Parameter 11: institute (short) name
    $html .= display_configuration_parameter($installType, get_lang('InstituteURL'), 'institutionUrlForm', $institutionUrlForm);


    $html .= '<div class="form-group">
            <label class="col-sm-6 control-label">' . get_lang("EncryptMethodUserPass") . '</label>
        <div class="col-sm-6">';
    if ($installType == 'update') {
        $html .= '<input type="hidden" name="encryptPassForm" value="'. $encryptPassForm .'" />'. $encryptPassForm;
    } else {

        $html .= '<div class="checkbox">
                    <label>
                        <input  type="radio" name="encryptPassForm" value="bcrypt" id="encryptPass1" '. ($encryptPassForm == 'bcrypt' ? 'checked="checked" ':'') .'/> bcrypt
                    </label>';

        $html .= '<label>
                        <input  type="radio" name="encryptPassForm" value="sha1" id="encryptPass1" '. ($encryptPassForm == 'sha1' ? 'checked="checked" ':'') .'/> sha1
                    </label>';

        $html .= '<label>
                        <input type="radio" name="encryptPassForm" value="md5" id="encryptPass0" '. ($encryptPassForm == 'md5' ? 'checked="checked" ':'') .'/> md5
                    </label>';

        $html .= '<label>
                        <input type="radio" name="encryptPassForm" value="none" id="encryptPass2" '. ($encryptPassForm == 'none' ? 'checked="checked" ':'') .'/>'. get_lang('None').'
                    </label>';
        $html .= '</div>';
    }
    $html .= '</div></div>';

    $html .= '<div class="form-group">
            <label class="col-sm-6 control-label">' . get_lang('AllowSelfReg') . '</label>
            <div class="col-sm-6">';
    if ($installType == 'update') {
        if ($allowSelfReg == 'true') {
            $label = get_lang('Yes');
        } elseif ($allowSelfReg == 'false') {
            $label = get_lang('No');
        } else {
            $label = get_lang('AfterApproval');
        }
        $html .= '<input type="hidden" name="allowSelfReg" value="'. $allowSelfReg .'" />'. $label;
    } else {
        $html .= '<div class="control-group">';
        $html .= '<label class="checkbox-inline">
                        <input type="radio" name="allowSelfReg" value="1" id="allowSelfReg1" '. ($allowSelfReg == 'true' ? 'checked="checked" ' : '') . ' /> '. get_lang('Yes') .'
                    </label>';
        $html .= '<label class="checkbox-inline">
                        <input type="radio" name="allowSelfReg" value="0" id="allowSelfReg0" '. ($allowSelfReg == 'false' ? '' : 'checked="checked" ') .' /> '. get_lang('No') .'
                    </label>';
         $html .= '<label class="checkbox-inline">
                    <input type="radio" name="allowSelfReg" value="0" id="allowSelfReg0" '. ($allowSelfReg == 'approval' ? '' : 'checked="checked" ') .' /> '. get_lang('AfterApproval') .'
                </label>';
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="form-group">';
    $html .= '<label class="col-sm-6 control-label">'. get_lang('AllowSelfRegProf') .'</label>
        <div class="col-sm-6">';
    if ($installType == 'update') {
        if ($allowSelfRegProf == 'true') {
            $label = get_lang('Yes');
        } else {
            $label = get_lang('No');
        }
        $html .= '<input type="hidden" name="allowSelfRegProf" value="'. $allowSelfRegProf.'" />'. $label;
    } else {
        $html .= '<div class="control-group">
                <label class="checkbox-inline">
                    <input type="radio" name="allowSelfRegProf" value="1" id="allowSelfRegProf1" '. ($allowSelfRegProf ? 'checked="checked" ' : '') .'/>
                ' . get_lang('Yes') .'
                </label>';
        $html .= '<label class="checkbox-inline">
                    <input type="radio" name="allowSelfRegProf" value="0" id="allowSelfRegProf0" '. ($allowSelfRegProf ? '' : 'checked="checked" ') .' />
                   '. get_lang('No') .'
                </label>';
        $html .= '</div>';
    }
    $html .= '</div>
    </div>';

    echo panel($html, get_lang('Platform'), 'platform');
 ?>
    <div class='form-group'>
        <div class="col-sm-6">
            <button type="submit" class="btn btn-default pull-right" name="step3" value="&lt; <?php echo get_lang('Previous'); ?>" ><em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?></button>
            <input type="hidden" name="is_executable" id="is_executable" value="-" />
        </div>
        <div class="col-sm-6">
            <button class="btn btn-success" type="submit" name="step5" value="<?php echo get_lang('Next'); ?> &gt;" ><em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?></button>
        </div>
    </div>

    <?php
}

/**
 * After installation is completed (step 6), this message is displayed.
 * @param string $installType
 */
function display_after_install_message($installType)
{
    echo '<div class="RequirementContent">'.get_lang('FirstUseTip').'</div>';
    echo '<div class="alert alert-warning">';
    echo '<strong>'.get_lang('SecurityAdvice').'</strong>';
    echo ': ';
    printf(get_lang('ToProtectYourSiteMakeXReadOnlyAndDeleteY'), 'app/config/', 'main/install/');
    echo '</div>';
    ?></form>
    <br />
    <a class="btn btn-success btn-large btn-install" href="../../index.php">
        <?php echo get_lang('GoToYourNewlyCreatedPortal'); ?>
    </a>
    <?php
}

/**
 * This function return countries list from array (hardcoded)
 * @param   bool  $combo  (Optional) True for returning countries list with select html
 * @return  array|string countries list
 */
function get_countries_list_from_array($combo = false)
{
    $a_countries = array(
        "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan",
        "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi",
        "Cambodia", "Cameroon", "Canada", "Cape Verde", "Central African Republic", "Chad", "Chile", "China", "Colombi", "Comoros", "Congo (Brazzaville)", "Congo", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic",
        "Denmark", "Djibouti", "Dominica", "Dominican Republic",
        "East Timor (Timor Timur)", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia",
        "Fiji", "Finland", "France",
        "Gabon", "Gambia, The", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana",
        "Haiti", "Honduras", "Hungary",
        "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy",
        "Jamaica", "Japan", "Jordan",
        "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan",
        "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg",
        "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Morocco", "Mozambique", "Myanmar",
        "Namibia", "Nauru", "Nepa", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Norway",
        "Oman",
        "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland","Portugal",
        "Qatar",
        "Romania", "Russia", "Rwanda",
        "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia and Montenegro", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Syria",
        "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu",
        "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Uruguay", "Uzbekistan",
        "Vanuatu", "Vatican City", "Venezuela", "Vietnam",
        "Yemen",
        "Zambia", "Zimbabwe"
    );

    $country_select = '';
    if ($combo) {
        $country_select = '<select class="selectpicker show-tick" id="country" name="country">';
        $country_select .= '<option value="">--- '.get_lang('SelectOne').' ---</option>';
        foreach ($a_countries as $country) {
            $country_select .= '<option value="'.$country.'">'.$country.'</option>';
        }
        $country_select .= '</select>';
        return $country_select;
    }

    return $a_countries;
}

/**
 * Lock settings that can't be changed in other portals
 */
function lockSettings()
{
    $access_url_locked_settings = api_get_locked_settings();
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    foreach ($access_url_locked_settings as $setting) {
        $sql = "UPDATE $table SET access_url_locked = 1 WHERE variable  = '$setting'";
        Database::query($sql);
    }
}

/**
 * Update dir values
 */
function updateDirAndFilesPermissions()
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $permissions_for_new_directories = isset($_SESSION['permissions_for_new_directories']) ? $_SESSION['permissions_for_new_directories'] : 0770;
    $permissions_for_new_files = isset($_SESSION['permissions_for_new_files']) ? $_SESSION['permissions_for_new_files'] : 0660;
    // use decoct() to store as string
    $sql = "UPDATE $table SET selected_value = '0" . decoct($permissions_for_new_directories) . "'
              WHERE variable  = 'permissions_for_new_directories'";
    Database::query($sql);

    $sql = "UPDATE $table SET selected_value = '0" . decoct($permissions_for_new_files) . "' WHERE variable  = 'permissions_for_new_files'";
    Database::query($sql);

    if (isset($_SESSION['permissions_for_new_directories'])) {
        unset($_SESSION['permissions_for_new_directories']);
    }

    if (isset($_SESSION['permissions_for_new_files'])) {
        unset($_SESSION['permissions_for_new_files']);
    }
}

/**
 * @param $current_value
 * @param $wanted_value
 * @return string
 */
function compare_setting_values($current_value, $wanted_value)
{
    $current_value_string = $current_value;
    $current_value = (float)$current_value;
    $wanted_value = (float)$wanted_value;

    if ($current_value >= $wanted_value) {
        return Display::label($current_value_string, 'success');
    } else {
        return Display::label($current_value_string, 'important');
    }
}

/**
 * @param $course_dir
 * @param $course_attempt_name
 * @param string $file
 * @return bool
 */
function check_course_script_interpretation($course_dir, $course_attempt_name, $file = 'test.php')
{
    $output = false;
    //Write in file
    $file_name = $course_dir.'/'.$file;
    $content = '<?php echo "123"; exit;';

    if (is_writable($file_name)) {
        if ($handler = @fopen($file_name, "w")) {
            //write content
            if (fwrite($handler, $content)) {
                $sock_errno = '';
                $sock_errmsg = '';
                $url = api_get_path(WEB_PATH).'app/courses/'.$course_attempt_name.'/'.$file;

                $parsed_url = parse_url($url);
                //$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : ''; //http
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                // Patch if the host is the default host and is used through
                // the IP address (sometimes the host is not taken correctly
                // in this case)
                if (empty($host) && !empty($_SERVER['HTTP_HOST'])) {
                    $host = $_SERVER['HTTP_HOST'];
                    $url = preg_replace('#:///#', '://'.$host.'/', $url);
                }
                $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
                $port = isset($parsed_url['port']) ? $parsed_url['port'] : '80';

                //Check fsockopen (doesn't work with https)
                if ($fp = @fsockopen(str_replace('http://', '', $url), -1, $sock_errno, $sock_errmsg, 60)) {
                    $out  = "GET $path HTTP/1.1\r\n";
                    $out .= "Host: $host\r\n";
                    $out .= "Connection: Close\r\n\r\n";

                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        $result = str_replace("\r\n", '',fgets($fp, 128));
                        if (!empty($result) && $result == '123') {
                            $output = true;
                        }
                    }
                    fclose($fp);
                    //Check allow_url_fopen
                } elseif (ini_get('allow_url_fopen')) {
                    if ($fp = @fopen($url, 'r')) {
                        while ($result = fgets($fp, 1024)) {
                            if (!empty($result) && $result == '123') {
                                $output = true;
                            }
                        }
                        fclose($fp);
                    }
                    // Check if has support for cURL
                } elseif (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    //curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec ($ch);
                    if (!empty($result) && $result == '123') {
                        $output = true;
                    }
                    curl_close($ch);
                }
            }
            @fclose($handler);
        }
    }

    return $output;
}

/**
 * Save settings values
 *
 * @param string $organizationName
 * @param string $organizationUrl
 * @param string $siteName
 * @param string $adminEmail
 * @param string $adminLastName
 * @param string $adminFirstName
 * @param string $language
 * @param string $allowRegistration
 * @param string $allowTeacherSelfRegistration
 * @param string $installationProfile The name of an installation profile file in main/install/profiles/
 */
function installSettings(
    $organizationName,
    $organizationUrl,
    $siteName,
    $adminEmail,
    $adminLastName,
    $adminFirstName,
    $language,
    $allowRegistration,
    $allowTeacherSelfRegistration,
    $installationProfile = ''
) {
    $allowRegistration = $allowRegistration ? 'true' : 'false';
    $allowTeacherSelfRegistration = $allowTeacherSelfRegistration ? 'true' : 'false';

    // Use PHP 5.3 to avoid issue with weird peripherical auto-installers like travis-ci
    $settings = array(
        'Institution' => $organizationName,
        'InstitutionUrl' => $organizationUrl,
        'siteName' => $siteName,
        'emailAdministrator' => $adminEmail,
        'administratorSurname' => $adminLastName,
        'administratorName' => $adminFirstName,
        'platformLanguage' => $language,
        'allow_registration' => $allowRegistration,
        'allow_registration_as_teacher' => $allowTeacherSelfRegistration,
    );

    foreach ($settings as $variable => $value) {
        $sql = "UPDATE settings_current
                SET selected_value = '$value'
                WHERE variable = '$variable'";
        Database::query($sql);
    }
    installProfileSettings($installationProfile);
}

/**
 * Executes DB changes based in the classes defined in
 * src/Chamilo/CoreBundle/Migrations/Schema/*
 *
 * @param string $chamiloVersion
 * @param EntityManager $manager
 * @throws \Doctrine\DBAL\DBALException
 */
function migrate($chamiloVersion, EntityManager $manager)
{
    $debug = true;
    $connection = $manager->getConnection();

    $config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($connection);

    // Table name that will store migrations log (will be created automatically,
    // default name is: doctrine_migration_versions)
    $config->setMigrationsTableName('version');
    // Namespace of your migration classes, do not forget escape slashes, do not add last slash
    $config->setMigrationsNamespace('Application\Migrations\Schema\V'.$chamiloVersion);
    // Directory where your migrations are located
    $config->setMigrationsDirectory(api_get_path(SYS_PATH).'app/Migrations/Schema/V'.$chamiloVersion);
    // Load your migrations
    $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());

    $migration = new \Doctrine\DBAL\Migrations\Migration($config);
    $versions = $config->getMigrations();

    /** @var Doctrine\DBAL\Migrations\Version $migrationItem */
    foreach ($versions as $version) {
        $version->getMigration()->setEntityManager($manager);
    }

    $to = null;
    // Retrieve SQL queries that should be run to migrate you schema to $to version,
    // if $to == null - schema will be migrated to latest version
    $versions = $migration->getSql($to);

    if ($debug) {
        $nl = '<br>';
        foreach ($versions as $version => $queries) {
            echo 'VERSION: '.$version.$nl;
            echo '----------------------------------------------'.$nl.$nl;
            foreach ($queries as $query) {
                echo $query.$nl;
            }
            echo $nl.$nl;
        }
    }

    try {
        // Execute migration!
        $migration->migrate($to);
        if ($debug) {
            echo 'DONE'.$nl;
        }
        return true;
    } catch (Exception $ex) {
        if ($debug) {
            echo 'ERROR: '.$ex->getMessage().$nl;
            return false;
        }
    }

    return false;
}

/**
* @param EntityManager $em
 *
* @throws \Doctrine\DBAL\DBALException
 */
function fixIds(EntityManager $em)
{
    $debug = true;
    $connection = $em->getConnection();

    if ($debug) {
        error_log('fixIds');
        error_log('Update tools');
    }

    // Create temporary indexes to increase speed of the following operations
    // Adding and removing indexes will usually take much less time than
    // the execution without indexes of the queries in this function, particularly
    // for large tables
    $sql = "ALTER TABLE c_document ADD INDEX tmpidx_doc(c_id, id)";
    $connection->executeQuery($sql);
    $sql = "ALTER TABLE c_student_publication ADD INDEX tmpidx_stud (c_id, id)";
    $connection->executeQuery($sql);
    $sql = "ALTER TABLE c_quiz ADD INDEX tmpidx_quiz (c_id, id)";
    $connection->executeQuery($sql);
    $sql = "ALTER TABLE c_item_property ADD INDEX tmpidx_ip (to_group_id)";
    $connection->executeQuery($sql);

    $sql = "SELECT * FROM c_lp_item";
    $result = $connection->fetchAll($sql);
    foreach ($result as $item) {
        $courseId = $item['c_id'];
        $iid = isset($item['iid']) ? intval($item['iid']) : 0;
        $ref = isset($item['ref']) ? intval($item['ref']) : 0;
        $sql = null;

        $newId = '';

        switch ($item['item_type']) {
            case TOOL_LINK:
                $sql = "SELECT * FROM c_link WHERE c_id = $courseId AND id = $ref";
                $data = $connection->fetchAssoc($sql);
                if ($data) {
                    $newId = $data['iid'];
                }
                break;
            case TOOL_STUDENTPUBLICATION:
                $sql = "SELECT * FROM c_student_publication WHERE c_id = $courseId AND id = $ref";
                $data = $connection->fetchAssoc($sql);
                if ($data) {
                    $newId = $data['iid'];
                }
                break;
            case TOOL_QUIZ:
                $sql = "SELECT * FROM c_quiz WHERE c_id = $courseId AND id = $ref";
                $data = $connection->fetchAssoc($sql);
                if ($data) {
                    $newId = $data['iid'];
                }
                break;
            case TOOL_DOCUMENT:
                $sql = "SELECT * FROM c_document WHERE c_id = $courseId AND id = $ref";
                $data = $connection->fetchAssoc($sql);
                if ($data) {
                    $newId = $data['iid'];
                }
                break;
            case TOOL_FORUM:
                $sql = "SELECT * FROM c_forum_forum WHERE c_id = $courseId AND forum_id = $ref";
                $data = $connection->fetchAssoc($sql);
                if ($data) {
                    $newId = $data['iid'];
                }
                break;
            case 'thread':
                $sql = "SELECT * FROM c_forum_thread WHERE c_id = $courseId AND thread_id = $ref";
                $data = $connection->fetchAssoc($sql);
                if ($data) {
                    $newId = $data['iid'];
                }
                break;
        }

        if (!empty($sql) && !empty($newId) && !empty($iid)) {
            $sql = "UPDATE c_lp_item SET ref = $newId WHERE iid = $iid";

            $connection->executeQuery($sql);
        }
    }

    // Set NULL if session = 0
    $sql = "UPDATE c_item_property SET session_id = NULL WHERE session_id = 0";
    $connection->executeQuery($sql);

    // Set NULL if group = 0
    $sql = "UPDATE c_item_property SET to_group_id = NULL WHERE to_group_id = 0";
    $connection->executeQuery($sql);

    // Set NULL if insert_user_id = 0
    $sql = "UPDATE c_item_property SET insert_user_id = NULL WHERE insert_user_id = 0";
    $connection->executeQuery($sql);

    // Delete session data of sessions that don't exist.
    $sql = "DELETE FROM c_item_property
            WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)";
    $connection->executeQuery($sql);

    // Delete group data of groups that don't exist.
    $sql = "DELETE FROM c_item_property
            WHERE to_group_id IS NOT NULL AND to_group_id NOT IN (SELECT DISTINCT id FROM c_group_info)";
    $connection->executeQuery($sql);

    // This updates the group_id with c_group_info.iid instead of c_group_info.id

    if ($debug) {
        error_log('update iids');
    }

    $groupTableToFix = [
        'c_group_rel_user',
        'c_group_rel_tutor',
        'c_permission_group',
        'c_role_group',
        'c_survey_invitation',
        'c_attendance_calendar_rel_group'
    ];

    foreach ($groupTableToFix as $table) {
        $sql = "SELECT * FROM $table";
        $result = $connection->fetchAll($sql);
        foreach ($result as $item) {
            $iid = $item['iid'];
            $courseId = $item['c_id'];
            $groupId = intval($item['group_id']);

            // Fix group id
            if (!empty($groupId)) {
                $sql = "SELECT * FROM c_group_info
                        WHERE c_id = $courseId AND id = $groupId
                        LIMIT 1";
                $data = $connection->fetchAssoc($sql);
                if (!empty($data)) {
                    $newGroupId = $data['iid'];
                    $sql = "UPDATE $table SET group_id = $newGroupId
                            WHERE iid = $iid";
                    $connection->executeQuery($sql);
                } else {
                    // The group does not exists clean this record
                    $sql = "DELETE FROM $table WHERE iid = $iid";
                    $connection->executeQuery($sql);
                }
            }
        }
    }

    // Fix c_item_property
    if ($debug) {
        error_log('update c_item_property');
    }

    $sql = "SELECT * FROM course";
    $courseList = $connection->fetchAll($sql);
    if ($debug) {
        error_log('Getting course list');
    }

    foreach ($courseList  as $courseData) {
        $courseId = $courseData['id'];
        if ($debug) {
            error_log('Updating course: '.$courseData['code']);
        }

        $sql = "SELECT * FROM c_item_property WHERE c_id = $courseId";
        $result = $connection->fetchAll($sql);
        $counter = 0;
        error_log("Items to process: ".count($result));

        foreach ($result as $item) {
            //$courseId = $item['c_id'];
            $sessionId = intval($item['session_id']);
            $groupId = intval($item['to_group_id']);
            $iid = $item['iid'];
            $ref = $item['ref'];

            // Fix group id
            if (!empty($groupId)) {
                $sql = "SELECT * FROM c_group_info
                        WHERE c_id = $courseId AND id = $groupId";
                $data = $connection->fetchAssoc($sql);
                if (!empty($data)) {
                    $newGroupId = $data['iid'];
                    $sql = "UPDATE c_item_property SET to_group_id = $newGroupId
                            WHERE iid = $iid";
                    $connection->executeQuery($sql);
                } else {
                    // The group does not exists clean this record
                    $sql = "DELETE FROM c_item_property WHERE iid = $iid";
                    $connection->executeQuery($sql);
                }
            }

            $sql = '';
            $newId = '';
            switch ($item['tool']) {
                case TOOL_LINK:
                    $sql = "SELECT * FROM c_link WHERE c_id = $courseId AND id = $ref ";
                    break;
                case TOOL_STUDENTPUBLICATION:
                    $sql = "SELECT * FROM c_student_publication WHERE c_id = $courseId AND id = $ref";
                    break;
                case TOOL_QUIZ:
                    $sql = "SELECT * FROM c_quiz WHERE c_id = $courseId AND id = $ref";
                    break;
                case TOOL_DOCUMENT:
                    $sql = "SELECT * FROM c_document WHERE c_id = $courseId AND id = $ref";
                    break;
                case TOOL_FORUM:
                    $sql = "SELECT * FROM c_forum_forum WHERE c_id = $courseId AND id = $ref";
                    break;
                case 'thread':
                    $sql = "SELECT * FROM c_forum_thread WHERE c_id = $courseId AND id = $ref";
                    break;
            }

            if (!empty($sql) && !empty($newId)) {
                $data = $connection->fetchAssoc($sql);
                if (isset($data['iid'])) {
                    $newId = $data['iid'];
                }
                $sql = "UPDATE c_item_property SET ref = $newId WHERE iid = $iid";
                error_log($sql);
                $connection->executeQuery($sql);
            }
            error_log("Process item #$counter");
            $counter++;
        }
    }

    if ($debug) {
        error_log('update gradebook_link');
    }

    // Fix gradebook_link
    $sql = "SELECT * FROM gradebook_link";
    $result = $connection->fetchAll($sql);
    foreach ($result as $item) {
        $courseCode = $item['course_code'];
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            continue;
        }
        $courseId = $courseInfo['real_id'];
        $ref = $item['ref_id'];
        $iid = $item['id'];
        $sql = '';

        switch ($item['type']) {
            case LINK_LEARNPATH:
                $sql = "SELECT * FROM c_link WHERE c_id = $courseId AND id = $ref ";
                break;
            case LINK_STUDENTPUBLICATION:
                $sql = "SELECT * FROM c_student_publication WHERE c_id = $courseId AND id = $ref";
                break;
            case LINK_EXERCISE:
                $sql = "SELECT * FROM c_quiz WHERE c_id = $courseId AND id = $ref";
                break;
            case LINK_ATTENDANCE:
                //$sql = "SELECT * FROM c_document WHERE c_id = $courseId AND id = $ref";
                break;
            case LINK_FORUM_THREAD:
                $sql = "SELECT * FROM c_forum_thread WHERE c_id = $courseId AND thread_id = $ref";
                break;
        }

        if (!empty($sql)) {
            $data = $connection->fetchAssoc($sql);
            if (isset($data) && isset($data['iid'])) {
                $newId = $data['iid'];
                $sql = "UPDATE gradebook_link SET ref_id = $newId
                        WHERE id = $iid";
                $connection->executeQuery($sql);
            }
        }
    }

    if ($debug) {
        error_log('update groups');
    }

    $sql = "SELECT * FROM groups";
    $result = $connection->executeQuery($sql);
    $groups = $result->fetchAll();

    $oldGroups = array();

    if (!empty($groups )) {
        foreach ($groups as $group) {
            $sql = "INSERT INTO usergroup (name, group_type, description, picture, url, visibility, updated_at, created_at)
                    VALUES ('{$group['name']}', '1', '{$group['description']}', '{$group['picture_uri']}', '{$group['url']}', '{$group['visibility']}', '{$group['updated_on']}', '{$group['created_on']}')";

            $connection->executeQuery($sql);
            $id = $connection->lastInsertId('id');
            $oldGroups[$group['id']] = $id;
        }
    }

    if (!empty($oldGroups)) {
        foreach ($oldGroups as $oldId => $newId) {
            $path = \GroupPortalManager::get_group_picture_path_by_id(
                $oldId,
                'system'
            );
            if (!empty($path)) {

                $newPath = str_replace(
                    "groups/$oldId/",
                    "groups/$newId/",
                    $path['dir']
                );
                $command = "mv {$path['dir']} $newPath ";
                system($command);
            }
        }

        $sql = "SELECT * FROM group_rel_user";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO usergroup_rel_user (usergroup_id, user_id, relation_type)
                            VALUES ('{$data['group_id']}', '{$data['user_id']}', '{$data['relation_type']}')";
                    $connection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM group_rel_group";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']]) && isset($oldGroups[$data['subgroup_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $data['subgroup_id'] = $oldGroups[$data['subgroup_id']];
                    $sql = "INSERT INTO usergroup_rel_usergroup (group_id, subgroup_id, relation_type)
                            VALUES ('{$data['group_id']}', '{$data['subgroup_id']}', '{$data['relation_type']}')";
                    $connection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM announcement_rel_group";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']])) {
                    // Deleting relation
                    $sql = "DELETE FROM announcement_rel_group WHERE id = {$data['id']}";
                    $connection->executeQuery($sql);

                    // Add new relation
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO announcement_rel_group(group_id, announcement_id)
                            VALUES ('{$data['group_id']}', '{$data['announcement_id']}')";
                    $connection->executeQuery($sql);
                }
            }
        }

        $sql = "SELECT * FROM group_rel_tag";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();
        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                if (isset($oldGroups[$data['group_id']])) {
                    $data['group_id'] = $oldGroups[$data['group_id']];
                    $sql = "INSERT INTO usergroup_rel_tag (tag_id, usergroup_id)
                            VALUES ('{$data['tag_id']}', '{$data['group_id']}')";
                    $connection->executeQuery($sql);
                }
            }
        }
    }

    if ($debug) {
        error_log('update extra fields');
    }

    // Extra fields
    $extraFieldTables = [
        ExtraField::USER_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_USER_FIELD),
        ExtraField::COURSE_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_COURSE_FIELD),
        //ExtraField::LP_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_LP_FIELD),
        ExtraField::SESSION_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_SESSION_FIELD),
        //ExtraField::CALENDAR_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_CALENDAR_EVENT_FIELD),
        //ExtraField::QUESTION_FIELD_TYPE => Database::get_main_table(TABLE_MAIN_CALENDAR_EVENT_FIELD),
        //ExtraField::USER_FIELD_TYPE => //Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD),
    ];

    foreach ($extraFieldTables as $type => $table) {
        //continue;
        $sql = "SELECT * FROM $table ";
        $result = $connection->query($sql);
        $fields = $result->fetchAll();

        foreach ($fields as $field) {
            $originalId = $field['id'];
            $extraField = new ExtraField();
            $extraField
                ->setExtraFieldType($type)
                ->setVariable($field['field_variable'])
                ->setFieldType($field['field_type'])
                ->setDisplayText($field['field_display_text'])
                ->setDefaultValue($field['field_default_value'])
                ->setFieldOrder($field['field_order'])
                ->setVisible($field['field_visible'])
                ->setChangeable($field['field_changeable'])
                ->setFilter($field['field_filter']);

            $em->persist($extraField);
            $em->flush();

            $values = array();
            switch ($type) {
                case ExtraField::USER_FIELD_TYPE:
                    $optionTable = Database::get_main_table(
                        TABLE_MAIN_USER_FIELD_OPTIONS
                    );
                    $valueTable = Database::get_main_table(
                        TABLE_MAIN_USER_FIELD_VALUES
                    );
                    $handlerId = 'user_id';
                    break;
                case ExtraField::COURSE_FIELD_TYPE:
                    $optionTable = Database::get_main_table(
                        TABLE_MAIN_COURSE_FIELD_OPTIONS
                    );
                    $valueTable = Database::get_main_table(
                        TABLE_MAIN_COURSE_FIELD_VALUES
                    );
                    $handlerId = 'c_id';
                    break;
                case ExtraField::SESSION_FIELD_TYPE:
                    $optionTable = Database::get_main_table(
                        TABLE_MAIN_SESSION_FIELD_OPTIONS
                    );
                    $valueTable = Database::get_main_table(
                        TABLE_MAIN_SESSION_FIELD_VALUES
                    );
                    $handlerId = 'session_id';
                    break;
            }

            if (!empty($optionTable)) {

                $sql = "SELECT * FROM $optionTable WHERE field_id = $originalId ";
                $result = $connection->query($sql);
                $options = $result->fetchAll();

                foreach ($options as $option) {
                    $extraFieldOption = new ExtraFieldOptions();
                    $extraFieldOption
                        ->setDisplayText($option['option_display_text'])
                        ->setField($extraField)
                        ->setOptionOrder($option['option_order'])
                        ->setValue($option['option_value']);
                    $em->persist($extraFieldOption);
                    $em->flush();
                }

                $sql = "SELECT * FROM $valueTable WHERE field_id = $originalId ";
                $result = $connection->query($sql);
                $values = $result->fetchAll();
            }

            if (!empty($values)) {
                foreach ($values as $value) {
                    $extraFieldValue = new ExtraFieldValues();
                    $extraFieldValue
                        ->setValue($value['field_value'])
                        ->setField($extraField)
                        ->setItemId($value[$handlerId]);
                    $em->persist($extraFieldValue);
                    $em->flush();
                }
            }
        }
    }
    // Drop temporary indexes added to increase speed of this function's queries
    $sql = "ALTER TABLE c_document DROP INDEX tmpidx_doc";
    $connection->executeQuery($sql);
    $sql = "ALTER TABLE c_student_publication DROP INDEX tmpidx_stud";
    $connection->executeQuery($sql);
    $sql = "ALTER TABLE c_quiz DROP INDEX tmpidx_quiz";
    $connection->executeQuery($sql);
    $sql = "ALTER TABLE c_item_property DROP INDEX tmpidx_ip";
    $connection->executeQuery($sql);
}

/**
 *
 * After the schema was created (table creation), the function adds
 * admin/platform information.
 *
 * @param EntityManager $manager
 * @param string $sysPath
 * @param string $encryptPassForm
 * @param string $passForm
 * @param string $adminLastName
 * @param string $adminFirstName
 * @param string $loginForm
 * @param string $emailForm
 * @param string $adminPhoneForm
 * @param string $languageForm
 * @param string $institutionForm
 * @param string $institutionUrlForm
 * @param string $siteName
 * @param string $allowSelfReg
 * @param string $allowSelfRegProf
 * @param string $installationProfile Installation profile, if any was provided
 */
function finishInstallation(
    $manager,
    $sysPath,
    $encryptPassForm,
    $passForm,
    $adminLastName,
    $adminFirstName,
    $loginForm,
    $emailForm,
    $adminPhoneForm,
    $languageForm,
    $institutionForm,
    $institutionUrlForm,
    $siteName,
    $allowSelfReg,
    $allowSelfRegProf,
    $installationProfile = ''
) {
    $sysPath = !empty($sysPath) ? $sysPath : api_get_path(SYS_PATH);

    // Inserting data
    $data = file_get_contents($sysPath.'main/install/data.sql');
    $result = $manager->getConnection()->prepare($data);
    $result->execute();
    $result->closeCursor();

    UserManager::setPasswordEncryption($encryptPassForm);

    // Create admin user.
    UserManager::create_user(
        $adminFirstName,
        $adminLastName,
        1,
        $emailForm,
        $loginForm,
        $passForm,
        'ADMIN', //$official_code = '',
        $languageForm,
        $adminPhoneForm,
        '', //$picture_uri = '',
        PLATFORM_AUTH_SOURCE,
        '',//$expirationDate,
        1,
        0,
        null,
        '',
        false,  //$send_mail = false,
        true //$isAdmin = false
    );

    // Create anonymous user.
    UserManager::create_user(
        'Joe',
        'Anonymous',
        6,
        'anonymous@localhost',
        'anon',
        'anon',
        'anonymous', //$official_code = '',
        $languageForm,
        '',
        '', //$picture_uri = '',
        PLATFORM_AUTH_SOURCE,
        '',
        1,
        0,
        null,
        '',
        false,  //$send_mail = false,
        false //$isAdmin = false
    );

    // Set default language
    $sql = "UPDATE language SET available=1 WHERE dokeos_folder = '$languageForm'";
    Database::query($sql);

    // Install settings
    installSettings(
        $institutionForm,
        $institutionUrlForm,
        $siteName,
        $emailForm,
        $adminLastName,
        $adminFirstName,
        $languageForm,
        $allowSelfReg,
        $allowSelfRegProf,
        $installationProfile
    );

    lockSettings();
    updateDirAndFilesPermissions();
}

/**
 * Update settings based on installation profile defined in a JSON file
 * @param string $installationProfile The name of the JSON file in main/install/profiles/ folder
 *
 * @return bool false on failure (no bad consequences anyway, just ignoring profile)
 */
function installProfileSettings($installationProfile = '')
{
    if (empty($installationProfile)) {
        return false;
    }
    $jsonPath = api_get_path(SYS_PATH).'main/install/profiles/'.$installationProfile.'.json';
    // Make sure the path to the profile is not hacked
    if (!Security::check_abs_path($jsonPath, api_get_path(SYS_PATH).'main/install/profiles/')) {
        return false;
    }
    if (!is_file($jsonPath)) {
        return false;
    }
    if (!is_readable($jsonPath)) {
        return false;
    }
    if (!function_exists('json_decode')) {
        // The php-json extension is not available. Ignore profile.
        return false;
    }
    $json = file_get_contents($jsonPath);
    $params = json_decode($json);
    if ($params === false or $params === null) {
        return false;
    }
    $settings = $params->params;
    if (!empty($params->parent)) {
        installProfileSettings($params->parent);
    }
    foreach ($settings as $id => $param) {
        $sql = "UPDATE settings_current
                SET selected_value = '".$param->selected_value."'
                WHERE variable = '".$param->variable."'";
        if (!empty($param->subkey)) {
            $sql .= " AND subkey='" . $param->subkey . "'";
        }
        Database::query($sql);
    }

    return true;
}
