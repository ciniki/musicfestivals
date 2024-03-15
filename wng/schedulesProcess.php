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
    // Load the schedules
    //
    if( isset($s['layout']) && $s['layout'] == 'division-buttons' ) {
        $strsql = "SELECT sections.id, "
            . "sections.name, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
            $strsql .= "AND ((sections.flags&0x20) = 0x20 OR (divisions.flags&0x20) = 0x20) "; // results published on website
        } else {
            $strsql .= "AND (sections.flags&0x10) = 0x10 "; // Schedule published on website
        }
        $strsql .= "ORDER BY sections.sequence, sections.name, divisions.division_date "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'text'=>'name',
                )),
            array('container'=>'divisions', 'fname'=>'division_id', 
                'fields'=>array('id'=>'division_id', 'name'=>'division_name', 'text'=>'division_name', 'division_date_text',
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
            foreach($sc['divisions'] as $did => $division) {
                $sections[$sid]['divisions'][$did]['permalink'] = ciniki_core_makePermalink($ciniki, $division['name']);
                $sections[$sid]['divisions'][$did]['url'] = $request['page']['path'] . '/' . $sections[$sid]['permalink'] . '/' . $sections[$sid]['divisions'][$did]['permalink'];
                if( isset($s['division-dates']) && $s['division-dates'] == 'yes' ) {
                    $sections[$sid]['divisions'][$did]['text'] .= '<br/>' . $division['division_date_text'];
                }
                if( isset($section_permalink) && $section_permalink == $sections[$sid]['permalink'] 
                    && isset($division_permalink) && $division_permalink == $sections[$sid]['divisions'][$did]['permalink'] 
                    ) {
                    $selected_division = $sections[$sid]['divisions'][$did];
                }
            }
        }
    } elseif( isset($s['layout']) && $s['layout'] == 'date-buttons' ) {
        $strsql = "SELECT sections.id, "
            . "sections.name, "
            . "divisions.ssection_id AS ssection_id, "
            . "divisions.id AS division_id, "
            . "divisions.name AS division_name, "
            . "CONCAT_WS(' - ', sections.name, divisions.name) AS text, "
            . "DATE_FORMAT(divisions.division_date, '%W, %M %D, %Y') AS division_date_text "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
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
                'fields'=>array('id'=>'division_id', 'ssection_id', 'name'=>'division_name', 'text'=>'text', 'division_date_text',
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.674', 'msg'=>'Unable to load schedule_sections', 'err'=>$rc['err']));
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
            . "sections.name "
            . "FROM ciniki_musicfestival_schedule_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_schedule_divisions AS divisions ON ("
                . "sections.id = divisions.ssection_id "
                . "AND divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        if( isset($s['results-only']) && $s['results-only'] == 'yes' ) {
            $strsql .= "AND ((sections.flags&0x20) = 0x20 OR (divisions.flags&0x20) = 0x20) "; // results published on website
        } else {
            $strsql .= "AND (sections.flags&0x10) = 0x10 "; // Schedule published on website
        }
        $strsql .= "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'text'=>'name')),
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
        $blocks[] = array(
            'type' => 'text',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => (isset($s['notreleased']) && $s['notreleased'] != '' ? $s['notreleased'] : 'The schedule has not yet been released.'),
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
    
    if( isset($selected_section) && isset($selected_division) ) {
        $section['settings']['section-id'] = $selected_section['id'];
        $section['settings']['division-id'] = $selected_division['id'];
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
    elseif( isset($s['layout']) && $s['layout'] == 'division-buttons' ) {
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
        foreach($sections as $section) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'schedule-buttons',
                'title' => $section['name'],
                'level' => 2,
                'list' => $section['divisions'],
                );
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
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'schedule-buttons',
            'list' => $sections,
            );
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
