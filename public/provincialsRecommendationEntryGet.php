<?php
//
// Description
// ===========
// This method will return an entry as part of a adjudicator recommendation
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
function ciniki_musicfestivals_provincialsRecommendationEntryGet($ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
        'entry_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Recommendation'),
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $provincials_tnid, $provincials_festival_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $provincials_maps = $rc['maps'];

    //
    // Load the entry
    //
    if( $args['entry_id'] == 0 ) {
        $entry = [
            'id' => 0,
            'name' => '',
            'position' => '',
            'mark' => '',
            ];
    } else {
        $strsql = "SELECT entries.id, "
            . "entries.status, "
            . "entries.position, "
            . "entries.name, "
            . "entries.mark, "
            . "entries.notes, "
            . "entries.dt_invite_sent, "
            . "entries.class_id, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "recommendations.id AS recommendation_id, "
            . "recommendations.status AS recommendation_status, "
            . "localreg.id AS registration_id, "
            . "localreg.display_name AS local_display_name, "
            . "localclasses.code AS local_class_code, "
            . "localclasses.name AS local_class_name, "
            . "localcategories.name AS local_category_name, "
            . "localsections.name AS local_section_name "
            . "FROM ciniki_musicfestival_recommendation_entries AS entries "
            . "INNER JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                . "entries.recommendation_id = recommendations.id "
                . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ( "
                . "entries.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS localreg ON ("
                . "entries.local_reg_id = localreg.id "
                . "AND localreg.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS localclasses ON ("
                . "localreg.class_id = localclasses.id "
                . "AND localclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS localcategories ON ("
                . "localclasses.category_id = localcategories.id "
                . "AND localcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS localsections ON ("
                . "localcategories.section_id = localsections.id "
                . "AND localsections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . "AND entries.id = '" . ciniki_core_dbQuote($ciniki, $args['entry_id']) . "' "
            . "AND entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1287', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
        }
        if( !isset($rc['entry']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1288', 'msg'=>'Unable to find requested entry'));
        }
        $entry = $rc['entry'];
        
        $entry['details'] = array(
            array('label' => 'Status', 'value'=>$maps['recommendationentry']['status'][$entry['status']]),
            array('label' => 'Provincial Class', 'value'=>$entry['class_code'] . ' - ' . $entry['class_name']),
            array('label' => 'Name', 'value'=>$entry['name']),
            array('label' => 'Position', 'value'=>$maps['recommendationentry']['position'][$entry['position']]),
            array('label' => 'Mark', 'value'=>$entry['mark']),
            );
        if( isset($entry['local_class_code']) && $entry['local_class_code'] != '' ) {
            $entry['details'][] = [
                'label' => 'Local Class', 
                'value' => $entry['local_class_code'] . ' - ' . $entry['local_category_name'] . ' - ' . $entry['local_class_name'],
                ];
        }
        if( $entry['status'] > 30 && $entry['dt_invite_sent'] != '' && $entry['dt_invite_sent'] != '0000-00-00 00:00:00' ) {
            $dt = new DateTime($entry['dt_invite_sent'], new DateTimezone('UTC'));
            $dt->setTimezone(new DateTimezone($intl_timezone));
            $entry['details'][] = ['label' => 'Invite Sent', 'value' => $dt->format("l, F j, Y g:i A")];
        }
        if( isset($entry['notes']) && $entry['notes'] != '' ) {
            $entry['details'][] = ['label' => 'Notes', 'value' => $entry['notes']];
        }

        //
        // Load the list of emails sent about this entry
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'registrationMessages');
        $rc = ciniki_musicfestivals_registrationMessages($ciniki, $args['tnid'], $entry['registration_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1486', 'msg'=>'Unable to load emails', 'err'=>$rc['err']));
        }
        $entry['messages'] = isset($rc['messages']) ? $rc['messages'] : array();

/*        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], [
//            'object' => 'ciniki.musicfestivals.recommendationentry',
//            'object_id' => $entry['id'],
            'object' => 'ciniki.musicfestivals.registration',
            'object_id' => $entry['registration_id'],
            'xml' => 'no',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1341', 'msg'=>'Unable to load emails', 'err'=>$rc['err']));
        }
        $entry['messages'] = isset($rc['messages']) ? $rc['messages'] : array(); */

        //
        // Get the list of class registrations
        //
        $strsql = "SELECT entries.id, "
            . "entries.recommendation_id, "
            . "entries.status, "
            . "entries.status AS status_text, "
            . "entries.name, "
            . "entries.position, "
            . "entries.position AS position_text, "
            . "entries.mark, "
            . "entries.provincials_reg_id, "
            . "entries.local_reg_id, "
            . "entries.dt_invite_sent, "
            . "recommendations.date_submitted, "
            . "sections.name AS section_name, "
            . "categories.name AS category_name, "
            . "classes.name AS class_name, "
            . "IFNULL(registrations.status, '') AS reg_status_text "
            . "FROM ciniki_musicfestival_recommendations AS recommendations "
            . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                . "recommendations.id = entries.recommendation_id "
                . "AND entries.class_id = '" . ciniki_core_dbQuote($ciniki, $entry['class_id']) . "' "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "entries.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "entries.provincials_reg_id = registrations.id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . "ORDER BY entries.position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'recommendation_id', 'status', 'status_text', 'name', 'position', 'position_text', 'mark', 
                    'provincials_reg_id', 'local_reg_id', 'date_submitted', 'date_invited'=>'dt_invite_sent',
                    'section_name', 'category_name', 'class_name', 'reg_status_text', 
                    ),
                'utctotz'=>array(
                    'date_invited' => array('timezone'=>$intl_timezone, 'format'=>'M j - g:i A'),
                    ),
                'maps'=>array(
                    'status_text'=>$maps['recommendationentry']['status'],
                    'position_text'=>$maps['recommendationentry']['position'],
                    'reg_status_text'=>$provincials_maps['registration']['status'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1510', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
        }
        $entry['classentries'] = isset($rc['entries']) ? $rc['entries'] : array();

        foreach($entry['classentries'] as $e) {
            if( $e['position'] > 100 && $e['position'] < 600 ) {
                $entry['nextalt'] = $e;
                break;
            }
        }
    }

    return array('stat'=>'ok', 'entry'=>$entry);
}
?>
