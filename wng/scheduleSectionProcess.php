<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_scheduleSectionProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.675', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.676', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.677', 'msg'=>"No festival specified"));
    }

    //
    // Make sure a festival was specified
    //
    if( !isset($s['section-id']) || $s['section-id'] == '' || $s['section-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.678', 'msg'=>"No schedule specified"));
    }

    //
    // Check if syllabus is displaying just live or just virtual
    //
    if( isset($s['ipv']) && $s['ipv'] == 'inperson' ) {
        $lv_word = 'Live ';
    } elseif( isset($s['ipv']) && $s['ipv'] == 'virtual' ) {
        $lv_word = 'Virtual ';
    }

    //
    // Get the music festival details
    //
    $dt = new DateTime('now', new DateTimezone('UTC'));
    $strsql = "SELECT id, name, flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date "
//        . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird, "
//        . "IFNULL(DATEDIFF(virtual_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS virtual "
        . "FROM ciniki_musicfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['festival']) ) {
        $festival = $rc['festival'];
        $earlybird_dt = new DateTime($rc['festival']['earlybird_date'], new DateTimezone('UTC'));
        $live_dt = new DateTime($rc['festival']['live_date'], new DateTimezone('UTC'));
        $virtual_dt = new DateTime($rc['festival']['virtual_date'], new DateTimezone('UTC'));
        $festival['earlybird'] = ($earlybird_dt > $dt ? 'yes' : 'no');
        $festival['live'] = ($live_dt > $dt ? 'yes' : 'no');
        $festival['virtual'] = ($virtual_dt > $dt ? 'yes' : 'no');
        if( ($festival['flags']&0x10) == 0x10 ) {   // Adjudication Plus
            $festival['earlybird_plus_live'] = $festival['earlybird'];
            $festival['plus_live'] = $festival['live'];
        }
    }

    //
    // Load the schedules
    //
    $strsql = "SELECT sections.id, "
        . "sections.name, "
        . "sections.sponsor_settings, "
        . "sections.provincial_settings "
        . "FROM ciniki_musicfestival_schedule_sections AS sections "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//        . "AND (sections.flags&0x10) = 0x10 "   // Schedule published on website
        . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.679', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'ok', 'blocks' => array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => 'Unable to find section requested',
            ));
    }
    $schedulesection = $rc['section'];
    $schedulesection['permalink'] = ciniki_core_makePermalink($ciniki, $schedulesection['name']);
    if( $schedulesection['sponsor_settings'] != '' ) {
        $values = unserialize($schedulesection['sponsor_settings']);
        foreach($values as $k => $v) {
            $schedulesection[$k] = $v;
        }
    }
    if( $schedulesection['provincial_settings'] != '' ) {
        $values = unserialize($schedulesection['provincial_settings']);
        foreach($values as $k => $v) {
            $schedulesection[$k] = $v;
        }
    }

    //
    // Check if schedule download requests for this section
    //
    if( isset($s['layout']) && $s['layout'] == 'date-buttons'
        && isset($request['uri_split'][($request['cur_uri_pos']+2)])
        && $request['uri_split'][($request['cur_uri_pos']+2)] == 'schedule.pdf' 
        ) {
        //
        // Download the syllabus section pdf
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'schedulePDF');
        $rc = ciniki_musicfestivals_templates_schedulePDF($ciniki, $tnid, array(
            'published' => 'yes',
            'festival_id' => $s['festival-id'],
            'schedulesection_id' => $s['section-id'],
            'division_id' => $s['division-id'],
            'ipv' => isset($s['ipv']) ? $s['ipv'] : '',
            'division_header_format' => isset($s['division_header_format']) ? $s['division_header_format'] : '',
            'division_header_labels' => isset($s['division_header_labels']) ? $s['division_header_labels'] : '',
            'names' => isset($s['names']) ? $s['names'] : '',
            'titles' => isset($s['titles']) ? $s['titles'] : '',
            'video_urls' => isset($s['video_urls']) ? $s['video_urls'] : '',
            'section_page_break' => isset($s['section_page_break']) ? $s['section_page_break'] : '',
            'provincials_info' => 'yes',
            'top_sponsors' => 'yes',
            'bottom_sponsors' => 'yes',
            'section_page_break' => isset($s['section_page_break']) ? $s['section_page_break'] : '',
            ));
        if( isset($rc['pdf']) ) {
            $filename = $festival['name'] . ' - ' . $schedulesection['name'] . ' Schedule';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $filename = ciniki_core_makePermalink($ciniki, $filename) . '.pdf';
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');

            $rc['pdf']->Output($filename, 'I');

            return array('stat'=>'exit');
        } else {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Unable to download pdf',
                );
        }
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] == 'schedule.pdf' 
        ) {
        //
        // Download the syllabus section pdf
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'schedulePDF');
        $rc = ciniki_musicfestivals_templates_schedulePDF($ciniki, $tnid, array(
            'published' => 'yes',
            'festival_id' => $s['festival-id'],
            'schedulesection_id' => $schedulesection['id'],
            'ipv' => isset($s['ipv']) ? $s['ipv'] : '',
            'division_header_format' => isset($s['division_header_format']) ? $s['division_header_format'] : '',
            'division_header_labels' => isset($s['division_header_labels']) ? $s['division_header_labels'] : '',
            'names' => isset($s['names']) ? $s['names'] : '',
            'titles' => isset($s['titles']) ? $s['titles'] : '',
            'video_urls' => isset($s['video_urls']) ? $s['video_urls'] : '',
            'section_page_break' => isset($s['section_page_break']) ? $s['section_page_break'] : '',
            'provincials_info' => 'yes',
            'top_sponsors' => 'yes',
            'bottom_sponsors' => 'yes',
            'section_page_break' => isset($s['section_page_break']) ? $s['section_page_break'] : '',
            ));
        if( isset($rc['pdf']) ) {
            $filename = $festival['name'] . ' - ' . $schedulesection['name'] . ' Schedule';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
            $filename = ciniki_core_makePermalink($ciniki, $filename) . '.pdf';
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');

            $rc['pdf']->Output($filename, 'I');

            return array('stat'=>'exit');
        } else {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Unable to download pdf',
                );
        }
    }
    
    //
    // Load the sponsors
    //
    if( isset($schedulesection['top_sponsor_ids']) 
        && is_array($schedulesection['top_sponsor_ids']) 
        && count($schedulesection['top_sponsor_ids']) > 0 
        ) {
        $strsql = "SELECT id, name, url, image_id "
            . "FROM ciniki_musicfestival_sponsors "
            . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $schedulesection["top_sponsor_ids"]) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sponsors', 'fname'=>'id', 'fields'=>array('url', 'image-id'=>'image_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.689', 'msg'=>'Unable to load sponsors', 'err'=>$rc['err']));
        }
        $top_sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();
    }
    if( isset($schedulesection['bottom_sponsor_ids']) 
        && is_array($schedulesection['bottom_sponsor_ids']) 
        && count($schedulesection['bottom_sponsor_ids']) > 0 
        ) {
        $strsql = "SELECT id, name, url, image_id "
            . "FROM ciniki_musicfestival_sponsors "
            . "WHERE id IN (" . ciniki_core_dbQuoteIDs($ciniki, $schedulesection["bottom_sponsor_ids"]) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sponsors', 'fname'=>'id', 'fields'=>array('url', 'image-id'=>'image_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.689', 'msg'=>'Unable to load sponsors', 'err'=>$rc['err']));
        }
        $bottom_sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();
    }

    //
    // Load the divisions, timeslots and registrations
    //
    $strsql = "SELECT divisions.id AS division_id, "
        . "divisions.name AS division_name, "
        . "divisions.address, "
        . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text, ";
    if( isset($s['separate-classes']) && $s['separate-classes'] == 'yes' ) {
        $strsql .= "CONCAT_WS('-', timeslots.id, classes.id) AS timeslot_id, ";
    } else {
        $strsql .= "timeslots.id AS timeslot_id, ";
    }
    $strsql .= "TIME_FORMAT(timeslots.slot_time, '%l:%i %p') AS slot_time_text, "
        . "timeslots.name AS timeslot_name, "
        . "timeslots.description, "
        . "registrations.id AS reg_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS timeslot_time, "
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
        . "registrations.participation, "
        . "registrations.mark, "
        . "registrations.placement, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "categories.name AS category_name, "
        . "sections.name AS section_name, "
        . "members.name AS member_name "
        . "FROM ciniki_musicfestival_schedule_divisions AS divisions "
        . "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id " 
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "divisions.id = timeslots.sdivision_id " 
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "timeslots.id = registrations.timeslot_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
        . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
            . "registrations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE divisions.ssection_id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' "
        . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($s['division-id']) && $s['division-id'] > 0 ) {
        $strsql .= "AND divisions.id = '" . ciniki_core_dbQuote($ciniki, $s['division-id']) . "' ";
    }
    if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
        $strsql .= "AND ((divisions.flags&0x20) = 0x20 OR (ssections.flags&0x20)) ";
        if( isset($s['min-mark']) && $s['min-mark'] != '' ) {
            $strsql .= "AND registrations.mark >= '" . ciniki_core_dbQuote($ciniki, $s['min-mark']) . "' ";
        }
    }
    if( isset($s['ipv']) && $s['ipv'] == 'inperson' ) {
        $strsql .= "AND (registrations.participation < 1 || ISNULL(registrations.participation) ) ";
    } elseif( isset($s['ipv']) && $s['ipv'] == 'virtual' ) {
        $strsql .= "AND registrations.participation = 1 ";
    }
    $strsql .= "ORDER BY divisions.division_date, division_id, slot_time, registrations.timeslot_sequence, registrations.display_name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'divisions', 'fname'=>'division_id', 
            'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'date'=>'division_date_text', 'address'),
            ),
        array('container'=>'timeslots', 'fname'=>'timeslot_id', 
            'fields'=>array('id'=>'timeslot_id', 'title'=>'timeslot_name', 'time'=>'slot_time_text', 'synopsis'=>'description', 
                'class_code', 'class_name', 'category_name', 'section_name',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'display_name', 'public_name', 'timeslot_time',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                'participation', 'class_name', 'mark', 'placement', 'member_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $divisions = isset($rc['divisions']) ? $rc['divisions'] : array();
    
    $s['title'] .= ($s['title'] != '' ? ' - ' : '') . $schedulesection['name'];

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
//        'level' => $section['sequence'] == 1 ? 1 : 2,
        'level' => 1,
        'title' => isset($s['title']) ? $s['title'] : '',
        );

    //
    // Check for top sponsors
    //
    if( isset($top_sponsors) && count($top_sponsors) > 0 ) {
        $items = array();
        foreach($schedulesection['top_sponsor_ids'] as $sid) {
            if( isset($top_sponsors[$sid]) ) {
                $items[] = $top_sponsors[$sid];
            }
        }
        $blocks[] = array(
            'type' => 'imagebuttons',
            'title' => isset($schedulesection['top_sponsors_title']) ? $schedulesection['top_sponsors_title'] : '',
            'class' => 'aligncenter schedule-top-sponsors',
            'image-format' => 'padded',
            'image-ratio' => isset($schedulesection['top_sponsors_image_ratio']) ? $schedulesection['top_sponsors_image_ratio'] : '4-3',
            'items' => $items,
            );
    }
/*    if( isset($schedulesection['top_sponsor1_image_id']) && $schedulesection['top_sponsor1_image_id'] > 0 ) {
        $blocks[] = array(
            'type' => 'asideimage', 
            'title' => $schedulesection['top_sponsor1_name'],
            'image-id' => $schedulesection['top_sponsor1_image_id'],
            );
    }
*/
    //
    // Check if download button
    //
    if( isset($s['section-pdf']) && ($s['section-pdf'] == 'top' || $s['section-pdf'] == 'both') ) {
        $url = $request['ssl_domain_base_url'] . $request['page']['path'];
        $division_name = '';
        if( isset($s['layout']) && $s['layout'] == 'date-buttons' ) {
            $url .= '/' . $request['uri_split'][$request['cur_uri_pos']];
            $url .= '/' . $request['uri_split'][($request['cur_uri_pos']+1)];
            if( isset($divisions[0]['name']) ) {
                $division_name = $divisions[0]['name'];
            }
        } else {
            $url .= '/' . $schedulesection['permalink'];
        }
        $url .= '/schedule.pdf';

        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'schedule-top-buttons buttons-top-' . $schedulesection['permalink'],
            'list' => array(
                array(
                    'url' => $url,
                    'target' => '_blank',
                    'text' => 'Download ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . 'Schedule PDF for ' . $schedulesection['name'] . ($division_name != '' ? ' - ' . $division_name : ''),
                    ),
                ),
            );
    }

    //
    // Show the divisions
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    foreach($divisions as $division) {
        if( isset($division['timeslots']) ) {
            //
            // Process the timeslots
            //
            $videos = 'no';
            foreach($division['timeslots'] as $tid => $timeslot) {
                $name = $timeslot['title'];
                if( $name == '' && $timeslot['class_name'] != '' ) {
                    $name = $timeslot['class_name'];
                }
                if( isset($s['separate-classes']) && $s['separate-classes'] == 'yes' && $timeslot['class_code'] != '' ) {
                    if( isset($s['class-format']) && $s['class-format'] == 'code-section-category-class' ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($s['class-format']) && $s['class-format'] == 'section-category-class' ) {
                        $name = $timeslot['section_name'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($s['class-format']) && $s['class-format'] == 'category-class' ) {
                        $name = $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } elseif( isset($s['class-format']) && $s['class-format'] == 'code-category-class' ) {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['category_name'] . ' - ' . $timeslot['class_name']; 
                    } else {
                        $name = $timeslot['class_code'] . ' - ' . $timeslot['class_name']; 
                    }
                }
                $division['timeslots'][$tid]['title'] = $name;
                $division['timeslots'][$tid]['items'] = array();
                //
                // Create the items table for the schedule
                //
                if( isset($timeslot['registrations']) ) {
                    foreach($timeslot['registrations'] as $registration) {
                        //
                        // Setup name
                        //
                        $name = $registration['public_name'];
                        if( isset($s['names']) && $s['names'] == 'private' ) {
                            $name = $registration['display_name'];
                        }

                        //
                        // Check if titles required, then add line for each title, otherwise add names
                        //
                        if( (isset($s['titles']) && $s['titles'] == 'yes')
                            || isset($s['video_urls']) && $s['video_urls'] == 'yes' 
                            ) {
                            $titles = '';
                            $video_links = '';
                            for($i = 1; $i <= 8; $i++) {
                                //
                                // Make sure the title exists
                                //
                                if( isset($registration["title{$i}"]) && $registration["title{$i}"] != '' ) {
                                    $rc = ciniki_musicfestivals_titleMerge($ciniki, $tnid, $registration, $i);
                                    if( $rc['stat'] != 'ok' ) {
                                        return $rc;
                                    }
                                    if( isset($s['video_urls']) && $s['video_urls'] == 'yes' 
                                        && isset($registration["video_url{$i}"]) && $registration["video_url{$i}"] != '' 
                                        ) {
/*                                        $division['timeslots'][$tid]['items'][] = array(
                                            'name'=>$name, 
                                            'title'=>$rc['title'], 
                                            'video'=>"<a target='_blank' class='link' href='" . $registration["video_url{$i}"] . "'>Watch Video</a>",
                                            'mark'=>$registration['mark'],
                                            'placement'=>$registration['placement'],
                                            ); */
//                                        $titles .= ($titles != '' ? '' : '' ) 
                                        $titles .= "<div class='perf_title'>{$rc['title']}</div>";
                                        $video_links .= ($video_links != '' ? '<br/>' : '') 
                                            . "<a target='_blank' class='link' href='" . $registration["video_url{$i}"] . "'>Watch Video</a>";
                                        $videos = 'yes';
                                    } else {
                                        $titles .= "<div class='perf_title'>{$rc['title']}</div>";
//                                        $titles .= ($titles != '' ? '<br/>' : '' ) . "<div class='title'>{$rc['title']}</div>";
/*                                        $division['timeslots'][$tid]['items'][] = array(
                                            'name' => ($i == 1 ? $name : ''), 
                                            'title' => $rc['title'], 
                                            'video' => '',
                                            'timeslot_time' => ($i == 1 ? $registration['timeslot_time'] : ''),
                                            'mark' => $registration['mark'],
                                            'placement' => $registration['placement'],
                                            ); */
                                    }
                                }
                            }
                            $division['timeslots'][$tid]['items'][] = array(
                                'name' => $name,
                                'titles' => $titles,
                                'videos' => $video_links,
                                'timeslot_time' => $registration['timeslot_time'],
                                'member_name' => $registration['member_name'],
                                'mark' => $registration['mark'],
                                'placement' => $registration['placement'],
                                );
                        } 
                        else {
                            $division['timeslots'][$tid]['items'][] = array(
                                'name' => $name,
                                'timeslot_time' => $registration['timeslot_time'],
                                'member_name' => $registration['member_name'],
                                'mark' => $registration['mark'],
                                'placement' => $registration['placement'],
                                );
                        }
                    }
                    if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
                        uasort($division['timeslots'][$tid]['items'], function($a, $b) {
                            if( $a['mark'] == $b['mark'] ) {
                                return strcasecmp($a['name'], $b['name']);
                            }
                            return $a['mark'] > $b['mark'] ? -1 : 1;
                            });
                        $prev_name = '';
                        foreach($division['timeslots'][$tid]['items'] as $iid => $item) {
                            if( $prev_name == $item['name'] ) {
                                $division['timeslots'][$tid]['items'][$iid]['name'] = '';
                                $division['timeslots'][$tid]['items'][$iid]['placement'] = '';
                            }
                            $prev_name = $item['name'];
                        }
                    }
                }
            }

            if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
                if( isset($s['placement']) && $s['placement'] == 'yes' ) {
                    $columns = array(
                        array('label'=>'Placement', 'field'=>'placement', 'class'=>''),
                        array('label'=>'Name', 'field'=>'name', 'class'=>''),
                        );
                } else {
                    $columns = array(
                        array('label'=>'Placement', 'field'=>'placement', 'class'=>''),
                        array('label'=>'Name', 'field'=>'name', 'class'=>''),
                        );
                }
                if( isset($s['titles']) && $s['titles'] == 'yes' ) {
                    $columns[] = array('label'=>'Titles', 'field'=>'titles', 'class'=>'');
                }
                if( isset($s['mark']) && $s['mark'] == 'yes' ) {
                    $columns[] = array('label'=>'Mark', 'field'=>'mark', 'class'=>'');
                }
                if( $videos == 'yes' ) {
                    $columns[] = array('label'=>'Video', 'field'=>'videos', 'class'=>'alignright');
                }
                $blocks[] = array(
                    'type' => 'schedule',
                    'title' => $division['name'] . (isset($s['division-dates']) && $s['division-dates'] == 'yes' ? ' - ' . $division['date'] : ''),
                    'subtitle' => $division['address'],
                    'times' => 'no',
                    'class' => 'musicfestival-timeslots limit-width limit-width-80',
                    'items' => $division['timeslots'],
                    'details-headers' => 'no',
                    'details-columns' => $columns,
                    );
            } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x080000) ) {
                $columns = array(
                    array('label'=>'Time', 'fold-label'=>'Time:', 'field'=>'timeslot_time', 'class'=>'aligntop'),
                    array('label'=>'Name', 'fold-label'=>'Name:', 'field'=>'name', 'class'=>'aligntop'),
                    );
                if( isset($s['titles']) && $s['titles'] == 'yes' ) {
                    $columns[] = array('label'=>'Titles', 'fold-label'=>'Titles:', 'field'=>'titles', 'class'=>'aligntop');
                }
                if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
                    // Provincials add member column
                    $columns[] = array('label'=>'Home Festival', 'fold-label'=>'Home Festival:', 'field'=>'member_name', 'class'=>'aligntop');
                }
                if( $videos == 'yes' ) {
                    $columns[] = array('label'=>'Video', 'fold-label'=>'Videos:', 'field'=>'videos', 'class'=>'alignright');
                }
                $blocks[] = array(
                    'type' => 'text',
                    'title' => $division['name'],
                    'content' => 
                        '<b>Location</b>: ' . $division['address'] . '<br/>'
                        . '<b>Adjudicator</b>: Name Here', // . $division['adjudicator_name'],
                    );
                foreach($division['timeslots'] as $timeslot) {
                    if( isset($timeslot['items']) && count($timeslot['items']) > 0 ) {
                        $blocks[] = array(
                            'type' => 'table', 
                            'class' => 'fold-at-50',
                            'columns' => $columns,
                            'rows' => $timeslot['items'],
                            );
                    } elseif( $timeslot['synopsis'] != '' ) {
                        $blocks[] = array(
                            'type' => 'text',
                            'level' => 3,
                            'subtitle' => $timeslot['time'] . ' - ' . $timeslot['title'],
                            'content' => $timeslot['synopsis'],
                            );
                    } else {
                        $blocks[] = array(
                            'type' => 'title',
                            'level' => 3,
                            'title' => $timeslot['time'] . ' - ' . $timeslot['title'],
                            );
                    }
                }
            } else {
                $columns = array(
                    array('label'=>'Name', 'field'=>'name', 'class'=>''),
                    );
                if( isset($s['titles']) && $s['titles'] == 'yes' ) {
                    $columns[] = array('label'=>'Titles', 'field'=>'titles', 'class'=>'');
                }
                if( $videos == 'yes' ) {
                    $columns[] = array('label'=>'Video', 'field'=>'videos', 'class'=>'alignright');
                }
                $blocks[] = array(
                    'type' => 'schedule',
                    'title' => $division['name'] . (isset($s['division-dates']) && $s['division-dates'] == 'yes' ? ' - ' . $division['date'] : ''),
                    'subtitle' => $division['address'],
                    'class' => 'musicfestival-timeslots limit-width limit-width-80',
                    'items' => $division['timeslots'],
                    'details-headers' => 'no',
                    'details-columns' => $columns,
                    );
            }
        }
    }

    //
    // Check for provincial information
    //
    if( isset($schedulesection['provincials_title']) && $schedulesection['provincials_title'] != '' 
        && isset($schedulesection['provincials_content']) && $schedulesection['provincials_content'] != '' 
        && (!isset($s['results-only']) || $s['results-only'] != 'yes')
        ) {
        $blocks[] = array(
            'type' => 'contentphoto', 
            'title' => $schedulesection['provincials_title'],
            'class' => 'schedule-provincials-info',
            'content' => $schedulesection['provincials_content'],
            'image-size' => 'medium',
            'image-id' => isset($schedulesection['provincials_image_id']) ? $schedulesection['provincials_image_id'] : 0,
            );
    }

    //
    // Check for bottom sponsors
    //
    if( isset($bottom_sponsors) && count($bottom_sponsors) > 0 ) {
        if( isset($schedulesection['bottom_sponsors_title']) && $schedulesection['bottom_sponsors_title'] != '' 
            && isset($schedulesection['bottom_sponsors_content']) && $schedulesection['bottom_sponsors_content'] != '' 
            ) {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'schedule-bottom-sponsors-title clearright',
                'title' => $schedulesection['bottom_sponsors_title'],
                'content' => isset($schedulesection['bottom_sponsors_content']) ? $schedulesection['bottom_sponsors_content'] : '',
                );
        } elseif( isset($schedulesection['bottom_sponsors_title']) && $schedulesection['bottom_sponsors_title'] != '' ) {
            $blocks[] = array(
                'type' => 'title',
                'class' => 'schedule-bottom-sponsors-title',
                'title' => $schedulesection['bottom_sponsors_title'],
                );
        }
        $items = array();
        foreach($schedulesection['bottom_sponsor_ids'] as $sid) {
            if( isset($bottom_sponsors[$sid]) ) {
                $items[] = $bottom_sponsors[$sid];
            }
        }
        $blocks[] = array(
            'type' => 'imagebuttons',
            'class' => 'aligncenter schedule-bottom-sponsors',
            'image-format' => 'padded',
            'image-ratio' => isset($schedulesection['bottom_sponsors_image_ratio']) ? $schedulesection['bottom_sponsors_image_ratio'] : '4-3',
            'items' => $items,
            );
    }

    //
    // Check if download button
    //
    if( isset($s['section-pdf']) && ($s['section-pdf'] == 'bottom' || $s['section-pdf'] == 'both') ) {
        $url = $request['ssl_domain_base_url'] . $request['page']['path'];
        $division_name = '';
        if( isset($s['layout']) && $s['layout'] == 'date-buttons' ) {
            $url .= '/' . $request['uri_split'][$request['cur_uri_pos']];
            $url .= '/' . $request['uri_split'][($request['cur_uri_pos']+1)];
            if( isset($divisions[0]['name']) ) {
                $division_name = $divisions[0]['name'];
            }
        } else {
            $url .= '/' . $schedulesection['permalink'];
        }
        $url .= '/schedule.pdf';

        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'schedule-bottom-buttons buttons-bottom-' . $schedulesection['permalink'],
            'list' => array(
                array(
                    'url' => $url,
                    'target' => '_blank',
                    'text' => 'Download ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . 'Schedule PDF for ' . $schedulesection['name'] . ($division_name != '' ? ' - ' . $division_name : ''),
                    ),
                ),
            );
    }

    
    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
}
?>
