<x-mail::message>
@if ($stage === 'completed')
# Your workspace has been deleted

The workspace **{{ $workspaceName }}** and all of its data have now been permanently deleted, as requested.

This is irreversible. If you'd like to use the service again, you're welcome to create a new workspace at any time.
@else
# Deletion scheduled

We've received a request to delete the workspace **{{ $workspaceName }}**.

Your subscription has been cancelled and data syncing has stopped. The workspace and all of its data will be **permanently deleted on {{ $purgeAt?->format('F j, Y') }}**.

**Changed your mind?** Sign in and open **Company → Danger zone → Cancel deletion** any time before that date to keep your workspace.
@endif

If you didn't request this, please contact support immediately.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
