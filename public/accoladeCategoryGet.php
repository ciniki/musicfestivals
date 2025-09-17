<?php
//
// Description
// ===========
// This method will return all the information about an accolade category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the accolade category is attached to.
// category_id:          The ID of the accolade category to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_accoladeCategoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Accolade Category'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladeCategoryGet');
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
    // Return default for new Accolade Category
    //
    if( $args['category_id'] == 0 ) {
        //
        // Get the next sequence 
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesNext');
        $rc = ciniki_core_sequencesNext($ciniki, $args['tnid'], 'ciniki.musicfestivals.accoladecategory', '', '');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sequence = $rc['sequence'];

        $accoladecategory = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'flags'=>0x03,
            'sequence'=>$sequence,
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
            'awarded_email_subject'=>'',
            'awarded_email_content'=>'',
            'awarded_pdf_content'=>'',
            'teacher_email_subject'=>'',
            'teacher_email_content'=>'',
        );
    }

    //
    // Get the details for an existing Accolade Category
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_accolade_categories.id, "
            . "ciniki_musicfestival_accolade_categories.name, "
            . "ciniki_musicfestival_accolade_categories.permalink, "
            . "ciniki_musicfestival_accolade_categories.flags, "
            . "ciniki_musicfestival_accolade_categories.sequence, "
            . "ciniki_musicfestival_accolade_categories.image_id, "
            . "ciniki_musicfestival_accolade_categories.synopsis, "
            . "ciniki_musicfestival_accolade_categories.description, "
            . "ciniki_musicfestival_accolade_categories.awarded_email_subject, "
            . "ciniki_musicfestival_accolade_categories.awarded_email_content, "
            . "ciniki_musicfestival_accolade_categories.awarded_pdf_content, "
            . "ciniki_musicfestival_accolade_categories.teacher_email_subject, "
            . "ciniki_musicfestival_accolade_categories.teacher_email_content "
            . "FROM ciniki_musicfestival_accolade_categories "
            . "WHERE ciniki_musicfestival_accolade_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_accolade_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'accoladecategories', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'flags', 'sequence', 'image_id', 'synopsis', 'description', 'awarded_email_subject', 'awarded_email_content', 'awarded_pdf_content', 'teacher_email_subject', 'teacher_email_content'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1106', 'msg'=>'Accolade Category not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['accoladecategories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.1107', 'msg'=>'Unable to find Accolade Category'));
        }
        $accoladecategory = $rc['accoladecategories'][0];
    }

    return array('stat'=>'ok', 'accoladecategory'=>$accoladecategory);
}
?>
