<?php

namespace App\Mail;

use App\Models\DailyTransactionEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyTransactionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DailyTransactionEmail $emailRecord,
        public string $companyName = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Transaction Report - ' . $this->emailRecord->business_date->format('Y-m-d'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-transaction-report',
            with: [
                'emailRecord' => $this->emailRecord,
                'companyName' => $this->companyName,
                'salesTotal' => number_format($this->emailRecord->sales_total, 2),
                'expensesTotal' => number_format($this->emailRecord->expenses_total, 2),
                'cashShortage' => number_format($this->emailRecord->cash_shortage, 2),
                'timestamp' => $this->emailRecord->sent_at->format('Y-m-d H:i:s'),
                'businessDate' => $this->emailRecord->business_date->format('Y-m-d'),
                'sentBy' => $this->emailRecord->sentBy?->name ?? 'System (Auto)',
                'sendType' => ucfirst($this->emailRecord->type),
            ],
        );
    }
}
