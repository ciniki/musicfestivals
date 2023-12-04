<?php
//
// Description
// ===========
// This method will return all the information about an social post.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the social post is attached to.
// socialpost_id:          The ID of the social post to get the details for.
//
// Returns
// -------
//
function ciniki_musicfestivals_socialPostGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'socialpost_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Social Post'),
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
    $rc = ciniki_musicfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.musicfestivals.socialPostGet');
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
    // Return default for new Social Post
    //
    if( $args['socialpost_id'] == 0 ) {
        $socialpost = array('id'=>0,
            'tnid' => $args['tnid'],
            'user_id'=>'',
            'flags'=>'0',
            'image_id'=>'',
            'content'=>'',
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing Social Post
    //
    else {
        $strsql = "SELECT posts.id, "
            . "posts.tnid, "
            . "tenants.name AS tenant_name, "
            . "posts.user_id, "
            . "users.display_name AS user_display_name, "
            . "posts.flags, "
            . "posts.image_id, "
            . "posts.content, "
            . "posts.notes "
            . "FROM ciniki_musicfestivals_socialposts AS posts "
            . "LEFT JOIN ciniki_users AS users ON ("
                . "posts.user_id = users.id "
                . ") "
            . "LEFT JOIN ciniki_tenants AS tenants ON ("
                . "posts.tnid = tenants.id "
                . ") "
            . "WHERE ("
                . "posts.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "OR (posts.flags&0x01) = 0x01 "
                . ") "
            . "AND posts.id = '" . ciniki_core_dbQuote($ciniki, $args['socialpost_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
            array('container'=>'socialposts', 'fname'=>'id', 
                'fields'=>array('tnid', 'tenant_name', 'user_id', 'user_display_name', 'flags', 'image_id', 'content', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.615', 'msg'=>'Social Post not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['socialposts'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.616', 'msg'=>'Unable to find Social Post'));
        }
        $socialpost = $rc['socialposts'][0];
    }

    return array('stat'=>'ok', 'socialpost'=>$socialpost);
}
?>
