{{-- The reason box and the admin's note in the suspension emails. $reason = User::suspensionReason(), $note, $by. --}}
<div class="em-hot-soft" style="margin: 0 0 {{ $note ? '12px' : '22px' }}; padding: 16px 18px; border-radius: 18px; background: #FDE9DA; border: 1px solid #F2B791;">
    <p class="em-hot" style="margin: 0 0 6px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: #B23A0A;">Reason</p>
    <p class="em-ink" style="margin: 0 0 4px; font-size: 16px; font-weight: 700; color: #12170F;">{{ $reason[4] }}</p>
    <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.5; color: #464D42;">{{ $reason[5] }}</p>
</div>
@if ($note)
    <div class="em-soft" style="margin: 0 0 22px; padding: 12px 16px; border-radius: 0 14px 14px 0; background: #F6F5EE; border-left: 3px solid #B23A0A;">
        <p class="em-ink3" style="margin: 0 0 4px; font-size: 12px; font-weight: 700; color: #5B6257;">Note from the admin</p>
        <p class="em-ink" style="margin: 0 0 6px; font-size: 15px; line-height: 1.5; color: #12170F;">{{ $note }}</p>
        <p class="em-ink3" style="margin: 0; font-size: 13px; color: #5B6257;">{{ $by && ! Str::startsWith($by, 'PickleX') ? $by.' · PickleX admin' : 'PickleX admin team' }}</p>
    </div>
@endif
