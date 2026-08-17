@props(['student', 'rows', 'summary'])

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 max-w-3xl mx-auto print:shadow-none print:border-0">
    <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-6">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ config('app.name') }}</h1>
            <p class="text-xs text-gray-500">Academic Report Card</p>
        </div>
        <button onclick="window.print()" class="print:hidden inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
            </svg>
            Print
        </button>
    </div>

    <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm mb-6">
        <p><span class="text-gray-500">Student:</span> <span class="font-medium text-gray-900">{{ $student->user->name }}</span></p>
        <p><span class="text-gray-500">Admission No:</span> <span class="font-medium text-gray-900">{{ $student->admission_no }}</span></p>
        <p><span class="text-gray-500">Class:</span> <span class="font-medium text-gray-900">{{ $student->schoolClass->name }} - {{ $student->section->name }}</span></p>
        <p><span class="text-gray-500">Roll No:</span> <span class="font-medium text-gray-900">{{ $student->roll_number }}</span></p>
    </div>

    <div class="overflow-x-auto border border-gray-200 rounded-lg">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">%</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Grade</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-2.5 text-gray-900 whitespace-nowrap">{{ $row['subject'] }}</td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['exam_title'] }}</td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['obtained'] }} / {{ $row['total'] }}</td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['percentage'] }}%</td>
                        <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $row['grade'] }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $row['passed'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                {{ $row['passed'] ? 'Pass' : 'Fail' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">No declared results yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (count($rows) > 0)
        <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
            <div class="text-sm text-gray-600">
                Total: <span class="font-medium text-gray-900">{{ $summary['obtained'] }} / {{ $summary['total'] }}</span>
                &nbsp;·&nbsp;
                Overall: <span class="font-medium text-gray-900">{{ $summary['percentage'] }}%</span>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $summary['passed'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                Overall Result: {{ $summary['passed'] ? 'PASS' : 'FAIL' }}
            </span>
        </div>
    @endif
</div>
