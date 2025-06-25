<?php
//
// Description
// -----------
// Create the short name for an item
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_shortnameMake(&$ciniki, $tnid, $args) {

    $shortname = '';
    
    if( isset($args['format']) && $args['format'] == 'smartinitials'
        && isset($args['text']) && $args['text'] != '' 
        ) {
        // 
        // Rules for divisions
        //
        if( isset($args['type']) && $args['type'] == 'division' ) {
            $shortname = $args['text'];
            $shortname = preg_replace("/(Ages|Group |Level)/", '', $shortname);
            $shortname = preg_replace("/Songs from the Screen and More/", 'SSAM', $shortname);
            $shortname = preg_replace("/[a-z ]/", '', $shortname);
        }

        //
        // Rules for timeslots 
        //
        if( isset($args['type']) && $args['type'] == 'timeslot' ) {
            if( preg_match("/(Workshop )/", $args['text']) ) {
                $shortname = 'Workshop';
            } elseif( preg_match("/(Lunch )/", $args['text']) ) {
                $shortname = 'Lunch';
            } else {
                $shortname = $args['text'];
                $shortname = preg_replace("/(Ages|Group |Level)/", '', $shortname);
                $shortname = preg_replace("/Songs from the Screen and More/", 'SSAM', $shortname);
                $shortname = preg_replace("/[a-z ]/", '', $shortname);
            }
        }
    }

    return array('stat'=>'ok', 'shortname'=>$shortname);
}
?>
