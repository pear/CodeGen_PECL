<?php
/**
 * Class for managing PHP internal resource types
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
 * Class for managing PHP internal resource types
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Resource 
    extends CodeGen_PECL_Element 
{
    /**
     * Resource type name
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
            return PEAR::raiseError("'$name' is not a valid resource name");
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
     * Type of the payload that the resource data pointer points to
     *
     * @var string
     * @access private
     */
    protected $payload = "void";

    /**
     * Set method for payload type
     *
     * @access public
     * @param  string type name
     * @return bool   true on success
     */
    function setPayload($type)
    {
        $this->payload = $type;
        
        return true;
    }
    
    /**
     * Get method for payload type
     *
     * @access public
     * @return string
     */
    function getPayload()
    {
        return $this->payload;
    }


    /**
     * Whether the resource memory is allocated and freed by the extension itself
     *
     * @var bool
     * @access private
     */
    protected $alloc = true;

    /**
     * Set method for alloc
     *
     * @access public
     * @param  bool   allocate memory?
     * @return bool   true on success
     */
    function setAlloc($text)
    {
        $this->alloc = (bool)$text;
        
        return true;
    }

    /**
     * Get mehod for alloc
     *
     * @access public
     * @return bool
     */
    function getAlloc()
    {
        return $this->alloc;
    }
    

    /** 
     * Code snippet to be added to the resource destructor callback
     *
     * @var string
     * @access private
     */
    protected $destruct = "";

    /**
     * Set method for destructor snippet
     *
     * @access public
     * @param  string C code snippet
     * @return bool   true on success
     */
    function setDestruct($text)
    {
        $this->destruct = $text;
        
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
     * Generate resource registration code for MINIT()
     *
     * @access public
     * @return string C code snippet
     */
    function minitCode() {
        $code = $this->ifConditionStart();

        $code.= "
le_{$this->name} = zend_register_list_destructors_ex({$this->name}_dtor, 
                       NULL, \"{$this->name}\", module_number);

";

        $code.= $this->ifConditionEnd();
  
        return $code;
    }


    /**
     * Generate C code header block for resources
     *
     * @access public
     * @param  string Extension name
     * @return string C code
     */
    static function cCodeHeader($name) 
    {
        return "/* {{{ Resource destructors */\n";
    }

    /**
     * Generate C code footer block for resources
     *
     * @access public
     * @param  string Extension name
     * @return string C code
     */
    static function cCodeFooter($name) 
    {
      return "/* }}} *\n\n";
    }

    /** 
     * Generate C code for resource destructor callback
     *
     * @access public
     * @param  object extension
     * @return string C code snippet
     */
    function cCode($extension) {
        $code = $this->ifConditionStart();

        $code.= "int le_{$this->name};\n";

        if ($extension->getLanguage() == "cpp") {
            $code.= 'extern "C" ';
        }

        $code.= 
"void {$this->name}_dtor(zend_rsrc_list_entry *rsrc TSRMLS_DC)
{
    {$this->payload} * resource = ({$this->payload} *)(rsrc->ptr);

";

        $code .= $extension->codegen->varblock($this->destruct);

        if ($this->alloc) {
            $code .= "\n\tefree(resource);\n";
        }
        
        $code.= "}\n";

        $code.= $this->ifConditionEnd();

        $code.= "\n";
        
        return $code;
    }



    /** 
     * Generate covenience macros for resource access
     *
     * @access public
     * @return string C code snippet
     */
    function hCode() {
        $upname = strtoupper($this->name);
        
        $code = $this->ifConditionStart();

        $code.= "
#define {$upname}_REGISTER(r)   ZEND_REGISTER_RESOURCE(return_value, r, le_{$this->name });
#define {$upname}_FETCH(r, z)   ZEND_FETCH_RESOURCE(r, {$this->payload} *, z, -1, ${$this->name}, le_{$this->name }); if (!r) { RETURN_FALSE; }
";

        $code.= $this->ifConditionEnd();

        $code.= "\n";

        return $code;
    }



    /** 
     * Generate config.m4 to check for payload type
     *
     * @access public
     * @return string autoconf code snippet
     */
    function configm4($extension_name) {
        if ($this->ifCondition) {
            return ""; // TODO implement more clever checking
        }

        return "  AC_CHECK_TYPE(".$this->getPayload()." *, [], [AC_MSG_ERROR(required payload type for resource ".$this->getName()." not found)], [#include \"\$srcdir/php_{$extension_name}.h\"])\n";
    }



    /** 
     * Generate documentation for this resource
     *
     * @access public 
     * @param  string id basename for extension
     * @return string DocBook XML code snippet
     */
    function docEntry($base) {
        return "
    <section id='$base.resources.{$this->name}'>
     <title><literal>{$this->name}</literal></title>
     <para>
      {$this->description}
     </para>
    </section>
";
    }
    
}

?>
