<?php
//
// Description
// -----------
// This function will return the blocks for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_musicfestivals_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.musicfestivals']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.206', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.207', 'msg'=>"No section specified."));
    }

    if( $section['ref'] == 'ciniki.musicfestivals.syllabus' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusProcess');
        return ciniki_musicfestivals_wng_syllabusProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.syllabussection' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'syllabusSectionProcess');
        return ciniki_musicfestivals_wng_syllabusSectionProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.registrations' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'registrationsProcess');
        return ciniki_musicfestivals_wng_registrationsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.files' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'filesProcess');
        return ciniki_musicfestivals_wng_filesProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.adjudicators' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'adjudicatorsProcess');
        return ciniki_musicfestivals_wng_adjudicatorsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.schedules' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'schedulesProcess');
        return ciniki_musicfestivals_wng_schedulesProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.results' ) {
        $section['settings']['results-only'] = 'yes';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'schedulesProcess');
        return ciniki_musicfestivals_wng_schedulesProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.timeslotphotos' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'timeslotPhotosProcess');
        return ciniki_musicfestivals_wng_timeslotPhotosProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.categorylists' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'categoryListsProcess');
        return ciniki_musicfestivals_wng_categoryListsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.sponsors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'sponsorsProcess');
        return ciniki_musicfestivals_wng_sponsorsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.trophies' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'trophiesProcess');
        return ciniki_musicfestivals_wng_trophiesProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.members' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'membersProcess');
        return ciniki_musicfestivals_wng_membersProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.recommendations' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'recommendationsProcess');
        return ciniki_musicfestivals_wng_recommendationsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.memberdeadlines' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'memberdeadlinesProcess');
        return ciniki_musicfestivals_wng_memberdeadlinesProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.musicfestivals.locations' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'wng', 'locationsProcess');
        return ciniki_musicfestivals_wng_locationsProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
