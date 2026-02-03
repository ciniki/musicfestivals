<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_acthooks($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // FIXME: Add check if the music festivals enabled and if provincials or local festival
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        $hooks = [
            'musicfestival' => [
                'register' => ['fn' => 'acthookProvincialsRegisterProcess'],
                ],
            ];
    } else {
        $hooks = [
            'musicfestival' => [
                'provincials' => ['fn' => 'acthookProvincialsProcess'],
                ],
            ];
    }

    return array('stat'=>'ok', 'hooks'=>$hooks);
}
?>
