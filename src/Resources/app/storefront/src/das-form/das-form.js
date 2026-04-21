import Plugin from 'src/plugin-system/plugin.class';

const STORAGE_KEYS = [
    'dasform_productName',
    'dasform_productId',
    'dasform_inquiryText',
    'dasform_inquirySubject',
];

export default class DasFormContactInject extends Plugin {
    init() {
        console.log('[DasForm] JS initialized v2.1');
        this._registerAjaxModalHook();
        this._injectFormDataFromLocalStorage();
    }

    _registerAjaxModalHook() {
        document.querySelectorAll('[data-ajax-modal]').forEach(link => {
            link.addEventListener('click', () => {
                const url = new URL(link.getAttribute('data-url'), window.location.origin);
                localStorage.setItem('dasform_productName', url.searchParams.get('productName') || '');
                localStorage.setItem('dasform_productId', url.searchParams.get('productId') || '');
                localStorage.setItem('dasform_inquiryText', url.searchParams.get('inquiryText') || '');
                localStorage.setItem('dasform_inquirySubject', url.searchParams.get('inquirySubject') || '');
            });
        });
    }

    _injectFormDataFromLocalStorage() {
        const interval = setInterval(() => {
            const subjectInput = document.getElementById('form-subject');
            const commentInput = document.getElementById('form-comment');

            const productName = localStorage.getItem('dasform_productName') || '';
            const inquiryText = localStorage.getItem('dasform_inquiryText') || '';
            const inquirySubject = localStorage.getItem('dasform_inquirySubject') || '';

            if (!productName) {
                return;
            }

            if (subjectInput && !subjectInput.value) {
                subjectInput.value = inquirySubject
                    ? inquirySubject
                    : `Anfrage zum Produkt: ${productName}`;
            }

            if (commentInput && !commentInput.value) {
                const baseComment = `Ich interessiere mich für Ihren Artikel ${productName} und bitte um Kontaktaufnahme.`;
                commentInput.value = inquiryText
                    ? `${baseComment}\n\n${inquiryText}`
                    : baseComment;
            }

            const form = (subjectInput && subjectInput.form) || (commentInput && commentInput.form);
            if (form && !form.dataset.dasformRouted) {
                form.setAttribute('action', '/dasform/inquiry');
                form.dataset.dasformRouted = '1';
                console.log('[DasForm] Form action redirected to /dasform/inquiry');
            }

            if (subjectInput?.value && commentInput?.value) {
                STORAGE_KEYS.forEach(k => localStorage.removeItem(k));
                clearInterval(interval);
            }
        }, 300);

        setTimeout(() => clearInterval(interval), 10000);
    }
}
