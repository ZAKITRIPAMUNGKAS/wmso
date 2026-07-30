<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class ReportExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, WithCustomStartCell, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $typeLabel;
    protected $startDate;
    protected $endDate;
    protected $companyName;

    public function __construct($data, $typeLabel, $startDate, $endDate, $companyName)
    {
        $this->data = $data;
        $this->typeLabel = $typeLabel;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->companyName = $companyName;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Start data from row 7
     */
    public function startCell(): string
    {
        return 'A7';
    }

    /**
     * Header columns
     */
    public function headings(): array
    {
        if ($this->data->isEmpty()) return [];
        return array_keys((array) $this->data->first());
    }

    /**
     * Mapping data to Excel format
     */
    public function map($row): array
    {
        $row = (array) $row;
        $keys = array_keys($row);
        
        return [
            Date::dateTimeToExcel(Carbon::parse($row['Tanggal'])),
            $row['No_Referensi'],
            $row[$keys[2]], // Dynamic: Supplier/Customer/Gudang
            $row['Produk'],
            (int) $row['Qty'],
            $row['Satuan'],
            (float) $row['Harga_Satuan'],
            (float) $row['Total'],
        ];
    }

    /**
     * Formatting columns (Currency & Date)
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => '"Rp "#,##0',
            'H' => '"Rp "#,##0',
        ];
    }

    /**
     * Header styles & Freeze Panes
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A8');

        return [
            7 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Indigo-600
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    /**
     * Custom Events for Metadata & Summary
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // 1. Company Meta Header
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', strtoupper($this->companyName));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['size' => 16, 'bold' => true, 'color' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
                ]);
                
                $sheet->mergeCells('A2:H2');
                $sheet->setCellValue('A2', "LAPORAN " . strtoupper($this->typeLabel));
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 14, 'bold' => true, 'color' => ['rgb' => '4F46E5']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
                ]);
                
                $sheet->setCellValue('A3', "Periode:");
                $sheet->setCellValue('B3', "{$this->startDate} s/d {$this->endDate}");
                $sheet->getStyle('A3')->getFont()->setBold(true);
                
                $sheet->setCellValue('A4', "Dicetak:");
                $sheet->setCellValue('B4', now()->format('d M Y H:i'));
                $sheet->getStyle('A4')->getFont()->setBold(true);

                // 2. Summary Row
                $lastRow = $sheet->getHighestRow();
                $summaryRow = $lastRow + 1;
                
                if ($lastRow >= 8) { // Only if there is data
                    $sheet->setCellValue("D{$summaryRow}", "TOTAL");
                    $sheet->getStyle("D{$summaryRow}")->getFont()->setBold(true);
                    
                    $sheet->setCellValue("E{$summaryRow}", "=SUM(E8:E{$lastRow})");
                    $sheet->setCellValue("H{$summaryRow}", "=SUM(H8:H{$lastRow})");
                    
                    $sheet->getStyle("A{$summaryRow}:H{$summaryRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFC']
                        ]
                    ]);
                    
                    $sheet->getStyle("H{$summaryRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0');
                    
                    // 3. Borders for entire table
                    $sheet->getStyle("A7:H{$summaryRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                }
                
                // 4. Alignment for data rows
                $sheet->getStyle("A8:A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E8:E{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G8:H{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
