<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';

use Medoo\Medoo;
$database = new Medoo($databaseconfig);

use XeroPHP\Application\PrivateApplication;
$xero = new PrivateApplication($xeroconfig);

$totalUpdateRows = 0; $totalNewRows = 0; $totalWarnRows = 0;

/* --- GET LAST SYNC DATE --- */
$lastSyncDate = "2015-01-01";
$lastSyncDateDB = $database->select("options","option_value",["option_name"=>"lastSyncDate"]);
if (sizeof($lastSyncDateDB) == 0) {
    //No date already stored
    $lastSyncDate = \DateTime::createFromFormat('Y-m-d', '2000-01-01');
} else {
    $lastSyncDate = \DateTime::createFromFormat('Y-m-d', $lastSyncDateDB[0]);
    //$lastSyncDate = date_sub($lastSyncDate,date_interval_create_from_date_string('1 days'));
}

/* --- READ IN INVOICES FROM XERO INTO SQLSERVER --- */
$pageNo = 1;
do {
    $invoices = $xero->load('Accounting\\Invoice')
        ->page($pageNo)
        ->where('Type', XeroPHP\Models\Accounting\Invoice::INVOICE_TYPE_ACCREC)
        ->modifiedAfter($lastSyncDate)
        ->execute(); 

    foreach($invoices as $inv) {
        $invoicedata = [
            "XeroID" => $inv->InvoiceID,
            "JobNo" => jobNoFromInvoiceReference($inv->Reference),
            "InvoiceNumber" => $inv->InvoiceNumber,
            "ContactName" => $inv->Contact->Name,
            "SubTotal" => friendlyInvoiceSubtotal($inv->SubTotal,$inv->Status),
            "InvoiceDate" => $inv->Date->format('Y-m-d'),
            "InvoiceStatus" => friendlyInvoiceStatus($inv->Status)
        ];

        if ($database->has("xeroInvoices",["XeroID" => $inv->InvoiceID])) {
            $updatedrows = $database->update("XeroInvoices",$invoicedata,["XeroID" => $inv->InvoiceID])->rowCount();
            if ($updatedrows > 0) {
                ++$totalUpdateRows;
                echo "Invoice " . $inv->InvoiceNumber . " updated\r\n";
            } else {
                ++$totalWarnRows;
                echo "***WARNING: Invoice " . $inv->InvoiceNumber . " not updated\r\n";
            };
        } else {
            $updatedrows = $database->insert("XeroInvoices",$invoicedata)->rowCount(); 
            if ($updatedrows > 0) {
                    ++$totalNewRows;
                    echo "Invoice " . $inv->InvoiceNumber . " inserted\r\n";
                } else {
                    ++$totalWarnRows;
                    echo "***WARNONG: Invoice " . $inv->InvoiceNumber . " not updated\r\n";
                };
        }
    }
    
    // Check if there's another page of invoices
    $noOfInvoicesOnPage = count($invoices);
    if( $noOfInvoicesOnPage == 100) {
        ++$pageNo;
        $morePages = true;
    } else {$morePages = false;};

} while ($morePages == true);

echo $totalUpdateRows . " invoices(s) updated, " . $totalNewRows . " invoices(s) inserted, " . $totalWarnRows. " invoices(s) failed to update.\r\n";

