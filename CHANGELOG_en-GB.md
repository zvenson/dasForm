# 2.4.4

- New setting **"Corner radius of the buttons"** (configuration card "Buttons"). Leaving it empty still means the theme decides the radius, which stays the normal case. It is only needed when a theme rounds its buy button through a selector that does not match our buttons (e.g. `button.btn-buy` in a custom.css, while our buttons are `<a>` elements). The value is applied inline and therefore beats any stylesheet rule; plain lengths such as `20px`, `0.5rem` or `50rem` are accepted.

# 2.4.3

- Follow-up to 2.4.2: the buttons now carry `btn btn-primary btn-buy`, exactly the classes of the add-to-cart button. With `btn` alone they inherited the theme's *global* button radius and turned square in themes with rounded buy buttons, where the rounding lives on the more specific class.
- **Height fixed**: the buttons are now exactly as tall as "Add to shopping cart". In Shopware 6.7 the button height lives entirely in `$btn-line-height` (2.125rem = 34px, plus 2×2px padding and 2×1px border = 40px) — the padding is a mere 2px. The plugin set its own `line-height` on the label element and thereby shrank the button by 14px. It no longer sets any size-determining property (`line-height`, `padding`, `border-radius`, `min-height`, `font-size`), only colour and layout.
- Icons render at the theme's base size instead of `icon-sm` and now line up flush with the label.

# 2.4.2

- The buttons now carry the theme's `btn` class. Border radius, padding, font and focus ring therefore come from each shop's own theme and automatically match "Add to shopping cart" — including themes with fully rounded pill buttons. The radius used to be hardcoded to 8px, which looked out of place in such themes. Only colour and layout remain ours.

# 2.4.1

- The data protection confirmation is now only required when the shop actually shows it. Shopware renders the checkbox exclusively when `core.loginRegistration.requireDataProtectionCheckbox` is enabled (Settings → Login & registration); with the setting off, only the informational text appears. The controller demanded the confirmation regardless — in such shops the inquiry could not be submitted at all, always failing with "Bitte bestätigen Sie die Datenschutzbestimmungen".

# 2.4.0

- **Global activation**: two new switches in the basic configuration show "Product inquiry" resp. "Financing inquiry" on *all* products without ticking a checkbox on each one. While a switch is off, the per-product custom field decides as before.
- **Second button "Financing inquiry"**: switched on/off per product, with its own button label and subject (three new custom fields). The plugin configuration additionally accepts a dedicated recipient for financing inquiries — empty means the same recipient as for product inquiries.
- Both buttons now carry icons from the Shopware icon set (`envelope` for the product inquiry, `euro` for financing).
- New configuration card "Buttons": the colour of both buttons is configurable via colour picker. The colour is applied inline as a CSS variable, so changing it requires no theme build.
- Buttons were redesigned: rounded, with hover/focus states; stacked on mobile and side by side with equal width from the medium breakpoint upwards.
- **Variant fix**: a checkbox set on the main product now also applies to its variants. The template previously read `page.product.parent`, which is never loaded on the product detail page, and Shopware inherits `customFields` only as a whole block, so custom fields of the variant hid the main product's values. A new `ProductPageSubscriber` resolves the values server-side and loads the main product when needed.
- The custom fields are now also created when the plugin is activated. Previously this only happened in `install()`/`update()` — if the field set was never created (e.g. because the database already knew the current version, so `plugin:update` did nothing), the "Product inquiry" tab was permanently missing from the product and the inquiry button could not be enabled anywhere.
- The inquiry type travels as a query parameter on the form action, so the correct recipient and mail wording apply even without JavaScript.

# 2.3.1

- Shopware 6.7 compatibility: the form is now located via the version-stable field names (`subject`/`comment`) instead of fixed element IDs (`form-subject`/`form-comment`). The IDs changed in 6.7, which broke both prefill and sending.
- Prefill now reacts via a `MutationObserver` exactly when the modal form appears in the DOM — no fixed time window that could expire before the modal is opened.

# 2.3.0

- Sales channel / sub-shop fix: inquiries are now sent to the correct, channel-specific URL. Previously the form action was hardcoded to `/dasform/inquiry` in JavaScript, which broke sending in sub-shops mounted under a domain/path prefix.
- The URL is now generated server-side via `path()` (including the sales-channel prefix) and passed to the storefront JS through a data attribute.
- Product name, inquiry text and subject are now passed to the form via data attributes instead of `localStorage` — reliable prefill in sub-shops as well.
- The server-side action rewrite in the contact form now uses the exact, channel-correct URL instead of a path substring replacement.
