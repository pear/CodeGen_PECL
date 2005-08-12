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
require_once "CodeGen/PECL/Element/ClassConstant.php";
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
        private $properties = array();
        
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
        private $constants = array();
        
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
        private $isAbstract = false;

        function isAbstract() 
        {
            $this->isAbstract = true;
        }



        /**
         * Is this class final?
         *
         * @var   bool
         */
        private $isFinal = false;

        function isFinal() 
        {
            $this->isFinal = true;
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
         * Create C header entry for clas
         *
         * @access public
         * @param  class Extension  extension the function is part of
         * @return string           C header code snippet
         */
        function hCode($extension) 
        {
            $code = "";

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

            echo "static void class_init_{$this->name}(void)\n{\n";

            echo "    zend_class_entry ce;\n\n";
  
            echo "    INIT_CLASS_ENTRY(ce, \"{$this->name}\", {$this->name}_methods);\n";

            if ($this->extends) {
                echo "    {$this->name}_ce_ptr = zend_register_internal_class_ex(&ce, NULL, \"{$this->extends}\" TSRMLS_CC);\n";
            } else {
                echo "    {$this->name}_ce_ptr = zend_register_internal_class(&ce);\n";
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
                echo "    do {\n";
                echo "        zend_class_entry **tmp;\n";
                
                $interfaces = array();
                foreach ($this->implements as $interface) {
                    echo sprintf("        if (SUCCESS == zend_hash_find(CG(class_table), \"%s\", %d, (void **)&tmp)) {\n", 
                                    strtolower($interface), strlen($interface)+1, $interface);
                    echo "            zend_class_implements({$this->name}_ce_ptr TSRMLS_CC, 1, *tmp);\n";
                    echo "        } else {\n";
                    echo "            php_error(E_WARNING, \"Couldn't find interface '$interface' while setting up class '{$this->name}', skipped\");\n";
                    echo "        }\n";
                }
                echo "    } while(0);\n";
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