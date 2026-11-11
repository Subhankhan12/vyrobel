<?php

// I18N-01 — ar translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'تتم مدفوعات البطاقات عبر Stripe (وضع الاختبار) — وستتاح عند التبديل إلى المفاتيح الفعلية. يرجى تسجيل دفعة نقدية أو تحويل بنكي لاستلام دفعة في مكتب الاستقبال الآن.',
    'payment_amount_invalid' => 'يرجى إدخال مبلغ دفعة أكبر من صفر.',
    'payment_recorded' => 'تم تسجيل الدفعة.',
    'charge_amount_invalid' => 'يرجى إدخال مبلغ رسم أكبر من صفر.',
    'charge_added' => 'تمت إضافة الرسم.',
    'refund_only_settled' => 'يمكن استرداد الدفعات المكتملة فقط.',
    'already_fully_refunded' => 'تم استرداد هذه الدفعة بالكامل بالفعل.',
    'refund_amount_invalid' => 'يرجى إدخال مبلغ استرداد أكبر من صفر.',
    'refund_exceeds' => 'تم رفض هذا الاسترداد — المبلغ المتبقي القابل للاسترداد من هذه الدفعة هو :amount :currency فقط.',
    'refund_issued' => 'تم إصدار الاسترداد.',
    'billed_to_account' => 'تمت الفوترة على حساب :account. يبقى الرصيد مستحقًا حتى يتم الدفع.',
    'returned_to_guest' => 'تمت الإعادة إلى حساب إقامة الضيف.',
    'hold_released' => 'تم تحرير المبلغ المجمد على بطاقة الضيف — دون تحصيل أي مبلغ.',
];
