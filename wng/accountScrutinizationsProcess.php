<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accountScrutinizationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'videoProcess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestival/scrutinizations';
    $display = 'sections';

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_musicfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.395', 'msg'=>'', 'err'=>$rc['err']));
    }
    $festival = $rc['festival'];

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalMaps');
    $rc = ciniki_musicfestivals_festivalMaps($ciniki, $tnid, $festival);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Load the list of sections the person is scrutinizing
    //
    $strsql = "SELECT sections.id AS section_id, "
        . "sections.permalink AS section_permalink, "
        . "sections.name AS section_name, "
        . "categories.id AS category_id, "
        . "categories.permalink AS category_permalink, "
        . "categories.name AS category_name, "
        . "classes.id AS class_id, "
        . "classes.code AS class_code, "
        . "classes.name AS class_name, "
        . "classes.flags AS class_flags, "
        . "classes.titleflags, "
        . "classes.min_competitors, "
        . "classes.max_competitors, "
        . "classes.min_titles, "
        . "classes.max_titles, "
        . "registrations.id AS reg_id, "
        . "registrations.status, "
        . "registrations.status AS status_text, "
        . "registrations.display_name AS display_name "
        . "FROM ciniki_musicfestival_scrutineers AS scrutineers "
        . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
            . "("
                . "(scrutineers.section_id = 0 AND scrutineers.syllabus_id = sections.syllabus_id) "
                . "OR scrutineers.section_id = sections.id "
                . ") "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_registrations AS registrations ON ("
            . "classes.id = registrations.class_id "
            . "AND registrations.status >= 10 " // Registered
            . "AND registrations.status < 70 "  // Not cancelled
            . ") "
        . "WHERE scrutineers.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND scrutineers.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND scrutineers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.code, classes.name, registrations.status, registrations.date_added "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sections', 'fname'=>'section_permalink', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'categories', 'fname'=>'category_permalink', 
            'fields'=>array('id'=>'category_id', 'name'=>'category_name'),
            ),
        array('container'=>'classes', 'fname'=>'class_code', 
            'fields'=>array('id'=>'class_id', 'code'=>'class_code', 'name'=>'class_name', 
                'flags'=>'class_flags', 'titleflags',
                'min_competitors', 'max_competitors', 'min_titles', 'max_titles',
                ),
            ),
        array('container'=>'registrations', 'fname'=>'reg_id', 
            'fields'=>array('id'=>'reg_id', 'status', 'status_text', 'display_name'),
            'maps'=>array('status_text' => $maps['registration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1141', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();


    //
    // Map statuses to
    // 10 - registered
    // 31-39 - Problems
    // 50-59 - Approved
    // 

    //
    // Default to showing list of sections
    //
    foreach($sections as $sid => $section) {
        $sections[$sid]['num_registered'] = 0;
        $sections[$sid]['num_problems'] = 0;
        $sections[$sid]['num_approved'] = 0;
        if( isset($section['categories']) ) {
            foreach( $section['categories'] as $cid => $category) {
                $sections[$sid]['categories'][$cid]['num_registered'] = 0;
                $sections[$sid]['categories'][$cid]['num_problems'] = 0;
                $sections[$sid]['categories'][$cid]['num_approved'] = 0;
                if( isset($category['classes']) ) {
                    foreach( $category['classes'] as $clid => $class) {
                        $sections[$sid]['categories'][$cid]['classes'][$clid]['num_registered'] = 0;
                        $sections[$sid]['categories'][$cid]['classes'][$clid]['num_problems'] = 0;
                        $sections[$sid]['categories'][$cid]['classes'][$clid]['num_approved'] = 0;
                        if( isset($class['registrations']) ) {
                            foreach( $class['registrations'] as $rid => $reg) {
                                if( $reg['status'] == 10 ) {
                                    $sections[$sid]['num_registered'] += 1;
                                    $sections[$sid]['categories'][$cid]['num_registered'] += 1;
                                    $sections[$sid]['categories'][$cid]['classes'][$clid]['num_registered'] += 1;
                                }
                                elseif( $reg['status'] >= 10 && $reg['status'] < 50 ) {
                                    $sections[$sid]['num_problems'] += 1;
                                    $sections[$sid]['categories'][$cid]['num_problems'] += 1;
                                    $sections[$sid]['categories'][$cid]['classes'][$clid]['num_problems'] += 1;
                                }
                                elseif( $reg['status'] >= 50 && $reg['status'] < 60 ) {
                                    $sections[$sid]['num_approved'] += 1;
                                    $sections[$sid]['categories'][$cid]['num_approved'] += 1;
                                    $sections[$sid]['categories'][$cid]['classes'][$clid]['num_approved'] += 1;
                                }
                                $sections[$sid]['categories'][$cid]['classes'][$clid]['registrations'][$rid]['buttons'] = "<a class='button' href='{$base_url}/{$sid}/{$cid}/{$clid}/{$rid}'>Review</a>";
                            }
                        }
                        foreach(['num_registered', 'num_problems', 'num_approved'] AS $field) {
                            if( $sections[$sid]['categories'][$cid]['classes'][$clid][$field] == 0 ) {
                                $sections[$sid]['categories'][$cid]['classes'][$clid][$field] = '';
                            }
                        }
                        $sections[$sid]['categories'][$cid]['classes'][$clid]['buttons'] = "<a class='button' href='{$base_url}/{$sid}/{$cid}/{$clid}'>Open</a>";
                    }
                }
                foreach(['num_registered', 'num_problems', 'num_approved'] AS $field) {
                    if( $sections[$sid]['categories'][$cid][$field] == 0 ) {
                        $sections[$sid]['categories'][$cid][$field] = '';
                    }
                }
                $sections[$sid]['categories'][$cid]['buttons'] = "<a class='button' href='{$base_url}/{$sid}/{$cid}'>Open</a>";
            }
        }
        foreach(['num_registered', 'num_problems', 'num_approved'] AS $field) {
            if( $sections[$sid][$field] == 0 ) {
                $sections[$sid][$field] = '';
            }
        }
        $sections[$sid]['buttons'] = "<a class='button' href='{$base_url}/{$sid}'>Open</a>";
    }
    
    if( isset($request['uri_split'][($request['cur_uri_pos']+3)]) ) {
        $sid = $request['uri_split'][($request['cur_uri_pos']+3)];
        if( !isset($sections[$request['uri_split'][($request['cur_uri_pos']+3)]]) ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Unable to find section',
                ];
        } else {
            $section = $sections[$sid];
            $display = 'section';
            if( isset($request['uri_split'][($request['cur_uri_pos']+4)]) ) {
                $cid = $request['uri_split'][($request['cur_uri_pos']+4)];
                if( !isset($sections[$sid]['categories'][$cid]) ) {
                    $blocks[] = [
                        'type' => 'msg',
                        'level' => 'error',
                        'content' => 'Unable to find category',
                        ];
                } else {
                    $category = $sections[$sid]['categories'][$cid];
                    $display = 'category';
                    if( isset($request['uri_split'][($request['cur_uri_pos']+5)]) ) {
                        $clid = $request['uri_split'][($request['cur_uri_pos']+5)];
                        if( !isset($sections[$sid]['categories'][$cid]['classes'][$clid]) ) {
                            $blocks[] = [
                                'type' => 'msg',
                                'level' => 'error',
                                'content' => 'Unable to find class',
                                ];
                        } else {
                            $class = $sections[$sid]['categories'][$cid]['classes'][$clid];
                            $display = 'class';
                            if( isset($request['uri_split'][($request['cur_uri_pos']+6)]) ) {
                                $reg_id = $request['uri_split'][($request['cur_uri_pos']+6)];
                                if( !isset($sections[$sid]['categories'][$cid]['classes'][$clid]['registrations'][$reg_id]) ) {
                                    $blocks[] = [
                                        'type' => 'msg',
                                        'level' => 'error',
                                        'content' => 'Unable to find registration',
                                        ];
                                } else {
                                    $registration = $sections[$sid]['categories'][$cid]['classes'][$clid]['registrations'][$reg_id];
                                    $display = 'registration';
                                }
                            }
                        }
                    }
                }
            }
        }
    } 

    if( $display == 'registration' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'accountScrutinizeRegistrationProcess');
        $rc = ciniki_musicfestivals_wng_accountScrutinizeRegistrationProcess($ciniki, $tnid, $request, [
            'festival' => $festival,
            'base_url' => "{$base_url}/{$sid}/{$cid}/{$clid}",
            'maps' => $maps,
            'section' => $section,
            'category' => $category,
            'class' => $class,
            'registration_id' => $registration['id'],
            ]);
        if( $rc['stat'] != 'ok' ) {
            $blocks[] = [
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Unable to find registration',
                ];
        } elseif( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $block) {
                $blocks[] = $block;
            }
        }
    }
    elseif( $display == 'class' ) {
        $blocks[] = [
            'type' => 'table',
            'title' => $section['name'] . ' - ' . $category['name'] . ' - ' . $class['code'] . ' - ' . $class['name'],
            'section' => 'scrutineers-section',
            'headers' => 'yes',
            'class' => 'fold-at-50 limit-width limit-width-80',
            'columns' => [
                ['label' => 'Registration', 'field' => 'display_name', 'fold-label'=>''],
                ['label' => 'Status', 'field' => 'status_text', 'fold-label'=>'Status:', 'class' => 'aligncenter'],
                ['label' => '', 'field' => 'buttons', 'fold-label'=>'', 'class' => 'alignright buttons'],
                ],
            'rows' => $class['registrations'],
            ];
            
    }
    elseif( $display == 'category' ) {
        $blocks[] = [
            'type' => 'table',
            'title' => $section['name'] . ' - ' . $category['name'],
            'section' => 'scrutineers-section',
            'headers' => 'yes',
            'class' => 'fold-at-50 limit-width limit-width-80',
            'columns' => [
                ['label' => 'Class', 'field' => 'name', 'fold-label'=>''],
                ['label' => 'Pending Review', 'field' => 'num_registered', 'fold-label'=>'Registered:', 'class' => 'aligncenter'],
                ['label' => 'Issues', 'field' => 'num_problems', 'fold-label'=>'Issues:', 'class' => 'aligncenter'],
                ['label' => 'Approved', 'field' => 'num_approved', 'fold-label'=>'Approved:', 'class' => 'aligncenter'],
                ['label' => '', 'field' => 'buttons', 'fold-label'=>'', 'class' => 'alignright buttons'],
                ],
            'rows' => $category['classes'],
            ];
            
    }
    elseif( $display == 'section' ) {
        $blocks[] = [
            'type' => 'table',
            'title' => $section['name'],
            'section' => 'scrutineers-section',
            'headers' => 'yes',
            'class' => 'fold-at-50 limit-width limit-width-80',
            'columns' => [
                ['label' => 'Category', 'field' => 'name', 'fold-label'=>''],
                ['label' => 'Pending Review', 'field' => 'num_registered', 'fold-label'=>'Registered:', 'class' => 'aligncenter'],
                ['label' => 'Issues', 'field' => 'num_problems', 'fold-label'=>'Issues:', 'class' => 'aligncenter'],
                ['label' => 'Approved', 'field' => 'num_approved', 'fold-label'=>'Approved:', 'class' => 'aligncenter'],
                ['label' => '', 'field' => 'buttons', 'fold-label'=>'', 'class' => 'alignright buttons'],
                ],
            'rows' => $section['categories'],
            ];
            
    }
    elseif( $display == 'sections' ) {
        $blocks[] = [
            'type' => 'table',
            'section' => 'scrutineers-sections',
            'headers' => 'yes',
            'class' => 'fold-at-50 limit-width limit-width-80',
            'columns' => [
                ['label' => 'Section', 'field' => 'name', 'fold-label'=>''],
                ['label' => 'Pending Review', 'field' => 'num_registered', 'fold-label'=>'Registered:', 'class' => 'aligncenter'],
                ['label' => 'Issues', 'field' => 'num_problems', 'fold-label'=>'Issues:', 'class' => 'aligncenter'],
                ['label' => 'Approved', 'field' => 'num_approved', 'fold-label'=>'Approved:', 'class' => 'aligncenter'],
                ['label' => '', 'field' => 'buttons', 'fold-label'=>'', 'class' => 'alignright buttons'],
                ],
            'rows' => $sections,
            ];
            
    }
    
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
