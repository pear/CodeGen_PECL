<?php
/**
* Console script to generate PECL extensions from command line
*
* @author Hartmut Holzgraefe <hartmut@php.net>
* @version $Id: pecl-gen,v 1.4 2006/06/25 11:35:22 hholzgra Exp $
*/

require_once "CodeGen/PECL/Command.php";

// create extension object
$extension = new CodeGen_PECL_Extension;

$command = new CodeGen_PECL_Command($extension, "pecl-gen");

if ($command->options->have("experimental", "x")) {
    echo "the --experimental (-x) option has been deprecated

please use the 'version' attribute of the <extension> tag
to select version-specific features
";

    exit(3);
}

if ($command->options->have("function"))
{
    $command->singleFunction();
    exit(0);
}

// ext_skel compatibility?
if ($command->options->have("extname")) {
    $command->extSkelCompat();
    exit(0);
}

$parser = new CodeGen_PECL_ExtensionParser($extension);

$command->execute($parser);

