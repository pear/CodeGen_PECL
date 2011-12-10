<?php
/**
 * Class representing a library dependency
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * include
 */
require_once "CodeGen/Dependency/Lib.php";
require_once "CodeGen/PECL/Dependency/Platform.php";

/**
 * Class representing a library dependencyp
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_Lib
    extends CodeGen_Dependency_Lib
{
    /**
     * Constructor
     *
     * @param  string  library basename
     * @param  string  platform name
     */
    function __construct($name, $platform = "all")
    {
        // TODO check name
        $this->name = $name;

        $this->platform = new CodeGen_PECL_Dependency_Platform($platform);
    }

    /**
     * write config.m4 code snippet for unix builds
     *
     * @param  string Extension name
     * @param  string --with option name
     * @return string code snippet
     */
    function configm4($extName, $withName)
    {
        static $first = true;

        $extUpname  = strtoupper($extName);
        $withUpname = str_replace("-", "_", strtoupper($withName));

        if (!$this->platform->test("unix")) {
            return "";
        }

        $ret = "";

        if ($first) {
            $ret.= "  PHP_SUBST({$extUpname}_SHARED_LIBADD)\n\n";
            $first = false;
        }
        
        if ($this->function) {
            $ret.= "
  PHP_CHECK_LIBRARY({$this->name}, {$this->function},
  [
    PHP_ADD_LIBRARY_WITH_PATH({$this->name}, \$PHP_{$withUpname}_DIR/{$this->path}, {$extUpname}_SHARED_LIBADD)
  ],[
    AC_MSG_ERROR([wrong {$this->name} lib version or lib not found])
  ],[
    -L\$PHP_{$withUpname}_DIR/{$this->path}
  ])
";
        } else {
            $ret.= "  PHP_ADD_LIBRARY_WITH_PATH({$this->name}, \$PHP_{$withUpname}_DIR/{$this->path}, {$extUpname}_SHARED_LIBADD)\n";
            
        }
        
        return $ret;
    }

    /**
     * write config.w32 code snippet for windows builds
     *
     * @param  string Extension name
     * @param  string --with option name
     * @return string code snippet
     */
    function configw32($extName, $withName)
    {
        if (!$this->platform->test("windows")) {
            return "";
        }

        $extUpname = strtoupper($extName);
      
        return "
  if (!CHECK_LIB(\"{$this->name}.lib\", \"{$extName}\", PHP_$extUpname)) { 
    ERROR(\"{$extName}: library '{$this->name}' not found\");
  }
";        
    }
}

?>
