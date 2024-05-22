<?php

declare(strict_types=1);

namespace SimpleSAML\Module\metaedit;

use Exception;
use SAML2\Constants;

/**
 * Editor for metadata
 *
 * @package simpleSAMLphp
 */
class MetaEditor
{
    /**
     * @param array $request
     * @param array &$metadata
     * @param string $key
     */
    protected function getStandardField(array $request, array &$metadata, string $key): void
    {
        if (array_key_exists('field_' . $key, $request)) {
            $metadata[$key] = $request['field_' . $key];
        } elseif (isset($metadata[$key])) {
            unset($metadata[$key]);
        }
    }


    /**
     * @param array $request
     * @param array &$metadata
     * @param string $key
     * @param string $binding
     * @param bool $indexed
     */
    protected function getEndpointField(
        array $request,
        array &$metadata,
        string $key,
        string $binding,
        bool $indexed,
    ): void {
        if (array_key_exists('field_' . $key, $request)) {
            $e = [
                'Binding' => $binding,
                'Location' => $request['field_' . $key],
            ];
            if ($indexed) {
                $e['index'] = 0;
            }
            $metadata[$key] = [$e];
        } else {
            if (isset($metadata[$key])) {
                unset($metadata[$key]);
            }
        }
    }


    /**
     * @param array $request
     * @param array $metadata
     * @param array $override
     * @return array
     */
    public function formToMeta(array $request, array $metadata = [], array $override = []): array
    {
        $this->getStandardField($request, $metadata, 'entityid');
        $this->getStandardField($request, $metadata, 'name');
        $this->getStandardField($request, $metadata, 'description');
        $this->getEndpointField($request, $metadata, 'AssertionConsumerService', Constants::BINDING_HTTP_POST, true);
        $this->getEndpointField($request, $metadata, 'SingleLogoutService', Constants::BINDING_HTTP_REDIRECT, false);
        $metadata['updated'] = time();

        foreach ($override as $key => $value) {
            $metadata[$key] = $value;
        }
        return $metadata;
    }


    /**
     * @param array $request
     * @param string $key
     */
    protected function requireStandardField(array $request, string $key): void
    {
        if (!array_key_exists('field_' . $key, $request)) {
            throw new Exception('Required field [' . $key . '] was missing.');
        }
        if (empty($request['field_' . $key])) {
            throw new Exception('Required field [' . $key . '] was empty.');
        }
    }


    /**
     * @param array $request
     */
    public function checkForm(array $request): void
    {
        $this->requireStandardField($request, 'entityid');
        $this->requireStandardField($request, 'name');
    }


    /**
     * @param string $name
     * @return string
     */
    protected function header(string $name): string
    {
        return '<tr ><td>&nbsp;</td><td class="header">' . $name . '</td></tr>';
    }


    /**
     * @param array $metadata
     * @param string $key
     * @param string $name
     * @return string
     */
    protected function readonlyDateField(array $metadata, string $key, string $name): string
    {
        $value = '<span style="color: #aaa">Not set</a>';
        if (array_key_exists($key, $metadata)) {
            $value = date('j. F Y, G:i', $metadata[$key]);
        }
        return '<tr><td class="name">' . $name . '</td><td class="data">' . $value . '</td></tr>';
    }


    /**
     * @param array $metadata
     * @param string $key
     * @param string $name
     * @return string
     */
    protected function readonlyField(array $metadata, string $key, string $name): string
    {
        $value = '';
        if (array_key_exists($key, $metadata)) {
            $value = $metadata[$key];
        }
        return '<tr><td class="name">' . $name . '</td><td class="data">' . htmlspecialchars($value) . '</td></tr>';
    }


    /**
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function hiddenField(string $key, string $value): string
    {
        return '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />';
    }


    /**
     * @param array &$metadata
     * @param string $key
     */
    protected function flattenLanguageField(array &$metadata, string $key): void
    {
        if (array_key_exists($key, $metadata)) {
            if (is_array($metadata[$key])) {
                if (isset($metadata[$key]['en'])) {
                    $metadata[$key] = $metadata[$key]['en'];
                } else {
                    unset($metadata[$key]);
                }
            }
        }
    }


    /**
     * @param array $metadata
     * @param string $key
     * @param string $name
     * @param bool $textarea
     * @return string
     */
    protected function standardField(array $metadata, string $key, string $name, bool $textarea = false): string
    {
        $value = '';
        if (array_key_exists($key, $metadata)) {
            $value = htmlspecialchars($metadata[$key]);
        }
        if ($textarea) {
            return '<tr><td class="name">' . $name . '</td><td class="data"><textarea name="field_'
                . $key . '" rows="5" cols="50">' . $value . '</textarea></td></tr>';
        } else {
            return '<tr><td class="name">' . $name
                . '</td><td class="data"><input type="text" size="60" name="field_'
                . $key . '" value="' . $value . '" /></td></tr>';
        }
    }


    /**
     * @param array $metadata
     * @param string $key
     * @param string $name
     * @param bool $textarea
     * @return string
     */
    protected function endpointField(array $metadata, string $key, string $name, bool $textarea = false): string
    {
        $value = '';
        if (array_key_exists($key, $metadata)) {
            if (is_array($metadata[$key])) {
                $value = htmlspecialchars($metadata[$key][0]['Location']);
            } else {
                $value = htmlspecialchars($metadata[$key]);
            }
        }

        if ($textarea) {
            return '<tr><td class="name">' . $name . '</td><td class="data"><textarea name="field_'
                . $key . '" rows="5" cols="50">' . $value . '</textarea></td></tr>';
        } else {
            return '<tr><td class="name">' . $name
                . '</td><td class="data"><input type="text" size="60" name="field_'
                . $key . '" value="' . $value . '" /></td></tr>';
        }
    }


    /**
     * @param array $metadata
     * @return string
     */
    public function metaToForm(array $metadata): string
    {
        $this->flattenLanguageField($metadata, 'name');
        $this->flattenLanguageField($metadata, 'description');
        return '<form action="edit.php" method="post">' .
            (array_key_exists('entityid', $metadata) ?
            $this->hiddenField('was-entityid', $metadata['entityid']) :
            '') . '<div id="tabdiv"><ul><li><a href="#basic">Name and descrition</a></li>' .
            '<li><a href="#saml">SAML 2.0</a></li></ul><div id="basic"><table class="formtable">' .
            $this->standardField($metadata, 'entityid', 'EntityID') .
            $this->standardField($metadata, 'name', 'Name of service') .
            $this->standardField($metadata, 'description', 'Description of service', true) .
            $this->readonlyField($metadata, 'owner', 'Owner') .
            $this->readonlyDateField($metadata, 'updated', 'Last updated') .
            $this->readonlyDateField($metadata, 'expire', 'Expire') .
            '</table></div><div id="saml"><table class="formtable">' .
            $this->endpointField($metadata, 'AssertionConsumerService', 'AssertionConsumerService endpoint') .
            $this->endpointField($metadata, 'SingleLogoutService', 'SingleLogoutService endpoint') .
            '</table></div></div><input type="submit" name="submit" value="Save" style="margin-top: 5px" /></form>';
    }
}
