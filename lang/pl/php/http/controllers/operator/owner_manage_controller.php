<?php

// I18N-01 — pl translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Wpisz prowizję w procentach.',
    'commission_range' => 'Prowizja musi być liczbą od 0 do 100.',
    'commission_unchanged' => 'Nic nie zmieniono – prowizja właściciela :name od :date już wynosi :rate.',
    'commission_from_today' => 'Prowizja właściciela :name wynosi :rate od dzisiaj – wcześniej nie obowiązywała żadna umowa.',
    'commission_waiting_cancelled' => 'Prowizja właściciela :name pozostaje na poziomie :rate – zmiana zaplanowana na :date nie nastąpi.',
    'commission_from_date' => 'Prowizja właściciela :name wynosi :rate od :date – dotyczy rezerwacji rozliczanych od teraz. Rezerwacje już rozliczone zachowują dotychczasową prowizję.',
    'payouts_paused' => 'Wstrzymano wypłaty dla właściciela :name – żadna wypłata nie zostanie przekazana do czasu wznowienia.',
    'payouts_resumed' => 'Wznowiono wypłaty dla właściciela :name.',
    'period_start_required' => 'Wybierz pierwszy dzień okresu.',
    'period_end_required' => 'Wybierz ostatni dzień okresu.',
    'period_end_after_start' => 'Okres musi kończyć się w dniu rozpoczęcia lub później.',
    'period_not_closed' => 'Nie można zamknąć tego okresu: :reason Wybierz okres, który zaczyna się po ostatnim zamkniętym.',
    'period_closed' => 'Okres zamknięty – rozliczenie nr :number zamknięte, do wypłaty: :amount. Przychód jeszcze niepobrany od gości pozostaje nierozliczony i pojawi się w późniejszym rozliczeniu.',
    'note_required' => 'Najpierw wpisz notatkę.',
    'note_max' => 'Notatka może mieć do 2000 znaków.',
    'note_added' => 'Dodano notatkę.',
    'remove_password_required' => 'Wpisz hasło, aby zarchiwizować tego właściciela.',
    'password_wrong' => 'To hasło jest nieprawidłowe – wpisz hasło używane do logowania.',
    'confirm_name_required' => 'Wpisz nazwę właściciela, aby potwierdzić.',
    'confirm_name_mismatch' => 'Wpisana nazwa się nie zgadza – właściciel nie został zarchiwizowany.',
    'owner_removed' => 'Zarchiwizowano właściciela :name – jednostki odłączono, wszystkie rozliczenia i wypłaty zachowano na potrzeby księgowości.',
    'workspace_paused_until' => 'Organizacja wstrzymana – dane właścicieli są tylko do odczytu do :date.',
    'workspace_paused_for_now' => 'Organizacja wstrzymana – dane właścicieli są na razie tylko do odczytu.',
];
