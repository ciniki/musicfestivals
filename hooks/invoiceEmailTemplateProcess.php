<?php
//
// Description
// -----------
// This hook will 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_hooks_invoiceEmailTemplateProcess(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    $invoice = $args['invoice'];
    $subject = $args['subject'];
    $message = $args['message'];

    //
    // Get the registration ID's to load
    //
    $reg_ids = [];
    foreach($invoice['items'] as $item) {
        if( $item['object'] == 'ciniki.musicfestivals.registration' && $item['object_id'] > 0 ) {
            $reg_ids[] = $item['object_id'];
        }
    }

    //
    // Load the registrations and competitors from invoice registration ids
    //
    $competitor_names = '';
    if( count($reg_ids) > 0 ) {
        $strsql = "SELECT registrations.id, "
            . "registrations.private_name, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "categories.name AS category_name, "
            . "sections.name AS section_name, "
            . "competitors.id AS competitor_id, "
            . "competitors.name, "
            . "competitors.first, "
            . "competitors.last "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
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
            . "INNER JOIN ciniki_musicfestival_competitors AS competitors ON ("
                . "("
                    . "registrations.competitor1_id = competitors.id "
                    . "OR registrations.competitor2_id = competitors.id "
                    . "OR registrations.competitor3_id = competitors.id "
                    . "OR registrations.competitor4_id = competitors.id "
                    . "OR registrations.competitor5_id = competitors.id "
                    . ") "
                . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $reg_ids) . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY registrations.id, competitors.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'class_code', 'class_name', 'category_name', 'section_name', 'private_name'),
                ),
            array('container'=>'competitors', 'fname'=>'competitor_id', 
                'fields'=>array('name', 'first', 'last'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1461', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
        $names = [];
        foreach($registrations as $reg) {
            if( !isset($reg['competitors']) ) { 
                continue;
            }
            foreach($reg['competitors'] as $competitor) {
                if( !in_array($competitor['name'], $names) ) {
                    $names[] = $competitor['name'];
                }
            }
        }

        //
        // Combine names
        //
        sort($names);
        for($i = 0; $i < count($names); $i++) {
            $competitor_names .= ($competitor_names != '' ? ($i < (count($names)-1) ? ', ' : ' and ') : '') . $names[$i];
        }
    }

    //
    // Run substitutions on subject and message
    //
    $subject = str_replace("{_competitornames_}", $competitor_names, $subject);
    $message = str_replace("{_competitornames_}", $competitor_names, $message);

    return array('stat'=>'ok', 'subject'=>$subject, 'message'=>$message);
}
?>
