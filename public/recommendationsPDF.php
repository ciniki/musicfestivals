<?php
//
// Description
// -----------
// This method returns the recommendation entries in excel format.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_recommendationsPDF(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator Recommendation'),
        'member_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Member'),
//        'recommendation_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Recommendation'),
//        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
//        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationsPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load member
    //
    $strsql = "SELECT members.id, "
        . "members.name "
        . "FROM ciniki_musicfestivals_members AS members "
        . "WHERE members.id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
        . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'member');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.935', 'msg'=>'Unable to load member', 'err'=>$rc['err']));
    }
    if( !isset($rc['member']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.936', 'msg'=>'Unable to find requested member'));
    }
    $member = $rc['member'];

    //
    // Load the festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $args['tnid'], $args['festival_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    
    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the list of recommendations for the member festival
    //
    $strsql = "SELECT entries.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS class, "
        . "entries.status, "
        . "entries.position, "
        . "entries.name, "
        . "entries.mark, "
        . "recommendations.adjudicator_name "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member_id']) . "' "
        . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY classes.name, entries.position, entries.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array( 'id', 'class', 'status', 'position', 'name', 'mark', 'adjudicator_name'),
            'maps'=>array('position'=>$maps['recommendationentry']['position']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.755', 'msg'=>'Unable to load recommendations', 'err'=>$rc['err']));
    }
    $recommendations = isset($rc['recommendations']) ? $rc['recommendations'] : array();

    foreach($recommendations as $rid => $recommendation) {
        $recommendations[$rid]['cssclass'] = 'statuswhite';
        if( $recommendation['status'] == 10 ) {
            if( preg_match("/Alt/", $recommendation['position']) ) {
                $recommendations[$rid]['cssclass'] = 'statusyellow';
            }
        } elseif( $recommendation['status'] == 30 ) {
            $recommendations[$rid]['cssclass'] = 'statusorange';
        } elseif( $recommendation['status'] == 50 ) {
            $recommendations[$rid]['cssclass'] = 'statusgreen';
        } elseif( $recommendation['status'] == 70 ) {
            $recommendations[$rid]['cssclass'] = 'statusred';
        } elseif( $recommendation['status'] == 80 ) {
            $recommendations[$rid]['cssclass'] = 'statuspurple';
        } elseif( $recommendation['status'] == 90 ) {
            $recommendations[$rid]['cssclass'] = 'statusgrey';
        }
    }
   
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'memberRecommendationsPDF');
    $rc = ciniki_musicfestivals_templates_memberRecommendationsPDF($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        'title' => $member['name'],
        'subtitle' => $festival['name'] . ' - Recommendations',
        'member_id' => $args['member_id'],
        'recommendations' => $recommendations,
        ]);  
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($rc['filename'], 'I');
    }

    return array('stat'=>'exit');
}
?>
