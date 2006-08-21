<?php
/**
 * Class describing a PHP class within a PECL extension 
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
require_once "CodeGen/PECL/Element/Property.php";
require_once "CodeGen/PECL/Element/ClassConstant.php";
require_once "CodeGen/PECL/Element/Method.php";
require_once "CodeGen/PECL/Element/ObjectInterface.php";

/**
 * Class describing a PHP class within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

    class CodeGen_PECL_Element_Class
      extends CodeGen_PECL_Element 
      implements CodeGen_PECL_Element_ObjectInterface
    {
        /**
         * The class name
         *
         * @var     string
         */
        protected $name  = "unknown";

        /**
         * class name setter
         *
         * @param string Classname
         */
        function setName($name) 
        {
            if (!self::isName($name)) {
                return PEAR::raiseError("'$name' is not a valid class name");
            }
            
            $this->name = $name;
            
            return true;
        }

        /**
         * class name getter
         *
         * @return string Classname
         */
        function getName()
        {
            return $this->name;
        }



        /**
         * A short description
         *
         * @var     string
         */
        protected $summary = "";

        /**
         * Description summary setter
         *
         * @param string Description summary
         */
        function setSummary($text)
        {
            $this->summary = $text;
            return true;
        }




        /**
         * A long description
         *
         * @var     string
         */
        protected $description  = "";

        /**
         * Class description setter
         *
         * @param string Class description
         */
        function setDescription($text)
        {
            $this->description = $text;
            return true;
        }


        
        /**
         * Documentation
         *
         * TODO: isn't this in Element base class already?
         *
         * @var   string
         */
        protected $documentation = "";

        /**
         * Class documentation setter
         *
         * @param string Class documentation
         */
        function setDocumentation($text) {
            $this->documentation = $text;
        }


        
        /**
         * Extents which class?
         *
         * @var   string
         */
        protected $extends = "";

        /**
         * Set parent class that this class inherits from
         *
         * @param string parent class name
         */
        function setExtends($parent) 
        {
            if (!self::isName($parent)) {
                return PEAR::raiseError("'$parent' is not a valid parent class name");
            }           

            $this->extends = $parent;
        }
        

        /**
         * Implemented Interfaces
         *
         * @var   array
         */
        protected $implements = array();
        
        /**
         * Add an interface that this class implements
         *
         * @param string interface name
         */
        function addInterface($interface) 
        {
            if (!self::isName($interface)) {
                return PEAR::raiseError("'$interface' is not a valid interface name");
            }           
        
            if (isset($this->implements[$interface])) {
                return PEAR::raiseError("interface '$interface' added twice");
            }

            $this->implements[$interface] = $interface;
        }


        /**
         * Properties
         *
         * @var   array
         */
        protected $properties = array();
        
        /**
         * Add a class property
         *
         * @param object a class property object
         */
        function addProperty(CodeGen_PECL_Element_Property $property) 
        {
            $name = $property->getName();

            if (isset($this->properties[$name])) {
                return PEAR::raiseError("property '$name' already exists");
            }

            $this->properties[$name] = $property;
        }

        
        /**
         * Constants
         *
         * @var   array
         */
        protected $constants = array();
        
        /**
         * Add a constant to a class
         *
         * @param object a class constant object
         */
        function addConstant(CodeGen_PECL_Element_ClassConstant $constant) 
        {
            $name = $constant->getName();

            if (isset($this->constants[$name])) {
                return PEAR::raiseError("constant '$name' already exists");
            }

            $this->constants[$name] = $constant;
        }

        
        /**
         * Member Functions
         *
         * @var   array
         */
        protected $methods = array();
        
        /**
         * Add a method definition to the class
         *
         * @param object class method object
         */
        function addMethod(CodeGen_PECL_Element_Method $method) 
        {
            $name = $method->getName();

            if (isset($this->functions[$name])) {
                return PEAR::raiseError("method '$name' already exists");
            }

            $this->methods[$name] = $method;

            return true;
        }



        /**
         * Is this an abstract class?
         *
         * @var   bool
         */
        protected $isAbstract = false;

        /**
         * Make class abstract
         */
        function isAbstract() 
        {
            $this->isAbstract = true;
        }



        /**
         * Is this class final?
         *
         * @var   bool
         */
        protected $isFinal = false;

        /**
         * Make class final
         */
        function isFinal()
        {
            $this->isFinal = true;
        }



        /**
         * Is this an interface?
         *
         * @var   bool
         */
        protected $isInterface = false;

        /**
         * Make class an interface 
         */
        function isInterface() 
        {
            // TODO: check for already added non-abstract stuff
            
            $this->isInterface = true;
        }


        /**
         * Class payload data type  
         *
         * @var string  C type name class payload data
         */
        protected $payloadType = "";

        /**
         * Payload type setter
         * 
         * @param string
         */
        function setPayloadType($type) 
        {
            // TODO check
            $this->payloadType = $type;
        }

        /**
         * Payload type getter
         *
         * @return string
         */
        function getPayloadType() 
        {
            return $this->payloadType;
        }


        /**
         * Allocate storage space for payload data? 
         *
         * @var bool
         */
        protected $payloadAlloc = true;

        /**
         * Payload alloc setter
         * 
         * @param string
         */
        function setPayloadAlloc($alloc) 
        {
            $this->payloadAlloc = (bool)$alloc;
        }

        /**
         * Payload init code snippet
         *
         * @param string
         */
        protected $payloadCtor = "";
        
        /** 
         * Payload init code setter
         *
         * @param string code snippet
         */
        function setPayloadCtor($code)
        {
            $this->payloadCtor = $code;
        }

        /**
         * Payload init code getter
         *
         * @return string code snippet
         */
        function getPayloadCtor($extension)
        {
            $code = "";

            if ($this->payloadAlloc) {
                $code.= "    payload->data = ({$this->payloadType} *)malloc(sizeof({$this->payloadType}));\n";
            }

            $code .= $extension->codegen->varblock($this->payloadCtor);

            return $code;
        }


        /**
         * Payload dtor code snippet
         *
         * @param string
         */
        protected $payloadDtor = "";
        
        /** 
         * Payload dtor code setter
         *
         * @param string code snippet
         */
        function setPayloadDtor($code)
        {
            $this->payloadDtor = $code;
        }

        /**
         * Payload dtor code getter
         *
         * @return string code snippet
         */
        function getPayloadDtor($extension)
        {
            $code = $extension->codegen->varblock($this->payloadDtor);

            if ($this->payloadAlloc) {
                $code.= "    free(payload->data);\n";
            }

            return $code;
        }
        /**
         * Create C header entry for clas
         *
         * @access public
         * @param  class Extension  extension the function is part of
         * @return string           C header code snippet
         */
        function hCode($extension) 
        {
            $code = "";

            if ($this->payloadType) {
                $upname = strtoupper($this->name);
                echo "
typedef struct _php_obj_{$this->name} {
    zend_object obj;
    {$this->payloadType} *data;
} php_obj_{$this->name}; 
";
            }

            foreach ($this->methods as $method) {
                $code.= $method->hCode($extension);
            }

            return $code;
        }


        /**
         * Generate global scope code
         *
         * @access public
         * @return string
         */
        function globalCode($extension) 
        {
            $upname = strtoupper($this->name);

            ob_start();

            echo "/* {{{ Class {$this->name} */\n\n";

            echo "static zend_class_entry * {$this->name}_ce_ptr = NULL;\n\n";

            echo "/* {{{ Methods */\n\n";
            foreach ($this->methods as $method) {
                echo $method->cCode($extension);
                echo "\n";
            }

            echo "static zend_function_entry {$this->name}_methods[] = {\n";

            foreach ($this->methods as $method) {
                echo "    ".$method->methodEntry()."\n";
            }

            echo "    { NULL, NULL, NULL }\n";
            echo "};\n\n";
        
            echo "/* }}} Methods */\n\n";

            if ($this->payloadType) {
                echo "
static zend_object_handlers {$this->name}_obj_handlers;

static void {$this->name}_obj_free(void *object TSRMLS_DC)
{
    php_obj_{$this->name} *payload = (php_obj_{$this->name} *)object;
    
    {$this->payloadType} *data = payload->data;
".$this->getPayloadDtor($extension)."
    efree(object);
}

static zend_object_value {$this->name}_obj_create(zend_class_entry *class_type TSRMLS_DC)
{
    php_obj_{$this->name} *payload;
    zval         *tmp;
    zend_object_value retval;

    payload = (php_obj_{$this->name} *)emalloc(sizeof(php_obj_{$this->name}));
    memset(payload, 0, sizeof(php_obj_{$this->name}));
    payload->obj.ce = class_type;
".$this->getPayloadCtor($extension)."
    retval.handle = zend_objects_store_put(payload, NULL, (zend_objects_free_object_storage_t) {$this->name}_obj_free, NULL TSRMLS_CC);
    retval.handlers = &{$this->name}_obj_handlers;
    
    return retval;
}

";
            }

            echo "static void class_init_{$this->name}(void)\n{\n";

            echo "    zend_class_entry ce;\n\n";

            echo "    INIT_CLASS_ENTRY(ce, \"{$this->name}\", {$this->name}_methods);\n";

            if ($this->payloadType) {
                echo "    ce.create_object = {$this->name}_obj_create;\n";
            }

            if ($this->extends) {
                echo "    {$this->name}_ce_ptr = zend_register_internal_class_ex(&ce, NULL, \"{$this->extends}\" TSRMLS_CC);\n";
            } else {
                echo "    {$this->name}_ce_ptr = zend_register_internal_class(&ce);\n";
            }

            if ($this->payloadType) {
                echo "    memcpy(&{$this->name}_obj_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));\n";
                echo "    {$this->name}_obj_handlers.clone_obj = NULL;\n";
            }

            if ($this->isFinal) {
              echo "    {$this->name}_ce_ptr->ce_flags |= ZEND_ACC_FINAL_CLASS;\n";
            }

            if ($this->isAbstract) {
              echo "    {$this->name}_ce_ptr->ce_flags |= ZEND_ACC_EXPLICIT_ABSTRACT_CLASS;\n";
            }

            if (!empty($this->properties)) {
                echo "\n    /* {{{ Property registration */\n\n";
                foreach ($this->properties as $property) {
                    echo $property->minitCode($this->name."_ce_ptr");
                }
                echo "    /* }}} Property registration */\n\n";
            }
            
            if (!empty($this->constants)) {
                echo "\n";
                echo CodeGen_PECL_Element_ClassConstant::minitHeader();
                foreach ($this->constants as $constant) {
                    echo $constant->minitCode($this->name."_ce_ptr");
                }
                echo CodeGen_PECL_Element_ClassConstant::minitFooter();
            }
            
            if (count($this->implements)) {
                ob_start();

                echo "zend_class_entry **tmp;\n";
                
                $interfaces = array();
                foreach ($this->implements as $interface) {
                    echo sprintf("if (SUCCESS == zend_hash_find(CG(class_table), \"%s\", %d, (void **)&tmp)) {\n", 
                                    strtolower($interface), strlen($interface) + 1);
                    echo "    zend_class_implements({$this->name}_ce_ptr TSRMLS_CC, 1, *tmp);\n";
                    echo "} else {\n";
                    echo "    php_error(E_WARNING, \"Couldn't find interface '$interface' while setting up class '{$this->name}', skipped\");\n";
                    echo "}\n";
                }

                echo $extension->codegen->varblock(ob_get_clean());
            }
                

            echo "}\n\n";

            echo "/* }}} Class {$this->name} */\n\n";

            return ob_get_clean();
        }


        /**
         * MINIT code fragment
         * 
         * @access public
         * @return string
         */
        function minitCode($extension) 
        {
            return "class_init_{$this->name}();\n";
        }
        

        /**
         * DocBook documentation fragment
         *
         * @access public
         * @return string
         */
        function docEntry($base) 
        {
            $xml = "";
 
            return $xml;
        }

        /**
         * Write method test cases
         *
         * @param object Extension to write tests for
         */
        function writeTests(CodeGen_PECL_Extension $extension)
        {
            foreach ($this->methods as $method) {
                $method->writeTest($extension);
            }
        }

        /**
         * Return function alias entries for all methods
         *
         */
        function functionAliasEntries()
        {
            $code = "";

            foreach($this->methods as $method) 
            {
                $code.= $method->functionAliasEntry();
            }

            return $code;
        }
        
    }

?>
