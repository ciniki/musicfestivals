<?php
//
// Description
// -----------
// Search the classes for the festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_wng_apiClassSearch(&$ciniki, $tnid, $request) {
   
   
    if( !isset($request['args']['search_string']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.827', 'msg'=>'No search string specified'));
    }
    if( !isset($request['args']['festival-id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.829', 'msg'=>'No festival specified'));
    }

    //
    // Get the music festival details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
    $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $request['args']['festival-id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.831', 'msg'=>'Unable to find requested festival'));
    }
    $festival = $rc['festival'];

    //
    // Create the keywords string
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classKeywordsMake');
    $rc = ciniki_musicfestivals_classKeywordsMake($ciniki, $tnid, [
        'keywords' => $request['args']['search_string'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        error_log('Unable to create keywords: ' . $request['args']['search_string']);
        return array('stat'=>'ok');
    }
    $keywords = str_replace(' ', '% ', trim($rc['keywords']));

    $limit = 50;

    //
    // search the classes
    //
    if( $keywords != '' ) {
        $strsql = "SELECT classes.id, "
            . "categories.id AS category_id, "
            . "categories.name AS category_name, "
            . "categories.permalink AS category_permalink, "
            . "categories.groupname, "
            . "categories.primary_image_id AS category_image_id, "
            . "categories.synopsis AS category_synopsis, "
            . "categories.description AS category_description, "
            . "sections.id AS section_id, "
            . "sections.name AS section_name, "
            . "sections.permalink AS section_permalink, "
            . "classes.code, "
            . "classes.name, "
            . "classes.synopsis, "
            . "classes.permalink, "
            . "classes.sequence, "
            . "classes.flags, "
            . "classes.feeflags, "
            . "classes.keywords, "
            . "earlybird_fee, "
            . "fee, "
            . "virtual_fee, "
            . "earlybird_plus_fee, "
            . "plus_fee "
            . "FROM ciniki_musicfestival_classes AS classes "
            . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id ";
        if( isset($request['args']['syllabus_id']) ) {
            $strsql .= "AND sections.syllabus_id = '" . ciniki_core_dbQuote($ciniki, $request['args']['syllabus_id']) . "' ";
        }
        $strsql .= "AND (sections.flags&0x01) = 0 "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $request['args']['festival-id']) . "' "
            . "AND classes.keywords LIKE '% " . ciniki_core_dbQuote($ciniki, $keywords) . "%' ";
        if( isset($request['args']['lv']) && $request['args']['lv'] == 'live' ) {
            $strsql .= "AND (classes.feeflags&0x03) > 0 ";
        } elseif( isset($request['args']['lv']) && $request['args']['lv'] == 'virtual' ) {
            $strsql .= "AND (classes.feeflags&0x08) = 0x08 ";
        }
        $strsql .= "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
            . "LIMIT " . ($limit + 1)
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id',  
                'fields'=>array('id', 'section_name', 'section_permalink', 
                    'groupname', 'category_name', 'category_permalink',
                    'code', 'name', 'synopsis', 'keywords',
                    'permalink', 'sequence', 'flags', 'feeflags', 
                    'earlybird_fee', 'fee', 'virtual_fee', 'earlybird_plus_fee', 'plus_fee',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.828', 'msg'=>'Unable to search classes', 'err'=>$rc['err']));
        }
        $classes = isset($rc['classes']) ? $rc['classes'] : [];
    } else {
        $classes = [];
    }

    if( count($classes) > 0 ) {
        //
        // Process the classes to determine which fee to show
        //
        $live_column = 'no';
        $virtual_column = 'no';
        $plus_live_column = 'no';
        $live_label = $festival['earlybird'] == 'yes' ? 'Earlybird' : 'Fee';
        $count = 0;
        foreach($classes as $cid => $class) {
            $count++;
            if( $count > $limit ) {
                unset($classes[$cid]);
                continue;
            }
/*            if( $festival['earlybird'] == 'yes' || $festival['live'] == 'yes' ) {
                if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' && $class['earlybird_fee'] > 0 ) {
                    $classes[$cid]['live_fee'] = '$' . number_format($class['earlybird_fee'], 2);
                } elseif( isset($festival['live']) && $festival['live'] == 'yes' && $class['fee'] > 0 ) {
                    $classes[$cid]['live_fee'] = '$' . number_format($class['fee'], 2);
                } else {
                    $classes[$cid]['live_fee'] = 'n/a';
                }
            } else {
                $classes[$cid]['live_fee'] = 'closed';
            }
            if( ($festival['flags']&0x04) == 0x04 ) {
                $live_label = $festival['earlybird'] == 'yes' ? 'Earlybird Live' : 'Live';
                if( $festival['virtual'] == 'yes' && $class['virtual_fee'] > 0 ) {
                    $classes[$cid]['virtual_fee'] = '$' . number_format($class['virtual_fee'], 2);
                } elseif( $festival['virtual'] == 'yes' ) {
                    $classes[$cid]['virtual_fee'] = 'n/a';
                } else {
                    $classes[$cid]['virtual_fee'] = 'closed';
                }
            }
            if( ($festival['flags']&0x10) == 0x10 && isset($festival['plus_live']) ) {
                $live_label = 'Regular Fee';
                if( $festival['plus_live'] == 'yes' ) {
                    if( isset($festival['earlybird_plus']) && $festival['earlybird_plus'] == 'yes' && $class['earlybird_plus_fee'] > 0 ) {
                        $classes[$cid]['plus_live_fee'] = '$' . number_format($class['earlybird_plus_fee'], 2);
                    } elseif( $festival['plus_live'] == 'yes' && $class['plus_fee'] > 0 ) {
                        $classes[$cid]['plus_live_fee'] = '$' . number_format($class['plus_fee'], 2);
                    } else {
                        $classes[$cid]['plus_live_fee'] = 'n/a';
                    }
                } else {
                    $classes[$cid]['plus_live_fee'] = 'closed';
                }
            } */
            // Duplicated in syllabusSectionProcess
            if( ($class['feeflags']&0x03) > 0 ) {
                $live_column = 'yes';
            }
            if( ($class['feeflags']&0x08) == 0x08 ) {
                $virtual_column = 'yes';
            }
            if( ($class['feeflags']&0x30) > 0 ) {
                $plus_live_column = 'yes';
            }
            if( ($class['feeflags']&0x02) == 0x02 ) {
                if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' && ($class['feeflags']&0x01) == 0x01 ) {
                    $classes[$cid]['live_fee'] = '$' . number_format($class['earlybird_fee'], 2);
                } else {
                    $classes[$cid]['live_fee'] = '$' . number_format($class['fee'], 2);
                }
            } else {
                $classes[$cid]['live_fee'] = 'n/a';
            }
            if( ($festival['flags']&0x04) == 0x04 ) {
                $live_label = $festival['earlybird'] == 'yes' ? 'Earlybird Live' : 'Live';
                if( ($class['feeflags']&0x08) == 0x08 ) {
                    $classes[$cid]['virtual_fee'] = '$' . number_format($class['virtual_fee'], 2);
                } else {
                    $classes[$cid]['virtual_fee'] = 'n/a';
                }
            }
            if( ($festival['flags']&0x10) == 0x10 && isset($festival['plus_live']) ) {
                $live_label = 'Regular Fee';
                if( ($class['feeflags']&0x20) == 0x20 ) {
                    if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' 
                        && ($class['feeflags']&0x10) == 0x10 
                        ) {
                        $classes[$cid]['plus_live_fee'] = '$' . number_format($class['earlybird_plus_fee'], 2);
                    } else {
                        $classes[$cid]['plus_live_fee'] = '$' . number_format($class['plus_fee'], 2);
                    }
                } else {
                    $classes[$cid]['plus_live_fee'] = 'n/a';
                }
            }
            if( ($festival['flags']&0x0100) == 0x0100 ) {
                $classes[$cid]['fullname'] = $class['code'] . ' - ' . $class['section_name'] . ' - ' . $class['category_name'] . ' - ' . $class['name'];
            } else {
                $classes[$cid]['fullname'] = $class['code'] . ' - ' . $class['name'];
            }
//            $classes[$cid]['synopsis'] = $class['keywords'];
            //
            // Determine which link to use
            //
            if( substr($request['args']['baseurl'], -1) != '/' ) {
                $request['args']['baseurl'] .= '/';
            }
            if( isset($request['args']['layout']) 
                && ($request['args']['layout'] == 'groupbuttons' || $request['args']['layout'] == 'groups') ) {
                $group_permalink = ciniki_core_makePermalink($ciniki, $class['groupname']);
                $classes[$cid]['link'] = "<a class='button' href='{$request['args']['baseurl']}{$class['section_permalink']}/{$group_permalink}#{$class['category_permalink']}'>View</a>";
            } else {
                $classes[$cid]['link'] = "<a class='button' href='{$request['args']['baseurl']}{$class['section_permalink']}#{$class['category_permalink']}'>View</a>";
            }
        }
        //
        // Check if online registrations enabled, and online registrations enabled for this class
        //
        $block = array(
            'type' => 'table', 
            'section' => 'classes', 
            'title' => 'Search Results',
            'headers' => 'yes',
            'class' => 'fold-at-40 musicfestival-classes musicfestival-syllabus-section',
            'columns' => array(
                array('label'=>'Class', 'fold-label'=>'Class:', 'field'=>'fullname', 'info-field'=>'synopsis', 'class'=>''),
                ),
            'rows' => $classes,
            );
        if( $live_column == 'yes' ) {
            $block['columns'][] = array(
                'label' => $live_label, 
                'fold-label' => $live_label . ':', 
                'field' => 'live_fee', 
                'class' => 'aligncenter fold-alignleft fee live-fee',
                );
        }
        if( $virtual_column == 'yes' ) {
            $block['headers'] = 'yes';
            $block['columns'][] = array(
                'label' => 'Virtual',
                'fold-label' => 'Virtual:', 
                'field' => 'virtual_fee', 
                'class' => 'aligncenter fold-alignleft fee virtual-fee',
                );
        }
        if( isset($festival['plus_live']) && $plus_live_column == 'yes' ) {
            $block['headers'] = 'yes';
            $block['columns'][] = array('label'=>'Adjudication Plus Fee', 'fold-label'=>'Adjudication Plus Fee:', 'field'=>'plus_live_fee', 'class'=>'aligncenter fold-alignleft fee plus-fee');
            
        }
        if( isset($section['tableheader']) && $section['tableheader'] == 'multiprices' && count($block['columns']) < 3 ) {
            $block['headers'] = 'no';
        }
        $block['columns'][] = array('label'=>'', 'field'=>'link', 'class'=>'alignright buttons'); 
