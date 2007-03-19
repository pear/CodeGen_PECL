<?php
/**
 * PECL specific extensions to the Release class
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
require_once "CodeGen/Release.php";


/**
 * PECL specific extensions to the Release class
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005, 2006 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Release 
    extends CodeGen_Release
{
    /**
     * generate XML fragment for package.xml
     *
     * @access public
     * @return string XML fragment
     */
    function packageXml()
    {
        $code = "\n  <release>\n";
        foreach (array("version", "state", "notes") as $key) {
            $code.= "    <$key>";
            if ($this->$key !== "") {
              $code.= htmlentities($this->$key);
            } else {
              $code.= "unknown";
            }
            $code.= "</$key>\n";
        }
        if ($this->date !== "") {
            $code .= "    <date>".date("Y-m-d", $this->date)."</date>\n";
        }
        $code.= "  </release>\n";
        
        return $code;
    }

    /**
     * generate XML fragment for package.xml 2.0
     *
     * @access public
     * @param  object License 
     * @return string XML fragment
     */
    function packageXml2($license)
    {
        $code ="";

        $date = $this->date ? $this->date : time();
        $code.= "  <date>".date("Y-m-d", $date)."</date>\n";       

        $code.= "  <version>\n";
        $code.= "    <release>{$this->version}</release>\n";
        $code.= "    <api>{$this->version}</api>\n";
        $code.= "  </version>\n";
        $code.= "  <stability>\n";
        $code.= "    <release>{$this->state}</release>\n";
        $code.= "    <api>{$this->state}</api>\n";
        $code.= "  </stability>\n\n";

        // this is ugly but with package.xml 2.0 this now has to be put
        // here whereas in 1.0 license was a tag at the same level as
        // the release block ... :/
        if ($license instanceof CodeGen_License) {
            $uri = $license->getUri();
            if (!empty($uri)) {
                $uri = "uri=\"$uri\" ";
            }

            $code.= "  <license {$uri}filesource=\"LICENSE\">{$license->getShortName()}</license>\n\n";
        } else {
            $code.= "  <license>unknown</license\n\n";
        }

        $code .="  <notes>\n".htmlentities($this->notes)."\n  </notes>\n\n";

        return $code;
    }


    /**
     * Code snippet for phpinfo output
     *
     * @access public
     * @param  string extension name
     * @return string C code snippet
     */
    function phpinfoCode($name) 
    {
        return sprintf("    php_printf(\"<p>Version %s%s (%s)</p>\\n\");\n",
                       $this->version,
                       $this->state,
                       date("Y-m-d", $this->date));
    }
}

?>
