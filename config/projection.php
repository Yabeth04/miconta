<?php

/**
 * Calendario base de pagos de universidad (día 15 de referencia).
 *
 * Patrón anual derivado del itinerario 2026:
 * - Q1: pagos feb / mar / abr
 * - Hueco: may (sin pago)
 * - Q2: pagos jun / jul / ago
 * - Hueco: sep (sin pago)
 * - Q3: pagos oct / nov / dic
 * - Hueco: ene (sin pago; primer pago del año en feb)
 *
 * En meses sin pago, la cuota de universidad queda libre en el saldo.
 */
return [
    'payment_day'               => 15,

    /** Meses (1–12) en los que sí se paga la universidad. */
    'university_payment_months' => [2, 3, 4, 6, 7, 8, 10, 11, 12],

    'defaults'                  => [
        'university_fee' => 110000,
    ],
];
