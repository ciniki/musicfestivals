<?php
//
// Description
// ===========
// This method will return all the information about an section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the section is attached to.
// section_id:          The ID of the section to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_scheduleWord($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'schedulesection_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section'),
        'division_header_format'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division Header Format'),
        'division_header_labels'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division Header Labels'),
        'section_adjudicator_bios'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section Adjudicator Bios'),
        'names'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name Format'),
        'competitor_numbering'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Competitor Numbering'),
        'titles'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Include Titles'),
        'video_urls'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Include Video URLs'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
        'header'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Show Header'),
        'footer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Show Footer'),
        'footerdate'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Footer Date'),
        'section_page_break'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section Page Break'),
        'division_page_break'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Division Page Break'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Note: These can be made into options in the future
    //
    $args['top_sponsors'] = 'yes';
    $args['provincials_info'] = 'yes';
    $args['bottom_sponsors'] = 'yes';

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'checkAccess');
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.scheduleWord');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Run the template
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.musicfestivals', 0x010000) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'scheduleProvincialsWord');
        $rc = ciniki_musicfestivals_templates_scheduleProvincialsWord($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'scheduleWord');
        $rc = ciniki_musicfestivals_templates_scheduleWord($ciniki, $args['tnid'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Return the word doc
    //
    if( isset($rc['word']) ) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . preg_replace("/[^A-Za-z0-9]/", '', $rc['filename']) . '.docx');
        header('Cache-Control: max-age=0');

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($rc['word'], 'Word2007');
        $objWriter->save('php://output');
    }

    return array('stat'=>'exit');
}
?>
