<?php
//
// Description
// -----------
// This method will return the list of Accolades for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Accolade for.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'year'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Year'),
//        'typename'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'sort'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sort'),
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of accolades
    //
    if( isset($args['class_id']) && $args['class_id'] > 0 ) {
        $strsql = "SELECT accolades.id, "
            . "accolades.subcategory_id, "
            . "accolades.name, "
            . "categories.name AS category_name, "
            . "subcategories.name AS subcategory_name, "
//            . "accolades.typename, "
//            . "accolades.category, "
            . "accolades.donated_by, "
            . "accolades.first_presented, "
            . "accolades.amount, "
            . "accolades.criteria, "
            . "classes.class_id, "
            . "'' AS winner_name "
            . "FROM ciniki_musicfestival_accolades AS accolades "
            . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
                . "accolades.subcategory_id = subcategories.id "
                . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_accolade_categories AS categories ON ("
                . "subcategories.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_accolade_classes AS classes ON ("
                . "accolades.id = classes.accolade_id "
                . "AND classes.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
            $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        if( isset($args['subcategory_id']) && $args['subcategory_id'] != '' && $args['subcategory_id'] > 0 ) {
            $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' ";
        }
//        if( isset($args['typename']) && $args['typename'] != '' && $args['typename'] != 'All' ) {
//            $strsql .= "AND accolades.typename = '" . ciniki_core_dbQuote($ciniki, $args['typename']) . "' ";
//        }
//        if( isset($args['category']) && $args['category'] != '' && $args['category'] != 'All' ) {
//            $strsql .= "AND accolades.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
//        }
        $strsql .= "HAVING ISNULL(classes.class_id) "
            . "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, accolades.category, accolades.name "
            . "";
    } else {
        $strsql = "SELECT accolades.id, "
            . "accolades.subcategory_id, "
            . "accolades.name, "
            . "categories.name AS category_name, "
            . "subcategories.name AS subcategory_name, "
//            . "accolades.typename, "
//            . "accolades.category, "
            . "accolades.donated_by, "
            . "accolades.first_presented, "
            . "accolades.amount, "
            . "accolades.criteria, ";
        if( isset($args['year']) && $args['year'] != 'None' && is_numeric($args['year']) ) {
            $strsql .= "IFNULL(winners.name, '') AS winner_name ";
        } else {
            $strsql .= "'' AS winner_name ";
        }
        $strsql .= "FROM ciniki_musicfestival_accolades AS accolades "
            . "INNER JOIN ciniki_musicfestival_accolade_subcategories AS subcategories ON ("
                . "accolades.subcategory_id = subcategories.id "
                . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "INNER JOIN ciniki_musicfestival_accolade_categories AS categories ON ("
                . "subcategories.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "";
        if( isset($args['year']) && $args['year'] != 'None' && is_numeric($args['year']) ) {
            $strsql .= "LEFT JOIN ciniki_musicfestival_accolade_winners AS winners ON ("
                . "accolades.id = winners.accolade_id "
                . "AND winners.year = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' "
                . "AND winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
        $strsql .= "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
        if( isset($args['category_id']) && $args['category_id'] != '' && $args['category_id'] > 0 ) {
            $strsql .= "AND categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' ";
        }
        if( isset($args['subcategory_id']) && $args['subcategory_id'] != '' && $args['subcategory_id'] > 0 ) {
            $strsql .= "AND subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' ";
        }
//        if( isset($args['typename']) && $args['typename'] != '' && $args['typename'] != 'All' ) {
//            $strsql .= "AND accolades.typename = '" . ciniki_core_dbQuote($ciniki, $args['typename']) . "' ";
//        }
//        if( isset($args['category']) && $args['category'] != '' && $args['category'] != 'All' ) {
//            $strsql .= "AND accolades.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
//        }
        $strsql .= "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name, name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'accolades', 'fname'=>'id', 
            'fields'=>array('id', 'subcategory_id', 'category_name', 'subcategory_name', 'name', 'donated_by', 'first_presented', 'amount', 'criteria', 'winner_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $accolades = isset($rc['accolades']) ? $rc['accolades'] : array();
    $accolade_ids = array();
    foreach($accolades as $iid => $accolade) {
        $accolade_ids[] = $accolade['id'];
//        $accolades[$iid]['sortkey'] = "{$accolade['typename']}-{$accolade['category']}-{$accolade['name']}";
    }
/*    if( isset($args['sort']) && $args['sort'] == 'name_asc' ) {
        uasort($accolades, function($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
            });
    } elseif( isset($args['sort']) && $args['sort'] == 'name_desc' ) {
        uasort($accolades, function($a, $b) {
            return strnatcasecmp($b['name'], $a['name']);
            });
    } else {
        uasort($accolades, function($a, $b) {
            return strnatcasecmp($a['sortkey'], $b['sortkey']);
            });
    } 
    $accolades = array_values($accolades);
    */

    //
    // Check if output is pdf
    //
    if( isset($args['output']) && $args['output'] == 'pdf' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'accoladeListPDF');
        $rc = ciniki_musicfestivals_templates_accoladeListPDF($ciniki, $args['tnid'], [
//            'festival_id' => $args['festival_id'],
            'accolades' => $accolades,
            'winners' => isset($args['year']) ? $args['year'] : '',
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.929', 'msg'=>'', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output('Accolade List.pdf', 'I');
            return array('stat'=>'exit');
        }
    }

    $rsp = array('stat'=>'ok', 'accolades'=>$accolades, 'nplist'=>$accolade_ids);

    //
    // Get the list of categories and subcategories
    //
    $strsql = "SELECT id, sequence, name "
        . "FROM ciniki_musicfestival_accolade_categories AS categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'sequence', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1167', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $rsp['accolade_categories'] = isset($rc['categories']) ? $rc['categories'] : array();
    array_unshift($rsp['accolade_categories'], ['id'=>0, 'sequence'=>0, 'name'=>'All']);

    $rsp['accolade_subcategories'] = [];
    if( isset($args['category_id']) && $args['category_id'] > 0 ) {
        $strsql = "SELECT id, sequence, name "
            . "FROM ciniki_musicfestival_accolade_subcategories AS subcategories "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'subcategories', 'fname'=>'id', 
                'fields'=>array('id', 'sequence', 'name'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1100', 'msg'=>'Unable to load subcategories', 'err'=>$rc['err']));
        }
        $rsp['accolade_subcategories'] = isset($rc['subcategories']) ? $rc['subcategories'] : array();
        array_unshift($rsp['accolade_subcategories'], ['id'=>0, 'sequence'=>0, 'name'=>'All']);
    }

/*
    //
    // Get the list of types
    //
    $strsql = "SELECT DISTINCT accolades.typename "
        . "FROM ciniki_musicfestival_accolades AS accolades "
        . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY accolades.typename "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'types', 'fname'=>'typename', 'fields'=>array('name'=>'typename')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.902', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $types = isset($rc['types']) ? $rc['types'] : array();
    array_unshift($types, ['name'=>'All']);

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT accolades.category "
        . "FROM ciniki_musicfestival_accolades AS accolades "
        . "WHERE accolades.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['typename']) && $args['typename'] != '' && $args['typename'] != 'All' ) {
        $strsql .= "AND accolades.typename = '" . ciniki_core_dbQuote($ciniki, $args['typename']) . "' ";
    }
    $strsql .= "ORDER BY accolades.category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.964', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    array_unshift($categories, ['name'=>'All']);
*/
    //
    // Get the list of years of winners
    //
    $strsql = "SELECT DISTINCT winners.year "
        . "FROM ciniki_musicfestival_accolade_winners AS winners "
        . "WHERE winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY winners.year DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'years', 'fname'=>'year', 'fields'=>array('name'=>'year')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.903', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $rsp['accolade_years'] = isset($rc['years']) ? $rc['years'] : array();
    array_unshift($rsp['accolade_years'], ['name'=>'None']);

    return $rsp;
//    $rsp = array('stat'=>'ok', 'accolades'=>$accolades, 'accolade_types'=>$types, 'accolade_categories'=>$categories, 'accolade_years'=>$years, 'nplist'=>$accolade_ids);
}
?>
