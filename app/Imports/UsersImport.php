<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;

class UsersImport implements SkipsEmptyRows, ToModel, WithChunkReading, WithLimit, WithStartRow
{
    public function model(array $row)
    {
        //
    }

    public function startRow(): int
    {
        return 2;
    }

    public function limit(): int
    {
        return 200;
    }

    /* public function headingRow(): int
    {
        return 8;
    } */

    public function batchSize(): int
    {
        return 10;
    }

    public function chunkSize(): int
    {
        return 10;
    }
}
