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
function ciniki_musicfestivals_wng_accountAdjudicationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'videoProcess');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestivaladjudications';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/musicfestivaladjudications");
        return array('stat'=>'exit');
    }

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.395', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load the festival details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE ciniki_musicfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_musicfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.396', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

    //
    // Load the adjudicator
    //
    $strsql = "SELECT id "  
        . "FROM ciniki_musicfestival_adjudicators "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.397', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( isset($rc['adjudicator']['id']) ) {
        $adjudicator = 'yes';
        $adjudicator_id = $rc['adjudicator']['id'];
    }

    //
    // Load the schedule sections, divisions, timeslots, classes, registrations
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.name AS section_name, "
        . "sections.adjudicator1_id, "
        . "sections.adjudicator2_id, "
        . "sections.adjudicator3_id, "
        . "divisions.id AS division_id, "
        . "divisions.uuid AS division_uuid, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.uuid AS timeslot_uuid, "
        . "IF(timeslots.name='', IFNULL(class1.name, ''), timeslots.name) AS timeslot_name, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.class1_id, "
        . "timeslots.class2_id, "
        . "timeslots.class3_id, "
        . "IFNULL(class1.name, '') AS class1_name, "
        . "IFNULL(class2.name, '') AS class2_name, "
        . "IFNULL(class3.name, '') AS class3_name, "
//        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid AS reg_uuid, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.video1_url, "
        . "registrations.video2_url, "
        . "registrations.video3_url, "
        . "registrations.music1_orgfilename, "
        . "registrations.music2_orgfilename, "
        . "registrations.music3_orgfilename, "
        . "IFNULL(comments.id, 0) AS comment_id, "
        . "IFNULL(comments.comments, '') AS comments, "
        . "IFNULL(comments.grade, '') AS grade, "
        . "IFNULL(comments.score, '') AS score, "
        . "regclass.flags AS reg_class_flags, "
        . "regclass.name AS reg_class_name "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "sections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.class1_id > 0 "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
            . "timeslots.class1_id = class1.id " 
            . "AND class1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class2 ON ("
            . "timeslots.class3_id = class2.id " 
            . "AND class2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS class3 ON ("
            . "timeslots.class3_id = class3.id " 
            . "AND class3.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "(timeslots.class1_id = registrations.class_id "  
                . "OR timeslots.class2_id = registrations.class_id "
                . "OR timeslots.class3_id = registrations.class_id "
                . ") "
            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_comments AS comments ON ("
            . "registrations.id = comments.registration_id "
            . "AND comments.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS regclass ON ("
            . "registrations.class_id = regclass.id "
            . "AND regclass.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND ("
            . "sections.adjudicator1_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . "OR sections.adjudicator2_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . "OR sections.adjudicator3_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
            . ") "
        . "";
    $strsql .= "ORDER BY section_name, divisions.division_date, division_id, slot_time, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_uuid', 
            'fields'=>array('id'=>'division_id', 'uuid'=>'division_uuid', 
                'name'=>'division_name', 'date'=>'division_date_text', 'address',
                )),
        array('container'=>'timeslots', 'fname'=>'timeslot_uuid', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name',
                )),
        array('container'=>'registrations', 'fname'=>'reg_uuid', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name', 'title1', 'title2', 'title3',
                'video1_url', 'video2_url', 'video3_url', 
                'music1_orgfilename', 'music2_orgfilename', 'music3_orgfilename', 
                'reg_class_flags', 'class_name'=>'reg_class_name', 'comment_id', 'comments', 'grade', 'score')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();


    /* Check for division and timeslot */
    if( isset($request['uri_split'][($request['cur_uri_pos']+3)]) 
        && isset($divisions[$request['uri_split'][($request['cur_uri_pos']+2)]]['timeslots'][$request['uri_split'][($request['cur_uri_pos']+3)]])
        ) {
        $timeslot = $divisions[$request['uri_split'][($request['cur_uri_pos']+2)]]['timeslots'][$request['uri_split'][($request['cur_uri_pos']+3)]];
        $display = 'timeslot';

        //
        // Check for form submit
        //
        if( isset($_POST['action']) && $_POST['action'] == 'submit' ) {
            //
            // Update the comments for the adjudicator and registrations
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'adjudicatorCommentsUpdate');
            $rc = ciniki_musicfestivals_wng_adjudicatorCommentsUpdate($ciniki, $tnid, $request, array(
                'registrations' => $timeslot['registrations'],
                'adjudicator_id' => $adjudicator_id,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.394', 'msg'=>'Unable to update comments', 'err'=>$rc['err']));
            }

            header("Location: {$request['ssl_domain_base_url']}/account/musicfestivaladjudications");
            $request['session']['account-musicfestivals-adjudications-saved'] = 'yes';
            return array('stat'=>'exit');
        }

        //
        // Check for download
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+5)]) 
            && isset($timeslot['registrations'][$request['uri_split'][($request['cur_uri_pos']+4)]])
            && in_array($request['uri_split'][($request['cur_uri_pos']+5)], ['music1.pdf','music2.pdf','music3.pdf'])
            ) {
            $registration = $timeslot['registrations'][$request['uri_split'][($request['cur_uri_pos']+4)]];

            //
            // Get the tenant storage directory
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
            $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $storage_filename = $rc['storage_dir'] . '/ciniki.musicfestivals/files/' 
                . $registration['uuid'][0] . '/' . $registration['uuid'] . '_' 
                . preg_replace("/\.pdf/", "", $request['uri_split'][($request['cur_uri_pos']+5)]);
            if( !file_exists($storage_filename) ) {
                $blocks[] = array(
                    'type' => 'msg',
                    'level' => 'error',
                    'content' => 'File not found',
                    );
                return array('stat'=>'ok', 'blocks'=>$blocks);
            }

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            // Set mime header
            $finfo = finfo_open(FILEINFO_MIME);
            if( $finfo ) { 
                header('Content-Type: ' . finfo_file($finfo, $storage_filename)); 
            }
            // Open file in browser
            header('Content-Disposition: inline;filename="' 
                . preg_replace("/\.pdf/", '', $registration[$request['uri_split'][($request['cur_uri_pos']+5)] . '_orgfilename']) 
                . '"');
            // Download file to filesystem
            header('Content-Length: ' . filesize($storage_filename));
            header('Cache-Control: max-age=0');

            $fp = fopen($storage_filename, 'rb');
            fpassthru($fp);

            return array('stat'=>'exit');
        }
    }


