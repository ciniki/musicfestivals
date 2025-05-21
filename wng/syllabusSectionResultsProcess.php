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
function ciniki_musicfestivals_wng_syllabusSectionResultsProcess(&$ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.943', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.944', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    if( isset($section['groupname']) ) {
        $groupname = $section['groupname'];
    }
    $blocks = array();

    //
    // Check if syllabus is displaying just live or just virtual
    //
    if( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live' ) {
        $lv_word = 'Live ';
    } elseif( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual' ) {
        $lv_word = 'Virtual ';
    }

    //
    // Make sure a festival was specified
    //
    if( (!isset($section['festival_id']) || $section['festival_id'] == '' || $section['festival_id'] == 0)
        && (!isset($s['section-id']) || $s['section-id'] == '' || $s['section-id'] == 0)
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.945', 'msg'=>"No festival specified"));
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
    // Check for syllabus section requested
    //
    if( isset($section['section_permalink']) ) {
        $section_permalink = $section['section_permalink'];
    }
    elseif( isset($s['section-id']) ) {
        $section_id = $s['section-id'];
    }
    elseif( !isset($request['uri_split'][$request['cur_uri_pos']])
        || $request['uri_split'][$request['cur_uri_pos']] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.946', 'msg'=>"No syllabus specified"));
    } else {
        $section_permalink = $request['uri_split'][$request['cur_uri_pos']];
    }

    $base_url = $request['base_url'] . $request['page']['path'];

    //
    // Check for image format
    //
    $thumbnail_format = 'square-cropped';
    $thumbnail_padding_color = '#ffffff';
    if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' ) {
        $thumbnail_format = $s['thumbnail-format'];
        if( isset($s['thumbnail-padding-color']) && $s['thumbnail-padding-color'] != '' ) {
            $thumbnail_padding_color = $s['thumbnail-padding-color'];
        } 
    }
   
    if( isset($s['section-id']) ) {
        //
        // Get the section details
        //
        $strsql = "SELECT sections.id, "
            . "sections.festival_id, "
            . "sections.permalink, "
            . "sections.name, "
            . "sections.flags, "
            . "sections.primary_image_id, "
            . "sections.synopsis, "
            . "sections.latefees_start_amount, "
            . "sections.latefees_daily_increase, "
            . "sections.latefees_days, ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) 
            && isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live'
            ) {
            $strsql .= "sections.live_description AS description, ";
        } 
        elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) 
            && isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual'
            ) {
            $strsql .= "sections.virtual_description AS description, ";
        } 
        else {
            $strsql .= "sections.description, ";
        }
        $strsql .= "sections.live_end_dt, "
            . "sections.virtual_end_dt "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $s['section-id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.969', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
        }
        if( !isset($rc['section']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.970', 'msg'=>'Unable to find requested section'));
        }
        $syllabus_section = $rc['section'];
        $section['festival_id'] = $syllabus_section['festival_id'];

        //
        // Get the music festival details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'festivalLoad');
        $rc = ciniki_musicfestivals_wng_festivalLoad($ciniki, $tnid, $section['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $festival = $rc['festival'];

    } else {
        //
        // Get the music festival details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'festivalLoad');
        $rc = ciniki_musicfestivals_wng_festivalLoad($ciniki, $tnid, $section['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $festival = $rc['festival'];

        //
        // Get the section details
        //
        $strsql = "SELECT sections.id, "
            . "sections.permalink, "
            . "sections.name, "
            . "sections.flags, "
            . "sections.primary_image_id, "
            . "sections.synopsis, "
            . "sections.latefees_start_amount, "
            . "sections.latefees_daily_increase, "
            . "sections.latefees_days, ";
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) 
            && isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live'
            ) {
            $strsql .= "sections.live_description AS description, ";
        } 
        elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x020000) 
            && isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual'
            ) {
            $strsql .= "sections.virtual_description AS description, ";
        } 
        else {
            $strsql .= "sections.description, ";
        }
        $strsql .= "sections.live_end_dt, "
            . "sections.virtual_end_dt "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND sections.permalink = '" . ciniki_core_dbQuote($ciniki, $section_permalink) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.988', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
        }
        if( !isset($rc['section']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.989', 'msg'=>'Unable to find requested section'));
        }
        $syllabus_section = $rc['section'];
    }

    //
    // Check if section has other deadlines
    //
