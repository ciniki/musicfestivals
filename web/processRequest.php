<?php
//
// Description
// -----------
// This function will process a web request for the Music Festival module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get music festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_musicfestivals_web_processRequest(&$ciniki, $settings, $tnid, $args) {

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
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.177', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );
    $uri_split = $args['uri_split'];

    //
    // Check for music festival permalink, for archived festivals
    //
    $festival_id = 0;
/*    $festival = array(
        'flags' => 0,
        'settings' => array(
            'age-restriction-msg' => '',
            ),
        ); */
    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        //
        // Check if a musicfestival
        //
        $strsql = "SELECT id, name, flags, "
            . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $uri_split[0]) . "' "
            . "ORDER BY start_date DESC "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['festival']) ) {
            $festival_id = $rc['festival']['id'];
            $festival = $rc['festival'];
            $festival['settings'] = array(
                'age-restriction-msg' => '',
                );
            $uri_split = shift($uri_split);
            $page['breadcrumbs'][] = array('name'=>$rc['festival']['name'], 'url'=>$args['base_url'] . '/' . $uri_split[0]);
        }
    }

    //
    // No festival specified on the url, load the specified one in the settings, or find the more recent.
    //
    if( $festival_id == 0 ) {
        //
        // Load the festival name
        //
        $strsql = "SELECT id, name, flags, "
            . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['festival']) ) {
            $festival_id = $rc['festival']['id'];
            $festival = $rc['festival'];

            $page['breadcrumbs'][] = array('name'=>$rc['festival']['name'], 'url'=>$args['base_url']);
        }
    }

    //
    // Check if no festival found
    //
    if( $festival_id == 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.39', 'msg'=>'We could not find the requested Music Festival. Please try again or contact us for more information.'));
    }

    //
    // Load the settings for the festival
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_musicfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.196', 'msg'=>'We could not find the requested Music Festival. Please try again or contact us for more information.'));
    }
    foreach($rc['settings'] as $k => $v) {
        $festival['settings'][$k] = $v;
    }

    //
    // Check if customer is logged in and an adjudicator
    //
    $adjudicator = 'no';
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $strsql = "SELECT id "  
            . "FROM ciniki_musicfestival_adjudicators "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'adjudicator');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.163', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
        }
        if( isset($rc['adjudicator']['id']) ) {
            $adjudicator = 'yes';
            $adjudicator_id = $rc['adjudicator']['id'];
        }
    }

    //
    // Get the sponsors for the festival
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sponsors']) 
        && ($ciniki['tenant']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'web', 'sponsorRefList');
        $rc = ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $tnid, 
            'ciniki.musicfestivals.festival', $festival_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sponsors']) ) {
            $sponsors = $rc['sponsors'];
        }
    }

    //
    // Check if file to download
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'download' && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'web', 'fileDownload');
        $rc = ciniki_musicfestivals_web_fileDownload($ciniki, $ciniki['request']['tnid'], $festival_id, $args['uri_split'][1]);
        if( $rc['stat'] == 'ok' ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            $file = $rc['file'];
            if( $file['extension'] == 'pdf' ) {
                header('Content-Type: application/pdf');
            }
//          header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
            header('Content-Length: ' . strlen($file['binary_content']));
            header('Cache-Control: max-age=0');

            print $file['binary_content'];
            exit;
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.63', 'msg'=>'The file you requested does not exist.'));
    }

    //
    // Decide what should be displayed, default to about page
    //
    $display = 'about';
    if( isset($uri_split[0]) ) {
        if( $uri_split[0] == 'about' ) {
            $display = 'about';
        } elseif( $uri_split[0] == 'adjudicators' ) {
            $display = 'adjudicators';
            $adjudicator_permalink = $uri_split[0];
        } elseif( $uri_split[0] == 'registrations' ) {
            $display = 'registrations';
            array_shift($uri_split); 
        } elseif( $uri_split[0] == 'adjudications' ) {
            $display = 'adjudications';
            array_shift($uri_split); 
        } else {
            $strsql = "SELECT id, name, permalink, primary_image_id AS image_id, synopsis, description "
                . "FROM ciniki_musicfestival_sections "
                . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $uri_split[0]) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND (flags&0x01) = 0 "   // Make sure visible on website
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['section']) ) {
                $section = $rc['section'];
                $display = 'section';
                array_shift($uri_split);
            } else {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.48', 'msg'=>'We could not find the request page.'));
            }
        }
    }

    //
    // Load the details for the festival, and display the main page.
    //
    if( $display == 'about' ) {
        $strsql = "SELECT id, name, start_date, end_date, status, flags, primary_image_id, description "
            . "FROM ciniki_musicfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['festival']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.49', 'msg'=>'We could not find the request page.'));
        }
        $festival = $rc['festival'];
       
        if( isset($festival['primary_image_id']) && $festival['primary_image_id'] > 0 ) {
            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$festival['primary_image_id']);
        }

        $content = $festival['description'];
        $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$content);

        //
        // Get any files
        //
        $strsql = "SELECT id, name, permalink, extension, description "
            . "FROM ciniki_musicfestival_files "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_musicfestival_files.webflags&0x01) > 0 "       // Make sure file is to be visible
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'extension', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $page['blocks'][] = array('type'=>'files', 'base_url'=>$args['base_url'] . '/download', 'files'=>$rc['files']);
        }
    }

    //
    // Process the registrations page
    //
    elseif( $display == 'registrations' ) {
        $page['breadcrumbs'][] = array('name'=>'Registrations', 'url'=>$args['base_url'] . '/registrations');

        $args['uri_split'] = $uri_split;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'web', 'processRequestRegistrations');
        $rc = ciniki_musicfestivals_web_processRequestRegistrations($ciniki, $settings, $tnid, array(
            'uri_split' => $uri_split,
            'festival_id' => $festival_id,
            'festival_flags' => $festival['flags'],
            'earlybird' => $festival['earlybird'],
            'settings' => $festival['settings'],
            'base_url' => $args['base_url'] . '/registrations',
            'ssl_domain_base_url' => $args['ssl_domain_base_url'] . '/registrations',
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $block) {
                $page['blocks'][] = $block;
            }
        }
    }

    //
    // Display the adjudications page
    //
    elseif( $display == 'adjudications' && $adjudicator == 'yes' ) {
        $page['breadcrumbs'][] = array('name'=>'Adjudications', 'url'=>$args['base_url'] . '/adjudications');

        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'web', 'processRequestAdjudications');
        $rc = ciniki_musicfestivals_web_processRequestAdjudications($ciniki, $settings, $tnid, array(
            'uri_split' => $uri_split,
            'adjudicator_id' => $adjudicator_id,
            'festival_id' => $festival_id,
            'festival_flags' => $festival['flags'],
            'earlybird' => $festival['earlybird'],
            'settings' => $festival['settings'],
            'base_url' => $args['base_url'] . '/adjudications',
            'ssl_domain_base_url' => $args['ssl_domain_base_url'] . '/adjudications',
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $block) {
                $page['blocks'][] = $block;
            }
        }
    }

