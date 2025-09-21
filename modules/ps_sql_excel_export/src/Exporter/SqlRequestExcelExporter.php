<?php

namespace PsSqlExcelExport\Exporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\SqlRequestExecutionResult;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\ValueObject\SqlRequestId;

class SqlRequestExcelExporter
{
    public function __construct() {}

    public function exportToFile(SqlRequestId $sqlRequestId, SqlRequestExecutionResult $result)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // give headers and rows
            $headers = $result->getColumns();
            $rows = $result->getRows();

            // writing headers in the first row
            $col = 1;
            foreach ($headers as $header) {
                $cell = Coordinate::stringFromColumnIndex($col) . '1';
                $sheet->setCellValue($cell, $header);
                $col++;
            }

            // writing data rows starting from the second row
            $rowIndex = 2;
            foreach ($rows as $row) {
                $col = 1;
                foreach ($row as $value) {
                    $cell = Coordinate::stringFromColumnIndex($col) . $rowIndex;
                    $sheet->setCellValue($cell, $value);
                    $col++;
                }
                $rowIndex++;
            }

            // save to a temporary file
            $filename = sprintf('sql_request_%d.xlsx', $sqlRequestId->getValue());
            $tempFile = tempnam(sys_get_temp_dir(), 'ps_sql_excel');
            if ($tempFile === false) {
                throw new \Exception('Cannot create temporary file');
            }
            $filePath = $tempFile . '.xlsx';

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return new \SplFileInfo($filePath);
        } catch (\Exception $e) {
            throw new \Exception('Error exporting to Excel: ' . $e->getMessage());
        }
    }
}
