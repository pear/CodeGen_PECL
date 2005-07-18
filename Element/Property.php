<?php
/**
 * Class describing a class property within a PECL extension 
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
 * Class describing a class property within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
    class CodeGen_PECL_Element_Property
		extends CodeGen_PECL_Element
    {
        /**
         * Is this an abstract property?
         *
         * @var   bool
         */
        private $isAbstract = false;

        function isAbstract() 
        {
            $this->isAbstract = true;
        }

        /**
         * Is this an interface property?
         *
         * @var   bool
         */
        private $isInterface = false;

        function isInterface() 
        {
            $this->isInterface = true;
        }

        /**
         * Is this a final property?
         *
         * @var   bool
         */
        private $isFinal = false;

        function isFinal() 
        {
            $this->isFinal = true;
        }

        /**
         * Is this a static property?
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
		 * Property type
		 *
		 * @var string
		 */
		private $type = "null";

		function setType($type) 
		{
			switch ($type) {
			case "void":
				$type = "null";
				break;
			case "int":
				$type = "long";
				break;
			case "long":
			// case "float": not yet supported by ZEND API
			// case "double": not yet supported by ZEND API
			case "string":
			case "null":
				break;
			default:
				return PEAR::raiseError("'$type' is not a valid property type");
			}

			$this->type = $type;

			return true;
		}


		/**
		 * Property name
		 *
		 * @var string
		 */
		private $name = "unknown";

		function setName($name) 
		{
			if (!$this->isName($name)) {
				return PEAR::raiseError("'$name' is not a valid property name");
			}
			
			$this->name = $name;

			return true;
		}

		function getName() 
        {
			return $this->name;
		}

		/**
		 * Default value
		 *
		 * @var    string
		 * @access private
		 */
		private $value = "";

		function setValue($value) 
		{
			// TODO check?
			$this->value = $value;

			return true;
		}

		/** 
		 * MINIT code fragment
		 *
		 * @access public
		 * @return string
		 */
		function minitCode($classptr) {
			$code = "zend_declare_property_{$this->type}({$classptr}, ";
			$code.= '"'.$this->name.'", '.strlen($this->name).', ';

			switch ($this->type) {
			case "string":
				$code .= '"'.$this->value.'", ';
				break;
			case "long":
				$code .= (int)$this->value.", ";
				break;
			// case "double": not yet supported
			default: 
				break;
			}

			
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

			$code .= " TSRMLS_DC);\n";

			return $code;
		}
	}
?>