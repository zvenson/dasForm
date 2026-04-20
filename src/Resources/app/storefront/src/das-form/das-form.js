import Plugin from 'src/plugin-system/plugin.class';

export default class DasFormContactInject extends Plugin {
    init() {
        console.log('[DasForm] JS initialized');
        this._registerAjaxModalHook();
        this._injectFormDataFromLocalStorage();
    }

    _registerAjaxModalHook() {
        document.querySelectorAll('[data-ajax-modal]').forEach(link => {
            link.addEventListener('click', () => {
                const url = new URL(link.getAttribute('data-url'), window.location.origin);
                const productName = url.searchParams.get('productName') || '';
                const productId = url.searchParams.get('productId') || '';
                const inquiryText = url.searchParams.get('inquiryText') || '';
                const inquirySubject = url.searchParams.get('inquirySubject') || '';

                localStorage.setItem('dasform_productName', productName);
                localStorage.setItem('dasform_productId', productId);
                localStorage.setItem('dasform_inquiryText', inquiryText);
                localStorage.setItem('dasform_inquirySubject', inquirySubject);

                console.log('[DasForm] Stored product data in localStorage');
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

            if (subjectInput?.value && commentInput?.value) {
                console.log('[DasForm] Subject and comment prefilled ✅');

                localStorage.removeItem('dasform_productName');
                localStorage.removeItem('dasform_productId');
                localStorage.removeItem('dasform_inquiryText');
                localStorage.removeItem('dasform_inquirySubject');

                clearInterval(interval);
            }
        }, 300);

        setTimeout(() => clearInterval(interval), 10000);
    }
}
