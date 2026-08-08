<?php
/**
 * Generates a sample BioTime/ZKTeco-style attendance Excel file
 * for August 2026 (Aug 1-8, Mon-Sat schedule).
 *
 * Usage: php database/generate_attendance_sample.php
 * Output: database/sample_attendance_aug2026.xlsx
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Working days Aug 2026 (Mon-Sat, skip Sunday Aug 2)
$workDays = [
    '2026-08-01', // Sat
    '2026-08-03', // Mon
    '2026-08-04', // Tue
    '2026-08-05', // Wed
    '2026-08-06', // Thu
    '2026-08-07', // Fri
    '2026-08-08', // Sat
];

// Employees: [name, code, group, ot_days_with_checkout]
// OT = check-out after 17:00/18:00 depending on group
$employees = [
    ['Karim Nassar',     'BYB001', 'G1', [2, 4, 6]],  // OT on Mon/Wed/Fri
    ['Lina Khoury',      'BYB002', 'G2', [1, 5]],     // OT on Sat/Wed
    ['Georges Saade',    'BYB003', 'G1', [1, 3, 7]],  // OT on Sat/Tue/Sat
    ['Nadia Rahhal',     'BYB004', 'G3', []],          // No OT
    ['Elie Gemayel',     'BYB005', 'G1', [2, 6]],     // OT on Mon/Fri
    ['Maya Haddad',      'BYB006', 'G2', [4]],         // OT on Wed
    ['Charbel Abi Nader','BYB007', 'G1', [3, 5, 7]],  // OT on Tue/Thu/Sat
];

// Normal check-out times (no OT) — 17:00 or 18:00 with slight variation
// OT check-out: 1-3 blocks after OT start (OT start G1=17:00, G2=18:00, G3=17:30)
function normalOut(string $group): string {
    return match($group) {
        'G1' => '17:05',
        'G2' => '18:03',
        'G3' => '17:32',
        default => '17:00',
    };
}

function otOut(string $group, int $seed): string {
    // 1-3 blocks of OT (each 90 min), OT start + 90*blocks + some minutes
    $blocks = ($seed % 3) + 1;
    $startMin = match($group) {
        'G1' => 17 * 60,
        'G2' => 18 * 60,
        'G3' => 17 * 60 + 30,
        default => 17 * 60,
    };
    $endMin = $startMin + ($blocks * 90) + ($seed % 15);
    $h = intdiv($endMin, 60);
    $m = $endMin % 60;
    return sprintf('%02d:%02d', $h, $m);
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance');

// Title row (common in BioTime exports)
$sheet->setCellValue('A1', 'Byblos Printing SAL — Attendance Report August 2026');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header row (row 2) — matches detectHeader() aliases
$headers = ['No.', 'Employee Name', 'Employee Code', 'Date', 'Check In', 'Check Out'];
foreach ($headers as $i => $h) {
    $col = chr(65 + $i); // A, B, C, ...
    $sheet->setCellValue($col . '2', $h);
}
$sheet->getStyle('A2:F2')->getFont()->setBold(true);
$sheet->getStyle('A2:F2')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF5B8DB8');
$sheet->getStyle('A2:F2')->getFont()->getColor()->setARGB('FFFFFFFF');

// Data rows
$row = 3;
$seq = 1;
foreach ($employees as $empIdx => [$name, $code, $group, $otDayIndices]) {
    foreach ($workDays as $dayIdx => $date) {
        $isOt     = in_array($dayIdx + 1, $otDayIndices, true);
        $checkIn  = '08:' . str_pad((string)(($empIdx * 3 + $dayIdx * 2) % 12), 2, '0', STR_PAD_LEFT);
        $checkOut = $isOt ? otOut($group, $empIdx + $dayIdx) : normalOut($group);

        $sheet->setCellValue('A' . $row, $seq);
        $sheet->setCellValue('B' . $row, $name);
        $sheet->setCellValue('C' . $row, $code);
        $sheet->setCellValue('D' . $row, $date);
        $sheet->setCellValue('E' . $row, $checkIn);
        $sheet->setCellValue('F' . $row, $checkOut);

        if ($row % 2 === 0) {
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE8F0F7');
        }

        $row++;
        $seq++;
    }
}

// Auto-size columns
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$outputPath = __DIR__ . '/sample_attendance_aug2026.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);

echo "Generated: " . $outputPath . PHP_EOL;
echo "Rows: " . ($seq - 1) . " attendance records (" . count($employees) . " employees × " . count($workDays) . " days)" . PHP_EOL;
