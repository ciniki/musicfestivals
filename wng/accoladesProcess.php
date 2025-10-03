<?php
//
// Description
// -----------
// This function will generate the blocks to display accolades
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_accoladesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.363', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.364', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();
    $base_url = $request['page']['path'];

    //
    // 
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x40) 
        && isset($s['syllabus-page']) && $s['syllabus-page'] > 0 
        ) {
        $strsql = "SELECT settings "
            . "FROM ciniki_wng_sections "
            . "WHERE page_id = '" . ciniki_core_dbQuote($ciniki, $s['syllabus-page']) . "' "
            . "AND ref like '%.%.syllabus' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY sequence "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.561', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
        }
        if( isset($rc['section']) ) {
            $settings = json_decode($rc['section']['settings'], true);
            if( isset($settings['syllabus-id']) ) {
                $syllabus_id = $settings['syllabus-id'];
            }
        }
    }

    //
    // Get the list of sub categories
    //
    $strsql = "SELECT id, "
        . "name, "
        . "permalink "
        . "FROM ciniki_musicfestival_accolade_subcategories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0x01 "    // Visible
        . "";
    if( isset($s['category-id']) && $s['category-id'] != '' && $s['category-id'] != '0' ) {
        $strsql .= "AND category_id = '" . ciniki_core_dbQuote($ciniki, $s['category-id']) . "' ";
    }
    $strsql .= "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'subcategories', 'fname'=>'permalink', 
            'fields'=>array('id', 'name', 'permalink'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1122', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
    }
    $subcategories = isset($rc['subcategories']) ? $rc['subcategories'] : array();
   
    $buttons = array();
    foreach($subcategories as $subcategory) {
        $buttons[] = array(
            'text' => $subcategory['name'],
            'url' => $base_url . '/' . $subcategory['permalink'],
            );
    }
    if( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title',
            'title' => $s['title'],
            );
    }
    $blocks[] = array(
        'type' => 'buttons',
        'class' => 'musicfestival-accolade-categories aligncenter',
        'list' => $buttons,
        );

    if( isset($request['uri_split'][($request['cur_uri_pos']+2)]) 
        && isset($subcategories[$request['uri_split'][($request['cur_uri_pos']+1)]])
        ) {
        $subcategory = $subcategories[$request['uri_split'][($request['cur_uri_pos']+1)]];
        $accolade_permalink = urldecode($request['uri_split'][($request['cur_uri_pos']+2)]);
        
        //
        // Get the accolades for a category
        //
        $strsql = "SELECT id, "
            . "name, "
            . "permalink "
            . "FROM ciniki_musicfestival_accolades "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND subcategory_id = '" . ciniki_core_dbQuote($ciniki, $subcategory['id']) . "' ";
        $strsql .= "ORDER BY name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accolades', 'fname'=>'permalink', 
                'fields'=>array('id', 'title'=>'name', 'permalink')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.366', 'msg'=>'Unable to load accolades', 'err'=>$rc['err']));
        }
        $accolades = isset($rc['accolades']) ? $rc['accolades'] : array();

        //
        // Get the accolade details
        //
        $strsql = "SELECT accolades.id, "
            . "accolades.name, "
            . "accolades.primary_image_id, "
            . "accolades.donated_by, "
            . "accolades.first_presented, "
            . "accolades.criteria, "
            . "accolades.amount, "
            . "accolades.description "
            . "FROM ciniki_musicfestival_accolades AS accolades "
            . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND accolades.subcategory_id = '" . ciniki_core_dbQuote($ciniki, $subcategory['id']) . "' "
            . "AND accolades.permalink = '" . ciniki_core_dbQuote($ciniki, $accolade_permalink) . "' ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'accolade');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.367', 'msg'=>'Unable to load accolade', 'err'=>$rc['err']));
        }
        if( !isset($rc['accolade']) ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-90',
                'level' => 'error',
                'content' => 'Accolade not found',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        $accolade = $rc['accolade'];
        $accolade['full_description'] = '';
        if( $accolade['donated_by'] != '' ) {
            $accolade['full_description'] .= '<b>Donated By:</b> ' . $accolade['donated_by'] . '<br/>';
        }
        if( $accolade['first_presented'] != '' ) {
            $accolade['full_description'] .= '<b>First Presented:</b> ' . $accolade['first_presented'] . '<br/>';
        }
        if( $accolade['criteria'] != '' ) {
            $accolade['full_description'] .= '<b>Criteria:</b> ' . $accolade['criteria'] . '<br/>';
        }
        if( $accolade['amount'] != '' ) {
            $accolade['full_description'] .= '<b>Amount:</b> ' . $accolade['amount'] . '<br/>';
        }
        if( $accolade['description'] != '' ) {
            if( $accolade['full_description'] != '' ) {   
                $accolade['full_description'] .= '<br/>';
            }
            $accolade['full_description'] .= $accolade['description'];
        }

        //
        // Get the list of winners
        //
        $strsql = "SELECT winners.id, "
            . "winners.accolade_id, "
            . "winners.name, "
            . "winners.year "
            . "FROM ciniki_musicfestival_accolade_winners AS winners "
            . "WHERE winners.accolade_id = '" . ciniki_core_dbQuote($ciniki, $accolade['id']) . "' "
            . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY winners.year DESC, winners.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'winners', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'year')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $winners = isset($rc['winners']) ? $rc['winners'] : array();

        //
        // Get the list of classes
        //
