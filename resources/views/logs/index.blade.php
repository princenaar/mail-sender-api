<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/3.1.1/css/dataTables.dataTables.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-gray-900">Mail Logs</h1>
            <form method="POST" action="{{ route('logs.logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-blue-700 underline">Sign out</button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-4 overflow-x-auto">
            <table id="mail-logs" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipients</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preview</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failure reason</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $log->subject }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @foreach((array) $log->to as $email)
                                    <div>{{ $email }}</div>
                                @endforeach
                                @if(!empty($log->cc))
                                    <div class="text-xs text-gray-400 mt-1">CC: {{ implode(', ', $log->cc) }}</div>
                                @endif
                                @if(!empty($log->bcc))
                                    <div class="text-xs text-gray-400 mt-1">BCC: {{ implode(', ', $log->bcc) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->status === \App\Enums\MailStatus::Pending)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">pending</span>
                                @elseif($log->status === \App\Enums\MailStatus::Sent)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">sent</span>
                                @elseif($log->status === \App\Enums\MailStatus::Failed)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">failed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->sent_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                @if($log->html_body)
                                    <span class="text-gray-400 italic">{{ Str::limit(strip_tags($log->html_body), 80) }}</span>
                                @elseif($log->text_body)
                                    {{ Str::limit($log->text_body, 80) }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-red-700">{{ $log->failure_reason ?? '—' }}</td>
                            <td>{{ $log->created_at?->getTimestamp() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.datatables.net/3.1.1/js/dataTables.js"></script>
    <script>
        new DataTable('#mail-logs', {
            order: [[6, 'desc']],
            columnDefs: [
                { targets: [0, 2, 3, 4, 5, 6], searchable: false },
                { targets: 6, visible: false, orderable: true },
            ],
            language: {
                emptyTable: 'No mail logs found',
                zeroRecords: 'No mail logs match that email address',
                search: 'Search email:',
            },
        });
    </script>
</body>
</html>
