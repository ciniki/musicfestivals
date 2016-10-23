<?php
//
// Description
// -----------
// The mappings of int fields to text.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_maps($ciniki) {
    $maps = array();
    $maps['festival'] = array('status'=>array(
        '10'=>'Active',
        '60'=>'Archived',
        ));

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
