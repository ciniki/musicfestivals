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
        && strtolower($registration["movements{$i}"]) != 'tba'
        && strtolower($registration["movements{$i}"]) != 'tbd'
        && strtolower($registration["movements{$i}"]) != 'n/a'
        && strtolower($registration["movements{$i}"]) != 'not applicable'
        && strtolower($registration["movements{$i}"]) != 'none'
        ) {
        $line .= ', ' . $registration["movements{$i}"];
    }
    if( $registration["composer{$i}"] != ''
        && strtolower($registration["composer{$i}"]) != 'na'
        && strtolower($registration["composer{$i}"]) != 'tba'
        && strtolower($registration["composer{$i}"]) != 'tbd'
        && strtolower($registration["composer{$i}"]) != 'n/a'
        && strtolower($registration["composer{$i}"]) != 'not applicable'
        && strtolower($registration["composer{$i}"]) != 'none'
        ) {
        if( preg_match("/^\s*[Bb][Yy]\s+/", $registration["composer{$i}"]) ) {
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Rr][Rr]\s+/", $registration["composer{$i}"]) ) {    // arr. OR arranged
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Tt][Tt][Rr]\s+/", $registration["composer{$i}"]) ) {    // Attr or attributed
            $line .= ' ' . $registration["composer{$i}"];
        } elseif( preg_match("/^\s*[Aa][Dd][Aa][Pp]\s+/", $registration["composer{$i}"]) ) {     // Adapted
            $line .= ' ' . $registration["composer{$i}"];
        } else {
            $line .= ' by ' . $registration["composer{$i}"];
        }
    } 

    return array('stat'=>'ok', 'title'=>$line);
}
?>
