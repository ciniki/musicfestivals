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
function ciniki_musicfestivals_schedulePDF($ciniki) {
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
        'titles'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Include Titles'),
        'video_urls'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Include Video URLs'),
        'ipv'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'In Person/Virtual'),
        'header'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Show Header'),
        'footer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Show Footer'),
        'footerdate'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Footer Date'),
        'section_page_break'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Section Page Break'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.schedulePDF');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Run the template
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'schedulePDF');
    $rc = ciniki_musicfestivals_templates_schedulePDF($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Return the pdf
    //
    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($rc['filename'], 'I');
    }

    return array('stat'=>'exit');
}
?>
