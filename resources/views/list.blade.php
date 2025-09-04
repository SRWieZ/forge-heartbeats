<div class="mx-2 my-1">
    <div class="flex space-x-1">
        <span class="font-bold text-green">🎯 Forge Heartbeats Status</span>
    </div>
</div>

@if(!empty($matched))
    <div class="mx-2 my-1">
        <div class="font-bold text-blue">📋 Monitored Tasks</div>
    </div>
    
    <div class="mx-2">
        <table>
            <thead>
                <tr>
                    <th class="px-2 text-left">Task</th>
                    <th class="px-2 text-left">Status</th>
                    <th class="px-2 text-left">Schedule</th>
                    <th class="px-2 text-left">Grace Period</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matched as $name => $match)
                    <tr>
                        <td class="px-2">{{ $match['task']->getDisplayName() }}</td>
                        <td class="px-2">
                            @php
                                $status = $match['heartbeat']->status;
                                $color = match($status) {
                                    'pending' => 'yellow',
                                    'up' => 'green',
                                    'down' => 'red',
                                    default => 'gray'
                                };
                                $icon = match($status) {
                                    'pending' => '⏳',
                                    'up' => '✅',
                                    'down' => '❌',
                                    default => '❓'
                                };
                            @endphp
                            <span class="text-{{ $color }}">{{ $icon }} {{ ucfirst($status) }}</span>
                        </td>
                        <td class="px-2 text-gray">{{ $match['task']->cronExpression }}</td>
                        <td class="px-2 text-gray">{{ $match['heartbeat']->gracePeriod }}min</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($unmatchedTasks))
    <div class="mx-2 my-1">
        <div class="font-bold text-yellow">⚠️  Unmonitored Tasks</div>
        <div class="text-gray">These tasks exist in your schedule but have no corresponding heartbeat</div>
    </div>
    
    <div class="mx-2">
        <table>
            <thead>
                <tr>
                    <th class="px-2 text-left">Task</th>
                    <th class="px-2 text-left">Schedule</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unmatchedTasks as $task)
                    <tr>
                        <td class="px-2 text-yellow">{{ $task->getDisplayName() }}</td>
                        <td class="px-2 text-gray">{{ $task->cronExpression }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($orphanedHeartbeats))
    <div class="mx-2 my-1">
        <div class="font-bold text-red">🗑️  Orphaned Heartbeats</div>
        <div class="text-gray">These heartbeats exist in Forge but have no corresponding scheduled task</div>
    </div>
    
    <div class="mx-2">
        <table>
            <thead>
                <tr>
                    <th class="px-2 text-left">Heartbeat</th>
                    <th class="px-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orphanedHeartbeats as $heartbeat)
                    <tr>
                        <td class="px-2 text-red">{{ $heartbeat->name }}</td>
                        <td class="px-2">
                            @php
                                $status = $heartbeat->status;
                                $color = match($status) {
                                    'pending' => 'yellow',
                                    'up' => 'green', 
                                    'down' => 'red',
                                    default => 'gray'
                                };
                                $icon = match($status) {
                                    'pending' => '⏳',
                                    'up' => '✅',
                                    'down' => '❌', 
                                    default => '❓'
                                };
                            @endphp
                            <span class="text-{{ $color }}">{{ $icon }} {{ ucfirst($status) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if(!empty($unnamedTasks))
    <div class="mx-2 my-1">
        <div class="font-bold text-yellow">❓ Unnamed Tasks</div>
        <div class="text-gray">These tasks cannot be monitored because they don't have identifiable names</div>
    </div>
    
    <div class="mx-2">
        <div class="text-gray">{{ count($unnamedTasks) }} unnamed task(s) found</div>
    </div>
@endif

@if(empty($matched) && empty($unmatchedTasks) && empty($orphanedHeartbeats) && empty($unnamedTasks))
    <div class="mx-2 my-1">
        <div class="text-yellow">⚠️  No scheduled tasks or heartbeats found</div>
    </div>
@endif

<div class="mx-2 my-1">
    <div class="text-gray">Run <span class="text-blue">php artisan forge-heartbeats:sync</span> to synchronize your schedule with Forge</div>
</div>