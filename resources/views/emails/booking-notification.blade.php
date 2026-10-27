<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ახალი ჯავშანი #{{ $booking->id }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0d0d0e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f4f4f5; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #0d0d0e; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 620px; background-color: #18181b; border: 1px solid #27272a; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 36px 24px 36px; background: linear-gradient(180deg, #27272a 0%, #18181b 100%); border-bottom: 1px solid #3f3f46; text-align: center;">
                            <div style="font-size: 24px; font-weight: 800; letter-spacing: 2px; color: #f2ca50; text-transform: uppercase; margin-bottom: 6px;">
                                Foodline.ge
                            </div>
                            <div style="font-size: 13px; color: #a1a1aa; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                                Premium Catering Service
                            </div>
                        </td>
                    </tr>

                    <!-- Status Banner -->
                    <tr>
                        <td style="padding: 20px 36px; background-color: rgba(242, 202, 80, 0.08); border-bottom: 1px solid rgba(242, 202, 80, 0.2);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 12px; font-weight: 700; color: #f2ca50; text-transform: uppercase; letter-spacing: 1px;">
                                            ახალი შეკვეთა მიღებულია
                                        </div>
                                        <div style="font-size: 20px; font-weight: 700; color: #ffffff; margin-top: 4px;">
                                            ჯავშანი #{{ $booking->id }}
                                        </div>
                                    </td>
                                    <td align="right">
                                        <div style="font-size: 12px; color: #a1a1aa;">ღირებულება</div>
                                        <div style="font-size: 22px; font-weight: 800; color: #f2ca50;">
                                            ₾ {{ number_format($booking->total_estimate, 2) }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Customer & Event Info -->
                    <tr>
                        <td style="padding: 28px 36px;">
                            <div style="font-size: 13px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">
                                დამკვეთის და ღონისძიების დეტალები
                            </div>
                            
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #121214; border: 1px solid #27272a; border-radius: 10px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; width: 40%; color: #a1a1aa; font-size: 13px;">სახელი, გვარი:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 600; font-size: 14px;">{{ $booking->full_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 13px;">ტელეფონის ნომერი:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; font-size: 14px;">
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->phone) }}" style="color: #f2ca50; text-decoration: none; font-weight: 700;">
                                            {{ $booking->phone }}
                                        </a>
                                    </td>
                                </tr>
                                @if(!empty($booking->email))
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 13px;">ელ-ფოსტა:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; font-size: 14px;">
                                        <a href="mailto:{{ $booking->email }}" style="color: #f2ca50; text-decoration: none;">
                                            {{ $booking->email }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 13px;">მიტანის თარიღი:</td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 700; font-size: 14px;">
                                        📅 {{ substr($booking->event_date, 0, 10) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; {{ !empty($booking->notes) ? 'border-bottom: 1px solid #27272a;' : '' }} color: #a1a1aa; font-size: 13px;">მისამართი:</td>
                                    <td style="padding: 12px 16px; {{ !empty($booking->notes) ? 'border-bottom: 1px solid #27272a;' : '' }} color: #ffffff; font-size: 14px;">
                                        📍 {{ $booking->address }}
                                    </td>
                                </tr>
                                @if(!empty($booking->notes))
                                <tr>
                                    <td style="padding: 12px 16px; color: #a1a1aa; font-size: 13px; vertical-align: top;">შენიშვნა / კომენტარი:</td>
                                    <td style="padding: 12px 16px; color: #fbbf24; font-size: 13px; line-height: 1.5; font-style: italic;">
                                        "{{ $booking->notes }}"
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    <!-- Order Items -->
                    <tr>
                        <td style="padding: 0 36px 28px 36px;">
                            <div style="font-size: 13px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">
                                შეკვეთილი პროდუქტები
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #121214; border: 1px solid #27272a; border-radius: 10px; overflow: hidden; font-size: 13px;">
                                <thead>
                                    <tr style="background-color: #27272a; border-bottom: 1px solid #3f3f46; color: #a1a1aa; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">
                                        <th style="padding: 12px 16px; text-align: left;">პროდუქტი</th>
                                        <th style="padding: 12px 16px; text-align: center; width: 80px;">რაოდენობა</th>
                                        <th style="padding: 12px 16px; text-align: right; width: 90px;">ფასი</th>
                                        <th style="padding: 12px 16px; text-align: right; width: 100px;">ჯამი</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Combos First --}}
                                    @foreach($orderDetails['combos'] as $combo)
                                        @php
                                            $qty = intval($combo['qty'] ?? 1);
                                            $price = floatval($combo['price'] ?? $combo['price_per_guest'] ?? 0);
                                            $subtotal = $qty * $price;
                                        @endphp
                                        <tr style="border-bottom: 1px solid #27272a; background-color: rgba(242, 202, 80, 0.04);">
                                            <td style="padding: 12px 16px;">
                                                <div style="font-weight: 700; color: #ffffff; font-size: 14px;">
                                                    {{ $combo['name'] ?? 'Combo Package' }}
                                                </div>
                                                <div style="font-size: 11px; color: #f2ca50; font-weight: 700; text-transform: uppercase; margin-top: 2px;">
                                                    ★ მზა კომბო პაკეტი {{ !empty($combo['for_guests']) ? '(' . $combo['for_guests'] . ' სტუმარზე)' : '' }}
                                                </div>
                                            </td>
                                            <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #ffffff;">
                                                <span style="background: rgba(242, 202, 80, 0.15); border: 1px solid rgba(242, 202, 80, 0.35); color: #f2ca50; padding: 3px 8px; border-radius: 10px; font-size: 12px;">
                                                    {{ $qty }}
                                                </span>
                                            </td>
                                            <td style="padding: 12px 16px; text-align: right; color: #a1a1aa;">
                                                ₾ {{ number_format($price, 2) }}
                                            </td>
                                            <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: #f2ca50;">
                                                ₾ {{ number_format($subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Custom Items --}}
                                    @foreach($orderDetails['custom_items'] as $item)
                                        @php
                                            $qty = intval($item['qty'] ?? 1);
                                            $price = floatval($item['price'] ?? 0);
                                            $subtotal = $qty * $price;
                                        @endphp
                                        <tr style="border-bottom: 1px solid #27272a;">
                                            <td style="padding: 12px 16px;">
                                                <div style="font-weight: 600; color: #ffffff; font-size: 13px;">
                                                    {{ $item['name'] ?? '-' }}
                                                </div>
                                                @if(!empty($item['category']))
                                                    <div style="font-size: 11px; color: #71717a; margin-top: 1px;">
                                                        {{ $item['category'] }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="padding: 12px 16px; text-align: center; font-weight: 700; color: #ffffff;">
                                                <span style="background: #27272a; color: #e4e4e7; padding: 3px 8px; border-radius: 10px; font-size: 12px;">
                                                    {{ $qty }}
                                                </span>
                                            </td>
                                            <td style="padding: 12px 16px; text-align: right; color: #a1a1aa;">
                                                ₾ {{ number_format($price, 2) }}
                                            </td>
                                            <td style="padding: 12px 16px; text-align: right; font-weight: 600; color: #ffffff;">
                                                ₾ {{ number_format($subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    <!-- Total Row -->
                                    <tr style="background-color: #18181b;">
                                        <td colspan="3" style="padding: 14px 16px; text-align: right; font-size: 14px; font-weight: 700; color: #ffffff;">
                                            ჯამური საორიენტაციო თანხა:
                                        </td>
                                        <td style="padding: 14px 16px; text-align: right; font-size: 16px; font-weight: 800; color: #f2ca50;">
                                            ₾ {{ number_format($booking->total_estimate, 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>



                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 36px; background-color: #121214; border-top: 1px solid #27272a; text-align: center; font-size: 12px; color: #71717a;">
                            ეს შეტყობინება ავტომატურად გამოგზავნილია საიტიდან <a href="https://foodline.ge" style="color: #f2ca50; text-decoration: none;">Foodline.ge</a>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
