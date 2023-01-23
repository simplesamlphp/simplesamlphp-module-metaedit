<?php

use Exception;
use SimpleSAML\Configuration;
use SimpleSAML\Utils;

/* Load simpleSAMLphp, configuration and metadata */
$config = Configuration::getInstance();
$metaconfig = Configuration::getConfig('module_metaedit.php');

$mdh = new \SimpleSAML\Metadata\MetaDataStorageHandlerSerialize($metaconfig->getOptionalValue('metahandlerConfig', null));

$authsource = $metaconfig->getOptionalValue('auth', 'login-admin');
$useridattr = $metaconfig->getOptionalValue('useridattr', 'eduPersonPrincipalName');

$as = new \SimpleSAML\Auth\Simple($authsource);
$as->requireAuth();
$attributes = $as->getAttributes();
// Check if userid exists
if (!isset($attributes[$useridattr])) {
    throw new \Exception('User ID is missing');
}
$userid = $attributes[$useridattr][0];

/**
 * @param array|null $metadata
 * @param string $userid
 */
function requireOwnership(?array $metadata = [], string $userid): void
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


if (array_key_exists('entityid', $_REQUEST)) {
    $metadata = $mdh->getMetadata($_REQUEST['entityid'], 'saml20-sp-remote');
    requireOwnership($metadata, $userid);
} elseif (array_key_exists('xmlmetadata', $_REQUEST)) {
    $xmldata = $_REQUEST['xmlmetadata'];
    $xmlUtils = new Utils\XML();
    $xmlUtils->checkSAMLMessage($xmldata, 'saml-meta');
    $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsString($xmldata);
    $entity = array_pop($entities);
    $metadata =  $entity->getMetadata20SP();

    /* Trim metadata endpoint arrays. */
    $metadata['AssertionConsumerService'] = [
        Utils\Config\Metadata::getDefaultEndpoint(
            $metadata['AssertionConsumerService'],
            [\SAML2\Constants::BINDING_HTTP_POST]
        )
    ];
    $metadata['SingleLogoutService'] = [
        Utils\Config\Metadata::getDefaultEndpoint(
            $metadata['SingleLogoutService'],
            [\SAML2\Constants::BINDING_HTTP_REDIRECT]
        )
    ];
} else {
    $metadata = [
        'owner' => $userid,
    ];
}


$editor = new \SimpleSAML\Module\metaedit\MetaEditor();


if (isset($_POST['submit'])) {
    $editor->checkForm($_POST);
    $metadata = $editor->formToMeta($_POST, array(), array('owner' => $userid));

    if (isset($_REQUEST['was-entityid']) && $_REQUEST['was-entityid'] !== $metadata['entityid']) {
        $premetadata = $mdh->getMetadata($_REQUEST['was-entityid'], 'saml20-sp-remote');
        requireOwnership($premetadata, $userid);
        $mdh->deleteMetadata($_REQUEST['was-entityid'], 'saml20-sp-remote');
    }

    $testmetadata = null;
    try {
        $testmetadata = $mdh->getMetadata($metadata['entityid'], 'saml20-sp-remote');
    } catch (Exception $e) {
        // catch
    }
    if ($testmetadata) {
        requireOwnership($testmetadata, $userid);
    }

    $result = $mdh->saveMetadata($metadata['entityid'], 'saml20-sp-remote', $metadata);
    if ($result === false) {
        throw new Exception("Could not save metadata. See log for details");
    }

    $template = new \SimpleSAML\XHTML\Template($config, 'metaedit:saved.twig');
    $template->send();
    exit;
}

$form = $editor->metaToForm($metadata);

$template = new \SimpleSAML\XHTML\Template($config, 'metaedit:formedit.twig');
$template->data['form'] = $form;
$template->send();
