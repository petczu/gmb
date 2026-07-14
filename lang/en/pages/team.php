<?php

declare(strict_types=1);

return [
    'col_name' => 'Name',
    'col_email' => 'Email',
    'col_role' => 'Role',

    'edit' => 'Edit',
    'location_access' => 'Location access',
    'location_access_helper' => 'Leave empty for access to all locations.',
    'guest_location_helper' => 'Leave empty to notify about all locations, or pick specific ones.',

    'change_role' => 'Change role',
    'remove' => 'Remove',
    'add_member' => 'Add member',
    'add_member_email_helper' => 'We will email them an invitation to join this workspace.',
    'add_guest' => 'Add guest',
    'add_guest_helper' => 'A guest only receives the notifications you route to them. No login, no access to the workspace.',
    'guest_language' => 'Language',
    'guest_language_helper' => 'Notifications and reports for this contact are sent in this language.',
    'name' => 'Name',

    // Notifications
    'member_updated' => 'Member updated',
    'role_updated' => 'Role updated to :role',
    'member_removed' => 'Member removed',
    'invitation_sent' => 'Invitation sent',
    'guest_added' => 'Guest added',

    // Pending invitations
    'pending_hint' => 'Sent but not accepted yet. Resend the email or revoke the invitation if it went to the wrong address.',
    'invite_resend' => 'Resend',
    'invite_revoke' => 'Revoke',
    'invite_revoke_desc' => 'The invitation link stops working immediately. The person is not notified.',
    'invite_revoked' => 'Invitation revoked',
    'col_status' => 'Status',
    'status_active' => 'Active',
    'status_pending' => 'Pending',
];
