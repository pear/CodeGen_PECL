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
 * @copyright  2005 Hartmut Holzgraefe
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
 * @copyright  2005 Hartmut Holzgraefe
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
		foreach (array("version", "date", "state", "notes") as $key) {
			if ($this->$key !== "") {
				$code.= "    <$key>{$this->$key}</$key>\n";
			}
		}
		$code.= "  </release>\n";
		
		return $code;
	}

}

?>