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
        function __construct($classname) 
        {
            $this->classname = $classname;
        }

        /**
         * The name of the class this method belongs to
         *
         * @var   string
         */
        private $classname = "";
       

        /**
         * Name for procedural alias of this method
         *
         * @var   string
         */
        private $proceduralName = "";

        function getProceduralName() 
        {
            return $this->proceduralName;
        }
     
        function setProceduralName($name) 
        {
            if (!$this->isName($name)) {
                return PEAR::raiseError("'$name' is not a valid function alias name");             
            }

            $this->proceduralName = $name;

            return true;
        }

        /**
         * Is this an abstract method?
         *
         * @var   bool
         */
        private $isAbstract = false;

        function isAbstract() 
        {
            $this->isAbstract = true;
        }

        /**
         * Is this an interface method?
         *
         * @var   bool
         */
        private $isInterface = false;

        function isInterface() 
        {
            $this->isInterface = true;
        }

        /**
         * Is this a final method?
         *
         * @var   bool
         */
        private $isFinal = false;

        function isFinal() 
        {
            $this->isFinal = true;
        }

        /**
         * Is this a static method?
         *
         * @var   bool
         */
        private $isStatic = false;

        function isStatic() 
        {
            $this->isStatic = true;
        }

        /**
         * Visibility of this property
         *
         * @var   string 
         */
        private $access = "public";

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
         * Code needed ahead of the method table 
         *
         * Abstract/Interface methods need to define their argument
         * list ahead of the method table
         *
         * @returns string
         */
        function argInfoCode() {
            if ($this->isAbstract) {
                
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
            return "
    if (zend_parse_method_parameters($argc TSRMLS_CC, getThis(), \"$argString\", ".join(", ",$argPointers).") == FAILURE) {
      return;
    }

";
        }


        /**
         * Set parameter and return value information from PHP style prototype
         *
         * @access public
         * @param  string  PHP style prototype 
         * @return bool    Success status
         */
        function setProto($proto, $extension) {
            parent::setProto($proto, $extension);

            $param = array();
            $param['name']    = "thisObj";
            $param['type']    = "object";
            $param['subtype'] = $this->classname;
            $param['byRef']   = true;

            array_unshift($this->params, $param);
        }

        /**
         * Create registration line for method table
         *
         * @param  string  Name of class owning this method
         * @return string  C code snippet
         */
        function methodEntry() 
        {
            $code= "PHP_ME({$this->classname}, {$this->name}, NULL, ";
            $code.= "ZEND_ACC_".strtoupper($this->access);
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
            $code.= ")";
            
            return $code;
        }

        /**
         * Create proto line for method 
         *
         * @param  string  Name of class owning this method
         * @return string  C code snippet
         */
        function cProto() 
        {
            return "PHP_METHOD({$this->classname}, {$this->name})";
        }
    }
?>