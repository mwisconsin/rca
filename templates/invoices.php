<?php

require_once('include/address.php');  // for create_html_display_address

// Template for business partner and care facility invoices.  Expects global variables of:
//
// $invoicee_contact = array( 'Name' => string representing (BP/CF) name
//                            'ContactName' => string representing primary contact name
//                            'Address' => standard address hash )
//
// $invoice_columns = array( numeric indexed array; each value is the symbolic column name used 
//                           to access values in the following hashes )
//
// $invoice_column_names = array( hash_index => string for displayable name;
//                                hash index comes from $invoice_columns )
// $invoice_data = array( numeric indexed array; each entry is an array:
//                        array( 'Rows' => array( numeric indexed array; each entry is an array:
//                                                array('Columns => array(hash_index => display string) )
//                                              )
//                               'SummaryRow' => array( hash_index => displayable string; 
//                                                      hash index comes from $invoice_columns) 
//                             )
//                      )
//
// For future expansion, add a usage total section toward the end?
//
//
// The template is designed to not include any processing - it is display only.

global $invoicee_contact, $invoice_columns, $invoice_column_names, $invoice_column_data;
?>
<div>
<?php echo $invoicee_contact['Name'] ?><br />
<?php echo $invoicee_contact['ContactName'] ?><br />
<?php echo '<div>' . create_compact_display_address($invoicee_contact['Address']) . '</div>'; ?>
</div>
<table border="1" width="100%">
    <tr><?php
        foreach ($invoice_columns as $column_index) {
            echo '<th>' . $invoice_column_names[$column_index] . '</th>';
        }?>
    </tr>
<?php
    // The displayable data

    // Each data subsection (e.g. a BP location, or a CF rider)
    foreach ($invoice_data as $data_item) {

        // Display the rows first
        foreach ($data_item['Rows'] as $row_data) {

            // To display a row, iterate through the columns and spit out the strings
            echo '    <tr>';
            foreach ($invoice_columns as $column_index) {
                echo "<td>{$row_data[$column_index]}</td>";
            }
            echo "</tr>\n";
        }

        // Then the summary row - it will have bolded cell contents
        echo '    <tr style="font-weight: bold;">';
        foreach ($invoice_columns as $column_index) {
            echo "<td>{$data_item['SummaryRow'][$column_index]}</td>";
        }
        echo "</tr>\n";
    } // Done displaying data
?>

</table>
