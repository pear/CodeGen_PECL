<?php
/**
 * Class describing a function within a PECL extension 
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

require_once "CodeGen/Tools/Indent.php";
require_once "CodeGen/Tools/Tokenizer.php";

/**
 * Class describing a function within a PECL extension 
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
    class CodeGen_PECL_Element_Function 
      extends CodeGen_PECL_Element 
    {
        /**
         * The function name
         *
         * @access private
         * @var     string
         */
        protected $name = "";

        /**
         * name setter
         *
         * @param string
         */
        function setName($name) 
        {
            if (!self::isName($name)) {
                return PEAR::raiseError("'$name' is not a valid function name");
            }
            
            switch ($this->role) {
            case "internal":
                if (!$this->isInternalName($name)) {
                    return PEAR::raiseError("'$name' is not a valid internal function name");
                }
                break;

            case "public":
                // keywords are not allowed as function names
                if (self::isKeyword($name)) {
                    return PEAR::raiseError("'$name' is a reserved word which is not valid for function names");
                }
                // you should not redefine standard PHP functions
                foreach(get_extension_funcs("standard") as $stdfunc) {
                    if (!strcasecmp($name, $stdfunc)) {
                        return PEAR::raiseError("'$name' is already the name of a PHP standard function");
                    }
                }
                break;
            }

            $this->name = $name;
            
            return true;
        }

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
         * A short description
         *
         * @access private
         * @var     string
         */
        protected $summary = "";

        /**
         * summary getter
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
         * @access private
         * @var     string
         */
        protected $description  = "";

        /**
         * description setter
         * 
         * @param string
         */
        function setDescription($text)
        {
            $this->description = $text;
            return true;
        }




        /**
         * Type of function: internal, public
         *
         * @access private
         * @var     string
         */
        protected $role  = "public";

        /**
         * role setter
         * 
         * @param string
         */
        function setRole($role)
        {
            switch($role) {
            case "internal":
                if (!$this->isInternalName($this->name)) {
                    return PEAR::raiseError("'{$this->name}' is not a valid internal function name");
                }
                break;

            case "public":
                break;

            case "private":
                return PEAR::raiseError("'private' functions are no longer supported, use global <code> sections instead");
                break;

            default:
                return PEAR::raiseError("'$role' is not a valid function role"); 
            }
            
            $this->role = $role;
            
            return true;
        }

        function getRole() 
        {
            return $this->role;
        }


        /**
         * Function has variable arguments "..."
         * 
         * @access private
         * @var    bool
         */
        protected $varargs = false;


        /**
         * Function prototype
         *
         * @access private
         * @var     string
         */
        protected $proto = "void unknown(void)";

        /**
         * Function returntype (parsed from proto)
         *
         * @access private
         * @var     string
         */
        protected $returns = "void";

        /**
         * Function parameters (parsed from proto)
         *
         * @access private
         * @var     array
         */
        protected $params = array();

        /**
         * Number of optional parameters (parsed from proto)
         *
         * @access private
         * @var     int
         */
        protected $optional = 0;

        /**
         * Set parameter and return value information from PHP style prototype
         *
         * @access public
         * @param  string  PHP style prototype 
         * @return bool    Success status
         */
        function setProto($proto, $extension) {
            $this->proto = $proto;

            if ($extension->haveVersion("0.9.0rc1")) {
                $stat = $this->newSetProto($proto, $extension);
            } else {
                $stat = $this->oldSetProto($proto);
            }

            return $stat;
        }

        /**
         * Set parameter and return value information from PHP style prototype
         *
         * @param  string  PHP style prototype 
         * @return bool    Success status
         */
        private function newSetProto($proto, $extension) {
            $tokenGroups = array();
            $optionals = 0;
            $firstOptional = 0;
            $squareBrackets = 0;
            
            $scanner = new CodeGen_Tools_Tokenizer($proto);

            // group #0 -> return type and function name
            $group = array();       
            while ($scanner->nextToken()) {
                if ($scanner->type === 'char' && $scanner->token === '(') {
                    break;
                }
                $group[] = array($scanner->type, $scanner->token);
            }
            $tokenGroups[] = $group;
            
            // all following groups are parameters delimited by ','
            $group = array();       
            while ($scanner->nextToken()) {
                if ($scanner->type === 'char') { 
                    if ($scanner->token === '[') {
                        $squareBrackets++;
                        if(!$firstOptional) {
                            if (count($group)) {
                                $tokenGroups[] = $group;
                                $group = array();
                            }
                            $firstOptional = count($tokenGroups);
                        }
                        continue;
                    } 
                    
                    if ($scanner->token === ',') {
                        if (count($group)) {
                            $tokenGroups[] = $group;
                            $group = array();
                        }
                        continue;
                    } 
                    

                    if ($scanner->token === ']') {
                        $squareBrackets--;
                        continue;
                    }

                    if ($scanner->token === ')') {
                        $tokenGroups[] = $group;
                        $group = array();
                        break;
                    } 
                }

                $group[] = array($scanner->type, $scanner->token);
            }

            if ($squareBrackets > 0) {
                return PEAR::raiseError("missing closing ']'");
            }
            if ($squareBrackets < 0) {
                return PEAR::raiseError("to many closing ']'");
            }

            
            //
            // get return type and function name
            //
            $tokens = array_shift($tokenGroups);
            
            // first the function name
            list($type, $token) = array_pop($tokens);
            if ($type !== 'char' || $token !== '@') { // @ is allowed as function name placeholder
                if ($type !== 'name' || !self::isName($token)) {
                    return PEAR::raiseError("'$token' is not a valid function name");
                }
                if (self::isKeyword($token)) {
                    return PEAR::raiseError("keyword '$token' can't be used as function name");
                }
            }

            $functionName = $token;
            // function name is not really used, taken from <function> tag instead
            // TODO: add WARNING for mismatch?
            if (! empty($this->name)) {
                $functionName = $this->name;
            } else {
                $this->name = $functionName;
            }
            
            // then the return type
            list($type, $token) = array_shift($tokens);
            if ($type !== 'name' || !self::isType($token)) {
                return PEAR::raiseError("'$token' is not a valid return type");
            }
            $returnType = $token;
            
            // some types may carry a name
            if ($token === "object" || $token == "resource") {
                if ($tokens[0][0] === 'name') {
                    list($type, $token) = array_shift($tokens);
                    $returnSubtype = $token;
                }
            }
            
            // return by reference?
            if (count($tokens) && $tokens[0][0] === 'char' && $tokens[0][1] === '&') {
                list($type, $token) = array_shift($tokens);
                $returnByRef = true;
            }
            
            // any tokens left?
            if (count($tokens)) {
                return PEAR::raiseError("extra token '".$tokens[0][1]."' in function prototype");
            }
            
            // 
            // now the parameters one by one
            // 
            $params = array();
            $vararg = false;
            while ($tokens = array_shift($tokenGroups)) {    
                if ($vararg) {
                    return PEAR::raiseError("no further parameters are allowed after '...'");
                }
                
                $param = array();
                
                // check for default value assignment
                $equals = false;
                $default = array();
                foreach ($tokens as $key => $value) {
                    if ($value[0] === 'char' && $value[1] === '=') {
                        $equals = $key; 
                        break;
                    }
                }
                if ($equals !== false) {
                    while (count($tokens) > $equals + 1) {
                        array_unshift($default, array_pop($tokens));
                    }
                    array_pop($tokens);
                }
                
                // get parameter name
                list($type, $token) = array_pop($tokens);
                if ($type !== 'name') {
                    return PEAR::raiseError("'$token' is not a valid parameter name (1)");
                }
                $param['name'] = $token;
                
                // then the parameter type
                list($type, $token) = array_shift($tokens);
                if ($type !== 'name' || !self::isType($token)) {
                    switch ($param["name"]) {
                    case "void":
                        if (!empty($params)) {
                            return PEAR::raiseError("only the first (and only) paramter can be of type void");
                        }
                        $param["type"] = $param["name"];
                        $param["name"] = "";
                        break;
                    case "...":
                        if ($optionals) { // TODO not set yet ???
                            return PEAR::raiseError("'...' varargs can't be combined with optional args");
                        }
                        $vararg = true;
                        $param["type"] = $param["name"];
                        $param["name"] = "";
                        break;
                    default:
                        return PEAR::raiseError("'$token' is not a valid type for parameter '".$param['name']."'");
                    }
                } else {
                    $param['type'] = $token;
                }
                
                if (!empty($param["name"]) && !self::isName($param["name"])) {
                    return PEAR::raiseError("'{$param[name]}' is not a valid parameter name (2)");
                }

                // some types may carry a name
                if ($token === "object" || $token === "resource") {
                    if ($tokens[0][0] === 'name') {
                        list($type, $token) = array_shift($tokens);
                        $param['subtype'] = $token;
                    }
                }
                
                // return by reference?
                if (count($tokens) && $tokens[0][0] === 'char' && $tokens[0][1] === '&') {
                    list($type, $token) = array_shift($tokens);
                    $param['byRef'] = true;
                }
                
                // any tokens left?
                if (count($tokens)) {
                    return PEAR::raiseError("extra token '".$tokens[0][1]."' in specification of parameter '$param[name]'");
                }
                
                // do we have a default value?
                switch (count($default)) {
                case 0:
                    break;
                case 1:
                    list($type, $token) = array_shift($default);
                    switch ($type) {
                    case 'string': 
                    case 'char':
                        $param['default'] = '"'.str_replace('"', '\"', $token).'"';
                        break;
                    case 'numeric': 
                        $param['default']  = $token;
                        break;
                    case 'name':
                        switch (strtolower($token)) {
                        // first check for 'known' PHP constants
                        case 'true':
                        case 'false':
                        case 'null':
                        case 'array()':
                            $param['default']  = $token; 
                            break;                          
                        default:
                            // now see if this is a constand defined by this extension
                            $constants = $extension->getConstants();
                            if (isset($constants[$token])) {
                                $param["default"] = $constants[$token]->value;
                            } else {
                                return PEAR::raiseError("invalid default value '$token' specification for parameter '$param[name]' ($type)");
                            }
                        }
                        break;
                    default:
                        return PEAR::raiseError("invalid default value '$token' specification for parameter '$param[name]' ($type)");
                    }
                    break;
                default:
                    return PEAR::raiseError("invalid default value '$token' specification for parameter '$param[name]' ($type)");
                }
                
                if ($firstOptional && count($params)+1 >= $firstOptional) {
                    $param['optional'] = true;
                    $optionals++;
                }
                
                $params[] = $param;
            }

            $this->returns  = $returnType;

            if (isset($returnSubtype)) {
                $this->returns .= " $returnSubtype";
            }

            if (isset($returnByRef)) {
                $this->returns .= " &";
            }

            $this->params   = $params;
            $this->optional = $optionals;
            $this->vararg   = $vararg;

            return true;
        }


        function oldSetProto($proto) 
        {
            // This is the classic setProto() version, we keep it for now
            // as the new version is not 100% backwards compatible yet
            
            // 'tokenize' it
            $tokens = array();

            // we collect valid C names as Strings, any other character for itself, blanks are skipped
            // TODO: this does no longer work if we ever add default values ...
            $len = strlen($proto);
            $name = "";
            for ($n = 0; $n < $len; $n++) {
                $char = $proto{$n};
                if (ctype_alpha($char) || $char == '_' || ($n && ctype_digit($char))) {
                    $name .= $char;
                } else if ($char == '.' && $proto{$n+1} == '.' && $proto{$n+2} == '.') {
                    $name = "...";
                    $n += 2;
                } else {
                    if ($name) $tokens[] = $name;
                    $name = "";
                    if (trim($char)) {
                        $tokens[] = $char;
                    }
                }
            }

            if ($name) {
                $tokens[] = $name;
            }

            $n = 0;
            $opts = 0;
            $numopts = 0;

            $params = array();

            $returnType = ($this->isType($tokens[$n])) ? $tokens[$n++] : "void";

            $functionName = $tokens[$n++];

            if ($returnType === "resource" && $tokens[$n] !== "(") {
                $returnSubtype = $functionName;
                $functionName = $tokens[$n++];
            }

            // function name is not really used, taken from <function> tag instead
            // TODO: add WARNING for mismatch?
            if (! empty($this->name)) {
                $functionName = $this->name;
            } else {
                $this->name = $functionName;
            }

            if ($tokens[$n++] != '(') return PEAR::raiseError("'(' expected instead of '$tokens[$n]'");
            
            if ($tokens[$n] == ')') { 
                /* done */
            } else if ($tokens[$n] == '...') {
                $params[0]['type'] = "...";
                $n++;
            } else {
                for ($param = 0; $tokens[$n]; $n++, $param++) {
                    if ($tokens[$n] == '[') {
                        $params[$param]['optional'] = true;
                        $opts++;
                        $n++;
                        if ($param > 0) { 
                            if ($tokens[$n] != ',') {
                                return PEAR::raiseError("',' expected after '[' instead of '$token[$n]'");
                            }
                            $n++;
                        }
                    }

                    if (!$this->isType($tokens[$n])) {
                        return PEAR::raiseError("type name expected instead of '$tokens[$n]'");
                    }

                    $params[$param]['type'] = $tokens[$n];
                    $n++;

                    if ($tokens[$n] == "&") {
                        $params[$param]['byRef'] = true;
                        $n++;
                    }

                    if ($this->isName($tokens[$n])) {
                        $params[$param]['name']=$tokens[$n];
                        $n++;
                    }

                    if ($tokens[$n] == "&") {
                        $params[$param]['byRef'] = true;
                        $n++;
                    }

                    if ($params[$param]['type'] === "resource" && $this->isName($tokens[$n])) {
                        $params[$param]['subtype'] = $params[$param]['name'];
                        $params[$param]['name'] = $tokens[$n];
                        $n++;
                    }

                    if ($tokens[$n] == '[') {
                        $n--;
                        continue;
                    }

                    if ($tokens[$n] == ',') continue;
                    if ($tokens[$n] == ']') break;
                    if ($tokens[$n] == ')') break;            

                }

                $numopts = $opts;
                while ($tokens[$n] == ']') {
                    $n++;
                    $opts--;
                }
                if ($opts != 0) {
                    return PEAR::raiseError("'[' / ']' count mismatch");
                }
            } 

            if ($tokens[$n] != ')') {
                return PEAR::raiseError("')' expected instead of '$tokens[$n]'");
            }

            $this->returns  = $returnType;

            if (isset($returnSubtype)) {
                $this->returns .= " $returnSubtype";
            }

            $this->params   = $params;
            $this->optional = $numopts;

            return true;
        }


        /**
         * Code snippet
         *
         * @access private
         * @var    string
         */
        protected $code = "";

        /**
         * Source file of code snippet
         *
         * @access private
         * @var    string
         */
        protected $codeFile = "";

        /**
         * Source line of code snippet
         *
         * @access private
         * @var    int
         */
        protected $codeLine = 0;

        /**
         * Code setter
         *
         * @param string code snippet
         * @param int    source line
         * @param int    source filename
         */
        function setCode($code, $line = 0, $file = "")
        {
            $this->code     = $code;
            $this->codeFile = $file;
            $this->codeLine = $line;
            return true;
        }

        /**
         * Code getter
         *
         * @return string
         */
        function getCode()
        {
            return $this->code;
        }





        /**
         * test code snippet
         *
         * @var string
         */
        protected $testCode = "echo 'OK'; // no test case for this function yet";

        /**
         * testCode setter
         *
         * @param  string code snippet
         */
        function setTestCode($code)
        {
            $this->testCode = $code;
        }

        /**
         * testCode getter
         *
         * @return string
         */
        function getTestCode()
        {
            return $this->testCode;
        }


        /**
         * expected test result string
         *
         * @var array
         */
        protected $testResult = array();
 
        /**
         * testResult setter
         *
         * @param  string result text
         * @param  string test output comparison mode
         */
        function setTestResult($text, $mode = "plain")
        {
            $this->testResult = array("result" => $text, "mode" => $mode);
        }

        /**
         * testResult getter
         *
         * @return array
         */
        function getTestResult()
        {
            return $this->testResult;
        }


        /**
         * test code description
         *
         * @var string
         */
        protected $testDescription = "";

        /**
         * testDescritpion setter
         *
         * @param  string text
         */
        function setTestDescription($text)
        {
            $this->testDescription = $text;
        }

        /**
         * testDescription getter
         *
         * @return string
         */
        function getTestDescription()
        {
            return $this->testDescription;
        }


        /**
         * test additional skipif condition
         *
         * @var string
         */
        protected $testSkipIf = "";

        /**
         * testSkipIf setter
         *
         * @param  string code snippet
         */
        function setTestSkipIf($code)
        {
            $this->testSkipIf = $code;
        }

        /**
         * testSkipIf getter
         *
         * @return string
         */
        function getTestSkipIf()
        {
            return $this->testSkipIf;
        }



        /**
         * test additional ini condition
         *
         * @var string
         */
        protected $testIni = "";

        /**
         * testIni setter
         *
         * @param  string code snippet
         */
        function setTestIni($code)
        {
            $this->testIni = $code;
        }

        /**
         * testIni getter
         *
         * @return string
         */
        function getTestIni()
        {
            return $this->testIni;
        }









        /**
         * Check whether a function name is already used internally
         *
         * @access public
         * @param  string  function name
         * @return bool    true if function name is already used internally
         */
        function isInternalName($name)
        {
            switch ($name) {
            case "MINIT":
            case "MSHUTDOWN":
            case "RINIT":
            case "RSHUTDOWN":
            case "MINFO":
                return true;
            }

            return false;
        }


        /** 
         * Helper for cCode
         *
         * @param  string  Parameter spec. array
         * @param  string  default value for type
         * @return string  default value
         */
        function defaultval($param, $default) {
            if (isset($param["default"])) {
                return $param["default"];
            }

            return $default;
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
    if (zend_parse_parameters($argc TSRMLS_CC, \"$argString\", ".join(", ",$argPointers).") == FAILURE) { 
        return;
    }
";
        }


        /**
         * Generate local variable declarations
         *
         * @return string C code snippet
         */
        function localVariables($extension) 
        {
            $code = "";

            $returns = explode(" ", $this->returns);

            // for functions returning a named resource we create payload pointer variable
            $resources = $extension->getResources();
            if ($returns[0] === "resource") {
                if (isset($returns[1]) && isset($resources[$returns[1]])) {
                    $resource = $resources[$returns[1]];
                    $payload  = $resource->getPayload();
                    if ($resource->getAlloc()) {
                        $code .= "    $payload * return_res = ($payload *)ecalloc(1, sizeof($payload));\n";
                    } else {
                        $code .= "    $payload * return_res;\n";
                    }
                } else {
                    $code .= "    void * return_res;\n";
                }
            }

            return $code;
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
            $code = "";

            $returns = explode(" ", $this->returns);

            switch ($this->role) {
            case "public":
                // function prototype comment
                $code .= "\n/* {{{ proto {$this->proto}\n  ";
                if (!empty($this->summary)) {
                    $code .= $this->summary;
                }
                $code .= " */\n";

                // function declaration
                $code .= $this->cProto()."\n";
                $code .= "{\n";
                
                $code .= $this->localVariables($extension);

                // now we create local variables for all parameters
                // at the same time we put together the parameter parsing string
                if (is_array($this->params) && count($this->params)) {
                    $argString = "";
                    $argPointers = array();
                    $optional = false;
                    $resFetch = "";
                    $zvalType = false;
                    foreach ($this->params as $param) {
                        if ($param["type"] === "void"  || $param["type"] === "...") {
                            continue;
                        }

                        $name = $param['name']; 
                        
                        if ($param['type'] == "resource" && $extension->haveVersion("1.0.0alpha")) {
                            $argPointers[] = "&{$name}_res";
                        } else {
                            $argPointers[] = "&$name";
                        }

                        if (isset($param['optional']) && !$optional) {
                            $optional = true;
                            $argString .= "|";
                        }

                        switch ($param['type']) {
                        case "bool":
                            $argString .= "b";
                            $default = $this->defaultval($param, "0");
                            $code .= "    zend_bool $name = $default;\n";
                            break;

                        case "int":
                            $argString .= "l";
                            $default = $this->defaultval($param, "0");
                            $code .= "    long $name = $default;\n";
                            break;

                        case "float":
                            $argString .= "d";
                            $default = $this->defaultval($param, "0.0");
                            $code .= "    double $name = $default;\n";
                            break;

                        case "string":
                            $argString .= "s";
                            $default = $this->defaultval($param, "NULL");
                            $code .= "    const char * $name = $default;\n";
                            $code .= sprintf("    int {$name}_len = %d;\n", $default==="NULL" ? 0 : strlen($default) - 2);
                            $argPointers[] = "&{$name}_len";
                            break;

                        case "array":
                            $zvalType = true;
                            $argString .= "a";
                            $code .= "    zval * $name = NULL;\n";
                            break;

                        case "object": 
                            $zvalType = true;
                            $code .= "    zval * $name = NULL;\n";
                            if (isset($param['subtype'])) {
                                $argString .= "O";
                                $argPointers[] = "$param[subtype]_ce_ptr";
                            } else {
                                $argString .= "o";
                            }
                            break;

                        case "resource":
                            $zvalType = true;
                            $argString .= "r";

                            if ($extension->haveVersion("1.0.0alpha")) {
                                $resVar     = $name."_res";
                                $payloadVar = $name;
                                $idVar      = $name."_resid";
                            } else {
                                $resVar     = $name;
                                $payloadVar = "res_".$name;
                                $idVar      = $name."_id";
                            }

                            $code .= "    zval * $resVar = NULL;\n";
                            $code .= "    int $idVar = -1;\n";

                            $resources = $extension->getResources();
                            if (isset($param['subtype']) && isset($resources[$param['subtype']])) {
                                $resource = $resources[$param['subtype']];
                                if ($extension->haveVersion("1.0.0dev")) {
                                    $varname = $name;
                                } else {
                                    $varname = "res_{$name}";
                                }
                                $code .= "    ".$resource->getPayload()." * $payloadVar;\n";
                                
                                $resFetch .= "    ZEND_FETCH_RESOURCE($payloadVar, ".$resource->getPayload()." *, &$resVar, $idVar, \"$param[subtype]\", le_$param[subtype]);\n";
                            } else {
                                $resFetch .="    ZEND_FETCH_RESOURCE(???, ???, $resVar, $idVar, \"???\", ???_rsrc_id);\n";
                            }
                            break;

                        case "stream":
                            $zvalType = true;
                            $argString .= "r";
                            $code .= "    zval * {$name}_zval = NULL; \n";
                            $code .= "    php_stream * $name = NULL:\n";
                            $resFetch.= "    php_stream_from_zval($name, &_z$name);\n"; 
                            break;

                        case "callback": 
                            $resFetch.= "    if (!zend_is_callable({$name}, 0 NULL) {\n";
                            $resFetch.= "      php_error(E_WARNING, \"Invalid comparison function.\");\n";
                            $resFetch.= "      return;";
                            $resFetch.= "    }\n";
                            /* fallthru */
                        case "mixed":
                            $zvalType = true;
                            $argString .= "z";
                            $code .= "    zval * {$name} = NULL;\n";
                            break;                          
                        }

                        if (empty($param['byRef']) && $param['type'] != 'object') {
                            $argString .= "/";
                        } else if (!$zvalType) {
                            // TODO: pass by ref for 'simple' types requires further thinking
                        }
                    }
                } 

                // now we do the actual parameter parsing
                if (empty($argString)) {
                    if (!empty($this->params) && $this->params[0]['type'] == "...") {
                        $code .= "/* custom parsing code required */\n\n";
                    } else {
                        $code .= "    if (ZEND_NUM_ARGS()>0)  {\n        WRONG_PARAM_COUNT;\n    }\n\n";
                    }
                } else {
                    if ($this->varargs) {
                        $argc = sprint("MIN(ZEND_NUM_ARGS(),%d)", count($this->params)-1);
                    } else {
                        $argc = "ZEND_NUM_ARGS()";
                    }


                    $code .= $this->parseParameterHook($argc, $argString, $argPointers);
                    
                    if ($this->varargs) {
                        // TODO can't do a zend_get_parameters_array with offset yet
                    }
                    

                    if ($resFetch) {
                        $code.="$resFetch\n\n";
                    }
                }
                    
                // for functions returning an array we initialize return_value
                if ($returns[0] === "array") {
                    $code.="array_init(return_value);\n\n";
                }

                if ($this->code) {
                    if ($extension->getLinespecs()) {
                        // generate #line preprocessor directive
                        if ($this->codeLine) {
                            $linedef = "#line {$this->codeLine} ";
                            if ($this->codeFile) {
                                $linedef.= '"'.$this->codeFile.'"';
                            }
                        } else {
                            $linedef = "";
                        }
                    }

                    // if function code is specified so we add it here
                    if ($extension->getLanguage() == "c") {
                        // in C variable declarations have to be at the very beginning
                        // of a block, so we have to add {...} around the snippet
                        $code .= "    do {\n";
                        if (isset($linedef)) {
                            $code .= "$linedef\n";
                        }
                        $code .= CodeGen_Tools_Indent::indent(8, $this->code);
                        $code .= "    } while(0);\n"; 
                    } else {
                        // in C++ variable may be declared at any time
                        if (isset($linedef)) {
                            $code .= "$linedef\n";
                        }
                        $code .= CodeGen_Tools_Indent::indent(4, $this->code)."\n";
                    }

                    // when a function returns a named resource we know what to do
                    if ($returns[0] == "resource" && isset($returns[1])) {
                        $code .= "    ZEND_REGISTER_RESOURCE(return_value, return_res, le_$returns[1]);\n";
                    }
                } else {
                    // no code snippet was given so we produce a suggestion for the return statement
                    $code .= "    php_error(E_WARNING, \"{$this->name}: not yet implemented\"); RETURN_FALSE;\n\n";
                    
                    switch ($returns[0]) {
                    case "void":
                        break;
                        
                    case "bool":
                        $code .= "    RETURN_FALSE;\n"; 
                        break;
                        
                    case "int":
                        $code .= "    RETURN_LONG(0);\n"; 
                        break;
                        
                    case "float":
                        $code .= "    RETURN_DOUBLE(0.0);\n";
                        break;
                        
                    case "string":
                        $code .= "    RETURN_STRINGL(\"\", 0, 1);\n";
                        break;
                        
                    case "array":
                        $code .= "    array_init(return_value);\n";
                        break;
                        
                    case "object": 
                        $code .= "    object_init(return_value)\n";
                        break;
                        
                    case "resource":
                        if (isset($returns[1])) {
                            $code .= "    ZEND_REGISTER_RESOURCE(return_value, return_res, le_$returns[1]);\n";
                        } else {
                            $code .= "    /* RETURN_RESOURCE(...); */\n";
                        }
                        break;
                        
                    case "stream":
                        $code .= "    /* php_stream_to_zval(stream, return_value); */\n";
                        break;
                        
                    case "mixed":
                        $code .= "    /* RETURN_...(...); */\n";              
                        break;
                        
                    default: 
                        $code .= "    /* UNKNOWN RETURN TYPE '$this->returns' */\n";
                        break;
                    }
                }
                
                $code .= "}\n/* }}} {$this->name} */\n\n";
                break;
                
            case "internal":
                if (!empty($this->code)) {
                    $code .= "    {\n";
                    $code .= CodeGen_Tools_Indent::indent(8, $this->code."\n");
                    $code .= "    }\n";
                }
                break;
            }
            return $code;
        }
        


        /**
         * Create DocBook reference entry for the function
         *
         * @access public
         * @param  string  base (currently not used)
         * @return string  DocBook XML code
         */
        function docEntry($base) 
        {
            $xml = 
'<?xml version="1.0" encoding="iso-8859-1"?>
<!-- '.'$'.'Revision: 1.0 $ -->
  <refentry id="function.' . strtolower(str_replace("_", "-", $this->name)) . '">
   <refnamediv>
    <refname>' . $this->name . '</refname>
    <refpurpose>' . $this->summary . '</refpurpose>
   </refnamediv>
   <refsect1>
    <title>Description</title>
     <methodsynopsis>
';
            
            $xml .= "      <type>{$this->returns}</type><methodname>{$this->name}</methodname>\n";
            if (empty($this->params) || $this->params[0]["type"] === "void") {
                $xml .= "      <void/>\n";
            } else if ($this->params[0]["type"] === "...") {
                $xml .= "      <methodparam choice='opt' rep='repeat'><type>mixed</type><parameter>...</parameter></methodparam>\n";
            } else {
                foreach ($this->params as $key => $param) {
                    if (isset($param['optional'])) {
                        $xml .= "      <methodparam choice='opt'>";
                    } else {
                        $xml .= "      <methodparam>";
                    }
                    $xml .= "<type>$param[type]</type><parameter>";
                    if (isset($param['byRef'])) {
                        $xml .= "&amp;"; 
                    }
                    $xml .= "$param[name]</parameter>";
                    $xml .= "</methodparam>\n";
                }
            }


            $description = $this->description;

            if (!strstr($this->description,"<para>")) {
                $description = "     <para>\n$description     </para>\n";
            }

            $xml .= 
'     </methodsynopsis>
'.$description.'
   </refsect1>
  </refentry>
';
            $xml .= $this->docEditorSettings(4);
 
            return $xml;
        }

        

        /**
         * write test case for this function
         *
         * @access public
         * @param  class Extension  extension the function is part of
         */
        function writeTest(CodeGen_PECL_Extension $extension) 
        {
            $test = $this->createTest($extension);

            if ($test instanceof CodeGen_PECL_Element_Test) {
                $test->writeTest($extension);
            }
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
            if (!$this->testCode) {
                return false;
            }

            $test = new CodeGen_PECL_Element_Test;
            
            $test->setName($this->name);
            $test->setTitle($this->name."() function");
            
            if ($this->testDescription) {
                $test->setDescription($this->testDescription);
            }
            
            if ($this->testIni) {
                $test->addIni($this->testIni);
            }
            
            $test->setSkipIf("!extension_loaded('".$extension->getName()."')");
            if ($this->testSkipIf) {
                $test->addSkipIf($this->testSkipIf);
            }
            
            $test->setCode($this->testCode);
            
            if (!empty($this->testResult)) {
                $test->setOutput($this->testResult['result']);
                if (isset($this->testResult['mode'])) {
                    $test->setMode($this->testResult['mode']);
                }
            }
            
            return $test;
        }


        /** 
         * C function signature
         *
         * @return  string  C snippet
         */
        function cProto()
        {
            return "PHP_FUNCTION({$this->name})";
        }

        /**
         * Create C header entry for userlevel function
         *
         * @access public
         * @param  class Extension  extension the function is part of
         * @return string           C header code snippet
         */
        function hCode($extension) 
        {
            return $this->cProto().";\n";
        }


    }

?>
