<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountRecommendationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/recommendations';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/musicfestival/recommendations");
        return array('stat'=>'exit');
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
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
    // Load current festival
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
    // Load the adjudicator
    //
    $strsql = "SELECT adjudicators.id "  
        . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $local['id']) . "' "
        . "AND adjudicators.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.397', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( !isset($rc['adjudicator']['id']) ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Not currently an adjudicator.',
            ]]);
    }
    $adjudicator = $rc['adjudicator'];

    //
    // Load adjudicator registrations
    //
    $strsql = "SELECT classes.provincials_code, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "registrations.id, "
        . "registrations.mark, "
        . "registrations.display_name "
        . "FROM ciniki_musicfestival_adjudicatorrefs AS refs "
        . "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $local['id']) . "' "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE refs.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator['id']) . "' "
        . "AND ("
                . "("
                . "refs.object = 'ciniki.musicfestivals.schedulesection' "
                . "AND refs.object_id = ssections.id "
                . ") OR ("
                . "refs.object = 'ciniki.musicfestivals.scheduledivision' "
                . "AND refs.object_id = divisions.id "
                . ") "
            . ") "
        . "AND refs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY registrations.class_id, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'classes', 'fname'=>'provincials_code', 'fields'=>array()),
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'class_code', 'class_name', 'mark', 'display_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1041', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $adjudicator['registrations'] = isset($rc['classes']) ? $rc['classes'] : array();

    foreach($adjudicator['registrations'] as $cid => $class) {
        foreach($class['registrations'] as $rid => $reg) {
            $adjudicator['registrations'][$cid]['registrations'][$rid]['name'] = $reg['display_name'] . ' - ' . $reg['class_code'] . ' - ' . $reg['class_name'];
        }
    }

    //
    // Load the adjudicator customer record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, [
        'customer_id' => $request['session']['customer']['id'],
        'phones' => 'yes',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer = $rc['customer'];
    $adjudicator['name'] = $customer['display_name'];
    if( isset($customer['phones'][0]['phone_number']) ) {
        $adjudicator['phone'] = $customer['phones'][0]['phone_number'];
    } elseif( isset($request['session']['ciniki.musicfestivals']['adjudicator_phone']) ) {
        $adjudicator['phone'] = $request['session']['ciniki.musicfestivals']['adjudicator_phone'];
    } else {
        $adjudicator['phone'] = '';
    }
    if( isset($customer['emails'][0]['address']) ) {
        $adjudicator['email'] = $customer['emails'][0]['address'];
    }

    //
    // Get the member id
    //
    $strsql = "SELECT members.id, "
        . "members.tnid AS provincials_tnid, "
        . "members.name, "
        . "festivals.name AS provincials_name, "
        . "customers.customer_id "
        . "FROM ciniki_musicfestivals_members AS members "
        . "INNER JOIN ciniki_musicfestival_members AS fmembers ON ("
            . "members.id = fmembers.member_id "
            . "AND fmembers.festival_id = '" . ciniki_core_dbQuote($ciniki, $local['provincial-festival-id']) . "' "
            . "AND members.tnid = fmembers.tnid "
            . ") "
        . "INNER JOIN ciniki_musicfestivals AS festivals ON ("
            . "fmembers.festival_id = festivals.id "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_member_customers AS customers ON ("
            . "members.id = customers.member_id "
            . "AND customers.tnid = festivals.tnid "
            . ") "
        . "WHERE members.member_tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'members', 'fname'=>'id', 'fields'=>array('id', 'provincials_tnid', 'name', 'provincials_name')),
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('id'=>'customer_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1052', 'msg'=>'Unable to load members', 'err'=>$rc['err']));
    }
    if( !isset($rc['members']) || count($rc['members']) == 0 ) {
        return array('stat'=>'ok', 'blocks'=>[[
            'type' => 'msg',
            'level' => 'error',
            'content' => 'No provincial festival configured.',
            ]]);
    }
    $member = array_pop($rc['members']);
    $provincials = [
        'id' => $local['provincial-festival-id'],
        'tnid' => $member['provincials_tnid'],
        'name' => $member['provincials_name'],
        'member_id' => $member['id'],
        ];

    //
    // Load the provincial sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.recommendations_description AS description "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials['tnid']) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials['id']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'permalink', 'fields'=>array('id', 'permalink', 'name', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.595', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Load the existing submissions 
    //
    $strsql = "SELECT recommendations.id, "
        . "recommendations.uuid, "
        . "recommendations.member_id, "
        . "recommendations.festival_id, "
        . "recommendations.section_id, "
        . "IFNULL(sections.name, '') AS section_name, "
        . "recommendations.status, "
        . "recommendations.status AS status_text, "
        . "recommendations.adjudicator_name, "
        . "recommendations.adjudicator_phone, "
        . "recommendations.adjudicator_email, "
        . "recommendations.acknowledgement, "
        . "recommendations.date_submitted "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "recommendations.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials['tnid']) . "' "
            . ") "
        . "WHERE recommendations.local_adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator['id']) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'festival_id', 'member_id', 'section_id', 'section_name', 
                'adjudicator_name', 'adjudicator_phone', 'adjudicator_email', 
                'status', 'status_text',
                'acknowledgement', 'date_submitted'),
            'maps'=>array('status_text'=>$maps['recommendation']['status']),
            'utctotz'=>array(
                'date_submitted'=>array('format'=>'M j, Y g:i A', 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1034', 'msg'=>'Unable to load recommendations', 'err'=>$rc['err']));
    }
    $recommendations = isset($rc['recommendations']) ? $rc['recommendations'] : array();

    //
    // Use the last email/phone/name submitted for recommendations
    //
    if( array_key_last($recommendations) != null ) {
        $last_id = array_key_last($recommendations);
        if( isset($recommendations[$last_id]['name']) && $adjudicator['name'] != $recommendations[$last_id]['name'] ) {
            $adjudicator['name'] = $recommendations[$last_id]['name'];
        }
        if( isset($recommendations[$last_id]['phone']) && $adjudicator['phone'] != $recommendations[$last_id]['phone'] ) {
            $adjudicator['phone'] = $recommendations[$last_id]['phone'];
        }
        if( isset($recommendations[$last_id]['email']) && $adjudicator['email'] != $recommendations[$last_id]['email'] ) {
            $adjudicator['email'] = $recommendations[$last_id]['email'];
        }
    }
    foreach($recommendations as $rid => $recommendation) {
        //
        // Check for a delete
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+4)])
            && $request['uri_split'][($request['cur_uri_pos']+4)] == 'delete'
            && $request['uri_split'][($request['cur_uri_pos']+3)] == $recommendation['uuid']
            && isset($_GET['confirm'])
            ) {
            //
            // Remove the recommendation
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationRemove');
            $rc = ciniki_musicfestivals_recommendationRemove($ciniki, $provincials['tnid'], $recommendation['id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'ok', 'blocks'=>[[
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => $rc['err']['msg'],
                    ]]);
            }
            unset($recommendations[$rid]);
            continue;
        }
        elseif( isset($request['uri_split'][($request['cur_uri_pos']+3)])
            && $request['uri_split'][($request['cur_uri_pos']+3)] == $recommendation['uuid']
            ) {
            $recommendation['member_name'] = $member['name'];
            $request['cur_uri_pos'] += 4;
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountRecommendationProcess');
            $rc = ciniki_musicfestivals_wng_accountRecommendationProcess($ciniki, $tnid, $request, [
                'recommendation' => $recommendation,
                'sections' => $sections,
                'local' => $local,
                'provincials' => $provincials,
                'adjudicator' => $adjudicator,
                'member' => $member,
                ]);
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'exit' ) {
                return array('stat'=>'ok', 'blocks'=>[[
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => $rc['err']['msg'],
                    ]]);
            } else {
                return $rc;
            }
        }
        if( $recommendation['status'] == 10 ) {
            $recommendations[$rid]['actions'] = "<a class='button' href='{$base_url}/{$recommendation['uuid']}'>Edit</a>"
                . "<a class='button' href='{$base_url}/{$recommendation['uuid']}/delete'>Delete</a>"
                . "";
        } else {
            $recommendations[$rid]['actions'] = "<a class='button' href='{$base_url}/{$recommendation['uuid']}'>View</a>";
        }
    }

    //
    // Display the list of section to add 
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+3)])
        && $request['uri_split'][($request['cur_uri_pos']+3)] == 'add'
        ) {
        $request['cur_uri_pos'] += 3;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountRecommendationProcess');
        $rc = ciniki_musicfestivals_wng_accountRecommendationProcess($ciniki, $tnid, $request, [
            'sections' => $sections,
            'local' => $local,
            'member' => $member,
            'provincials' => $provincials,
            'adjudicator' => $adjudicator,
            ]);
        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exit' ) {
            return array('stat'=>'ok', 'blocks'=>[[
                'type' => 'msg',
                'level' => 'error',
                'content' => $rc['err']['msg'],
                ]]);
        } else {
            return $rc;
        }
    }

    //
    // Display title
    //
    $blocks[] = [
        'type' => 'title', 
        'title' => $provincials['name'] . ' - Recommendations',
        ];

    //
    // Display submissions and button to Add Submission
    //
    if( count($recommendations) > 0 ) {
        $blocks[] = [
            'type' => 'table',
            'headers' => 'yes',
            'columns' => [
                'section' => ['label' => 'Section', 'field'=>'section_name'],
                'status' => ['label' => 'Status', 'field'=>'status_text'],
                'submitted' => ['label' => 'Submitted', 'field'=>'date_submitted'],
                'actions' => ['label' => '', 'field'=>'actions', 'class'=>'alignright buttons'],
                ],
            'rows' => $recommendations,
            ];
    }

    //
    // Add button
    //
    $blocks[] = [
        'type' => 'buttons',
        'class' => 'aligncenter',
        'items' => [
            ['title' => 'Add Recommendation', 'url' => $base_url . '/add'],
            ],
        ];

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
