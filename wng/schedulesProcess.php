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
function ciniki_musicfestivals_wng_schedulesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.671', 'msg'=>"I'm sorry, the page you requested does not exist."));
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
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.672', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Check if schedule is displaying just live or just virtual
    //
    $ipv_sql = '';
    if( isset($s['ipv']) && $s['ipv'] == 'inperson' ) {
        $lv_word = 'Live ';
        $ipv_sql = "AND (registrations.participation = 0 OR registrations.participation = 2) ";
    } elseif( isset($s['ipv']) && $s['ipv'] == 'virtual' ) {
        $lv_word = 'Virtual ';
        $ipv_sql = "AND registrations.participation = 1 ";
    }

    //
    // Get the music festival details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'festivalLoad');
    $rc = ciniki_musicfestivals_wng_festivalLoad($ciniki, $tnid, $s['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

    $division_date_format = '%W, %M %D, %Y';
    if( isset($festival['schedule-date-format']) && $festival['schedule-date-format'] != '' ) {
        $division_date_format = $festival['schedule-date-format'];
    }

    //
    // Check if download of syllabus requested
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] == 'schedule.pdf' 
        ) {
        //
        // Download the schedule pdf
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'schedulePDF');
        $rc = ciniki_musicfestivals_templates_schedulePDF($ciniki, $tnid, array(
            'published' => 'yes',
            'festival_id' => $s['festival-id'],
            'ipv' => isset($s['ipv']) ? $s['ipv'] : '',
            'division_header_format' => isset($s['division_header_format']) ? $s['division_header_format'] : '',
            'division_header_labels' => isset($s['division_header_labels']) ? $s['division_header_labels'] : '',
            'names' => isset($s['names']) ? $s['names'] : '',
            'titles' => isset($s['titles']) ? $s['titles'] : '',
            'video_urls' => isset($s['video_urls']) ? $s['video_urls'] : '',
            'section_page_break' => isset($s['section_page_break']) ? $s['section_page_break'] : '',
            ));
        if( isset($rc['pdf']) ) {
            $filename = $festival['name'] . ' Schedule';
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
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.673', 'msg'=>"No festival specified"));
    }

    if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) && $request['uri_split'][($request['cur_uri_pos']+1)] != 'schedule.pdf' ) {
        $section_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
    }
    if( isset($s['layout']) && $s['layout'] == 'date-buttons' && isset($request['uri_split'][($request['cur_uri_pos']+1)]) && $request['uri_split'][($request['cur_uri_pos']+1)] != 'schedule.pdf' ) {
        $date_permalink = $request['uri_split'][($request['cur_uri_pos']+1)];
    }
    if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) && $request['uri_split'][($request['cur_uri_pos']+2)] != 'schedule.pdf' ) {
        $division_permalink = $request['uri_split'][($request['cur_uri_pos']+2)];
    }

    //
    // Check if needs to be restricted to sections that have live or virtual
    //
    $ipv_sections_sql = '';
    if( isset($ipv_sql) && $ipv_sql != '' ) {
        $strsql = "SELECT DISTINCT sections.id "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "registrations.timeslot_id = timeslots.id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "timeslots.sdivision_id = divisions.id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_sections AS sections ON ("
                . "divisions.ssection_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . $ipv_sql
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.musicfestivals', 'sections', 'id');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.763', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
        }
        $ipv_section_ids = isset($rc['sections']) ? $rc['sections'] : array();
        if( count($ipv_section_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $ipv_sections_sql = "AND sections.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ipv_section_ids) . ") ";
        }
    }

    //
    // Load the schedules
    //
    if( isset($s['layout']) && ($s['layout'] == 'section-grouped-buttons' || $s['layout'] == 'division-buttons' || $s['layout'] == 'division-grouped-buttons' || $s['layout'] == 'division-buttons-name') ) {
        $strsql = "SELECT sections.id, "
            . "sections.name, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "IFNULL(locations.name, '') AS location_name, "
            . "DATE_FORMAT(divisions.division_date, '" . ciniki_core_dbQuote($ciniki, $division_date_format) . "') AS division_date_text, "
            . "DATE_FORMAT(divisions.division_date, '%Y-%m-%d') AS division_ymd "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_schedule_timeslots AS timeslots ON ("
                . "divisions.id = timeslots.sdivision_id "
                . "AND timeslots.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                . "divisions.location_id = locations.id "
                . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
                . "("
                    . "timeslots.id = registrations.timeslot_id "
                    . "OR timeslots.id = registrations.finals_timeslot_id "
                    . ") "
                . $ipv_sql
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . $ipv_sections_sql;
        if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
            $strsql .= "AND ((sections.flags&0x20) = 0x20 OR (divisions.flags&0x20) = 0x20) "; // results published on website
        } else {
            $strsql .= "AND (sections.flags&0x10) = 0x10 "; // Schedule published on website
        }
        if( $s['layout'] == 'division-buttons-name' ) {
            $strsql .= "ORDER BY sections.sequence, sections.name, divisions.name, divisions.division_date, divisions.id, location_name ";
        } else {
            $strsql .= "ORDER BY sections.sequence, sections.name, divisions.division_date, divisions.name, location_name ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'text'=>'name',
                )),
            array('container'=>'divisions', 'fname'=>'division_id', 
                'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'text'=>'division_name', 
                    'division_date_text', 'division_ymd', 'location_name',
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.738', 'msg'=>'Unable to load schedule_sections', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();
        foreach($sections as $sid => $sc) {
            $sections[$sid]['permalink'] = ciniki_core_makePermalink($ciniki, $sc['name']);
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'];
            if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] ) {
                $selected_section = $sections[$sid];
            }
            foreach($sc['divisions'] as $did => $division) {
                $sections[$sid]['divisions'][$did]['permalink'] = ciniki_core_makePermalink($ciniki, $division['name']);
                $sections[$sid]['divisions'][$did]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'] . '/' . $sections[$sid]['divisions'][$did]['permalink'];
                if( isset($s['division-dates']) && $s['division-dates'] == 'yes' ) {
                    $sections[$sid]['divisions'][$did]['text'] .= '<br/>' . $division['division_date_text'];
                }
                if( isset($s['division-locations']) && $s['division-locations'] == 'yes' ) {
                    $sections[$sid]['divisions'][$did]['text'] .= '<br/>' . $division['location_name'];
                }
                if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] 
                    && isset($division_permalink) && $division_permalink == $sections[$sid]['divisions'][$did]['permalink'] 
                    ) {
                    $selected_division = $sections[$sid]['divisions'][$did];
                    $selected_division_ids[] = $sections[$sid]['divisions'][$did]['id'];
                }
            }
        }
    } elseif( isset($s['layout']) && $s['layout'] == 'date-buttons' ) {
        $strsql = "SELECT sections.id, "
            . "sections.name, "
            . "divisions.ssection_id AS ssection_id, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "IFNULL(locations.name, '') AS location_name, "
            . "CONCAT_WS(' - ', sections.name, divisions.name) AS text, "
            . "DATE_FORMAT(divisions.division_date, '" . ciniki_core_dbQuote($ciniki, $division_date_format) . "') AS division_date_text, "
            . "DATE_FORMAT(divisions.division_date, '%Y-%m-%d') AS division_ymd "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                . "divisions.location_id = locations.id "
                . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . $ipv_sections_sql;
        if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
            $strsql .= "AND ((sections.flags&0x20) = 0x20 OR (divisions.flags&0x20) = 0x20) "; // results published on website
        } else {
            $strsql .= "AND (sections.flags&0x10) = 0x10 "; // Schedule published on website
        }
        $strsql .= "ORDER BY divisions.division_date, sections.sequence, sections.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'dates', 'fname'=>'division_date_text', 
                'fields'=>array('division_date_text',
                )),
            array('container'=>'divisions', 'fname'=>'division_id', 
                'fields'=>array('id'=>'division_id', 'ssection_id', 'name'=>'division_name', 'text'=>'text', 
                    'division_date_text', 'division_ymd', 'location_name',
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.739', 'msg'=>'Unable to load schedule_sections', 'err'=>$rc['err']));
        }
        $dates = isset($rc['dates']) ? $rc['dates'] : array();
        foreach($dates as $sid => $dt) {
            $dates[$sid]['permalink'] = ciniki_core_makePermalink($ciniki, $dt['division_date_text']);
            $dates[$sid]['url'] = $request['page']['path'] . '/' . $dates[$sid]['permalink'];
            if( isset($date_permalink) && $date_permalink == $dates[$sid]['permalink'] ) {
                $selected_date = $dates[$sid];
            }
            foreach($dt['divisions'] as $did => $division) {
                $dates[$sid]['divisions'][$did]['permalink'] = ciniki_core_makePermalink($ciniki, $division['name']);
                $dates[$sid]['divisions'][$did]['url'] = $request['page']['path'] . '/' . $dates[$sid]['permalink'] . '/' . $dates[$sid]['divisions'][$did]['permalink'];
                if( isset($s['division-dates']) && $s['division-dates'] == 'yes' ) {
                    $dates[$sid]['divisions'][$did]['text'] .= '<br/>' . $division['division_date_text'];
                }
                if( isset($date_permalink) && $date_permalink == $dates[$sid]['permalink'] 
                    && isset($division_permalink) && $division_permalink == $dates[$sid]['divisions'][$did]['permalink'] 
                    ) {
                    $selected_division = $dates[$sid]['divisions'][$did];
                }
            }
        }
    } else {
        $strsql = "SELECT sections.id, "
            . "sections.name, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "IFNULL(locations.name, '') AS location_name, "
            . "CONCAT_WS(' - ', sections.name, divisions.name) AS text, "
            . "DATE_FORMAT(divisions.division_date, '%Y-%m-%d') AS division_ymd "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_locations AS locations ON ("
                . "divisions.location_id = locations.id "
                . "AND locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . $ipv_sections_sql;
        if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
            $strsql .= "AND ((sections.flags&0x20) = 0x20 OR (divisions.flags&0x20) = 0x20) "; // results published on website
        } else {
            $strsql .= "AND (sections.flags&0x10) = 0x10 "; // Schedule published on website
        }
        $strsql .= "ORDER BY sections.sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'text'=>'name'),
                ),
            array('container'=>'divisions', 'fname'=>'division_id', 
                'fields'=>array('id'=>'division_id', 'ssection_id'=>'id', 'name'=>'division_name', 'text', 
                    'division_ymd', 'location_name',
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.674', 'msg'=>'Unable to load schedule_sections', 'err'=>$rc['err']));
        }
        $sections = isset($rc['sections']) ? $rc['sections'] : array();
        foreach($sections as $sid => $sc) {
            $sections[$sid]['permalink'] = ciniki_core_makePermalink($ciniki, $sc['name']);
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'];
            if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] ) {
                $selected_section = $sections[$sid];
            }
        }
    }

    //
    // Check if download button
    //
    if( isset($s['complete-pdf']) && ($s['complete-pdf'] == 'top' || $s['complete-pdf'] == 'both') ) {
        $top_download_block = array(
            'type' => 'buttons',
            'list' => array(
                array(
                    'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/schedule.pdf',
                    'target' => '_blank',
                    'text' => "Download Complete " . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . "Schedule PDF",
                    ),
                ),
            );
    }

    //
    // Check if any sections have been published yet
    //
    if( (isset($sections) && count($sections) == 0) 
        || (isset($dates) && count($dates) == 0)
        ) {
        $msg = 'The schedule has not been released.';
        if( isset($s['notreleased']) && $s['notreleased'] != '' ) {
            $msg = $s['notreleased'];
        }
        elseif( isset($s['results-only'])  && $s['results-only'] == 'yes' ) {
            $msg = 'The results have not been released.';
        }
        $blocks[] = array(
            'type' => 'text',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => $msg,
            );
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }

    //
    // Add the title block
    //
    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => $s['content'],
            );
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : 'Schedule',
            );
    }

    //
    // Check if class search is to be used
    //
    if( isset($s['live-search']) && $s['live-search'] == 'top' ) {
        $api_args = [
            'festival-id' => $festival['id'],
            'layout' => $s['layout'],
            'baseurl' => $request['ssl_domain_base_url'] . $request['page']['path'],
            ];
        if( isset($section['syllabus_id']) ) {
            $api_args['syllabus_id'] = $section['syllabus_id'];
        }
        $blocks[] = [
            'type' => 'livesearch',
            'label' => 'Search Class or Name',
            'id' => $section['sequence'],
            'api-search-url' => $request['api_url'] . '/ciniki/musicfestivals/scheduleSearch',
            'api-args' => $api_args,
            ];
    }

    //
    // Show todays divisions
    //
    if( isset($s['today-divisions']) && $s['today-divisions'] == 'yes' ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
//        $dt = new DateTime('2025-02-19', new DateTimezone($intl_timezone));
        $todays_date = $dt->format('Y-m-d');
        $divisions = [];
        foreach($sections as $sc) {
            if( isset($sc['divisions']) ) {
                foreach($sc['divisions'] as $div) {
                    if( $div['division_ymd'] == $todays_date ) {
                        $divisions[] = [
                            'text' => $div['location_name'] 
                                . ($div['location_name'] != '' ? '<br/>' : '') . $div['name'],
                            'url' => $div['url'],
                            ];
                    }
                }
            }
        } 
        if( count($divisions) > 0 ) {
            $todays_block = array(
                'type' => 'buttons',
                'class' => 'schedule-buttons',
                'title' => 'Schedule for ' . $dt->format('l, F jS, Y'),
                'level' => 2,
                'list' => $divisions,
                );
        }
    }


    if( isset($selected_section) && isset($selected_division) ) {
        $section['settings']['section-id'] = $selected_section['id'];
        if( $s['layout'] == 'division-buttons-name' && isset($selected_division_ids) && count($selected_division_ids) > 1 ) {
            $section['settings']['division-ids'] = $selected_division_ids;
        } else {
            $section['settings']['division-id'] = $selected_division['id'];
        }
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'scheduleSectionProcess');
        return ciniki_musicfestivals_wng_scheduleSectionProcess($ciniki, $tnid, $request, $section);
    }
    elseif( isset($selected_date) && isset($selected_division) ) {
        $section['settings']['section-id'] = $selected_division['ssection_id'];
        $section['settings']['division-id'] = $selected_division['id'];
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'scheduleSectionProcess');
        return ciniki_musicfestivals_wng_scheduleSectionProcess($ciniki, $tnid, $request, $section);
    }
    elseif( isset($selected_section) ) {
        $section['settings']['section-id'] = $selected_section['id'];
        $request['cur_uri_pos']++;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'scheduleSectionProcess');
        return ciniki_musicfestivals_wng_scheduleSectionProcess($ciniki, $tnid, $request, $section);
    }
    //
    // Display the buttons for the list of schedules
    //
    elseif( isset($s['layout']) && ($s['layout'] == 'division-buttons' || $s['layout'] == 'division-grouped-buttons' || $s['layout'] == 'division-buttons-name') ) {
        if( isset($division_permalink) && $division_permalink != '' ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => 'Schedule not found',
                );
        }
        if( isset($top_download_block) ) {
            $blocks[] = $top_download_block;
        }
        if( isset($todays_block) ) {
            $blocks[] = $todays_block;
        }
        foreach($sections as $section) {
            if( $s['layout'] == 'division-grouped-buttons' ) {
                $groups = array();
                foreach($section['divisions'] as $division) {
                    if( preg_match("/^(.*)\s+-\s+([^-]+)$/", $division['name'], $m) ) {
                        $name = $m[1];
                        $button = "<a class='button' href='{$division['url']}'>{$m[2]}</a>";
                    } else {
                        $name = $division['name'];
                        $button = "<a class='button' href='{$division['url']}'>{$division['name']}</a>";
                    }
                    if( !isset($groups[$name]) ) {
                        $groups[$name] = array(
                            'title' => $name,
                            'buttons' => $button,
                            );
                    } else {
                        $groups[$name]['buttons'] .= $button;
                    }
                }
                $blocks[] = array(
                    'type' => 'title',
                    'level' => 2,
                    'title' => $section['name'],
                    );
                $blocks[] = array(
                    'type' => 'table',
                    'section' => 'schedule-divisions',
                    'headers' => 'no',
                    'class' => 'fold-at-50 schedule-grouped-buttons',
                    'columns' => array(
                        array('label' => 'Section', 'fold-label'=>'', 'field'=>'title', 'class'=>'section-title'),
                        array('label' => 'Buttons', 'fold-label'=>'', 'field'=>'buttons', 'class'=>'alignleft fold-alignleft buttons'),
                        ),
                    'rows' => $groups,
                    );
            } else {
                if( $s['layout'] == 'division-buttons-name' ) {
                    uasort($section['divisions'], function($a, $b) {
                        return strnatcmp($a['name'], $b['name']);
                        });
                    if( (!isset($s['division-dates']) || $s['division-dates'] != 'yes')
                        && (!isset($s['division-locations']) || $s['division-locations'] != 'yes')
                        ) {
                        $prev_name = '';
                        foreach($section['divisions'] as $did => $division) {
                            if( $prev_name == $division['name'] ) {
                                unset($section['divisions'][$did]);
                            }
                            $prev_name = $division['name'];
                        }
                    }
                }
                $blocks[] = array(
                    'type' => 'buttons',
                    'class' => 'schedule-buttons',
                    'title' => $section['name'],
                    'level' => 2,
                    'list' => $section['divisions'],
                    );
            }
        }
    }
    elseif( isset($s['layout']) && $s['layout'] == 'date-buttons' ) {
        if( isset($division_permalink) && $division_permalink != '' ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => 'Schedule not found',
                );
        }
        if( isset($top_download_block) ) {
            $blocks[] = $top_download_block;
        }
        if( isset($todays_block) ) {
            $blocks[] = $todays_block;
        }
        foreach($dates as $dt) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'schedule-buttons',
                'title' => $dt['division_date_text'],
                'level' => 2,
                'list' => $dt['divisions'],
                );
        }
    }
    else {
        if( isset($section_permalink) && $section_permalink != '' ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error', 
                'content' => 'Schedule not found',
                );
        }
        if( isset($top_download_block) ) {
            $blocks[] = $top_download_block;
        }
        if( isset($todays_block) ) {
            $blocks[] = $todays_block;
        }
        if( isset($s['layout']) && $s['layout'] == 'section-grouped-buttons' ) {
            foreach($sections as $sid => $section) {    
                $sections[$sid]['buttons'] = '';
                foreach($section['divisions'] as $did => $division) {
                    $sections[$sid]['buttons'] .= "<a class='button' href='{$division['url']}'>{$division['name']}</a>";
                }
            }
            $blocks[] = array(
                'type' => 'table',
                'section' => 'schedule-divisions',
                'headers' => 'no',
                'class' => 'fold-at-50 schedule-grouped-buttons',
                'columns' => array(
                    array('label' => 'Section', 'fold-label'=>'', 'field'=>'name', 'class'=>'section-title'),
                    array('label' => 'Buttons', 'fold-label'=>'', 'field'=>'buttons', 'class'=>'alignleft fold-alignleft buttons'),
                    ),
                'rows' => $sections,
                );
        } else {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'schedule-buttons',
                'list' => $sections,
                );
        }
    }

    //
    // Check if download button
    //
    if( isset($s['complete-pdf']) && ($s['complete-pdf'] == 'bottom' || $s['complete-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'list' => array(
                array(
                    'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/schedule.pdf',
                    'target' => '_blank',
                    'text' => "Download Complete " . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . "Schedule PDF",
                    ),
                ),
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