/*    $dt = new DateTime('now', new DateTimezone('UTC'));
    if( ($festival['flags']&0x08) == 0x08 && $syllabus_section['live_end_dt'] != '' && $syllabus_section['live_end_dt'] != '0000-00-00 00:00:00' ) {
        $live_dt = new DateTime($syllabus_section['live_end_dt'], new DateTimezone('UTC'));
        $festival['live'] = ($live_dt > $dt ? 'yes' : 'no');
        if( ($festival['flags']&0x10) == 0x10 ) {   // Adjudication Plus
            $festival['plus_live'] = $festival['live'];
        }
    }
    if( $festival['live'] == 'no' && ($syllabus_section['flags']&0x30) > 0 && $syllabus_section['latefees_days'] > 0 ) {
        if( ($festival['flags']&0x08) == 0x08 && $syllabus_section['live_end_dt'] != '0000-00-00 00:00:00' ) {
            $section_live_dt = new DateTime($syllabus_section['live_end_dt'], new DateTimezone('UTC'));
        } else {
            $section_live_dt = clone $festival['live_end_dt'];
        }
        $interval = $section_live_dt->diff($dt);
        $section_live_dt->add(new DateInterval("P{$syllabus_section['latefees_days']}D"));
        if( $section_live_dt > $dt ) {      // is within latefees_days
            $festival['live'] = 'yes';
            $syllabus_section['live_days_past'] = $interval->format('%d');
            $syllabus_section['live_latefees'] = $syllabus_section['latefees_start_amount']
                + ($syllabus_section['latefees_daily_increase'] * $syllabus_section['live_days_past']);
        }
    }

    if( $syllabus_section['virtual_end_dt'] != '' && $syllabus_section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
        $virtual_dt = new DateTime($syllabus_section['virtual_end_dt'], new DateTimezone('UTC'));
        $festival['virtual'] = ($virtual_dt > $dt ? 'yes' : 'no');
    }
    if( ($festival['flags']&0x06) > 0 && $festival['virtual'] == 'no' && ($syllabus_section['flags']&0x30) > 0 && $syllabus_section['latefees_days'] > 0 ) {
        if( ($festival['flags']&0x08) == 0x08 && $syllabus_section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
            $section_virtual_dt = new DateTime($syllabus_section['virtual_end_dt'], new DateTimezone('UTC'));
        } else {
            $section_virtual_dt = clone $festival['virtual_end_dt'];
        }
        $interval = $section_virtual_dt->diff($dt);
        $section_virtual_dt->add(new DateInterval("P{$syllabus_section['latefees_days']}D"));
        if( $section_virtual_dt > $dt ) {      // is within latefees_days
            $festival['virtual'] = 'yes';
            $syllabus_section['virtual_days_past'] = $interval->format('%d');
            $syllabus_section['virtual_latefees'] = $syllabus_section['latefees_start_amount']
                + ($syllabus_section['latefees_daily_increase'] * $syllabus_section['virtual_days_past']);
        }
    }

  
    if( isset($groupname) ) {
        $download_url = $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $syllabus_section['permalink'] . '/' . ciniki_core_makePermalink($ciniki, $groupname) . '/download.pdf';
    } else {
        $download_url = $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $syllabus_section['permalink'] . '/download.pdf';
    }
*/
    //
    // Check for syllabus download
    //
/*    if( ( isset($groupname) 
            && isset($request['uri_split'][($request['cur_uri_pos']+2)])
            && $request['uri_split'][($request['cur_uri_pos']+2)] == 'download.pdf' 
        ) || (
            isset($request['uri_split'][($request['cur_uri_pos']+1)])
            && $request['uri_split'][($request['cur_uri_pos']+1)] == 'download.pdf' 
            )
        ) {
        //
        // Download the syllabus section pdf
        //
        $pdf_args = array(
            'festival_id' => $festival['id'],
            'section_id' => $syllabus_section['id'],
            'live-virtual' => isset($s['display-live-virtual']) ? $s['display-live-virtual'] : '',
            );
        if( isset($groupname) ) {
            $pdf_args['groupname'] = $groupname;
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'syllabusPDF');
        $rc = ciniki_musicfestivals_templates_syllabusPDF($ciniki, $tnid, $pdf_args);
        if( isset($rc['pdf']) ) {
            $filename = $festival['name'] . ' - ' . $syllabus_section['name'];
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
*/
    //
    // Check for section end dates
    //
