<x-mail::message>
# Weekly Closing Test Summary

**Period:** {{ $data['period_label'] }}  
**Window:** {{ $data['period_start'] }} to {{ $data['period_end'] }} (Asia/Kuala_Lumpur)  
**Generated at:** {{ $data['generated_at'] }}  
**Snapshot file:** {{ $data['snapshot_path'] }}

## Admin Weekly Business Progress

**Total orders:** {{ $data['admin']['total_orders'] }}  
**Total order amount:** RM {{ number_format((float) $data['admin']['total_order_amount'], 2) }}  
**Total order units:** {{ $data['admin']['total_order_units'] }}  
**Total POS sales:** {{ $data['admin']['total_pos_sales'] }}  
**Total POS amount:** RM {{ number_format((float) $data['admin']['total_pos_amount'], 2) }}  
**Total payable bonus:** RM {{ number_format((float) $data['admin']['total_payable_bonus'], 2) }}  
**Pending payout count:** {{ $data['admin']['pending_payout_count'] }}

## Pending Payment List (Simulation)

<x-mail::table>
| Agent | Email | Tier 1 bonus | Tier 2 bonus | Total payable |
| :-- | :-- | --: | --: | --: |
@forelse ($data['pending_payments'] as $row)
| {{ $row['agent_name'] }} | {{ $row['email'] ?: '-' }} | RM {{ number_format((float) $row['tier1_bonus'], 2) }} | RM {{ number_format((float) $row['tier2_bonus'], 2) }} | RM {{ number_format((float) $row['total_bonus'], 2) }} |
@empty
| - | - | RM 0.00 | RM 0.00 | RM 0.00 |
@endforelse
</x-mail::table>

## Agent Performance (All Users)

<x-mail::table>
| Agent | Personal orders | Personal amount | New agents | Team size (T1/T2) | Tier orders (T1/T2) | Tier bonus (T1/T2) | POS sales | POS amount |
| :-- | --: | --: | --: | :-- | :-- | :-- | --: | --: |
@foreach ($data['agents'] as $agent)
| {{ $agent['agent_name'] }} | {{ $agent['personal_orders'] }} | RM {{ number_format((float) $agent['personal_order_amount'], 2) }} | {{ $agent['new_agents_registered'] }} | {{ $agent['tier1_agents_total'] }}/{{ $agent['tier2_agents_total'] }} | {{ $agent['tier1_orders_count'] }}/{{ $agent['tier2_orders_count'] }} | RM {{ number_format((float) $agent['tier1_bonus'], 2) }}/RM {{ number_format((float) $agent['tier2_bonus'], 2) }} | {{ $agent['pos_sales_count'] }} | RM {{ number_format((float) $agent['pos_sales_amount'], 2) }} |
@endforeach
</x-mail::table>

This is a testing-only closing snapshot email generated without UI closing screens.

Thanks,<br>
Anugerah3D
</x-mail::message>
