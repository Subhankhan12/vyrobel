<?php

// I18N-01 — vi translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Cách tính giá',
    'pricing_shape_intro' => 'Gói <strong>cố định</strong> tính một mức giá bất kể tài khoản vận hành bao nhiêu phòng. Gói <strong>theo phòng</strong> tính một mức giá cho mỗi phòng đang hoạt động, được đếm lại mỗi kỳ.',
    'shape_label' => 'Cách tính',
    'shape_flat_option' => 'Cố định — một mức giá mỗi kỳ',
    'shape_per_unit_option' => 'Theo phòng — giá × số phòng đang hoạt động',
    'billing_interval_label' => 'Chu kỳ thanh toán',
    'interval_monthly_option' => 'Hằng tháng',
    'interval_yearly_option' => 'Hằng năm',
    'price_label' => 'Giá',
    'price_hint' => 'Số tiền cuối cùng — đã bao gồm thuế GTGT.',
    'unit_rate_label' => 'Giá mỗi phòng',
    'unit_rate_hint' => 'Chưa gồm thuế — Stripe cộng thuế riêng của khách hàng.',
    'currency_label' => 'Tiền tệ',
    'vat_pct_label' => 'Thuế GTGT % <span style="color: var(--ad-faint); font-weight: 400;">· không bắt buộc</span>',
    'stripe_price_heading' => 'Stripe Price',
    'stripe_price_intro' => 'Tạo Price trong trang quản trị Stripe, rồi dán ID của nó vào đây — bảng này không thể tạo Price. Price theo phòng phải là <strong>per-unit billing, tax behaviour exclusive</strong>; Price cố định là <strong>licensed, inclusive</strong>. Xem <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID Stripe Price',
    'draft_not_offered' => 'Nháp — chưa cung cấp cho khách hàng.',
];
