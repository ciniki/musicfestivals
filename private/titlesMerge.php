<?php
//
// Description
// -----------
// This function will merge the title, composer and movements into 1 line
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_titlesMerge(&$ciniki, $tnid, $registration) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $titles = '';
    for($i = 1; $i <= 8; $i++) {
        $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
        if( isset($rc['title']) && $rc['title'] != '' ) {
            $titles .= ($titles != '' ? "\n" : '') . $rc['title'];
        }
    }

    return array('stat'=>'ok', 'titles'=>$titles);
}
?>
