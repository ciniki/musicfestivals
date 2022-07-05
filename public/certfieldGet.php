<?php
//
// Description
// ===========
// This method will return all the information about an certificate field.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the certificate field is attached to.
// field_id:          The ID of the certificate field to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_certfieldGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'field_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certificate Field'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.certfieldGet');
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
    // Return default for new Certificate Field
    //
    if( $args['field_id'] == 0 ) {
        $field = array('id'=>0,
            'certificate_id'=>'',
            'name'=>'',
            'field'=>'',
            'xpos'=>'',
            'ypos'=>'',
            'width'=>'',
            'height'=>'',
            'font'=>'',
            'size'=>'',
            'style'=>'',
            'align'=>'C',
            'valign'=>'M',
            'color'=>'',
            'bgcolor'=>'',
            'text'=>'',
        );
    }

    //
    // Get the details for an existing Certificate Field
    //
    else {
        $strsql = "SELECT ciniki_musicfestival_certificate_fields.id, "
            . "ciniki_musicfestival_certificate_fields.certificate_id, "
            . "ciniki_musicfestival_certificate_fields.name, "
            . "ciniki_musicfestival_certificate_fields.field, "
            . "ciniki_musicfestival_certificate_fields.xpos, "
            . "ciniki_musicfestival_certificate_fields.ypos, "
            . "ciniki_musicfestival_certificate_fields.width, "
            . "ciniki_musicfestival_certificate_fields.height, "
            . "ciniki_musicfestival_certificate_fields.font, "
            . "ciniki_musicfestival_certificate_fields.size, "
            . "ciniki_musicfestival_certificate_fields.style, "
            . "ciniki_musicfestival_certificate_fields.align, "
            . "ciniki_musicfestival_certificate_fields.valign, "
            . "ciniki_musicfestival_certificate_fields.color, "
            . "ciniki_musicfestival_certificate_fields.bgcolor, "
            . "ciniki_musicfestival_certificate_fields.text "
            . "FROM ciniki_musicfestival_certificate_fields "
            . "WHERE ciniki_musicfestival_certificate_fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_musicfestival_certificate_fields.id = '" . ciniki_core_dbQuote($ciniki, $args['field_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('certificate_id', 'name', 'field', 'xpos', 'ypos', 'width', 'height', 'font', 'size', 'style', 'align', 'valign', 'color', 'bgcolor', 'text'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.307', 'msg'=>'Certificate Field not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['fields'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.308', 'msg'=>'Unable to find Certificate Field'));
        }
        $field = $rc['fields'][0];
    }

    return array('stat'=>'ok', 'field'=>$field);
}
?>