/*        if( ($festival['flags']&0x06) == 0x06 ) {   // Virtual option & Virtual Pricing
            $block = array(
                'type' => 'table', 
                'section' => 'classes', 
                'title' => 'Search Results',
                'headers' => 'yes',
                'class' => 'fold-at-40 musicfestival-classes',
                'columns' => array(
                    array('label'=>'Class', 'fold-label'=>'Class:', 'field'=>'fullname', 'info-field'=>'synopsis', 'class'=>''),
//                            array('label'=>'Course', 'field'=>'name', 'class'=>''),
                    array('label'=>$live_label, 'fold-label'=>$live_label . ':', 'field'=>'live_fee', 'class'=>'aligncenter fold-alignleft'),
                    array('label'=>'Virtual', 'fold-label'=>'Virtual:', 'field'=>'virtual_fee', 'class'=>'aligncenter fold-alignleft'),
//                            array('label'=>'', 'field'=>'register', 'class'=>'alignright buttons'),
                    ),
                'rows' => $classes,
                );
        } else {
            $block = array(
                'type' => 'table', 
                'section' => 'classes', 
                'title' => 'Search Results',
                'headers' => 'yes',
                'class' => 'fold-at-40 musicfestival-classes musicfestival-syllabus-section',
                'columns' => array(
                    array('label'=>'Class', 'fold-label'=>'Class:', 'field'=>'fullname', 'info-field'=>'synopsis', 'class'=>''),
                    array('label'=>$live_label, 'fold-label'=>$live_label . ':', 'field'=>'live_fee', 'class'=>'aligncenter fold-alignleft'),
                    ),
                'rows' => $classes,
                );
        }
        if( isset($festival['plus_live']) ) {
            $block['headers'] = 'yes';
            $block['columns'][] = array('label'=>'Adjudication Plus Fee', 'fold-label'=>'Adjudication Plus Fee:', 'field'=>'plus_live_fee', 'class'=>'aligncenter fold-alignleft');
            
        }
        $block['columns'][] = array('label'=>'', 'field'=>'link', 'class'=>'alignright buttons'); 
        */
        $blocks[] = $block;
        if( $count > $limit ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'warning',
                'content' => 'Too many results, please add more keywords to your search',
                ];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'blocksGenerate');
        return ciniki_wng_blocksGenerate($ciniki, $tnid, $request, $blocks);
    } elseif( $request['args']['search_string'] != '' && $keywords == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'warning', 
            'content' => 'Keep typing...',
            ]);
    } elseif( $request['args']['search_string'] == '' ) {
        return array('stat'=>'ok', 'content'=>'');
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'generators', 'msg');
        return ciniki_wng_generators_msg($ciniki, $tnid, $request, [
            'type' => 'message',
            'level' => 'error', 
            'content' => 'No classes found',
            ]);
    }


    return array('stat'=>'ok', 'content'=>'');
}
?>
