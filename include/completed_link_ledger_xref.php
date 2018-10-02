<?php

require_once('include/database.php');
require_once('include/rc_log.php');


/**
 * Store a completed link to ledger entry cross reference to the database.  This is
 * for an original entry (not an adjustment or status change).
 * @param $link_id completed link 
 * @param $ledger_id ledger ID for links
 * @param $entity_role 'RIDER', 'DRIVER', or 'RCAPARTNER'
 * @return TRUE on success, FALSE on failure
 */
function store_original_link_ledger_xref( $link_id, $ledger_id, $entity_role ) {
    return store_link_ledger_xref($link_id, $ledger_id, $entity_role, 0);
}


/**
 * Store a completed link to ledger entry cross reference to the database.  This is
 * for an adjusted entry (i.e. a status change or amount tweak).
 * @param $link_id completed link 
 * @param $ledger_id ledger ID for links
 * @param $entity_role 'RIDER', 'DRIVER', or 'RCAPARTNER'
 * @return TRUE on success, FALSE on failure
 */
function store_adjustment_link_ledger_xref( $link_id, $ledger_id, $entity_role, $seq_num ) {
    return store_link_ledger_xref($link_id, $ledger_id, $entity_role, $seq_num);
}

/**
 * Store a completed link to ledger entry cross reference to the database.  
 * This function should be called internally, not from other files.
 * @param $link_id completed link 
 * @param $ledger_id ledger ID for links
 * @param $entity_role 'RIDER', 'DRIVER', or 'RCAPARTNER'
 * @param $type Sequence number, increasing with numbers the same for a transaction set
 * @return TRUE on success, FALSE on failure
 */
function store_link_ledger_xref($link_id, $ledger_id, $entity_role, $seq_num) {
    $safe_link_id = mysql_real_escape_string($link_id);
    $safe_ledger_id = mysql_real_escape_string($ledger_id);
    $safe_role = mysql_real_escape_string($entity_role);
    $safe_seq = mysql_real_escape_string($seq_num);

    $sql = "INSERT INTO completed_link_ledger_xref 
                (LinkID, LedgerEntryID, EntityRole, LinkSequence) VALUES
            ($safe_link_id, $safe_ledger_id, '$safe_role', $safe_seq)";
    $result = mysql_query($sql);
    
    if ($result) {
        return TRUE;
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(), 
                        "Error storing lh-ledger xref $seq_num for $link_id, $ledger_id, $entity_role", $sql);
    }
    return FALSE;
}

function get_max_link_ledger_sequence_num($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "SELECT MAX(LinkSequence) AS MaxSeq FROM completed_link_ledger_xref
            WHERE LinkID=$safe_link_id";

    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        $ret = $row['MaxSeq'];
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Could not find max link sequence number for link $link_id", $sql);
        $ret = FALSE;
    }

    return $ret;
}

function get_last_link_ledger_transaction_set($link_id) {
    $safe_link_id = mysql_real_escape_string($link_id);

    $sql = "SELECT * FROM completed_link_ledger_xref NATURAL JOIN ledger
            WHERE completed_link_ledger_xref.LinkID = $safe_link_id AND
                  LinkSequence = (SELECT MAX(LinkSequence) FROM completed_link_ledger_xref
                                  WHERE LinkID = $safe_link_id)";

    $result = mysql_query($sql);
    if ($result) {
        $ret = array();
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $ret[] = $row;
        }
    } else {
        rc_log_db_error(PEAR_LOG_ERR, mysql_error(),
                        "Error finding last link-ledger transaction set for $link_id", $sql);
        $ret = FALSE;
    }

    return $ret;
}

?>
