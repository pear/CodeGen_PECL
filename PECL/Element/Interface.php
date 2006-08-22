<?php
/**
 * Class describing a PHP interface within a PECL extension 
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
require_once "CodeGen/PECL/Element/Method.php";
require_once "CodeGen/PECL/Element/ObjectInterface.php";

require_once "CodeGen/Tools/Indent.php";

/**
 * Class describing a PHP interface within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

    class CodeGen_PECL_Element_Interface
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
         * name set()er
         *
         * @param string 
         */
        function setName($name) 
        {
            if (!self::isName($name)) {
                return PEAR::raiseError("'$name' is not a valid interface name");
            }
            
            $this->name = $name;
            
            return true;
        }

        /**
         * name get()er
         *
         * @return string
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
         * summary set()er
         *
         * @param string 
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
         * description set()er
         *
         * @param string 
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
         * description set()er
         *
         * @param string 
         */
        function setDocumentation($text) {
            $this->documentation = $text;
        }


        
        /**
         * Extents which interface?
         *
         * @var   string
         */
        protected $extends = "";

        /**
         * extends set()er
         *
         * @param string 
         */
        function setExtends($parent) 
        {
            if (!self::isName($parent)) {
                return PEAR::raiseError("'$parent' is not a valid parent interface name");
            }           

            $this->extends = $parent;
        }
        

        
        /**
         * Member Functions
         *
         * @var   array
         */
        protected $methods = array();
        
        /**
         * Add a method to the interface
         *
         * @param object
         */
        function addMethod(CodeGen_PECL_Element_Method $method) 
        {
            $name = $method->getName();

            if (isset($this->functions[$name])) {
                return PEAR::raiseError("method '$name' already exists");
            }

# TODO
#            if (!$method->isAbstract || !$method->isInterface) {
#                return PEAR::raiseError("an interface method has to be declated both abstract and interface");
#            }
            
            $this->methods[$name] = $method;

            return true;
        }



        /**
         * Create C header entry for interface
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

            if ($code) {
                $code = $this->ifConditionStart() . $code . $this->ifConditionEnd();
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

            echo "/* {{{ Interface {$this->name} */\n\n";

            echo $this->ifConditionStart();

            echo "static zend_class_entry * {$this->name}_ce_ptr = NULL;\n\n";

            echo "static zend_function_entry {$this->name}_methods[] = {\n";

            foreach ($this->methods as $method) {
                echo "    ".$method->methodEntry()."\n";
            }

            echo "    { NULL, NULL, NULL }\n";
            echo "};\n\n";

            echo "static void interface_init_{$this->name}(void)\n{\n";
            echo "    zend_class_entry ce;\n";  
            if ($this->extends) {
                echo "    zend_class_entry **parent_ce;\n";
            }
            echo "\n";
            echo "    INIT_CLASS_ENTRY(ce, \"{$this->name}\", {$this->name}_methods);\n";
            echo "    {$this->name}_ce_ptr = zend_register_internal_interface(&ce TSRMLS_CC);\n";
            
            if ($this->extends) {
                echo sprintf("        if (SUCCESS == zend_hash_find(CG(class_table), \"%s\", %d, (void **)&parent_ce)) {\n", 
                             strtolower($this->extends), strlen($this->extends) + 1);
                echo "";
                echo "    if (parent_ce) {\n";
                echo "        zend_do_inheritance({$this->name}_ce_ptr, *parent_ce TSRMLS_CC);\n";
                echo "    }\n";
            }

            echo "}\n\n";    

            echo  $this->ifConditionEnd();

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
            return $this->ifConditionStart() . "interface_init_{$this->name}();\n" . $this->ifConditionEnd();
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

        function getPayloadType() 
        {
            return "";
        }
    }

?>