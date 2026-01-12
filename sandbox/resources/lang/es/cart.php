<?php

return [
    // Cart sidebar
    'title' => 'Carrito de compras',
    'close' => 'Cerrar carrito',
    'browse_products' => 'Ver productos',
    'checkout_button' => 'Pagar',

    // Empty cart
    'empty' => [
        'title' => 'Tu carrito está vacío',
        'description' => 'Comienza a comprar para añadir artículos a tu carrito.',
        'browse_products' => 'Ver productos',
    ],

    // Cart item
    'item' => [
        'remove' => 'Eliminar',
        'sku' => 'SKU',
        'qty' => 'Cant.',
        'update_qty' => 'Actualizar cantidad',
    ],

    // Totals
    'totals' => [
        'subtotal' => 'Subtotal',
        'tax' => 'Impuestos',
        'shipping' => 'Envío',
        'total' => 'Total',
        'shipping_calculated' => 'Envío calculado en el pago.',
        'free_shipping' => 'Envío gratis',
    ],

    // Actions
    'actions' => [
        'checkout' => 'Pagar',
        'continue_shopping' => 'Continuar comprando',
        'update_cart' => 'Actualizar carrito',
        'clear_cart' => 'Vaciar carrito',
    ],

    // Checkout
    'checkout' => 'Pagar',
    'order_confirmed' => 'Pedido confirmado',
    'checkout_details' => [
        'title' => 'Pagar',
        'steps' => [
            'shipping' => 'Envío',
            'billing' => 'Facturación',
            'payment' => 'Pago',
        ],
        'shipping_info' => 'Información de envío',
        'billing_info' => 'Información de facturación',
        'same_as_shipping' => 'Igual que la dirección de envío',
        'continue_to_billing' => 'Continuar a facturación',
        'continue_to_payment' => 'Continuar al pago',
        'back' => 'Atrás',
        'place_order' => 'Realizar pedido',
        'processing' => 'Procesando...',
        'order_summary' => 'Resumen del pedido',
    ],

    // Form fields
    'form' => [
        'first_name' => 'Nombre',
        'last_name' => 'Apellidos',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'company' => 'Empresa',
        'address_line_1' => 'Dirección línea 1',
        'address_line_2' => 'Dirección línea 2 (opcional)',
        'city' => 'Ciudad',
        'state' => 'Provincia',
        'postal_code' => 'Código postal',
        'country' => 'País',
    ],

    // Payment
    'payment' => [
        'title' => 'Método de pago',
        'pay_on_delivery' => 'Pago contra entrega / Factura',
        'pay_on_delivery_desc' => 'Paga cuando recibas tu pedido o por factura',
        'ideal' => 'iDEAL',
        'credit_card' => 'Tarjeta de crédito',
        'bank_transfer' => 'Transferencia bancaria',
    ],

    // Success
    'success' => [
        'title' => '¡Gracias por tu pedido!',
        'subtitle' => 'Tu pedido ha sido confirmado y se procesará en breve.',
        'order_number' => 'Número de pedido',
        'confirmation_email' => 'Recibirás un correo de confirmación en :email.',
        'order_details' => 'Detalles del pedido',
        'continue_shopping' => 'Continuar comprando',
        'shipping_address' => 'Dirección de envío',
        'billing_address' => 'Dirección de facturación',
    ],

    // Notifications
    'notifications' => [
        'added' => 'Producto añadido al carrito',
        'removed' => 'Producto eliminado del carrito',
        'updated' => 'Carrito actualizado',
        'error' => 'Algo salió mal',
    ],
];
