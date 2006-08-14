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

/**
 * include
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
    protected $name = false;

    /**
     * Short Summary
     *
     * @var    string
     * @access private
     */
    protected $summary = "";

    /**
     * Long Description
     *
     * @var    string
     * @access private
     */
    protected $description = "";

    /**
     * A file to test for to check a given argument path
     *
     * @var    string
     * @access private
     */
    protected $testfile = false;

    /**
     * Default search path
     *
     * @var    string
     * @access private
     */
    protected $defaults = "/usr:/usr/local";

    /**
     * dependant libraries
     *
     * @var    string
     * @access private
     */
    protected $libs = array();


    /**
     * dependant header files
     *
     * @var    string
     * @access private
     */
    protected $headers = array();

    /**
     * name getter
     * 
     * @param string
     */
    function getName()
    {
        return $this->name;
    }


    /**
     * name setter
     *
     * @param  string
     */
    function setName($name)
    {
        if (!preg_match('|^[a-z][a-z0-9_-]*$|i', $name)) {
            return PEAR::raiseError("'$name' is not a valid --with option name");
        }

        $this->name = str_replace("_", "-", $name);
        return true;
    }

    /**
     * summary setter
     *
     * @param string
     */
    function setSummary($text)
    {
        $this->summary = $text;

        return true;
    }

    /**
     * summary getter
     *
     * @return string
     */
    function getSummary()
    {
        return $this->summary ? $this->summary : "whether {$this->name} is available";
    }

    /**
     * description setter
     *
     * @param string
     */
    function setDescription($text)
    {
        $this->description = $text;

        return true;
    }

    /**
     * testfile setter
     *
     * @param string
     */
    function setTestfile($path) 
    {
        $this->testfile = $path;
    }

    /**
     * testfile getter
     * 
     * @return string
     */
    function getTestfile()
    {
        return $this->testfile;
    }

    /**
     * default searchpath setter
     *
     * @param string
     */
    function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * default searchpath getter
     * 
     * @return string
     */
    function getDefaults()
    {
        return $this->defaults;
    }

	
    /**
     * add library dependency
     * 
     * @param  object
     */
    function addLib(CodeGen_PECL_Dependency_Lib $lib)
    {
        $name = $lib->getName();
        
        if (isset($this->libs[$name])) {
            return PEAR::raiseError("library '$name' specified twice");
        }

        $this->libs[$name] = $lib;

        return true;
    }

    /**
     * libraries getter
     *
     * @return array
     */
    function getLibs()
    {
        return $this->libs;
    }

    /** 
     * add header dependency
     *
     * @param object
     */
    function addHeader(CodeGen_PECL_Dependency_Header $header)
    {
        $name = $header->getName();
        
        if (isset($this->headers[$name])) {
            return PEAR::raiseError("header '$name' specified twice");
        }

        $this->headers[$name] = $header;

        return true;
    }

    /**
     * headers getter
     *
     * @return array
     */
    function getHeaders()
    {
        return $this->headers;
    }

}

?>
