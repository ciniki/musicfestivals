<?php
//
// Description
// ===========
// This method will return all the information about an sponsor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the sponsor is attached to.
// sponsor_id:          The ID of the sponsor to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sponsorGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.sponsorGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Sponsor
    //
    if( $args['sponsor_id'] == 0 ) {
        $seq = 1;
        if( $args['festival_id'] && $args['festival_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS max_sequence "
                . "FROM ciniki_musicfestival_sponsors "
                . "WHERE ciniki_musicfestival_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_musicfestival_sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'max');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['max']['max_sequence']) ) {
                $seq = $rc['max']['max_sequence'] + 1;
            }
        }
        $sponsor = array('id'=>0,
            'festival_id'=>isset($args['festival_id']) ? $args['festival_id'] : 0,
            'name'=>'',
            'url'=>'',
            'sequence'=>$seq,
            'flags'=>'0',
            'image_id'=>'0',
        );
    }

    //
    // Get the details for an existing Sponsor
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_sponsors.id, "
            . "ciniki_musicfestival_sponsors.festival_id, "
            . "ciniki_musicfestival_sponsors.name, "
            . "ciniki_musicfestival_sponsors.url, "
            . "ciniki_musicfestival_sponsors.sequence, "
            . "ciniki_musicfestival_sponsors.flags, "
            . "ciniki_musicfestival_sponsors.image_id "
            . "FROM ciniki_musicfestival_sponsors "
            . "WHERE ciniki_musicfestival_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_sponsors.id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sponsors', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'url', 'sequence', 'flags', 'image_id'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.225', 'msg'=>'Sponsor not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['sponsors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.226', 'msg'=>'Unable to find Sponsor'));
        }
        $sponsor = $rc['sponsors'][0];

        //
        // Get the categories and tags for the customer
        //
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_musicfestival_sponsor_tags "
            . "WHERE sponsor_id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'tags', 'fname'=>'tag_type', 
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tag_type'] == 10 ) {
                    $sponsor['tags'] = $tags['lists'];
                }
            }
        }
    }

    $rsp = array('stat'=>'ok', 'sponsor'=>$sponsor, 'tags'=>array());

    //
    // Get the complete list of tags
    //
    $strsql = "SELECT DISTINCT tags.tag_type, tags.tag_name AS names "
        . "FROM ciniki_musicfestival_sponsor_tags AS tags "
        . "INNER JOIN ciniki_musicfestival_sponsors AS sponsors ON ("
            . "tags.sponsor_id = sponsors.id "
            . "AND sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $sponsor['festival_id']) . "' "
            . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND tags.tag_type = 10 "
        . "ORDER BY tags.tag_type, tags.tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.ags', array(
        array('container'=>'tags', 'fname'=>'tag_type', 'fields'=>array('type'=>'tag_type', 'names'), 
            'dlists'=>array('names'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $type) {
            if( $type['type'] == 10 ) {
                $rsp['tags'] = explode('::', $type['names']);
            }
        }
    }

    return $rsp;
}
?>
