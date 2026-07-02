<code style="display:block; word-break:break-all; background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; padding:.6rem .75rem; font-size:.85rem;">{{ $secret }}</code>
<div style="margin-top:.75rem; font-size:.8rem; color:#6b7280;">
    {{ __('pages/webhooks.signature_hint') }}
    <code style="font-size:.8rem;">X-Webhook-Signature: sha256=HMAC_SHA256(body, secret)</code>
</div>
