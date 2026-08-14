import Plugin from 'src/plugin-system/plugin.class';

export default class DasFormContactInject extends Plugin {
    init() {
        // All values are server-rendered onto the buttons, so they carry the
        // correct sales-channel base path / language prefix for sub-shops.
        this._productName = this.el.dataset.dasformProductName || '';

        this._buttons = Array.from(this.el.querySelectorAll('[data-dasform-type]'));

        // Which button the visitor pressed decides subject, comment and the
        // endpoint the form is routed to. With a single button there is nothing
        // to choose, so it is active right away.
        this._active = this._buttons.length === 1 ? this._readButton(this._buttons[0]) : null;

        this._buttons.forEach((button) => {
            button.addEventListener('click', () => {
                this._active = this._readButton(button);
                // A freshly opened modal brings a new form that must be filled
                // again, even if a previous one was already handled.
                this._injectedForm = null;
                this._tryInject();
            });
        });

        // The contact form is injected into the DOM later (ajax modal), and the
        // user may open it at any time. A MutationObserver reacts exactly when
        // the form appears — no fixed timeout that can expire before the click.
        this._observer = new MutationObserver(() => this._tryInject());
        this._observer.observe(document.body, { childList: true, subtree: true });

        // Cover the case where the form is already present at init time.
        this._tryInject();
    }

    _readButton(button) {
        return {
            action: button.dataset.dasformAction || '',
            text: button.dataset.dasformInquiryText || '',
            subject: button.dataset.dasformInquirySubject || '',
        };
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
        if (!this._active) {
            return;
        }

        const found = this._findForm();
        if (!found || found.form === this._injectedForm) {
            return;
        }

        const { form, subjectInput, commentInput } = found;

        // Route the form to the sales-channel-correct inquiry endpoint.
        // Never fall back to a hardcoded absolute path — that breaks on
        // sub-shops mounted under a domain path prefix.
        if (this._active.action) {
            form.setAttribute('action', this._active.action);
        }

        if (!this._productName) {
            return;
        }

        if (subjectInput && !subjectInput.value) {
            subjectInput.value = this._active.subject
                ? this._active.subject
                : `Anfrage zum Produkt: ${this._productName}`;
        }

        if (commentInput && !commentInput.value) {
            const baseComment = `Ich interessiere mich für Ihren Artikel ${this._productName} und bitte um Kontaktaufnahme.`;
            commentInput.value = this._active.text
                ? `${baseComment}\n\n${this._active.text}`
                : baseComment;
        }

        this._injectedForm = form;
    }
}
