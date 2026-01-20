<x-mail::message>
# <span style="color: {{ $vehicleRequisition->approval_status == 1 ? 'green' : 'red' }};">Vehicle requisition {!! $vehicleRequisition->status !!} (<a href="{{ route('vms.vehicle-requisitions.show', ['vehicle_requisition'=> $vehicleRequisition->id]) }}" target="_blank">{{ $vehicleRequisition->requisition_no }}</a>)</span>

Your booking request has been <span style="color: {{ $vehicleRequisition->approval_status == 1 ? 'green' : 'red' }};">{!! $vehicleRequisition->status !!}</span> by the admin. Below are the details of the booking:

<table class="tables" style="margin-top: 30px;">
    <tr>
        <td style="padding-bottom: 8px;"><b>Requisition For</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->requisition_for->name_with_code }}</td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Vehicle</b></td>
        <td style="padding-bottom: 8px;">:
            @if ($vehicleRequisition->any_vehicle && !$vehicleRequisition->vehicle_id )
                Any Vehicle
            @else
                {{ $vehicleRequisition->vehicle->name }} {{ $vehicleRequisition->vehicle->license_plate ? "({$vehicleRequisition->vehicle->license_plate})" : '' }}
            @endif
        </td>
    </tr>
    @if ($vehicleRequisition->vehicle_driver)
    <tr>
        <td style="padding-bottom: 8px;"><b>Assigned Driver</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->vehicle_driver->name . ($vehicleRequisition->vehicle_driver->phone ? " ({$vehicleRequisition->vehicle_driver->phone})" : '') }} </td>
    </tr>
    @endif
    <tr>
        <td style="padding-bottom: 8px;"><b>From Date</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->from_datetime->format('Y-m-d h:i a') }}</td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>To Date</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->to_datetime->format('Y-m-d h:i a') }}</td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Pickup Location</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->from_location }}</td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Destination</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->to_location }}</td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Details</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->details ?? 'n/a' }}</td>
    </tr>
    @if ($vehicleRequisition->approval_status != 0)
    <tr>
        <td style="vertical-align: left; border-bottom: 1px solid #c3cfe0; text-align: left; padding-top: 15px;" colspan="2"><b>Approval</b></td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Status</b></td>
        <td style="padding-bottom: 8px;">: <span style="color: {{ $vehicleRequisition->approval_status == 1 ? 'green' : 'red' }};">{!! $vehicleRequisition->status !!}</span></td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Approval Date</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->approval_ts->format('Y-m-d h:i a') }}</td>
    </tr>
    <tr>
        <td style="padding-bottom: 8px;"><b>Approval comments</b></td>
        <td style="padding-bottom: 8px;">: {{ $vehicleRequisition->approval_comment ?? 'n/a' }}</td>
    </tr>
    @endif
</table>

<div class="" style="margin-top: 30px;">
    Thanks,<br>
    {{ config('app.name') }}
</div>
</x-mail::message>
