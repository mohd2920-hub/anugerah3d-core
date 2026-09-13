<x-mail::message>
# Welcome to Anugerah3D

Hi {{ $staffName }},

Your Superadmin has invited you to the admin platform. Set your password to access the pages assigned to you.

<x-mail::button :url="$invitationUrl">Set your password</x-mail::button>

This invitation expires in 48 hours and can be used once. If it expires, ask your Superadmin for a new invitation.

Anugerah3D
</x-mail::message>
