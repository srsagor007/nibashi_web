<?php

namespace App\Exports;

use App\Models\Retailer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RetailersExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return Retailer::query()
            ->with([
                'businessUnit:id,name',
                'customerChannel:id,name',
                'distributor:id,zone_id,region_id,area_id,territory_id,depot_id,customer',
                'retailerCategory:id,name',
                'retailerType:id,name',
                'distributor.zone',
                'distributor.region',
                'distributor.area',
                'distributor.territory',
                'distributor.depot',
            ])
            ->when($this->request->zone_id, fn($q, $zoneId) =>
                $q->whereHas('distributor', fn($d) => $d->where('zone_id', $zoneId))
            )
            ->when($this->request->region_id, fn($q, $regionId) =>
                $q->whereHas('distributor', fn($d) => $d->where('region_id', $regionId))
            )
            ->when($this->request->area_id, fn($q, $areaId) =>
                $q->whereHas('distributor', fn($d) => $d->where('area_id', $areaId))
            )
            ->when($this->request->territory_id, fn($q, $territoryId) =>
                $q->whereHas('distributor', fn($d) => $d->where('territory_id', $territoryId))
            )
            ->when($this->request->distributor_id, fn($q, $distributorId) =>
                $q->whereHas('distributor', fn($d) => $d->where('distributor_customer_id', $distributorId))
            )
            ->when($this->request->outlet_id, fn($q, $outletId) =>
                $q->whereHas('distributor', fn($d) => $d->where('id', $outletId))
            )
            ->get()
            ->map(function ($retailer) {
                return [
                    'Business Unit'        => optional($retailer->businessUnit)->name,
                    'Customer Channel'     => optional($retailer->customerChannel)->name,
                    'Distributor'          => optional($retailer->distributor)->customer,
                    'Outlet Address'          => optional($retailer->distributor)->address,
                    'Route'                => $retailer->route,
                    'Shop Name'            => $retailer->shop_name,
                    'Owner Name'           => $retailer->owner_name,
                    'Phone Number'         => $retailer->phone_number,
                    'Other Phone'          => $retailer->phone_number_other,
                    'Email'                => $retailer->email,
                    'Retailer Category'    => optional($retailer->retailerCategory)->name,
                    'Retailer Type'        => optional($retailer->retailerType)->name,
                    'Retailing Service'    => $retailer->retailing_service_text, // accessor
                    'Address'              => $retailer->address,
                    'Latitude'             => $retailer->lat,
                    'Longitude'            => $retailer->lon,
                    'Shop Sign Required'   => $retailer->shop_sign_required ? 'Yes' : 'No',
                    'Shop Sign Available'  => $retailer->shop_sign_available ? 'Yes' : 'No',
                    'Submitted Employee'   => $retailer->submited_employee_id, // you can join User name if needed
                    'Zone'                 => optional($retailer->distributor->zone)->name ?? '',
                    'Region'               => optional($retailer->distributor->region)->name ?? '',
                    'Area'                 => optional($retailer->distributor->area)->name ?? '',
                    'Territory'            => optional($retailer->distributor->territory)->name ?? '',
                    'Depot'                => optional($retailer->distributor->depot)->name ?? '',
                    'Is Active'            => $retailer->is_active ? 'Yes' : 'No',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Business Unit',
            'Customer Channel',
            'Distributor',
            'Outlet Address',
            'Route',
            'Shop Name',
            'Owner Name',
            'Phone Number',
            'Other Phone',
            'Email',
            'Retailer Category',
            'Retailer Type',
            'Retailing Service',
            'Address',
            'Latitude',
            'Longitude',
            'Shop Sign Required',
            'Shop Sign Available',
            'Submitted Employee',
            'Zone',
            'Region',
            'Area',
            'Territory',
            'Depot',
            'Is Active',
        ];
    }
}
