<?php
/**
 * Class that manages internal logo images for the extensions phpinfo block
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

/**
 * Class that manages internal logo images for the extensions phpinfo block
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Element_Logo 
  extends CodeGen_PECL_Element 
{
    /**
     * Basename
     *
     * @access private
     * @var string
     */
    var $name;
    
    /**
     * ID for URL call of image ("...?=ID")
     *
     * @access private
     * @var string
     */
    var $id;
    
    /**
     * The actual image data as a binary string
     *
     * @access private
     * @var string
     */
    var $data;
    
    /**
     *
     * @access private
     * @var string
     */
    var $mimeType = false;
    
    /**
     * Constructor
     *
     * @access public
     * @param  string image basename
     * @param  string source filename
     */
    function __construct($name) 
    {
        $this->name = $name;
        $this->id = '"'.strtoupper($name).'_LOGO_ID"';
    } 
    
    /** 
     * Set image data and mimetype
     *
     * @param  string  binary image data
     * @param  string  mimetype
     * @return bool    true on success
     */
    function setData($data, $mimetype = false)
    {
        $this->data = $data;

        if ($mimetype) {
            $this->mimetype = $mimetype;
        } else {
            // perform a little magic
            // we only accept GIF, PNG and JPEG here, so we can test 
            // for the 'magic' signatures ourself
            if (!strncmp("GIF8", $data, 4)) { 
                $this->mimetype = "image/gif";
            } else if(!strncmp(chr(0x89)."PNG\r\n", $data, 6)) {
                $this->mimetype = "image/png";
            } else if(ord($data[0]) == 0xff && ord($data[1]) == 0xd8) {
                $this->mimetype = "image/jpeg";
            } else {
                return PEAR::raiseError("can't detect mimetype for logo image data, pease add 'mimetype=...' attribute");
            }
        }

        return true;
    }

    /**
     * Load image data from file, set mimetype
     *
     * @param  string  path to image file
     * @param  string  mimetype
     * @return bool    true on success
     */
    function loadFile($path, $mimetype = false)
    {
        if (!is_readable($path)) {
            return PEAR::raiseError("Can't read logo image file '$path'");
        }

        return $this->setData(file_get_contents($path), $mimetype);
    }

    
    
    /** 
     * Code snippet for image registration in MINFO()
     *
     * @access public
     * @return string C code snippet
     */
    function minitCode() 
    {
        return "  php_register_info_logo({$this->id}, \"{$this->mimeType}\", {$this->name}_logo, ".strlen($this->data).");\n";
    }

    /** 
     * Code snippet for image release in MSHUTDOWN()
     *
     * @access public
     * @return string C code snippet
     */
    function mshutdownCode() 
    {
        return "  php_unregister_info_logo({$this->id});\n";
    }

    /**
     * Code snippet for image data declaration
     *
     * @access public
     * @param  string extension name
     * @return string C code snippet
     */
    function cCode($name) 
    {
        return "
#include \"php_logos.h\"

static unsigned char {$this->name}_logo[] = {
#include \"{$this->name}_logo.h\"
}; 
";
    }

    /**
     * Generate data for declaration include file
     *
     * @access public
     * @return string C code snippet
     */
    function hCode() 
    {
        $len = strlen($this->data);
        $code = " ";
        $i=0;
        for ($n = 0; $n < $len; $n++) {
            $code .= sprintf(" %3d",ord($this->data[$n]));
            if ($n == $len - 1) break;
            $code .=  ",";
            if (++$i==8) {
                $code .= "\n ";
                $i=0;
            }
        }
        
        $code .= "\n";
        
        return $code;
    }

}

?>
