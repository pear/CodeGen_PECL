<?php
/**
 * Class for testfile generation as needed for 'make test'
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
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class for testfile generation as needed for 'make test'
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Test
    extends CodeGen_PECL_Element
{
    /** 
     * Constructor
     * 
     * @access public
     * @param  string  testfile basename
     */
    function __construct() 
    {
    }

    /**
     * testfile basename
     *
     * @access private
     * @type   string
     */
    var $name = "";
    
    /**
     * Setter for testcase name
     *
     * @access public
     * @return string  value of
     */ function setName($name) 
    {
        if (! $this->isName($name)) {
            return PEAR::raise("'$name' is not a valid test case basename");
        }

        $this->name = $name;
        if (empty($this->title)) {
            $this->title = $name;   
        }
    }

    /**
     * Getter for testcase name
     *
     * @access public
     * @return string  value of
     */ 
    function getName() 
    {
        return $this->name;
    }

    /**
     * Testcase description
     *
     * @access private
     * @type   string
     */
    var $title = "";

    /**
     * Getter for testcase title
     *
     * @access public
     * @return string  value of
     */
    function getTitle() 
    {
        return $this->title;
    }
    
    /**
     * Setter for testcase title
     *
     * @access public
     * @param  string  new value for
     */
    function setTitle($title) 
    {
        $this->title = $title;
    }
    
    /**
     * Test code to decide whether to skip a test
     *
     * @access private
     * @type   string
     */
    var $skipif = "";

    /**
     * Getter for skipif test code
     *
     * @access public
     * @return string  value of
     */
    function getSkipif() 
    {
        return $this->skipif;
    }
    
    /**
     * Setter for skipif testcode
     *
     * @access public
     * @param  string  new value for
     */
    function setSkipIf($code) 
    {
        $this->skipif = $code;
    }
    
    /**
     * GET data
     *
     * @access private
     * @type   string
     */
    var $get = false;

    /**
     * Getter for GET data
     *
     * @access public
     * @return string  value of
     */
    function getGet() 
    {
        return $this->get;
    }
    
    /**
     * Setter for GET data
     *
     * @access public
     * @param  string  new value for
     */
    function setGet($data) 
    {
        $this->get = $data;
    }
    
    /**
     * raw POST data
     *
     * @access private
     * @type   string
     */
    var $post = false;

    /**
     * Getter for raw POST data
     *
     * @access public
     * @return string  value of
     */
    function getPost() 
    {
        return $this->post;
    }
    
    /**
     * Setter for raw POST data
     *
     * @access public
     * @param  string  new value for
     */
    function setPost($data) 
    {
        $this->post = $data;
    }
    
    /**
     * actual test code
     *
     * @access private
     * @type   string
     */

    /**
     * Getter for test code
     *
     * @access public
     * @return string  value of
     */
    function getCode() 
    {
        return $this->code;
    }
    
    /**
     * Setter for test code
     *
     * @access public
     * @param  string  new value for
     */
    function setCode($code) 
    {
        $this->code = $code;
    }
    
    /**
     * expected output for test code
     *
     * @access private
     * @type   string
     */

    /**
     * Getter for expected output
     *
     * @access public
     * @return string  value of
     */
    function getOutput() 
    {
        return $this->output;
    }
    
    /**
     * Setter for expected output
     *
     * @access public
     * @param  string  new value for
     */
    function setOutput($data) 
    {
        $this->output = $data;
    }

    /** 
     * all required properties set?
     *
     * @access public
     * @return bool
     */
    function complete() 
    {
        if (empty($this->code))   return PEAR::raiseError("no code specified for test case");
        if (empty($this->output)) return PEAR::raiseError("no output specified for test case");
        return true;
    }
    
    /**
     * generate testcase file
     *
     * @access public
     * @param  object  the complete extension context
     */
    function writeTest($extension) 
    {
        ob_start();

        echo "--TEST--\n{$this->name}\n";
        
        if (!empty($this->skipif)) {
            echo "--SKIPIF--\n{$this->skipif}\n";
        }

        if (!empty($this->post)) {
            echo "--POST--\n{$this->post}\n";
        }

        if (!empty($this->get)) {
            echo "--GET--\n{$this->get}\n";
        }
        
        echo "--FILE--\n{$this->code}";
        echo "--EXPECT--\n{$this->output}";

        $fp = fopen("{$extension->name}/tests/{$this->name}.phpt", "w");
        fputs($fp, ob_get_contents());
        ob_end_clean();
        fclose($fp);
    }
}
?>