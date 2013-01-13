<?php
/**
 * Class for managing PHP stream wrappers
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
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Stream.php,v 1.4 2006/10/09 21:27:05 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class for managing PHP stream wrappers
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Stream
    extends CodeGen_PECL_Element
{
    /**
     * Stream type name
     *
     * @var string
     * @access private
     */
    protected $name = "unknown";

    /**
     * Set method for name
     *
     * @access public
     * @param  string name
     * @return bool   true on success
     */
    function setName($name)
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid stream name");
        }

        $this->name = $name;

        return true;
    }

    function getName()
    {
        return $this->name;
    }

    /**
     * DocBook XML snippet that describes the resource for the manual
     *
     * @var string
     * @access private
     */
    protected $summary = "";

    /**
     * Set method for destructor snippet
     *
     * @access public
     * @param  string C code snippet
     * @return bool   true on success
     */
    function setSummary($text)
    {
        $this->summary = $text;

        return true;
    }

    /**
     * DocBook XML snippet that describes the resource for the manual
     *
     * @var string
     * @access private
     */
    protected $description = "";

    /**
     * Set method for destructor snippet
     *
     * @access public
     * @param  string C code snippet
     * @return bool   true on success
     */
    function setDescription($text)
    {
        $this->description = $text;

        return true;
    }

    /**
     * code blocks for various handlers
     *
     * @var array
     */
    protected $codeBlocks = array();

    /**
     * add code for a handler
     *
     * @param  string handler role
     * @param  string C code snippet
     */
    function addCode($role, $code)
    {
        if (isset($codeBlock[$role])) {
            return PEAR::raiseError("Codeblock '$role' was already set");
        }

        if (!in_array($role, array("open", "close", "stat", "urlstat", "diropen",
                                   "unlink", "rename", "mkdir", "rmdir",
                                   "write", "read", "flush", "seek", "cast", "set"))) {
            return PEAR::raiseError("'$role' is not a valid stream codeblock type");
        }

        $this->codeBlocks[$role] = $code;
    }

    /**
     * Generate resource registration code for MINIT()
     *
     * @access public
     * @return string C code snippet
     */
    function minitCode()
    {
        return "/* ".$this->name." stream goes here */\n";
    }

    /**
     * Generate C code for resource destructor callback
     *
     * @access public
     * @param  object extension
     * @return string C code snippet
     */
    function cCode($extension)
    {
        ob_start();

        echo "static php_stream_ops php_{$this->name}_stream_ops = {\n";

        echo "    ";
        echo isset($this->codeBlocks['write']) ? "php_{$this->name}_file_write," : "NULL, /* write */";
        echo "\n";

        echo "    ";
        echo isset($this->codeBlocks['read']) ? "php_{$this->name}_file_read," : "NULL, /* read */";
        echo "\n";

        echo "    ";
        echo isset($this->codeBlocks['close']) ? "php_{$this->name}_file_close," : "NULL, /* close */";
        echo "\n";

        echo "    ";
        echo isset($this->codeBlocks['flush']) ? "php_{$this->name}_file_flush," : "NULL, /* flush */";
        echo "\n";

        echo '"'.$this->summary.'"'."\n";

        echo "    ";
        echo isset($this->codeBlocks['cast']) ? "php_{$this->name}_file_cast," : "NULL, /* cast */";
        echo "\n";

        echo "    ";
        echo isset($this->codeBlocks['stat']) ? "php_{$this->name}_file_stat," : "NULL, /* stat */";
        echo "\n";

        echo "    ";
        echo isset($this->codeBlocks['set']) ? "php_{$this->name}_file_set_option," : "NULL, /* set_option */";
        echo "\n";

        echo "};\n\n";

        return ob_get_clean();
    }

    /**
     * Generate covenience macros for resource access
     *
     * @access public
     * @return string C code snippet
     */
    function hCode($extension)
    {
        return "/* ".$this->name." stream goes here */\n";
    }

    /**
     * Generate documentation for this resource
     *
     * @access public
     * @param  string id basename for extension
     * @return string DocBook XML code snippet
     */
    function docEntry($base)
    {
        return "   ";
    }

}

