<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            direction: rtl;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .report-date {
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .member-row {
            background-color: #f9f9f9;
        }
        .subscription-row td {
            padding-right: 20px;
        }
        .status-paid {
            color: green;
        }
        .status-pending {
            color: orange;
        }
        .status-overdue {
            color: red;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">تقرير اشتراكات الأعضاء</div>
        <div class="report-date">
            @if($startDate && $endDate)
                الفترة: {{ $startDate }} إلى {{ $endDate }}
            @else
                تقرير شامل
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم العضو</th>
                <th>رقم العضوية</th>
                <th>المبلغ</th>
                <th>حالة الدفع</th>
                <th>تاريخ الدفع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $member)
                @forelse($member->subscriptions as $subscription)
                    <tr class="{{ $loop->first ? 'member-row' : 'subscription-row' }}">
                        @if($loop->first)
                            <td rowspan="{{ $member->subscriptions->count() }}">{{ $member->name }}</td>
                            <td rowspan="{{ $member->subscriptions->count() }}">{{ $member->membership_number }}</td>
                        @endif
                        <td>{{ number_format($subscription->amount, 2) }} ريال</td>
                        <td class="status-{{ $subscription->subscription_status }}">
                            @switch($subscription->subscription_status)
                                @case('paid')
                                    مدفوع
                                    @break
                                @case('pending')
                                    معلق
                                    @break
                                @case('overdue')
                                    متأخر
                                    @break
                                @default
                                    {{ $subscription->subscription_status }}
                            @endswitch
                        </td>
                        <td>{{ $subscription->payment_date ? date('Y-m-d', strtotime($subscription->payment_date)) : '-' }}</td>
                    </tr>
                @empty
                    <tr class="member-row">
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->membership_number }}</td>
                        <td colspan="3">لا يوجد اشتراكات</td>
                    </tr>
                @endforelse
            @endforeach
        </tbody>
    </table>
</body>
</html> 