<?php
//
// Description
// -----------
// This function will process the json rules object, setting up list numbering starts
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_rulesProcess(&$ciniki, $tnid, $rules_str) {

    $rules = json_decode($rules_str, true);

    $start = 1;
    if( isset($rules['sections']) ) {
        foreach($rules['sections'] as $sid => $section) {
            if( isset($section['start']) 
                && $section['start'] != '' 
                && $section['start'] != 'previous' 
                && is_numeric($section['start']) 
                ) {
                $rules['sections'][$sid]['start'] = intval($section['start']);
                $start = intval($section['start']);
            } else {
                $rules['sections'][$sid]['start'] = $start;
            }
            foreach($section['items'] as $iid => $item) {
                if( $section['list-type'] == 'A' ) {
                    $rules['sections'][$sid]['items'][$iid]['bullet'] = chr($start + 64) . '.';
                } elseif( $section['list-type'] == 'a' ) {
                    $rules['sections'][$sid]['items'][$iid]['bullet'] = chr($start + 96) . '.';
                } elseif( $section['list-type'] == 'I' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'numToRoman');
                    $rc = ciniki_core_numToRoman($cinii, $tnid, $start);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $rules['sections'][$sid]['items'][$iid]['bullet'] = $rc['roman'] . '.';
                } elseif( $section['list-type'] == 'i' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'numToRoman');
                    $rc = ciniki_core_numToRoman($cinii, $tnid, $start, ['lowercase'=>'yes']);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $rules['sections'][$sid]['items'][$iid]['bullet'] = $rc['roman'] . '.';
                } else {
                    $rules['sections'][$sid]['items'][$iid]['bullet'] = $start . '.';
                }
                $start++;
            }
        }
    } else {
        $rules['sections'] = [];
    }

    return array('stat'=>'ok', 'rules'=>$rules);
}
?>
