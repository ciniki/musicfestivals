<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_acthookProvincialsProcess(&$ciniki, $tnid, &$request) {

    $blocks = [];

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/ahk/musicfestival/provincials';
    $display = 'list';
    $form_errors = '';
    $errors = array();

    if( !isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid request',
            ]]);
    }

    $entry_uuid = $request['uri_split'][$request['cur_uri_pos']];
    $entry_action = $request['uri_split'][($request['cur_uri_pos']+1)];
    if( $entry_uuid == '' || $entry_action == '' ) {
        return array('stat'=>'ok', 'blocks'=>[
            ['type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid request',
            ]]);
    }

    //
    // Load the current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1017', 'msg'=>'', 'err'=>$rc['err']));
    }
    $local = $rc['festival'];

    if( !isset($local['provincial-festival-id']) ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No provincial festival specified',
            ]]);
    }

    //
    // Load the provincials festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $tnid, ['festival' => $local]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Not currently configured',
            ]]);
    }
    $provincials_festival_id = $local['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    if( !isset($member['reg_status']) || $member['reg_status'] != 'open' ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Provincial registrations are now closed',
            ]]);
    }

    //
    // Load the recommendation
    //
    $strsql = "SELECT entries.id, "
        . "entries.status, "
        . "entries.position, "
        . "entries.name, "
        . "entries.mark, "
        . "entries.notes, "
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
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ( "
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS localreg ON ("
            . "entries.local_reg_id = localreg.id "
            . "AND localreg.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS localclasses ON ("
            . "localreg.class_id = localclasses.id "
            . "AND localclasses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS localcategories ON ("
            . "localclasses.category_id = localcategories.id "
            . "AND localcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS localsections ON ("
            . "localcategories.section_id = localsections.id "
            . "AND localsections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE entries.uuid = '" . ciniki_core_dbQuote($ciniki, $entry_uuid) . "' "
        . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'entry');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1287', 'msg'=>'Unable to load entry', 'err'=>$rc['err']));
    }
    if( !isset($rc['entry']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1288', 'msg'=>'Unable to find requested entry'));
    }
    $entry = $rc['entry'];

    if( $entry['status'] < 35 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'This link is not currently valid',
            ]]);
    } elseif( $entry['status'] >= 40 && $entry['status'] < 70 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'You have already accepted.',
            ]]);
    } elseif( $entry['status'] == 70 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'You have already declined.',
            ]]);
    } elseif( $entry['status'] == 80 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'You have already another recommenation.',
            ]]);
    } elseif( $entry['status'] > 80 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'This link has expired.',
            ]]);
    } elseif( $entry['status'] != 35 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Invalid link.',
            ]]);
    }

    if( $entry_action == 'accept' && isset($_GET['confirm']) && $_GET['confirm'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendationentry', $entry['id'], [
            'status' => 40,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>[[
                'type' => 'msg',
                'level' => 'error',
                'content' => 'We are unable to process your request, please contact us for help.',
                ]]);
        }

        $blocks[] = [
            'type' => 'msg',
            'level' => 'success',
            'content' => 'Thank you for your confirmation.',
            ];

        return array('stat'=>'ok', 'blocks'=>$blocks);
    } 
    elseif( $entry_action == 'decline' && isset($_GET['confirm']) && $_GET['confirm'] == 'decline' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $provincials_tnid, 'ciniki.musicfestivals.recommendationentry', $entry['id'], [
            'status' => 70,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>[[
                'type' => 'msg',
                'level' => 'error',
                'content' => 'We are unable to process your request, please contact us for help.',
                ]]);
        }

        $blocks[] = [
            'type' => 'msg',
            'level' => 'success',
            'content' => 'Thank you for your confirmation.',
            ];

        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    if( $entry_action == 'accept' ) {
        $blocks[] = [
            'type' => 'text',
            'title' => 'Confirmation Required',
            'level' => 1,
            'class' => 'aligncenter',
            'content' => (isset($local['provincials-invite-confirm-message']) && $local['provincials-invite-confirm-message'] != '' 
                ? $local['provincials-invite-confirm-message'] 
                : 'Please confirm you would like to participate at provincials.'),
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['text' => 'Confirm', 'url' => "{$base_url}/{$entry_uuid}/accept?confirm=yes"],
                ],
            ];
    }
    elseif( $entry_action == 'decline' ) {
        $blocks[] = [
            'type' => 'text',
            'title' => 'Confirmation Required',
            'level' => 1,
            'class' => 'aligncenter',
            'content' => (isset($local['provincials-invite-decline-message']) && $local['provincials-invite-decline-message'] != '' 
                ? $local['provincials-invite-decline-message'] 
                : 'Please confirm you do <b>not</b> want to participate at provincials.'),
            ];
        $blocks[] = [
            'type' => 'buttons',
            'class' => 'aligncenter',
            'items' => [
                ['text' => 'Decline', 'url' => "{$base_url}/{$entry_uuid}/decline?confirm=decline"],
                ],
            ];
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
