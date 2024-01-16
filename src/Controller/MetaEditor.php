<?php

declare(strict_types=1);

namespace SimpleSAML\Module\metaedit\Controller;

use Exception;
use SAML2\Constants;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Metadata;
use SimpleSAML\Module\metaedit\MetaEditor as Editor;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

use function array_key_exists;
use function array_pop;

/**
 * Controller class for the metaedit module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp-module-metaedit
 */
class MetaEditor
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Configuration */
    protected Configuration $moduleConfig;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     * @var \SimpleSAML\Auth\Simple|string
     * @psalm-var \SimpleSAML\Auth\Simple|class-string
     */
    protected $authSimple = Auth\Simple::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $session The session to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->moduleConfig = Configuration::getConfig('module_metaedit.php');
        $this->session = $session;
    }


    /**
     * Inject the \SimpleSAML\Auth\Simple dependency.
     *
     * @param \SimpleSAML\Auth\Simple $authSimple
     */
    public function setAuthSimple(Auth\Simple $authSimple): void
    {
        $this->authSimple = $authSimple;
    }


    /**
     * Main index
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \SimpleSAML\XHTML\Template
     */
    public function main(Request $request): Template
    {
        $authsource = $this->moduleConfig->getValue('auth', 'login-admin');
        $useridattr = $this->moduleConfig->getValue('useridattr', 'eduPersonPrincipalName');

        $as = new $this->authSimple($authsource);
        $as->requireAuth();
        $attributes = $as->getAttributes();

        // Check if userid exists
        if (!isset($attributes[$useridattr])) {
            throw new Error\Exception('User ID is missing');
        }
        $userid = $attributes[$useridattr][0];

        $mdh = new Metadata\MetaDataStorageHandlerSerialize(
            $this->moduleConfig->getArray('metahandlerConfig', ['directory' => '']),
        );

        $delete = $request->get('delete');
        if ($delete !== null) {
            $premetadata = $mdh->getMetadata($delete, 'saml20-sp-remote');
            $this->requireOwnership($premetadata, $userid);
            $mdh->deleteMetadata($delete, 'saml20-sp-remote');
        }

        $list = $mdh->getMetadataSet('saml20-sp-remote');

        $slist = ['mine' => [], 'others' => []];
        foreach ($list as $listitem) {
            if (array_key_exists('owner', $listitem)) {
                if ($listitem['owner'] === $userid) {
                    $slist['mine'][] = $listitem;
                    continue;
                }
            }
            $slist['others'][] = $listitem;
        }

        $t = new Template($this->config, 'metaedit:metalist.twig');
        $t->data['metadata'] = $slist;
        $t->data['userid'] = $userid;

        return $t;
    }


    /**
     * Editor
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \SimpleSAML\XHTML\Template
     */
    public function edit(Request $request): Template
    {
        $authsource = $this->moduleConfig->getValue('auth', 'login-admin');
        $useridattr = $this->moduleConfig->getValue('useridattr', 'eduPersonPrincipalName');

        $as = new $this->authSimple($authsource);
        $as->requireAuth();

        $attributes = $as->getAttributes();
        // Check if userid exists
        if (!isset($attributes[$useridattr])) {
            throw new Error\Exception('User ID is missing');
        }
        $userid = $attributes[$useridattr][0];

        $entityId = $request->get('entityid');
        $xmlMetadata = $request->get('xmlmetadata');

        $mdh = new Metadata\MetaDataStorageHandlerSerialize($this->moduleConfig->getArray('metahandlerConfig', []));

        if ($entityId !== null) {
            $metadata = $mdh->getMetadata($entityId, 'saml20-sp-remote');
            $this->requireOwnership($metadata, $userid);
        } elseif ($xmlMetadata !== null) {
            $xmlUtils = new Utils\XML();
            $xmlUtils->checkSAMLMessage($xmlMetadata, 'saml-meta');
            $entities = Metadata\SAMLParser::parseDescriptorsString($xmlMetadata);
            $entity = array_pop($entities);
            $metadata = $entity->getMetadata20SP();

            /* Trim metadata endpoint arrays. */
            $metadata['AssertionConsumerService'] = [
                Utils\Config\Metadata::getDefaultEndpoint(
                    $metadata['AssertionConsumerService'],
                    [Constants::BINDING_HTTP_POST]
                )
            ];
            $metadata['SingleLogoutService'] = [
                Utils\Config\Metadata::getDefaultEndpoint(
                    $metadata['SingleLogoutService'],
                    [Constants::BINDING_HTTP_REDIRECT]
                )
            ];
        } else {
            $metadata = [
                'owner' => $userid,
            ];
        }

        $editor = new Editor();

        if ($request->get('submit')) {
            $editor->checkForm($request->request->all());
            $metadata = $editor->formToMeta($request->request->all(), [], ['owner' => $userid]);
            $wasEntityId = $request->get('was-entityid');
            if (($wasEntityId !== null) && ($wasEntityId !== $metadata['entityid'])) {
                $premetadata = $mdh->getMetadata($wasEntityId, 'saml20-sp-remote');
                $this->requireOwnership($premetadata, $userid);
                $mdh->deleteMetadata($wasEntityId, 'saml20-sp-remote');
            }

            try {
                $testmetadata = $mdh->getMetadata($metadata['entityid'], 'saml20-sp-remote');
            } catch (Exception $e) {
                // catch
                $testmetadata = null;
            }

            if ($testmetadata) {
                $this->requireOwnership($testmetadata, $userid);
            }

            $result = $mdh->saveMetadata($metadata['entityid'], 'saml20-sp-remote', $metadata);
            if ($result === false) {
                throw new Error\Exception("Could not save metadata. See log for details");
            }

            return new Template($this->config, 'metaedit:saved.twig');
        }

        $form = $editor->metaToForm($metadata);

        $t = new Template($this->config, 'metaedit:formedit.twig');
        $t->data['form'] = $form;

        return $t;
    }


    /**
     * Importer
     *
     * @return \SimpleSAML\XHTML\Template
     */
    public function import(): Template
    {
        /* Load simpleSAMLphp, configuration and metadata */
        return new Template($this->config, 'metaedit:xmlimport.twig');
    }


    /**
     * @param array $metadata
     * @param string $userid
     * @return void
     */
    private function requireOwnership(array $metadata, string $userid): void
    {
        if (!isset($metadata['owner'])) {
            throw new Exception('Metadata has no owner. Which means no one is granted access, not even you.');
        }

        if ($metadata['owner'] !== $userid) {
            throw new Exception(
                'Metadata has an owner that is not equal to your userid, hence you are not granted access.'
            );
        }
    }
}
