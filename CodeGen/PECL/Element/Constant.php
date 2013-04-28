<?php
/**
 * Class describing a PHP constant within a PECL extension
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class describing a PHP constant within a PECL extension
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

class CodeGen_PECL_Element_Constant
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
     * Create a C #define for this constant
     *
     * @access private
     * @var    bool
     */
    protected $define = false;

    /**
     * Resource group
     *
     * @var string
     */
    protected $group = "default";

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
     * @param  string
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
     * Get constant value
     *
     * @access public
     * @return string  the value
     */
    function getValue()
    {
        return $this->value;
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
     * Set group this constant belongs to
     *
     * @access public
     * @param string  group name
     * @return bool   true on success
     */
    function setGroup($group)
    {
        $this->group = $group;

        return true;
    }

    /**
     * Get group this constant belongs to
     *
     * @access public
     * @return string group name
     */
    function getGroup()
    {
        return $this->group;
    }

    /**
     * Set define flag
     *
     * @access public
     * @param  string  the value
     * @return bool    true on success
     */
    function setDefine($value)
    {
        if (is_bool($value)) {
            $this->define = $value;
            return true;
        } else if (in_array($value, array("yes", "no"), true)) {
            $this->define = ($value === 'yes');
            return true;
        }

        return PEAR::raiseError("'define' attribute has to be 'yes' or 'no', '$value' given");
    }

    /**
     * Create C code snippet to register this constant
     *
     * @access public
     * @param  class Extension  extension we are owned by
     * @return sting            C code snippet
     */
    function cCode($extension)
    {
        $code = $this->ifConditionStart();

        switch ($this->type) {
        case "int":
            $code.= "REGISTER_LONG_CONSTANT(\"{$this->name}\", {$this->value}, CONST_PERSISTENT | CONST_CS);\n";
            break;

        case "float":
            $code.= "REGISTER_DOUBLE_CONSTANT(\"{$this->name}\", {$this->value}, CONST_PERSISTENT | CONST_CS);\n";
            break;

        case "string":
            $code.= "REGISTER_STRINGL_CONSTANT(\"{$this->name}\", \"{$this->value}\", ".strlen($this->value).", CONST_PERSISTENT | CONST_CS);\n";
            break;
        }

        $code.= $this->ifConditionEnd();

        return $code;
    }

    /**
     * Create C header snippet to register this constant
     *
     * @access public
     * @param  class Extension  extension we are owned by
     * @return sting            C code snippet
     */
    function hCode($extension)
    {
        if (!$this->define) {
            return "";
        }

        $code = $this->ifConditionStart();

        switch ($this->type) {
        case "int":
        case "float":
            $code.= "#define {$this->name} {$this->value}\n";
            break;
        case "string":
            $code.= "#define {$this->name} \"$value\"\n";
            break;
        }

        $this->ifConditionEnd();

        return $code;
    }

    /**
     * Generate DocBook XML section block header
     *
     * @access public
     * @param  string  Extension name
     * @return string  DocBook XML snippet
     */
    static function docHeader($name)
    {
        return "  <variablelist>\n";
    }

    /**
     * Generate DocBook XML entry for this constant
     *
     * @access public
     * @param  string  Extension name (currently unused)
     * @return string  DocBook XML snippet
     */
    function docEntry($base)
    {
      // TODO: indent desc.

        return "
   <varlistentry>
    <term>
     <constant>{$this->name}</constant>
     (<type>{$this->type}</type>)
    </term>
    <listitem>
     <simpara>
      {$this->desc}
     </simpara>
    </listitem>
   </varlistentry>\n";

    }

    /**
     * Generate DocBook XML section block footer
     *
     * @access public
     * @param  string  Extension name
     * @return string  DocBook XML snippet
     */
    static function docFooter($name)
    {
        return "  </variablelist>\n";
    }
}

