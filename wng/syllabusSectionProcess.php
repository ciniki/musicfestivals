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
function ciniki_musicfestivals_wng_syllabusSectionProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.214', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.215', 'msg'=>"No festival specified"));
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
    if( !isset($section['festival_id']) || $section['festival_id'] == '' || $section['festival_id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.247', 'msg'=>"No festival specified"));
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

    $section_permalink = $request['uri_split'][$request['cur_uri_pos']];
    $base_url = $request['base_url'] . $request['page']['path'];

    //
    // Check for syllabus section requested
    //
    if( !isset($request['uri_split'][$request['cur_uri_pos']])
        || $request['uri_split'][$request['cur_uri_pos']] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.216', 'msg'=>"No syllabus specified"));
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
        . "sections.primary_image_id, "
        . "sections.synopsis, ";
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
        . "ORDER BY sections.sequence, sections.name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.217', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.218', 'msg'=>'Unable to find requested section'));
    }
    $section = $rc['section'];

    //
    // Check if section has other deadlines
    //
    $dt = new DateTime('now', new DateTimezone('UTC'));
    if( $section['live_end_dt'] != '' && $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
        $live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
        $festival['live'] = ($live_dt > $dt ? 'yes' : 'no');
        if( ($festival['flags']&0x10) == 0x10 ) {   // Adjudication Plus
            $festival['plus_live'] = $festival['live'];
        }
    }
    if( $section['virtual_end_dt'] != '' && $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
        $virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
        $festival['virtual'] = ($virtual_dt > $dt ? 'yes' : 'no');
    }
  
    if( isset($groupname) ) {
        $download_url = $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $section['permalink'] . '/' . $groupname . '/download.pdf';
    } else {
        $download_url = $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $section['permalink'] . '/download.pdf';
    }

    //
    // Check for syllabus download
    //
    if( ( isset($groupname) 
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
            'section_id' => $section['id'],
            'live-virtual' => isset($s['display-live-virtual']) ? $s['display-live-virtual'] : '',
            );
        if( isset($groupname) ) {
            $pdf_args['groupname'] = $groupname;
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'syllabusPDF');
        $rc = ciniki_musicfestivals_templates_syllabusPDF($ciniki, $tnid, $pdf_args);
        if( isset($rc['pdf']) ) {
            $filename = $festival['name'] . ' - ' . $section['name'];
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
    // Check for section end dates
    //
    if( ($festival['flags']&0x08) == 0x08 ) {
        if( $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
            $section_live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
            if( $section_live_dt < $dt ) {
                $festival['live'] = 'no';
            }
        }
        if( $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
            $section_virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
            if( $section_virtual_dt < $dt ) {
                $festival['virtual'] = 'no';
            }
        }
        if( ($festival['flags']&0x10) == 0x10 ) {   // Adjudication Plus
            $festival['plus_live'] = $festival['live'];
        }
    }
  
    if( isset($section['description']) && $section['description'] != '' ) {
        $blocks[] = array(
            'type' => 'text',
            'level' => 1,
            'class' => 'musicfestival-syllabus-section',
            'title' => (isset($s['title']) ? $s['title'] . ($s['title'] != '' ? ' - ' : '') : 'Syllabus - ') . $section['name']
                    . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
            'content' => $section['description'],
            );
    } else {
        $blocks[] = array(
            'type' => 'title', 
            'level' => 1,
            'class' => 'musicfestival-syllabus-section',
            'title' => (isset($s['title']) ? $s['title'] : 'Syllabus') . ' - ' . $section['name']
                    . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
            );
    }

    //
    // Check if download button
    //
    if( isset($s['section-pdf']) && ($s['section-pdf'] == 'top' || $s['section-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => "buttons-top-{$section['permalink']} musicfestival-syllabus-section",
            'list' => array(
                array(
                    'url' => $download_url,
                    'target' => '_blank',
                    'text' => 'Download ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') 
                        . 'Syllabus PDF for ' . $section['name']
                        . (isset($groupname) && $groupname != '' ? ' - ' . $groupname : ''),
                    ),
                ),
            );
    }
    
    //
    // Get the levels for this section
    //
    $level_strsql = '';
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
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.547', 'msg'=>'Unable to load tags', 'err'=>$rc['err']));
        }
        $levels = array(
            array(
                'text' => 'All Classes',
//                'class' => (!isset($_GET['level']) ? 'selected' : ''),
                'selected' => (!isset($_GET['level']) ? 'yes' : ''),
                'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $section['permalink'],
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
                    'url' => $request['ssl_domain_base_url'] . $request['page']['path'] . '/' . $section['permalink'] . '?level=' . $tag['permalink'],
                    );
            }
        }
    }

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
        . "categories.primary_image_id AS category_image_id, "
        . "categories.synopsis AS category_synopsis, "
        . "categories.description AS category_description, "
        . "classes.code, "
        . "classes.name, "
        . "classes.synopsis, "
        . "classes.permalink, "
        . "classes.sequence, "
        . "classes.flags, "
        . "earlybird_fee, "
        . "fee, "
        . "virtual_fee, "
        . "earlybird_plus_fee, "
        . "plus_fee "
        . "FROM ciniki_musicfestival_categories AS categories "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id ";
    if( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'live' ) {
        $strsql .= "AND classes.fee > 0 ";
    } elseif( isset($s['display-live-virtual']) && $s['display-live-virtual'] == 'virtual' ) {
        $strsql .= "AND classes.virtual_fee > 0 ";
    }
        $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . $level_strsql 
        . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
        . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($groupname) ) {
        $strsql .= "AND categories.groupname = '" . ciniki_core_dbQuote($ciniki, $groupname) . "' ";
    } 
    $strsql .= "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category_id', 
            'fields'=>array('name'=>'category_name', 'permalink'=>'category_permalink', 
                'image_id'=>'category_image_id', 
                'synopsis'=>'category_synopsis', 'description'=>'category_description',
            )),
        array('container'=>'classes', 'fname'=>'id',  
            'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'synopsis',
                'permalink', 'sequence', 'flags', 
                'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $categories = $rc['categories'];
        //
        // Get the filters
        //
        if( isset($levels) && count($levels) > 1 ) {
            $blocks[] = array(
                'id' => 'filter',
                'type' => 'dropdown',
                'class' => 'musicfestival-syllabus-section',
                'list' => $levels,
                );
        }

        foreach($categories as $category) {
            $intro = ($category['description'] != '' ? $category['description'] : ($category['synopsis'] != '' ? $category['synopsis'] : ''));
            if( $intro != '' ) {
                $blocks[] = array(
                    'type' => 'text', 
                    'id' => $category['permalink'],
                    'title' => $category['name'], 
                    'class' => 'musicfestival-syllabus-section',
                    'content' => ($category['description'] != '' ? $category['description'] : ($category['synopsis'] != '' ? $category['synopsis'] : ' ')),
                    );
            }
            if( isset($category['classes']) && count($category['classes']) > 0 ) {
                //
                // Process the classes to determine which fee to show
                //
                $live_label = $festival['earlybird'] == 'yes' ? 'Earlybird' : 'Fee';
                foreach($category['classes'] as $cid => $class) {
                    if( $festival['earlybird'] == 'yes' || $festival['live'] == 'yes' ) {
                        if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' && $class['earlybird_fee'] > 0 ) {
                            $category['classes'][$cid]['live_fee'] = '$' . number_format($class['earlybird_fee'], 2);
                        } elseif( isset($festival['live']) && $festival['live'] == 'yes' && $class['fee'] > 0 ) {
                            $category['classes'][$cid]['live_fee'] = '$' . number_format($class['fee'], 2);
                        } else {
                            $category['classes'][$cid]['live_fee'] = 'n/a';
                        }
                    } else {
                        $category['classes'][$cid]['live_fee'] = 'closed';
                    }
                    if( ($festival['flags']&0x04) == 0x04 ) {
                        $live_label = $festival['earlybird'] == 'yes' ? 'Earlybird Live' : 'Live';
                        if( $festival['virtual'] == 'yes' && $class['virtual_fee'] > 0 ) {
                            $category['classes'][$cid]['virtual_fee'] = '$' . number_format($class['virtual_fee'], 2);
                        } elseif( $festival['virtual'] == 'yes' ) {
                            $category['classes'][$cid]['virtual_fee'] = 'n/a';
                        } else {
                            $category['classes'][$cid]['virtual_fee'] = 'closed';
                        }
                    }
                    if( ($festival['flags']&0x10) == 0x10 && isset($festival['plus_live']) ) {
                        $live_label = 'Regular Fee';
                        if( $festival['plus_live'] == 'yes' ) {
                            if( isset($festival['earlybird_plus']) && $festival['earlybird_plus'] == 'yes' && $class['earlybird_plus_fee'] > 0 ) {
                                $category['classes'][$cid]['plus_live_fee'] = '$' . number_format($class['earlybird_plus_fee'], 2);
                            } elseif( $festival['plus_live'] == 'yes' && $class['plus_fee'] > 0 ) {
                                $category['classes'][$cid]['plus_live_fee'] = '$' . number_format($class['plus_fee'], 2);
                            } else {
                                $category['classes'][$cid]['plus_live_fee'] = 'n/a';
                            }
                        } else {
                            $category['classes'][$cid]['plus_live_fee'] = 'closed';
                        }
                    }
                    $category['classes'][$cid]['fullname'] = $class['code'] . ' - ' . $class['name'];
                    if( ($festival['flags']&0x01) == 0x01 
                        && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') 
                        ) {
                        $category['classes'][$cid]['register'] = "<a class='button' href='{$request['ssl_domain_base_url']}/account/musicfestivalregistrations?add=yes&cl=" . $class['uuid'] . "'>Register</a>";
                    }
                }
                //
                // Check if online registrations enabled, and online registrations enabled for this class
                //
                if( ($festival['flags']&0x06) == 0x06 ) {   // Virtual option & Virtual Pricing
                    $block = array(
                        'type' => 'table', 
                        'section' => 'classes', 
                        'headers' => ($festival['flags']&0x04) == 0x04 ? 'yes' : 'no',
                        'class' => 'fold-at-40 musicfestival-classes',
                        'columns' => array(
                            array('label'=>'Class', 'fold-label'=>'Class:', 'field'=>'fullname', 'info-field'=>'synopsis', 'class'=>''),
    //                            array('label'=>'Course', 'field'=>'name', 'class'=>''),
                            array('label'=>$live_label, 'fold-label'=>$live_label . ':', 'field'=>'live_fee', 'class'=>'aligncenter fold-alignleft'),
                            array('label'=>'Virtual', 'fold-label'=>'Virtual:', 'field'=>'virtual_fee', 'class'=>'aligncenter fold-alignleft'),
//                            array('label'=>'', 'field'=>'register', 'class'=>'alignright buttons'),
                            ),
                        'rows' => $category['classes'],
                        );
                } else {
                    $block = array(
                        'type' => 'table', 
                        'section' => 'classes', 
                        'headers' => 'no',
                        'class' => 'fold-at-40 musicfestival-classes musicfestival-syllabus-section',
                        'columns' => array(
                            array('label'=>'Class', 'fold-label'=>'Class:', 'field'=>'fullname', 'info-field'=>'synopsis', 'class'=>''),
                            array('label'=>$live_label, 'fold-label'=>$live_label . ':', 'field'=>'live_fee', 'class'=>'aligncenter fold-alignleft'),
                            ),
                        'rows' => $category['classes'],
                        );
                }
                if( isset($festival['plus_live']) ) {
                    $block['headers'] = 'yes';
                    $block['columns'][] = array('label'=>'Adjudication Plus Fee', 'fold-label'=>'Adjudication Plus Fee:', 'field'=>'plus_live_fee', 'class'=>'aligncenter fold-alignleft');
                    
                }
                $block['columns'][] = array('label'=>'', 'field'=>'register', 'class'=>'alignright buttons');
                if( $intro == '' ) {
                    $block['title'] = $category['name']; 
                }
                $blocks[] = $block;
            }
        }
    }

    //
    // Check if download button
    //
    if( isset($s['section-pdf']) && ($s['section-pdf'] == 'bottom' || $s['section-pdf'] == 'both') ) {
        $blocks[] = array(
            'type' => 'buttons',
            'class' => "buttons-bottom-{$section['permalink']} musicfestival-syllabus-section",
            'list' => array(
                array(
                    'url' => $download_url,
                    'target' => '_blank',
                    'text' => 'Download ' . (isset($lv_word) && $lv_word != '' ? "{$lv_word} " : '') . 'Syllabus PDF for ' . $section['name'],
                    ),
                ),
            );
    }



    return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
}
?>
