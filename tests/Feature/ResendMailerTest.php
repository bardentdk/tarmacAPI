<?php

use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;

it('can build the Resend mailer used in production', function () {
    config(['services.resend.key' => 're_test_key']);

    expect(Mail::mailer('resend')->getSymfonyTransport())->toBeInstanceOf(ResendTransport::class);
});
