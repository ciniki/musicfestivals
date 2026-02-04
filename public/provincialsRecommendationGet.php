<?php
//
// Description
// ===========
// This method will return all the information about an adjudicator recommendation.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the adjudicator recommendation is attached to.
// recommendation_id:          The ID of the adjudicator recommendation to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_provincialsRecommendationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
        'classes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Classes'),
        'registrations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Registrations'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationGet');
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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the load festival and provincials festival info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    $nplist = [];
    //
    // Load the recommendation
    //
    $strsql = "SELECT recommendations.id, "
        . "recommendations.festival_id, "
        . "IFNULL(sections.name, '') AS section_name, "
        . "recommendations.status, "
        . "recommendations.status AS status_text, "
        . "recommendations.section_id, "
        . "recommendations.adjudicator_name, "
        . "recommendations.adjudicator_phone, "
        . "recommendations.adjudicator_email, "
        . "recommendations.acknowledgement, "
        . "DATE_FORMAT(recommendations.date_submitted, '%b %d, %Y %l:%i %p') AS date_submitted, "
        . "recommendations.local_adjudicator_id "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "recommendations.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "AND recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'status_text', 'section_id', 'section_name', 
                'adjudicator_name', 'adjudicator_phone', 'adjudicator_email', 'acknowledgement', 
                'date_submitted', 'local_adjudicator_id', 
                ),
            'maps'=>array('status_text'=>$maps['recommendation']['status']),
            'utctotz'=>array(
                'date_submitted'=> array('timezone'=>$intl_timezone, 'format'=>'M j, Y g:i:s A'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.604', 'msg'=>'Submission not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['recommendations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.605', 'msg'=>'Unable to find Submission'));
    }
    $recommendation = $rc['recommendations'][0];
    $recommendation['details'] = array(
        array('label' => 'Status', 'value'=>$recommendation['status_text']),
        array('label' => 'Section', 'value'=>$recommendation['section_name']),
//            array('label' => 'Acknowledgement', 'value'=>$recommendation['acknowledgement']),
//            array('label' => 'Submitted', 'value'=>$recommendation['date_submitted']),
        );
    if( $recommendation['status'] > 10 ) {
       $recommendation['details'][] = array('label' => 'Submitted', 'value'=>$recommendation['date_submitted']);
    }

    $recommendation['details'][] = ['label' => 'Adjudicator', 'value'=>$recommendation['adjudicator_name']];
    $recommendation['details'][] = ['label' => 'Phone', 'value'=>$recommendation['adjudicator_phone']];
    $recommendation['details'][] = ['label' => 'Email', 'value'=>$recommendation['adjudicator_email']];

    //
    // Get the entries
    //
    $strsql = "SELECT entries.id, "
        . "entries.status, "
        . "entries.status AS status_text, "
        . "entries.position, "
        . "entries.position AS position_text, "
        . "entries.name, "
        . "entries.mark, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name "
        . "FROM ciniki_musicfestival_recommendation_entries AS entries "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "WHERE entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['id']) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "ORDER BY class_code, entries.position "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'entries', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'status_text', 'class_code', 'class_name', 'position', 'position_text', 'name', 'mark'),
            'maps'=>array(
                'position_text'=>$maps['recommendationentry']['position_shortname'],
                'status_text'=>$maps['recommendationentry']['status'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.973', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
    }
    $recommendation['entries'] = isset($rc['entries']) ? $rc['entries'] : array();

    $recommendation['num_approved'] = 0;
    $recommendation['num_accepted'] = 0;
    foreach($recommendation['entries'] as $eid => $entry) {
        $nplist[] = $entry['id'];
        $recommendation['entries'][$eid]['class_name'] = str_replace($recommendation['section_name'] . ' - ', '', $recommendation['entries'][$eid]['class_name']);
        if( preg_match("/^([^-]+) - /", $recommendation['section_name'], $m) ) {
            if( $m[1] != '' ) {
                $recommendation['entries'][$eid]['name'] = str_replace($m[1] . ' - ', '', $recommendation['entries'][$eid]['name']);
            }
        }
        if( $entry['status'] == 30 ) {
            $recommendation['num_approved']++;
        }
        if( $entry['status'] == 40 ) {
            $recommendation['num_accepted']++;
        }
    }

    $rsp = array('stat'=>'ok', 'recommendation'=>$recommendation, 'nplist'=>$nplist, 'classes'=>[], 'registrations'=>[]);

    //
    // Return the list of classes
    //
    if( isset($args['classes']) && $args['classes'] == 'yes' 
        && isset($recommendation['section_id']) && $recommendation['section_id'] > 0 
        ) {
        $section = [
            'id' => $recommendation['section_id'],
            'name' => $recommendation['section_name'],
            ];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationClassesLoad');
        $rc = ciniki_musicfestivals_recommendationClassesLoad($ciniki, $provincials_tnid, $section);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1290', 'msg'=>'', 'err'=>$rc['err']));
        }
        $rsp['classes'] = isset($rc['classes']) ? $rc['classes'] : array();
    }

    //
    // Return the list of registrations for the adjudicator
    //
    if( isset($args['registrations']) && $args['registrations'] == 'yes' 
        && isset($recommendation['local_adjudicator_id']) && $recommendation['local_adjudicator_id'] > 0 
        && isset($recommendation['section_id']) && $recommendation['section_id'] > 0 
        ) {
        $strsql = "SELECT classes.provincials_code, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name, "
            . "registrations.id, "
            . "registrations.mark, "
            . "registrations.display_name, "
            . "registrations.title1, "
            . "registrations.movements1, "
            . "registrations.composer1, "
            . "registrations.perf_time1 "
            . "FROM ciniki_musicfestival_adjudicatorrefs AS arefs "
            . "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
                . "ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "ssections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "timeslots.id = registrations.timeslot_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE arefs.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['local_adjudicator_id']) . "' "
            . "AND ("
                    . "(arefs.object_id = ssections.id AND arefs.object = 'ciniki.musicfestivals.schedulesection') "
                    . "OR (arefs.object_id = divisions.id AND arefs.object = 'ciniki.musicfestivals.scheduledivision') "
                . ") "
            . "AND arefs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY registrations.display_name, sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'section_name', 'category_name', 'class_code', 'class_name', 'mark',
                    'title1', 'movements1', 'composer1', 'perf_time1'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1041', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $rsp['registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
        foreach($rsp['registrations'] as $rid => $reg) {
           $rc = ciniki_musicfestivals_titleMerge($ciniki, $args['tnid'], $reg, 1);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $title = $rc['title'];
            $rsp['registrations'][$rid]['name'] = $reg['display_name'] . ' - ' . $reg['class_code'] . ' - ' . $reg['class_name'] . ' - ' . $title;
        }
    }


    return $rsp;
}
?>
