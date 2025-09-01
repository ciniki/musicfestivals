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
    $base_url = $request['ssl_domain_base_url'] . $request['page']['path'];

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
    // Load the syllabus
    //
    $strsql = "SELECT syllabuses.id, "
        . "syllabuses.festival_id, "
        . "syllabuses.name "
        . "FROM ciniki_musicfestival_syllabuses AS syllabuses "
        . "WHERE syllabuses.id = '" . ciniki_core_dbQuote($ciniki, $s['syllabus-id']) . "' "
        . "AND syllabuses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'syllabus');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1037', 'msg'=>'Unable to load syllabus', 'err'=>$rc['err']));
    }
    if( !isset($rc['syllabus']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1038', 'msg'=>'Unable to find requested syllabus'));
    }
    $syllabus = $rc['syllabus'];
    $festival_id = $syllabus['festival_id'];

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
            'syllabus_id' => $syllabus['id'],
            'live-virtual' => isset($s['display-live-virtual']) ? $s['display-live-virtual'] : '',
            );
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
            $strsql .= "AND (classes.feeflags&0x02) = 0x02 ";
        } else {
            $strsql .= "AND (classes.feeflags&0x08) = 0x08 ";
        }
        $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $syllabus['id']) . "' "
            . "AND (sections.flags&0x01) = 0 " // Visible
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
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $syllabus['id']) . "' "
            . "AND (sections.flags&0x01) = 0 " // Visible
            . "ORDER BY sections.sequence, sections.name, categories.sequence "
            . "";
    } else {
        $strsql = "SELECT sections.id, "
            . "sections.permalink, "
            . "sections.name, "
            . "sections.primary_image_id, "
            . "sections.synopsis, "
            . "NULL AS groupname "
            . "FROM ciniki_musicfestival_sections AS sections "
            . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $syllabus['id']) . "' "
            . "AND (sections.flags&0x01) = 0 " // Visible
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
    foreach($sections as $sid => $sec) {
        if( isset($sec['groups']) ) {
            foreach($sec['groups'] as $gid => $group) {
                if( $group == null ) {
                    continue;
                }
                $permalink = ciniki_core_makePermalink($ciniki, $group['groupname']);
                if( $permalink != $gid ) {
                    $sections[$sid]['groups'][$permalink] = $group;
                    unset($sections[$sid]['groups'][$gid]);
                }
            }
        }
    }

    //
    // Add the title block
    //
    $blocks[] = array(
        'type' => 'title', 
        'title_sequence' => (isset($section['title_sequence']) && $section['title_sequence'] == 1 ? 1 : 2),
        'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
        );
    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text', 
//            'title_sequence' => (isset($section['title_sequence']) && $section['title_sequence'] == 1 ? 1 : 2),
//            'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
            'content' => $s['content'],
            );
    } /*else {
        $blocks[] = array(
            'type' => 'title', 
            'title_sequence' => (isset($section['title_sequence']) && $section['title_sequence'] == 1 ? 1 : 2),
            'title' => isset($s['title']) ? $s['title'] : 'Syllabus',
            );
    } */

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
            $section['groupname'] = $sections[$request['uri_split'][$request['cur_uri_pos']]]['groups'][$groupname]['groupname'];
