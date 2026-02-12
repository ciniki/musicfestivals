<?php
//
// Description
// -----------
// This function will process a new or existing recommendation submission
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountRecommendationProcess(&$ciniki, $tnid, &$request, $args) {

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
    // Load the list of positions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationPositions');
    $rc = ciniki_musicfestivals_recommendationPositions($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $positions = $rc['positions'];

    $sections = $args['sections'];
  
    //
    // Load entries if recommendation specified
    //
    if( isset($args['recommendation']) ) {  
        $recommendation = $args['recommendation'];
        $strsql = "SELECT entries.id, "
            . "entries.status, "
            . "entries.status AS status_text, "
            . "entries.class_id, "
            . "entries.position, "
            . "entries.name, "
            . "entries.mark, "
            . "entries.provincials_reg_id, "
            . "entries.local_reg_id "
            . "FROM ciniki_musicfestival_recommendation_entries AS entries "
            . "WHERE entries.recommendation_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['id']) . "' "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials']['tnid']) . "' "
            . "ORDER BY entries.class_id, position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'status_text', 'class_id', 'position', 'name', 'mark', 
                    'provincials_reg_id', 'local_reg_id'),
                'maps'=>array('status_text'=>$maps['recommendationentry']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1035', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
        }
        $entries = isset($rc['entries']) ? $rc['entries'] : array();

        $recommendation['entries'] = [];
        foreach($entries as $entry) {
            if( isset($recommendation['entries'][$entry['class_id']][$entry['position']]) ) {
                // This error shouldn't happen unless editing an entry that provincials have updated
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1042', 'msg'=>'Second entry for recommendation class position'));
            }
            $recommendation['entries'][$entry['class_id']][$entry['position']] = $entry;
        }
    } 
    //
    // Setup new recommendation
    //
    else {
        $recommendation = [
            'id' => 0,
            'festival_id' => $args['provincials']['id'],
            'member_id' => $args['member']['id'],
            'member_name' => $args['member']['name'],
            'section_id' => 0,
            'section_name' => '',
            'status' => 10,
            'status_text' => 'Draft',
            'adjudicator_name' => $args['adjudicator']['name'],
            'adjudicator_phone' => $args['adjudicator']['phone'],
            'adjudicator_email' => $args['adjudicator']['email'],
            'local_adjudicator_id' => $args['adjudicator']['id'],
            'acknowledgement' => '',
            'date_submitted' => '',
            'entries' => [],
            ];
    }

    if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) 
        && $request['uri_split'][($request['cur_uri_pos']+1)] != ''
        && $recommendation['section_id'] == 0
        ) {
        $section_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
        if( isset($sections[$section_permalink]) ) {
            $recommendation['section_id'] = $sections[$section_permalink]['id'];
            $recommendation['section'] = $sections[$section_permalink];
            $recommendation['section_name'] = $sections[$section_permalink]['name'];
        }
    }

    //
    // Load all existing submissions for member
    //
    $strsql = "SELECT recommendations.section_id, "
        . "entries.class_id, "
        . "entries.position "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials']['tnid']) . "' "
            . ") "
        . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['festival_id']) . "' "
        . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['member_id']) . "' "
        . "AND recommendations.section_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['section_id']) . "' "
        . "AND recommendations.status > 10 "     // Submitted
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['provincials']['tnid']) . "' "
        . "ORDER BY recommendations.section_id, entries.class_id, entries.position "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1048', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $rows = isset($rc['rows']) ? $rc['rows'] : array();
    $existing = [];
    foreach($rows as $row) {
        $existing[$row['class_id']][$row['position']] = 'Already Submitted';
    }

    //
    // Check to see if section selected
    //
    if( $recommendation['section_id'] == 0 ) {
        //
        // Display section picker
        //
        foreach($sections as $sid => $section) {
            $sections[$sid]['buttons'] = "<a class='button' href='{$base_url}/add/{$section['permalink']}'>Submit Recommendations</a>";
        }
        $blocks[] = array(
            'type' => 'table', 
            'section' => 'syllabus', 
            'headers' => 'no',
            'class' => 'fold-at-40 musicfestival-syllabus',
            'columns' => array(
                array('label'=>'Section', 'fold-label'=>'', 'field'=>'name', 'class'=>'section-title'),
                array('label'=>'Buttons', 'fold-label'=>'', 'field'=>'buttons', 'class'=>'alignleft fold-alignleft buttons'),
                ),
            'rows' => $sections,
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    } 
    else {
        foreach($sections as $section) {
            if( $section['id'] == $recommendation['section_id'] ) {
                $recommendation['section'] = $section;
                $recommendation['section_name'] = $section['name'];
                break;
            }
        }
    }
    if( !isset($recommendation['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1038', 'msg'=>'No section specified'));
    }

    //
    // Load the section classes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationClassesLoad');
    $rc = ciniki_musicfestivals_recommendationClassesLoad($ciniki, $args['provincials']['tnid'], $section);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1054', 'msg'=>'', 'err'=>$rc['err']));
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    //
    // Decide what should be displayed
    //
    $display = 'view';
    if( isset($request['uri_split'][$request['cur_uri_pos']])
        && $request['uri_split'][$request['cur_uri_pos']] == 'delete'
        ) {
        $display = 'delete';
    } elseif( $recommendation['status'] < 30 ) {
        $display = 'form';
    }

    //
    // Show the recommendation form, or display the recommendation entries
    //
    if( $display == 'form' ) {
        //
        // Generate the form
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'recommendationFormGenerate');
        $rc = ciniki_musicfestivals_wng_recommendationFormGenerate($ciniki, $args['provincials']['tnid'], $request, [   
            'recommendation' => $recommendation,
            'section' => $section,
            'classes' => $classes,
            'existing' => $existing,
            'adjudicator' => $args['adjudicator'],
            'cancel-url' => $base_url,
//            'save-draft' => 'yes',
            'edit-name' => 'no',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1036', 'msg'=>'Unable to generate form', 'err'=>$rc['err']));
        }
        $form_errors = $rc['form_errors'];
        $form_sections = $rc['form_sections'];
    } else {
        $blocks[] = [
            'type' => 'text',
            'title' => $section['name'],
            'content' => ''
                . ($display == 'view' ? "<b>Submitted On</b>: {$recommendation['date_submitted']}<br/>" : '')
                . "<b>Status</b>: {$recommendation['status_text']}<br/>"
            ];
        //
        // Display the submission
        //
        foreach($classes as $cid => $class) {
            $entries = [];
            foreach($positions as $i => $position) {
                if( isset($recommendation['entries'][$cid][$i]) ) {
                    $entry = $recommendation['entries'][$cid][$i];
                    $entry['position_text'] = $position['label'];
                    $entries[] = $entry;
                }
            }
            if( count($entries) > 0 ) {
                $blocks[] = array(
                    'type' => 'table',
                    'title' => $class['code'] . ' - ' . $class['name'],
                    'headers' => 'yes',
                    'class' => 'fold-at-50',
                    'columns' => array(
                        array('label'=>'Position', 'fold-label'=>'Position: ', 'field'=>'position_text'),
                        array('label'=>'Competitor', 'field'=>'name'),
                        array('label'=>'Status', 'field'=>'status_text'),
                        array('label'=>'Mark', 'fold-label'=>'Mark: ', 'field'=>'mark'),
                        ),
                    'rows' => $entries,
                    );
            }
        }
        if( $display == 'delete' && $recommendation['status'] < 30 ) {
            $blocks[] = [
                'type' => 'buttons',
                'class' => 'alignright',
                'items' => [
                    'delete' => ['text' => 'Delete Draft', 'url'=>"{$base_url}/{$recommendation['uuid']}/delete?confirm"],
                    ],
                ];
        }

        return array('stat'=>'ok', 'blocks'=>$blocks, 'clear'=>'yes', 'stop'=>'yes');
    }

    //
    // Process a submission
    //
    if( $form_errors == '' && isset($_POST['action']) && $_POST['action'] == 'submit' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'recommendationSave');
        $rc = ciniki_musicfestivals_wng_recommendationSave($ciniki, $args['provincials']['tnid'], $request, [
            'recommendation' => $recommendation,
            'form_sections' => $form_sections,
            'existing' => $existing,
            'classes' => $classes,
            'members' => [$recommendation['member_id'] => ['id'=>$recommendation['member_id']]],
//            'save-draft' => isset($_POST['submit']) && $_POST['submit'] == 'Save Draft' ? 'yes' : 'no',
            ]);
        if( $rc['stat'] != 'ok' ) {
            if( isset($rc['form_errors']) ) {
                $form_errors = $rc['form_errors'];
            } else {
                $form_errors = $rc['err']['msg'];
            }
        } else {
            $recommendation = $rc['recommendation'];
            //
            // Email submission to adjudicator and members
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationEmail');
            $rc = ciniki_musicfestivals_recommendationEmail($ciniki, $args['provincials']['tnid'], [
                'recommendation' => $recommendation,
                'classes' => $classes,
                'adjudicator-subject' => 'Thank you for your submission',
                'member-subject' => "We have received adjudicator recommendations for " . $recommendation['section_name'],
                'members' => isset($args['member']['customers']) ? $args['member']['customers'] : [],
//                'notify-subject' => "Recommendations for " . $recommendation['member_name'] . ' in ' . $recommendation['section_name'],
//                'nofity-emails' => isset($s['notify-emails']) ? $s['notify-emails'] : '',
                'email-type' => 'submitted',
                ]);
            if( $rc['stat'] != 'ok' ) {
                $blocks[] = array(
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => $rc['err']['msg'],
                    );
            }

            header("Location: $base_url");
            return array('stat'=>'exit');
        }
    }

    //
    // Display the form
    //
    if( $form_errors != '' ) {
        $form_errors = "<b>Please correct the following errors:</b><br/><br/>" . preg_replace("/\n/", '<br/>', $form_errors);
    }
    $blocks[] = array(
        'type' => $section['description'] != '' ? 'text' : 'title',
        'level' => 2,
        'title' => $section['name'] . ' Recommendations',
        'content' => $section['description'],
        );
//    return array('stat'=>'ok', 'blocks'=>[[
//        'type' => 'html',
//        'html' => '<pre>' . print_r($form_sections, true) . '</pre>',
//        ]]);
    $blocks[] = array(
        'type' => 'form',
        'form-id' => 'section-form',
        'guidelines' => 'Please submit all your recommendations for ' . $section['name'] . ' at once.',
        'class' => 'limit-width limit-width-90 musicfestival-recommendations',
        'problem-list' => $form_errors,
        'section-selector' => 'yes',
        'form-sections' => $form_sections,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks, 'clear'=>'yes', 'stop'=>'yes');
}
?>
