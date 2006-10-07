%name CodeGen_PECL_Tools_ProtoParser_
%declare_class {class CodeGen_PECL_Tools_ProtoParser}
%include_class {
  protected $name       = "";
  protected $varargs    = false;
  protected $returns    = array("type" => "void");
  protected $params     = array();
  protected $optional   = 0;
  protected $hasRefArgs = false;

  function dump()
  {
    echo "Function: ".$this->name."\n";
    echo "Returns:  "; var_dump($this->returns);
	echo "Params:   "; var_dump($this->params);
  }
}

proto_line ::= proto.
proto_line ::= proto SEMICOLON.

proto ::= typespec(A) NAME(B) PAR_OPEN param_spec PAR_CLOSE. {
  $this->returns = A;
  $this->name    = B;
}

typespec(A) ::= VOID.                  { A = array("type" => "void"); }
typespec(A) ::= typename(B).           { A = B; }
typespec(A) ::= typename(B) AMPERSAND. { A = B; A["byref"] = true; }

typename(A) ::= BOOL.                  { A = array("type" => "bool"); }
typename(A) ::= INT.                   { A = array("type" => "int"); }
typename(A) ::= FLOAT.                 { A = array("type" => "float"); }
typename(A) ::= STRING.                { A = array("type" => "string"); }
typename(A) ::= ARRAY_.                { A = array("type" => "array"); }
typename(A) ::= CLASS_ NAME(B).        { A = array("type" => "class ",    "subtype" => B); }
typename(A) ::= RESOURCE NAME.         { A = array("type" => "resource ", "subtype" => B); }
typename(A) ::= MIXED.                 { A = array("type" => "mixed"); }
typename(A) ::= CALLBACK.              { A = array("type" => "callback"); }
typename(A) ::= STREAM.                { A = array("type" => "stream"); }

param_spec ::= param_list.
param_spec ::= ELLIPSE.                { $this->varargs = true; }
param_spec ::= VOID.

param_list ::= param_list COMMA ELLIPSE. { $this->varargs = true; }
param_list ::= param_list COMMA param.
param_list ::= param.
param_list ::= .

param ::= typename(A) NAME(B). {
  $param = A;
  $param["name"] = B;
  $this->params[] = $param;
}
param ::= typename(A) NAME(B) EQ default(C). {
  $param = A;
  $param["name"] = B;
  $param["default"] = C;
  $this->params[] = $param;
}

default(A) ::= TRUE_.  { A = "true"; }
default(A) ::= FALSE_. { A = "false"; }
default(A) ::= NULL_.  { A = "null"; }
default(A) ::= NUMVAL(B). { A = B; }
default(A) ::= STRVAL(B). { A = '"'.B.'"'; }
default(A) ::= ARRAY_ PAR_OPEN PAR_CLOSE. { A = "array()"; }



