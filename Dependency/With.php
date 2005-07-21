<?php
/**
 * Class representing a --with configure option
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
 * Class representing a --with configure option
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_With
    extends CodeGen_Element
{
    /**
     * Set option name
     *
     * @var    name
     * @access private
     */
    var $name = false;

    /**
     * Short Summary
     *
     * @var    string
     * @access private
     */
    var $summary = "";

    /**
     * Long Description
     *
     * @var    string
     * @access private
     */
    var $description = "";

    /**
     * A file to test for to check a given argument path
     *
     * @var    string
     * @access private
     */
    var $testfile = false;

    /**
     * Default search path
     *
     * @var    string
     * @access private
     */
    var $defaults = "/usr:/usr/local";

    /**
     * dependant libraries
     *
     * @var    string
     * @access private
     */
    var $libs = array();


    /**
     * dependant header files
     *
     * @var    string
     * @access private
     */
    var $headers = array();




    function setName($name)
    {
        if (!ereg("[:alpha:][[:alnum:]-]*", $name)) {
            return PEAR::raiseError("'$name' is not a valid --with option name");
        }

        $this->name = $name;
        return true;
    }

    function setSummary($text)
    {
        $this->summary = $text;

        return true;
    }

    function setDescription($text)
    {
        $this->description = $text;

        return true;
    }

    function setTestfile($path) 
    {
        $this->testfile = $path;
    }

    function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    function addLib($lib)
    {
        $name = $lib->getName();
        
        if (isset($this->libs[$name])) {
            return PEAR::raiseError("library '$name' specified twice");
        }

        $this->libs[$name] = $lib;

        return true;
    }

    function addHeader($header)
    {
        $name = $header->getName();
        
        if (isset($this->headers[$name])) {
            return PEAR::raiseError("header '$name' specified twice");
        }

        $this->headers[$name] = $header;

        return true;
    }
}

?>
