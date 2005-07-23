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
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/PECL/Element.php";
require_once "CodeGen/PECL/Element/Property.php";
require_once "CodeGen/PECL/Element/Method.php";

require_once "CodeGen/Tools/Indent.php";

/**
 * Class describing a PHP class within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

    class CodeGen_PECL_Element_Class
      extends CodeGen_PECL_Element 
    {
        /**
         * The class name
         *
         * @var     string
         */
        private $name  = "unknown";

        function setName($name) 
        {
            if (!self::isName($name)) {
                return PEAR::raiseError("'$name' is not a valid class name");
            }
            
            $this->name = $name;
            
            return true;
        }

        function getName()
        {
            return $this->name;
        }



        /**
         * A short description
         *
         * @var     string
         */
        private $summary = "";

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
        private $description  = "";

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
        private $documentation = "";

        function setDocumentation($text) {
            $this->documentation = $text;
        }


        
        /**
         * Extents which class?
         *
         * @var   string
         */
        private $extends = "";

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
        private $implements = array();
        
        function addInterface($interface) 
        {
            if (!self::isName($parent)) {
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
        private $properties = array();
        
        function addProperty($property) 
        {
            if (!is_a($property, "CodeGen_PECL_Element_Property")) {
                return PEAR::raiseError("argument is not CodeGen_PECL_Element_Property");
            }
            
            if (isset($this->properties[$property->getName()])) {
                return PEAR::raiseError("property '$property' already exists");
            }

            $this->properties[$property->getName()] = $property;
        }

        
        /**
         * Member Functions
         *
         * @var   array
         */
        public $methods = array();
        
        function addMethod($method) 
        {
            if (!is_a($method, "CodeGen_PECL_Element_Method")) {
                return PEAR::raiseError("argument is not CodeGen_PECL_Element_Method");
            }
            
            if (isset($this->functions[$method->name])) {
                return PEAR::raiseError("method '$function' already exists");
            }

            $this->methods[$method->name] = $method;

            return true;
        }



        /**
         * Is this an abstract class?
         *
         * @var   bool
         */
        private $isAbstract = false;

        function isAbstract() 
        {
            $this->isAbstract = true;
        }



        /**
         * Is this an interface?
         *
         * @var   bool
         */
        private $isInterface = false;

        function isInterface() 
        {
            // TODO: check for already added non-abstract stuff
            
            $this->isInterface = true;
        }


        /**
         * Generate global scope code
         *
         * @access public
         * @return string
         */
        function globalCode($extension) 
        {
            $code = "static zend_class_entry * {$this->name}_ce_ptr = NULL;\n\n";

            foreach ($this->methods as $method) {
                $code .= $method->cCode($extension);
                $code .= "\n";
            }

            $code.= "static zend_function_entry {$this->name}_methods[] = {\n";

            foreach ($this->methods as $method) {
                $code .= "  ".$method->methodEntry()."\n";
            }

            $code.= "  { NULL, NULL, NULL }\n";
            $code.="};\n";
        

            $code.="
static void class_init_{$this->name}(void) 
{
    zend_class_entry ce;
    INIT_CLASS_ENTRY(ce, \"{$this->name}\", {$this->name}_methods);
    {$this->name}_ce_ptr = zend_register_internal_class(&ce);
";

            foreach ($this->properties as $property) {
              $code .= "    ".$property->minit_code($this->name."_ce_ptr");
            }

            $code.= "}\n";

            return $code;
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
        
    }

?>