{{-- DO20-1 fixture — a document whose length is controlled by $rows, so a test can force a real multi-page
     overflow. A one-page PDF prints "1 of 1" whether the paging logic works or not. --}}
<html><body style="font-family:sans-serif;font-size:12px">
@for ($i = 1; $i <= $rows; $i++)
<p style="margin:0 0 6px">Row {{ $i }} — filler line that forces the document to overflow onto further pages.</p>
@endfor
</body></html>
