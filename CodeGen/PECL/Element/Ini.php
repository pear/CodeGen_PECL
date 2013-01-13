<?php
/**
 * Class describing a PHP ini directive within a PECL extension
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
 * @version    CVS: $Id: Ini.php,v 1.7 2006/10/12 13:11:25 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class describing a PHP ini directive within a PECL extension
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Ini
    extends CodeGen_PECL_Element
{
    // TODO this should be a subclass of CodeGen_PECL_Element_Global ?

    /**
     * Directive name
     *
     * @access private
     * @var     string
     */
    protected $name;

    /**
     * Set method for name
     *
     * @access public
     * @var string directive name
     */
    function setName($name)
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid php.ini directive name");
        }

        $this->name = $name;

        return true;
    }

    /**
     * Get method for name
     *
     * @access public
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Directive data type
     *
     * @access private
     * @var     string
     */
    protected $type;

    /**
     * Set method for data type
     *
     * @access public
     * @param string one of bool, int, float, string
     */
    function setType($type)
    {
        switch ($type) {
        case "bool":
            $this->cType = "zend_bool";
            if (!$this->onupdate) {
                $this->onupdate = "OnUpdateBool";
            }
            return true;

        case "int":
            $this->cType = "long";
            if (!$this->onupdate) {
                $this->onupdate = "OnUpdateLong";
            }
            return true;

        case "float":
            $this->cType = "double";
            if (!$this->onupdate) {
                $this->onupdate = "OnUpdateReal";
            }
            return true;

        case "string":
            $this->cType = "char *";
            if (!$this->onupdate) {
                $this->onupdate = "OnUpdateString";
            }
            return true;

        default:
            return PEAR::raiseError("'$this->type' not supported, only bool, int, float and string");
        }
    }

    /**
     * Get method for type
     *
     * @access public
     * @return string
     */
    function getType()
    {
        return $this->cType;
    }

    /**
     * Directive default value
     *
     * @access private
     * @var     string
     */
    protected $value;

    /**
     * Set method for default value
     *
     * @access public
     * @param string default value
     */
    function setValue($value)
    {
        // TODO checks
        $this->value = $value;

        return true;
    }

    /**
     * Get method for default value
     *
     * @access public
     * @return string
     */
    function getValue()
    {
        return $this->value;
    }

    /**
     * Directive description
     *
     * @access private
     * @var     string
     */
    protected $desc;

    /**
     * Set method for directive description
     *
     * @access public
     * @param string description
     */
    function setDesc($desc)
    {
        $this->desc = $desc;

        return true;
    }

    /**
     * Get method for description
     *
     * @access public
     * @return string
     */
    function getDesc()
    {
        return $this->desc;
    }

    /**
     * Directive access mode
     *
     * @access private
     * @var     string
     */
    protected $access = "PHP_INI_ALL";

    /**
     * Set method for access mode
     *
     * @access private
     * @param string access mode specification (system|perdir|user|all)
     */
    function setAccess($access)
    {
        switch ($access) {
        case "system":
            $this->access = "PHP_INI_SYSTEM";
            return true;
        case "perdir":
            $this->access = "PHP_INI_PERDIR";
            return true;
        case "user":
            // TODO shouldn't this be ALL instead?
            $this->access = "PHP_INI_USER";
            return true;
        case "all":
        case "":
            $this->access = "PHP_INI_ALL";
            return true;
        default:
            return PEAR::raiseError("'$access' is not a valid access mode (system|perdir|user|all)");
        }
    }

    /**
     * Get method for access
     *
     * @access public
     * @return string
     */
    function getAccess()
    {
        return $this->access;
    }

    /**
     * Directive OnUpdate handler
     *
     * @access private
     * @var     string
     */
    protected $onupdate;

    /**
     * Set method for OnUpdate handler
     *
     * @access public
     * @param string C function name
     */
    function setOnUpdate($name)
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid update function name");
        }

        $this->onupdate = $name;

        return true;
    }

    /**
     * Get method for update handler
     *
     * @access public
     * @return string
     */
    function getOnupdate()
    {
        return $this->onupdate;
    }

    /**
     * Internal C type that stores the directives value
     *
     * @access private
     * @var     string
     */
    protected $cType;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->setType("string");
    }

    /**
     * Generate header for ini directive registration code
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet
     */
    static function cCodeHeader($name)
    {
        // this is a small incompatibility between ZE1 and ZE2 APIs
        // "OnUpdateInt" was changed to "OnUpdateLong" as it actualy
        // works on C type "long", not "int"
        // the actual implementation didn't change so it is safe to
        // just revert the name change using a cpp #define
        // TODO: skip this for extensions that depend on PHP 5 anyway
        $code = "
#ifndef ZEND_ENGINE_2
#define OnUpdateLong OnUpdateInt
#endif
";

        $code .="PHP_INI_BEGIN()\n";

        return $code;
    }

    /**
     * Generate registration code for this directive
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet
     */
    function cCode($name)
    {
        $code = $this->ifConditionStart();

        $code.= "  STD_PHP_INI_ENTRY(\"$name.{$this->name}\", \"{$this->value}\", {$this->access}, {$this->onupdate}, {$this->name}, zend_{$name}_globals, {$name}_globals)\n";

        $code.= $this->ifConditionEnd();

        return $code;
    }

    /**
     * Generate footer for ini directive registration code
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet
     */
    static function cCodeFooter($name)
    {
        return "PHP_INI_END()\n\n";
    }

    /**
     * Generate header for ini directive documentation
     *
     * @access private
     * @param  string extension basename
     * @return string DocBook XML snippet
     */
    static function docHeader($name)
    {
        return
            "    <table>
     <title>$name &ConfigureOptions;</title>
      <tgroup cols='4'>
       <thead>
        <row>
         <entry>&Name;</entry>
         <entry>&Default;</entry>
         <entry>&Changeable;</entry>
         <entry>Changelog</entry>
        </row>
       </thead>
      <tbody>
";
    }

    /**
     * Generate documentation for ini directive documentation
     *
     * @access private
     * @param  string id basename for extension
     * @return string DocBook XML snippet
     */
    function docEntry($base)
    {
        return
            "    <row>
     <entry>$this->name</entry>
     <entry>$this->value</entry>
     <entry>$this->access</entry>
     <entry></entry>
    </row>
";
    }

    /**
     * Generate footer for ini directive documentation
     *
     * @access private
     * @param  string extension basename
     * @return string DocBook XML snippet
     */
    static function docFooter($name)
    {
        return
            "     </tbody>
    </tgroup>
   </table>
";
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */

