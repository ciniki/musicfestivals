<?php
//
// Description
// ===========
// This method will return the provincial recommendations info for a festival
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the festival is attached to.
// festival_id:          The ID of the festival to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_provincials($ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'sections'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sections'),
        'section_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'classes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Classes'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
        'statuses'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Statuses'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'recommendations'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Submissions'),
        'recommendation_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Submission'),
        'entries'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Entries'),
        'adjudicators'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Adjudicators'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.volunteers');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    $date_format = 'D, M j, Y';

    //
    // Load the load festival and provincials festival info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'provincialsFestivalMemberLoad');
    $rc = ciniki_musicfestivals_provincialsFestivalMemberLoad($ciniki, $args['tnid'], [
        'festival_id' => $args['festival_id'],
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $festival = $rc['festival'];
    $provincials_festival_id = $festival['provincial-festival-id'];
    $member = $rc['member'];
    $provincials_tnid = $member['tnid'];

    $rsp = array('stat'=>'ok', 'festival'=>$festival);
    
    //
    // Set up query restrictions
    //
    $status_sql = '';
    $section_sql = '';
    $class_sql = '';
    $recommendation_sql = '';
    if( isset($args['status']) && $args['status'] != '' && $args['status'] > 0 ) {
        $status_sql = "AND entries.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['section_id']) && $args['section_id'] > 0 ) {
        $section_sql = "AND recommendations.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' ";
        if( isset($args['class_id']) && $args['class_id'] > 0 ) {
            $class_sql = "AND entries.class_id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' ";
        }
    }
    if( isset($args['recommendation_id']) && $args['recommendation_id'] > 0 ) {
        $recommendation_sql .= "AND recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' ";
    }

    //
    // Load the sections
    //
    if( isset($args['sections']) && $args['sections'] == 'yes' ) {
        if( isset($args['recommendations']) && $args['recommendations'] == 'yes' ) {
            $strsql = "SELECT sections.id, "
                . "sections.name, "
                . "COUNT(recommendations.id) AS num_items "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                    . "sections.id = recommendations.section_id "
                    . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                    . ") "
                . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . "GROUP BY sections.id "
                . "ORDER BY sections.sequence, sections.name, recommendations.id "
                . "";
        } else {
            $strsql = "SELECT sections.id, "
                . "sections.name, "
                . "COUNT(entries.id) AS num_items "
                . "FROM ciniki_musicfestival_sections AS sections "
                . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                    . "sections.id = recommendations.section_id "
                    . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                    . ") "
                . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                    . "recommendations.id = entries.recommendation_id "
                    . $status_sql
                    . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                    . ") "
                . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . "GROUP BY sections.id "
                . "ORDER BY sections.sequence, sections.name, recommendations.id, entries.id "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'num_items')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1279', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
        }
        $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
        if( !isset($args['adjudicators']) ) { // Don't add totals when using to add recommendation
            $total = 0;
            foreach($rsp['sections'] as $sid => $section) {
                if( $section['num_items'] > 0 ) {
                    $rsp['sections'][$sid]['name'] .= ' (' . $section['num_items'] . ')';
                    $total += $section['num_items'];
                }
            }
            array_unshift($rsp['sections'], ['id' => 0, 'name' => 'All' . ($total > 0 ? " ({$total})" : '')]);
        }
    }

    //
    // Load the classes
    //
    if( isset($args['classes']) && $args['classes'] == 'yes' && isset($args['section_id']) && $args['section_id'] > 0 ) {
        $strsql = "SELECT classes.id, "
            . "classes.name, "
            . "IFNULL(COUNT(entries.id), '') AS num_items "
            . "FROM ciniki_musicfestival_categories AS categories "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "categories.id = classes.category_id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_recommendations AS recommendations ON ("
                . "recommendations.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
                . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                . "recommendations.id = entries.recommendation_id "
                . "AND classes.id = entries.class_id "
                . $status_sql
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "WHERE categories.section_id = '" . ciniki_core_dbQuote($ciniki, $args['section_id']) . "' "
            . "AND categories.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . "GROUP BY classes.id "
            . "ORDER BY categories.sequence, categories.name, classes.sequence, classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'num_items'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1279', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
        }
        $rsp['classes'] = isset($rc['classes']) ? $rc['classes'] : array();
        $total = 0;
        foreach($rsp['classes'] as $cid => $class) {
            if( $class['num_items'] > 0 ) {
                $rsp['classes'][$cid]['name'] .= ' (' . $class['num_items'] . ')';
                $total += $class['num_items'];
            }
        }
        array_unshift($rsp['classes'], ['id' => 0, 'name' => 'All' . ($total > 0 ? " ({$total})" : '')]);
    }

    //
    // Load the statuses
    //
    if( isset($args['statuses']) && $args['statuses'] == 'yes' ) {
        //
        // Get the counts
        //
        $strsql = "SELECT entries.status, "
            . "COUNT(entries.id) AS num_items "
            . "FROM ciniki_musicfestival_recommendations AS recommendations "
            . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                . "recommendations.id = entries.recommendation_id "
                . $class_sql
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . $section_sql
            . "GROUP BY entries.status "
            . "ORDER BY entries.status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.musicfestivals', 'num_items');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1280', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num_items']) ? $rc['num_items'] : array();
        $rsp['entry_statuses'] = [
            '0' => ['status' => '0', 'name' => 'All', 'num_items' => ''],
            '10' => ['status' => '10', 'name' => 'Recommended', 'num_items' => ''],
            '20' => ['status' => '20', 'name' => 'Issues', 'num_items' => ''],
            '30' => ['status' => '30', 'name' => 'Approved', 'num_items' => ''],
            '35' => ['status' => '35', 'name' => 'Invited', 'num_items' => ''],
            '40' => ['status' => '40', 'name' => 'Accepted', 'num_items' => ''],
            '45' => ['status' => '45', 'name' => 'Instructions Sent', 'num_items' => ''],
            '50' => ['status' => '50', 'name' => 'Registered', 'num_items' => ''],
            '70' => ['status' => '70', 'name' => 'Turned Down', 'num_items' => ''],
            '80' => ['status' => '80', 'name' => 'Already Recommended', 'num_items' => ''],
            '85' => ['status' => '85', 'name' => 'Ineligible', 'num_items' => ''],
            '90' => ['status' => '90', 'name' => 'Expired', 'num_items' => ''],
            ];
        $total = 0;
        foreach($num_items as $status => $num) {
            if( isset($rsp['entry_statuses'][$status]['num_items']) ) {
                $rsp['entry_statuses'][$status]['num_items'] = $num;
            }
            $total += $num;
        }
        if( $total > 0 ) {
            $rsp['entry_statuses'][0]['num_items'] = $total;
        }
    }

    //
    // Load the submissions
    //
    if( isset($args['recommendations']) && $args['recommendations'] == 'yes' ) {
        $strsql = "SELECT recommendations.id, "
            . "recommendations.date_submitted, "
            . "DATE_FORMAT(recommendations.date_submitted, '%b %e, %Y') AS date_submitted_text, "
            . "recommendations.status, "
            . "recommendations.status AS status_text, "
            . "recommendations.adjudicator_name, "
            . "sections.name AS section_name "
            . "FROM ciniki_musicfestival_recommendations AS recommendations "
            . "INNER JOIN ciniki_musicfestival_sections AS sections ON ("
                . "recommendations.section_id = sections.id "
                . $section_sql
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "WHERE recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . "AND recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "ORDER BY recommendations.date_submitted DESC, section_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'recommendations', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'date_submitted', 'date_submitted_text', 'status', 'status_text', 'adjudicator_name', 'section_name',
                    ),
                'maps'=>array('status_text'=>$maps['recommendation']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1282', 'msg'=>'Unable to load submissions', 'err'=>$rc['err']));
        }
        $rsp['recommendations'] = isset($rc['recommendations']) ? $rc['recommendations'] : array();
        array_unshift($rsp['recommendations'], ['id' => 0, 'section_name' => 'All']);
    }

    //
    // Get the entries
    //
    if( isset($args['entries']) && $args['entries'] == 'yes' ) {
        $strsql = "SELECT entries.id, "
            . "entries.recommendation_id, "
            . "entries.status, "
            . "entries.status AS status_text, "
            . "entries.name, "
            . "entries.position, "
            . "entries.position AS position_text, "
            . "entries.mark, "
            . "entries.provincials_reg_id, "
            . "entries.local_reg_id, "
            . "recommendations.date_submitted, "
            . "sections.name AS section_name, "
            . "categories.name AS category_name, "
            . "classes.name AS class_name "
            . "FROM ciniki_musicfestival_recommendations AS recommendations "
            . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
                . "recommendations.id = entries.recommendation_id "
                . $class_sql
                . $status_sql
                . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_classes AS classes ON ("
                . "entries.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_categories AS categories ON ("
                . "classes.category_id = categories.id "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
                . "categories.section_id = sections.id "
                . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
                . ") "
            . "WHERE recommendations.festival_id = '" . ciniki_core_dbQuote($ciniki, $provincials_festival_id) . "' "
            . "AND recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $member['id']) . "' "
            . $recommendation_sql
            . $section_sql
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $provincials_tnid) . "' "
            . "ORDER BY recommendations.date_submitted DESC, sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name, entries.position "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'entries', 'fname'=>'id', 
                'fields'=>array('id', 'recommendation_id', 'status', 'status_text', 'name', 'position', 'position_text', 'mark', 
                    'provincials_reg_id', 'local_reg_id', 'date_submitted', 
                    'section_name', 'category_name', 'class_name',
                    ),
                'maps'=>array(
                    'status_text'=>$maps['recommendationentry']['status'],
                    'position_text'=>$maps['recommendationentry']['position'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1281', 'msg'=>'Unable to load entries', 'err'=>$rc['err']));
        }
        $rsp['entries'] = isset($rc['entries']) ? $rc['entries'] : array();
        $rsp['num_approved'] = 0;
        $rsp['num_accepted'] = 0;
        foreach($rsp['entries'] as $entry) {
            if( $entry['status'] == 30 ) {
                $rsp['num_approved']++;
            } elseif( $entry['status'] == 40 ) {
                $rsp['num_accepted']++;
            }
        }
    }

    //
    // Get the adjudicators
    //
    if( isset($args['adjudicators']) && $args['adjudicators'] == 'yes' ) {
        $strsql = "SELECT adjudicators.id, "
            . "customers.display_name "
            . "FROM ciniki_musicfestival_adjudicators AS adjudicators "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "adjudicators.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY customers.display_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1285', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
        }
        $rsp['adjudicators'] = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();
    }

    return $rsp;
}
?>
