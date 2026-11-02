<?php

// I18N-01 — th translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'ป้อนที่อยู่ IP หรือบล็อก CIDR ที่ถูกต้อง (เช่น 203.0.113.4 หรือ 203.0.113.0/24)',
    'ip_already_listed' => ':ip อยู่ในรายการ IP ที่อนุญาตแล้ว',
    'allowlist_enabled_auto_added' => 'เปิดใช้รายการ IP ที่อนุญาตแล้ว เราเพิ่มที่อยู่ปัจจุบันของคุณ (:ip) ไว้ด้วย เพื่อไม่ให้คุณเข้าระบบไม่ได้',
    'ip_added' => 'เพิ่ม :ip ในรายการ IP ที่อนุญาตแล้ว',
    'remove_would_lock_out' => 'การนำ :ip ออกจะทำให้คุณเข้าระบบไม่ได้ — เป็นกฎเดียวที่อนุญาตให้ที่อยู่ของคุณ (:current) เข้าถึงระบบหลังบ้านได้ โปรดเพิ่มกฎสำหรับที่อยู่ของคุณก่อน หรือนำกฎอื่นออก',
    'ip_removed_allowlist_off' => 'นำ :ip ออกแล้ว — รายการ IP ที่อนุญาตปิดอยู่ ผู้ที่เข้าสู่ระบบได้ถูกต้องทุกคนจึงเข้าถึงระบบหลังบ้านได้',
    'ip_removed' => 'นำ :ip ออกจากรายการ IP ที่อนุญาตแล้ว',
    'session_already_ended' => 'เซสชันนั้นสิ้นสุดไปแล้ว',
    'signed_out_of_device' => 'ออกจากระบบอุปกรณ์ :device แล้ว',
    'trusted_device_revoked' => 'เพิกถอนอุปกรณ์ที่เชื่อถือได้แล้ว',
    'csv_title' => 'ส่งออกบัญชี Vyrobel',
    'csv_name' => 'ชื่อ',
    'csv_email' => 'อีเมล',
    'csv_locale' => 'ภาษา',
    'csv_roles' => 'บทบาท',
    'csv_activity' => 'กิจกรรม',
    'csv_when' => 'เวลา',
    'csv_action' => 'การดำเนินการ',
    'range_this_year' => 'ปีนี้',
    'range_custom' => 'ช่วงที่กำหนดเอง',
    'range_all_time' => 'ทั้งหมด',
];
