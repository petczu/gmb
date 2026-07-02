<div class="space-y-4">
    @if ($review)
        <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
            <div class="mb-1 flex items-center justify-between">
                <span class="font-semibold">{{ $review->author_name ?? 'Anonymous' }}</span>
                <span class="text-warning-500">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</span>
            </div>
            <p class="text-gray-600 dark:text-gray-300">{{ $review->text }}</p>
        </div>
    @endif

    <div class="rounded-lg bg-primary-50 p-3 text-sm dark:bg-primary-950/40">
        <div class="mb-1 font-semibold text-primary-700 dark:text-primary-300">Agent reply</div>
        <p class="whitespace-pre-line text-gray-800 dark:text-gray-100">{{ $reply }}</p>
    </div>
</div>
