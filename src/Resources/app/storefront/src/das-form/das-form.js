import Plugin from 'src/plugin-system/plugin.class';

export default class DasFormContactInject extends Plugin {
    init() {
        // All values are server-rendered onto the inject element, so they carry
        // the correct sales-channel base path / language prefix for sub-shops.
        this._action = this.el.dataset.dasformAction || '';
        this._productName = this.el.dataset.dasformProductName || '';
        this._inquiryText = this.el.dataset.dasformInquiryText || '';
        this._inquirySubject = this.el.dataset.dasformInquirySubject || '';

        // The contact form is injected into the DOM later (ajax modal), and the
        // user may open it at any time. A MutationObserver reacts exactly when
        // the form appears — no fixed timeout that can expire before the click.
        this._observer = new MutationObserver(() => this._tryInject());
        this._observer.observe(document.body, { childList: true, subtree: true });

        // Cover the case where the form is already present at init time.
        this._tryInject();
    }

    /**
     * Locate the contact/inquiry form via the stable field name attributes
     * (`subject`, `comment`) instead of element IDs. The DOM ids changed
     * between Shopware versions (e.g. 6.7), the field names did not — they are
     * the same keys the controller reads server-side.
     */
    _findForm() {
        const comments = document.querySelectorAll('[name="comment"]');
        for (const comment of comments) {
            const form = comment.form;
            if (form && form.querySelector('[name="subject"]')) {
                return { form, subjectInput: form.querySelector('[name="subject"]'), commentInput: comment };
            }
        }
        return null;
    }

    _tryInject() {
        const found = this._findForm();
        if (!found) {
            return;
        }

        const { form, subjectInput, commentInput } = found;

        // Route the form to the sales-channel-correct inquiry endpoint.
        // Never fall back to a hardcoded absolute path — that breaks on
        // sub-shops mounted under a domain path prefix.
        if (this._action && !form.dataset.dasformRouted) {
            form.setAttribute('action', this._action);
            form.dataset.dasformRouted = '1';
        }

        if (!this._productName) {
            return;
        }

        if (subjectInput && !subjectInput.value) {
            subjectInput.value = this._inquirySubject
                ? this._inquirySubject
                : `Anfrage zum Produkt: ${this._productName}`;
        }

        if (commentInput && !commentInput.value) {
            const baseComment = `Ich interessiere mich für Ihren Artikel ${this._productName} und bitte um Kontaktaufnahme.`;
            commentInput.value = this._inquiryText
                ? `${baseComment}\n\n${this._inquiryText}`
                : baseComment;
        }
    }
}
