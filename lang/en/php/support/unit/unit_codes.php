<?php

// App\Support\Unit\UnitCodes — what the Add units field says when the codes typed cannot be added, and the short list of
// codes the confirmation reads ("101–103, 105 and 3 more"). The codes themselves are data.

return [
    'none_entered' => 'Enter at least one unit code.',
    // :total is how many the typed codes and ranges come to, :max the most one add may create
    'too_many' => 'That is :total units — add at most :max at a time.',
    // :start is the first 24 characters of the code, :max the longest a code may be
    'too_long' => '“:start…” is longer than :max characters.',
    'listed_twice' => ':code is listed twice.',
    // the first few codes (:codes) and how many more there are
    'summary_more' => '{1} :codes and :count more|[0,*] :codes and :count more',
];
