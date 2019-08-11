<?php

/* Load simpleSAMLphp, configuration and metadata */

$config = \SimpleSAML\Configuration::getInstance();
$session = \SimpleSAML\Session::getSessionFromRequest();

$template = new \SimpleSAML\XHTML\Template($config, 'metaedit:xmlimport.twig');
$template->send();
