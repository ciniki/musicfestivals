<?php
//
// Description
// -----------
// This function will move the specified entry to Former, and shift everybody else up
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_provincialsClassShift(&$ciniki, $tnid, $args) {
    
    //
    // Get the list of class registrations
    //
    $strsql = "SELECT entries.id, "
        . "entries.recommendation_id, "
        . "entries.status, "
        . "entries.name, "
        . "entries.position, "
        . "entries.mark "
//        . "entries.provincials_reg_id, "
//        . "entries.local_reg_id, "
//        . "recommendations.date_submitted "
//        . "sections.name AS section_name, "
//        . "categories.name AS category_name, "
//        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials_tnid']) . "' "
            . ") "
//        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
//            . "entries.class_id = classes.id "
//            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials_tnid']) . "' "
//            . ") "
//        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
//            . "classes.category_id = categories.id "
//            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials_tnid']) . "' "
//            . ") "
//        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
//            . "categories.section_id = sections.id "
//            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials_tnid']) . "' "
//            . ") "
//        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
//            . "entries.provincials_reg_id = registrations.id "
//            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials_tnid']) . "' "
//            . ") "
        . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['provincials_festival_id']) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials_tnid']) . "' "
        . "ORDER BY entries.position "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'recommendation_id', 'status', 'name', 'position', 'mark'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1517', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
    }
    $entries = isset($rc['entries']) ? $rc['entries'] : array();

    $nextalt = 'no';
    foreach($entries as $entry) {
        if( $entry['position'] > 100 && $entry['position'] < 600 ) {
            $nextalt = 'yes';
            break;
        }
    }

    //
    // If not alternates, leave as is
    //
    if( $nextalt == 'no' ) {
        error_log('NOALT');
        return array('stat'=>'ok');
    }

    $positions = [1,2,3,101,102,103];

    $move = 'no';
    $prev_position = 0;
    foreach($entries as $entry) {
        if( isset($args['entry_id']) && $args['entry_id'] == $entry['id'] && $entry['position'] < 100 ) {
            //
            // Update to former
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['provincials_tnid'], 'ciniki.musicfestivals.recommendationentry', $args['entry_id'], [
                'position' => ($entry['position'] + 600),
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1518', 'msg'=>'Unable to update the recommendationentry', 'err'=>$rc['err']));
            }
            // Move the next entries encountered
            $move = 'yes';
            continue;
        }
        // Check if gap, then move up
        if( $move == 'no' && !isset($args['entry_id']) && $entry['position'] > 1 && (
            ($entry['position'] == 2 && $prev_position == 0)
            || ($entry['position'] == 3 && $prev_position == 1)
            || ($entry['position'] == 3 && $prev_position < 2)
            || ($entry['position'] == 101 && $prev_position < 3)
            || ($entry['position'] == 102 && $prev_position < 101)
            || ($entry['position'] == 103 && $prev_position < 102)
            || ($entry['position'] == 104 && $prev_position < 103)
            )) {
            $move = 'yes';     
        }

        if( $move == 'yes' && $entry['position'] > 1 && $entry['position'] < 600 ) {
            //
            // Move entry up
            //
            $new_position = $entry['position'] - 1;
            if( $new_position > 3 && $new_position < 101 ) {
                $new_position = 3;
            }
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $args['provincials_tnid'], 'ciniki.musicfestivals.recommendationentry', $entry['id'], [
                'position' => $new_position,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1519', 'msg'=>'Unable to update the recommendationentry', 'err'=>$rc['err']));
            }
        }
        $prev_position = $entry['position'];
    }

    return array('stat'=>'ok');
}
?>
