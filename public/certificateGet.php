<?php
//
// Description
// ===========
// This method will return all the information about an certificate.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the certificate is attached to.
// certificate_id:          The ID of the certificate to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_certificateGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'certificate_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certificate'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        'outlines'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Outlines'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.certificateGet');
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
    // Return default for new Certificate
    //
    if( $args['certificate_id'] == 0 ) {
        $certificate = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'image_id'=>'',
            'section_id'=>'',
            'min_score'=>'0',
        );
    }

    //
    // Get the details for an existing Certificate
    //
    else {
        $strsql = "SELECT certificates.id, "
            . "certificates.festival_id, "
            . "certificates.name, "
            . "certificates.image_id, "
            . "certificates.section_id, "
            . "certificates.min_score "
            . "FROM ciniki_musicfestival_certificates AS certificates "
            . "WHERE certificates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND certificates.id = '" . ciniki_core_dbQuote($ciniki, $args['certificate_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'certificates', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'image_id', 'section_id', 'min_score'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.292', 'msg'=>'Certificate not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['certificates'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.293', 'msg'=>'Unable to find Certificate'));
        }
        $certificate = $rc['certificates'][0];
        $args['festival_id'] = $certificate['festival_id'];

        //
        // Load the fields
        //
        $strsql = "SELECT fields.id, "
            . "fields.name, "
            . "fields.field, "
            . "fields.xpos, "
            . "fields.ypos, "
            . "fields.width, "
            . "fields.height, "
            . "fields.font, "
            . "fields.size, "
            . "fields.style, "
            . "fields.align, "
            . "fields.valign, "
            . "fields.color, "
            . "fields.bgcolor, "
            . "fields.text "
            . "FROM ciniki_musicfestival_certificate_fields AS fields "
            . "WHERE fields.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND fields.certificate_id = '" . ciniki_core_dbQuote($ciniki, $args['certificate_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'fields', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'field', 'xpos', 'ypos', 'width', 'height',
                    'font', 'size', 'style', 'align', 'valign', 'color', 'bgcolor', 'text',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.294', 'msg'=>'Unable to load fields', 'err'=>$rc['err']));
        }
        $certificate['fields'] = isset($rc['fields']) ? $rc['fields'] : array();
    }
    $rsp = array('stat'=>'ok', 'certificate'=>$certificate);

    //
    // Check if request to generate test certificate 
    //
    if( isset($args['output']) && $args['output'] == 'pdf' ) {
        foreach($certificate['fields'] as $fid => $field) {
            if( $field['field'] != 'text' ) {   
                $certificate['fields'][$fid]['text'] = $field['name'];
            }
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'templates', 'certificatesPDF');
        $rc = ciniki_musicfestivals_templates_certificatesPDF($ciniki, $args['tnid'], array(
            'festival_id' => $certificate['festival_id'],
            'certificates' => array($certificate),
            'testmode' => isset($args['outlines']) && $args['outlines'] == 'yes' ? 'yes' : 'no',
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.303', 'msg'=>'Unable to generate PDF', 'err'=>$rc['err']));
        }
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'I');
            return array('stat'=>'exit');
        }
    }

    //
    // Load the sections for the festival
    //
    if( isset($args['festival_id']) && $args['festival_id'] > 0 ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_musicfestival_sections "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence, name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'sections', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.295', 'msg'=>'Unable to load sections', 'err'=>$rc['err']));
        }
        $rsp['sections'] = isset($rc['sections']) ? $rc['sections'] : array();
        array_unshift($rsp['sections'], array('id'=>0, 'name'=>'All'));
    }

    return $rsp;
}
?>
