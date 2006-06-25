<?php
/**
 * Class that describes a member function within a PHP class
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
require_once "CodeGen/PECL/Element/Function.php";
require_once "CodeGen/PECL/Element/Class.php";

/**
 * Class that describes a member function within a PHP class
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
    class CodeGen_PECL_Element_Method
      extends CodeGen_PECL_Element_Function
    {
        function __construct(Codegen_PECL_Element_Class $class) 
        {
            $this->class     = $class;
            $this->classname = $class->getName(); 
        }

        /**
         * The class this method belongs to
         *
         * @var   object
         */
        protected $class;

        /**
         * Name of class this method belongs to
         *
         * @var string
         */
        protected $classname;
         
       

        /**
         * Name for procedural alias of this method
         *
         * @var   string
         */
        protected $proceduralName = "";

        function getProceduralName() 
        {
            return $this->proceduralName;
        }
     
        function setProceduralName($name) 
        {
            if ($name == "default") {
                $name = $this->classname."_".$this->name;
            } else if (!$this->isName($name)) {
                return PEAR::raiseError("'$name' is not a valid function alias name");             
            }

            $this->proceduralName = $name;

            return true;
        }

        /**
         * distinguishable name getter
         *
         * @return string
         */
        function getFullName()
        {
            return $this->classname."__".$this->name;
        }

        /**
         * Is this an abstract method?
         *
         * @var   bool
         */
        protected $isAbstract = false;

        function isAbstract() 
        {
            $this->isAbstract = true;

            return $this->validate();
        }

        /**
         * Is this an interface method?
         *
         * @var   bool
         */
        protected $isInterface = false;

        function isInterface() 
        {
            $this->isInterface = true;
            $this->isAbstract  = true;

            return $this->validate();
        }

        /**
         * Is this a final method?
         *
         * @var   bool
         */
        protected $isFinal = false;

        function isFinal() 
        {
            $this->isFinal = true;

            return $this->validate();
        }

        /**
         * Is this a static method?
         *
         * @var   bool
         */
        protected $isStatic = false;

        function isStatic() 
        {
            $this->isStatic = true;

            return $this->validate();
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
                return $this->validate();
            default:
                return PEAR::raiseError("'$access' is not a valid access property");
            }
        }

        /**
         * Hook for parameter parsing API function 
         *
         * @param  string  C expr. for number of arguments
         * @param  string  Argument string
         * @param  array   Argument variable pointers
         */
        protected function parseParameterHook($argc, $argString, $argPointers)
        {
            if ($this->name == "__construct") {
                $code = parent::parseParameterHook($argc, $argString, $argPointers);
                $code.= "\n    _this_zval = getThis();\n";
            } else {
            $code = "
    if (zend_parse_method_parameters($argc TSRMLS_CC, getThis(), \"$argString\", ".join(", ",$argPointers).") == FAILURE) {
        return;
    }

";
            }
            $code .= "    _this_ce = Z_OBJCE_P(_this_zval);\n\n";

            $payload = $this->class->getPayloadType();
            if ($payload) {
                $code.= "    payload = (php_obj_{$this->classname} *) zend_object_store_get_object(_this_zval TSRMLS_CC);\n";
            }

            return $code;
        }


        /**
         * Generate local variable declarations
         *
         * @return string C code snippet
         */
        function localVariables($extension) 
        {
            $code = parent::localVariables($extension);
            $code.= "    zend_class_entry * _this_ce;\n";
            
            if ($this->name == "__construct") {
                $code.= "    zval * _this_zval;\n";
            }

            $payload = $this->class->getPayloadType(); 
            if ($payload) {
                $code.= "    php_obj_{$this->classname} *payload;\n";
            }

            return $code;
        }

        /**
         * Set parameter and return value information from PHP style prototype
         *
         * @access public
         * @param  string  PHP style prototype 
         * @return bool    Success status
         */
        function setProto($proto, $extension) {
            $err = parent::setProto($proto, $extension);
            if (PEAR::isError($err)) {
                return $err;
            }
            
            if ($this->name != "__construct") {
                $param = array();
                $param['name']    = "_this_zval";
                $param['type']    = "object";
                $param['subtype'] = $this->classname;
                $param['byRef']   = true;

                array_unshift($this->params, $param);
            }
        }

        /**
         * Create registration line for method table
         *
         * @param  string  Name of class owning this method
         * @return string  C code snippet
         */
        function methodEntry() 
        {
            $code = "";

			// TODO catch arg #2->type == void
            $arginfo = (count($this->params)>1) ? ($this->getFullName()."_args") : "NULL";

            if ($this->isAbstract || $this->isInterface) {
                $code.= "ZEND_FENTRY({$this->name}, NULL, $arginfo, ZEND_ACC_ABSTRACT | ";
                if ($this->isInterface) {
                    $code.= " ZEND_ACC_INTERFACE | ";
                }
            } else {
                $code.= "PHP_ME({$this->classname}, {$this->name}, $arginfo, /**/";
            }

            $code.= "ZEND_ACC_".strtoupper($this->access);

            if ($this->isStatic) {
                $code.= " | ZEND_ACC_STATIC";
            }

            if ($this->isFinal) {
                $code.= " | ZEND_ACC_FINAL";
            }

            $code.= ")";
            
            return $code;
        }

        /**
         * Create registration line for method table
         *
         * @param  string  Name of class owning this method
         * @return string  C code snippet
         */
        function functionAliasEntry() 
        {
            if (!$this->proceduralName) {
                return "";
            }

			// TODO arg_info?
            return "    PHP_MALIAS({$this->classname}, {$this->proceduralName}, {$this->name}, NULL, ZEND_ACC_PUBLIC)\n";
        }
        /**
         * Create proto line for method 
         *
         * @param  string  Name of class owning this method
         * @return string  C code snippet
         */
        function cProto() 
        {
            if ($this->isAbstract || $this->isInterface) {
                return "";
            }
            
            return "PHP_METHOD({$this->classname}, {$this->name})";
        }


        /**
         * Create C code implementing the PHP userlevel function
         *
         * @access public
         * @param  class Extension  extension the function is part of
         * @return string           C code implementing the function
         */
        function cCode($extension) 
        {
            if (!$this->isAbstract && !$this->isInterface) {
                return parent::cCode($extension);
            }

            return "";
        }

        /**
         * Validate settings, spot conflicts
         *
         * @return true or exception
         */
        function validate()
        {
            /* an abstract method can't be final or private and can't have code */
            if ($this->isAbstract && $this->isFinal) {
                return PEAR::raiseError("A method can't be abstract and final at the same time");
            }

            if ($this->isAbstract && $this->access == "private") {
                return PEAR::raiseError("A method can't be abstract and private at the same time");
            }

            if ($this->isAbstract && !empty($this->code)) {
                return PEAR::raiseError("A method can't be abstract and implemented at the same time");
            }

            // TODO add "abstract may not have test" as soon as test mess is cleaned up
            return true;
        }

        /**
         * The role attribute doesn't apply here
         *
         * @param  string
         * @return exception
         */
        function setRole($role)
        {
            return PEAR::raiseError("the role attribute is not defined for class member functions");
        }

        /**
         * Code addition must be validated here
         *
         * @param  string  code snippet
         */
        function setCode($code)
        {
            parent::setCode($code);

            return $this->validate();
        }

        /**
         * Method name checking is less strict
         * 
         * Method names can't clash with PHP standard functions
         * so we can just check for syntax and keywords here
         *
         * @param string method name
         */
        function setName($name)
        {
            if (!self::isName($name)) {
                return PEAR::raiseError("'$name' is not a valid function name");
            }
            
            if (self::isKeyword($name)) {
                return PEAR::raiseError("'$name' is a reserved word which is not valid for function names");
            }
            
            $this->name = $name;
            
            return true;
        }

        
        /**
         * Create test case for this function
         *
         * @access public
         * @param  object  extension the function is part of
         * @return object  generated test case
         */
        function createTest(CodeGen_PECL_Extension $extension) 
        {
            if ($this->isAbstract || $this->isInterface) {
                return null;
            }

            $test = parent::createTest($extension);

            $test->setName($this->getFullName());
            $test->setTitle($this->classname."::".$this->name."() member function");
            
            return $test;
        }

        /**
         * Code needed ahead of the method table 
         *
         * Abstract/Interface methods need to define their argument
         * list ahead of the method table
         *
         * @returns string
         */
        function argInfoCode() {
            $code = "";

            if (count($this->params) > 0) {
                $code.= "ZEND_BEGIN_ARG_INFO(".$this->getFullName()."_args, 0)\n";

                $params = $this->params;
                array_shift($params);

                $useTypeHints = true;

                foreach($params as $param) {
                    if ($param['type'] != "object" || !isset($param['subtype'])) {
                        $useTypeHints = false;
                        break;
                    }
                }

                // TODO optional paramteres?
                foreach($params as $param) {
                    $byRef = empty($param["byRef"]) ? 0 : 1;
                    if ($useTypeHints) {
                        $code.= "  ZEND_ARG_OBJ_INFO(0, $param[name], $param[subtype], 0)\n";
                    } else {
                        $code.= "  ZEND_ARG_INFO($byRef, $param[name])\n";
                    }
                }                
                
                $code.= "ZEND_END_ARG_INFO()\n";
            }

            return $code;
        } 

    }


?>
