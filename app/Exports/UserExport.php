<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection, WithColumnWidths, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Renata ID',
            'User Code',
            'Business Code',
            'Designation',
            'Supervisor',
            'Phone',
            'Role',
            'RSM Region',
            'Status',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Name
            'B' => 15, // Renata ID
            'C' => 15, // User Code
            'D' => 20, // Business Code
            'E' => 20, // Designation
            'F' => 25, // Supervisor
            'G' => 15, // Phone
            'H' => 20, // Role
            'I' => 20, // RSM Region
            'J' => 12, // Status
        ];
    }
}
