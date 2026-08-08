<?php

declare(strict_types=1);

namespace App\Modules\Attendance;

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Parses BioTime/ZKTeco Excel attendance exports and writes:
 *  - attendance_records  (clock-in / clock-out per employee-day)
 *  - ot_calculations     (OT blocks, amounts)
 *  - attendance_imports  (import log)
 */
final class AttendanceImportRepository
{
    public function __construct(private Database $database)
    {
    }

    // ------------------------------------------------------------------
    // Company / employee helpers
    // ------------------------------------------------------------------

    public function companyOptions(): array
    {
        return $this->database->fetchAll(
            "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC"
        );
    }

    public function recentImports(int $companyId, int $limit = 20): array
    {
        return $this->database->fetchAll(
            'SELECT ai.*, u.email AS imported_by_email
             FROM attendance_imports ai
             LEFT JOIN users u ON u.id = ai.imported_by
             WHERE ai.company_id = :cid
             ORDER BY ai.created_at DESC
             LIMIT ' . $limit,
            ['cid' => $companyId]
        );
    }

    /** Load all active employees for a company, keyed by lower-case full name and by employee_code */
    private function employeeIndex(int $companyId): array
    {
        $rows = $this->database->fetchAll(
            "SELECT id, employee_code,
                    LOWER(CONCAT(TRIM(first_name), ' ', TRIM(last_name))) AS full_name_lc,
                    LOWER(TRIM(first_name)) AS first_lc
             FROM employees
             WHERE company_id = :cid AND employee_status NOT IN ('archived','terminated','resigned')",
            ['cid' => $companyId]
        );

        $index = [];
        foreach ($rows as $r) {
            $index['name:' . $r['full_name_lc']] = (int) $r['id'];
            if ($r['employee_code']) {
                $index['code:' . strtolower(trim((string) $r['employee_code']))] = (int) $r['id'];
            }
        }
        return $index;
    }

    private function resolveEmployee(array $index, string $rawName, string $rawCode): ?int
    {
        $code = strtolower(trim($rawCode));
        $name = strtolower(trim($rawName));

        if ($code && isset($index['code:' . $code])) return $index['code:' . $code];
        if ($name && isset($index['name:' . $name])) return $index['name:' . $name];

        // Partial name match: try first-word match as a fallback
        $firstName = explode(' ', $name)[0] ?? '';
        foreach ($index as $key => $id) {
            if (str_starts_with($key, 'name:') && str_contains($key, $firstName) && $firstName !== '') {
                return $id;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Excel parsing — auto-detect BioTime format
    // ------------------------------------------------------------------

    /**
     * Returns an array of normalized rows:
     *  [employee_code, employee_name, work_date (Y-m-d), clock_in (H:i:s|null), clock_out (H:i:s|null)]
     */
    public function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();

        [$headerRow, $colMap] = $this->detectHeader($sheet);
        if ($headerRow === null) {
            throw new \RuntimeException('Could not detect header row in the Excel file. Expected columns: Name/Employee Name, Date, Check In/First In, Check Out/Last Out.');
        }

        $rows   = [];
        $maxRow = $sheet->getHighestDataRow();

        for ($r = $headerRow + 1; $r <= $maxRow; $r++) {
            $rawDate = $this->cellValue($sheet, $r, $colMap['date'] ?? 0);
            if ($rawDate === '' || $rawDate === null) continue;

            $date = $this->parseDate($rawDate);
            if ($date === null) continue;

            $name    = trim((string) $this->cellValue($sheet, $r, $colMap['name'] ?? 0));
            $code    = trim((string) $this->cellValue($sheet, $r, $colMap['code'] ?? 0));
            $clockIn = $this->parseTime($this->cellValue($sheet, $r, $colMap['clock_in'] ?? 0));
            $clockOut= $this->parseTime($this->cellValue($sheet, $r, $colMap['clock_out'] ?? 0));

            if ($name === '' && $code === '') continue;

            $rows[] = [
                'employee_code' => $code,
                'employee_name' => $name,
                'work_date'     => $date,
                'clock_in'      => $clockIn,
                'clock_out'     => $clockOut,
            ];
        }

        return $rows;
    }

    private function detectHeader(Worksheet $sheet): array
    {
        $maxRow = min(15, $sheet->getHighestDataRow());
        $maxCol = min(30, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));

        $nameAliases    = ['name', 'employee name', 'emp name', 'staff name', 'full name', 'اسم الموظف'];
        $codeAliases    = ['no', 'no.', 'employee no', 'emp no', 'emp. no', 'employee no.', 'employee id', 'emp id', 'code', 'employee code'];
        $dateAliases    = ['date', 'work date', 'attendance date', 'day', 'التاريخ'];
        $inAliases      = ['check in', 'first in', 'clock in', 'in', 'time in', 'punch in', 'on duty'];
        $outAliases     = ['check out', 'last out', 'clock out', 'out', 'time out', 'punch out', 'off duty'];

        for ($r = 1; $r <= $maxRow; $r++) {
            $colMap = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $val = strtolower(trim((string) $this->cellValue($sheet, $r, $c)));
                if ($val === '') continue;
                if (!isset($colMap['name'])     && in_array($val, $nameAliases, true))  $colMap['name']     = $c;
                if (!isset($colMap['code'])     && in_array($val, $codeAliases, true))  $colMap['code']     = $c;
                if (!isset($colMap['date'])     && in_array($val, $dateAliases, true))  $colMap['date']     = $c;
                if (!isset($colMap['clock_in']) && in_array($val, $inAliases, true))    $colMap['clock_in'] = $c;
                if (!isset($colMap['clock_out'])&& in_array($val, $outAliases, true))   $colMap['clock_out']= $c;
            }
            // A valid header needs at least date + one time + one identifier
            if (isset($colMap['date'], $colMap['clock_in']) && (isset($colMap['name']) || isset($colMap['code']))) {
                return [$r, $colMap];
            }
        }
        return [null, []];
    }

