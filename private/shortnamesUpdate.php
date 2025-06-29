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
function ciniki_musicfestivals_shortnamesUpdate(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'shortnameMake');

    if( isset($args['type']) && $args['type'] == 'division' ) {
        $strsql = "SELECT divisions.id, "
            . "divisions.name, "
            . "divisions.shortname "
            . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
            . "WHERE divisions.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.800', 'msg'=>'Unable to load divisions', 'err'=>$rc['err']));
        }
        $rows = isset($rc['rows']) ? $rc['rows'] : array();

        foreach($rows AS $row) {
            $rc = ciniki_musicfestivals_shortnameMake($ciniki, $tnid, [
                'type' => 'division',
                'format' => $args['format'],
                'text' => $row['name'],
                ]);
            if( isset($rc['shortname']) && $rc['shortname'] != $row['shortname'] ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.scheduledivision', $row['id'], [
                    'shortname' => $rc['shortname'],
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.801', 'msg'=>'Unable to update shortname for schedule division', 'err'=>$rc['err']));
                }
            }
        }
    }

    if( isset($args['type']) && $args['type'] == 'timeslot' ) {
        $strsql = "SELECT timeslots.id, "
            . "timeslots.name, "
            . "timeslots.shortname "
            . "FROM ciniki_musicfestival_schedule_timeslots AS timeslots "
            . "WHERE timeslots.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.802', 'msg'=>'Unable to load timeslots', 'err'=>$rc['err']));
        }
        $rows = isset($rc['rows']) ? $rc['rows'] : array();

        foreach($rows AS $row) {
            $rc = ciniki_musicfestivals_shortnameMake($ciniki, $tnid, [
                'type' => 'timeslot',
                'format' => $args['format'],
                'text' => $row['name'],
                ]);
            if( isset($rc['shortname']) && $rc['shortname'] != $row['shortname'] ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.scheduletimeslot', $row['id'], [
                    'shortname' => $rc['shortname'],
                    ], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1025', 'msg'=>'Unable to update shortname for schedule timeslot', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