/*    elseif( $display == 'section' && $adjudicator == 'yes' && isset($uri_split[0]) && $uri_split[0] != '' ) {
        $display_name = urldecode($uri_split[0]);
        error_log($display_name);
        $base_url = $args['base_url'] . '/' . $section['permalink'];
        $page['breadcrumbs'][] = array('name'=>$section['name'], 'url'=>$base_url);
        $page['breadcrumbs'][] = array('name'=>$display_name, 'url'=>$base_url . '/'. $uri_split[0]);

        //
        // Get the registrations for the section
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.uuid, "
            . "registrations.festival_id, "
            . "sections.id AS section_id, "
            . "registrations.teacher_customer_id, "
            . "teachers.display_name AS teacher_name, "
            . "registrations.billing_customer_id, "
            . "registrations.rtype, "
            . "registrations.rtype AS rtype_text, "
            . "registrations.status, "
            . "registrations.status AS status_text, "
            . "registrations.display_name, "
            . "registrations.class_id, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "registrations.title1, "
            . "registrations.perf_time1, "
            . "FORMAT(registrations.fee, 2) AS fee, "
            . "registrations.payment_type "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_customers AS teachers ON ("
                . "registrations.teacher_customer_id = teachers.id "
                . "AND teachers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
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
            . "WHERE registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND registrations.display_name = '" . ciniki_core_dbQuote($ciniki, $display_name) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($section['id']) && $section['id'] > 0 ) {
            $strsql .= "HAVING section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' ";
        }
        $strsql .= "ORDER BY registrations.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'festival_id', 'teacher_customer_id', 'teacher_name', 'billing_customer_id', 'rtype', 'rtype_text', 'status', 'status_text', 'display_name', 
                    'class_id', 'class_code', 'class_name', 'title1', 'perf_time1', 'fee', 'payment_type'),
                ),
            )); 
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
        foreach($registrations as $rid => $reg) {
            $registrations[$rid]['class_title'] = '<b>Class</b>: ' . $reg['class_name'];
            $registrations[$rid]['class_title'] .= '<br/><b>Participant</b>: ' . $reg['display_name'];
            if( $reg['title'] != '' ) {
                $registrations[$rid]['class_title'] .= '<br/><b>Title</b>: ' . $reg['title'];
            }
        }
        $page['blocks'][] = array('type'=>'table', 'section'=>'adjudications', 
            'columns'=>array(
                array('label'=>'Class/Title', 'field'=>'class_title', 'class'=>''),
//                array('label'=>'Class', 'field'=>'class_name', 'class'=>''),
//                array('label'=>'Title', 'field'=>'title', 'class'=>''),
                array('label'=>'Comments', 'field'=>'comments', 'class'=>''),
                array('label'=>'', 'field'=>'edit', 'class'=>'alignright'),
                ),
            'rows'=>$registrations,
            );
//        $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($registrations, true) . '</pre>');
    } */
    //
    // Display the registrations for adjudication
    //
