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
    if( !isset($s['syllabus-id']) || $s['syllabus-id'] == '' || $s['syllabus-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.212', 'msg'=>"No syllabus specified"));
    }

    //
    // Check if syllabus is displaying just live or just virtual
    //
    if( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live' ) {
        $lv_word = 'Live ';
    } elseif( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual' ) {
        $lv_word = 'Virtual ';
    }

    //
    // Check if syllabus-id contains - and is a combination of festival_id+syllabus
    //
    if( preg_match("/^(.*)\-(.*)$/", $s['syllabus-id'], $m) ) {
        $festival_id = $m[1];
        $section['syllabus'] = $m[2];
    } else {
        $festival_id = $s['syllabus-id'];
    }

    //
    // Get the music festival details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'festivalLoad');
    $rc = ciniki_musicfestivals_wng_festivalLoad($ciniki, $tnid, $festival_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];

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
        $pdf_args = array(
            'festival_id' => $festival['id'],
            'live-virtual' => isset($s['display-live-virtual']) ? $s['display-live-virtual'] : '',
            );
        if( isset($section['syllabus']) ) {
            $pdf_args['syllabus'] = $section['syllabus'];
        }
        $rc = ciniki_musicfestivals_templates_syllabusPDF($ciniki, $tnid, $pdf_args);
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
    if( isset($s['display-live-virtual']) && in_array($s['display-live-virtual'], ['live','virtual']) ) {
        $strsql = "SELECT sections.id, "
            . "sections.permalink, "
            . "sections.name, "
            . "sections.primary_image_id, "
            . "sections.synopsis, "
            . "categories.groupname "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "sections.id = categories.section_id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id ";
        if( $s['display-live-virtual'] == 'live' ) {
            $strsql .= "AND classes.fee > 0 ";
        } else {
            $strsql .= "AND classes.virtual_fee > 0 ";
        }
        $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' ";
        if( isset($section['syllabus']) ) {
            $strsql .= "AND sections.syllabus = '" . ciniki_core_dbQuote($ciniki, $section['syllabus']) . "' ";
        }
        $strsql .= "AND (sections.flags&0x01) = 0 "
            . "ORDER BY sections.sequence, sections.name "
            . "";
    } elseif( isset($s['layout']) && ($s['layout'] == 'groups' || $s['layout'] == 'groupbuttons') ) {
        $strsql = "SELECT sections.id, "
            . "sections.permalink, "
            . "sections.name, "
            . "sections.primary_image_id, "
            . "sections.synopsis, "
            . "IFNULL(categories.groupname, '') AS groupname "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "sections.id = categories.section_id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' ";
        if( isset($section['syllabus']) ) {
            $strsql .= "AND sections.syllabus = '" . ciniki_core_dbQuote($ciniki, $section['syllabus']) . "' ";
        }
        $strsql .= "AND (sections.flags&0x01) = 0 "
            . "ORDER BY sections.sequence, sections.name, categories.sequence "
            . "";
    } else {
        $strsql = "SELECT sections.id, "
            . "sections.permalink, "
            . "sections.name, "
            . "sections.primary_image_id, "
            . "sections.synopsis, "
            . "'' AS groupname "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' ";
        if( isset($section['syllabus']) ) {
            $strsql .= "AND sections.syllabus = '" . ciniki_core_dbQuote($ciniki, $section['syllabus']) . "' ";
        }
        $strsql .= "AND (sections.flags&0x01) = 0 "
            . "ORDER BY sections.sequence, sections.name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'permalink', 
            'fields'=>array('id', 'permalink', 'title'=>'name', 'image-id'=>'primary_image_id', 'synopsis'),
            ),
        array('container'=>'groups', 'fname'=>'groupname',
            'fields'=>array('groupname'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.213', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Add the title block
    //
    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
            'content' => $s['content'],
            );
    } else {
        $blocks[] = array(
            'type' => 'title', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
            );
    }

    //
    // Check for syllabus section requested
    //
    if( isset($s['layout']) && ($s['layout'] == 'groups' || $s['layout'] == 'groupbuttons') 
        && isset($request['uri_split'][($request['cur_uri_pos']+2)])
        && $request['uri_split'][($request['cur_uri_pos']+2)] != '' 
        ) {
        $request['cur_uri_pos']++;
        $groupname = urldecode($request['uri_split'][($request['cur_uri_pos']+1)]);
        if( $groupname == 'Other' || $groupname == 'other' ) {
            $section['groupname'] = '';
            $section['festival_id'] = $festival['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
            return ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
        }
        elseif( isset($sections[$request['uri_split'][$request['cur_uri_pos']]]['groups'][$groupname]) ) {
            $section['groupname'] = $groupname;
            $section['festival_id'] = $festival['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
            return ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
        } else {
            $request['cur_uri_pos']--;
        }
        //
        // Nothing returned a section, generate error
        //
        $blocks[] = array(
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Section not found',
            );
    } 
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+1)])
        && $request['uri_split'][($request['cur_uri_pos']+1)] != '' 
        ) {
        $request['cur_uri_pos']++;
        if( isset($sections[$request['uri_split'][$request['cur_uri_pos']]]) ) {
            $section['festival_id'] = $festival['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
            return ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
        } else {
            $request['cur_uri_pos']--;
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
                    'text' => "Download Complete " . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . "Syllabus PDF",
                    ),
                ),
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
        if( isset($section['syllabus']) ) {
            $api_args['syllabus'] = $section['syllabus'];
        }
        $blocks[] = [
            'type' => 'livesearch',
            'label' => 'Search Classes',
            'id' => $section['sequence'],
            'api-search-url' => $request['api_url'] . '/ciniki/musicfestivals/classSearch',
            'api-args' => $api_args,
            ];
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
    // Display as image buttons
    //
    elseif( isset($s['layout']) && $s['layout'] == 'imagebuttons' ) {
        foreach($sections as $sid => $section) {
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
    // Display as table with groups
    //
    elseif( isset($s['layout']) && $s['layout'] == 'groups' ) {
        foreach($sections as $sid => $section) {
            $sections[$sid]['buttons'] = '';
            foreach($section['groups'] as $groupname => $group) {
                if( $sections[$sid]['buttons'] != '' ) {
                    $sections[$sid]['buttons'] .= ' ';
                }
                if( $groupname == '' ) {
                    $groupname = 'Other';
                }
                $sections[$sid]['buttons'] .= "<a class='button' href='{$request['ssl_domain_base_url']}{$request['page']['path']}/{$section['permalink']}/" . urlencode($groupname) . "'>{$groupname}</a>";
            }
        }
        $blocks[] = array(
            'type' => 'table',
            'section' => 'syllabus',
            'headers' => 'no',
            'class' => 'fold-at-50 musicfestival-syllabus syllabus-groups',
            'columns' => array(
                array('label'=>'Section', 'fold-label'=>'', 'field'=>'title', 'class'=>'section-title'),
                array('label'=>'Buttons', 'fold-label'=>'', 'field'=>'buttons', 'class'=>'align-left fold-alignleft buttons'),
                ),
            'rows' => $sections,
            );
    }

    //
    // Display as table with groups
    //
    elseif( isset($s['layout']) && $s['layout'] == 'groupbuttons' ) {
        foreach($sections as $sid => $section) {
            $buttons = array();
            foreach($section['groups'] as $groupname => $group) {
                $buttons[] = array(
                    'title' => $groupname == '' ? 'Other' : $groupname,
                    'url' => "{$request['ssl_domain_base_url']}{$request['page']['path']}/{$section['permalink']}/" . urlencode($groupname),
                    );
            }
            
            $blocks[] = array(
                'type' => 'buttons',
                'section' => 'syllabus',
                'class' => 'musicfestival-syllabus syllabus-groupbuttons',
                'title' => $section['title'],
                'level' => 2,
                'items' => $buttons,
                );
        }
    }
    
    //
    // Default to buttons
    //
    else {
        foreach($sections as $sid => $section) {
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
//            $sections[$sid]['image-ratio'] = (isset($s['image-ratio']) ? $s['image-ratio'] : '4-3');
//            $sections[$sid]['title-position'] = (isset($s['title-position']) ? $s['title-position'] : 'overlay-bottomhalf');
            $sections[$sid]['url'] = $request['page']['path'] . '/' . $section['permalink'];
        }
        $blocks[] = array(
            'type' => 'buttons',
            'class' => 'syllabus-buttons',
            'list' => $sections,
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
                    'text' => "Download Complete " . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . "Syllabus PDF",
                    ),
                ),
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
