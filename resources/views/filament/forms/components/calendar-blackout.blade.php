<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}') || [],
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth(),
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            dayNames: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            
            init() {
                if (!Array.isArray(this.state)) {
                    this.state = [];
                }
            },
            
            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
            },
            
            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
            },
            
            getDaysInMonth() {
                const days = [];
                const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                
                let startDay = firstDay.getDay() - 1;
                if (startDay === -1) startDay = 6;
                
                for (let i = 0; i < startDay; i++) {
                    days.push({ empty: true });
                }
                
                for (let d = 1; d <= lastDay.getDate(); d++) {
                    const monthStr = String(this.currentMonth + 1).padStart(2, '0');
                    const dayStr = String(d).padStart(2, '0');
                    const dateStr = `${this.currentYear}-${monthStr}-${dayStr}`;
                    const dayOfWeek = new Date(this.currentYear, this.currentMonth, d).getDay();
                    
                    days.push({
                        empty: false,
                        day: d,
                        dateStr: dateStr,
                        isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
                    });
                }
                
                return days;
            },
            
            isDisabled(dateStr) {
                return Array.isArray(this.state) && this.state.includes(dateStr);
            },
            
            toggleDate(dateStr) {
                if (!Array.isArray(this.state)) {
                    this.state = [];
                }
                const index = this.state.indexOf(dateStr);
                if (index > -1) {
                    this.state.splice(index, 1);
                } else {
                    this.state.push(dateStr);
                    this.state.sort();
                }
            },
            
            blockAllWeekendsInMonth() {
                if (!Array.isArray(this.state)) this.state = [];
                const days = this.getDaysInMonth().filter(d => !d.empty && d.isWeekend);
                days.forEach(d => {
                    if (!this.state.includes(d.dateStr)) {
                        this.state.push(d.dateStr);
                    }
                });
                this.state.sort();
            },
            
            unblockAllWeekendsInMonth() {
                if (!Array.isArray(this.state)) return;
                const days = this.getDaysInMonth().filter(d => !d.empty && d.isWeekend);
                const weekendDates = days.map(d => d.dateStr);
                this.state = this.state.filter(d => !weekendDates.includes(d));
            },
            
            clearAll() {
                this.state = [];
            }
        }"
        class="fl-calendar-root"
    >
        <style>
            .fl-calendar-root {
                background: #141416;
                border: 1px solid #27272a;
                border-radius: 1rem;
                padding: 1.5rem;
                color: #f4f4f5;
                font-family: inherit;
                max-width: 800px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            }
            .fl-cal-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding-bottom: 1.25rem;
                border-bottom: 1px solid #27272a;
            }
            .fl-cal-title-wrap {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .fl-cal-title {
                font-size: 1.35rem;
                font-weight: 700;
                color: #ffffff;
                margin: 0;
            }
            .fl-cal-year {
                color: #f59e0b;
                margin-left: 0.35rem;
            }
            .fl-cal-nav {
                display: flex;
                align-items: center;
                gap: 0.25rem;
                background: #1f1f23;
                border: 1px solid #3f3f46;
                border-radius: 0.5rem;
                padding: 0.2rem;
            }
            .fl-cal-nav-btn {
                background: transparent;
                border: none;
                color: #d4d4d8;
                padding: 0.35rem 0.6rem;
                border-radius: 0.35rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                transition: background 0.15s, color 0.15s;
            }
            .fl-cal-nav-btn:hover {
                background: #27272a;
                color: #ffffff;
            }
            .fl-cal-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .fl-btn-action {
                border-radius: 0.5rem;
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                transition: all 0.15s ease;
            }
            .fl-btn-block-weekends {
                background: rgba(220, 38, 38, 0.15);
                border: 1px solid #dc2626;
                color: #fca5a5;
            }
            .fl-btn-block-weekends:hover {
                background: #dc2626;
                color: #ffffff;
            }
            .fl-btn-unblock {
                background: #27272a;
                border: 1px solid #3f3f46;
                color: #e4e4e7;
            }
            .fl-btn-unblock:hover {
                background: #3f3f46;
                color: #ffffff;
            }
            .fl-btn-clear {
                background: transparent;
                border: 1px solid #3f3f46;
                color: #a1a1aa;
            }
            .fl-btn-clear:hover {
                border-color: #ef4444;
                color: #ef4444;
            }
            .fl-cal-legend {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 1.5rem;
                padding: 0.75rem 0;
                margin-bottom: 0.5rem;
                font-size: 0.75rem;
                color: #a1a1aa;
                border-bottom: 1px solid #1f1f23;
            }
            .fl-legend-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .fl-dot-open {
                width: 0.85rem;
                height: 0.85rem;
                border-radius: 0.25rem;
                background: #27272a;
                border: 1px solid #52525b;
            }
            .fl-dot-closed {
                width: 0.85rem;
                height: 0.85rem;
                border-radius: 0.25rem;
                background: #dc2626;
                border: 1px solid #f87171;
            }
            .fl-grid-header {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 0.5rem;
                text-align: center;
                font-size: 0.75rem;
                font-weight: 700;
                color: #a1a1aa;
                padding: 0.5rem 0;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .fl-grid-header .fl-weekend {
                color: #fbbf24;
            }
            .fl-cal-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 0.5rem;
            }
            .fl-day-cell {
                height: 3.5rem;
                border-radius: 0.6rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                border: 1px solid #27272a;
                background: #1c1c20;
                color: #e4e4e7;
                cursor: pointer;
                transition: all 0.15s ease;
                padding: 0.25rem;
                user-select: none;
            }
            .fl-day-cell:hover {
                background: #27272a;
                border-color: #f59e0b;
                transform: translateY(-1px);
            }
            .fl-day-cell.disabled {
                background: #dc2626 !important;
                border-color: #f87171 !important;
                color: #ffffff !important;
                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
                transform: scale(1.02);
            }
            .fl-day-cell.disabled:hover {
                background: #b91c1c !important;
            }
            .fl-day-num {
                font-size: 1rem;
                font-weight: 700;
                line-height: 1;
            }
            .fl-day-status {
                font-size: 0.6rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                margin-top: 0.25rem;
                padding: 0.1rem 0.35rem;
                border-radius: 0.25rem;
            }
            .fl-status-open {
                color: #71717a;
            }
            .fl-day-cell:hover .fl-status-open {
                color: #fbbf24;
            }
            .fl-status-off {
                background: #7f1d1d;
                color: #ffffff;
            }
            .fl-cal-footer {
                margin-top: 1.5rem;
                padding-top: 1.25rem;
                border-top: 1px solid #27272a;
            }
            .fl-footer-title {
                font-size: 0.85rem;
                font-weight: 600;
                color: #d4d4d8;
                margin-bottom: 0.75rem;
            }
            .fl-badge-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0.4rem;
                max-height: 8rem;
                overflow-y: auto;
                padding-right: 0.5rem;
            }
            .fl-date-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                background: rgba(220, 38, 38, 0.2);
                border: 1px solid #dc2626;
                color: #fca5a5;
                font-size: 0.75rem;
                font-family: monospace;
                padding: 0.2rem 0.55rem;
                border-radius: 9999px;
            }
            .fl-date-badge-remove {
                background: transparent;
                border: none;
                color: #fca5a5;
                cursor: pointer;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.85rem;
                line-height: 1;
            }
            .fl-date-badge-remove:hover {
                color: #ffffff;
            }
        </style>

        <!-- Calendar Header -->
        <div class="fl-cal-header">
            <div class="fl-cal-title-wrap">
                <h3 class="fl-cal-title">
                    <span x-text="monthNames[currentMonth]"></span>
                    <span class="fl-cal-year" x-text="currentYear"></span>
                </h3>
                <div class="fl-cal-nav">
                    <button type="button" @click="prevMonth()" class="fl-cal-nav-btn" title="Previous Month">
                        &larr;
                    </button>
                    <button type="button" @click="nextMonth()" class="fl-cal-nav-btn" title="Next Month">
                        &rarr;
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="fl-cal-actions">
                <button
                    type="button"
                    @click="blockAllWeekendsInMonth()"
                    class="fl-btn-action fl-btn-block-weekends"
                >
                    Block Weekends
                </button>
                <button
                    type="button"
                    @click="unblockAllWeekendsInMonth()"
                    class="fl-btn-action fl-btn-unblock"
                >
                    Unblock Weekends
                </button>
                <button
                    type="button"
                    @click="clearAll()"
                    class="fl-btn-action fl-btn-clear"
                >
                    Clear All
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div class="fl-cal-legend">
            <div class="fl-legend-item">
                <span class="fl-dot-open"></span>
                <span>Open (Click to Block)</span>
            </div>
            <div class="fl-legend-item">
                <span class="fl-dot-closed"></span>
                <span style="color: #f87171; font-weight: 600;">Disabled / Blocked (Turned Off)</span>
            </div>
        </div>

        <!-- Days of Week Header -->
        <div class="fl-grid-header">
            <template x-for="dayName in dayNames" :key="dayName">
                <div x-text="dayName" :class="dayName === 'Sat' || dayName === 'Sun' ? 'fl-weekend' : ''"></div>
            </template>
        </div>

        <!-- Days Grid -->
        <div class="fl-cal-grid">
            <template x-for="(d, idx) in getDaysInMonth()" :key="idx">
                <div>
                    <template x-if="d.empty">
                        <div style="height: 3.5rem; opacity: 0;"></div>
                    </template>
                    <template x-if="!d.empty">
                        <button
                            type="button"
                            @click="toggleDate(d.dateStr)"
                            :class="isDisabled(d.dateStr) ? 'fl-day-cell disabled' : 'fl-day-cell'"
                            style="width: 100%;"
                        >
                            <span class="fl-day-num" x-text="d.day"></span>
                            
                            <template x-if="isDisabled(d.dateStr)">
                                <span class="fl-day-status fl-status-off">OFF</span>
                            </template>
                            <template x-if="!isDisabled(d.dateStr)">
                                <span class="fl-day-status fl-status-open">OPEN</span>
                            </template>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <!-- Blocked Dates Summary List -->
        <div class="fl-cal-footer">
            <div class="fl-footer-title">
                Total Disabled Dates: 
                <span style="color: #ef4444; font-weight: 700;" x-text="Array.isArray(state) ? state.length : 0"></span>
            </div>
            <div class="fl-badge-list">
                <template x-if="!Array.isArray(state) || state.length === 0">
                    <span style="font-size: 0.8rem; color: #71717a; font-style: italic;">No dates currently disabled.</span>
                </template>
                <template x-for="date in (Array.isArray(state) ? state : [])" :key="date">
                    <span class="fl-date-badge">
                        <span x-text="date"></span>
                        <button type="button" @click="toggleDate(date)" class="fl-date-badge-remove" title="Remove">&times;</button>
                    </span>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>
