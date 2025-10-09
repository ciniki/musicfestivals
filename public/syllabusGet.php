<?php
//
// Description
// ===========
// This method will return all the information about an syllabus.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the syllabus is attached to.
// syllabus_id:          The ID of the syllabus to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_syllabusGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'syllabus_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Syllabus'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.syllabusGet');
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
    // Return default for new Syllabus
    //
    if( $args['syllabus_id'] == 0 ) {
        $syllabus = array('id'=>0,
            'festival_id'=>(isset($args['festival_id']) ? $args['festival_id'] : 0),
            'name'=>'',
            'permalink'=>'',
            'sequence'=>'1',
            'flags'=>'0',
            'live_end_dt'=>'0000-00-00 00:00:00',
            'virtual_end_dt'=>'0000-00-00 00:00:00',
            'titles_end_dt'=>'0000-00-00 00:00:00',
            'upload_end_dt'=>'0000-00-00 00:00:00',
            'sections_description'=>'',
            'rules'=>'',
        );
    }

    //
    // Get the details for an existing Syllabus
    //
    else {
        $strsql = "SELECT syllabuses.id, "
            . "syllabuses.festival_id, "
            . "syllabuses.name, "
            . "syllabuses.permalink, "
            . "syllabuses.sequence, "
            . "syllabuses.flags, "
            . "syllabuses.live_end_dt, "
            . "syllabuses.virtual_end_dt, "
            . "syllabuses.titles_end_dt, "
            . "syllabuses.upload_end_dt, "
            . "syllabuses.sections_description, "
            . "syllabuses.rules "
            . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
            . "WHERE syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND syllabuses.id = '" . ciniki_core_dbQuote($ciniki, $args['syllabus_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'syllabuses', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'permalink', 'sequence', 'flags', 
                    'live_end_dt', 'virtual_end_dt', 'titles_end_dt', 'upload_end_dt',
                    'sections_description', 'rules',
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1063', 'msg'=>'Syllabus not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['syllabuses'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1064', 'msg'=>'Unable to find Syllabus'));
        }
        $syllabus = $rc['syllabuses'][0];
    }

    return array('stat'=>'ok', 'syllabus'=>$syllabus);
}
?>
