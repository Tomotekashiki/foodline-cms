@php
    $record = $getRecord();
    $details = $record?->order_details;
    if (is_string($details)) {
        $details = json_decode($details, true) ?: [];
    }
    $customItems = $details['custom_items'] ?? [];
    $combosList = $details['combos'] ?? [];
    if (empty($combosList) && !empty($details['combo'])) {
        $combosList = [$details['combo']];
    }
    $hasAnyItems = !empty($combosList) || !empty($customItems);
@endphp

<div style="width: 100%;">
    @if($hasAnyItems)
        <div style="width: 100%; background: #18181b; border: 1px solid #27272a; border-radius: 10px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; table-layout: fixed;">
                <thead>
                    <tr style="background: #27272a; border-bottom: 1px solid #3f3f46; color: #a1a1aa; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th style="padding: 10px 12px; width: 55%;">Product</th>
                        <th style="padding: 10px 12px; text-align: center; width: 22%;">Price</th>
                        <th style="padding: 10px 12px; text-align: right; width: 23%;">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Combos First --}}
                    @foreach($combosList as $combo)
                        @php
                            $comboQty = intval($combo['qty'] ?? 1);
                            $comboPrice = floatval($combo['price'] ?? $combo['price_per_guest'] ?? 0);
                        @endphp
                        <tr style="border-bottom: 1px solid #27272a; background: rgba(245, 158, 11, 0.03);">
                            <td style="padding: 10px 12px;">
                                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                    @if(!empty($combo['image_url']))
                                        <img src="{{ $combo['image_url'] }}" alt="{{ $combo['name'] ?? '' }}" style="width: 38px; height: 38px; min-width: 38px; max-width: 38px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(245, 158, 11, 0.4); display: block; flex-shrink: 0;" />
                                    @else
                                        <div style="width: 38px; height: 38px; min-width: 38px; max-width: 38px; border-radius: 6px; background: #27272a; border: 1px solid rgba(245, 158, 11, 0.4); display: flex; align-items: center; justify-content: center; color: #f59e0b; flex-shrink: 0; font-size: 14px;">★</div>
                                    @endif
                                    <div style="min-width: 0; flex-grow: 1;">
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span style="font-weight: 700; color: #f4f4f5; font-size: 13px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                {{ $combo['name'] ?? 'Combo Package' }}
                                            </span>
                                            <span style="font-size: 9px; font-weight: 700; text-transform: uppercase; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24; padding: 1px 5px; border-radius: 4px; letter-spacing: 0.05em; white-space: nowrap;">
                                                Combo
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 12px; text-align: center; color: #f59e0b; font-weight: 700; white-space: nowrap; font-size: 13px;">
                                ₾ {{ $comboPrice }}
                            </td>
                            <td style="padding: 10px 12px; text-align: right; white-space: nowrap;">
                                <span style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 11px;">
                                    {{ $comboQty }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Custom Menu Items --}}
                    @foreach($customItems as $item)
                        @php 
                            $price = floatval($item['price'] ?? 0);
                            $qty = intval($item['qty'] ?? 1);
                        @endphp
                        <tr style="border-bottom: 1px solid #27272a;">
                            <td style="padding: 10px 12px;">
                                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                    @if(!empty($item['image']))
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] ?? '' }}" style="width: 38px; height: 38px; min-width: 38px; max-width: 38px; border-radius: 6px; object-fit: cover; border: 1px solid #3f3f46; display: block; flex-shrink: 0;" />
                                    @endif
                                    <div style="min-width: 0; flex-grow: 1;">
                                        <div style="font-weight: 600; color: #f4f4f5; font-size: 13px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['name'] ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 12px; text-align: center; color: #d4d4d8; font-weight: 500; white-space: nowrap; font-size: 13px;">
                                ₾ {{ $price }}
                            </td>
                            <td style="padding: 10px 12px; text-align: right; white-space: nowrap;">
                                <span style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 11px;">
                                    {{ $qty }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="padding: 20px; text-align: center; color: #71717a; background: #18181b; border: 1px solid #27272a; border-radius: 10px; font-size: 12px;">
            No products in this order.
        </div>
    @endif
</div>