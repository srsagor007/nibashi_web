<?php

namespace App\Mail;

use App\Models\VehicleRequisition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VehicleRequisitionMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public VehicleRequisition $vehicleRequisition)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vehicle requisition request: ' . "#{$this->vehicleRequisition->requisition_no}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.vehicle_requisitions.vehicle_requisition',
            with: [
                'vehicleRequisition' => $this->vehicleRequisition,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