//        if( isset($syllabus_id) && $syllabus_id > 0 ) {
        if( isset($s['display-classes']) && $s['display-classes'] == 'yes' ) {
            $strsql = "SELECT classes.id, "
                . "classes.code AS class_code, "
                . "classes.name AS class_name, "
                . "categories.name AS category_name, "
                . "categories.permalink AS category_permalink, "
                . "categories.groupname AS category_groupname, "
                . "sections.name AS section_name, "
                . "sections.permalink AS section_permalink, "
                . "sections.festival_id, "
                . "wngsections.settings AS wngsectionsettings, "
                . "wngpages.id AS page_id "
                . "FROM ciniki_musicfestival_accolade_classes AS tc "
                . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                    . "tc.class_id = classes.id "
                    . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_categories AS categories ON ("
                    . "classes.category_id = categories.id "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                    . "categories.section_id = sections.id "
                    . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                // sections and pages must be visible for class list to show
                . "INNER JOIN ciniki_wng_sections AS wngsections ON ("
                    . "wngsections.ref = 'ciniki.musicfestivals.syllabus' "
                    . "AND wngsections.settings like CONCAT('%\"syllabus-id\":\"', sections.syllabus_id, '\"%') "
                    . "AND (wngsections.flags&0x10) = 0 " // Visible
                    . "AND wngsections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_wng_pages AS wngpages ON ("
                    . "wngsections.page_id = wngpages.id "
                    . "AND (wngpages.flags&0x01) = 1 " // Visible
                    . "AND wngpages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE tc.accolade_id = '" . ciniki_core_dbQuote($ciniki, $accolade['id']) . "' "
                . "AND tc.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY sections.sequence, section_name, categories.sequence, category_name, "
                    . "classes.sequence, class_name, class_code "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
                array('container'=>'classes', 'fname'=>'id', 
                    'fields'=>array('id', 'class_code', 'class_name', 
                        'category_name', 'category_permalink', 'category_groupname', 
                        'section_name', 'section_permalink',
                        'wngsectionsettings', 'page_id', 'festival_id',
                        )),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $classes = isset($rc['classes']) ? $rc['classes'] : array();
            ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'classNameFormat');
            foreach($classes as $cid => $class) {
                $rc = ciniki_musicfestivals_classNameFormat($ciniki, $tnid, [
                    'format' => 'code-section-category-class', //isset($festival['comments-class-format']) ? $festival['comments-class-format'] : '',
                    'section' => $class['section_name'],
                    'category' => $class['category_name'],
                    'code' => $class['class_code'],
                    'name' => $class['class_name'],
                    ]);
                $classes[$cid]['class_name'] = $rc['name'];
                $classes[$cid]['buttons'] = '';
                if( isset($class['wngsectionsettings']) && $class['wngsectionsettings'] != '' 
                    && isset($request['site']['pages'][$class['page_id']])
                    ) {
                    $settings = json_decode($class['wngsectionsettings'], true);
                    if( isset($settings['layout']) && $settings['layout'] != '' ) {
                        if( $settings['layout'] == 'groups' ) {
                            $groupname = ciniki_core_makePermalink($ciniki, $class['category_groupname']);
                            $classes[$cid]['buttons'] .= "<a class='button' href='{$request['ssl_domain_base_url']}"
                                . "{$request['site']['pages'][$class['page_id']]['path']}"
                                . "/{$class['section_permalink']}/{$groupname}#{$class['category_permalink']}'>"
                                . "View"
                                . "</a>";
                        } else {
                            $classes[$cid]['buttons'] .= "<a class='button' href='{$request['ssl_domain_base_url']}"
                                . "{$request['site']['pages'][$class['page_id']]['path']}"
                                . "/{$class['section_permalink']}#{$class['category_permalink']}'>"
                                . "View"
                                . "</a>";
                        }
                    }
                }
            }
        }

        $blocks[] = array(
            'type' => 'title',
            'level' => 2,
            'class' => 'limit-width limit-width-90',
            'title' => $subcategory['name'] . ' - ' . $accolade['name'],
            );
