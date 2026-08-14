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

        this._initRates();
    }

    /**
     * Rate preview. The tooltip is moved to <body> and positioned with
     * `position: fixed`, so no ancestor with `overflow: hidden` can clip it —
     * inside the buy widget that is a real risk.
     */
    _initRates() {
        this._tooltip = this.el.querySelector('[data-dasform-rates]');
        this._tooltipTrigger = this.el.querySelector('.dasform-btn-wrap--rates .dasform-btn');

        if (!this._tooltip || !this._tooltipTrigger) {
            return;
        }

        // Ohne Hover (Touch) gaebe es keinen Weg, den Tooltip zu oeffnen. Dort
        // uebernimmt die kompakte Zeile unter den Buttons, die rein per
        // Media-Query eingeblendet wird — hier ist dann nichts zu tun.
        if (window.matchMedia('(hover: none)').matches) {
            return;
        }

        document.body.appendChild(this._tooltip);

        const show = () => this._showTooltip();
        const hide = () => this._hideTooltip();

        this._tooltipTrigger.addEventListener('mouseenter', show);
        this._tooltipTrigger.addEventListener('focus', show);
        this._tooltipTrigger.addEventListener('mouseleave', hide);
        this._tooltipTrigger.addEventListener('blur', hide);

        window.addEventListener('scroll', hide, { passive: true });
        window.addEventListener('resize', hide, { passive: true });
    }

    _showTooltip() {
        const rect = this._tooltipTrigger.getBoundingClientRect();

        // Display first, otherwise offsetWidth/offsetHeight are still 0.
        this._tooltip.classList.add('is-visible');

        const margin = 12;
        // `clientWidth` statt `innerWidth`: letzteres zaehlt die Scrollleiste mit,
        // wodurch der Tooltip auf schmalen Bildschirmen rechts angeschnitten wird.
        const viewport = document.documentElement.clientWidth;
        const width = this._tooltip.offsetWidth;

        // Ueber dem Button zentrieren statt linksbuendig: der Finanzierungs-Button
        // sitzt rechts aussen, linksbuendig klebte der Tooltip am Fensterrand.
        // Der Klammerwert greift erst, wenn er sonst herauslaufen wuerde.
        const centered = rect.left + rect.width / 2 - width / 2;
        const left = Math.round(
            Math.max(margin, Math.min(centered, viewport - width - margin))
        );
        const above = rect.top - this._tooltip.offsetHeight - margin;

        this._tooltip.style.left = `${left}px`;
        // Flip below the button when there is not enough room above.
        this._tooltip.style.top = `${above < margin ? rect.bottom + margin : above}px`;
    }

    _hideTooltip() {
        this._tooltip.classList.remove('is-visible');
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
