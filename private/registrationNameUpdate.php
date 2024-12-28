<?php
//
// Description
// -----------
// This function will update the registration display_name based on the rtype and competitor names.
//
// Arguments
// ---------
// ciniki:
// tnid:                 The tenant ID to check the session user against.
// method:                      The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_musicfestivals_registrationNameUpdate(&$ciniki, $tnid, $args) {
   
    //
    // Check if festival passed
    //
    if( isset($args['festival']) ) {
        $festival = $args['festival'];
    }
    elseif( isset($args['festival_id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'musicfestivals', 'private', 'festivalLoad');
        $rc = ciniki_musicfestivals_festivalLoad($ciniki, $tnid, $args['festival_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.895', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
        }
        $festival = $rc['festival'];
    }
    if( !isset($festival) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.736', 'msg'=>'No festival specified'));
    }

    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.billing_customer_id, "
        . "registrations.rtype, "
        . "registrations.status, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.private_name, "
        . "registrations.pn_display_name, "
        . "registrations.pn_public_name, "
        . "registrations.pn_private_name, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id, "
        . "registrations.class_id, "
        . "FORMAT(registrations.fee, 2) AS fee, "
        . "registrations.notes, "
        . "competitors.id AS competitor_id, "
        . "competitors.name AS competitor_name, "
        . "competitors.pronoun, "
        . "competitors.public_name AS competitor_public_name, "
        . "competitors.flags "
        . "FROM ciniki_musicfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_musicfestival_competitors AS competitors ON ( "
            . "(registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.musicfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('rtype', 'display_name', 'public_name', 'private_name',
                'pn_display_name', 'pn_public_name', 'pn_private_name',
                'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id',
                )),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('competitor_id', 'name'=>'competitor_name', 'public_name'=>'competitor_public_name', 'pronoun', 'flags'
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.115', 'msg'=>'Registration not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['registrations']) || count($rc['registrations']) <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.116', 'msg'=>'Unable to find Registration'));
    }
    $registration = array_pop($rc['registrations']);

    $display_name = '';
    $public_name = '';
    $private_name = '';
    $pn_display_name = '';
    $pn_public_name = '';
    $pn_private_name = '';

    //
    // Only update display name for non-ensembles
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    if( $registration['rtype'] < 90 ) {
        $names = array();   // Full names
        $publicnames = array();  // Public names
        $privatenames = array();  // Private names
        $pn_names = array();  // Pronoun Full Names
        $pn_publicnames = array();  // Pronoun Public Names
        $pn_privatenames = array();  // Pronoun Private Names
        for($i = 1; $i <= 4; $i++) {
            if( $registration["competitor{$i}_id"] > 0 
                && isset($registration['competitors'][$registration["competitor{$i}_id"]]['name']) 
                ) {
                $competitor = $registration['competitors'][$registration["competitor{$i}_id"]];
                $pronoun = $competitor['pronoun'] != '' ? ' (' . $competitor['pronoun'] . ')' : '';
                if( isset($festival['waiver-name-status']) 
                    && ($festival['waiver-name-status'] == 'on' || $festival['waiver-name-status'] == 'internal')
                    && ($competitor['flags']&0x04) == 0 
                    ) {
                    $names[] = "Name Withheld";
                    $publicnames[] = "Name Withheld";
                    $pn_names[] = "Name Withheld";
                    $pn_publicnames[] = "Name Withheld";
                    $privatenames[] = $competitor['name'];
                    $pn_privatenames[] = $competitor['name'] . $pronoun;
                } else {
                    $names[] = $competitor['name'];
                    $privatenames[] = $competitor['name'];
                    $pn_names[] = $competitor['name'] . $pronoun;
                    $pn_privatenames[] = $competitor['name'] . $pronoun;
                    if( $competitor['public_name'] != '' ) {
                        $publicnames[] = $competitor['public_name'];
                        $pn_publicnames[] = $competitor['public_name'] . $pronoun;
                    } else {
                        $publicnames[] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $competitor['name']); 
                        $pn_publicnames[] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $competitor['name']) . $pronoun; 
                    }
                }
            } 
        }
        if( count($names) == 4 ) {
            $display_name = $names[0] . ', ' . $names[1] . ', ' . $names[2] . ' & ' . $names[3];
            $public_name = $publicnames[0] . ', ' . $publicnames[1] . ', ' . $publicnames[2] . ' & ' . $publicnames[3];
            $private_name = $privatenames[0] . ', ' . $privatenames[1] . ', ' . $privatenames[2] . ' & ' . $privatenames[3];
            $pn_display_name = $pn_names[0] . ', ' . $pn_names[1] . ', ' . $pn_names[2] . ' & ' . $pn_names[3];
            $pn_public_name = $pn_publicnames[0] . ', ' . $pn_publicnames[1] . ', ' . $pn_publicnames[2] . ' & ' . $pn_publicnames[3];
            $pn_private_name = $pn_privatenames[0] . ', ' . $pn_privatenames[1] . ', ' . $pn_privatenames[2] . ' & ' . $pn_privatenames[3];
        } elseif( count($names) == 3 ) {
            $display_name = $names[0] . ', ' . $names[1] . ' & ' . $names[2];
            $public_name = $publicnames[0] . ', ' . $publicnames[1] . ' & ' . $publicnames[2];
            $private_name = $privatenames[0] . ', ' . $privatenames[1] . ' & ' . $privatenames[2];
            $pn_display_name = $pn_names[0] . ', ' . $pn_names[1] . ' & ' . $pn_names[2];
            $pn_public_name = $pn_publicnames[0] . ', ' . $pn_publicnames[1] . ' & ' . $pn_publicnames[2];
            $pn_private_name = $pn_privatenames[0] . ', ' . $pn_privatenames[1] . ' & ' . $pn_privatenames[2];
        } elseif( count($names) == 2 ) {
            $display_name = $names[0] . ' & ' . $names[1];
            $public_name = $publicnames[0] . ' & ' . $publicnames[1];
            $private_name = $privatenames[0] . ' & ' . $privatenames[1];
            $pn_display_name = $pn_names[0] . ' & ' . $pn_names[1];
            $pn_public_name = $pn_publicnames[0] . ' & ' . $pn_publicnames[1];
            $pn_private_name = $pn_privatenames[0] . ' & ' . $pn_privatenames[1];
        } elseif( count($names) == 1 ) {
            $display_name = $names[0];
            $public_name = $publicnames[0];
            $private_name = $privatenames[0];
            $pn_display_name = $pn_names[0];
            $pn_public_name = $pn_publicnames[0];
            $pn_private_name = $pn_privatenames[0];
        }
        $update_args = array();
        if( isset($display_name) && $display_name != $registration['display_name'] ) {
            $update_args['display_name'] = $display_name;
        }
        if( isset($public_name) && $public_name != $registration['public_name'] ) {
            $update_args['public_name'] = $public_name;
        }
        if( isset($private_name) && $private_name != $registration['private_name'] ) {
            $update_args['private_name'] = $private_name;
        }
        if( isset($pn_display_name) && $pn_display_name != $registration['pn_display_name'] ) {
            $update_args['pn_display_name'] = $pn_display_name;
        }
        if( isset($pn_public_name) && $pn_public_name != $registration['pn_public_name'] ) {
            $update_args['pn_public_name'] = $pn_public_name;
        }
        if( isset($pn_private_name) && $pn_private_name != $registration['pn_private_name'] ) {
            $update_args['pn_private_name'] = $pn_private_name;
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['registration_id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.117', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
            }
        }
    } elseif( $registration['rtype'] == 90 ) {  
        $update_args = array();
        if( $registration['competitor1_id'] > 0 
            && isset($registration['competitors'][$registration['competitor1_id']]['name']) 
            ) {
            if( isset($festival['waiver-name-status']) 
                && ($festival['waiver-name-status'] == 'on' || $festival['waiver-name-status'] == 'internal')
                && ($competitor['flags']&0x04) == 0
                ) {
                $display_name = 'Name Withheld';
                $public_name = 'Name Withheld';
                $private_name = $registration['competitors'][$registration['competitor1_id']]['name'];
                $pn_display_name = $display_name;
                $pn_public_name = $public_name;
                $pn_private_name = $private_name;
            } else {
                $display_name = $registration['competitors'][$registration['competitor1_id']]['name'];
                $public_name = $registration['competitors'][$registration['competitor1_id']]['name'];
                $private_name = $registration['competitors'][$registration['competitor1_id']]['name'];
                $pn_display_name = $display_name;
                $pn_public_name = $public_name;
                $pn_private_name = $private_name;
            }
            if( $registration['display_name'] != $registration['competitors'][$registration['competitor1_id']]['name'] ) {
                $update_args['display_name'] = $registration['competitors'][$registration['competitor1_id']]['name'];
            }
            if( $registration['public_name'] != $registration['competitors'][$registration['competitor1_id']]['name'] ) {
                $update_args['public_name'] = $registration['competitors'][$registration['competitor1_id']]['name'];
            }
            if( $registration['private_name'] != $registration['competitors'][$registration['competitor1_id']]['name'] ) {
                $update_args['private_name'] = $registration['competitors'][$registration['competitor1_id']]['name'];
            }
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.musicfestivals.registration', $args['registration_id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.152', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok', 'display_name'=>$display_name, 'public_name'=>$public_name, 'private_name'=>$private_name, 'pn_display_name'=>$pn_display_name, 'pn_public_name'=>$pn_public_name, 'pn_private_name'=>$pn_private_name);
}
?>
