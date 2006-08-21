<?php
/**
 * Class representing a header file dependency
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
 * @link       http://pear.php.net/package/CodeGen_PECL
 */

/**
 * include
 */
require_once "CodeGen/Dependency/Header.php";

/**
 * Class representing a header file dependency
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_Header
  extends CodeGen_Dependency_Header
{
    /**
     * return config.m4 code snippet for unix builds
     *
     * @param   string  Extension name
     * @param   string  --with option name
     * @return  string
     */
    function configm4($extname, $withname)
    {
        $upname = strtoupper($extname);
        $withUpname = strtoupper($withname);
        return "  AC_CHECK_HEADER([{$this->name}], [], AC_MSG_ERROR('{$this->name}' header not found))\n";
    }

    /**
     * return config.w32 code snippet for windows builds
     *
     * @param   string  Extension name
     * @param   string  --with option name
     * @return  string
     */
    function configw32($extname, $withname)
    {
        $upname = strtoupper($extname);
        echo "  
  if (!CHECK_HEADER_ADD_INCLUDE(\"{$this->name}\", \"CFLAGS_$upname\")) {
    ERROR(\"{$extname}: header '{$this->name}' not found\");
  }
";
    }
}

?>