/* --- READ IN CREDIT NOTES FROM XERO --- */
/*$creditNotes = $xero->load('Accounting\\CreditNote')
    ->where('Type', XeroPHP\Models\Accounting\CreditNote::CREDIT_NOTE_TYPE_ACCRECCREDIT)
    ->execute(); 

//print_r($creditNotes);

foreach($creditNotes as $cr) {
    echo($cr->CreditNoteNumber . ", ". 
    $cr->Reference . ", " . 
    $cr->Contact->Name . ", ". 
    $cr->Date->format('Y-m-d') . ", ". 
    $cr->SubTotal . ", " . 
    $cr->Status . ", " . 
    $cr->CreditNoteID . "\r\n");
}*/
$totalUpdateRows = 0; $totalNewRows = 0; $totalWarnRows = 0;
$pageNo = 1;
do {
    $creditNotes = $xero->load('Accounting\\CreditNote')
        ->page($pageNo)
        ->where('Type', XeroPHP\Models\Accounting\CreditNote::CREDIT_NOTE_TYPE_ACCRECCREDIT)
        ->modifiedAfter($lastSyncDate)
        ->execute(); 

    foreach($creditNotes as $cr) {
        $crdata = [
            "XeroID" => $cr->CreditNoteID,
            "JobNo" => jobNoFromInvoiceReference($cr->Reference),
            "InvoiceNumber" => $cr->CreditNoteNumber,
            "ContactName" => $cr->Contact->Name,
            "SubTotal" => -1 * friendlyInvoiceSubtotal($cr->SubTotal,$cr->Status),
            "InvoiceDate" => $cr->Date->format('Y-m-d'),
            "InvoiceStatus" => friendlyInvoiceStatus($cr->Status)
        ];

        if ($database->has("xeroInvoices",["XeroID" => $cr->CreditNoteID])) {
            $updatedrows = $database->update("XeroInvoices",$crdata,["XeroID" => $cr>CreditNoteID])->rowCount();
            if ($updatedrows > 0) {
                ++$totalUpdateRows;
                echo "Credit note " . $cr->CreditNoteNumber . " updated\r\n";
            } else {
                ++$totalWarnRows;
                echo "***WARNING: Credit note " . $cr->CreditNoteNumber . " not updated\r\n";
            };
        } else {
            $updatedrows = $database->insert("XeroInvoices",$crdata)->rowCount(); 
            if ($updatedrows > 0) {
                    ++$totalNewRows;
                    echo "Credit note " . $cr->CreditNoteNumber . " inserted\r\n";
                } else {
                    ++$totalWarnRows;
                    echo "***WARNING: Credit note " . $cr->CreditNoteNumber . " not updated\r\n";
                };
        }
    }
    
    // Check if there's another page of invoices
    $noOfInvoicesOnPage = count($creditNotes);
    if( $noOfInvoicesOnPage == 100) {
        ++$pageNo;
        $morePages = true;
    } else {$morePages = false;};

} while ($morePages == true);

echo $totalUpdateRows . " credit note(s) updated, " . $totalNewRows . " credit note(s) inserted, " . $totalWarnRows. " credit note(s) failed to update.\r\n";


/* --- Update cached columns in database --- */
$database->query("EXEC sp_UpdateCachedJobColumns;");

/* --- Record Sync date --- */
$out = $database->update("options",["option_value"=>date('Y-m-d')],["option_name"=>"lastSyncDate"])->rowCount();
if ($out == 0) {$database->insert("options",["option_name"=>"lastSyncDate","option_value"=>date('Y-m-d')]);}

/* --- Formatting functions --- */
function friendlyInvoiceStatus($xeroStatus) {
    switch ($xeroStatus) {
        case "DRAFT":
            return "Draft";
        case "SUBMITTED":
            return "Awaiting Approval";
        case "AUTHORISED":
            return "Awaiting Payment";
        case "PAID":
            return "Paid";
        case "VOIDED":
            return "Voided";
        case "DELETED":
            return "Deleted";
        default:
            return "";
    }
}

function jobNoFromInvoiceReference($xeroReference) {
    $strElems = preg_split("/[\s\/\-]+/",$xeroReference);
    //var_dump($strElems);
    if (sizeof($strElems) < 3) {
        //not enough
        echo '***WARNING: Check reference (reference incoherence): ';
        return "";
    } else {
        if (is_numeric($strElems[1]) && is_numeric($strElems[2])) {
            return $strElems[1] . "/" . $strElems[2];
        } else {
            echo '***WARNING: Check reference (reference incoherence): ';
            return "";
        }
    }
    //return substr($xeroReference,4,8);
}

function friendlyInvoiceSubtotal($subtotal,$invoiceStatus) {
    switch ($invoiceStatus) {
        case "AUTHORISED":
        case "PAID":
            return $subtotal;
        case "DRAFT":
        case "VOIDED":
        case "DELETED":
        case "SUBMITTED":
            return null;
    }
}


?>