<?php
/**
 * Class describing a class property within a PECL extension
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
 * @version    CVS: $Id: Property.php,v 1.8 2006/10/10 07:18:46 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";
require_once "CodeGen/PECL/Element/Function.php";
require_once "CodeGen/PECL/Element/Class.php";

/**
 * Class describing a class property within a PECL extension
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Property
    extends CodeGen_PECL_Element
{
    /**
     * Is this an abstract property?
     *
     * @var   bool
     */
    protected $isAbstract = false;

    function isAbstract()
    {
        $this->isAbstract = true;
    }

    /**
     * Is this an interface property?
     *
     * @var   bool
     */
    protected $isInterface = false;

    function isInterface()
    {
        $this->isInterface = true;
    }

    /**
     * Is this a final property?
     *
     * @var   bool
     */
    protected $isFinal = false;

    function isFinal()
    {
        $this->isFinal = true;
    }

    /**
     * Is this a static property?
     *
     * @var   bool
     */
    protected $isStatic = false;

    function isStatic()
    {
        $this->isStatic = true;
    }

    /**
     * Visibility of this property
     *
     * @var   string
     */
    protected $access = "public";

    function setAccess($access)
    {
        switch ($this->access) {
        case "private":
        case "protected":
        case "public":
            $this->access = $access;
            return true;
        default:
            return PEAR::raiseError("'$access' is not a valid access property");
        }
    }

    /**
     * Property type
     *
     * @var string
     */
    protected $type = "null";

    function setType($type)
    {
        switch ($type) {
        case "int":
        case "long":
            $this->type = "long";
            break;
        case "float":
        case "double":
            $this->type = "double";
            break;
        case "string":
            $this->type = "string";
            break;
        case "null":
        case "void":
            $this->type = "null";
            break;
        default:
            return PEAR::raiseError("'$type' is not a valid property type");
        }

        return true;
    }

    /**
     * Property name
     *
     * @var string
     */
    protected $name = "unknown";

    function setName($name)
    {
        if (!$this->isName($name)) {
            return PEAR::raiseError("'$name' is not a valid property name");
        }

        $this->name = $name;

        return true;
    }

    function getName()
    {
        return $this->name;
    }

    /**
     * Default value
     *
     * @var    string
     * @access private
     */
    protected $value = "";

    function setValue($value)
    {
        // TODO check?
        $this->value = $value;

        return true;
    }

    /**
     * MINIT code fragment
     *
     * @access public
     * @return string
     */
    function minitCode($classptr)
    {
        $code = $this->ifConditionStart();

        $code.= "    zend_declare_property_{$this->type}({$classptr}, \n";
        $code.= '        "' . $this->name . '", ' . strlen($this->name) . ", ";

        switch ($this->type) {
        case "string":
            $code .= '"'.$this->value.'", ';
            break;
        case "long":
        case "double":
            // TODO zend_declare_property_double only available in 5.1? add a configure check for this?
            $code .= $this->value.", ";
            break;
        default:
            break;
        }

        $code.= "\n        ZEND_ACC_".strtoupper($this->access);
        if ($this->isStatic) {
            $code.= " | ZEND_ACC_STATIC";
        }
        if ($this->isAbstract) {
            $code.= " | ZEND_ACC_ABSTRACT";
        }
        if ($this->isInterface) {
            $code.= " | ZEND_ACC_INTERFACE";
        }
        if ($this->isFinal) {
            $code.= " | ZEND_ACC_FINAL";
        }

        $code .= " TSRMLS_DC);\n\n";

        $code.= $this->ifConditionEnd();

        return $code;
    }
}
?>