/*    $blocks[] = array(
        'type' => 'html',
        'html' => '<pre>' . print_r($divisions, true) . '</pre>',
        ); */


    //
    // Prepare any errors
    //
    $form_errors = '';
    if( isset($errors) && count($errors) > 0 ) {
        foreach($errors as $err) {
            $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
        }
    }

    if( $form_errors != '' ) { 
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => $form_errors,
            );
    }
    
    if( isset($request['session']['account-musicfestivals-adjudications-saved']) ){
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'success',
            'content' => 'Comments saved',
            );
        unset($request['session']['account-musicfestivals-adjudications-saved']);
    }

    if( $display == 'timeslot' ) {
        $sections = array();
        foreach($timeslot['registrations'] as $registration) {
            $section = array(    
                'id' => 'section-' . $registration['id'],
                'label' => $registration['name'] . ' - ' . $registration['class_name'],
                'fields' => array(),
                );
            $num_titles = 1;
            if( ($registration['reg_class_flags']&0x4000) == 0x4000 ) {
                $num_titles = 3;
            } elseif( ($registration['reg_class_flags']&0x1000) == 0x1000 ) {
                $num_titles = 2;
            }
            for($i = 1; $i <= $num_titles; $i++) {
                if( $registration["title{$i}"] != '' ) {
                    $section['fields']["title{$i}"] = array(
                        'id' => "title{$i}",
                        'ftype' => 'content',
//                        'ftype' => 'text',
                        'label' => ($i == 2 ? '2nd ' : ($i == 3 ? '3rd ' : ' ')) . 'Title',
                        'editable' => 'no',
                        'size' => 'medium',
                        'description' => $registration["title{$i}"],
//                        'value' => $registration["title{$i}"],
                        );
                    if( $registration["music{$i}_orgfilename"] != '' ) {
                        $download_url = "{$request['ssl_domain_base_url']}/account/musicfestivaladjudications"
                            . '/' . $request['uri_split'][($request['cur_uri_pos']+2)]
                            . '/' . $request['uri_split'][($request['cur_uri_pos']+3)]
                            . '/' . $registration['uuid']
                            . '/music' . $i . '.pdf'
                            . "";
                        $section['fields']["music{$i}_orgfilename"] = array(
                            'id' => "music{$i}_orgfilename",
                            'ftype' => 'button',
                            'label' => 'Music',
                            'size' => 'medium',
                            'target' => '_blank',
                            'href' => $download_url,
                            'value' => $registration["music{$i}_orgfilename"],
                            );
                    } else {
                        $section['fields']["music{$i}_orgfilename"] = array(
                            'id' => "music{$i}_orgfilename",
                            'ftype' => 'content',
                            'label' => ($i == 2 ? '2nd ' : ($i == 3 ? '3rd ' : ' ')) . 'Music PDF',
                            'size' => 'medium',
                            'description' => "No music file provided",
                            );
                    }
                    if( isset($registration["video{$i}_url"]) && $registration["video{$i}_url"] != '' ) {
                        $section['fields']["reg{$registration['id']}-video{$i}"] = array(   
                            'id' => "reg{$registration['id']}-video{$i}",
                            'ftype' => 'videocontent',
                            'size' => 'large',
                            'label' => ($i == 2 ? '2nd ' : ($i == 3 ? '3rd ' : ' ')) . 'Video',
                            'url' => $registration["video{$i}_url"],
                            );
                    }
                }
            }
            $section['fields']["{$registration['id']}-comments"] = array(
                'id' => "{$registration['id']}-comments",
                'ftype' => 'textarea',
                'label' => 'Comments',
                'onkeyup' => 'fieldUpdated()',
                'size' => 'large',
                'value' => $registration['comments'],
                );
            $section['fields']["{$registration['id']}-score"] = array(
                'id' => "{$registration['id']}-score",
                'ftype' => 'text',
                'onkeyup' => 'fieldUpdated()',
                'size' => 'small',
                'label' => 'Mark',
                'value' => $registration['score'],
                );
            $sections[$registration['id']] = $section;
        }
        $sections['submit'] = array(
            'id' => 'submit',
            'class' => 'buttons',
            'label' => '',
            'fields' => array(
                'timeslot_id' => array(
                    'id' => 'timeslot_id',
                    'ftype' => 'hidden',
                    'value' => $timeslot['id'],
                    ),
                'customer_id' => array(
                    'id' => 'customer_id',
                    'ftype' => 'hidden',
                    'value' => $request['session']['customer']['id'],
                    ),
                'cancel' => array(
                    'id' => 'cancel',
                    'ftype' => 'cancel',
                    'label' => 'Back',
                    ),
                'submit' => array(
                    'id' => 'submit',
                    'ftype' => 'submit',
                    'label' => 'Save',
                    ),
                ),
            );
        $js = ""
            . "var fSaveTimer=null;"
            . "function fieldUpdated(){"
                . "if(fSaveTimer==null){"
                    . "fSaveTimer=setTimeout(fSave, 10000);"
                . "}"
            . "}"
            . "function fSave(){"
                . "clearTimeout(fSaveTimer);"
                . "fSaveTimer=null;"
                . "C.form.qSave();"
            . "}"
            . "";
        $blocks[] = array(
            'type' => 'form',
            'id' => 'adjudication-form',
            'title' => $timeslot['name'],
            'class' => 'limit-width limit-width-60 musicfestival-adjudications',
            'form-sections' => $sections,
            'js' => $js,
            'api-save-url' => $request['api_url'] . '/ciniki/musicfestivals/adjudicationsSave',
            'last-saved-msg' => '',
            'api-args' => array(
                ),
            );
//        $blocks[] = array(
//            'type' => 'html',
//            'html' => '<pre>' . print_r($timeslot, true) . '</pre>',
//            );

    } else {
        //
        // Setup the open button and status
        //
        foreach($divisions as $division) {
            if( isset($division['timeslots']) && count($division['timeslots']) > 0 ) {
                foreach($division['timeslots'] as $tid => $timeslot) {
                    $num_completed = 0;
                    if( isset($timeslot['registrations']) ) {
                        foreach($timeslot['registrations'] as $rid => $registration) {
                            if( $registration['comments'] != '' && $registration['score'] != '' ) {
                                $num_completed++;
                            }
                        }
                    } else {
                        $timeslot['registrations'] = array();
                    }
                    $division['timeslots'][$tid]['status'] = $num_completed . ' of ' . count($timeslot['registrations']);
                    $division['timeslots'][$tid]['actions'] = "<a class='button' href='{$base_url}/{$division['uuid']}/{$timeslot['permalink']}'>Open</a>";
                }

                $blocks[] = array(
                    'type' => 'table', 
                    'title' => $division['name'], 
                    'section' => 'musicfestival-adjudications limit-width limit-width-60', 
                    'columns' => array(
                        array('label'=>'Name', 'field'=>'name', 'class'=>''),
                        array('label'=>'Completed', 'field'=>'status', 'class'=>'aligncenter'),
                        array('label'=>'', 'field'=>'actions', 'class'=>'aligncenter'),
                        ),
                    'rows'=>$division['timeslots'],
                    );
            }
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
