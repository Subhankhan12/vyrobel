<?php

// I18N-01 — th translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'รูปแบบราคา',
    'pricing_shape_intro' => 'แผนแบบ<strong>ราคาคงที่</strong>เรียกเก็บราคาเดียวไม่ว่าบัญชีจะมีห้องพักกี่ห้อง แผนแบบ<strong>ต่อห้องพัก</strong>เรียกเก็บราคาสำหรับห้องพักที่ใช้งานอยู่ทุกห้อง โดยนับใหม่ทุกรอบ',
    'shape_label' => 'รูปแบบ',
    'shape_flat_option' => 'ราคาคงที่ — ราคาเดียวต่อรอบ',
    'shape_per_unit_option' => 'ต่อห้องพัก — ราคา × ห้องพักที่ใช้งานอยู่',
    'billing_interval_label' => 'รอบการเรียกเก็บเงิน',
    'interval_monthly_option' => 'รายเดือน',
    'interval_yearly_option' => 'รายปี',
    'price_label' => 'ราคา',
    'price_hint' => 'ยอดสุดท้าย — รวมภาษีมูลค่าเพิ่ม',
    'unit_rate_label' => 'ราคาต่อห้องพัก',
    'unit_rate_hint' => 'ก่อนภาษี — Stripe จะเพิ่มภาษีของลูกค้าเอง',
    'currency_label' => 'สกุลเงิน',
    'vat_pct_label' => 'อัตรา VAT % <span style="color: var(--ad-faint); font-weight: 400;">· ไม่บังคับ</span>',
    'stripe_price_heading' => 'ราคาใน Stripe',
    'stripe_price_intro' => 'สร้างราคา (Price) ในแดชบอร์ด Stripe แล้ววางรหัสของราคานั้นที่นี่ — แผงนี้สร้างราคาให้ไม่ได้ ราคาแบบต่อห้องพักต้องเป็น<strong>การเรียกเก็บเงินแบบต่อหน่วย (per-unit) และลักษณะภาษีแบบไม่รวมภาษี (exclusive)</strong> ส่วนราคาแบบคงที่ต้องเป็น<strong>แบบ licensed และรวมภาษี (inclusive)</strong> ดู <code>docs/BILLING-STRIPE-PRICES.md</code>',
    'stripe_price_id_label' => 'รหัสราคาใน Stripe',
    'draft_not_offered' => 'ฉบับร่าง — ไม่ได้เสนอให้ลูกค้า',
];