/*    elseif( $display == 'section' && $adjudicator == 'yes' ) {
        $base_url = $args['base_url'] . '/' . $section['permalink'];
        $page['breadcrumbs'][] = array('name'=>$section['name'], 'url'=>$base_url);

        //
        // Load the schedule sections, divisions, timeslots, classes, registrations
        //
        $strsql = "SELECT sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "sections.adjudicator1_id, "
            . "sections.adjudicator2_id, "
            . "sections.adjudicator3_id, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "divisions.address, "
            . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, "
            . "timeslots.id AS timeslot_id, "
            . "timeslots.name AS timeslot_name, "
            . "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
            . "timeslots.class1_id, "
            . "timeslots.class2_id, "
            . "timeslots.class3_id, "
            . "IFNULL(class1.name, '') AS class1_name, "
            . "IFNULL(class2.name, '') AS class2_name, "
            . "IFNULL(class3.name, '') AS class3_name, "
            . "timeslots.name AS timeslot_name, "
            . "timeslots.description "
//            . "registrations.id AS reg_id, "
//            . "registrations.display_name, "
//            . "registrations.public_name, "
    //        . "'' AS title "
//            . "registrations.title "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id " 
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id " 
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
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "";
        if( isset($section['id']) && $section['id'] > 0 ) {
//            $strsql .= "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' ";
        }
        $strsql .= "ORDER BY divisions.division_date, division_id, slot_time "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'section_id', 'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'adjudicator1_id', 'adjudicator2_id', 'adjudicator3_id')),
            array('container'=>'divisions', 'fname'=>'division_id', 'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address')),
            array('container'=>'timeslots', 'fname'=>'timeslot_id', 'fields'=>array('id'=>'timeslot_id', 'name'=>'timeslot_name', 'time'=>'slot_time_text', 'class1_id', 'class2_id', 'class3_id', 'description', 'class1_name', 'class2_name', 'class3_name')),
 //           array('container'=>'registrations', 'fname'=>'reg_id', 'fields'=>array('id'=>'reg_id', 'name'=>'display_name', 'public_name', 'title')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();
    
 //       $page['blocks'][] = array('type'=>'buttonlist', 'section'=>'adjudication-participants', 'base_url'=>$base_url, 'tags'=>$registrations);
        //
        // Get the students for the section
        //
    } */

    //
    // Display the section information
    //
    elseif( $display == 'section' ) {
        $page['breadcrumbs'][] = array('name'=>$section['name'], 'url'=>$args['base_url'] . '/' . $section['permalink']);
        //
        // Display the section information
        //
        $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$section['name'], 
            'aside_image_id'=>(isset($section['image_id']) && $section['image_id'] > 0  ? $section['image_id'] : 0),
            'wide'=>(isset($section['image_id']) && $section['image_id'] > 0  ? 'no' : 'yes'),
            'content'=>($section['description'] != '' ? $section['description'] : $section['synopsis'])
            );

        //
        // Get the categories and classes
        //
        $strsql = "SELECT ciniki_musicfestival_classes.id, "
            . "ciniki_musicfestival_classes.uuid, "
            . "ciniki_musicfestival_classes.festival_id, "
            . "ciniki_musicfestival_classes.category_id, "
            . "ciniki_musicfestival_categories.id AS category_id, "
            . "ciniki_musicfestival_categories.name AS category_name, "
            . "ciniki_musicfestival_categories.primary_image_id AS category_image_id, "
            . "ciniki_musicfestival_categories.synopsis AS category_synopsis, "
            . "ciniki_musicfestival_categories.description AS category_description, "
            . "ciniki_musicfestival_classes.code, "
            . "ciniki_musicfestival_classes.name, "
            . "ciniki_musicfestival_classes.permalink, "
            . "ciniki_musicfestival_classes.sequence, "
            . "ciniki_musicfestival_classes.flags, ";
        if( $festival['earlybird'] >= 0 ) {
            $strsql .= "CONCAT('$', FORMAT(ciniki_musicfestival_classes.earlybird_fee, 2)) AS fee ";
        } else {
            $strsql .= "CONCAT('$', FORMAT(ciniki_musicfestival_classes.fee, 2)) AS fee ";
        }
        $strsql .= "FROM ciniki_musicfestival_categories, ciniki_musicfestival_classes "
            . "WHERE ciniki_musicfestival_categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
            . "AND ciniki_musicfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_musicfestival_categories.id = ciniki_musicfestival_classes.category_id "
            . "AND ciniki_musicfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY ciniki_musicfestival_categories.sequence, ciniki_musicfestival_categories.name, "
                . "ciniki_musicfestival_classes.sequence, ciniki_musicfestival_classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'categories', 'fname'=>'category_id', 
                'fields'=>array('name'=>'category_name', 'image_id'=>'category_image_id', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $categories = $rc['categories'];
            foreach($categories as $category) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$category['name'], 
                    'aside_image_id'=>(isset($category['image_id']) && $category['image_id'] > 0  ? $category['image_id'] : 0),
                    'wide'=>(isset($category['image_id']) && $category['image_id'] > 0  ? 'no' : 'yes'),
                    'content'=>($category['description'] != '' ? $category['description'] : ($category['synopsis'] != '' ? $category['synopsis'] : ' ')),
                    );
                if( isset($category['classes']) && count($category['classes']) > 0 ) {
                    //
                    // FIXME: Check if online registrations enabled, and online registrations enabled for this class
                    //
                    if( ($festival['flags']&0x01) == 0x01 ) {
                        foreach($category['classes'] as $cid => $class) {
                            $category['classes'][$cid]['register'] = "<a href='" . $args['base_url'] . "/registrations?r=new&cl=" . $class['uuid'] . "'>Register</a>";
                        }
                        $page['blocks'][] = array('type'=>'table', 'section'=>'classes', 
                            'columns'=>array(
                                array('label'=>'Code', 'field'=>'code', 'class'=>''),
                                array('label'=>'Course', 'field'=>'name', 'class'=>''),
                                array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                                array('label'=>'', 'field'=>'register', 'class'=>'alignright'),
                                ),
                            'rows'=>$category['classes'],
                            );
                    } else {
                        $page['blocks'][] = array('type'=>'table', 'section'=>'classes', 
                            'columns'=>array(
                                array('label'=>'', 'field'=>'code', 'class'=>''),
                                array('label'=>'', 'field'=>'name', 'class'=>''),
                                array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                                ),
                            'rows'=>$category['classes'],
                            );
                    }
                }
            }
        }
    }

    //
    // Display the adjudicators
    //
    elseif( $display == 'adjudicators' ) {
        $page['breadcrumbs'][] = array('name'=>'Adjudicators', 'url'=>$args['base_url'] . '/adjudicators');
        $strsql = "SELECT adjudicators.id, "
            . "adjudicators.customer_id, "
            . "customers.sort_name, "
            . "sections.name "
            . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "adjudicators.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                . "("
                    . "adjudicators.id = sections.adjudicator1_id "
                    . "OR adjudicators.id = sections.adjudicator2_id "
                    . "OR adjudicators.id = sections.adjudicator3_id "
                    . ") "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY customers.sort_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'a');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerPublicDetails');
            foreach($rc['rows'] as $row) {
                $rc = ciniki_customers_web_customerPublicDetails($ciniki, $settings, $tnid, array('customer_id'=>$row['customer_id']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $customer = $rc['customer'];
                $page['blocks'][] = array('type'=>'content', 'section'=>'section', 'title'=>$customer['display_name']
                    . (isset($row['name']) && $row['name'] != '' ? ' - ' . $row['name'] : ''), 
                    'aside_image_id'=>(isset($customer['image_id']) && $customer['image_id'] > 0  ? $customer['image_id'] : 0),
                    'html'=>$customer['processed_description']);
//                if( isset($customer['image_id']) && $customer['image_id'] > 0 ) {
//                    $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$customer['image_id']);
//                }
//                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$customer['display_name'], 'html'=>$customer['processed_description']);
            } 
        } else {
            $page['blocks'][] = array('type'=>'content', 'section'=>'section', 'title'=>'', 'content'=>"We don't currently have any adjudicators.");
        } 
    }

    if( isset($sponsors) && count($sponsors) > 0 ) {
        $page['blocks'][] = array('type'=>'sponsors', 'section'=>'sponsors', 'title'=>'', 'sponsors'=>$sponsors);
    }

    //
    // Add the submenu
    //
    $page['submenu'] = array();
    $page['submenu']['about'] = array('name'=>'About', 'url'=>$args['base_url'] . '/about');

    //
    // Get the sections
    //
    $strsql = "SELECT name, permalink "
        . "FROM ciniki_musicfestival_sections "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0 "   // Make sure visible on website
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        foreach($rc['rows'] as $row) {
            $page['submenu'][$row['permalink']] = array('name'=>$row['name'], 'url'=>$args['base_url'] . '/' . $row['permalink']);
        }
    }
    $page['submenu']['adjudicators'] = array('name'=>'Adjudicators', 'url'=>$args['base_url'] . '/adjudicators');

    if( isset($ciniki['session']['customer']['id']) ) {
        if( $adjudicator == 'yes' ) {
            $page['submenu']['adjudications'] = array('name'=>'Adjudications', 'url'=>$args['base_url'] . '/adjudications');
        } else {
            $page['submenu']['registrations'] = array('name'=>'Registrations', 'url'=>$args['base_url'] . '/registrations');
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
