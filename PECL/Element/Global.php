<?php
/**
 * Class describing a thread-global within a PECL extension 
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
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class describing a thread-global within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Global 
  extends CodeGen_PECL_Element 
{
    // TODO add description and use it for C code comments

    /**
     * The name of the global
     *
     * @access private
     * @var     string
     */
    protected $name;
    
    /**
     * Set method for name
     *
     * @access public
     * @var string global variable name
     */
    function setName($name) 
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid global name");
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
     * The type of the global
     *
     * @access private
     * @var     string
     */
    protected $type;
    
    /**
     * Set method for type
     *
     * @access public
     * @var string C type name
     */
    function setType($name)
    {
        if (!self::isType($name)) {
            return PEAR::raiseError("'$name' is not a valid type for a global");
        }
        
        $this->type = $name;
        
        return true;
    }
    
    /**
     * Get method for name
     *
     * @access public
     * @return string
     */
    function getType($name)
    {
        return $this->type;
    }

    

    /**
     * Default value
     *
     * @access private
     * @var     string
     */
    protected $value = null;

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
     * Overriding type check as we deal with real C types here
     *
     * @access public
     * @var string C type specifier
     */
    function isType($type) 
    {
        /* check is rather naive as it doesn't know about context
           so we check for a sequence of valid names for now
           TODO: check for either simple type, struct/class or single word (typedef)
        */
        $array = explode(" ", str_replace('*',' ',$type));
        foreach ($array as $name) {
            if (empty($name)) continue;
            // TODO :: should only be allowed for C++, not C extensions
            if (!$this->isName(str_replace("::","", $name))) return false; 
        }
        return true;
    }
  
  
  
    /**
     * Generate header for global variable registration code
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet 
     */
    static function cCodeHeader($name) 
    { 
        return "static void php_{$name}_init_globals(zend_{$name}_globals *{$name}_globals)\n{\n";
    }

    /**
     * Generate registration code for this global variable
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet 
     */
    function cCode($name) 
    {
        $code = "    {$name}_globals->{$this->name} = ";

        if ($this->value != null) {
            $code .= $this->value;
        } else {
            if (strstr($this->type, "*")) {
                $code .= "NULL";
            } else {
                $code .= "0";
            }
        } 
        
        $code .= ";\n";

        return $code;
    }
  
    /**
     * Generate footer for global variable registration code
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet 
     */
    static function cCodeFooter($name) 
    {
        return '
}

static void php_'.$name.'_shutdown_globals(zend_'.$name.'_globals *'.$name.'_globals)
{
}';
    }


    /**
     * Generate header for global variable registration code in header file
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet 
     */
    static function hCodeHeader($name) 
    {
        return "ZEND_BEGIN_MODULE_GLOBALS({$name})\n";
     }

    /**
     * Generate declaration for this global variable in header file
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet 
     */
    function hCode($name) 
    {
        return "  {$this->type} {$this->name};\n";
    }

    /**
     * Generate footer for global variable registration code in header file
     *
     * @access private
     * @param  string extension basename
     * @return string C code snippet 
     */
    static function hCodeFooter($name) 
    {
        $upname = strtoupper($name);
    
        return "
ZEND_END_MODULE_GLOBALS({$name})

#ifdef ZTS
#define {$upname}_G(v) TSRMG({$name}_globals_id, zend_{$name}_globals *, v)
#else
#define {$upname}_G(v) ({$name}_globals.v)
#endif

";
      
    }
  
}

?>
