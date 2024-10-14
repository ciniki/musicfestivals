<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_musicfestivals_sapos_objectList($ciniki, $tnid) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.musicfestivals.registration' => array(
            'name' => 'Music Festival Registrations',
            ),
        'ciniki.musicfestivals.adminfee' => array(
            'name' => 'Music Festival Admin Fees',
            ),
        'ciniki.musicfestivals.latefee' => array(
            'name' => 'Music Festival Late Fees',
            ),
        );
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $objects['ciniki.musicfestivals.memberlatefee'] = array('name'=>'Member Festival Late Fee');
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