    private function cellValue(Worksheet $sheet, int $row, int $col): mixed
    {
        if ($col === 0) return null;
        try {
            return $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        $str = trim((string) $raw);

        // Excel serial date (numeric)
        if (is_numeric($str)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $str);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {}
        }

        // Try various string formats
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y', 'j M Y', 'j F Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $str);
            if ($dt !== false) return $dt->format('Y-m-d');
        }

        // strtotime fallback
        $ts = strtotime($str);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function parseTime(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        $str = trim((string) $raw);
        if ($str === '' || $str === '--' || strtolower($str) === 'absent') return null;

        // Excel fractional day
        if (is_numeric($str) && (float) $str < 1) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $str);
                return $dt->format('H:i:s');
            } catch (\Throwable) {}
        }

        // HH:MM or HH:MM:SS
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $m)) {
            return sprintf('%02d:%02d:%02d', $m[1], $m[2], $m[3] ?? '00');
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Import: persist attendance records
    // ------------------------------------------------------------------

    /**
     * Runs the full import pipeline:
     *  1. Parse Excel
     *  2. Match employees
     *  3. Upsert attendance_records
     *  4. Calculate OT
     *  5. Log in attendance_imports
     *
     * Returns summary array.
     */
    public function import(string $filePath, string $originalName, int $companyId, int $month, int $year, int $importedBy): array
    {
        $rows  = $this->parseExcel($filePath);
        $index = $this->employeeIndex($companyId);

        $matched = 0; $unmatched = 0; $written = 0;
        $unmatchedNames = [];

        foreach ($rows as $row) {
            $empId = $this->resolveEmployee($index, $row['employee_name'], $row['employee_code']);
            if ($empId === null) {
                $unmatched++;
                $unmatchedNames[] = $row['employee_name'] ?: $row['employee_code'];
                continue;
            }
            $matched++;

            if ($row['clock_in'] === null && $row['clock_out'] === null) continue;

            // Upsert attendance_records
            $existing = $this->database->fetch(
                'SELECT id FROM attendance_records WHERE employee_id = :eid AND DATE(clock_in_time) = :d',
                ['eid' => $empId, 'd' => $row['work_date']]
            );

            $clockIn  = $row['work_date'] . ' ' . ($row['clock_in'] ?? '00:00:00');
            $clockOut = $row['clock_out'] ? $row['work_date'] . ' ' . $row['clock_out'] : null;

            if ($existing) {
                $this->database->execute(
                    'UPDATE attendance_records SET clock_in_time = :ci, clock_out_time = :co, source = \'import\' WHERE id = :id',
                    ['ci' => $clockIn, 'co' => $clockOut, 'id' => $existing['id']]
                );
            } else {
                $this->database->execute(
                    'INSERT INTO attendance_records (employee_id, clock_in_time, clock_out_time, source)
                     VALUES (:eid, :ci, :co, \'import\')',
                    ['eid' => $empId, 'ci' => $clockIn, 'co' => $clockOut]
                );
            }
            $written++;
        }

        // Log import
        $this->database->execute(
            'INSERT INTO attendance_imports (company_id, import_month, import_year, filename, row_count, matched, unmatched, imported_by)
             VALUES (:cid, :m, :y, :fn, :rc, :matched, :unmatched, :uid)',
            [
                'cid'       => $companyId,
                'm'         => $month,
                'y'         => $year,
                'fn'        => $originalName,
                'rc'        => count($rows),
                'matched'   => $matched,
                'unmatched' => $unmatched,
                'uid'       => $importedBy,
            ]
        );
        $importId = (int) $this->database->lastInsertId();

        // Run OT calculation for the period
        [$otRows, $otTotal] = $this->calculateOtForPeriod($companyId, $month, $year, $importId);

        return [
            'total'          => count($rows),
            'matched'        => $matched,
            'unmatched'      => $unmatched,
            'written'        => $written,
            'ot_rows'        => $otRows,
            'ot_total'       => $otTotal,
            'unmatched_names'=> array_unique($unmatchedNames),
            'import_id'      => $importId,
        ];
    }

    // ------------------------------------------------------------------
    // OT Calculation engine
    // ------------------------------------------------------------------

    /**
     * For every attendance record in the given period:
     *  - Get the employee's OT group
     *  - Compute OT minutes = clock_out − ot_start_time
     *  - Apply block rules (min 30 min to qualify, 90-min blocks, $8/block)
     *  - Upsert ot_calculations
     */
    public function calculateOtForPeriod(int $companyId, int $month, int $year, ?int $importId = null): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        $records = $this->database->fetchAll(
            'SELECT ar.id, ar.employee_id, ar.clock_in_time AS clock_in, ar.clock_out_time AS clock_out,
                    e.ot_group_id,
                    og.ot_start_hour, og.ot_start_minute,
                    og.amount_per_block, og.block_minutes, og.min_ot_minutes
             FROM attendance_records ar
             JOIN employees e ON e.id = ar.employee_id
             LEFT JOIN ot_groups og ON og.id = e.ot_group_id
             WHERE e.company_id = :cid
               AND ar.clock_in_time >= :start
               AND ar.clock_in_time < :end_after
               AND ar.clock_out_time IS NOT NULL',
            ['cid' => $companyId, 'start' => $startDate . ' 00:00:00', 'end_after' => $endDate . ' 23:59:59']
        );

        $otRows = 0; $otTotal = 0.0;

        foreach ($records as $rec) {
            if ($rec['ot_group_id'] === null) continue;

            $workDate = substr((string) $rec['clock_in'], 0, 10);

            // OT start = work_date + ot_start_hour:ot_start_minute
            $otStartTs = mktime(
                (int) $rec['ot_start_hour'],
                (int) $rec['ot_start_minute'],
                0,
                (int) substr($workDate, 5, 2),
                (int) substr($workDate, 8, 2),
                (int) substr($workDate, 0, 4)
            );
            $clockOutTs = strtotime((string) $rec['clock_out']);

            $rawOtSeconds = $clockOutTs - $otStartTs;
            if ($rawOtSeconds <= 0) continue;

            $rawOtMinutes = (int) floor($rawOtSeconds / 60);
            $minOt        = (int) $rec['min_ot_minutes'];
            $blockMin     = (int) $rec['block_minutes'];

            if ($rawOtMinutes < $minOt) continue;

            $completeBlocks = (int) floor($rawOtMinutes / $blockMin);
            if ($completeBlocks === 0) continue;

            $amount    = round($completeBlocks * (float) $rec['amount_per_block'], 2);
            $isNightOt = (int) ($clockOutTs > mktime(0, 0, 0,
                (int) substr($workDate, 5, 2),
                (int) substr($workDate, 8, 2) + 1,
                (int) substr($workDate, 0, 4)
            ));

            $this->database->execute(
                'INSERT INTO ot_calculations
                    (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot, import_id)
                 VALUES (:eid, :wd, :gid, :rom, :cb, :oa, :night, :iid)
                 ON DUPLICATE KEY UPDATE
                    ot_group_id = VALUES(ot_group_id),
                    raw_ot_minutes = VALUES(raw_ot_minutes),
                    complete_blocks = VALUES(complete_blocks),
                    ot_amount = VALUES(ot_amount),
                    is_night_ot = VALUES(is_night_ot),
                    import_id = VALUES(import_id)',
                [
                    'eid'   => $rec['employee_id'],
                    'wd'    => $workDate,
                    'gid'   => $rec['ot_group_id'],
                    'rom'   => $rawOtMinutes,
                    'cb'    => $completeBlocks,
                    'oa'    => $amount,
                    'night' => $isNightOt,
                    'iid'   => $importId,
                ]
            );
            $otRows++;
            $otTotal += $amount;
        }

        return [$otRows, round($otTotal, 2)];
    }

    /** OT summary for a payroll run — total OT per employee for the period */
    public function otSummaryForPeriod(int $companyId, int $month, int $year): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        return $this->database->fetchAll(
            'SELECT oc.employee_id,
                    SUM(oc.complete_blocks) AS total_blocks,
                    SUM(oc.ot_amount)       AS total_ot_amount,
                    SUM(oc.is_night_ot)     AS night_ot_days
             FROM ot_calculations oc
             JOIN employees e ON e.id = oc.employee_id
             WHERE e.company_id = :cid
               AND oc.work_date >= :start
               AND oc.work_date <= :end
             GROUP BY oc.employee_id',
            ['cid' => $companyId, 'start' => $startDate, 'end' => $endDate]
        );
    }

    /** OT detail per employee for the period */
    public function otDetailForEmployee(int $employeeId, int $month, int $year): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        return $this->database->fetchAll(
            'SELECT oc.*, og.name AS group_name, og.block_minutes, og.amount_per_block
             FROM ot_calculations oc
             LEFT JOIN ot_groups og ON og.id = oc.ot_group_id
             WHERE oc.employee_id = :eid
               AND oc.work_date >= :start AND oc.work_date <= :end
             ORDER BY oc.work_date ASC',
            ['eid' => $employeeId, 'start' => $startDate, 'end' => $endDate]
        );
    }
}
