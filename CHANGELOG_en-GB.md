# 2.3.1

- Shopware 6.7 compatibility: the form is now located via the version-stable field names (`subject`/`comment`) instead of fixed element IDs (`form-subject`/`form-comment`). The IDs changed in 6.7, which broke both prefill and sending.
- Prefill now reacts via a `MutationObserver` exactly when the modal form appears in the DOM — no fixed time window that could expire before the modal is opened.

# 2.3.0

- Sales channel / sub-shop fix: inquiries are now sent to the correct, channel-specific URL. Previously the form action was hardcoded to `/dasform/inquiry` in JavaScript, which broke sending in sub-shops mounted under a domain/path prefix.
- The URL is now generated server-side via `path()` (including the sales-channel prefix) and passed to the storefront JS through a data attribute.
- Product name, inquiry text and subject are now passed to the form via data attributes instead of `localStorage` — reliable prefill in sub-shops as well.
- The server-side action rewrite in the contact form now uses the exact, channel-correct URL instead of a path substring replacement.
