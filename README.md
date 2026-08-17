🛠️ DAS Form – Product Inquiry Button for Shopware 6
This Shopware 6 plugin adds a "Product Inquiry" button beneath the Add to Cart button on the product detail page.

When clicked, it opens a modal window containing a contact form that allows customers to ask questions about the product.
The subject and message fields in the form are automatically pre-filled with the product name to save your customers time and encourage interaction.

✨ What's new in 2.6
- **Financing rules maintainable in the backend**: a new field "Financing rules (JSON)" in the configuration card "Financing rates" holds the variants, their labels, `amort`, purchase credit and return month — no code change needed when the provider's figures change.
- `amort` is read as a string and decomposed into an integer fraction purely textually (`"0.42"` → 42/100), so the exact integer arithmetic is never undone by a float. Malformed JSON falls back to the built-in defaults and logs the cause instead of breaking the product page.

✨ What's new in 2.5
- **Rate preview** on the financing button: hovering reveals the possible monthly rates of the three FLEX variants, net and gross, plus purchase credit and earliest return month. Switch it off in the configuration card "Financing rates".
- Calculated to the cent in integer cents (no floating point), based on the net price, within a configurable price range (default 300 € to 30,000 € net).
- On touch devices the tooltip is replaced by a compact line below the buttons ("Finanzieren ab X € …"), since there is nothing to hover there. The switch is `(hover: none)`, not screen width.
- New setting **"Corner radius of the buttons"** for themes that round their buy button through a selector our buttons do not match.

✨ What's new in 2.4
- **Global activation** per button in the plugin config — switch it on once and the button appears on every product, no per-product tick needed. With the switch off, the per-product custom field decides (as in 2.0).
- Second button **"Finanzierungsanfrage"** next to the product inquiry, with its own per-product switch, button label and subject — and optionally its own recipient address.
- Icons on both buttons (`envelope` / `euro` from the Shopware icon set) and configurable button colours (Plugin config → "Schaltflächen").
- Responsive layout: stacked on mobile, side by side on desktop.
- Values maintained on a main product now reach its variants reliably (resolved server-side instead of relying on Twig inheritance).

✨ What's new in 2.0
- Per-product activation: three new custom fields on every product (tab "Specifications / Freitextfelder" → section "Produktanfrage"):
  - **Produktanfrage aktiv?** (bool, default off) – the button only appears when this is enabled AND the global master switch is on. The master switch alone no longer shows the button on any product – you have to opt each product in.
  - **Button-Text / Anfragetext** (text) – overrides the default "Produktanfrage" button label, and is additionally appended to the prefilled comment inside the contact form.
  - **Betreff des Formulars** (text) – prefilled as the subject line of the contact form. Leave empty to keep the default "Anfrage zum Produkt: …".
- Button is now full-width and uses the theme's info color (`$sw-color-info`), so it picks up the shop's theme styling instead of being hardcoded.
- Prefills (subject / comment) are injected via JavaScript after the AJAX modal loads, so per-product values reliably land in the form regardless of theme overrides.

💡 Why this plugin?
This feature was widely loved in Shopware 5 – and now it's making a comeback in Shopware 6.
It's a great starting point for anyone who wants to restore this helpful customer interaction feature – and it's 100% free.

⚠️ Note
This is an early version and may not be perfect yet – but it's a solid foundation to build on. Contributions and feedback are always welcome!

Installation: Please set a Shopping Experience in the basicInformation of shopware and select a the contact page there. Otherwise our plugin will not know where the contact form is.

After installing / updating to 2.0 run:
```
bin/console plugin:update SvenDasForm
bin/console cache:clear
./bin/build-storefront.sh
```

Support: <a href="https://www.webdesignhamburg.net">WebdesignHamburg Shopware</a>
