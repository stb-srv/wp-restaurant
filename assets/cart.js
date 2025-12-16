document.addEventListener('click', function (event) {
    if (event.target.closest('.wpr-add-to-cart')) {
        const button = event.target.closest('.wpr-add-to-cart');
        const itemId = button.getAttribute('data-id');
        const quantity = 1;

        const formData = new FormData();
        formData.append('action', 'wpr_add_to_cart');
        formData.append('nonce', wprCart.nonce);
        formData.append('item_id', itemId);
        formData.append('quantity', quantity);

        fetch(wprCart.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    const counter = document.querySelector('.wpr-cart-counter');
                    if (counter) {
                        counter.textContent = data.data.count;
                    }
                    button.classList.add('wpr-added');
                    setTimeout(() => button.classList.remove('wpr-added'), 1000);
                }
            });
    }

    if (event.target.closest('.wpr-cart-remove')) {
        const removeButton = event.target.closest('.wpr-cart-remove');
        const item = removeButton.closest('.wpr-cart-item');
        const itemId = item.getAttribute('data-id');

        const formData = new FormData();
        formData.append('action', 'wpr_remove_from_cart');
        formData.append('nonce', wprCart.nonce);
        formData.append('item_id', itemId);

        fetch(wprCart.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    item.remove();
                    const counter = document.querySelector('.wpr-cart-counter');
                    if (counter) {
                        counter.textContent = data.data.count;
                    }
                }
            });
    }

    if (event.target.closest('.wpr-cart-print')) {
        window.print();
    }
});
