<?php
//
// Description
// -----------
// This method will mark the recommendation as reviewed and send an email updating the local festival
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_recommendationReviewed(&$ciniki) {

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'recommendation_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Adjudicator Recommendation'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendationReviewed');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
        
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
    // Load the recommendation
    //
    $strsql = "SELECT recommendations.id, "
        . "recommendations.uuid, "
        . "recommendations.member_id, "
        . "members.name AS member_name, "
        . "recommendations.festival_id, "
        . "recommendations.section_id, "
        . "IFNULL(sections.name, '') AS section_name, "
        . "recommendations.status, "
        . "recommendations.status AS status_text, "
        . "recommendations.adjudicator_name, "
        . "recommendations.adjudicator_phone, "
        . "recommendations.adjudicator_email, "
        . "recommendations.acknowledgement, "
        . "recommendations.date_submitted, "
        . "entries.id AS entry_id, "
        . "entries.status AS entry_status, "
        . "entries.status AS entry_status_text, "
        . "entries.class_id, "
        . "entries.position, "
        . "entries.name AS entry_name, "
        . "entries.mark, "
        . "entries.provincials_reg_id, "
        . "entries.local_reg_id, "
        . "entries.notes "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "LEFT JOIN ciniki_musicfestivals_members AS members ON ("
            . "recommendations.member_id = members.id "
            . "AND members.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_sections AS sections ON ("
            . "recommendations.section_id = sections.id "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE recommendations.id = '" . ciniki_core_dbQuote($ciniki, $args['recommendation_id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY entries.class_id, entries.position "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'festival_id', 'member_id', 'member_name', 'section_id', 'section_name', 
                'adjudicator_name', 'adjudicator_phone', 'adjudicator_email', 
                'status', 'status_text',
                'acknowledgement', 'date_submitted',
                ),
            'maps'=>array('status_text'=>$maps['recommendation']['status']),
            'utctotz'=>array(
                'date_submitted'=>array('format'=>'M j, Y g:i A', 'timezone'=>$intl_timezone),
                ),
            ),
        array('container'=>'entries', 'fname'=>'entry_id', 
            'fields'=>array('id'=>'entry_id', 'status', 'status_text'=>'entry_status_text', 'class_id', 
                'position', 'name'=>'entry_name', 'mark', 
                'provincials_reg_id', 'local_reg_id', 'notes',
                ),
            'maps'=>array('status_text'=>$maps['recommendationentry']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1034', 'msg'=>'Unable to load recommendations', 'err'=>$rc['err']));
    }
    if( !isset($rc['recommendations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1033', 'msg'=>'Recommendation not found'));
    }
    $recommendation = $rc['recommendations'][0];
    
    if( isset($recommendation['entries']) ) {
        $entries = $recommendation['entries'];
        $recommendation['entries'] = [];
        foreach($entries as $cid => $entry) {
            $recommendation['entries'][$entry['class_id']][$entry['position']] = $entry;
        }
    }

    if( $recommendation['status'] < 50 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.musicfestivals.recommendation', $recommendation['id'], [
            'status' => 50,
            ], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1053', 'msg'=>'Unable to update the recommendation', 'err'=>$rc['err']));
        } 
    }

    //
    // Load the provincial sections
    //
    $strsql = "SELECT sections.id, "
        . "sections.permalink, "
        . "sections.name, "
        . "sections.recommendations_description AS description "
        . "FROM ciniki_musicfestival_sections AS sections "
        . "WHERE sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sections.id = '" . ciniki_core_dbQuote($ciniki, $recommendation['section_id']) . "' "
        . "ORDER BY sections.sequence, sections.name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.musicfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1057', 'msg'=>'Unable to load section', 'err'=>$rc['err']));
    }
    if( !isset($rc['section']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1058', 'msg'=>'Unable to find requested section'));
    }
    $section = $rc['section'];
    
    //
    // Load the classes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationClassesLoad');
    $rc = ciniki_musicfestivals_recommendationClassesLoad($ciniki, $args['tnid'], $section);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $classes = isset($rc['classes']) ? $rc['classes'] : array();

    //
    // Load the member customers
    //
    $strsql = "SELECT customers.customer_id "
        . "FROM ciniki_musicfestival_member_customers AS customers "
        . "WHERE customers.member_id = '" . ciniki_core_dbQuote($ciniki, $recommendation['member_id']) . "' "
        . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('id'=>'customer_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1052', 'msg'=>'Unable to load members customers', 'err'=>$rc['err']));
    }
    $customers = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Send out email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'recommendationEmail');
    $rc = ciniki_musicfestivals_recommendationEmail($ciniki, $args['tnid'], [
        'recommendation' => $recommendation,
        'classes' => $classes,
        'member-subject' => "We have reviewed recommendations for " . $recommendation['section_name'],
        'members' => $customers,
        'email-type' => 'updated',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
