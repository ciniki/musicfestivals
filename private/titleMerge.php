<?php
//
// Description
// -----------
// This function will merge the title, composer and movements into 1 line
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_musicfestivals_titleMerge(&$ciniki, $tnid, $registration, $i) {

    $line = $registration["title{$i}"];
    if( $registration["movements{$i}"] != '' 
        && strtolower($registration["movements{$i}"]) != 'na'
        && strtolower($registration["movements{$i}"]) != 'n/a'
        && strtolower($registration["movements{$i}"]) != 'not applicable'
        ) {
        $line .= ', ' . $registration["movements{$i}"];
    }
    if( $registration["composer{$i}"] != ''
        && strtolower($registration["composer{$i}"]) != 'na'
        && strtolower($registration["composer{$i}"]) != 'n/a'
        && strtolower($registration["composer{$i}"]) != 'not applicable'
        ) {
        if( preg_match("/^\s*[Bb][Yy]\s+/", $registration["composer{$i}"]) ) {
            $line .= ' ' . $registration["composer{$i}"];
        } else {
            $line .= ' by ' . $registration["composer{$i}"];
        }
    } 

    return array('stat'=>'ok', 'title'=>$line);
}
?>
