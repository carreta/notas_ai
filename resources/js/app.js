document.addEventListener('alpine:init', () => {
    Alpine.data('datePicker', (maxDate) => ({
        maxDate: maxDate,
        maxYear: 0,
        minYear: 0,
        open: false,
        viewYear: 0,
        viewMonth: 0,
        dateHasInteracted: false,
        dateSubmitAttempted: false,
        localError: '',
        months: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ],
        weekdays: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],

        init() {
            const parts = this.maxDate.split('-').map(Number);
            const maxYear = parts[0];
            const maxMonth = parts[1] - 1;

            this.maxYear = maxYear;
            this.minYear = maxYear - 20;

            const sel = this.selectedIso;
            if (sel) {
                const d = new Date(sel + 'T00:00:00');
                this.viewYear = d.getFullYear();
                this.viewMonth = d.getMonth();
            } else {
                this.viewYear = maxYear;
                this.viewMonth = maxMonth;
            }

            this.$watch('$wire.meeting_date', (value) => {
                if (value) {
                    const d = new Date(value + 'T00:00:00');
                    this.viewYear = d.getFullYear();
                    this.viewMonth = d.getMonth();
                }
            });
        },

        get selectedIso() {
            return this.$wire.meeting_date || '';
        },

        iso(year, month, day) {
            return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        },

        get calendarDays() {
            const year = this.viewYear;
            const month = this.viewMonth;
            const first = new Date(year, month, 1);
            const startWeekday = first.getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const cells = [];
            for (let i = 0; i < startWeekday; i++) {
                cells.push({ empty: true });
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const iso = this.iso(year, month, day);
                const disabled = iso > this.maxDate;

                cells.push({
                    empty: false,
                    day: day,
                    iso: iso,
                    disabled: disabled,
                    isToday: iso === this.maxDate,
                    isSelected: iso === this.selectedIso,
                });
            }

            return cells;
        },

        monthDisabled(monthIndex) {
            const maxMonth = Number(this.maxDate.split('-')[1]) - 1;
            return this.viewYear === this.maxYear && monthIndex > maxMonth;
        },

        canGoPrev() {
            return this.viewYear > this.minYear || (this.viewYear === this.minYear && this.viewMonth > 0);
        },

        canGoNext() {
            const parts = this.maxDate.split('-').map(Number);
            return !(this.viewYear === this.maxYear && this.viewMonth === (parts[1] - 1));
        },

        prevMonth() {
            if (!this.canGoPrev()) return;
            if (this.viewMonth === 0) {
                this.viewMonth = 11;
                this.viewYear--;
            } else {
                this.viewMonth--;
            }
        },

        nextMonth() {
            if (!this.canGoNext()) return;
            if (this.viewMonth === 11) {
                this.viewMonth = 0;
                this.viewYear++;
            } else {
                this.viewMonth++;
            }
        },

        selectDay(cell) {
            if (!cell || cell.empty || cell.disabled) return;

            this.$wire.set('meeting_date', cell.iso);
            this.open = false;
            this.localError = '';
            this.dateHasInteracted = true;
        },

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
            this.checkError();
        },

        checkError() {
            this.dateHasInteracted = true;
            const val = this.selectedIso;

            if (!val) {
                this.localError = this.dateSubmitAttempted ? 'Meeting date is required.' : '';
            } else if (val > this.maxDate) {
                this.localError = 'The meeting date cannot be in the future.';
            } else {
                this.localError = '';
            }
        },

        get serverError() {
            const errs = this.$wire.errors;
            if (!errs) return null;
            if (Array.isArray(errs.meeting_date) && errs.meeting_date.length) {
                return errs.meeting_date[0];
            }
            if (errs.meeting_date) {
                return errs.meeting_date;
            }
            return null;
        },

        get errorMessage() {
            return this.serverError || this.localError;
        },

        get hasError() {
            return !!this.errorMessage;
        },

        displayValue() {
            const val = this.selectedIso;
            if (!val) return '';
            const d = new Date(val + 'T00:00:00');
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        },

        yearOptions() {
            const opts = [];
            for (let y = this.maxYear; y >= this.minYear; y--) {
                opts.push(y);
            }
            return opts;
        },
    }));
});
