<?php

/* Load simpleSAMLphp, configuration and metadata */
$config = \SimpleSAML\Configuration::getInstance();
$metaconfig = \SimpleSAML\Configuration::getConfig('module_metaedit.php');

$mdh = new \SimpleSAML\Metadata\MetaDataStorageHandlerSerialize($metaconfig->getValue('metahandlerConfig', null));

$authsource = $metaconfig->getValue('auth', 'login-admin');
$useridattr = $metaconfig->getValue('useridattr', 'eduPersonPrincipalName');

$as = new \SimpleSAML\Auth\Simple($authsource);
$as->requireAuth();
$attributes = $as->getAttributes();

// Check if userid exists
if (!isset($attributes[$useridattr])) {
    throw new Exception('User ID is missing');
}
$userid = $attributes[$useridattr][0];

/**
 * @param array $metadata
 * @param string $userid
 */
function requireOwnership($metadata, $userid)
{
    if (!isset($metadata['owner'])) {
        throw new \Exception('Metadata has no owner. Which means no one is granted access, not even you.');
    }
    if ($metadata['owner'] !== $userid) {
        throw new \Exception(
            'Metadata has an owner that is not equal to your userid, hence you are not granted access.'
        );
    }
}


if (isset($_REQUEST['delete'])) {
    $premetadata = $mdh->getMetadata($_REQUEST['delete'], 'saml20-sp-remote');
    requireOwnership($premetadata, $userid);
    $mdh->deleteMetadata($_REQUEST['delete'], 'saml20-sp-remote');
}


$list = $mdh->getMetadataSet('saml20-sp-remote');

$slist = array('mine' => array(), 'others' => array());
foreach ($list as $listitem) {
    if (array_key_exists('owner', $listitem)) {
        if ($listitem['owner'] === $userid) {
            $slist['mine'][] = $listitem;
            continue;
        }
    }
    $slist['others'][] = $listitem;
}


$template = new \SimpleSAML\XHTML\Template($config, 'metaedit:metalist.twig');
$template->data['metadata'] = $slist;
$template->data['userid'] = $userid;
$template->send();
