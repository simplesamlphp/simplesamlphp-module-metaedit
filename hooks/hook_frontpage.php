<?php
/**
 * Hook to add the modinfo module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 * @return void
 */
function metaedit_hook_frontpage(&$links)
{
    assert(is_array($links));
    assert(array_key_exists("links", $links));

    $links['federation']['metaedit'] = [
        'href' => \SimpleSAML\Module::getModuleURL('metaedit/index.php'),
        'text' => ['en' => 'Metadata registry', 'no' => 'Metadata registrering'],
        'shorttext' => ['en' => 'Metadata registry', 'no' => 'Metadata registrering'],
    ];
}