/*    if( ($festival['flags']&0x08) == 0x08 ) {
        if( $syllabus_section['live_end_dt'] != '0000-00-00 00:00:00' ) {
            $section_live_dt = new DateTime($syllabus_section['live_end_dt'], new DateTimezone('UTC'));
            if( $section_live_dt < $dt ) {
                $festival['live'] = 'no';
            }
        }
        if( $syllabus_section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
            $section_virtual_dt = new DateTime($syllabus_section['virtual_end_dt'], new DateTimezone('UTC'));
            if( $section_virtual_dt < $dt ) {
                $festival['virtual'] = 'no';
            }
        }
        if( ($festival['flags']&0x10) == 0x10 ) {   // Adjudication Plus
            $festival['plus_live'] = $festival['live'];
        }
    }
*/
    //
    // Don't show titles or intros when displaying a pricelist
    //
/*    if( isset($s['layout']) && $s['layout'] == 'pricelist' && isset($s['section-id']) ) {
        $section['intros'] = 'no';
        $section['tableheader'] = 'multiprices';
        if( isset($s['title']) && $s['title'] != '' && isset($s['content']) && $s['content'] != '' ) {
            $blocks[] = array(
                'type' => 'text',
                'title_sequence' => $section['sequence'] == 1 ? 1 : 2,
                'title' => $s['title'],
                'content' => $s['content'],
                );
        } elseif( isset($s['title']) && $s['title'] != '' ) {
            $blocks[] = array(
                'type' => 'title',
                'title_sequence' => $section['sequence'] == 1 ? 1 : 2,
                'title' => $s['title'],
                );
        }
    } else {
        if( isset($syllabus_section['description']) && $syllabus_section['description'] != '' ) {
            $blocks[] = array(
                'type' => 'text',
                'title_sequence' => 1,
                'class' => 'musicfestival-syllabus-section',
                'title' => (isset($s['title']) ? $s['title'] . ($s['title'] != '' ? ' - ' : '') : 'Syllabus - ') . $syllabus_section['name'],
//                        . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
                'content' => $syllabus_section['description'],
                );
        } else { */
            $blocks[] = array(
                'type' => 'title', 
                'title_sequence' => 1,
                'class' => 'musicfestival-syllabus-section',
                'title' => (isset($s['title']) ? $s['title'] . ($s['title'] != '' ? ' - ' : ''): 'Syllabus - ') . $syllabus_section['name'],
//                        . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
                );
/*        } 
    }
*/
    //
    // Check if download button
    //
/*    if( isset($s['section-pdf']) && ($s['section-pdf'] == 'top' || $s['section-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => "buttons-top-{$syllabus_section['permalink']} musicfestival-syllabus-section",
            'list' => array(
                array(
                    'url' => $download_url,
                    'target' => '_blank',
                    'text' => 'Download ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') 
                        . 'Syllabus PDF for ' . $syllabus_section['name']
                        . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
                    ),
                ),
            );
    }
*/    
    //
    // Get the levels for this section
    //
/*    $level_strsql = '';
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x1000) ) {
        $strsql = "SELECT DISTINCT tags.tag_name, tags.permalink "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_class_tags AS tags ON ("
                . "classes.id = tags.class_id "
                . "AND tags.tag_type = 20 "
                . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $syllabus_section['id']) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY tags.tag_sort_name, tags.tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'tags', 'fname'=>'permalink', 
                'fields'=>array('name'=>'tag_name', 'permalink'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.971', 'msg'=>'Unable to load tags', 'err'=>$rc['err']));
        }
        $levels = array(
            array(
                'text' => 'All Classes',
//                'class' => (!isset($_GET['level']) ? 'selected' : ''),
                'selected' => (!isset($_GET['level']) ? 'yes' : ''),
                'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $syllabus_section['permalink'],
                ),
            );
        if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
            foreach($rc['tags'] as $tag) {
                if( isset($_GET['level']) && $_GET['level'] == $tag['permalink'] ) {
                    $level_strsql = "INNER JOIN ciniki_musicfestival_class_tags AS tags ON ("
                        . "classes.id = tags.class_id "
                        . "AND tags.tag_type = 20 "
                        . "AND tags.permalink = '" . ciniki_core_dbQuote($ciniki, $tag['permalink']) . "' "
                        . "AND tags.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") ";
                }
                $levels[] = array(
                    'text' => $tag['name'],
//                    'class' => (isset($_GET['level']) && $_GET['level'] == $tag['permalink'] ? 'selected' : ''),
                    'selected' => (isset($_GET['level']) && $_GET['level'] == $tag['permalink'] ? 'yes' : ''),
                    'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $syllabus_section['permalink'] . '?level=' . $tag['permalink'],
                    );
            }
        }
    }
*/
    //
    // Load the syllabus for the section
    //
    $strsql = "SELECT classes.id, "
        . "classes.uuid, "
        . "classes.festival_id, "
        . "classes.category_id, "
        . "categories.id AS category_id, "
        . "categories.name AS category_name, "
        . "categories.permalink AS category_permalink, "
