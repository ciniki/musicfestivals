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
function ciniki_musicfestivals_wng_accountMemberRecommendationsProcess(&$ciniki, $tnid, &$request, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'wng', 'private', 'videoProcess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'titleMerge');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'maps');
    $rc = ciniki_musicfestivals_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/musicfestivalmembers';
    $display = 'list';

    if( isset($_POST['submit']) && $_POST['submit'] == 'Back' ) {
        header("Location: {$request['ssl_domain_base_url']}/account/musicfestivalmembers");
        return array('stat'=>'exit');
    }

    if( !isset($args['member']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.753', 'msg'=>'No member specified'));
    }
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.754', 'msg'=>'No festival specified'));
    }

    //
    // Load the list of recommendations for the member festival
    //
    $strsql = "SELECT entries.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS class, "
        . "entries.position, "
        . "entries.name, "
        . "entries.mark, "
        . "recommendations.adjudicator_name "
        . "FROM ciniki_musicfestival_recommendations AS recommendations "
        . "INNER JOIN ciniki_musicfestival_recommendation_entries AS entries ON ("
            . "recommendations.id = entries.recommendation_id "
            . "AND entries.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_musicfestival_classes AS classes ON ("
            . "entries.class_id = classes.id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE recommendations.member_id = '" . ciniki_core_dbQuote($ciniki, $args['member']['id']) . "' "
        . "AND recommendations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY entries.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'recommendations', 'fname'=>'id', 
            'fields'=>array( 'id', 'class', 'position', 'name', 'mark', 'adjudicator_name'),
            'maps'=>array('position'=>$maps['recommendationentry']['position']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.755', 'msg'=>'Unable to load recommendations', 'err'=>$rc['err']));
    }
    $recommendations = isset($rc['recommendations']) ? $rc['recommendations'] : array();

    $blocks[] = array(
        'type' => 'table',
        'headers' => 'yes',
        'class' => 'fold-at-50',
        'columns' => array(
            array('label'=>'Competitor', 'field'=>'name'),
            array('label'=>'Class', 'fold-label'=>'Class: ', 'field'=>'class'),
            array('label'=>'Position', 'fold-label'=>'Position: ', 'field'=>'position'),
            array('label'=>'Mark', 'fold-label'=>'Mark: ', 'field'=>'mark'),
            array('label'=>'Adjudicator', 'fold-label'=>'Adjudicator: ', 'field'=>'adjudicator_name'),
            ),
        'rows' => $recommendations,
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
