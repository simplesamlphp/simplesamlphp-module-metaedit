<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;

/**
 * Hook to add the metaedit module to the config page.
 *
 * @param \SimpleSAML\XHTML\Template &$template The template that we should alter in this hook.
 */
function metaedit_hook_configpage(Template &$template): void
{
    $template->data['links'][] = [
        'href' => Module::getModuleURL('metaedit/'),
        'text' => Translate::noop('Metadata registry'),
    ];

    $template->getLocalization()->addModuleDomain('metaedit');
}
