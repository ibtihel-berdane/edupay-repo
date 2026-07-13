<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Minimal PDF helper layer used by invoice download endpoints.
function pdf_text(string $text): string
{
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    } else {
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function status_text(?string $status): string
{
    return [
        'paid' => 'Payee',
        'unpaid' => 'Non payee',
        'partially_paid' => 'Partiellement payee',
        'late' => 'En retard',
        'pending' => 'En attente',
        'validated' => 'Valide',
        'rejected' => 'Rejete',
        'obligatory' => 'Obligatoire',
        'optional' => 'Optionnel',
    ][$status ?? ''] ?? ucwords(str_replace('_', ' ', (string)$status));
}

function send_simple_pdf(string $filename, string $title, array $lines): never
{
    $y = 780;
    $content = "BT /F1 18 Tf 50 {$y} Td (" . pdf_text($title) . ") Tj ET\n";
    $y -= 28;

    foreach ($lines as $line) {
        $wrapped = $line === '' ? [''] : explode("\n", wordwrap((string)$line, 92, "\n", true));
        foreach ($wrapped as $wrappedLine) {
            if ($y < 45) {
                break 2;
            }
            if ($wrappedLine === '') {
                $y -= 10;
                continue;
            }
            $content .= "BT /F1 10 Tf 50 {$y} Td (" . pdf_text($wrappedLine) . ") Tj ET\n";
            $y -= 15;
        }
    }

    $objects = [
        "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
        "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
        "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
        "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream\nendobj\n",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object;
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

function send_invoice_pdf(array $invoice, array $items, array $payments): never
{
    $lines = [
        'Numero: ' . $invoice['invoice_number'],
        'Etudiant: ' . trim(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')),
        'Matricule: ' . ($invoice['matricule'] ?? ''),
        'Filiere / Niveau: ' . program_label($invoice['program'] ?? '') . ' / ' . level_label($invoice['level'] ?? ''),
        'Annee academique: ' . ($invoice['academic_year'] ?? ''),
        'Echeance: ' . ($invoice['due_date'] ?? ''),
        'Statut: ' . status_text($invoice['status'] ?? null),
        '',
        'Resume',
        'Total: ' . money($invoice['total_amount']),
        'Paye: ' . money($invoice['paid_amount']),
        'Reste: ' . money($invoice['remaining_amount']),
        '',
        'Elements',
    ];

    foreach ($items as $item) {
        $lines[] = fee_label($item['fee_name']) . ' | ' . status_text($item['fee_type']) . ' | Montant ' . money($item['amount']) .
            ' | Paye ' . money(invoice_item_paid((int)$item['id'])) .
            ' | Reste ' . money(invoice_item_remaining((int)$item['id']));
    }

    $lines[] = '';
    $lines[] = 'Paiements';
    if (!$payments) {
        $lines[] = 'Aucun paiement.';
    }
    foreach ($payments as $payment) {
        $lines[] = ($payment['payment_reference'] ?? '') . ' | ' . fee_label($payment['fee_name'] ?? null) .
            ' | ' . money($payment['amount']) .
            ' | ' . payment_method_label($payment['payment_method'] ?? null) .
            ' | ' . status_text($payment['status'] ?? null);
    }

    send_simple_pdf($invoice['invoice_number'] . '.pdf', 'Facture ' . $invoice['invoice_number'], $lines);
}
