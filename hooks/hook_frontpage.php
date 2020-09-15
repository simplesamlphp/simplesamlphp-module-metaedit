<?php

use Webmozart\Assert\Assert;

/**
 * Hook to add the modinfo module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */

function metaedit_hook_frontpage(array &$links): void
{
    Assert::keyExists($links, 'federation');

    $links['federation']['metaedit'] = [
        'href' => \SimpleSAML\Module::getModuleURL('metaedit/index.php'),
        'text' => ['en' => 'Metadata registry', 'no' => 'Metadata registrering'],
        'shorttext' => ['en' => 'Metadata registry', 'no' => 'Metadata registrering'],
    ];
}