//        . "categories.primary_image_id AS category_image_id, "
//        . "categories.synopsis AS category_synopsis, "
//        . "categories.description AS category_description, "
        . "sections.name AS section_name, "
        . "classes.code, "
        . "classes.name, "
        . "classes.synopsis, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.flags, "
        . "classes.feeflags, "
        . "classes.earlybird_fee, "
        . "classes.fee, "
        . "classes.virtual_fee, "
        . "classes.earlybird_plus_fee, "
        . "classes.plus_fee, "
        . "timeslots.groupname, "
        . "registrations.id AS registration_id, "
        . "registrations.display_name, "
        . "registrations.public_name, "
//        . "TIME_FORMAT(registrations.timeslot_time, '%l:%i %p') AS timeslot_time, "
//        . "TIME_FORMAT(registrations.finals_timeslot_time, '%l:%i %p') AS finals_timeslot_time, "
//        . "TIME_FORMAT(registrations.finals_timeslot_time, '%H%i') AS reg_finals_sort_time, "
        . "registrations.flags AS reg_flags, "
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
        . "registrations.level "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "categories.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id ";
    if( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live' ) {
        $strsql .= "AND (classes.feeflags&0x02) = 0x02 ";
    } elseif( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual' ) {
        $strsql .= "AND (classes.feeflags&0x08) = 0x08 ";
    }
        $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
