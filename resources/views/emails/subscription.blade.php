@component('mail::message')
# Hello {{ $userName }}

{{ $message }}

---

@component('mail::panel')
This is an automated message from your subscription management system. Please do not reply to this email.
@endcomponent

If you have any questions, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 