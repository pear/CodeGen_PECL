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
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

require_once "CodeGen/PECL/Element.php";

/**
 * Class representing a header file dependencyp
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_Header
    extends CodeGen_Element
{
    /** 
     * Header file name
     *
     * @var string
     */
    private $name;

    /**
     * Include this file ahead of PHP headers?
     *
     * @var bool
     */
    private $prepend = false;

    /**
     * search path relative to install prefix
     *
     * @var string
     */
    private $path = "include";

    /**
     * Constructor
     *
     * @param  string  header file name 
     */
    function __construct($name)
    {
        // TODO check name
        $this->name = $name;
    }

    /**
     * name getter
     *
     * @return string
     */
    function getName() 
    {
        return $this->name;
    }

    /**
     * prepend flag setter
     *
     * @param  bool
     */
    function setPrepend($prepend)
    {
        $this->prepend = ($prepend === "yes");
    }

    /**
     * search path setter
     *
     * @param string
     */
    function setPath($path)
    {
        $this->path = $path;
    }

    /** 
     * search path getter
     *
     * @return string
     */
    function getPath()
    {
        return $this->path;
    }

    /**
     * return header file code snippet
     *
     * @param  bool  
     * @return string
     */
    function hCode($prepend=false)
    {
        if ($this->prepend != $prepend) {
            return "";
        }

        return "#include <{$this->name}>\n";
    }

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
        return "  AC_CHECK_HEADER([\$PHP_{$upname}_DIR/{$this->path}/{$this->name}], [], AC_MSG_ERROR('{$this->name}' header not found))\n";
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