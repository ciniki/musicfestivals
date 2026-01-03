<?php
//
// Description
// -----------
// This method returns the list of roles for a festival, volunteers and shifts. It combines
// the configured list and adds any extras that have been added.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_musicfestivals_volunteerRolesLoad($ciniki, $tnid, $args) {

    //
    // Load the festival
    //
    if( isset($args['festival_id']) && $args['festival_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $festival = $rc['festival'];
    }
    elseif( isset($args['festival']) ) {
        $festival = $args['festival'];
    } 
    else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1245', 'msg'=>'No festival specified'));
    }

    $roles = [];
    if( isset($festival['volunteers-roles']) && $festival['volunteers-roles'] != '' ) {
        $roles = preg_split('/\s*,\s*/', trim($festival['volunteers-roles']));
    }

    //
    // Get the list of approved roles from tags
    //
    $strsql = "SELECT DISTINCT tags.tag_type, tags.tag_name AS names "
        . "FROM ciniki_musicfestival_volunteer_tags AS tags "
        . "INNER JOIN ciniki_musicfestival_volunteers AS volunteers ON ("
            . "tags.volunteer_id = volunteers.id "
            . "AND volunteers.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND volunteers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND tags.tag_type = 50 "
        . "ORDER BY tags.tag_type, tags.tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'tags', 'fname'=>'tag_type', 'fields'=>array('type'=>'tag_type', 'names'), 
            'dlists'=>array('names'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags'][50]['names']) ) {
        $names = explode('::', $rc['tags'][50]['names']);
        foreach($names as $name) {
            if( !in_array($name, $roles) ) {
                $roles[] = $name;
            }
        }
    }

    //
    // Get the number of faqs in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT DISTINCT role AS value "
        . "FROM ciniki_musicfestival_volunteer_shifts "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY role "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'results', 'fname'=>'value', 'fields'=>array('value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['results']) ) {
        foreach($rc['results'] as $row) {
            if( !in_array($row['value'], $roles) ) {
                $roles[] = $row['value'];
            }
        }
    }

    sort($roles); 

    return array('stat'=>'ok', 'roles'=>$roles);
}
?>
