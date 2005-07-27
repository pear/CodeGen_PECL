<?php
/**
 * Extension to the generic parser that adds PECL specific tags 
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
require_once "CodeGen/ExtensionParser.php";
require_once "CodeGen/PECL/Maintainer.php";

/**
 * Extension to the generic parser that adds PECL specific tags 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_ExtensionParser 
    extends CodeGen_ExtensionParser
{


    function tagstart_maintainer($attr)
    {
        // check: never popped?
        $this->pushHelper(new CodeGen_PECL_Maintainer);
        return true;
    }

    function tagstart_extension_release($attr)
    {
        $this->pushHelper(new CodeGen_PECL_Release);
        return true;
    }




    function tagstart_extension_function($attr)
    {
        $this->pushHelper(new CodeGen_PECL_Element_Function);
        
        $role = isset($attr["role"]) ? $attr["role"] : "public";
        
        if (isset($attr["name"])) {
            if ($role == "public" && $this->extension->getPrefix()) {
                $err = $this->helper->setName($this->extension->getPrefix()."_".$attr["name"]);
            } else {
                $err = $this->helper->setName($attr["name"]);
                }
            if (PEAR::isError($err)) {
                return $err;
            }
        } else {
            return PEAR::raiseError("'name' attribut for <function> missing");
        }
        
        $err = $this->helper->setRole($role);
        if (PEAR::isError($err)) {
            return $err;
        }
        
        return true;
    }
    
    function tagstart_extension_functions_function($attr) {
        return $this->tagstart_extension_function($attr);
    }
        
    function tagstart_extension_class_function($attr)
    {
        // TODO modify
        $this->pushHelper(new CodeGen_PECL_Element_Method($this->helper->getName()));
        
        if (isset($attr["name"])) {
            $err = $this->helper->setName($attr["name"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        } else {
            return PEAR::raiseError("'name' attribut for <function> missing");
        }
        
        return true;
    }
    
    function tagend_extension_function_summary($attr, $data) 
    {
        return $this->helper->setSummary(trim($data));
    }

    function tagstart_extension_function_description($attr)
    {
        $this->verbatim();
    }

    function tagend_extension_function_description($attr, $data) 
    {
        return $this->helper->setDescription(CodeGen_Tools_Indent::linetrim($data));
    }

    function tagend_extension_function_proto($attr, $data)
    {
        return $this->helper->setProto(trim($data), $this->extension);
    }

    function tagstart_extension_function_code($attr)
    {
        if (isset($attr["src"])) {
            if (!file_exists($attr["src"])) {
                return PEAR::raiseError("'src' file '$attr[src]' not found in <code>");                    
            }
            if (!is_readable($attr["src"])) {
                return PEAR::raiseError("Cannot read 'src' file '$attr[src]' in <code>");                    
            }
        }
    }

    function tagend_extension_function_code($attr, $data, $line=0, $file="")
    {
        if (isset($attr["src"])) {
            return $this->helper->setCode(CodeGen_Tools_Indent::linetrim(file_get_contents($attr["src"])));
        } else {
            return $this->helper->setCode($data, $line, $file);
        }
    }
    
    function tagend_extension_function_testcode($attr, $data)
    {
        return $this->helper->setTestCode(CodeGen_Tools_Indent::linetrim($data));
    }

    function tagend_extension_function_testresult($attr, $data)
    {
        return $this->helper->setTestResult(CodeGen_Tools_Indent::linetrim($data));
    }



    function tagend_extension_function($attr, $data) 
    {
        $err = $this->extension->addFunction($this->helper);
        $this->popHelper();
        return $err;
    }
    




    function tagend_extension_functions_function($attr, $data)
    {
        return $this->tagend_extension_function($attr, $data);
    }
    
    function tagend_extension_functions_function_code($attr, $data)
    {
        return $this->tagend_extension_function_code($attr, $data);
    }
    
    function tagend_extension_functions_function_summary($attr, $data)
    {
        return $this->tagend_extension_function_summary($attr, $data);
    }

    
    function tagend_extension_functions_function_description($attr, $data)
    {
        return $this->tagend_extension_function_description($attr, $data);
    }

    function tagend_extension_functions_function_proto($attr, $data)
    {
        return $this->tagend_extension_function_proto($attr, $data);
    }

    function tagend_extension_functions_function_testcode($attr, $data)
    {
        return $this->tagend_extension_function_testcode($attr, $data);
    }

    function tagend_extension_functions_function_testresult($attr, $data)
    {
        return $this->tagend_extension_function_testresult($attr, $data);
    }

    


    function tagend_class_function($attr, $data) 
    {
        $err = $this->helper_prev->addMethod($this->helper);
        $this->popHelper();
        return $err;
    }
    
    function tagend_functions($attr, $data) {
        return true;
    }        


        function tagstart_extension_resource($attr)
        {
            return $this->tagstart_resources_resource($attr);
        }

        function tagstart_resources_resource($attr)
        {
            $this->pushHelper(new CodeGen_PECL_Element_Resource);
            
            if (isset($attr["name"])) {
                $err = $this->helper->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("name attribut for resource missing");
            }

            if (isset($attr["payload"])) {
                $err = $this->helper->setPayload($attr["payload"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("payload attribut for resource missing");
            }

            if (isset($attr["alloc"])) {
                $err = $this->helper->setAlloc($this->toBool($attr["alloc"]));
                if (PEAR::isError($err)) {
                    return $err;
                }
            } 

            return true;
        }

        function tagend_resource_destruct($attr, $data)
        {
            return $this->helper->setDestruct(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_resource_description($attr, $data)
        {
            return $this->helper->setDescription(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_extension_resource($attr, $data) {
            return $this->tagend_resources_resource($attr, $data);
        }

        function tagend_resources_resource($attr, $data) 
        {
            $err = $this->extension->addResource($this->helper);
            $this->popHelper();
            return $err;
        }


        function tagend_resources($attr, $data) {
            return true;
        }



        function tagend_extension_logo($attr, $data)
        {
            // TODO checks
            if (!isset($attr["name"])) {
                $attr["name"] = $this->extension->name;
            }

            $logo = new CodeGen_PECL_Element_Logo($attr["name"]);

            if (!isset($attr["mimetype"])) {
                $attr["mimetype"] = false;
            }

            if (isset($attr["src"])) {
                $err = $logo->loadFile($attr["src"], $attr["mimetype"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                // we support uuencoded and base64 encoded embedded data
                $decoded = base64_decode($data);
                if (!is_string($decoded)) {
                    PEAR::raiseError("only uuencoded and base64 encoded image data is supported for embedded data");
                }
                    
                $err = $logo->setData($decoded, $attr["mimetype"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            }

            return $this->extension->addLogo($logo);
        }



        function tagend_extension_constant($attr, $data)
        {
            return $this->tagend_constants_constant($attr, $data);
        }

        function tagend_constants_constant($attr, $data)
        {
            $const = new CodeGen_PECL_Element_Constant;

            if (isset($attr["name"])) {
                $err = $const->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("name attribut for constant missing");
            }

            if (isset($attr["type"])) {
                $err = $const->setType($attr["type"]);
            } else {
                $err = $const->setType("int"); // default
            }
            if (PEAR::isError($err)) {
                return $err;
            }

            if (isset($attr["value"])) {
                $err = $const->setValue($attr["value"]);
            } else {
                $const->setDefine(false);
                $err = $const->setValue($attr["name"]); // default -> mimic a C #define or enum value                
            } 
            if (PEAR::isError($err)) {
                return $err;
            }
            
            if (isset($attr["define"])) {
                $err = $const->setDefine($attr["define"]);
            }
            if (PEAR::isError($err)) {
                return $err;
            }

            $const->setDesc(CodeGen_Tools_Indent::linetrim($data));

            return $this->extension->addConstant($const);
        }

        function tagend_constants($attr, $data) {
            return true;
        }


        
        function tagend_extension_global($attr, $data)
        {
            return $this->tagend_globals_global($attr, $data);
        }


        function tagend_globals_global($attr, $data)
        {
            $global = new CodeGen_PECL_Element_Global;

            if (isset($attr["name"])) {
                $err = $global->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("name attribut for global missing");
            }

            if (isset($attr["type"])) {
                $err = $global->setType($attr["type"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("type attribut for global missing");
            }

            if (isset($attr["value"])) {
                $err = $global->setValue($attr["value"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } 

            return $this->extension->addGlobal($global);
        }

        

        function tagend_extension_phpini($attr, $data) 
        {
            return $this->tagend_globals_phpini($attr, $data);
        }

        function tagend_globals_phpini($attr, $data)
        {
            $ini = new CodeGen_PECL_Element_Ini;

            if (isset($attr["name"])) {
                $err = $ini->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("name attribut for php.ini directive missing");
            }

            if (isset($attr["type"])) {
                $err = $ini->setType($attr["type"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } 

            if (isset($attr["value"])) {
                $err = $ini->setValue($attr["value"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } 

            if (isset($attr["access"])) {
                $err = $ini->setAccess($attr["access"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } 

            if (isset($attr["onupdate"])) {
                $err = $ini->setOnUpdate($attr["onupdate"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } 

            $ini->setDesc(CodeGen_Tools_Indent::linetrim($data));

            $err = $this->extension->addPhpini($ini);
            if (PEAR::isError($err)) {
                return $err;
            }
            
            // php.ini settings are stored in modul-global variables
            $global = new CodeGen_PECL_Element_Global;
            $err = $global->setName($ini->name);
            if (PEAR::isError($err)) {
                return $err;
            }
            $err = $global->setType($ini->c_type);
            if (PEAR::isError($err)) {
                return $err;
            }
            $err = $global->setValue($ini->value);
            if (PEAR::isError($err)) {
                return $err;
            }
            
            $err = $this->extension->addGlobal($global);

            return $err;
        }

        function tagend_globals($attr, $data) {
            return true;
        }
        





        function tagstart_extension_deps($attr)
        {
            if (isset($attr["language"])) {
                $err = $this->extension->setLanguage($attr["language"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            }

            if (isset($attr["platform"])) {
                $err = $this->extension->setPlatform($attr["platform"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            }
        }

        function tagstart_extension_deps_lib($attr)
        {
            if (!isset($attr["name"])) {
                return PEAR::raiseError("");
            }

            if (!isset($attr["platform"])) {
                $attr["platform"] = "all";
            }

            $lib = new CodeGen_PECL_Dependency_Lib($attr["name"], $attr["platform"]);

            if (isset($attr['path'])) {
                $lib->setPath($attr['path']);
            }

            if (isset($attr['function'])) {
                $lib->setFunction($attr['function']);
            }

            $this->extension->addLib($lib);

            return true;
        }

        function tagstart_extension_deps_header($attr)
        {
            // TODO check name
            $header = new CodeGen_PECL_Dependency_Header($attr["name"]);

            if (isset($attr['path'])) {
                $header->setPath($attr["path"]);
            }

            if (isset($attr['prepend'])) {
                $header->setPrepend($attr["prepend"]);
            }

            $this->extension->addHeader($header);
        }

        
        function tagstart_extension_deps_with($attr) 
        {
            $with = new CodeGen_PECL_Dependency_With;

            if (!isset($attr['name'])) {
                $attr["name"] = $this->extension->getName();
            }
            $err = $with->setName($attr["name"]);
            if (PEAR::isError($err)) {
                return $err;
            }

            if (isset($attr["testfile"])) {
                $with->setTestfile($attr["testfile"]);
            }

            if (isset($attr["defaults"])) {
                $with->setDefaults($attr["defaults"]);
            }

            $this->pushHelper($with);

            return true;
        }

        function tagstart_deps_with_header($attr) 
        {
            // TODO check name
            $header = new CodeGen_PECL_Dependency_Header($attr["name"]);

            if (isset($attr['path'])) {
                $header->setPath($attr["path"]);
            }

            if (isset($attr['prepend'])) {
                $header->setPrepend($attr["prepend"]);
            }

            $this->helper->addHeader($header);
        }

        function tagstart_deps_with_lib($attr) 
        {
            if (!isset($attr["platform"])) {
                $attr["platform"] = "all";
            }

            $lib = new CodeGen_PECL_Dependency_Lib($attr["name"], $attr["platform"]);

            if (isset($attr['path'])) {
                $lib->setPath($attr['path']);
            }

            if (isset($attr['function'])) {
                $lib->setFunction($attr['function']);
            }

            $this->helper->addLib($lib);
        }


        function tagend_extension_deps_with($attr, $data) {
            $this->helper->setSummary($data);

            $this->extension->addWith($this->helper);

            $this->popHelper();
        }


        function tagstart_extension_deps_file($attr) {
            if (!isset($attr['name'])) {
                return PEAR::raiseError("name attribut for file missing");
            }

            return $this->extension->addSourceFile($attr['name']);
        }


        function tagstart_extension_code($attr)
        {
            if (isset($attr["src"])) {
                if (!file_exists($attr["src"])) {
                    return PEAR::raiseError("Soruce file '$attr[src]' not found");                    
                }
                if (!is_readable($attr["src"])) {
                    return PEAR::raiseError("Cannot read source file '$attr[src]'");                    
                }
            }
        }

        function tagend_extension_code($attr, $data) {
            $role     = isset($attr["role"])     ? $attr["role"]     : "code";
            $position = isset($attr["position"]) ? $attr["position"] : "bottom";

            if (isset($attr["src"])) {
                return $this->extension->addCode($role, $position, CodeGen_Tools_Indent::linetrim(file_get_contents($attr["src"])));
            } else {
                return $this->extension->addCode($role, $position, CodeGen_Tools_Indent::linetrim($data));
            }
        }

        function tagend_extension_makefile($attr, $data) {
            return $this->extension->addMakeFragment(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_extension_deps_configm4($attr, $data) {
            return $this->extension->addConfigFragment(CodeGen_Tools_Indent::linetrim($data), 
                                                        isset($attr['position']) ? $attr['position'] : "top");
        }


        function tagstart_test($attr) {
            $test = new CodeGen_PECL_Element_Test();

            if (isset($attr["name"])) {
                $err = $test->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("name attribut for <test> missing");
            }

            $test->setSkipIf("!extension_loaded('".$this->extension->getName()."')");

            $this->pushHelper($test);
        }

        function tagend_test_title($attr, $data) {
            $this->helper->setTitle(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_test_skipif($attr, $data) {
            $this->helper->setSkipIf(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_test_get($attr, $data) {
            $this->helper->setGet(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_test_post($attr, $data) {
            $this->helper->setPost(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagstart_test_code($attr)
        {
            if (isset($attr["src"])) {
                if (!file_exists($attr["src"])) {
                    return PEAR::raiseError("Soruce file '$attr[src]' not found");                    
                }
                if (!is_readable($attr["src"])) {
                    return PEAR::raiseError("Cannot read source file '$attr[src]'");                    
                }
            }
        }

        function tagend_test_code($attr, $data) {
            if (isset($attr["src"])) {
                $this->helper->setCode(CodeGen_Tools_Indent::linetrim(file_get_contents($attr["src"])));
            } else {
                $this->helper->setCode(CodeGen_Tools_Indent::linetrim($data));
            }
        }

        function tagend_test_result($attr, $data) {
            $this->helper->setOutput(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagend_test($attr, $data) {
            $err = $this->extension->addTest($this->helper);
            $this->popHelper();
            return $err;
        }

        function tagend_tests($attr, $data) {
            return true;
        }






        function tagstart_class($attr)
        {
            $this->pushHelper(new CodeGen_PECL_Element_Class);

            if (isset($attr["name"])) {
                $err = $this->helper->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("name attribut for class missing");
            }

            if (PEAR::isError($err)) {
                return $err;
            }
            
            return true;
        }

        function tagend_class_summary($attr, $data) 
        {
            return $this->helper->setSummary(trim($data));
        }

        function tagend_class_description($attr, $data) 
        {
            return $this->helper->setDescription(CodeGen_Tools_Indent::linetrim($data));
        }

        function tagstart_class_code($attr)
        {
            if (isset($attr["src"])) {
                if (!file_exists($attr["src"])) {
                    return PEAR::raiseError("Soruce file '$attr[src]' not found");                    
                }
                if (!is_readable($attr["src"])) {
                    return PEAR::raiseError("Cannot read source file '$attr[src]'");                    
                }
            }
        }

        function tagend_class_code($attr, $data)
        {
            if (isset($attr["src"])) {
                return $this->helper->setCode(CodeGen_Tools_Indent::linetrim(file_get_contents($attr["src"])));
            } else {
                return $this->helper->setCode(CodeGen_Tools_Indent::linetrim($data));
            }
        }


        function tagstart_class_property($attr)
        {
            if (!isset($attr["name"])) {
                return PEAR::raiseError("Name attribute missing for property");
            }

            $prop = new CodeGen_PECL_Element_Property;

            $err = $prop->setName($attr["name"]);
            if (PEAR::isError($err)) {
                return $err;
            }

            if (isset($attr["type"])) {
                $err = $prop->setType($attr["type"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            }

            if (isset($attr["value"])) {
                $err = $prop->setValue($attr["value"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            }            

            return $this->helper->addProperty($prop);
        }


        function tagend_class($attr, $data) 
        {
            $err = $this->extension->addClass($this->helper);
            $this->popHelper();
            return true;
        }

        function tagstart_stream($attr)
        {
            $this->pushHelper(new CodeGen_PECL_Element_Stream);            

            if (isset($attr["name"])) {
                $err = $this->helper->setName($attr["name"]);
                if (PEAR::isError($err)) {
                    return $err;
                }
            } else {
                return PEAR::raiseError("'name' attribut for <stream> missing");
            }
        }

        function tagend_stream_open($attr, $data)
        {
            $this->helper->addCode("open", $data);
        }

        function tagend_stream_close($attr, $data)
        {
            $this->helper->addCode("close", $data);
        }

        function tagend_stream_stat($attr, $data)
        {
            $this->helper->addCode("stat", $data);
        }

        function tagend_stream_urlstat($attr, $data)
        {
            $this->helper->addCode("urlstat", $data);
        }

        function tagend_stream_diropen($attr, $data)
        {
            $this->helper->addCode("diropen", $data);
        }

        function tagend_stream_unlink($attr, $data)
        {
            $this->helper->addCode("unlink", $data);
        }

        function tagend_stream_rename($attr, $data)
        {
            $this->helper->addCode("rename", $data);
        }

        function tagend_stream_mkdir($attr, $data)
        {
            $this->helper->addCode("mkdir", $data);
        }

        function tagend_stream_rmdir($attr, $data)
        {
            $this->helper->addCode("rmdir", $data);
        }

        function tagend_stream_summary($attr, $data)
        {
            $this->helper->addCode("summary", $data);
        }

        function tagend_stream_write($attr, $data)
        {
            $this->helper->addCode("write", $data);
        }

        function tagend_stream_read($attr, $data)
        {
            $this->helper->addCode("read", $data);
        }

        function tagend_stream_flush($attr, $data)
        {
            $this->helper->addCode("flush", $data);
        }

        function tagend_stream_seek($attr, $data)
        {
            $this->helper->addCode("seek", $data);
        }

        function tagend_stream_cast($attr, $data)
        {
            $this->helper->addCode("cast", $data);
        }

        function tagend_stream_set($attr, $data)
        {
            $this->helper->addCode("set", $data);
        }

        function tagend_stream($attr, $data)
        {
            $this->extension->addStream($this->helper);
            $this->popHelper();
        }
    }


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
