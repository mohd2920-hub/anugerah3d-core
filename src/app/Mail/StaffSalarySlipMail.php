<?php

namespace App\Mail;

use App\Models\SalaryPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffSalarySlipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SalaryPayment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Slip Gaji Anugerah3D · '.$this->payment->slipNumber());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-salary-slip', with: $this->slipData());
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => view('emails.staff-salary-slip', $this->slipData())->render(), $this->payment->slipNumber().'.html')->withMime('text/html')];
    }

    private function slipData(): array
    {
        $date = $this->payment->paid_date->toImmutable();
        $base = SalaryPayment::where('recipient_key', $this->payment->recipient_key)->whereDate('paid_date', '<=', $date->toDateString())->where('id', '<=', $this->payment->id)->orderBy('paid_date')->orderBy('id');

        return ['payment' => $this->payment, 'periods' => [
            'Slip Harian' => collect([$this->payment]),
            'Ringkasan Mingguan · '.$date->startOfWeek()->format('d/m/Y').' hingga '.$date->format('d/m/Y') => (clone $base)->whereDate('paid_date', '>=', $date->startOfWeek()->toDateString())->get(),
            'Ringkasan Bulanan · '.$date->format('m/Y').' setakat '.$date->format('d/m/Y') => (clone $base)->whereDate('paid_date', '>=', $date->startOfMonth()->toDateString())->get(),
        ]];
    }
}
