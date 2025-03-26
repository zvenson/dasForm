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
                const productName = url.searchParams.get('productName');
                const productId = url.searchParams.get('productId');

                // 💾 Save to localStorage
                localStorage.setItem('dasform_productName', productName);
                localStorage.setItem('dasform_productId', productId);

                console.log('[DasForm] Stored product data in localStorage');
            });
        });
    }

    _injectFormDataFromLocalStorage() {
        const interval = setInterval(() => {
            const subjectInput = document.getElementById('form-subject');
            const commentInput = document.getElementById('form-comment');
    
            const productName = localStorage.getItem('dasform_productName');
            const productId = localStorage.getItem('dasform_productId');
    
            if (productName && subjectInput && !subjectInput.value) {
                subjectInput.value = `Anfrage zum Produkt: ${productName}`;
            }
    
            if (productName && commentInput && !commentInput.value) {
                commentInput.value = `Ich interessiere mich für Ihren Artikel ${productName} und bitte um Kontaktaufnahme.`;
            }
    
            // If both are filled, stop polling
            if (
                productName &&
                subjectInput?.value &&
                commentInput?.value
            ) {
                console.log('[DasForm] Subject and comment prefilled ✅');
    
                localStorage.removeItem('dasform_productName');
                localStorage.removeItem('dasform_productId');
    
                clearInterval(interval);
            }
        }, 300);
    
        setTimeout(() => clearInterval(interval), 10000);
    }
    
    
}