/*        $blocks[] = array(
            'type' => 'contentphoto',
            'class' => 'content-aligntop limit-width limit-width-90',
            'image-id' => $accolade['primary_image_id'],
            'content' => $accolade['full_description'],
            ); */
        $blocks[] = array(
            'type' => 'asideimage',
            'image-id' => $accolade['primary_image_id'],
            );
        $blocks[] = array(
            'type' => 'text',
            'class' => 'content-aligntop limit-width limit-width-90',
            'content' => $accolade['full_description'],
            );
        if( isset($winners) && count($winners) > 0 
            && (!isset($s['display-winners']) || $s['display-winners'] == 'yes')
            ) {
            $blocks[] = array(
                'type' => 'table',
                'subtitle' => 'Winners',
                'class' => 'fit-width musicfestival-accolade-winners limit-width limit-width-90',
                'headers' => 'no',
                'columns' => array(
                    array('label' => '', 'field'=>'year'),
                    array('label' => '', 'field'=>'name'),
                    ),
                'rows' => $winners,
                );
        }
        if( isset($classes) && count($classes) > 0 ) {
            $blocks[] = array(
                'type' => 'table', 
                'subtitle' => 'Eligible Classes', 
                'class' => 'fit-width musicfestival-accolade-classes limit-width limit-width-90',
                'headers' => 'no',
                'columns' => array(
//                    array('label' => 'Section', 'field'=>'section_name'),
//                    array('label' => 'Category', 'field'=>'category_name'),
//                    array('label' => 'Code', 'field'=>'class_code'),
                    array('label' => 'Class', 'field'=>'class_name'),
                    array('label' => '', 'field'=>'buttons'),
                    ),
                'rows' => $classes,
                );
        }
        
        //
        // Add prev/next buttons
        //
        if( count($accolades) > 1 ) {
            $first_accolade = null;
            $last_accolade = null;
            foreach($accolades as $accolade) {
                if( $first_accolade == null ) {
                    $first_accolade = $accolade;
                }
                if( $last_accolade != null && $accolade['permalink'] == $accolade_permalink ) {
                    $prev = $last_accolade;
                }
                if( $last_accolade != null && $last_accolade['permalink'] == $accolade_permalink ) {
                    $next = $accolade;
                }
                $last_accolade = $accolade;
            }
            if( !isset($next) ) {
                $next = $first_accolade;
            }
            if( !isset($prev) ) {
                $prev = $last_accolade;
            }
            if( isset($next) && isset($prev) ) {
                $blocks[] = array(
                    'type' => 'buttons',
                    'class' => 'aligncenter',
                    'list' => array(
                        array('text' => 'Previous', 'url' => $base_url . '/' . $subcategory['permalink'] . '/' . $prev['permalink']),
                        array('text' => 'Next', 'url' => $base_url . '/' . $subcategory['permalink'] . '/' . $next['permalink']),
                        ),
                    );
            }
        }
        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
    }
    elseif( isset($request['uri_split'][($request['cur_uri_pos']+1)]) 
        && isset($subcategories[$request['uri_split'][($request['cur_uri_pos']+1)]])
        ) {
        $subcategory = $subcategories[$request['uri_split'][($request['cur_uri_pos']+1)]];

        //
        // Get the accolades for a category
        //
        $strsql = "SELECT accolades.id, "
            . "accolades.name, "
            . "accolades.permalink, "
            . "accolades.primary_image_id, "
            . "accolades.donated_by, "
            . "accolades.first_presented, "
            . "accolades.criteria, "
            . "accolades.amount, "
            . "accolades.description, "
            . "winners.year AS winner_year, "
            . "winners.name AS winner_name "
            . "FROM ciniki_musicfestival_accolades AS accolades "
            . "LEFT JOIN ciniki_musicfestival_accolade_winners AS winners ON ("
                . "accolades.id = winners.accolade_id "
                . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND accolades.subcategory_id = '" . ciniki_core_dbQuote($ciniki, $subcategory['id']) . "' ";
        $strsql .= "ORDER BY accolades.name, winners.year DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accolades', 'fname'=>'permalink', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'image-id'=>'primary_image_id',
                    'donated_by', 'first_presented', 'criteria', 'amount', 'description'),
                ),
            array('container'=>'winners', 'fname'=>'winner_year', 
                'fields'=>array('year'=>'winner_year', 'name'=>'winner_name'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.343', 'msg'=>'Unable to load accolades', 'err'=>$rc['err']));
        }
        $accolades = isset($rc['accolades']) ? $rc['accolades'] : array();

        uasort($accolades, function($a, $b) {
            return strnatcasecmp($a['title'], $b['title']);
            });

        foreach($accolades as $tid =>$accolade) {
            $accolades[$tid]['url'] = $base_url . '/' . $subcategory['permalink'] . '/' . urlencode($accolade['permalink']);
            $accolades[$tid]['title-position'] = 'overlay-bottomhalf';

            if( isset($s['display-format']) && $s['display-format'] == 'buttons-list' ) {
                $accolades[$tid]['full_description'] = '';
                if( $accolade['donated_by'] != '' ) {
                    $accolades[$tid]['full_description'] .= '<b>Donated By:</b> ' . $accolade['donated_by'] . '<br/>';
                }
                if( $accolade['first_presented'] != '' ) {
                    $accolades[$tid]['full_description'] .= '<b>First Presented:</b> ' . $accolade['first_presented'] . '<br/>';
                }
                if( $accolade['criteria'] != '' ) {
                    $accolades[$tid]['full_description'] .= '<b>Criteria:</b> ' . $accolade['criteria'] . '<br/>';
                }
                if( $accolade['amount'] != '' ) {
                    $accolades[$tid]['full_description'] .= '<b>Amount:</b> ' . $accolade['amount'] . '<br/>';
                }
                if( $accolade['description'] != '' ) {
                    if( $accolades[$tid]['full_description'] != '' ) {   
                        $accolades[$tid]['full_description'] .= '<br/>';
                    }
                    $accolades[$tid]['full_description'] .= $accolade['description'];
                }
            }
        }

        $blocks[] = array(
            'type' => 'title',
            'level' => 2,
            'class' => 'limit-width limit-width-90',
            'title' => $subcategory['name'],
            );
        if( isset($s['display-format']) && $s['display-format'] == 'buttons-list' ) {
            foreach($accolades as $accolade) {
                $blocks[] = array(
                    'type' => 'title',
                    'level' => 3,
                    'class' => 'accolade-title',
                    'title' => $accolade['title'],
                    );
                $blocks[] = array(
                    'type' => 'asideimage',
                    'image-id' => $accolade['image-id'],
                    );
                $blocks[] = array(
                    'type' => 'text',
                    'content' => $accolade['full_description'],
                    );
/*
                $blocks[] = array(
                    'type' => 'contentphoto',
                    'image-position' => 'top-right-inline',
                    'image-size' => 'large',
                    'title' => $accolade['title'],
                    'image-id' => $accolade['image-id'],
                    'content' => $accolade['full_description'],
                    ); */
                if( isset($accolade['winners']) && count($accolade['winners']) > 0 
                    && (!isset($s['display-winners']) || $s['display-winners'] == 'yes')
                    ) {
                    $blocks[] = array(
                        'type' => 'table',
                        'subtitle' => 'Winners',
                        'class' => 'fit-width musicfestival-accolade-winners',
                        'headers' => 'no',
                        'columns' => array(
                            array('label' => '', 'field'=>'year'),
                            array('label' => '', 'field'=>'name'),
                            ),
                        'rows' => $accolade['winners'],
                        );
                } 
            }
        } elseif( isset($s['display-format']) && $s['display-format'] == 'buttons-buttons-accolade' ) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'aligncenter',
                'items' => $accolades,
                );
            
        } else {
            $blocks[] = array(
                'type' => 'imagebuttons',
                'image-version' => 'original',
                'title-position' => 'overlay-bottomhalf',
                'image-size' => 1024,
                'items' => $accolades,
                );
        }

        return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
