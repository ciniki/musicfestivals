<?php
//
// Description
// -----------
// This function will return the list of permission groups for this module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_hooks_modPerms(&$ciniki, $tnid, $args) {

    $modperms = array(
        'label' => 'Music Festivals',
        'perms' => array(
            'ciniki.musicfestivals' => 'Full Access',
            'ciniki.musicfestivals.volunteers' => 'Manage Volunteers',
            ),
        );

    return array('stat'=>'ok', 'modperms'=>$modperms);
}
?>
