<div class="space-y-2">
    <div><strong>Module:</strong> {{ $record->module?->name }}</div>
    <div><strong>Day:</strong> {{ optional($record->timeSlot)->exam_date }}</div>
    <div><strong>Time:</strong> {{ optional($record->timeSlot)->starts_at }}</div>
    <div><strong>Students:</strong> {{ $record->student_count }}</div>

    <div>
        <strong>Rooms:</strong><br>
        {!! $roomsHtml !!}
    </div>
</div>
