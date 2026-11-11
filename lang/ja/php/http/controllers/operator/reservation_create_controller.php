<?php

// I18N-01 — ja translation of lang/en/php/http/controllers/operator/reservation_create_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'workspace_paused' => 'ワークスペースは一時停止中（読み取り専用）のため、再開するまで新規予約は受け付けられません。「請求」から再開してください。',
    'invalid_dates' => '有効なチェックイン日とチェックアウト日を選択してください。',
    'too_many_nights' => '1件の予約は最長:max泊です。より短い宿泊期間を選択してください。',
    'choose_room_and_unit' => '客室タイプと、宿泊する客室を選択してください。',
    'room_type_unavailable' => 'その客室タイプは利用できません。',
    'room_not_free_or_unpriced' => 'その客室は空いていないか、この期間の一部の泊に料金が設定されていません。一覧からもう一度客室を選択してください。',
    'unit_no_longer_free' => 'その客室はこの期間に空きがなくなりました。別の客室を選択してください。',
    'card_coming_soon' => 'カード決済は近日対応予定です。現金または銀行振込で入金するか、後でフォリオから記録してください。',
    'no_payment_permission' => '入金を記録する権限がありません。',
    'invalid_payment_amount' => '有効な入金額を入力してください（例：150、150.00）。',
    'choose_override_reason' => 'このまま予約するには、一覧から理由を選択してください。',
    'override_note_required' => 'メモに理由を記入してください。「その他」を選択した場合は必須です。',
    'dates_restricted' => '予約は作成されませんでした。この期間には制限があります。下で理由を選んでこのまま予約するか、ステップ1で日程を変更してください。',
    'unit_just_taken' => 'その客室はたった今この期間で予約されたため、予約は作成されませんでした。別の客室を選択してください。',
    'reservation_created' => '予約:referenceを作成しました。',
    'credit_applied' => 'ウォレット残高から:amountを適用しました。',
    'recorded_as_paid' => ':amountを支払済みとして記録しました。',
    'walk_in_checked_in' => 'ウォークイン:referenceをチェックインしました。',
    'walk_in_created' => 'ウォークイン:referenceを作成しました。',
    'guest_not_found' => 'ゲストが見つかりませんでした。もう一度検索してください。',
    'choose_or_name_guest' => '既存ゲストを選択するか、新規ゲストの名前を入力してください。',
    'invalid_guest_email' => 'ゲストのメールアドレスが無効です。',
    'guest_email_exists' => 'このメールアドレスのゲストはすでに登録されています（:name）。メールアドレスをもう一度入力し、「このゲストを使用」または「新規ゲストを作成」を選択してください。',
];