//        . $level_strsql 
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
            . "registrations.timeslot_id = timeslots.id "
            . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
            . "timeslots.sdivision_id = divisions.id "
            . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_schedule_sections AS ssections ON ("
            . "divisions.ssection_id = ssections.id "
            . "AND ssections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $syllabus_section['id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "(divisions.flags&0x20) = 0x20 "
            . "OR (ssections.flags&0x20) = 0x20 "
            . ") ";
    if( isset($groupname) ) {
        $strsql .= "AND categories.groupname = '" . ciniki_core_dbQuote($ciniki, $groupname) . "' ";
    } 
    $strsql .= "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name, timeslots.groupname "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'permalink'=>'category_permalink', 
//                'image_id'=>'category_image_id', 
//                'synopsis'=>'category_synopsis', 'description'=>'category_description',
            )),
        array('container'=>'classes', 'fname'=>'id',  
            'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'section_name', 'category_name', 'synopsis',
                'permalink', 'sequence', 'flags', 'feeflags',
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                )),
        array('container'=>'groups', 'fname'=>'groupname', 'fields'=>array('name' => 'groupname')),
        array('container'=>'registrations', 'fname'=>'registration_id',  
            'fields'=>array('id'=>'registration_id', 'display_name', 'public_name', 'flags'=>'reg_flags',
//                'timeslot_time', 'finals_timeslot_time', 'reg_finals_sort_time',
                'title1', 'title2', 'title3', 'title4', 'title5', 'title6', 'title7', 'title8',
                'composer1', 'composer2', 'composer3', 'composer4', 'composer5', 'composer6', 'composer7', 'composer8',
                'movements1', 'movements2', 'movements3', 'movements4', 'movements5', 'movements6', 'movements7', 'movements8',
                'video_url1', 'video_url2', 'video_url3', 'video_url4', 'video_url5', 'video_url6', 'video_url7', 'video_url8',
                'participation', 'mark', 'placement', 'level', 
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
/*        //
        // Get the filters
        //
        if( isset($levels) && count($levels) > 1 ) {
            $blocks[] = array(
                'id' => 'filter',
                'type' => 'dropdown',
                'class' => 'musicfestival-syllabus-section',
                'list' => $levels,
                );
        } */

        foreach($categories as $category) {
/*            $intro = ($category['description'] != '' ? $category['description'] : ($category['synopsis'] != '' ? $category['synopsis'] : ''));
            if( $intro != '' && (!isset($section['intros']) || $section['intros'] == 'yes') ) {
                $blocks[] = array(
                    'type' => 'contentphoto', 
                    'id' => $category['permalink'],
                    'title' => $category['name'],
                    //'title' => (!isset($groupname) || $groupname != $category['name'] ? $category['name'] : ''), 
                    'class' => 'musicfestival-syllabus-section',
                    'content' => $intro,
                    'image-id' => $category['image_id'],
                    'image-position' => 'top-right',
                    'image-size' => 'medium',
                    );
            } */
            foreach($category['classes'] as $class) {
                foreach($class['groups'] as $group) {
                    //
                    // Check if online registrations enabled, and online registrations enabled for this class
                    //
                    $rc = ciniki_musicfestivals_classNameFormat($ciniki, $tnid, [
                        'code' => $class['code'],
                        'name' => $class['name'],
                        'category' => $class['category_name'],
                        'section' => $class['section_name'],
                        'format' => isset($s['class-format']) && $s['class-format'] != '' ? $s['class-format'] : 'code-category-class',
                        ]);
                    $name = $rc['name'];
                    if( $group['name'] != '' ) {
                        $name .= ' - ' . $group['name'];
                    }
                    foreach($group['registrations'] as $rid => $reg) {
                        $group['registrations'][$rid]['name'] = $reg['public_name'];
                        $group['registrations'][$rid]['titles'] = '';
                        if( isset($s['names']) && $s['names'] == 'private' ) {
                            $group['registrations'][$rid]['name'] = $reg['display_name'];
                        }
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
                                        $titles .= "<div class='video-title'><span class='perf-title'>{$rc['title']}</span>"
                                            . "<span class='perf-video'>"
                                            . "<a target='_blank' class='link' href='" . $registration["video_url{$i}"] . "'>Watch Video</a>"
                                            . "</span></div>";
                                    } else {
                                        $titles .= "<div class='perf-title'>{$rc['title']}</div>";
                                    }
                                }
                            }
                            $group['registrations'][$rid]['titles'] = $titles;
                        }
                    }

                    $columns = array();

                    if( ($class['flags']&0x0100) == 0x0100 && isset($s['mark']) && $s['mark'] == 'yes' ) {
                        $columns[] = array('label'=>'Mark', 'field'=>'mark', 'fold-label'=>'Mark:', 'class'=>'');
                    }
                    if( ($class['flags']&0x0200) == 0x0200 && isset($s['placement']) && $s['placement'] == 'yes' ) {
                        $columns[] = array('label'=>'Placement', 'field'=>'placement', 'class'=>'');
                    }
                    if( ($class['flags']&0x0400) == 0x0400 && isset($s['level']) && $s['level'] == 'yes' ) {
                        $columns[] = array('label'=>'Level', 'field'=>'level', 'fold-label'=>'Level:', 'class'=>'');
                    }
                    $columns[] = array('label'=>'Name', 'field'=>'name', 'class'=>'');
                    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
                        $columns[] = array('label'=>'Home Festival', 'field'=>'member_name', 'fold-label'=>'Home Festival:', 'class'=>'');
                    }
                    if( isset($s['titles']) && $s['titles'] == 'yes' ) {
                        $columns[] = array('label'=>'Titles', 'field'=>'titles', 'fold-label'=>'Titles:', 'class'=>'');
                    }
                    $blocks[] = array(
                        'type' => 'table',
                        'title' => $name,
                        'class' => 'musicfestival-timeslots limit-width limit-width-90 fold-at-50',
                        'headers' => 'no',
                        'columns' => $columns,
                        'rows' => $group['registrations'],
                        );  
                }
            }
        }
    }

    //
    // Check if download button
    //
/*    if( isset($s['section-pdf']) && ($s['section-pdf'] == 'bottom' || $s['section-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => "buttons-bottom-{$syllabus_section['permalink']} musicfestival-syllabus-section",
            'list' => array(
                array(
                    'url' => $download_url,
                    'target' => '_blank',
                    'text' => 'Download ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') 
                        . 'Syllabus PDF for ' . $syllabus_section['name']
                        . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
                    ),
                ),
            );
    }
*/

    if( isset($s['section-id']) ) {
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
}
?>
