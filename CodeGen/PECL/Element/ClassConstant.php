<?php
/**
 * Class describing a PHP class constant within a PECL extension
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
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class describing a PHP class constant within a PECL extension
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

class CodeGen_PECL_Element_ClassConstant
    extends CodeGen_PECL_Element
{
    /**
     * The constants name
     *
     * @access private
     * @var    string
     */
    protected $name;

    /**
     * The constants PHP data type
     *
     * @access private
     * @var    string
     */
    protected $type = "string";

    /**
     * The constants value
     *
     * @access private
     * @var    string
     */
    protected $value;

    /**
     * The constants description text
     *
     * @access private
     * @var    string
     */
    protected $desc;

    /**
     * Set constant name
     *
     * @access public
     * @param  string  the name
     * @return bool    true on success
     */
    function setName($name)
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name'is not a valid constant name");
        }

        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for constant names");
        }

        $this->name = $name;

        return true;
    }

    /**
     * Get constant name
     *
     * @access public
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Set constant type
     *
     * @access public
     * @param  string  the type
     * @return bool    true on success
     */
    function setType($type)
    {
        if (!in_array($type, array('int', 'float', 'string'))) {
            return PEAR::raiseError("'$type' is not a valid constant type, only int, float and string");
        }

        $this->type = $type;

        return true;
    }

    /**
     * Set constant value
     *
     * @access public
     * @param  string  the value
     * @return bool    true on success
     */
    function setValue($value)
    {
        $this->value = $value;

        if ($this->value == $this->name) {
            $this->deinfe = false;
        }

        return true;
    }

    /**
     * Set constant descriptive text
     *
     * @access public
     * @param  string  the name
     * @return bool    true on success
     */
    function setDesc($desc)
    {
        $this->desc = $desc;

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
        $key     = '"'.$this->name.'"';
        $key_len = strlen($this->name);

        $code = $this->ifConditionStart();

        switch ($this->type) {
        case "string":
            $code.= "        zend_declare_class_constant_stringl($classptr, $key, $key_len, \"{$this->value}\", ".strlen($this->value)." TSRMLS_CC );\n";
            break;
        case "int":
            $code.= "        zend_declare_class_constant_long($classptr, $key, $key_len, {$this->value} TSRMLS_CC );\n";
            break;
        case "float":
            $code.= "        zend_declare_class_constant_double($classptr, $key, $key_len, {$this->value} TSRMLS_CC );\n";
            break;
        default:
            return "";
        }

        $code.= $this->ifConditionEnd();

        return $code;
    }

    /**
     * MINIT code header
     *
     * @access public
     * @return string
     */
    static function minitHeader()
    {
        ob_start();

        echo "    /* {{{ Constant registration */\n\n";
        echo "    do {\n";
        echo "        zval *tmp, *val;\n";

        return ob_get_clean();
    }

    /**
     * MINIT code footer
     *
     * @access public
     * @return string
     */
    static function minitFooter()
    {
        ob_start();

        echo "    } while(0);\n\n";
        echo "    /* } Constant registration */\n\n";

        return ob_get_clean();
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */

