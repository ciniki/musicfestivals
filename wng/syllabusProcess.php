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
function ciniki_musicfestivals_wng_syllabusProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.210', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.230', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.212', 'msg'=>"No festival specified"));
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
    }

    //
    // Check if download of syllabus requested
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] == 'download.pdf' 
        ) {
        //
        // Download the syllabus section pdf
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'syllabusPDF');
        $rc = ciniki_musicfestivals_templates_syllabusPDF($ciniki, $tnid, array(
            'festival_id' => $s['festival-id'],
            ));
        if( isset($rc['pdf']) ) {
            $filename = $festival['name'];
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

    //
    // Get the list of sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.primary_image_id, "
        . "sections.synopsis "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND (sections.flags&0x01) = 0 "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'permalink', 
            'fields'=>array('id', 'permalink', 'title'=>'name', 'image-id'=>'primary_image_id', 'synopsis'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.213', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
        'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
        );

    //
    // Check for syllabus section requested
    //
    if( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $request['cur_uri_pos']++;
        if( isset($sections[$request['uri_split'][$request['cur_uri_pos']]]) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
            return ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
        } else {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Section not found',
                );
        }
    }

    //
    // Check if download button
    //
    if( isset($s['syllabus-pdf']) && ($s['syllabus-pdf'] == 'top' || $s['syllabus-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'list' => array(
                array(
                    'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/download.pdf',
                    'target' => '_blank',
                    'text' => 'Download Complete Syllabus PDF',
                    ),
                ),
            );
    }

    //
    // Display as trading cards
    //
    if( isset($s['layout']) && $s['layout'] == 'tradingcards' ) {
        $padding = '';
        if( isset($s['thumbnail-format']) && $s['thumbnail-format'] == 'square-padded' && isset($s['thumbnail-padding-color']) ) {
            $padding = $s['thumbnail-padding-color'];
        }
        foreach($sections as $sid => $section) {
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
            $sections[$sid]['button-class'] = isset($s['button-class']) && $s['button-class'] != '' ? $s['button-class'] : 'button';
            $sections[$sid]['button-1-text'] = 'View Syllabus';
            $sections[$sid]['button-1-url'] = $request['page']['path'] . '/' . $section['permalink'];
        }
        $blocks[] = array(
            'type' => 'tradingcards',
            'padding' => $padding,
            'items' => $sections,
            );
    } 

    //
    // Default to gallery
    //
    else {
        foreach($sections as $sid => $section) {
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
            $sections[$sid]['image-ratio'] = (isset($s['image-ratio']) ? $s['image-ratio'] : '4-3');
            $sections[$sid]['title-position'] = (isset($s['title-position']) ? $s['title-position'] : 'overlay-bottomhalf');
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
        }
        $blocks[] = array(
            'type' => 'imagebuttons',
            'items' => $sections,
            );
    }

    //
    // Check if download button
    //
    if( isset($s['syllabus-pdf']) && ($s['syllabus-pdf'] == 'bottom' || $s['syllabus-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'list' => array(
                array(
                    'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/download.pdf',
                    'target' => '_blank',
                    'text' => 'Download Complete Syllabus PDF',
                    ),
                ),
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
