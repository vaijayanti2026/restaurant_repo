
<div class="card-body" id="schedule">
    @foreach($days as $key => $day)
    @php
    $schedule = $schedules[$key] ?? null;
    @endphp
    <div class="time-schedule-row">
        <span class="time-schedule-date">{{translate("$day")}}</span>
        <div class="d-flex flex-wrap align-items-center gap-4 gap-sm-2 gapx-30">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="border rounded py-2 px-3">
                        <div class="d-flex gap-2">
                            <div>
                                <div>{{translate('Opening_Time')}}</div>
                                <input type="hidden" name="days[{{$key}}][name]" value="{{$day}}">
                            <input type="time" class="form-control" name="days[{{$key}}][start_time]" value="{{ old('days.'.$key.'.start_time', $schedule ? $schedule->opening_time : '') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="border rounded py-2 px-3">
                        <div class="d-flex gap-2">
                            <div>
                                <div>{{translate('Closing_Time')}}</div>
                            <input type="time" class="form-control" name="days[{{$key}}][closing_time]" value="{{ old('days.'.$key.'.closing_time', $schedule ? $schedule->closing_time : '') }}" required>
                            </div>
                        </div>
                    </div></div>
        </div>
    </div>
    @endforeach

</div>
