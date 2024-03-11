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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

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
    // Setup placement autofills
    //
    $festival['comments-placement-autofills'] = array();
    if( isset($festival['comments-placement-autofill']) && $festival['comments-placement-autofill'] != '' ) {
        $placements = explode(',', $festival['comments-placement-autofill']);
        foreach($placements as $p) {
            list($mark, $text) = explode(':', $p);
            $festival['comments-placement-autofills'][trim($mark)] = trim($text);
        }
    }

    //
    // Setup level autofills
    //
    $festival['comments-level-autofills'] = array();
    if( isset($festival['comments-level-autofill']) && $festival['comments-level-autofill'] != '' ) {
        $levels = explode(',', $festival['comments-level-autofill']);
        foreach($levels as $p) {
            list($mark, $text) = explode(':', $p);
            $festival['comments-level-autofills'][trim($mark)] = trim($text);
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
    $strsql = "SELECT ssections.id AS section_id, "
        . "ssections.name AS section_name, "
        . "ssections.adjudicator1_id, "
        . "ssections.adjudicator2_id, "
        . "ssections.adjudicator3_id, "
        . "divisions.id AS division_id, "
        . "divisions.uuid AS division_uuid, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
        . "timeslots.id AS timeslot_id, "
        . "timeslots.uuid AS timeslot_uuid, "
        . "timeslots.name AS timeslot_name, "
//        . "IF(timeslots.name='', IFNULL(class1.name, ''), timeslots.name) AS timeslot_name, "
        . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
//        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.uuid AS reg_uuid, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.title1, "
        . "registrations.title2, "
        . "registrations.title3, "
        . "registrations.title4, "
        . "registrations.title5, "
        . "registrations.title6, "
        . "registrations.title7, "
        . "registrations.title8, "
        . "registrations.composer1, "
        . "registrations.composer2, "
        . "registrations.composer3, "
        . "registrations.composer4, "
        . "registrations.composer5, "
        . "registrations.composer6, "
        . "registrations.composer7, "
        . "registrations.composer8, "
        . "registrations.movements1, "
        . "registrations.movements2, "
        . "registrations.movements3, "
        . "registrations.movements4, "
        . "registrations.movements5, "
        . "registrations.movements6, "
        . "registrations.movements7, "
        . "registrations.movements8, "
        . "registrations.video_url1, "
        . "registrations.video_url2, "
        . "registrations.video_url3, "
        . "registrations.video_url4, "
        . "registrations.video_url5, "
        . "registrations.video_url6, "
        . "registrations.video_url7, "
        . "registrations.video_url8, "
        . "registrations.music_orgfilename1, "
        . "registrations.music_orgfilename2, "
        . "registrations.music_orgfilename3, "
        . "registrations.music_orgfilename4, "
        . "registrations.music_orgfilename5, "
        . "registrations.music_orgfilename6, "
        . "registrations.music_orgfilename7, "
        . "registrations.music_orgfilename8, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "registrations.level, "
        . "registrations.comments, "
//        . "IFNULL(comments.id, 0) AS comment_id, "
//        . "IFNULL(comments.comments, '') AS comments, "
//        . "IFNULL(comments.grade, '') AS grade, "
//        . "IFNULL(comments.score, '') AS score, "
        . "classes.flags AS class_flags, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS syllabus_section_name "
        . "FROM ciniki_musicfestival_schedule_sections AS ssections "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "ssections.id = divisions.ssection_id " 
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.class1_id > 0 "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
/*        . "LEFT JOIN ciniki_musicfestival_classes AS class1 ON ("
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
            . ") " */
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
//            . "(timeslots.class1_id = registrations.class_id "  
//                . "OR timeslots.class2_id = registrations.class_id "
//                . "OR timeslots.class3_id = registrations.class_id "
//                . ") "
//            . "AND ((timeslots.flags&0x01) = 0 OR timeslots.id = registrations.timeslot_id) "
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
//        . "LEFT JOIN ciniki_musicfestival_comments AS comments ON ("
//            . "registrations.id = comments.registration_id "
//            . "AND comments.adjudicator_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
//            . "AND comments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . ") "
        . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
            . "registrations.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
            . "classes.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ssections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND ssections.adjudicator1_id = '" . ciniki_core_dbQuote($ciniki, $adjudicator_id) . "' "
        . "ORDER BY section_name, divisions.division_date, division_id, slot_time, registrations.timeslot_sequence, registrations.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_uuid', 
            'fields'=>array('id'=>'division_id', 'uuid'=>'division_uuid', 
                'name'=>'division_name', 'date'=>'division_date_text', 'address',
                )),
        array('container'=>'timeslots', 'fname'=>'timeslot_uuid', 
            'fields'=>array('id'=>'timeslot_id', 'permalink'=>'timeslot_uuid', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 
                'description', 
                )),
        array('container'=>'registrations', 'fname'=>'reg_uuid', 
            'fields'=>array('id'=>'reg_id', 'uuid'=>'reg_uuid', 'name'=>'display_name', 'public_name', 
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                'music_orgfilename1', 'music_orgfilename2', 'music_orgfilename3', 'music_orgfilename4', 
                'music_orgfilename5', 'music_orgfilename6', 'music_orgfilename7', 'music_orgfilename8',
                'class_flags', 'min_titles', 'max_titles', 'class_name',
                'class_code', 'class_name', 'category_name', 'syllabus_section_name',
                'mark', 'placement', 'level', 'comments',
                )),
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
                . $registration[preg_replace("/music([1-3])\.pdf/", 'music_orgfilename$1', $request['uri_split'][($request['cur_uri_pos']+5)])]
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
            $class_name = $registration['class_name'];
            if( isset($festival['comments-class-format']) 
                && $festival['comments-class-format'] == 'code-section-category-class' 
                ) {
                $class_name = $registration['class_code'] . ' - ' . $registration['syllabus_section_name'] . ' - ' . $registration['category_name'] . ' - ' . $registration['class_name']; 
            } elseif( isset($festival['comments-class-format']) 
                && $festival['comments-class-format'] == 'section-category-class' 
                ) {
                $class_name = $registration['syllabus_section_name'] . ' - ' . $registration['category_name'] . ' - ' . $registration['class_name']; 
            } elseif( isset($festival['comments-class-format']) 
                && $festival['comments-class-format'] == 'code-category-class' 
                ) {
                $class_name = $registration['class_code'] . ' - ' . $registration['category_name'] . ' - ' . $registration['class_name']; 
            } elseif( isset($festival['comments-class-format']) 
                && $festival['comments-class-format'] == 'category-class' 
                ) {
                $class_name = $registration['category_name'] . ' - ' . $registration['class_name']; 
            } else {
                $class_name = $registration['class_name']; 
            }
            

            $section = array(    
                'id' => 'section-' . $registration['id'],
                'label' => $registration['name'] . ' - ' . $class_name,
                'fields' => array(),
                );
            for($i = 1; $i <= $registration['max_titles']; $i++) {
                if( $registration["title{$i}"] != '' ) {
                    $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                    if( isset($rc['title']) ) {
                        $registration["title{$i}"] = $rc['title'];
                    }
                    $section['fields']["title{$i}"] = array(
                        'id' => "title{$i}",
                        'ftype' => 'content',
                        'label' => ($registration['max_titles'] > 1 ? 'Title #' . $i : 'Title'),
                        'editable' => 'no',
                        'size' => 'medium',
                        'description' => $registration["title{$i}"],
                        );
                    if( $registration["music_orgfilename{$i}"] != '' ) {
                        $download_url = "{$request['ssl_domain_base_url']}/account/musicfestivaladjudications"
                            . '/' . $request['uri_split'][($request['cur_uri_pos']+2)]
                            . '/' . $request['uri_split'][($request['cur_uri_pos']+3)]
                            . '/' . $registration['uuid']
                            . '/music' . $i . '.pdf'
                            . "";
                        $section['fields']["music_orgfilename{$i}"] = array(
                            'id' => "music_orgfilename{$i}",
                            'ftype' => 'button',
                            'label' => 'Music',
                            'size' => 'medium',
                            'target' => '_blank',
                            'href' => $download_url,
                            'value' => $registration["music_orgfilename{$i}"],
                            );
                    } else {
                        $section['fields']["music_orgfilename{$i}"] = array(
                            'id' => "music_orgfilename{$i}",
                            'ftype' => 'content',
                            'label' => 'Music PDF',
                            'size' => 'medium',
                            'description' => "No music file provided",
                            );
                    }
                    if( isset($registration["video_url{$i}"]) && $registration["video_url{$i}"] != '' ) {
                        $section['fields']["reg{$registration['id']}-video{$i}"] = array(   
                            'id' => "reg{$registration['id']}-video{$i}",
                            'ftype' => 'videocontent',
                            'size' => 'large',
                            'label' => ($registration['max_titles'] > 1 ? 'Video #' . $i : 'Video'),
                            'url' => $registration["video_url{$i}"],
                            );
                    }
                }
            }
            $section['fields']["{$registration['id']}-comments"] = array(
                'id' => "{$registration['id']}-comments",
                'ftype' => 'textarea',
                'class' => 'field-comments',
                'label' => 'Comments',
                'onkeyup' => 'fieldUpdated()',
                'size' => 'large',
                'value' => $registration['comments'],
                );
            if( isset($festival['comments-mark-adjudicator']) && $festival['comments-mark-adjudicator'] == 'yes' ) {
                $label = 'Mark';
                if( isset($festival['comments-mark-label']) && $festival['comments-mark-label'] != '' ) {
                    $label = $festival['comments-mark-label'];
                }
                $section['fields']["{$registration['id']}-mark"] = array(
                    'id' => "{$registration['id']}-mark",
                    'class' => 'field-comments-mark',
                    'ftype' => 'text',
                    'onkeyup' => "fieldMarkUpdated({$registration['id']})",
                    'size' => 'small',
                    'label' => $label,
                    'value' => $registration['mark'],
                    );
            }
            if( isset($festival['comments-placement-adjudicator']) && $festival['comments-placement-adjudicator'] == 'yes' ) {
                $label = 'Placement';
                if( isset($festival['comments-placement-label']) && $festival['comments-placement-label'] != '' ) {
                    $label = $festival['comments-placement-label'];
                }
                $section['fields']["{$registration['id']}-placement"] = array(
                    'id' => "{$registration['id']}-placement",
                    'ftype' => 'text',
                    'class' => 'field-comments-placement',
                    'onkeyup' => 'fieldUpdated()',
                    'size' => 'small',
                    'label' => $label,
                    'value' => $registration['placement'],
                    );
            }
            if( isset($festival['comments-level-adjudicator']) && $festival['comments-level-adjudicator'] == 'yes' ) {
                $label = 'Level';
                if( isset($festival['comments-level-label']) && $festival['comments-level-label'] != '' ) {
                    $label = $festival['comments-level-label'];
                }
                $section['fields']["{$registration['id']}-level"] = array(
                    'id' => "{$registration['id']}-level",
                    'ftype' => 'text',
                    'class' => 'field-comments-level',
                    'onkeyup' => 'fieldUpdated()',
                    'size' => 'small',
                    'label' => $label,
                    'value' => $registration['level'],
                    );
            }
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
                    . "fSaveTimer=setTimeout(fSave, 30000);"
                . "}"
            . "}"
            . "function fSave(){"
                . "clearTimeout(fSaveTimer);"
                . "fSaveTimer=null;"
                . "C.form.qSave();"
            . "}"
            . "";
        if( isset($festival['comments-placement-autofills']) && count($festival['comments-placement-autofills']) > 0 
            && isset($festival['comments-placement-adjudicator']) && $festival['comments-placement-adjudicator'] == 'yes' 
            ) {
            $js .= "var paf=" . json_encode($festival['comments-placement-autofills']) . ";";
        }
        if( isset($festival['comments-level-autofills']) && count($festival['comments-level-autofills']) > 0 
            && isset($festival['comments-level-adjudicator']) && $festival['comments-level-adjudicator'] == 'yes' 
            ) {
            $js .= "var laf=" . json_encode($festival['comments-level-autofills']) . ";";
        }
        $js .= "function fieldMarkUpdated(rid){"
            . "var m=C.gE('f-'+rid+'-mark').value;"
            . "";
        if( isset($festival['comments-placement-autofills']) && count($festival['comments-placement-autofills']) > 0 
            && isset($festival['comments-placement-adjudicator']) && $festival['comments-placement-adjudicator'] == 'yes' 
            ) {
            $js .= "var p=C.gE('f-'+rid+'-placement');"
                . "for(var i in paf){"
                    . "if(parseInt(m)>=parseInt(i)){"
                        . "p.value=paf[i];"
                    . "}"
                . "}";
        }
        if( isset($festival['comments-level-autofills']) && count($festival['comments-level-autofills']) > 0 
            && isset($festival['comments-level-adjudicator']) && $festival['comments-level-adjudicator'] == 'yes' 
            ) {
            $js .= "var l=C.gE('f-'+rid+'-level');"
                . "for(var i in laf){"
                    . "if(parseInt(m)>=parseInt(i)){"
                        . "l.value=laf[i];"
                    . "}"
                . "}";
        }
        $js .= "if(fSaveTimer==null){"
                . "fSaveTimer=setTimeout(fSave, 10000);"
            . "}"
            . "}";

        $blocks[] = array(
            'type' => 'form',
            'id' => 'adjudication-form',
            'title' => $timeslot['name'] != '' ? $timeslot['name'] : '',
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
                            if( (!isset($division['timeslots']['name']) || $division['timeslots']['name'] == '') 
                                && isset($registration['class_name']) 
                                ) {
                                $division['timeslots'][$tid]['name'] = $division['date'] . ' - ' . $timeslot['time'];
                            }
                            if( $registration['comments'] != '' && $registration['mark'] != '' ) {
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
