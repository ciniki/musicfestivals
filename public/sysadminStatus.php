<?php
//
// Description
// -----------
// This method will return the list of active/current festivals
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Social Post for.
//
// Returns
// -------
//
function ciniki_musicfestivals_sysadminStatus($ciniki) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Only sysadmins allowed
    //
    if( ($ciniki['session']['user']['perms']&0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.427', 'msg'=>'Access Denied'));
    }

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
    // Get the list of festivals
    //
    $strsql = "SELECT festivals.id, "
        . "festivals.tnid, "
        . "festivals.name AS festival_name, "
        . "tenants.name AS tenant_name, "
        . "festivals.flags, "
        . "festivals.status AS status_text, "
        . "DATE_FORMAT(festivals.start_date, '%b %d, %Y') AS start_date, "
        . "DATE_FORMAT(festivals.end_date, '%b %d, %Y') AS end_date, "
        . "(SELECT COUNT(*) "
            . "FROM ciniki_musicfestival_registrations AS registrations "
            . "WHERE festivals.id = registrations.festival_id "
            . ") AS num_reg, "
        . "settings.detail_key, "
        . "settings.detail_value "
        . "FROM ciniki_musicfestivals AS festivals "
        . "INNER JOIN ciniki_tenants AS tenants ON ("
            . "festivals.tnid = tenants.id "
            . ") "
        . "LEFT JOIN ciniki_musicfestival_settings AS settings ON ("
            . "festivals.id = settings.festival_id "
            . "AND detail_key IN ('waiver-general-title', 'waiver-general-msg') "
            . ") "
        . "WHERE festivals.status < 60 "
        . "ORDER BY festivals.start_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('id', 'tnid', 'festival_name', 'tenant_name', 'flags', 'status_text',
                'start_date', 'end_date', 'num_reg',
                ),
            'maps'=>array('status_text'=>$maps['festival']['status']),
            ),
        array('container'=>'settings', 'fname'=>'detail_key', 'fields'=>array('detail_value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.546', 'msg'=>'Unable to load festivals', 'err'=>$rc['err']));
    }
    $festivals = isset($rc['festivals']) ? $rc['festivals'] : array();
    $festivals_ids = array();
    $tnids = array();
    foreach($festivals as $k => $v) {
        $festivals_ids[] = $v['id'];
        if( !in_array($v['tnid'], $tnids) ) {
            $tnids[] = $v['tnid'];
        }
    }

    //
    // Get sapos flags
    //
    $strsql = "SELECT tnid, module, flags "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid in (" . ciniki_core_dbQuoteIDs($ciniki, $tnids) . ") "
        . "AND module = 'sapos' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sapos', 'fname'=>'tnid', 'fields'=>array('flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.751', 'msg'=>'Unable to load mail', 'err'=>$rc['err']));
    }
    $saposflags = isset($rc['sapos']) ? $rc['sapos'] : array();

    //
    // Check if they have a customer module enabled
    //
    $strsql = "SELECT tnid, module, flags "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid in (" . ciniki_core_dbQuoteIDs($ciniki, $tnids) . ") "
        . "AND package = 'customer' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'customermods', 'fname'=>'tnid', 'fields'=>array('flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.751', 'msg'=>'Unable to load mail', 'err'=>$rc['err']));
    }
    $customermods = isset($rc['customermods']) ? $rc['customermods'] : array();

    //
    // Get the mail settings
    //
    $strsql = "SELECT tnid, detail_value "
        . "FROM ciniki_mail_settings "
        . "WHERE tnid in (" . ciniki_core_dbQuoteIDs($ciniki, $tnids) . ") "
        . "AND detail_key = 'smtp-servers' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'mail', 'fname'=>'tnid', 'fields'=>array('detail_value')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.752', 'msg'=>'Unable to load mail', 'err'=>$rc['err']));
    }
    $mail = isset($rc['mail']) ? $rc['mail'] : array();

    //
    // Get the wng sites
    //
    $strsql = "SELECT sites.tnid, sites.theme, "
        . "(SELECT detail_value "
            . "FROM ciniki_wng_settings AS s1 "
            . "WHERE sites.tnid = s1.tnid AND s1.detail_key = 'meta-matomo-analytics-siteid' LIMIT 1) AS matomo_id, "
        . "(SELECT detail_value "
            . "FROM ciniki_wng_settings AS s2 "
            . "WHERE sites.tnid = s2.tnid AND s2.detail_key = 'cart-etransfer-submitted-message' LIMIT 1) AS etransfer_msg, "
        . "(SELECT detail_value "
            . "FROM ciniki_wng_settings AS s3 "
            . "WHERE sites.tnid = s3.tnid AND s3.detail_key = 'stripe-whsec' LIMIT 1) AS whsec "
        . "FROM ciniki_wng_sites AS sites "
        . "WHERE sites.tnid in (" . ciniki_core_dbQuoteIDs($ciniki, $tnids) . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'sites', 'fname'=>'tnid', 'fields'=>array('theme', 'matomo_id', 'etransfer_msg', 'whsec')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.750', 'msg'=>'Unable to load wng', 'err'=>$rc['err']));
    }
    $sites = isset($rc['sites']) ? $rc['sites'] : array();


    
    //
    // Process the festivals
    //
    foreach($festivals as $k => $v) {
        $festivals[$k]['waiver'] = '';
        $festivals[$k]['smtp'] = '';



        if( isset($v['settings']['waiver-general-title']['detail_value']) && $v['settings']['waiver-general-title']['detail_value'] != '' 
            && isset($v['settings']['waiver-general-msg']['detail_value']) && $v['settings']['waiver-general-msg']['detail_value'] != '' 
            ) {
            $festivals[$k]['waiver'] = 'yes';
        }
        if( isset($mail[$v['tnid']]['detail_value']) ) {
            if( $mail[$v['tnid']]['detail_value'] == 'email-smtp.us-east-1.amazonaws.com' ) {
                $festivals[$k]['smtp'] = 'AWS';
            } else {
                $festivals[$k]['smtp'] = $mail[$v['tnid']]['detail_value'];
            }
        }
        if( isset($sites[$v['tnid']]['theme']) && $sites[$v['tnid']]['theme'] != '' ) {
            $festivals[$k]['theme'] = $sites[$v['tnid']]['theme'];
        } elseif( isset($customermods[$v['tnid']]['flags']) ) {
            $festivals[$k]['theme'] = '-';
        }
        if( isset($sites[$v['tnid']]['matomo_id']) ) {
            $festivals[$k]['matomo_id'] = $sites[$v['tnid']]['matomo_id'];
        }
        if( isset($saposflags[$v['tnid']]['flags']) && ($saposflags[$v['tnid']]['flags']&0x40000000) == 0x40000000 ) {
            if( isset($sites[$v['tnid']]['etransfer_msg']) ) {
                $festivals[$k]['etransfer'] = 'yes';
            }
        } else {
            $festivals[$k]['etransfer'] = '-';
        }
        if( isset($saposflags[$v['tnid']]['flags']) && ($saposflags[$v['tnid']]['flags']&0x800000) == 0x800000 ) {
            if( isset($sites[$v['tnid']]['whsec']) ) {
                $festivals[$k]['stripe'] = 'yes';
            }
        } else {
            $festivals[$k]['stripe'] = '-';
        }
    }





    return array('stat'=>'ok', 'festivals'=>array_values($festivals), 'nplist'=>$festivals_ids);
}
?>
