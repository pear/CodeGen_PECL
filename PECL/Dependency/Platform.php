<?php
/**
 * Class representing a platform dependency
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
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen_PECL
 */

/**
 * include
 */
require_once "CodeGen/Tools/Platform.php";

/**
 * Class representing a platform dependency
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_PECL
 */
class CodeGen_PECL_Dependency_Platform
    extends CodeGen_Tools_Platform
{
    /**
     * package.xml dependencie entry
     *
     * @return string XML snippet
     */
    function packageXML()
    {
        if ($this->test("all")) return "";
        
        $xml = "";

        if ($this->test("windows")) {
            $xml.= "    <dep type=\"os\">windows</dep>\n";
        }

        if ($this->test("unix")) {
            $xml.= "    <dep type=\"os\">unix</dep>\n";
        }

        return $xml;
    }

    /**
     * package.xml 2.0 dependencie entry
     *
     * @return string XML snippet
     */
    function packageXML2()
    {
        if ($this->test("all")) return "";
        
        $xml = "";

        if ($this->test("windows")) {
            $xml.= "    <os><name>windows</name></os>\n";
        }

        if ($this->test("unix")) {
            $xml.= "    <os><name>unix</name></os>\n";
        }

        return $xml;
    }
}
