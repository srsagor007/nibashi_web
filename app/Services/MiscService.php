<?php

namespace App\Services;

use Carbon\Carbon;
use DB;

class MiscService
{
    public function get_total_working_days($date_range = null, $month = null)
    {
        if ($date_range) {
            [$start, $end] = array_map(function ($d) {
                return Carbon::parse(trim($d))->format('Y-m-d');
            }, explode('to', $date_range));

            $start_with_format = Carbon::createFromFormat('Y-m-d', $start)->startOfMonth();
            $end_with_format = Carbon::createFromFormat('Y-m-d', $end)->startOfMonth();
            $months = collect()
                ->merge(
                    collect([$start_with_format, $end_with_format])
                        ->flatMap(function () use ($start_with_format, $end_with_format) {
                            $dates = [];
                            $current_date = Carbon::parse($start_with_format);
                            $end_date = Carbon::parse($end_with_format);

                            while ($current_date <= $end_date) {
                                $dates[] = $current_date->format('m-Y');
                                $current_date->addMonth();
                            }

                            return $dates;
                        })
                )
                ->unique()
                ->values();

        } elseif ($month) {
            [$m, $y] = explode('-', $month);
            $start = Carbon::createFromDate($y, $m, 1)->startOfMonth();
            $end = Carbon::createFromDate($y, $m, 1)->endOfMonth();

            $months = collect()
                ->merge(
                    collect([$start, $end])
                        ->flatMap(function () use ($start, $end) {
                            $dates = [];
                            $current_date = Carbon::parse($start);
                            $end_date = Carbon::parse($end);

                            while ($current_date <= $end_date) {
                                $dates[] = $current_date->format('m-Y');
                                $current_date->addMonth();
                            }

                            return $dates;
                        })
                )
                ->unique()
                ->values();
        }

        $total_working_days = 0;
        $total_working_hours = 0;
        $full_day_hours = 8;
        $half_day_hours = 4;

        $between_date_value = [];
        foreach ($months as $month) {
            $days_record = DB::table('tbl_working_days')->where('month_year', $month)->first();
            if ($days_record && isset($days_record->days_value)) {
                $days_values = explode(',', $days_record->days_value);

                foreach ($days_values as $index => $value) {
                    $day = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

                    [$month_num, $year] = explode('-', $month);
                    $formatted_date = "{$year}-{$month_num}-{$day}";

                    try {
                        $date = Carbon::createFromFormat('Y-m-d', $formatted_date);
                    } catch (\Throwable $th) {
                        continue;
                    }

                    $start_date = Carbon::createFromFormat('Y-m-d', $start);
                    $end_date = Carbon::createFromFormat('Y-m-d', $end);

                    if ($date->between($start_date, $end_date)) {
                        array_push($between_date_value, $value);

                        // $total_working_days +=  $between_date === $start_day ? $value : 0;
                        // $total_working_hours += $value == 1.0 ? $full_day_hours : ($value == 0.5 ? $half_day_hours : $value * $full_day_hours);
                        // continue;
                    }
                }

                $values = collect($between_date_value)->map(function ($v) use ($full_day_hours, $half_day_hours) {
                    $v = (float) $v;

                    return match (true) {
                        $v === 1.0 => $full_day_hours,
                        $v === 0.5 => $half_day_hours,
                        $v > 0 => $v * $full_day_hours,
                        default => 0
                    };
                });

                $total_working_days = $values->filter(fn ($hours) => $hours > 0)->count();
                $total_working_hours = $values->sum();
            } else {
                [$year, $month_num] = explode('-', $month);
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);

                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date = Carbon::createFromDate($year, $month_num, $day);

                    // Only include dates in the range
                    if ($date->lt($start) || $date->gt($end)) {
                        continue;
                    }

                    $value = 1; // Default working day value

                    if ($day == $days_in_month) {
                        $value = 0.0; // Optional: treat last day differently
                    }

                    if ($value >= 0.5) {
                        $total_working_days += $value;
                        $total_working_hours += $value == 1.0 ? $full_day_hours : $half_day_hours;
                    }
                }
            }
        }

        return [
            'total_working_days' => $total_working_days,
            'total_working_hours' => $total_working_hours,
        ];

    }

    public function get_rx_data($pso_codes, $type, $date_range, $business_code = null)
    {
        [$start, $end] = array_map(function ($d) {
            return Carbon::parse(trim($d))->format('Y-m-d');
        }, explode('to', $date_range));

        $query = DB::table('tbl_rx as r')
            ->join('tbl_rx_brand as b', 'b.tbl_rx_id', '=', 'r.id')
            ->whereIn('r.submitted_by', $pso_codes)
            ->whereBetween(DB::raw('DATE_FORMAT(r.submitted_on, "%Y-%m-%d")'), [$start, $end])
            ->where('r.status', 1);

        if ($business_code) {
            $query->where('r.tbl_business_business_code', $business_code);
        }

        if ($type == 'rx') {
            return $query
                ->select(
                    'r.submitted_by',
                    DB::raw('SUM(b.total_brand_rx) as total_rx'),
                    'b.tbl_product_group_id as pg_id'
                )
                ->groupBy('r.submitted_by', 'b.tbl_product_group_id')
                ->get();
        } elseif ($type == 'brand') {
            return $query
                ->select(
                    'r.submitted_by',
                    DB::raw('COUNT(DISTINCT b.tbl_product_group_id) as total_brands')
                )
                ->groupBy('r.submitted_by')
                ->get();
        } else {
            $query->get();
        }
    }
}
