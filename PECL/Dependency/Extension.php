<?php
/**
 * Class representing a cross-extension dependency
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
 * include
 */
require_once "CodeGen/PECL/Element.php";

/**
 * Class representing a cross-extension dependency
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Dependency_Extension
    extends CodeGen_Element
{
    /** 
     * Extension name
     *
     * @var string
     */
    protected $name;

        /**
     * name getter
     *
     * @return string
     */
    function getName() 
    {
        return $this->name;
    }

    /**
     * name setter
     *
     * @param string
     */
    function setName($name) 
    {
        if (!$this->isName($name)) {
            PEAR::raiseError("'$name' is not a valid extension name ");
        }

        $this->name = $name;
    }


    /** 
     * Extension version relation
     *
     * @var array
     */
    protected $version = array();

    /**
     * version setter
     *
     * @param string
     */
    function setVersion($version, $relation = "ge") 
    {
        switch ($relation) {
        case "ge":
        case "le":
        case "gt":
        case "lt":
        case "eq":
            break;

        case ">=":
            $relation = "ge";
            break;

        case ">":
            $relation = "gt";
            break;

        case "<=":
            $relation = "le";
            break;

        case "<":
            $relation = "lt";
            break;

        case "=":
        case "==":
            $relation = "eq";
            break;

        default: 
            return PEAR::raiseError("'$relation' is not a valid version relation ");
        }

        // TODO check version string

        $this->version = array("version" => $version, "relation" => $relation);
    }


    /** 
     * Extension name
     *
     * @var string
     */
    protected $type = "REQUIRED";

    /**
     * type setter
     *
     * @param string
     */
    function setType($type) 
    {
        $type = strtoupper($type);
        
        switch ($type) {
        case "REQUIRED":
        case "OPTIONAL":
        case "CONFLICTS":
            $this->type = $type;
            break;
        default:
            return PEAR::raiseError("'$type' is not a valid dependency type "); 
        }
    }

    /**
     * Generate extension C code snippet
     *
     * @param  object extension
     * @return string code snippet
     */
    function cCode($extension)
    {
        if (!empty($this->version)) {
            return sprintf('    ZEND_MOD_%s_EX("%s", "%s", "%s")', $this->type, $this->name, $this->version["relation"], $this->version["version"])."\n";
        } else {
            return sprintf('    ZEND_MOD_%s("%s")', $this->type, $this->name)."\n";
        }
    }

    /**
     * Generate extension C code header
     *
     * @param  object extension
     * @return string code snippet
     */
    static function cCodeHeader($extension)
    {
        return "/* {{{ cross-extension dependencies */\n
#if ZEND_EXTENSION_API_NO >= 220050617
static zend_module_dep ".$extension->getName()."_deps[] = {
";
    }

    /**
     * Generate extension C code footer
     *
     * @param  object extension
     * @return string code snippet
     */
    static function cCodeFooter($extension)
    {
        return "    {NULL, NULL, NULL, 0}
};
#endif
/* }}} */
";
    }


    /**
     * package.xml dependencie entry
     *
     * @return string XML snippet
     */
    function packageXML()
    {
        $xml = '    <dep type="ext"';
        if (!empty($this->version)) {
            $xml.= sprintf(' rel="%s" version="%s"', $this->version["relation"], $this->version["version"]);
        }
        $xml.= ">{$this->name}</dep>\n";

        return $xml;
    }



    /**
     * package.xml 2.0 dependencie entry
     *
     * @param  mixed  requested type(s), either string or array
     * @return string XML snippet
     */
    function packageXML2($types = false)
    {
        $xml = "    <extension><name>{$this->name}</name>";

        if (!empty($types)) {
            $types = (array)$types;
            if (!in_array($this->type, $types))
            {
                return "";
            }
        }

        switch ($this->type ) {
        case 'REQUIRED':
        case 'OPTIONAL':
            if (!empty($this->version)) {
                $version = $this->version["version"];
                switch ($this->version["relation"]) {
                case 'gt':
                    $xml.= "<exclude>$version</exclude>";
                    /* fallthru */
                case 'ge':
                    $xml.= "<min>$version</min>";
                    break;
                case 'lt':
                    $xml.= "<exclude>$version</exclude>";
                    /* fallthru */
                case 'le':
                    $xml.= "<max>$version</max>";
                    break;
                case 'eq':
                    $xml.= "<min>$version</min>";
                    $xml.= "<max>$version</max>";
                    break;
                }
            }
            break;

        case 'CONFLICTS':
            $xml.= "<conflicts/>";
            break;
        }
        $xml.= "<extension>\n";

        return $xml;
    }
}
