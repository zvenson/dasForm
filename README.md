🛠️ DAS Form – Product Inquiry Button for Shopware 6
This Shopware 6 plugin adds a "Product Inquiry" button beneath the Add to Cart button on the product detail page.

When clicked, it opens a modal window containing a contact form that allows customers to ask questions about the product.
The subject and message fields in the form are automatically pre-filled with the product name to save your customers time and encourage interaction.

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