//            $section['groupname'] = $groupname;
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
    // Check if pdf buttons only requested
    //
    if( isset($s['pdfbuttons-only']) && $s['pdfbuttons-only'] == 'yes' ) {
        foreach($sections as $sid => $section) {
            $sections[$sid]['url'] = $base_url . '/' . $section['permalink'] . '/download.pdf';
        }
        $blocks[] = [
            'type' => 'buttons',
            'items' => $sections,
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    } 

    //
    // Setup the download url for pdf
    //
    $download_url = $request['ssl_domain_base_url'] . $request['page']['path'] . '/download.pdf';

    //
    // Check for buttons at the top and bottom of page
    //
    $syllabus_buttons = ['top' => [], 'bottom' => []];
    foreach(['top', 'bottom'] AS $tp) {
        if( isset($s["syllabus-{$tp}-button-1-pdf"]) && $s["syllabus-{$tp}-button-1-pdf"] == 'yes' ) {
            $syllabus_buttons[$tp][] = [
                'url' => $download_url,
                'target' => '_blank',
                'text' => 'Download Complete ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') 
                    . 'Syllabus PDF'
                ];
        }
        for($i = 2; $i <= 5; $i++) {
            if( isset($s["syllabus-{$tp}-button-{$i}-text"]) && $s["syllabus-{$tp}-button-{$i}-text"] != '' ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'urlProcess');
                $rc = ciniki_wng_urlProcess($ciniki, $tnid, $request, 
                    isset($s["syllabus-{$tp}-button-{$i}-page"]) ? $s["syllabus-{$tp}-button-{$i}-page"] : 0, 
                    isset($s["syllabus-{$tp}-button-{$i}-url"]) ? $s["syllabus-{$tp}-button-{$i}-url"] : '');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.120', 'msg'=>'', 'err'=>$rc['err']));
                }
                $syllabus_buttons[$tp][] = [
                    'url' => $rc['url'],
                    'target' => $rc['target'],
                    'text' => $s["syllabus-{$tp}-button-{$i}-text"],
                    ];
            }
        }
    }

    //
    // Check if download button
    //
    if( isset($syllabus_buttons['top']) && count($syllabus_buttons['top']) > 0 ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => "buttons-top-syllabus musicfestival-syllabus",
            'list' => $syllabus_buttons['top'],
            );
    }
    
    //
    // Check if download button
    //
/*    if( isset($s['syllabus-pdf']) && ($s['syllabus-pdf'] == 'top' || $s['syllabus-pdf'] == 'both') ) {
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
    } */

    //
    // Check if class search is to be used
    //
    if( isset($s['live-search']) && $s['live-search'] == 'top' ) {
        $api_args = [
            'festival-id' => $festival['id'],
            'syllabus_id' => $syllabus['id'],
            'layout' => $s['layout'],
            'baseurl' => $request['ssl_domain_base_url'] . $request['page']['path'],
            ];
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
                $sections[$sid]['buttons'] .= "<a class='button' href='{$request['ssl_domain_base_url']}{$request['page']['path']}/{$section['permalink']}/{$groupname}'>{$group['groupname']}</a>";
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
                if( $group == null ) {
                    $group = ['groupname' => 'Other'];
                }
                $buttons[] = array(
                    'title' => $group['groupname'] == '' ? 'Other' : $group['groupname'],
                    'url' => "{$request['ssl_domain_base_url']}{$request['page']['path']}/{$section['permalink']}/{$groupname}",
                    );
            }
            
            $blocks[] = array(
                'type' => 'buttons',
                'section' => 'syllabus',
                'class' => 'musicfestival-syllabus syllabus-groupbuttons',
                'title' => $section['title'],
                'content' => $section['synopsis'],
                'level' => 2,
                'items' => $buttons,
                );
        }
    }

    // 
    // Display as Class List
    //
    elseif( isset($s['layout']) && $s['layout'] == 'classlist' ) {
        foreach($sections as $sid => $s) {
            $section['festival_id'] = $festival['id'];
            $section['section_permalink'] = $s['permalink'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
            $rc = ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
            if( isset($rc['blocks']) ) {
                foreach($rc['blocks'] as $bid => $block) {
                    // Skip title block
                    if( $bid == 0 ) {
                        continue;
                    }
                    $blocks[] = $block;
                }
            }
        }
    }

    // 
    // Display as Price List, no categories or category descriptions
    //
    elseif( isset($s['layout']) && $s['layout'] == 'pricelist' ) {
        foreach($sections as $sid => $s) {
            $section['festival_id'] = $festival['id'];
            $section['section_permalink'] = $s['permalink'];
            $section['intros'] = 'no';
            $section['tableheader'] = 'multiprices';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
            $rc = ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
            if( isset($rc['blocks']) ) {
                foreach($rc['blocks'] as $bid => $block) {
                    $blocks[] = $block;
                }
            }
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
            'class' => 'musicfestival-syllabus syllabus-buttons',
            'list' => $sections,
            );
    }

    //
    // Check if download button
    //
    if( isset($syllabus_buttons['bottom']) && count($syllabus_buttons['bottom']) > 0 ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => "buttons-bottom-syllabus musicfestival-syllabus",
            'list' => $syllabus_buttons['bottom'],
            );
    }
    
    //
    // Check if download button
    //
/*    if( isset($s['syllabus-pdf']) && ($s['syllabus-pdf'] == 'bottom' || $s['syllabus-pdf'] == 'both') ) {
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
    } */

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
